<?php

session_start();

use Postcardarchive\Controllers\PostcardController;
use Postcardarchive\Controllers\PostcardMetaController;
use Postcardarchive\Controllers\UserStampsController; // Neu hinzugefügt
use Postcardarchive\Controllers\UserController;       // Neu hinzugefügt
use Postcardarchive\Utils\UtilsEncryptor;
use Postcardarchive\Models\StampCodeFileModel;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Hilfsfunktion zur Bildverarbeitung
 */
function processImageToWebP($fileInputName, $quality = 80) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath = $_FILES[$fileInputName]['tmp_name'];
    $info = getimagesize($tmpPath);
    
    if (!$info) return null;

    switch ($info[2]) {
        case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($tmpPath); break;
        case IMAGETYPE_PNG:  $image = imagecreatefrompng($tmpPath);  break;
        case IMAGETYPE_WEBP: $image = imagecreatefromwebp($tmpPath); break;
        default: return null;
    }

    ob_start();
    imagewebp($image, null, $quality);
    $binaryData = ob_get_clean();
    imagedestroy($image);

    return $binaryData;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $frontWebP = processImageToWebP('front_image');
    $backWebP  = processImageToWebP('back_image');

    if ($frontWebP && $backWebP) {
        try {
            // 1. Verschlüsselung & Key-Generierung
            $privateKeyObj = UtilsEncryptor::createPrivateKey();
            $publicKeyObj = UtilsEncryptor::getPublicKeyFromPrivateKey($privateKeyObj);
            
            $encryptedFront = UtilsEncryptor::encryptData($publicKeyObj, $frontWebP);
            $encryptedBack  = UtilsEncryptor::encryptData($publicKeyObj, $backWebP);

            $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
            $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;
            
            // 2. Postkarte erstellen
            $postcard = PostcardController::createPostcard($encryptedFront, $encryptedBack, $lat, $lng);
            $stampCode = $postcard->getStampCode();
            $privateKeyString = $privateKeyObj->toString('PKCS8');

            // 3. Metadaten (Land/Wetter) generieren
            $meta = null;
            try {
                $meta = PostcardMetaController::createPostcardMeta($postcard, [
                    'travel_mode' => $_POST['travel_mode'] ?? '🚗' 
                ]);
            } catch (\Exception $metaEx) {
                error_log("Metadaten-Fehler: " . $metaEx->getMessage());
            }

            if (UserController::isLoggedIn()) {
                $userId = $_SESSION['user_id'];
                $country = $meta ? $meta->getCountry() : 'Unbekannt';
                
                // Wir nutzen den UserStampsController um den Schlüssel in der DB zu hinterlegen
                UserStampsController::addCreatedStamp(
                    $userId, 
                    $stampCode, 
                    $privateKeyString, 
                    $country
                );
            }

            // 4. Schlüssel für Session/Download vorbereiten
            $fileModel = new StampCodeFileModel([
                'stamp_code'  => $stampCode,
                'private_key' => $privateKeyString
            ]);

            $_SESSION['last_key_file'] = [
                'filename' => "postcard_key_" . substr($stampCode, 0, 8) . ".json",
                'content'  => json_encode($fileModel->toArray(), JSON_PRETTY_PRINT)
            ];

            header("Location: ../index.php?success=1&code=" . urlencode($stampCode));
            exit;

        } catch (\Exception $e) {
            die("Fehler: " . $e->getMessage());
        }
    }
}