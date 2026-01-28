<?php

use Postcardarchive\Utils\UtilsDatabase;

require_once __DIR__ . '/../vendor/autoload.php';

$db = UtilsDatabase::connect();

$tables = ['postcards', 'postcard_meta', 'user_stamps', 'users'];
foreach ($tables as $table) {
    $db->exec("DELETE FROM $table");
}
echo "Datenbanktabellen wurden geleert.<br>";

# Lösche alle hochgeladenen Dateien
$uploadDir = __DIR__ . '/../uploads/';
$files = glob($uploadDir . '*');
foreach ($files as $file) {
    if (is_file($file)) {
        unlink($file);
    }
}
echo "Upload-Verzeichnis wurde geleert.<br>";

?>