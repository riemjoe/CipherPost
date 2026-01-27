<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postcard Archive - Home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Playfair+Display:ital,wght@1,700&display=swap" rel="stylesheet">
</head>
<body class="bg-[#f4f1ea] text-stone-800 font-sans min-h-screen flex flex-col">

    <main class="flex-grow flex flex-col items-center justify-center p-6 text-center">
        <header class="mb-16">
            <h1 class="text-6xl font-serif italic text-sky-900 mb-4 tracking-tight" style="font-family: 'Playfair Display', serif;">
                Postcard Archive
            </h1>
            <div class="h-1 w-24 bg-sky-800 mx-auto mb-6"></div>
            <p class="text-lg text-stone-500 max-w-md mx-auto leading-relaxed">
                Bewahre deine Reiseerinnerungen sicher und verschlüsselt auf. Nur wer den Schlüssel besitzt, kann die Karte lesen.
            </p>
        </header>

        <div class="grid md:grid-cols-2 gap-8 w-full max-w-4xl">
            <a href="create.php" class="group bg-white p-10 rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 border border-stone-200 flex flex-col items-center">
                <div class="w-20 h-20 bg-sky-50 rounded-full flex items-center justify-center text-4xl mb-6 group-hover:scale-110 transition-transform duration-300">
                    ✈️
                </div>
                <h2 class="text-2xl font-bold text-sky-900 mb-2">Postkarte senden</h2>
                <p class="text-stone-500">Erstelle eine neue Karte und generiere deinen privaten Schlüssel.</p>
            </a>

            <a href="view.php" class="group bg-white p-10 rounded-3xl shadow-sm hover:shadow-2xl transition-all duration-300 border border-stone-200 flex flex-col items-center">
                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center text-4xl mb-6 group-hover:scale-110 transition-transform duration-300">
                    🔑
                </div>
                <h2 class="text-2xl font-bold text-sky-900 mb-2">Postkarte öffnen</h2>
                <p class="text-stone-500">Nutze deine Schlüsseldatei, um eine Nachricht zu entschlüsseln.</p>
            </a>
        </div>
    </main>

    <footer class="p-8 text-center text-stone-400 text-xs uppercase tracking-widest">
        &copy; End-to-End Encrypted Memories
    </footer>

    <?php if (isset($_GET['success']) && isset($_SESSION['last_key_file'])): 
        $keyFileData = $_SESSION['last_key_file'];
    ?>
    <div class="fixed inset-0 bg-sky-900/40 backdrop-blur-md flex items-center justify-center z-50 p-4">
        <div class="bg-[#fdfcf5] p-1 shadow-2xl rounded-sm max-w-md w-full animate-in fade-in zoom-in duration-300 overflow-hidden">
            <div class="border-2 border-stone-200 p-8 relative overflow-hidden">
                
                <div class="absolute top-6 right-6 w-20 h-24 border-2 border-dashed border-stone-300 p-1 bg-white rotate-3 shadow-sm flex flex-col items-center justify-center hidden sm:flex">
                    <div class="text-[8px] font-mono text-stone-400 mb-1 italic uppercase">Original</div>
                    <div class="text-2xl">🛡️</div>
                    <div class="text-[8px] text-stone-400 mt-2 font-mono">ENCRYPTED</div>
                </div>

                <h3 class="font-serif italic text-3xl text-sky-900 mb-2" style="font-family: 'Playfair Display', serif;">Karte versiegelt!</h3>
                <p class="text-stone-500 text-sm mb-8 pr-0 sm:pr-20 leading-relaxed">
                    Deine Postkarte wurde verschlüsselt gespeichert. Damit du (oder der Empfänger) sie ansehen kann, muss die folgende Schlüsseldatei heruntergeladen werden.
                </p>
                
                <div class="mb-10 space-y-3">
                    <span class="text-[10px] uppercase tracking-[0.2em] text-stone-400 block mb-2 font-bold">Dein Digitaler Schlüssel</span>
                    
                    <button onclick="downloadKeyFile()" class="w-full flex items-center justify-center gap-3 bg-amber-600 text-white py-4 rounded-xl font-bold hover:bg-amber-700 transition-all shadow-lg border-b-4 border-amber-800 active:border-b-0 active:translate-y-1 group">
                        <span class="text-xl group-hover:bounce">📥</span> 
                        Schlüssel herunterladen
                    </button>
                    
                    <div class="bg-stone-100 p-3 rounded border border-stone-200">
                        <code class="text-[11px] text-stone-500 font-mono truncate block">
                            <?= htmlspecialchars($keyFileData['filename']) ?>
                        </code>
                    </div>
                </div>

                <div class="flex flex-col gap-3">
                    <a href="view.php" class="block text-center bg-sky-800 text-white py-3 rounded-lg font-bold hover:bg-sky-900 transition-all">
                        Direkt zum Entschlüsseln
                    </a>
                    <button onclick="closeModal()" class="text-stone-400 text-sm hover:text-stone-600 transition-colors py-2">
                        Später (Schließen)
                    </button>
                </div>

                <div class="mt-8 border-t border-stone-200 pt-4 opacity-50">
                    <div class="h-px bg-stone-200 w-full mb-2"></div>
                    <div class="h-px bg-stone-200 w-3/4"></div>
                </div>
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
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }

        function closeModal() {
            window.location.href = 'index.php';
        }
    </script>
    <?php 
        // Wichtig: Daten nach Bereitstellung aus der Session löschen
        unset($_SESSION['last_key_file']); 
    endif; ?>

</body>
</html>