<?php
use Postcardarchive\Controllers\PostcardController;
use Postcardarchive\Utils\UtilsEncryptor;
require_once __DIR__ . '/../vendor/autoload.php';

$postcard = null; $error = null; $frontDecrypted = null; $backDecrypted = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['key_file'])) {
    if ($_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
        $keyData = json_decode(file_get_contents($_FILES['key_file']['tmp_name']), true);
        if ($keyData && isset($keyData['stamp_code'], $keyData['private_key'])) {
            $postcard = PostcardController::getPostcardByStampCode($keyData['stamp_code']);
            if ($postcard) {
                try {
                    $privateKey = UtilsEncryptor::getPrivateKeyFromString($keyData['private_key']);
                    $frontDecrypted = UtilsEncryptor::decryptData($privateKey, $postcard->getFrontImage());
                    $backDecrypted  = UtilsEncryptor::decryptData($privateKey, $postcard->getBackImage());
                } catch (\Exception $e) { $error = "Schlüssel ungültig."; $postcard = null; }
            } else { $error = "Karte nicht gefunden."; }
        } else { $error = "Formatfehler."; }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postkarte öffnen | Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body class="min-h-screen pb-20">

    <nav class="p-8">
        <a href="index.php" class="group inline-flex items-center gap-3 text-sky-900 font-bold uppercase text-xs tracking-[0.2em]">
            <span class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-sky-900 group-hover:text-white transition-all">←</span>
            Home
        </a>
    </nav>

    <main class="max-w-5xl mx-auto px-6 flex flex-col items-center">
        <?php if (!$postcard): ?>
            <div class="glass-panel w-full max-w-md p-12 rounded-[3rem] text-center shadow-2xl animate-in zoom-in duration-500">
                <div class="text-6xl mb-8 animate-float">🔐</div>
                <h1 class="text-4xl font-serif italic text-sky-950 mb-4" style="font-family: 'Playfair Display', serif;">Privates Archiv</h1>
                <p class="text-stone-500 mb-10 font-light">Lade deine .json Schlüsseldatei hoch, um die Verschlüsselung zu lösen.</p>
                
                <form action="view.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <label class="block relative border-2 border-dashed border-stone-200 rounded-2xl p-8 hover:border-sky-500 hover:bg-white transition-all cursor-pointer group">
                        <input type="file" name="key_file" accept=".json" required class="hidden" onchange="this.nextElementSibling.innerText = this.files[0].name">
                        <span class="text-stone-400 text-xs font-bold tracking-widest uppercase">Schlüssel wählen</span>
                    </label>
                    <button type="submit" class="btn-travel w-full text-white py-5 rounded-2xl font-bold text-lg">Entschlüsseln</button>
                </form>
                <?php if ($error): ?> <p class="mt-4 text-red-500 text-xs italic"><?= $error ?></p> <?php endif; ?>
            </div>
        <?php else: ?>
            
            <div class="postcard-container cursor-pointer mb-12" onclick="this.classList.toggle('is-flipped')">
                <div class="postcard-inner">
                    <div class="postcard-front bg-white">
                        <img src="data:image/webp;base64,<?= base64_encode($frontDecrypted) ?>" class="w-full h-full object-cover" alt="Vorderseite">
                    </div>
                    <div class="postcard-back flex p-8">
                        <div class="w-2/3 border-r border-stone-200 pr-8 flex items-center justify-center">
                             <img src="data:image/webp;base64,<?= base64_encode($backDecrypted) ?>" class="max-w-full max-h-full object-contain rotate-[-1deg] drop-shadow-md" alt="Rückseite">
                        </div>
                        <div class="w-1/3 pl-8 flex flex-col">
                            <div class="w-16 h-20 border-2 border-dashed border-stone-300 rounded self-end mb-8 flex items-center justify-center text-stone-300 text-[8px] uppercase text-center font-bold">
                                Approved<br>Archive<br>2026
                            </div>
                            <div class="space-y-4 mt-auto opacity-30">
                                <div class="h-px bg-stone-400 w-full"></div>
                                <div class="h-px bg-stone-400 w-full"></div>
                                <div class="h-px bg-stone-400 w-3/4"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-stone-400 text-[10px] uppercase tracking-[0.4em] mb-16 animate-pulse">Karte antippen zum Umdrehen</p>

            <?php if ($postcard->getLatitude()): ?>
                <div class="w-full space-y-6 animate-in slide-in-from-bottom-10 duration-1000">
                    <h3 class="text-2xl font-serif italic text-sky-950 flex items-center gap-3 ml-2" style="font-family: 'Playfair Display', serif;">
                        <span class="text-3xl">📍</span> Wo diese Erinnerung entstand
                    </h3>
                    <div id="viewMap" class="h-96 w-full rounded-[3rem] shadow-2xl border-8 border-white"></div>
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                    <script>
                        const map = L.map('viewMap', { scrollWheelZoom: false }).setView([<?= $postcard->getLatitude() ?>, <?= $postcard->getLongitude() ?>], 13);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; OSM' }).addTo(map);
                        L.marker([<?= $postcard->getLatitude() ?>, <?= $postcard->getLongitude() ?>]).addTo(map).bindPopup("Archivierter Moment.").openPopup();
                    </script>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>
</html>