# Spécification originale utilisée

Ce projet reprend la demande MomoCut Stream Studio :

- Application web PHP/MySQL.
- Découpe automatique de vidéos pour réseaux sociaux.
- Watermark PNG transparent.
- Métadonnées virales via IA Mistral.
- Téléchargement individuel et archive ZIP.
- Technologies : PHP 8.2+, MySQL, Tailwind CSS, JavaScript vanilla, FFmpeg, cURL.

Le ZIP fourni ajoute un installateur automatique `install.php` pour éviter l’erreur `Unknown database 'momocut_db'` et inclut un `index.php` racine pour éviter l’erreur Apache `Forbidden` quand le projet est ouvert correctement via Laragon.
