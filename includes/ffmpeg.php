<?php
require_once __DIR__ . '/functions.php';

function processVideo(string $videoAbsPath, ?string $watermarkAbsPath, int $segmentDuration): array
{
    $ffmpegBinary = commandForShell('ffmpeg');

    if (!commandExists('ffmpeg')) {
        throw new RuntimeException('FFmpeg est introuvable pour PHP/Laragon. Ouvre install.php pour voir les chemins détectés.');
    }

    if ($segmentDuration < MIN_SEGMENT_DURATION || $segmentDuration > MAX_SEGMENT_DURATION) {
        throw new RuntimeException('Durée de segment invalide.');
    }

    $segmentCount = calculateSegmentCount($videoAbsPath, $segmentDuration);
    $videoFilename = safeOriginalName(pathinfo($videoAbsPath, PATHINFO_FILENAME));
    $outputDir = ensureDirectory(UPLOAD_PROCESSED_DIR);
    $processedSegments = [];

    for ($i = 0; $i < $segmentCount; $i++) {
        $startTime = $i * $segmentDuration;
        $segmentNumber = $i + 1;
        $segmentFilename = $videoFilename . '_segment_' . $segmentNumber . '_' . time() . '.mp4';
        $segmentAbsPath = $outputDir . $segmentFilename;
        $segmentRelPath = trim(UPLOAD_PROCESSED_DIR, '/') . '/' . $segmentFilename;

        $command = $ffmpegBinary . ' -hide_banner -loglevel error -y '
            . '-ss ' . escapeshellarg((string)$startTime) . ' '
            . '-i ' . escapeshellarg($videoAbsPath) . ' ';

        if ($watermarkAbsPath && is_file($watermarkAbsPath)) {
            $command .= '-i ' . escapeshellarg($watermarkAbsPath) . ' '
                . '-filter_complex ' . escapeshellarg('[1:v]scale=iw*0.20:-1[wm];[0:v][wm]overlay=main_w-overlay_w-20:20') . ' ';
        }

        $command .= '-t ' . escapeshellarg((string)$segmentDuration) . ' '
            . '-map 0:a? '
            . '-c:v libx264 -crf 20 -preset veryfast '
            . '-c:a aac -b:a 128k '
            . '-movflags +faststart '
            . escapeshellarg($segmentAbsPath) . ' 2>&1';

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode === 0 && is_file($segmentAbsPath) && filesize($segmentAbsPath) > 0) {
            $processedSegments[] = [
                'filename' => $segmentFilename,
                'absolute_path' => $segmentAbsPath,
                'relative_path' => $segmentRelPath,
                'segment_number' => $segmentNumber,
                'duration' => $segmentDuration,
            ];
        } else {
            $error = 'Erreur FFmpeg segment ' . $segmentNumber . ' : ' . implode("\n", $output);
            writeLog($error);
            throw new RuntimeException($error);
        }
    }

    return $processedSegments;
}
?>
