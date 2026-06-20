<?php require_once __DIR__ . '/../includes/security.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= e(urlFor('assets/css/tailwind.css')) ?>">
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
<header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur">
    <div class="max-w-6xl mx-auto px-4 py-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold">🎬 <?= e(APP_NAME) ?></h1>
            <p class="text-slate-400 text-sm">Découpe vidéo, watermark et métadonnées SEO/IA pour réseaux sociaux.</p>
        </div>
        <nav class="flex gap-3 text-sm">
            <a href="<?= e(urlFor('index.php')) ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500">Accueil</a>
            <a href="<?= e(urlFor('install.php')) ?>" class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700">Installer / vérifier</a>
        </nav>
    </div>
</header>
<main class="max-w-6xl mx-auto px-4 py-8">
