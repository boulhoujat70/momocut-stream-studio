# MomoCut Stream Studio

Application PHP/MySQL pour automatiser la découpe de vidéos courtes, ajouter un watermark PNG et générer des métadonnées SEO/IA pour TikTok, YouTube Shorts, Instagram Reels et Facebook Reels.

## Fonctionnalités

- Upload de vidéo `.mp4` jusqu’à 2 Go.
- Découpe automatique par durée personnalisable.
- Watermark `.png` transparent optionnel en haut à droite, jusqu’à 150 Mo.
- Prévisualisation des segments générés.
- Téléchargement individuel ou archive ZIP.
- Génération de titres, description et hashtags via Mistral si une clé API est configurée.
- Fallback local si aucune clé Mistral n’est configurée.
- Création automatique de la base de données avec `install.php`.

## Installation avec Laragon

1. Supprimer l’ancien dossier si besoin :

   ```txt
   C:\laragon\www\momocut-stream-studio
   ```

2. Décompresser ce ZIP dans :

   ```txt
   C:\laragon\www\
   ```

   Le chemin final doit être :

   ```txt
   C:\laragon\www\momocut-stream-studio\index.php
   ```

3. Ouvrir Laragon puis cliquer sur **Start All**.

4. Ouvrir l’installateur :

   ```txt
   http://localhost/momocut-stream-studio/install.php
   ```

5. Ouvrir l’application :

   ```txt
   http://localhost/momocut-stream-studio/
   ```

## Important pour éviter l’erreur Forbidden

N’ouvre pas le dossier directement depuis l’explorateur Windows et ne lance pas le fichier `index.php` par double-clic.

Il faut passer par le navigateur avec :

```txt
http://localhost/momocut-stream-studio/
```

Si tu vois encore **Forbidden**, vérifie que le fichier suivant existe :

```txt
C:\laragon\www\momocut-stream-studio\index.php
```

## Base de données

L’application crée automatiquement la base `momocut_db` et les tables nécessaires avec `install.php`.

Configuration par défaut Laragon :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'momocut_db');
define('DB_USER', 'root');
define('DB_PASS', '');
```

Tu peux aussi importer manuellement :

```txt
database/schema.sql
```

## FFmpeg

La découpe vidéo nécessite `ffmpeg` et `ffprobe` dans le PATH Windows.

Dans Laragon, après installation de FFmpeg, redémarre Laragon puis ouvre :

```txt
http://localhost/momocut-stream-studio/install.php
```

## Clé API Mistral optionnelle

Dans :

```txt
config/mistral_api.php
```

mets ta clé ici :

```php
define('MISTRAL_API_KEY', 'ta_cle_api');
```

Sans clé, l’application génère quand même des métadonnées locales de secours.

## Structure

```txt
momocut-stream-studio/
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
├── config/
├── database/
├── includes/
├── modules/
├── storage/
├── templates/
├── index.php
├── install.php
├── .htaccess
└── README.md
```


## Limites d’upload configurées

Cette version accepte :

- Vidéo MP4 : jusqu’à **2 Go**.
- Watermark PNG : jusqu’à **150 Mo**.

Si Laragon/PHP bloque encore les gros fichiers, ouvre `Menu > PHP > php.ini`, puis vérifie :

```ini
upload_max_filesize = 2048M
post_max_size = 2300M
max_execution_time = 0
max_input_time = 0
memory_limit = 3072M
```

Redémarre ensuite Laragon avec **Stop All**, puis **Start All**.


## Correction FFmpeg Laragon

Cette version détecte FFmpeg/FFprobe même quand Laragon/PHP ne voit pas le PATH Windows. Elle scanne les chemins classiques `C:/ffmpeg/bin`, `C:/Program Files/...` et les installations Winget/Gyan dans `AppData/Local/Microsoft/WinGet/Packages`.

Si besoin, renseigner les chemins exacts dans `config/app.php` :

```php
define('FFMPEG_BINARY', 'C:/chemin/vers/ffmpeg.exe');
define('FFPROBE_BINARY', 'C:/chemin/vers/ffprobe.exe');
```

Limites : vidéo 2 Go, watermark PNG 150 Mo.
