<?php

use Postcardarchive\Utils\UtilsFormatter;
use Postcardarchive\Utils\UtilsLogging;

session_start();

use Postcardarchive\Controllers\PostcardController;
use Postcardarchive\Controllers\PostcardMetaController;
use Postcardarchive\Controllers\UserStampsController; // Neu hinzugefügt
use Postcardarchive\Controllers\UserController;       // Neu hinzugefügt
use Postcardarchive\Utils\UtilsEncryptor;
use Postcardarchive\Models\StampCodeFileModel;

require_once __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $frontWebP = UtilsFormatter::compressImageData('front_image');
    $backWebP  = UtilsFormatter::compressImageData('back_image');

    if ($frontWebP && $backWebP) 
    {
        try 
        {
            $privateKeyObj = UtilsEncryptor::createPrivateKey();
            $publicKeyObj = UtilsEncryptor::getPublicKeyFromPrivateKey($privateKeyObj);
            
            $encryptedFront = UtilsEncryptor::encryptData($publicKeyObj, $frontWebP);
            $encryptedBack  = UtilsEncryptor::encryptData($publicKeyObj, $backWebP);

            $lat = !empty($_POST['lat']) ? (float)$_POST['lat'] : null;
            $lng = !empty($_POST['lng']) ? (float)$_POST['lng'] : null;
            
            $postcard = PostcardController::createPostcard($encryptedFront, $encryptedBack, $lat, $lng);
            $stampCode = $postcard->getStampCode();
            $privateKeyString = $privateKeyObj->toString('PKCS8');

            $meta = null;
            try 
            {
                $meta = PostcardMetaController::createPostcardMeta($postcard, [
                    'travel_mode' => $_POST['travel_mode'] ?? '🚗' 
                ]);
            } 
            catch (\Exception $metaEx) 
            {
                UtilsLogging::error("Metadaten-Fehler: " . $metaEx->getMessage());
            }

            if (UserController::isLoggedIn()) 
            {
                $userId = $_SESSION['user_id'];
                $country = $meta ? $meta->getCountry() : 'Unbekannt';
                
                UserStampsController::addCreatedStamp(
                    $userId, 
                    $stampCode, 
                    $privateKeyString, 
                    $country
                );

                UtilsLogging::debug("Postkarte $stampCode wurde dem Benutzer $userId zugewiesen.");
            }

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

        } 
        catch (\Exception $e) 
        {
            UtilsLogging::error("Fehler beim Erstellen der Postkarte: " . $e->getMessage());
            die("Fehler: " . $e->getMessage());
        }
    }
    else
    {
        UtilsLogging::error("Fehler: Ungültige Bilddaten hochgeladen.");
        die("Fehler: Ungültige Bilddaten hochgeladen.");
    }
}