<?php
/**
 * Postcard Archive - Erstellungsseite
 * Ermöglicht den Upload von Bildern und die Auswahl eines Standorts.
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postkarte erstellen | Postcard Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-stone-50 min-h-screen font-sans text-stone-800">

    <nav class="p-6">
        <a href="index.php" class="text-sky-800 hover:text-sky-600 flex items-center gap-2 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Zurück zur Übersicht
        </a>
    </nav>

    <main class="max-w-3xl mx-auto p-4 pb-12">
        <header class="mb-10 text-center">
            <h1 class="text-4xl font-serif italic text-sky-900 mb-2">Erinnerung festhalten</h1>
            <p class="text-stone-500">Lade deine Fotos hoch und markiere den Ort auf der Karte.</p>
        </header>

        <form action="api/process_create.php" method="POST" enctype="multipart/form-data" class="space-y-8 bg-white p-8 rounded-3xl shadow-xl border border-stone-100">
            
            <div class="grid md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-stone-700 ml-1">Vorderseite (Motiv)</label>
                    <div class="upload-zone relative border-2 border-dashed border-stone-200 rounded-2xl p-6 hover:border-sky-400 hover:bg-sky-50 transition-all group">
                        <input type="file" name="front_image" id="front_image" accept="image/*" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center pointer-events-none">
                            <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">🖼️</div>
                            <p class="text-xs text-stone-500" id="front_label">Bild auswählen oder ablegen</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-sm font-semibold text-stone-700 ml-1">Rückseite (Text/Stempel)</label>
                    <div class="upload-zone relative border-2 border-dashed border-stone-200 rounded-2xl p-6 hover:border-sky-400 hover:bg-sky-50 transition-all group">
                        <input type="file" name="back_image" id="back_image" accept="image/*" required class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center pointer-events-none">
                            <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">✍️</div>
                            <p class="text-xs text-stone-500" id="back_label">Bild auswählen oder ablegen</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-semibold text-stone-700 ml-1 flex justify-between">
                    Wo wurde dieses Foto aufgenommen?
                    <span class="text-stone-400 font-normal italic">(optional)</span>
                </label>
                <div id="map" class="h-80 rounded-2xl border border-stone-200 shadow-inner overflow-hidden"></div>
                <input type="hidden" name="lat" id="lat">
                <input type="hidden" name="lng" id="lng">
                <p class="text-[10px] text-stone-400 text-right uppercase tracking-widest">Klicke auf die Karte, um einen Pin zu setzen</p>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-sky-800 text-white py-4 rounded-2xl font-bold text-lg hover:bg-sky-900 transition-all shadow-lg hover:shadow-sky-200 active:scale-[0.98]">
                    Postkarte archivieren
                </button>
            </div>
        </form>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Karte initialisieren (Standard-Ansicht auf Europa/Welt)
        const map = L.map('map').setView([20, 0], 2);
        
        // Modernes Karten-Design (CartoDB Positron ist dezenter als Standard-OSM)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        let marker;

        // Klick-Event für die Standortwahl
        map.on('click', function(e) {
            const { lat, lng } = e.latlng;
            
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }

            document.getElementById('lat').value = lat.toFixed(6);
            document.getElementById('lng').value = lng.toFixed(6);
        });

        // Dateinamen-Anzeige Helper
        function handleFileSelect(inputId, labelId) {
            const input = document.getElementById(inputId);
            const label = document.getElementById(labelId);
            input.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    label.textContent = e.target.files[0].name;
                    label.classList.add('text-sky-600', 'font-bold');
                }
            });
        }

        handleFileSelect('front_image', 'front_label');
        handleFileSelect('back_image', 'back_label');
    </script>
</body>
</html>