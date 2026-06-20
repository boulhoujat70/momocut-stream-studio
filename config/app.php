<?php
// Configuration générale de MomoCut Stream Studio

define('APP_NAME', 'MomoCut Stream Studio');
define('APP_VERSION', '1.0.0');

define('MAX_VIDEO_SIZE', 2 * 1024 * 1024 * 1024); // 2 Go
define('MAX_WATERMARK_SIZE', 150 * 1024 * 1024); // 150 Mo

define('UPLOAD_ORIGINALS_DIR', 'assets/uploads/originals/');
define('UPLOAD_PROCESSED_DIR', 'assets/uploads/processed/');
define('UPLOAD_WATERMARKS_DIR', 'assets/uploads/watermarks/');
define('ZIP_DIR', 'storage/zips/');
define('LOG_DIR', 'storage/logs/');

// Laisse vide pour détection automatique.
// Si Laragon ne trouve pas FFmpeg malgré l'installation, indique les chemins complets ici, par exemple :
// define('FFMPEG_BINARY', 'C:/Users/TonNom/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe/ffmpeg-8.0-full_build/bin/ffmpeg.exe');
// define('FFPROBE_BINARY', 'C:/Users/TonNom/AppData/Local/Microsoft/WinGet/Packages/Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe/ffmpeg-8.0-full_build/bin/ffprobe.exe');
define('FFMPEG_BINARY', '');
define('FFPROBE_BINARY', '');

// Durées acceptées pour un segment vidéo en secondes.
define('MIN_SEGMENT_DURATION', 5);
define('MAX_SEGMENT_DURATION', 600);
?>
