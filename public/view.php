<?php
session_start();

use Postcardarchive\Controllers\PostcardController;
use Postcardarchive\Controllers\PostcardMetaController;
use Postcardarchive\Controllers\UserStampsController;
use Postcardarchive\Controllers\UserController;
use Postcardarchive\Utils\UtilsEncryptor;

require_once __DIR__ . '/../vendor/autoload.php';

$postcard = null; 
$error = null; 
$frontDecrypted = null; 
$backDecrypted = null;
$meta = null;

// LOGIK: VERARBEITUNG DES SCHLÜSSELS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keyData = null;

    // FALL A: Direkt-Zugriff aus dem Sammelalbum
    if (isset($_POST['direct_key_json'])) {
        $keyData = json_encode($_POST['direct_key_json'], true);
        // Korrektur: direct_key_json ist bereits ein string, falls es per POST kommt
        if (is_string($_POST['direct_key_json'])) {
            $keyData = json_decode($_POST['direct_key_json'], true);
        }
    } 
    // FALL B: Manueller Datei-Upload
    elseif (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
        $keyData = json_decode(file_get_contents($_FILES['key_file']['tmp_name']), true);
    }

    if ($keyData && isset($keyData['stamp_code'], $keyData['private_key'])) {
        $postcard = PostcardController::getPostcardByStampCode($keyData['stamp_code']);
        
        if ($postcard) {
            try {
                $privateKeyStr = $keyData['private_key'];
                $privateKey = UtilsEncryptor::getPrivateKeyFromString($privateKeyStr);
                
                // ENTSCHLÜSSELUNG
                $frontDecrypted = UtilsEncryptor::decryptData($privateKey, $postcard->getFrontImage());
                $backDecrypted  = UtilsEncryptor::decryptData($privateKey, $postcard->getBackImage());
                
                // METADATEN LADEN
                $meta = PostcardMetaController::getPostcardMetaByPostcardId($postcard->getId());

                // AUTOMATISCH IN USER-WALLET SPEICHERN (falls eingeloggt)
                if (UserController::isLoggedIn()) {
                    UserStampsController::addReceivedStamp(
                        $_SESSION['user_id'], 
                        $keyData['stamp_code'], 
                        $privateKeyStr, 
                        $meta ? $meta->getCountry() : 'Empfangen'
                    );
                }
                
            } catch (\Exception $e) { 
                $error = "Schlüssel ungültig oder Daten beschädigt."; 
                $postcard = null; 
            }
        } else { 
            $error = "Diese Postkarte existiert nicht im Archiv."; 
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $error = "Ungültiges Schlüsselformat.";
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
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&display=swap');
        
        .postcard-container {
            perspective: 2000px;
            width: 100%;
            max-width: 600px;
            aspect-ratio: 3/2;
        }
        .postcard-inner {
            position: relative;
            width: 100%;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            transform-style: preserve-3d;
        }
        .postcard-container.is-flipped .postcard-inner {
            transform: rotateY(180deg);
        }
        .postcard-front, .postcard-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }
        .postcard-back {
            transform: rotateY(180deg);
        }
    </style>
</head>
<body class="bg-stone-50 min-h-screen pb-20">

    <nav class="p-8">
        <a href="index.php" class="group inline-flex items-center gap-3 text-sky-900 font-bold uppercase text-xs tracking-[0.2em]">
            <span class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-sky-900 group-hover:text-white transition-all">←</span>
            Home
        </a>
    </nav>

    <main class="max-w-5xl mx-auto px-6 flex flex-col items-center">
        
        <?php if (!UserController::isLoggedIn()): ?>
            <div class="w-full max-w-md mb-8 flex items-center gap-4 bg-amber-50 border border-amber-100 p-5 rounded-3xl shadow-sm animate-in fade-in slide-in-from-top-4 duration-700">
                <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0 text-amber-600 font-bold">!</div>
                <div>
                    <h4 class="text-amber-900 font-bold text-xs uppercase tracking-wider">Du bist nicht angemeldet</h4>
                    <p class="text-amber-800 text-[11px] leading-relaxed opacity-80">
                        Diese Postkarte wird nur temporär angezeigt. Um sie dauerhaft in deiner Collection zu speichern, logge dich bitte zuerst ein.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$postcard): ?>
            <div class="glass-panel w-full max-w-md p-12 rounded-[3rem] text-center shadow-2xl animate-in zoom-in duration-500 bg-white border border-stone-100">
                <div class="text-6xl mb-8">🔐</div>
                <h1 class="text-4xl font-serif italic text-sky-950 mb-4" style="font-family: 'Playfair Display', serif;">Privates Archiv</h1>
                <p class="text-stone-500 mb-10 font-light text-sm leading-relaxed">
                    Lade eine Schlüsseldatei hoch, um die Verschlüsselung zu lösen.
                </p>
                
                <form action="view.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <label class="block relative border-2 border-dashed border-stone-200 rounded-2xl p-8 hover:border-sky-500 hover:bg-stone-50 transition-all cursor-pointer group">
                        <input type="file" name="key_file" accept=".json" required class="hidden" onchange="this.nextElementSibling.innerText = this.files[0].name">
                        <span class="text-stone-400 text-xs font-bold tracking-widest uppercase">Schlüssel wählen</span>
                    </label>
                    <button type="submit" class="w-full bg-sky-950 text-white py-5 rounded-2xl font-bold text-lg hover:bg-sky-900 transition-colors shadow-lg active:scale-95 transition-transform">
                        Entschlüsseln
                    </button>
                </form>
                <?php if ($error): ?> 
                    <p class="mt-4 text-red-500 text-xs italic font-bold"><?= $error ?></p> 
                <?php endif; ?>
            </div>

        <?php else: ?>
            <div class="postcard-container cursor-pointer mb-12" onclick="this.classList.toggle('is-flipped')">
                <div class="postcard-inner">
                    <div class="postcard-front bg-white shadow-xl overflow-hidden rounded-sm">
                        <img src="data:image/webp;base64,<?= base64_encode($frontDecrypted) ?>" class="w-full h-full object-cover" alt="Vorderseite">
                    </div>
                    
                    <div class="postcard-back flex p-8 bg-[#fdfcf8] shadow-xl rounded-sm border border-stone-100">
                        <div class="w-2/3 border-r border-stone-200 pr-8 flex items-center justify-center relative">
                             <img src="data:image/webp;base64,<?= base64_encode($backDecrypted) ?>" class="max-w-full max-h-full object-contain rotate-[-1deg] drop-shadow-sm" alt="Rückseite">
                        </div>
                        
                        <div class="w-1/3 pl-8 flex flex-col justify-between">
                            <div class="self-end">
                                <?php if ($meta && $meta->getCountry()): ?>
                                    <div class="stamp-border w-24 h-28 flex flex-col items-center justify-between p-2 rotate-3 hover:rotate-0 transition-transform duration-500 bg-white shadow-sm border border-stone-100">
                                        <div class="text-[7px] font-bold text-stone-400 uppercase tracking-widest border-b border-stone-100 w-full text-center pb-1">Archive</div>
                                        <div class="flex-grow flex items-center justify-center px-1">
                                            <span class="text-[11px] font-serif font-black text-sky-900 text-center leading-tight uppercase italic">
                                                <?= htmlspecialchars($meta->getCountry()) ?>
                                            </span>
                                        </div>
                                        <div class="text-[9px] font-mono text-stone-400">2026</div>
                                    </div>
                                <?php else: ?>
                                    <div class="w-20 h-24 border-2 border-dashed border-stone-200 flex items-center justify-center text-stone-300 text-[8px] uppercase font-bold text-center">Stamp</div>
                                <?php endif; ?>
                            </div>

                            <div class="flex flex-col items-end opacity-40">
                                <span class="text-3xl mb-2"><?= $meta ? htmlspecialchars($meta->getTravelMode()) : '🚗' ?></span>
                                <div class="space-y-4 w-full">
                                    <div class="h-px bg-stone-300 w-full"></div>
                                    <div class="h-px bg-stone-300 w-full"></div>
                                    <div class="h-px bg-stone-300 w-3/4"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-stone-400 text-[10px] uppercase tracking-[0.4em] mb-12 animate-pulse">Karte antippen zum Umdrehen</p>

            <?php if ($meta): ?>
                <div class="flex gap-4 mb-16 animate-in fade-in slide-in-from-bottom-4 duration-1000">
                    <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-stone-100 flex flex-col items-center">
                        <span class="text-[9px] uppercase font-bold text-stone-400 tracking-wider">Temperatur</span>
                        <span class="text-xl font-serif text-sky-950"><?= $meta->getTemperature() ?>°C</span>
                    </div>
                    <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-stone-100 flex flex-col items-center">
                        <span class="text-[9px] uppercase font-bold text-stone-400 tracking-wider">Himmel</span>
                        <span class="text-xl font-serif text-sky-950"><?= htmlspecialchars($meta->getWeatherCondition()) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($postcard->getLatitude()): ?>
                <div class="w-full space-y-6 animate-in slide-in-from-bottom-10 duration-1000">
                    <h3 class="text-2xl font-serif italic text-sky-950 flex items-center gap-3 ml-2" style="font-family: 'Playfair Display', serif;">
                        <span class="text-3xl">📍</span> Ort der Aufnahme
                    </h3>
                    <div id="viewMap" class="h-96 w-full rounded-[3rem] shadow-2xl border-8 border-white overflow-hidden"></div>
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                    <script>
                        const map = L.map('viewMap', { scrollWheelZoom: false }).setView([<?= $postcard->getLatitude() ?>, <?= $postcard->getLongitude() ?>], 13);
                        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { 
                            attribution: '&copy; OpenStreetMap' 
                        }).addTo(map);
                        L.marker([<?= $postcard->getLatitude() ?>, <?= $postcard->getLongitude() ?>]).addTo(map);
                    </script>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</body>
</html>