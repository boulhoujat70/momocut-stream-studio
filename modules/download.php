<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/momocut.php';

function getSegmentById(int $segmentId): ?array
{
    initializeDatabase();
    $pdo = getPdo(true);
    $stmt = $pdo->prepare("SELECT * FROM video_segments WHERE id = ?");
    $stmt->execute([$segmentId]);
    $segment = $stmt->fetch();
    return $segment ?: null;
}

function forceFileDownload(string $absolutePath, string $downloadName): void
{
    if (!is_file($absolutePath)) {
        http_response_code(404);
        echo 'Fichier introuvable.';
        exit;
    }

    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    header('Content-Length: ' . filesize($absolutePath));
    header('X-Content-Type-Options: nosniff');
    readfile($absolutePath);
    exit;
}

function downloadSegment(int $segmentId): void
{
    $segment = getSegmentById($segmentId);
    if (!$segment) {
        http_response_code(404);
        echo 'Segment introuvable.';
        exit;
    }

    forceFileDownload(absPath($segment['segment_path']), $segment['segment_filename']);
}

function createZipArchiveForVideo(int $videoId): string
{
    $segments = getVideoSegmentsById($videoId);
    if (empty($segments)) {
        throw new RuntimeException('Aucun segment à zipper.');
    }

    if (!class_exists('ZipArchive')) {
        throw new RuntimeException('L’extension PHP ZipArchive n’est pas activée. Active php_zip dans Laragon.');
    }

    $zipDir = ensureDirectory(ZIP_DIR);
    $zipPath = $zipDir . 'momocut_video_' . $videoId . '_' . time() . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Impossible de créer l’archive ZIP.');
    }

    foreach ($segments as $segment) {
        $abs = absPath($segment['segment_path']);
        if (is_file($abs)) {
            $zip->addFile($abs, $segment['segment_filename']);
        }
    }

    $zip->close();
    return $zipPath;
}

function downloadVideoZip(int $videoId): void
{
    $zipPath = createZipArchiveForVideo($videoId);
    forceFileDownload($zipPath, basename($zipPath));
}
?>
