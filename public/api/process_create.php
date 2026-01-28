<?php

use Postcardarchive\Controllers\PostcardMetaController;

session_start();

use Postcardarchive\Controllers\PostcardController;
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
            $privateKeyObj = UtilsEncryptor::createPrivateKey();
            $publicKeyObj = UtilsEncryptor::getPublicKeyFromPrivateKey($privateKeyObj);
            
            $encryptedFront = UtilsEncryptor::encryptData($publicKeyObj, $frontWebP);
            $encryptedBack  = UtilsEncryptor::encryptData($publicKeyObj, $backWebP);

            $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
            $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;
            
            $postcard = PostcardController::createPostcard($encryptedFront, $encryptedBack, $lat, $lng);
            $stampCode = $postcard->getStampCode();

            try {
                PostcardMetaController::createPostcardMeta($postcard, [
                    'travel_mode' => $_POST['travel_mode'] ?? '🚗' 
                ]);
            } catch (\Exception $metaEx) {
                // Ein Fehler bei den Metadaten (z.B. API-Timeout) sollte 
                // nicht den gesamten Prozess stoppen. Wir loggen ihn nur.
                error_log("Metadaten-Fehler: " . $metaEx->getMessage());
            }

            $fileModel = new StampCodeFileModel([
                'stamp_code'  => $stampCode,
                'private_key' => $privateKeyObj->toString('PKCS8')
            ]);

            // DATEN IN SESSION SPEICHERN STATT DOWNLOAD
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