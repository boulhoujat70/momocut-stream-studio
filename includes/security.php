<?php
function sanitizeInput(string $data): string
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function csrfToken(): string
{
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    startSecureSession();
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isUploadedFilePresent(array $file): bool
{
    return isset($file['error']) && $file['error'] !== UPLOAD_ERR_NO_FILE;
}

function uploadErrorMessage(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_OK => 'Upload réussi.',
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux.',
        UPLOAD_ERR_PARTIAL => 'Le fichier a été envoyé partiellement.',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier reçu.',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur.',
        UPLOAD_ERR_CANT_WRITE => 'Impossible d’écrire le fichier sur le disque.',
        UPLOAD_ERR_EXTENSION => 'Une extension PHP a bloqué l’upload.',
        default => 'Erreur inconnue lors de l’upload.',
    };
}

function detectMimeType(string $tmpPath): string
{
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmpPath) ?: '';
        finfo_close($finfo);
        return $mime;
    }
    return mime_content_type($tmpPath) ?: '';
}

function isValidVideo(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, uploadErrorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }

    if (($file['size'] ?? 0) > MAX_VIDEO_SIZE) {
        return [false, 'La vidéo dépasse la limite de 2 Go.'];
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension !== 'mp4') {
        return [false, 'Format vidéo non accepté. Utilise un fichier .mp4.'];
    }

    $mime = detectMimeType($file['tmp_name']);
    $accepted = ['video/mp4', 'application/mp4', 'application/octet-stream'];
    if (!in_array($mime, $accepted, true) && !str_starts_with($mime, 'video/')) {
        return [false, 'Type de fichier vidéo non reconnu : ' . $mime];
    }

    return [true, 'OK'];
}

function isValidWatermark(array $file): array
{
    if (!isUploadedFilePresent($file)) {
        return [true, 'Aucun watermark fourni.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [false, uploadErrorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
    }

    if (($file['size'] ?? 0) > MAX_WATERMARK_SIZE) {
        return [false, 'Le watermark dépasse la limite de 150 Mo.'];
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension !== 'png') {
        return [false, 'Le watermark doit être une image .png transparente.'];
    }

    $mime = detectMimeType($file['tmp_name']);
    if (!in_array($mime, ['image/png', 'application/octet-stream'], true)) {
        return [false, 'Type de watermark non reconnu : ' . $mime];
    }

    return [true, 'OK'];
}
?>
