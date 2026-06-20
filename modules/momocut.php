<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ffmpeg.php';

function processMomoCut(array $videoFile, ?array $watermarkFile, int $segmentDuration): array
{
    initializeDatabase();
    $pdo = getPdo(true);

    [$videoOk, $videoMessage] = isValidVideo($videoFile);
    if (!$videoOk) {
        return ['success' => false, 'message' => $videoMessage];
    }

    $watermarkFile = $watermarkFile ?? ['error' => UPLOAD_ERR_NO_FILE];
    [$watermarkOk, $watermarkMessage] = isValidWatermark($watermarkFile);
    if (!$watermarkOk) {
        return ['success' => false, 'message' => $watermarkMessage];
    }

    if ($segmentDuration < MIN_SEGMENT_DURATION || $segmentDuration > MAX_SEGMENT_DURATION) {
        return ['success' => false, 'message' => 'La durée doit être comprise entre ' . MIN_SEGMENT_DURATION . ' et ' . MAX_SEGMENT_DURATION . ' secondes.'];
    }

    try {
        $videoUpload = uploadFile($videoFile, UPLOAD_ORIGINALS_DIR, 'video_');
        $watermarkUpload = null;

        if (isUploadedFilePresent($watermarkFile)) {
            $watermarkUpload = uploadFile($watermarkFile, UPLOAD_WATERMARKS_DIR, 'watermark_');
        }

        $stmt = $pdo->prepare("INSERT INTO videos (original_filename, original_path, segment_duration, watermark_path, status) VALUES (?, ?, ?, ?, 'processing')");
        $stmt->execute([
            $videoFile['name'],
            $videoUpload['relative_path'],
            $segmentDuration,
            $watermarkUpload['relative_path'] ?? null,
        ]);
        $videoId = (int)$pdo->lastInsertId();

        $segments = processVideo(
            $videoUpload['absolute_path'],
            $watermarkUpload['absolute_path'] ?? null,
            $segmentDuration
        );

        if (empty($segments)) {
            throw new RuntimeException('Aucun segment n’a été généré.');
        }

        $stmtSegment = $pdo->prepare("INSERT INTO video_segments (video_id, segment_filename, segment_path, segment_number, duration) VALUES (?, ?, ?, ?, ?)");
        foreach ($segments as $segment) {
            $stmtSegment->execute([
                $videoId,
                $segment['filename'],
                $segment['relative_path'],
                $segment['segment_number'],
                $segment['duration'],
            ]);
        }

        $stmt = $pdo->prepare("UPDATE videos SET status = 'completed', error_message = NULL WHERE id = ?");
        $stmt->execute([$videoId]);

        return [
            'success' => true,
            'message' => count($segments) . ' segment(s) généré(s) avec succès.',
            'video_id' => $videoId,
            'segments' => $segments,
        ];
    } catch (Throwable $e) {
        if (!empty($videoId)) {
            $stmt = $pdo->prepare("UPDATE videos SET status = 'error', error_message = ? WHERE id = ?");
            $stmt->execute([$e->getMessage(), $videoId]);
        }
        writeLog($e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function getRecentVideos(int $limit = 10): array
{
    initializeDatabase();
    $pdo = getPdo(true);
    $stmt = $pdo->prepare("SELECT v.*, COUNT(s.id) AS segment_count FROM videos v LEFT JOIN video_segments s ON s.video_id = v.id GROUP BY v.id ORDER BY v.created_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getVideoById(int $videoId): ?array
{
    initializeDatabase();
    $pdo = getPdo(true);
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
    $stmt->execute([$videoId]);
    $video = $stmt->fetch();
    return $video ?: null;
}

function getVideoSegmentsById(int $videoId): array
{
    initializeDatabase();
    $pdo = getPdo(true);
    $stmt = $pdo->prepare("SELECT * FROM video_segments WHERE video_id = ? ORDER BY segment_number ASC");
    $stmt->execute([$videoId]);
    return $stmt->fetchAll();
}

function getVideoMetadataById(int $videoId): array
{
    initializeDatabase();
    $pdo = getPdo(true);
    $stmt = $pdo->prepare("SELECT * FROM video_metadata WHERE video_id = ? ORDER BY created_at DESC");
    $stmt->execute([$videoId]);
    return $stmt->fetchAll();
}
?>
