<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

startSecureSession();
$checks = [];
$success = false;
$error = null;

try {
    foreach ([UPLOAD_ORIGINALS_DIR, UPLOAD_PROCESSED_DIR, UPLOAD_WATERMARKS_DIR, ZIP_DIR, LOG_DIR] as $dir) {
        $abs = ensureDirectory($dir);
        $checks[] = ['name' => 'Dossier ' . $dir, 'ok' => is_dir($abs) && is_writable($abs), 'detail' => $abs];
    }

    initializeDatabase();
    $checks[] = ['name' => 'Base MySQL ' . DB_NAME, 'ok' => true, 'detail' => 'Base et tables créées/vérifiées.'];
    $success = true;
} catch (Throwable $e) {
    $error = $e->getMessage();
    $checks[] = ['name' => 'Base MySQL ' . DB_NAME, 'ok' => false, 'detail' => $error];
}

$ffmpegPath = executablePath('ffmpeg');
$ffprobePath = executablePath('ffprobe');
$checks[] = ['name' => 'FFmpeg', 'ok' => $ffmpegPath !== null, 'detail' => $ffmpegPath ? 'Disponible : ' . $ffmpegPath : 'Introuvable pour PHP/Laragon. Si le terminal le trouve, renseigne FFMPEG_BINARY dans config/app.php.'];
$checks[] = ['name' => 'FFprobe', 'ok' => $ffprobePath !== null, 'detail' => $ffprobePath ? 'Disponible : ' . $ffprobePath : 'Introuvable pour PHP/Laragon. Si le terminal le trouve, renseigne FFPROBE_BINARY dans config/app.php.'];
$checks[] = ['name' => 'Extension ZipArchive', 'ok' => class_exists('ZipArchive'), 'detail' => class_exists('ZipArchive') ? 'Disponible' : 'Active l’extension php_zip'];
$checks[] = ['name' => 'cURL', 'ok' => function_exists('curl_init'), 'detail' => function_exists('curl_init') ? 'Disponible' : 'Active l’extension php_curl pour Mistral'];

require __DIR__ . '/templates/header.php';
?>
<section class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
    <h2 class="text-2xl font-bold mb-2">Installation / vérification</h2>
    <p class="text-slate-400 mb-6">Cette page crée automatiquement la base <strong><?= e(DB_NAME) ?></strong> et les tables nécessaires.</p>

    <?php if ($success): ?>
        <div class="mb-6 rounded-xl bg-emerald-900/60 border border-emerald-700 p-4 text-emerald-100">
            Installation OK. Tu peux ouvrir l’application.
        </div>
    <?php elseif ($error): ?>
        <div class="mb-6 rounded-xl bg-red-900/60 border border-red-700 p-4 text-red-100">
            Erreur : <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="space-y-3">
        <?php foreach ($checks as $check): ?>
            <div class="rounded-xl bg-slate-950 border border-slate-800 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                <div>
                    <strong><?= e($check['name']) ?></strong>
                    <p class="text-sm text-slate-500 break-all"><?= e($check['detail']) ?></p>
                </div>
                <span class="text-sm px-3 py-1 rounded-full <?= $check['ok'] ? 'bg-emerald-500/20 text-emerald-300' : 'bg-red-500/20 text-red-300' ?>">
                    <?= $check['ok'] ? 'OK' : 'À corriger' ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-6 flex gap-3">
        <a href="index.php" class="px-5 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold">Ouvrir MomoCut</a>
        <a href="database/schema.sql" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700">Voir le SQL</a>
    </div>
</section>
<?php require __DIR__ . '/templates/footer.php'; ?>
