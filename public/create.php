<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erinnerung festhalten | Postcard Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-stone-50 min-h-screen">

    <nav class="p-8">
        <a href="index.php" class="group inline-flex items-center gap-3 text-sky-900 font-bold uppercase text-xs tracking-[0.2em]">
            <span class="w-8 h-8 rounded-full bg-white shadow-sm flex items-center justify-center group-hover:bg-sky-900 group-hover:text-white transition-all">←</span>
            Zurück
        </a>
    </nav>

    <main class="max-w-4xl mx-auto p-4 pb-20 animate-in fade-in slide-in-from-bottom-4 duration-700">
        <div class="bg-white rounded-[3rem] shadow-2xl shadow-stone-200 border border-stone-100 overflow-hidden">
            <header class="bg-sky-950 p-12 text-white relative overflow-hidden">
                <div class="relative z-10">
                    <h1 class="text-4xl font-serif italic mb-2" style="font-family: 'Playfair Display', serif;">Neue Postkarte</h1>
                    <p class="text-sky-200/60 font-light tracking-wide uppercase text-[10px]">Step into the Digital Archive</p>
                </div>
                <div class="absolute top-0 right-0 p-8 text-9xl opacity-10 rotate-12">📮</div>
            </header>

            <form action="api/process_create.php" method="POST" enctype="multipart/form-data" class="p-12 space-y-12">
                
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

                <button type="submit" class="w-full bg-sky-950 hover:bg-sky-900 text-white py-6 rounded-[1.5rem] font-bold text-xl shadow-2xl transition-all transform hover:-translate-y-1">
                    Erinnerung archivieren
                </button>
            </form>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
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

        function previewImage(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    document.getElementById(previewId).innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover animate-in fade-in zoom-in duration-500">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>