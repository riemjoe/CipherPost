<?php
// Am Anfang der Datei sicherstellen, dass wir Zugriff auf den Login-Status haben
require_once __DIR__ . '/../vendor/autoload.php';
use Postcardarchive\Controllers\UserController;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerung festhalten | Postcard Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&display=swap');
        
        #loading-overlay {
            display: none;
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.7);
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0c4a6e;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #e2e8f0;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-stone-50 min-h-screen">

    <div id="loading-overlay" class="fixed inset-0 z-[10000] flex flex-col items-center justify-center">
        <div class="loader-spinner mb-6"></div>
        <h2 class="text-2xl font-serif italic text-sky-950 animate-pulse" style="font-family: 'Playfair Display', serif;">
            Erinnerung wird archiviert...
        </h2>
        <p class="text-stone-400 text-[10px] uppercase tracking-[0.3em] mt-3">Verschlüsselung wird erstellt</p>
    </div>

    <nav class="p-8">
        <a href="index.php" class="group inline-flex items-center gap-3 text-sky-900 font-bold uppercase text-xs tracking-[0.2em]">
            <span class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-sky-900 group-hover:text-white transition-all">←</span>
            Zurück
        </a>
    </nav>

    <main class="max-w-4xl mx-auto p-4 pb-20 animate-in fade-in slide-in-from-bottom-4 duration-700">
        
        <?php if (!UserController::isLoggedIn()): ?>
            <div class="mb-8 flex items-center gap-5 bg-amber-50 border border-amber-100 p-6 rounded-[2rem] shadow-sm animate-in fade-in slide-in-from-top-4 duration-700">
                <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0 text-amber-600 text-xl font-bold">!</div>
                <div>
                    <h4 class="text-amber-900 font-bold text-xs uppercase tracking-[0.2em] mb-1">Eingeschränkter Modus</h4>
                    <p class="text-amber-800/80 text-[11px] leading-relaxed max-w-xl">
                        Du bist aktuell nicht angemeldet. Du kannst Postkarten erstellen und die Schlüsseldatei herunterladen, aber die Karte wird <strong>nicht</strong> automatisch in deiner persönlichen Collection gespeichert.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-[3rem] shadow-2xl shadow-stone-200 border border-stone-100 overflow-hidden">
            <header class="bg-sky-950 p-12 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-4xl font-serif italic mb-2" style="font-family: 'Playfair Display', serif;">Neue Postkarte</h1>
                    <p class="text-sky-200/60 font-light tracking-wide uppercase text-[10px]">Step into the Digital Archive</p>
                </div>
                <div class="absolute top-0 right-0 p-8 text-9xl opacity-10 rotate-12">📮</div>
            </header>

            <form id="create-postcard-form" action="api/process_create.php" method="POST" enctype="multipart/form-data" class="p-12 space-y-12">
                
                <div class="grid md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] ml-2">Vorderseite (Motiv)</label>
                        <label class="group relative flex flex-col items-center justify-center h-64 border-2 border-dashed border-stone-200 rounded-[2rem] cursor-pointer hover:border-sky-500 hover:bg-sky-50 transition-all overflow-hidden">
                            <input type="file" name="front_image" class="hidden" required onchange="previewImage(this, 'preview-front')">
                            <div id="preview-front" class="flex flex-col items-center text-stone-400">
                                <span class="text-5xl mb-4 group-hover:scale-110 transition-transform">🖼️</span>
                                <span class="text-xs font-medium">Bild auswählen</span>
                            </div>
                        </label>
                    </div>

                    <div class="space-y-4">
                        <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] ml-2">Rückseite (Text)</label>
                        <label class="group relative flex flex-col items-center justify-center h-64 border-2 border-dashed border-stone-200 rounded-[2rem] cursor-pointer hover:border-sky-500 hover:bg-sky-50 transition-all overflow-hidden">
                            <input type="file" name="back_image" class="hidden" required onchange="previewImage(this, 'preview-back')">
                            <div id="preview-back" class="flex flex-col items-center text-stone-400">
                                <span class="text-5xl mb-4 group-hover:scale-110 transition-transform">✍️</span>
                                <span class="text-xs font-medium">Textseite auswählen</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] ml-2">Wie bist du gereist?</label>
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
                        <?php 
                        $modes = ['🚗' => 'Auto', '✈️' => 'Flug', '🚆' => 'Zug', '🚲' => 'Rad', '🥾' => 'Wandern', '🚢' => 'Schiff'];
                        foreach ($modes as $emoji => $label): 
                        ?>
                        <label class="cursor-pointer group">
                            <input type="radio" name="travel_mode" value="<?= $emoji ?>" class="peer hidden" <?= $emoji === '🚗' ? 'checked' : '' ?>>
                            <div class="flex flex-col items-center p-4 rounded-2xl border-2 border-stone-100 peer-checked:border-sky-500 peer-checked:bg-sky-50 hover:bg-stone-50 transition-all">
                                <span class="text-2xl mb-1"><?= $emoji ?></span>
                                <span class="text-[9px] font-bold text-stone-400 uppercase tracking-tighter"><?= $label ?></span>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] ml-2 text-center">Fundort der Erinnerung</label>
                    <div id="map" class="h-80 rounded-[2rem] border border-stone-100 shadow-inner grayscale-[0.5] hover:grayscale-0 transition-all duration-700"></div>
                    <input type="hidden" name="lat" id="lat">
                    <input type="hidden" name="lng" id="lng">
                    <p class="text-center text-[9px] text-stone-400 italic">Klicke auf die Karte, um den Ort zu markieren.</p>
                </div>

                <div class="space-y-4 pt-6 border-t border-stone-100">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-[0.2em] ml-2">Empfänger (Direktzustellung)</label>
                    <div id="selected-receivers-list" class="flex flex-wrap gap-2 mb-4">
                        </div>
                    <button type="button" onclick="openModal()" class="inline-flex items-center gap-2 px-6 py-3 bg-stone-100 hover:bg-sky-100 text-sky-900 rounded-full text-xs font-bold transition-all group">
                        <span class="text-lg group-hover:rotate-90 transition-transform">+</span>
                        Empfänger direkt hinzufügen
                    </button>
                    <div id="hidden-receivers-inputs"></div>
                </div>

                <button type="submit" class="w-full bg-sky-950 hover:bg-sky-900 text-white py-6 rounded-[1.5rem] font-bold text-xl shadow-2xl transition-all transform hover:-translate-y-1">
                    Erinnerung archivieren
                </button>
            </form>
        </div>
    </main>

    <div id="user-modal" class="hidden fixed inset-0 z-[11000] flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl transform animate-in slide-in-from-bottom-8 duration-500">
            <div class="p-8 bg-sky-950 text-white flex justify-between items-center">
                <h3 class="text-xl font-serif italic">Empfänger suchen</h3>
                <button onclick="closeModal()" class="text-2xl leading-none">&times;</button>
            </div>
            <div class="p-8 space-y-6">
                <div class="relative">
                    <input type="text" id="user-search-query" 
                           class="w-full bg-stone-100 border-none rounded-2xl px-5 py-4 text-sm focus:ring-2 focus:ring-sky-500 outline-none transition-all" 
                           placeholder="Benutzernamen eingeben..."
                           onkeyup="if(event.key === 'Enter') searchUsers()">
                    <button onclick="searchUsers()" class="absolute right-2 top-2 bottom-2 px-4 bg-sky-600 text-white rounded-xl text-xs font-bold hover:bg-sky-700 transition-colors">
                        Suchen
                    </button>
                </div>
                
                <div id="search-results-container" class="max-h-64 overflow-y-auto custom-scrollbar space-y-2 pr-2">
                    <p class="text-center text-stone-400 text-xs py-10">Gib einen Namen ein, um die Suche zu starten.</p>
                </div>
                
                <button type="button" onclick="closeModal()" class="w-full py-3 text-stone-400 text-[10px] uppercase tracking-[0.3em] font-bold hover:text-stone-600 transition-colors">
                    Abbrechen
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Formular-Submit Handling für Ladescreen
        const form = document.getElementById('create-postcard-form');
        const loader = document.getElementById('loading-overlay');

        form.addEventListener('submit', function() {
            loader.style.display = 'flex';
        });

        // Leaflet Map Setup
        const map = L.map('map').setView([20, 0], 2);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { 
            attribution: '&copy; OpenStreetMap contributors' 
        }).addTo(map);

        let marker;
        map.on('click', (e) => {
            if (marker) marker.setLatLng(e.latlng);
            else marker = L.marker(e.latlng).addTo(map);
            document.getElementById('lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng').value = e.latlng.lng.toFixed(6);
        });

        // Bildvorschau Funktion
        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById(previewId).innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover animate-in fade-in zoom-in duration-500">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // --- EMPFÄNGER LOGIK ---
        let selectedUserIds = new Set();

        function openModal() {
            document.getElementById('user-modal').classList.remove('hidden');
            document.getElementById('user-search-query').focus();
        }

        function closeModal() {
            document.getElementById('user-modal').classList.add('hidden');
        }

        async function searchUsers() {
            const query = document.getElementById('user-search-query').value;
            const container = document.getElementById('search-results-container');

            if (query.length < 2) {
                container.innerHTML = '<p class="text-center text-amber-600 text-xs py-4">Bitte mindestens 2 Zeichen eingeben.</p>';
                return;
            }

            container.innerHTML = '<div class="flex justify-center py-10"><div class="loader-spinner !w-8 !h-8 !border-2"></div></div>';

            try {
                // Hier rufen wir einen API-Endpunkt auf, den wir noch anlegen müssen (siehe unten)
                const response = await fetch(`api/search_users.php?q=${encodeURIComponent(query)}`);
                const users = await response.json();

                container.innerHTML = '';
                if (users.length === 0) {
                    container.innerHTML = '<p class="text-center text-stone-400 text-xs py-10">Keine Benutzer gefunden.</p>';
                    return;
                }

                users.forEach(user => {
                    const div = document.createElement('div');
                    div.className = 'group flex items-center justify-between p-4 rounded-2xl border border-stone-100 hover:border-sky-200 hover:bg-sky-50 transition-all cursor-pointer';
                    div.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-stone-200 flex items-center justify-center text-[10px] font-bold uppercase">${user.username.substring(0,2)}</div>
                            <span class="text-sm font-medium text-stone-700">${user.username}</span>
                        </div>
                        <span class="text-sky-600 text-[10px] font-bold uppercase opacity-0 group-hover:opacity-100 transition-opacity">Hinzufügen +</span>
                    `;
                    div.onclick = () => selectUser(user.id, user.username);
                    container.appendChild(div);
                });
            } catch (e) {
                container.innerHTML = '<p class="text-center text-red-400 text-xs py-10">Fehler bei der Suche.</p>';
            }
        }

        function selectUser(id, username) {
            if (selectedUserIds.has(id)) {
                closeModal();
                return;
            }

            selectedUserIds.add(id);

            // Badge im Formular anzeigen
            const badge = document.createElement('div');
            badge.id = `receiver-badge-${id}`;
            badge.className = 'inline-flex items-center gap-2 px-4 py-2 bg-sky-900 text-white rounded-full text-xs font-bold animate-in zoom-in duration-300';
            badge.innerHTML = `
                <span>${username}</span>
                <button type="button" onclick="removeUser(${id})" class="text-sky-300 hover:text-white font-bold ml-1">×</button>
            `;
            document.getElementById('selected-receivers-list').appendChild(badge);

            // Hidden Input für POST hinzufügen
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'receivers[]';
            input.value = id;
            input.id = `receiver-input-${id}`;
            document.getElementById('hidden-receivers-inputs').appendChild(input);

            closeModal();
        }

        function removeUser(id) {
            selectedUserIds.delete(id);
            document.getElementById(`receiver-badge-${id}`).remove();
            document.getElementById(`receiver-input-${id}`).remove();
        }
    </script>
</body>
</html>