<?php
require_once __DIR__ . '/../config/mistral_api.php';
require_once __DIR__ . '/functions.php';

function fallbackMetadata(string $videoDescription): array
{
    $base = trim($videoDescription) !== '' ? trim($videoDescription) : 'Nouvelle vidéo';
    $short = mb_substr($base, 0, 45);

    return [
        'titles' => [
            $short . ' | À ne pas manquer',
            'Le moment fort : ' . mb_substr($base, 0, 35),
            'Regarde ça avant tout le monde',
        ],
        'description' => 'Découvrez cette vidéo optimisée pour les formats courts. Un extrait dynamique, clair et prêt à publier sur TikTok, YouTube Shorts, Instagram Reels et Facebook Reels.',
        'hashtags' => ['shorts', 'reels', 'tiktok', 'viral', 'video', 'contentcreator', 'momocut', 'trending', 'clips', 'socialmedia'],
        'source' => 'fallback',
    ];
}

function generateMetadata(string $videoDescription): array
{
    if (trim(MISTRAL_API_KEY) === '') {
        return fallbackMetadata($videoDescription);
    }

    $prompt = "Tu es un assistant de marketing vidéo. Pour cette vidéo : \"{$videoDescription}\", génère uniquement un JSON valide avec les clés titles, description, hashtags. titles doit contenir 3 titres viraux de moins de 60 caractères. description doit faire moins de 500 caractères. hashtags doit contenir 10 hashtags sans #.";

    $data = [
        'model' => MISTRAL_MODEL,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.7,
        'response_format' => ['type' => 'json_object'],
    ];

    $ch = curl_init(MISTRAL_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . MISTRAL_API_KEY,
        ],
        CURLOPT_TIMEOUT => 45,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        writeLog('Erreur Mistral HTTP ' . $httpCode . ' : ' . $curlError . ' / ' . (string)$response);
        return fallbackMetadata($videoDescription);
    }

    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    $json = json_decode($content, true);

    if (!is_array($json)) {
        writeLog('Réponse Mistral non JSON : ' . $content);
        return fallbackMetadata($videoDescription);
    }

    $titles = array_values(array_filter(array_map('trim', $json['titles'] ?? [])));
    $description = trim((string)($json['description'] ?? ''));
    $hashtags = array_values(array_filter(array_map(static function ($tag) {
        return trim(str_replace('#', '', (string)$tag));
    }, $json['hashtags'] ?? [])));

    if (count($titles) < 1 || $description === '' || count($hashtags) < 1) {
        return fallbackMetadata($videoDescription);
    }

    return [
        'titles' => array_slice($titles, 0, 3),
        'description' => mb_substr($description, 0, 500),
        'hashtags' => array_slice($hashtags, 0, 10),
        'source' => 'mistral',
    ];
}
?>
