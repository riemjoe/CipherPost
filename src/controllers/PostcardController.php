<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\PostcardModel;
use Postcardarchive\Utils\UtilsDatabase;
use Ramsey\Uuid\Uuid;

class PostcardController
{
    private static string $uploadDir = __DIR__ . '/../../data/uploads/';

    /**
     * Erstellt eine neue Postkarte und speichert die Bilder im Dateisystem.
     */
    public static function createPostcard($frontBinary, $backBinary, $latitude, $longitude)
    {
        $stampCode = self::generateStampCode();
        $createdAt = date('Y-m-d H:i:s');

        // Verzeichnis sicherstellen
        if (!is_dir(self::$uploadDir)) {
            mkdir(self::$uploadDir, 0777, true);
        }

        // Dateinamen generieren (Verschlüsselt gespeichert)
        $frontFilename = "front_" . $stampCode . ".bin";
        $backFilename = "back_" . $stampCode . ".bin";

        // Binärdaten in Dateien schreiben
        file_put_contents(self::$uploadDir . $frontFilename, $frontBinary);
        file_put_contents(self::$uploadDir . $backFilename, $backBinary);

        $postcardData = [
            'stamp_code'   => $stampCode,
            'front_image'  => $frontFilename, // Nur der Dateiname in die DB
            'back_image'   => $backFilename,  // Nur der Dateiname in die DB
            'latitude'     => $latitude,
            'longitude'    => $longitude,
            'created_at'   => $createdAt,
        ];

        $postcard = new PostcardModel($postcardData);
        $postcard->saveOrUpdate(UtilsDatabase::connect());
        return $postcard;
    }

    /**
     * Lädt eine Postkarte und füllt die Image-Properties mit dem Dateiinhalt.
     */
    public static function getPostcardByStampCode(string $stampCode): ?PostcardModel
    {
        $stampCode = trim($stampCode);
        $pdo = UtilsDatabase::connect();
        $stmt = $pdo->prepare("SELECT * FROM postcards WHERE stamp_code = :stamp_code");
        $stmt->execute([':stamp_code' => $stampCode]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data) {
            $postcard = new PostcardModel($data);
            
            // Dateiinhalt (verschlüsselt) wieder in das Model laden
            $frontPath = self::$uploadDir . $postcard->getFrontImage();
            $backPath = self::$uploadDir . $postcard->getBackImage();

            if (file_exists($frontPath)) {
                $postcard->setFrontImage(file_get_contents($frontPath));
            }
            if (file_exists($backPath)) {
                $postcard->setBackImage(file_get_contents($backPath));
            }

            return $postcard;
        }
        return null;
    }

    private static function generateStampCode()
    {
        return Uuid::uuid6()->toString();
    }
}