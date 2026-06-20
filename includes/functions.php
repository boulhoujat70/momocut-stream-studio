<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/security.php';

function projectRoot(): string
{
    return realpath(__DIR__ . '/..') ?: dirname(__DIR__);
}

function normalizePath(string $path): string
{
    return str_replace('\\', '/', $path);
}

function ensureDirectory(string $relativeDir): string
{
    $path = projectRoot() . '/' . trim($relativeDir, '/') . '/';
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
    return $path;
}

function appBaseUrl(): string
{
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    return rtrim(str_replace('\\', '/', $scriptDir), '/');
}

function urlFor(string $relativePath): string
{
    $relativePath = ltrim(normalizePath($relativePath), '/');
    $parts = array_map('rawurlencode', explode('/', $relativePath));
    return appBaseUrl() . '/' . implode('/', $parts);
}

function absPath(string $relativePath): string
{
    return projectRoot() . '/' . ltrim(normalizePath($relativePath), '/');
}

function generateUniqueFilename(string $prefix = ''): string
{
    return $prefix . bin2hex(random_bytes(8)) . '_' . time();
}

function safeOriginalName(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    $name = preg_replace('/[^a-zA-Z0-9_\-]+/', '-', $name);
    $name = trim($name, '-');
    return $name !== '' ? substr($name, 0, 80) : 'video';
}

function uploadFile(array $file, string $targetRelativeDir, string $prefix = ''): array
{
    $targetDir = ensureDirectory($targetRelativeDir);
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $basename = safeOriginalName($file['name']);
    $filename = generateUniqueFilename($prefix . $basename . '_') . '.' . $extension;
    $absolutePath = $targetDir . $filename;
    $relativePath = trim($targetRelativeDir, '/') . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossible de déplacer le fichier uploadé.');
    }

    return [
        'filename' => $filename,
        'absolute_path' => $absolutePath,
        'relative_path' => $relativePath,
    ];
}

function executablePath(string $command): ?string
{
    static $cache = [];

    $command = strtolower(trim($command));
    if (isset($cache[$command])) {
        return $cache[$command];
    }

    $constantName = match ($command) {
        'ffmpeg' => 'FFMPEG_BINARY',
        'ffprobe' => 'FFPROBE_BINARY',
        default => '',
    };

    $exe = PHP_OS_FAMILY === 'Windows' ? $command . '.exe' : $command;
    $candidates = [];

    if ($constantName !== '' && defined($constantName) && trim((string)constant($constantName)) !== '') {
        $candidates[] = trim((string)constant($constantName));
    }

    // Commande disponible dans le PATH.
    $candidates[] = $command;

    if (PHP_OS_FAMILY === 'Windows') {
        $programFiles = getenv('ProgramFiles') ?: 'C:/Program Files';
        $programFilesX86 = getenv('ProgramFiles(x86)') ?: 'C:/Program Files (x86)';
        $localAppData = getenv('LOCALAPPDATA') ?: '';
        $userProfile = getenv('USERPROFILE') ?: '';

        $candidates = array_merge($candidates, [
            'C:/ffmpeg/bin/' . $exe,
            'C:/FFmpeg/bin/' . $exe,
            'C:/laragon/bin/ffmpeg/bin/' . $exe,
            $programFiles . '/ffmpeg/bin/' . $exe,
            $programFiles . '/Gyan/FFmpeg/bin/' . $exe,
            $programFilesX86 . '/ffmpeg/bin/' . $exe,
        ]);

        $globPatterns = [];
        if ($localAppData !== '') {
            $globPatterns[] = normalizePath($localAppData) . '/Microsoft/WinGet/Packages/Gyan.FFmpeg*/ffmpeg-*/bin/' . $exe;
        }
        if ($userProfile !== '') {
            $globPatterns[] = normalizePath($userProfile) . '/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg*/ffmpeg-*/bin/' . $exe;
        }
        $globPatterns[] = 'C:/Users/*/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg*/ffmpeg-*/bin/' . $exe;

        foreach ($globPatterns as $pattern) {
            foreach (glob($pattern) ?: [] as $path) {
                $candidates[] = normalizePath($path);
            }
        }
    }

    foreach (array_unique(array_filter($candidates)) as $candidate) {
        $candidate = normalizePath($candidate);
        $isDirectCommand = $candidate === $command;
        $cmd = ($isDirectCommand ? $candidate : escapeshellarg($candidate)) . ' -version 2>&1';

        if (!$isDirectCommand && !is_file($candidate)) {
            continue;
        }

        $output = [];
        $code = 1;
        @exec($cmd, $output, $code);
        if ($code === 0) {
            return $cache[$command] = $candidate;
        }
    }

    return $cache[$command] = null;
}

function commandExists(string $command): bool
{
    return executablePath($command) !== null;
}

function commandForShell(string $command): string
{
    $path = executablePath($command) ?? $command;
    return $path === $command ? $command : escapeshellarg($path);
}

function secondsToTime(float|int $seconds): string
{
    $seconds = max(0, (int)round($seconds));
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    return $h > 0 ? sprintf('%02d:%02d:%02d', $h, $m, $s) : sprintf('%02d:%02d', $m, $s);
}

function getVideoDuration(string $videoPath): float
{
    if (!commandExists('ffprobe')) {
        throw new RuntimeException('FFprobe est introuvable. Installe FFmpeg puis redémarre Laragon.');
    }

    $command = commandForShell('ffprobe') . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($videoPath);
    $output = shell_exec($command);
    $duration = is_string($output) ? (float)trim($output) : 0.0;

    if ($duration <= 0) {
        throw new RuntimeException('Impossible de lire la durée de la vidéo avec FFprobe.');
    }

    return $duration;
}

function calculateSegmentCount(string $videoPath, int $segmentDuration): int
{
    $duration = getVideoDuration($videoPath);
    return max(1, (int)ceil($duration / $segmentDuration));
}

function writeLog(string $message): void
{
    $dir = ensureDirectory(LOG_DIR);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
    file_put_contents($dir . 'momocut.log', $line, FILE_APPEND);
}
?>
