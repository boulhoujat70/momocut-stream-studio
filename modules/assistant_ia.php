<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/mistral.php';

function generateVideoMetadata(int $videoId, string $videoDescription): array
{
    initializeDatabase();
    $pdo = getPdo(true);

    $metadata = generateMetadata($videoDescription);
    $firstTitle = $metadata['titles'][0] ?? 'Vidéo optimisée';

    $stmt = $pdo->prepare("INSERT INTO video_metadata (video_id, title, description, hashtags) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $videoId,
        $firstTitle,
        $metadata['description'],
        implode(',', $metadata['hashtags']),
    ]);

    return [
        'success' => true,
        'message' => 'Métadonnées générées avec succès.',
        'metadata' => $metadata,
    ];
}
?>
