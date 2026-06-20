CREATE DATABASE IF NOT EXISTS `momocut_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `momocut_db`;

CREATE TABLE IF NOT EXISTS `videos` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `video_segments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` INT NOT NULL,
  `segment_filename` VARCHAR(255) NOT NULL,
  `segment_path` VARCHAR(500) NOT NULL,
  `segment_number` INT NOT NULL,
  `duration` INT NOT NULL COMMENT 'Durée en secondes',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`video_id`),
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `video_metadata` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `video_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `hashtags` TEXT NOT NULL COMMENT 'Séparés par des virgules',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`video_id`),
  FOREIGN KEY (`video_id`) REFERENCES `videos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
