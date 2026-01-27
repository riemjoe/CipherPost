<?php session_start(); ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postcard Archive - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="min-h-screen flex flex-col overflow-x-hidden">

    <main class="flex-grow flex flex-col items-center justify-center p-6 relative">
        <div class="absolute top-[-10%] left-[-5%] w-96 h-96 bg-sky-100 rounded-full blur-3xl opacity-50 z-0"></div>
        
        <div class="relative z-10 w-full max-w-5xl animate-in fade-in slide-in-from-bottom-8 duration-1000">
            <header class="text-center mb-16">
                <span class="inline-block px-4 py-1 mb-4 text-[10px] font-bold tracking-[0.4em] uppercase bg-sky-900 text-white rounded-full">
                    Digital Memories
                </span>
                <h1 class="text-7xl font-serif italic text-sky-950 mb-6 tracking-tighter" style="font-family: 'Playfair Display', serif;">
                    Postcard Archive
                </h1>
                <p class="text-lg text-stone-500 max-w-md mx-auto leading-relaxed font-light">
                    Bewahre deine Reiseerinnerungen in einem <span class="text-sky-800 font-medium">verschlüsselten Tresor</span> auf. Nur dein Schlüssel erweckt sie zum Leben.
                </p>
            </header>

            <div class="grid md:grid-cols-2 gap-8">
                <a href="create.php" class="glass-panel group p-12 rounded-[2.5rem] flex flex-col items-center transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl">
                    <div class="w-24 h-24 bg-sky-50 rounded-3xl flex items-center justify-center text-5xl mb-8 group-hover:scale-110 transition-transform duration-500 shadow-inner animate-float">
                        ✈️
                    </div>
                    <h2 class="text-3xl font-bold text-sky-950 mb-3">Karte senden</h2>
                    <p class="text-stone-500 text-center leading-relaxed">Erstelle eine neue digitale Postkarte und generiere dein Unikat.</p>
                </a>

                <a href="view.php" class="glass-panel group p-12 rounded-[2.5rem] flex flex-col items-center transition-all duration-500 hover:-translate-y-3 hover:shadow-2xl">
                    <div class="w-24 h-24 bg-amber-50 rounded-3xl flex items-center justify-center text-5xl mb-8 group-hover:scale-110 transition-transform duration-500 shadow-inner animate-float" style="animation-delay: 0.5s">
                        🔑
                    </div>
                    <h2 class="text-3xl font-bold text-sky-950 mb-3">Archiv öffnen</h2>
                    <p class="text-stone-500 text-center leading-relaxed">Nutze deinen privaten Schlüssel, um verborgene Nachrichten zu lesen.</p>
                </a>
            </div>
        </div>
    </main>

    <footer class="p-10 text-center text-stone-400 text-[10px] uppercase tracking-[0.3em] font-bold">
        &copy; 2026 End-to-End Encrypted Memories • Devloped by Jonas Riemer
    </footer>

    <?php if (isset($_GET['success']) && isset($_SESSION['last_key_file'])): 
        $keyFileData = $_SESSION['last_key_file'];
    ?>
    <div class="fixed inset-0 bg-sky-950/60 backdrop-blur-xl flex items-center justify-center z-50 p-4">
        <div class="bg-[#fdfcf5] p-2 shadow-2xl rounded-3xl max-w-md w-full animate-in zoom-in duration-500">
            <div class="border-2 border-dashed border-stone-200 rounded-[2rem] p-8 text-center">
                <div class="text-6xl mb-6">🖋️</div>
                <h3 class="font-serif italic text-3xl text-sky-950 mb-4" style="font-family: 'Playfair Display', serif;">Karte versiegelt!</h3>
                <p class="text-stone-500 text-sm mb-8 leading-relaxed">
                    Deine Postkarte wurde sicher verschlüsselt. Lade jetzt deinen digitalen Schlüssel herunter – ohne ihn bleibt die Karte für immer verborgen.
                </p>
                
                <button onclick="downloadKeyFile()" class="btn-travel w-full flex items-center justify-center gap-3 text-white py-5 rounded-2xl font-bold mb-4">
                    <span>📥</span> Schlüssel sichern
                </button>
                
                <button onclick="closeModal()" class="text-stone-400 text-xs uppercase tracking-widest hover:text-sky-900 transition-colors">
                    Schließen
                </button>
            </div>
        </div>
    </div>
    <script>
        function downloadKeyFile() {
            const content = <?= json_encode($keyFileData['content']) ?>;
            const filename = <?= json_encode($keyFileData['filename']) ?>;
            const blob = new Blob([content], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = filename;
            document.body.appendChild(a); a.click();
            window.URL.revokeObjectURL(url); document.body.removeChild(a);
        }
        function closeModal() { window.location.href = 'index.php'; }
    </script>
    <?php unset($_SESSION['last_key_file']); endif; ?>
</body>
</html>