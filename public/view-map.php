<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Postcardarchive\Controllers\UserController;
use Postcardarchive\Controllers\UserStampsController;
use Postcardarchive\Utils\UtilsFormatter;

// 1. Zugriffsschutz
if (!UserController::isLoggedIn()) {
    header("Location: about-account.php");
    exit;
}

$userId = $_SESSION['user_id'];
$wallet = UserStampsController::getUserWallet($userId);

// 2. Sortierung in Tabs
$myStamps = array_filter($wallet, fn($s) => $s->getWasReceived() == 0);
$receivedStamps = array_filter($wallet, fn($s) => $s->getWasReceived() == 1);

// 3. ISO-Codes getrennt sammeln
function getIsosFromCollection($collection) {
    $isos = [];
    foreach ($collection as $stamp) {
        $iso = UtilsFormatter::countryToIso31661Alpha3($stamp->getCountry());
        if ($iso) {
            $isos[] = strtoupper(trim($iso));
        }
    }
    return array_values(array_unique($isos));
}

$myIsos = getIsosFromCollection($myStamps);
$receivedIsos = getIsosFromCollection($receivedStamps);

/**
 * Hilfsfunktion zum Rendern der Briefmarke
 */
function renderStampItem($stamp) {
    ob_start(); ?>
    <div class="stamp-grid-item flex justify-center">
        <form action="view.php" method="POST">
            <input type="hidden" name="direct_key_json" value='<?= json_encode(["stamp_code" => $stamp->getStampCode(), "private_key" => $stamp->getPrivateKey()]) ?>'>
            <button type="submit" class="w-32 h-40 flex flex-col items-center justify-between p-3 bg-white shadow-lg cursor-pointer">
                <div class="text-[7px] font-bold text-stone-400 uppercase tracking-widest border-b border-stone-100 w-full text-center pb-1">Collection</div>
                
                <div class="flex-grow flex flex-col items-center justify-center px-1 w-full">
                    <span class="text-[11px] font-serif font-black text-sky-900 text-center leading-tight uppercase italic block">
                        <?= htmlspecialchars($stamp->getCountry()) ?>
                    </span>
                    <span class="text-[8px] font-mono text-stone-400 uppercase italic leading-tight mt-1 block">
                        <?= htmlspecialchars($stamp->getPostcardMeta()->getCity()) ?>
                    </span>
                </div>
                
                <div class="text-[8px] font-mono text-stone-300">
                    <?= date('M Y', strtotime($stamp->getCreatedAt())) ?>
                </div>
            </button>
        </form>
    </div>
    <?php return ob_get_clean();
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meine Briefmarken | Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&display=swap');
        
        .tab-active { @apply border-sky-900 text-sky-900; }
        .tab-inactive { @apply border-transparent text-stone-400 hover:text-stone-600; }
        
        .stamp-grid-item { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
        .stamp-grid-item:hover { transform: scale(1.05) rotate(2deg); }
        
        #world-map-container { filter: drop-shadow(0 10px 15px rgba(0,0,0,0.05)); }

        #world-map {
            width: 100%;
            height: 300px;
        }
        @media (min-width: 768px) {
            #world-map { height: 500px; }
        }

        #world-map svg { touch-action: manipulation; }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

    <nav class="p-4 md:p-8 flex justify-between items-center">
        <a href="index.php" class="group inline-flex items-center gap-3 text-sky-900 font-bold uppercase text-xs tracking-[0.2em]">
            <span class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-sky-900 group-hover:text-white transition-all">←</span>
            <span class="hidden sm:inline">Home</span>
        </a>
        <h1 class="text-xl md:text-2xl font-serif italic text-sky-950" style="font-family: 'Playfair Display', serif;">Meine Briefmarken</h1>
    </nav>

    <main class="max-w-6xl mx-auto px-4 md:px-6 py-6 md:py-12">
        
        <div class="flex justify-center mb-10 md:mb-16 border-b border-stone-200">
            <button onclick="switchTab('my')" id="tab-my" class="px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-active">
                Meine (<?= count($myStamps) ?>)
            </button>
            <button onclick="switchTab('received')" id="tab-received" class="px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-inactive">
                Empfangen (<?= count($receivedStamps) ?>)
            </button>
        </div>

        <section class="mb-20">
            <div id="grid-my" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 md:gap-10">
                <?php foreach ($myStamps as $stamp): ?>
                    <?= renderStampItem($stamp) ?>
                <?php endforeach; ?>
                <?php if(empty($myStamps)): ?>
                    <p class="col-span-full text-center text-stone-400 py-10 italic">Noch keine eigenen Karten erstellt.</p>
                <?php endif; ?>
            </div>

            <div id="grid-received" class="hidden grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6 md:gap-10">
                <?php foreach ($receivedStamps as $stamp): ?>
                    <?= renderStampItem($stamp) ?>
                <?php endforeach; ?>
                <?php if(empty($receivedStamps)): ?>
                    <p class="col-span-full text-center text-stone-400 py-10 italic">Noch keine Karten von Freunden erhalten.</p>
                <?php endif; ?>
            </div>
        </section>

        <section id="world-map-container" class="bg-white p-4 md:p-8 rounded-2xl md:rounded-3xl border border-stone-100">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 md:mb-8 gap-2">
                <div class="w-1/3 hidden md:block"></div>
                <h2 id="map-title" class="text-center text-stone-400 uppercase text-[9px] md:text-[10px] tracking-[0.4em]">Eigene Reichweite</h2>
                <div class="md:w-1/3 text-center md:text-right">
                    <span id="world-percentage" class="bg-sky-50 text-sky-900 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest border border-sky-100">
                        0% der Welt
                    </span>
                </div>
            </div>
            <div id="world-map" class="w-full"></div>
        </section>

    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/3.5.17/d3.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/topojson/1.6.9/topojson.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datamaps@0.5.9/dist/datamaps.world.min.js"></script>

    <script>
        const myIsos = <?= json_encode($myIsos) ?>;
        const receivedIsos = <?= json_encode($receivedIsos) ?>;
        const totalCountries = 195; // Offiziell anerkannte Staaten
        let map;

        function updatePercentage(isos) {
            const percentage = ((isos.length / totalCountries) * 100).toFixed(1);
            document.getElementById('world-percentage').innerText = `${percentage}% der Welt`;
        }

        function initMap(isos) {
            const container = document.getElementById('world-map');
            container.innerHTML = ''; 
            
            const dataset = {};
            isos.forEach(iso => {
                if(iso && iso.length === 3) dataset[iso] = { fillKey: 'VISITED' };
            });

            map = new Datamaps({
                element: container,
                projection: 'mercator',
                responsive: true,
                fills: {
                    defaultFill: '#f5f5f4',
                    VISITED: '#0c4a6e'
                },
                data: dataset,
                geographyConfig: {
                    borderColor: '#ffffff',
                    highlightFillColor: '#38bdf8',
                    highlightBorderColor: '#ffffff',
                    popupOnHover: !('ontouchstart' in window), 
                    popupTemplate: function(geo, data) {
                        return `<div class="bg-white px-3 py-1 shadow-xl border border-stone-100 rounded text-[10px] uppercase font-bold tracking-wider text-sky-900">${geo.properties.name}</div>`;
                    }
                }
            });
            updatePercentage(isos);
        }

        // Initialisierung
        initMap(myIsos);

        window.addEventListener('resize', () => { if(map) map.resize(); });

        function switchTab(type) {
            const myGrid = document.getElementById('grid-my');
            const recGrid = document.getElementById('grid-received');
            const myTab = document.getElementById('tab-my');
            const recTab = document.getElementById('tab-received');
            const title = document.getElementById('map-title');

            if (type === 'my') {
                myGrid.classList.remove('hidden');
                recGrid.classList.add('hidden');
                myTab.className = 'px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-active';
                recTab.className = 'px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-inactive';
                title.innerText = 'Eigene Reichweite';
                initMap(myIsos);
            } else {
                recGrid.classList.remove('hidden');
                myGrid.classList.add('hidden');
                recTab.className = 'px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-active';
                myTab.className = 'px-4 md:px-8 py-4 font-bold uppercase text-[9px] md:text-[10px] tracking-[0.2em] md:tracking-[0.3em] border-b-2 transition-all tab-inactive';
                title.innerText = 'Empfangene Karten';
                initMap(receivedIsos);
            }
        }
    </script>
</body>
</html>