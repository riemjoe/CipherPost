<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
use Postcardarchive\Controllers\UserController;

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($action === 'register') {
        if (UserController::register($user, $email, $pass)) {
            $success = "Account erstellt! Du kannst dich jetzt einloggen.";
        } else {
            $error = "Benutzername bereits vergeben.";
        }
    } elseif ($action === 'login') {
        if (UserController::login($user, $pass)) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Ungültige Anmeldedaten.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Postcard Archive</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-stone-50 min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <div class="absolute top-[-10%] right-[-5%] w-96 h-96 bg-amber-100 rounded-full blur-3xl opacity-50 z-0"></div>
    <div class="absolute bottom-[-10%] left-[-5%] w-96 h-96 bg-sky-100 rounded-full blur-3xl opacity-50 z-0"></div>

    <main class="relative z-10 w-full max-w-md animate-in fade-in zoom-in duration-700">
        <div class="text-center mb-10">
            <h1 class="text-5xl font-serif italic text-sky-950 mb-2" style="font-family: 'Playfair Display', serif;">Willkommen zurück</h1>
            <p class="text-stone-500 font-light uppercase text-[10px] tracking-[0.3em]">Identity Verification</p>
        </div>

        <div class="glass-panel p-10 rounded-[3rem] shadow-2xl border border-white">
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 text-red-500 text-xs rounded-2xl border border-red-100 text-center uppercase font-bold tracking-wider">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 text-emerald-600 text-xs rounded-2xl border border-emerald-100 text-center uppercase font-bold tracking-wider">
                    <?= $success ?>
                </div>
            <?php endif; ?>

            <form id="authForm" method="POST" class="space-y-6">
                <input type="hidden" name="action" id="formAction" value="login">
                
                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest ml-4">Benutzername</label>
                    <input type="text" name="username" required class="w-full px-6 py-4 rounded-2xl border border-stone-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-50 outline-none transition-all">
                </div>

                <div id="emailField" class="space-y-2 hidden">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest ml-4">E-Mail</label>
                    <input type="email" name="email" class="w-full px-6 py-4 rounded-2xl border border-stone-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-50 outline-none transition-all">
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-stone-400 uppercase tracking-widest ml-4">Passwort</label>
                    <input type="password" name="password" required class="w-full px-6 py-4 rounded-2xl border border-stone-100 focus:border-sky-500 focus:ring-4 focus:ring-sky-50 outline-none transition-all">
                </div>

                <button type="submit" id="submitBtn" class="w-full bg-sky-950 text-white py-5 rounded-2xl font-bold text-lg hover:bg-sky-900 transition-all shadow-xl active:scale-95">
                    Einloggen
                </button>
            </form>

            <div class="mt-8 text-center">
                <button onclick="toggleAuth()" id="toggleBtn" class="text-stone-400 text-[10px] font-bold uppercase tracking-widest hover:text-sky-800 transition-colors">
                    Noch kein Konto? Registrieren
                </button>
            </div>
        </div>
    </main>

    <script>
        function toggleAuth() {
            const action = document.getElementById('formAction');
            const emailField = document.getElementById('emailField');
            const submitBtn = document.getElementById('submitBtn');
            const toggleBtn = document.getElementById('toggleBtn');

            if (action.value === 'login') {
                action.value = 'register';
                emailField.classList.remove('hidden');
                submitBtn.innerText = 'Konto erstellen';
                toggleBtn.innerText = 'Bereits registriert? Login';
            } else {
                action.value = 'login';
                emailField.classList.add('hidden');
                submitBtn.innerText = 'Einloggen';
                toggleBtn.innerText = 'Noch kein Konto? Registrieren';
            }
        }
    </script>
</body>
</html>