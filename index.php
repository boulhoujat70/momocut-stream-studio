<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/modules/momocut.php';
require_once __DIR__ . '/modules/assistant_ia.php';
require_once __DIR__ . '/modules/download.php';

startSecureSession();

$alert = null;
$dbReady = false;

try {
    initializeDatabase();
    $dbReady = true;
} catch (Throwable $e) {
    $alert = ['type' => 'error', 'message' => 'Base de données non prête : ' . $e->getMessage()];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'download_segment') {
        downloadSegment((int)($_GET['id'] ?? 0));
    }
    if ($action === 'download_zip') {
        downloadVideoZip((int)($_GET['video_id'] ?? 0));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $alert = ['type' => 'error', 'message' => 'Session expirée. Recharge la page puis réessaie.'];
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'process_video') {
            $duration = (int)($_POST['segment_duration'] ?? 60);
            $result = processMomoCut($_FILES['video'] ?? [], $_FILES['watermark'] ?? null, $duration);

            if ($result['success']) {
                header('Location: index.php?video_id=' . (int)$result['video_id'] . '&ok=' . urlencode($result['message']));
                exit;
            }
            $alert = ['type' => 'error', 'message' => $result['message']];
        }

        if ($action === 'generate_metadata') {
            $videoId = (int)($_POST['video_id'] ?? 0);
            $description = sanitizeInput($_POST['video_description'] ?? '');
            if ($videoId <= 0 || $description === '') {
                $alert = ['type' => 'error', 'message' => 'Description vidéo obligatoire.'];
            } else {
                $result = generateVideoMetadata($videoId, $description);
                header('Location: index.php?video_id=' . $videoId . '&ok=' . urlencode($result['message']));
                exit;
            }
        }
    }
}

if (isset($_GET['ok'])) {
    $alert = ['type' => 'success', 'message' => sanitizeInput($_GET['ok'])];
}

$selectedVideoId = (int)($_GET['video_id'] ?? 0);
$selectedVideo = $dbReady && $selectedVideoId > 0 ? getVideoById($selectedVideoId) : null;
$segments = $selectedVideo ? getVideoSegmentsById($selectedVideoId) : [];
$metadataRows = $selectedVideo ? getVideoMetadataById($selectedVideoId) : [];
$recentVideos = $dbReady ? getRecentVideos(12) : [];
$ffmpegOk = commandExists('ffmpeg');
$ffprobeOk = commandExists('ffprobe');
$zipOk = class_exists('ZipArchive');

require __DIR__ . '/templates/header.php';
?>

<?php if ($alert): ?>
    <div class="mb-6 rounded-xl p-4 <?= $alert['type'] === 'success' ? 'bg-emerald-900/60 border border-emerald-700 text-emerald-100' : 'bg-red-900/60 border border-red-700 text-red-100' ?>">
        <?= e($alert['message']) ?>
    </div>
<?php endif; ?>

<section class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <h2 class="text-xl font-bold">1. Découper une vidéo</h2>
                <p class="text-slate-400 text-sm mt-1">Upload MP4, segmentation automatique et watermark PNG optionnel.</p>
            </div>
            <span class="text-xs px-3 py-1 rounded-full bg-indigo-500/20 text-indigo-200">Max 2 Go</span>
        </div>

        <form action="index.php" method="post" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="process_video">

            <div>
                <label class="block text-sm font-medium mb-2">Vidéo MP4</label>
                <input required type="file" name="video" accept="video/mp4,.mp4" class="block w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm">
                <p class="text-slate-500 text-xs mt-2" data-video-name>Aucun fichier sélectionné</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Watermark PNG transparent <span class="text-slate-500">optionnel — max 150 Mo</span></label>
                <input type="file" name="watermark" accept="image/png,.png" class="block w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Durée de chaque segment</label>
                <div class="flex items-center gap-3">
                    <input type="number" name="segment_duration" value="60" min="5" max="600" class="w-32 rounded-xl bg-slate-950 border border-slate-700 px-4 py-3">
                    <span class="text-slate-400 text-sm">secondes</span>
                </div>
            </div>

            <button type="submit" class="w-full md:w-auto px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 font-semibold disabled:opacity-50" <?= $dbReady ? '' : 'disabled' ?>>
                Lancer MomoCut
            </button>
        </form>
    </div>

    <aside class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
        <h2 class="text-xl font-bold mb-4">État du serveur</h2>
        <div class="space-y-3 text-sm">
            <div class="flex justify-between gap-3"><span>MySQL / tables</span><strong class="<?= $dbReady ? 'text-emerald-400' : 'text-red-400' ?>"><?= $dbReady ? 'OK' : 'Erreur' ?></strong></div>
            <div class="flex justify-between gap-3"><span>FFmpeg</span><strong class="<?= $ffmpegOk ? 'text-emerald-400' : 'text-yellow-400' ?>"><?= $ffmpegOk ? 'OK' : 'À installer' ?></strong></div>
            <div class="flex justify-between gap-3"><span>FFprobe</span><strong class="<?= $ffprobeOk ? 'text-emerald-400' : 'text-yellow-400' ?>"><?= $ffprobeOk ? 'OK' : 'À installer' ?></strong></div>
            <div class="flex justify-between gap-3"><span>ZipArchive</span><strong class="<?= $zipOk ? 'text-emerald-400' : 'text-yellow-400' ?>"><?= $zipOk ? 'OK' : 'Activer php_zip' ?></strong></div>
        </div>
        <a href="install.php" class="mt-5 inline-block text-sm text-indigo-300 hover:text-indigo-200">Ouvrir l’installateur →</a>
    </aside>
</section>

<?php if ($selectedVideo): ?>
<section class="mt-8 rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold">Résultat : <?= e($selectedVideo['original_filename']) ?></h2>
            <p class="text-slate-400 text-sm">Statut : <?= e($selectedVideo['status']) ?> · Durée segment : <?= (int)$selectedVideo['segment_duration'] ?>s</p>
        </div>
        <?php if (!empty($segments)): ?>
            <a class="px-5 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 font-semibold text-center" href="index.php?action=download_zip&video_id=<?= (int)$selectedVideo['id'] ?>">Télécharger le ZIP</a>
        <?php endif; ?>
    </div>

    <?php if (!empty($segments)): ?>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
            <?php foreach ($segments as $segment): ?>
                <article class="rounded-xl bg-slate-950 border border-slate-800 overflow-hidden">
                    <video controls preload="metadata" class="w-full aspect-video" src="<?= e(urlFor($segment['segment_path'])) ?>"></video>
                    <div class="p-4">
                        <h3 class="font-semibold">Segment <?= (int)$segment['segment_number'] ?></h3>
                        <p class="text-slate-500 text-sm mb-3"><?= e($segment['segment_filename']) ?></p>
                        <a class="inline-flex px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm" href="index.php?action=download_segment&id=<?= (int)$segment['id'] ?>">Télécharger</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-slate-400">Aucun segment disponible pour cette vidéo.</p>
    <?php endif; ?>
</section>

<section class="mt-8 grid lg:grid-cols-2 gap-6">
    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
        <h2 class="text-xl font-bold mb-4">2. Générer titres, description et hashtags</h2>
        <form action="index.php" method="post" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" value="generate_metadata">
            <input type="hidden" name="video_id" value="<?= (int)$selectedVideo['id'] ?>">
            <textarea required name="video_description" rows="5" placeholder="Exemple : résumé de la vidéo, sujet, audience, ton..." class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-3 text-sm"></textarea>
            <button type="submit" class="px-6 py-3 rounded-xl bg-fuchsia-600 hover:bg-fuchsia-500 font-semibold">Générer avec IA</button>
            <p class="text-xs text-slate-500">Sans clé Mistral, l’application génère des métadonnées locales de secours.</p>
        </form>
    </div>

    <div class="rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
        <h2 class="text-xl font-bold mb-4">Métadonnées générées</h2>
        <?php if (empty($metadataRows)): ?>
            <p class="text-slate-400 text-sm">Aucune métadonnée pour le moment.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($metadataRows as $meta): ?>
                    <article class="rounded-xl bg-slate-950 border border-slate-800 p-4">
                        <div id="meta-<?= (int)$meta['id'] ?>" class="space-y-2 text-sm">
                            <p><strong>Titre :</strong> <?= e($meta['title']) ?></p>
                            <p><strong>Description :</strong> <?= e($meta['description']) ?></p>
                            <p><strong>Hashtags :</strong> <?= e($meta['hashtags']) ?></p>
                        </div>
                        <button type="button" data-copy="#meta-<?= (int)$meta['id'] ?>" class="mt-3 px-3 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs">Copier</button>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="mt-8 rounded-2xl bg-slate-900 border border-slate-800 p-6 shadow-xl">
    <h2 class="text-xl font-bold mb-4">Historique récent</h2>
    <?php if (empty($recentVideos)): ?>
        <p class="text-slate-400 text-sm">Aucune vidéo traitée.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="text-left text-slate-400">
                    <tr>
                        <th class="py-3 pr-4">Vidéo</th>
                        <th class="py-3 pr-4">Statut</th>
                        <th class="py-3 pr-4">Segments</th>
                        <th class="py-3 pr-4">Date</th>
                        <th class="py-3 pr-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php foreach ($recentVideos as $video): ?>
                        <tr>
                            <td class="py-3 pr-4"><?= e($video['original_filename']) ?></td>
                            <td class="py-3 pr-4"><?= e($video['status']) ?></td>
                            <td class="py-3 pr-4"><?= (int)$video['segment_count'] ?></td>
                            <td class="py-3 pr-4 text-slate-500"><?= e($video['created_at']) ?></td>
                            <td class="py-3 pr-4"><a class="text-indigo-300 hover:text-indigo-200" href="index.php?video_id=<?= (int)$video['id'] ?>">Voir</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/templates/footer.php'; ?>
