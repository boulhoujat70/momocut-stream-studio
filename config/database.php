<?php
require_once __DIR__ . '/app.php';

// Configuration MySQL pour Laragon par défaut.
// Tu peux modifier ces valeurs si ton MySQL utilise un autre utilisateur ou mot de passe.
define('DB_HOST', getenv('MOMOCUT_DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('MOMOCUT_DB_NAME') ?: 'momocut_db');
define('DB_USER', getenv('MOMOCUT_DB_USER') ?: 'root');
define('DB_PASS', getenv('MOMOCUT_DB_PASS') ?: '');

function getPdo(bool $withDatabase = true): PDO
{
    $dbPart = $withDatabase ? ';dbname=' . DB_NAME : '';
    $dsn = 'mysql:host=' . DB_HOST . $dbPart . ';charset=utf8mb4';

    return new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function initializeDatabase(): void
{
    $pdoServer = getPdo(false);
    $dbName = str_replace('`', '``', DB_NAME);
    $pdoServer->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pdo = getPdo(true);

    $pdo->exec("CREATE TABLE IF NOT EXISTS `videos` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT DEFAULT 1,
      `original_filename` VARCHAR(255) NOT NULL,
      `original_path` VARCHAR(500) NOT NULL,
      `segment_duration` INT NOT NULL COMMENT 'Durée en secondes',
      `watermark_path` VARCHAR(500) DEFAULT NULL,
      `status` ENUM('pending', 'processing', 'completed', 'error') DEFAULT 'pending',
      `error_message` TEXT DEFAULT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `video_segments` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `video_id` INT NOT NULL,
      `segment_filename` VARCHAR(255) NOT NULL,
      `segment_path` VARCHAR(500) NOT NULL,
      `segment_number` INT NOT NULL,
      `duration` INT NOT NULL COMMENT 'Durée en secondes',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (`video_id`),
      FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `video_metadata` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `video_id` INT NOT NULL,
      `title` VARCHAR(255) NOT NULL,
      `description` TEXT NOT NULL,
      `hashtags` TEXT NOT NULL COMMENT 'Séparés par des virgules',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      INDEX (`video_id`),
      FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
?>
