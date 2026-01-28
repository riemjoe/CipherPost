<?php
/**
 * Logout-Script für Postcard Archive
 */

session_start();

// Den Pfad zum Autoloader anpassen (je nachdem, wo deine logout.php liegt)
// Falls die Datei im Root liegt:
require_once __DIR__ . '/../vendor/autoload.php';
// Falls die Datei in einem Unterordner wie /public liegt:
// require_once __DIR__ . '/../vendor/autoload.php';

use Postcardarchive\Controllers\UserController;

/**
 * Wir nutzen die bestehende Methode aus dem UserController.
 * Diese zerstört die Session und leitet zu login.php weiter.
 */

// Zusätzliche Sicherheit: Session-Variablen leeren
$_SESSION = [];

// Falls Cookies für die Session genutzt werden, diese im Browser löschen
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Die eigentliche Logout-Logik deines Controllers aufrufen
UserController::logout();