<?php
use Postcardarchive\Controllers\PostcardController;
use Postcardarchive\Utils\UtilsEncryptor;

require_once __DIR__ . '/../vendor/autoload.php';

$postcard = null;
$error = null;
$frontDecrypted = null;
$backDecrypted = null;

// Prüfung: Wurde eine Schlüsseldatei hochgeladen?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['key_file'])) {
    if ($_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
        $jsonContent = file_get_contents($_FILES['key_file']['tmp_name']);
        $keyData = json_decode($jsonContent, true);

        if ($keyData && isset($keyData['stamp_code'], $keyData['private_key'])) {
            $stampCode = $keyData['stamp_code'];
            $privateKeyString = $keyData['private_key'];

            // 1. Postkarte aus der Datenbank abrufen
            $postcard = PostcardController::getPostcardByStampCode($stampCode);

            if ($postcard) {
                try {
                    // 2. Private Key Objekt erstellen
                    $privateKey = UtilsEncryptor::getPrivateKeyFromString($privateKeyString);

                    // 3. Bilder entschlüsseln
                    // Die Daten liegen in der DB als Base64-Strings (vom Encryptor generiert)
                    $frontDecrypted = UtilsEncryptor::decryptData($privateKey, $postcard->getFrontImage());
                    $backDecrypted  = UtilsEncryptor::decryptData($privateKey, $postcard->getBackImage());

                } catch (\Exception $e) {
                    $error = "Entschlüsselung fehlgeschlagen. Der Schlüssel ist eventuell beschädigt oder inkompatibel.";
                    $postcard = null;
                }
            } else {
                $error = "Keine passende Postkarte zu diesem Stamp-Code im Archiv gefunden.";
            }
        } else {
            $error = "Ungültiges Dateiformat. Bitte laden Sie die originale .json Schlüsseldatei hoch.";
        }
    } else {
        $error = "Fehler beim Hochladen der Datei.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postkarte entschlüsseln | Postcard Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@500&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#f0ede5] text-stone-800 font-sans min-h-screen">

    <nav class="p-6">
        <a href="index.php" class="text-sky-800 hover:text-sky-600 flex items-center gap-2 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Zurück zur Startseite
        </a>
    </nav>

    <main class="max-w-4xl mx-auto p-4 flex flex-col items-center">
        
        <?php if (!$postcard): ?>
            <div class="w-full max-w-md bg-white p-10 rounded-3xl shadow-xl border border-stone-200 text-center">
                <div class="text-5xl mb-6">🔑</div>
                <h1 class="text-3xl font-serif italic text-sky-900 mb-4" style="font-family: 'Playfair Display', serif;">Privates Archiv</h1>
                <p class="text-stone-500 mb-8">Diese Postkarte ist verschlüsselt. Bitte laden Sie Ihren Stamp-Code Schlüssel (.json) hoch, um den Inhalt zu sehen.</p>
                
                <form action="view.php" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <div class="relative border-2 border-dashed border-stone-200 rounded-2xl p-6 hover:border-sky-400 hover:bg-sky-50 transition-all group cursor-pointer">
                        <input type="file" name="key_file" accept=".json" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="this.nextElementSibling.innerText = this.files[0].name">
                        <p class="text-sm text-stone-400 font-medium italic">Schlüsseldatei auswählen...</p>
                    </div>
                    
                    <button type="submit" class="w-full bg-sky-800 text-white py-4 rounded-2xl font-bold text-lg hover:bg-sky-900 transition-all shadow-lg active:scale-[0.98]">
                        Postkarte öffnen
                    </button>
                </form>

                <?php if ($error): ?>
                    <div class="mt-6 p-4 bg-red-50 text-red-700 rounded-xl text-sm border border-red-100 italic">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            
            <div class="postcard-container w-full shadow-2xl cursor-pointer" onclick="this.classList.toggle('is-flipped')">
                <div class="postcard-inner">
                    <div class="postcard-front bg-white overflow-hidden rounded-sm flex items-center justify-center">
                        <img src="data:image/webp;base64,<?= base64_encode($frontDecrypted) ?>" class="w-full h-full object-cover" alt="Vorderseite">
                    </div>

                    <div class="postcard-back bg-[#fdfcf5] rounded-sm flex border-l border-stone-200 shadow-inner overflow-hidden">
                        <div class="w-full h-full flex p-6 md:p-10 relative">
                            <div class="w-2/3 border-r border-stone-200 pr-6 flex items-center justify-center">
                                <img src="data:image/webp;base64,<?= base64_encode($backDecrypted) ?>" class="max-w-full max-h-full object-contain rotate-[-1deg]" alt="Rückseite">
                            </div>
                            
                            <div class="w-1/3 pl-6 flex flex-col justify-start">
                                <div class="w-16 h-20 border-2 border-dashed border-stone-300 rounded-sm self-end mb-8 flex items-center justify-center text-stone-300 text-[10px] uppercase text-center px-1">
                                    Stamp<br>Code<br>Active
                                </div>
                                <div class="space-y-4 mt-auto mb-4">
                                    <div class="h-px bg-stone-300 w-full"></div>
                                    <div class="h-px bg-stone-300 w-full"></div>
                                    <div class="h-px bg-stone-300 w-full"></div>
                                    <div class="h-px bg-stone-200 w-1/2"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <p class="mt-8 text-stone-400 text-sm italic animate-pulse">Klicke auf die Karte zum Umdrehen</p>

            <?php if ($postcard->getLatitude() && $postcard->getLongitude()): ?>
                <div class="w-full mt-16 space-y-4 pb-20">
                    <h3 class="text-xl font-serif italic text-sky-900 ml-2 flex items-center gap-2" style="font-family: 'Playfair Display', serif;">
                        <span>📍</span> Fundort dieser Karte
                    </h3>
                    <div id="viewMap" class="h-80 w-full rounded-3xl shadow-lg border-4 border-white overflow-hidden"></div>
                </div>

                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                    const lat = <?= (float)$postcard->getLatitude() ?>;
                    const lng = <?= (float)$postcard->getLongitude() ?>;
                    const map = L.map('viewMap', { scrollWheelZoom: false }).setView([lat, lng], 13);

                    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(map);

                    L.marker([lat, lng]).addTo(map)
                        .bindPopup('Diese Karte wurde hier aufgenommen.')
                        .openPopup();
                </script>
            <?php endif; ?>

        <?php endif; ?>

    </main>

    <footer class="p-8 text-center text-stone-400 text-[10px] uppercase tracking-[0.3em]">
        End-to-End Encrypted Memories
    </footer>

</body>
</html>