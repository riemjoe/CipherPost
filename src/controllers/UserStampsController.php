<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\UserStamps;
use Postcardarchive\Utils\UtilsDatabase;
use PDO;
use Postcardarchive\Utils\UtilsLogging;

class UserStampsController
{
    /**
     * Speichert einen neu erstellten Stamp direkt beim Erstellvorgang.
     * Hier markieren wir 'was_received' als 0 (selbst erstellt).
     */
    public static function addCreatedStamp(int $userId, string $stampCode, string $privateKey, ?string $country): bool
    {
        $pdo = UtilsDatabase::connect();

        $stamp = new UserStamps([
            'user_id'      => $userId,
            'stamp_code'   => $stampCode,
            'private_key'  => $privateKey,
            'country'      => $country ?? 'Unbekannt',
            'was_received' => 0,
            'created_at'   => date('Y-m-d H:i:s')
        ]);

        try {
            $stamp->saveOrUpdate($pdo);
            return true;
        } catch (\Exception $e) {
            UtilsLogging::error("Fehler beim Speichern des erstellten Stamps: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Speichert einen empfangenen Stamp (z.B. wenn man einen Schlüssel hochlädt).
     * Hier markieren wir 'was_received' als 1 (empfangen).
     */
    public static function addReceivedStamp(int $userId, string $stampCode, string $privateKey, ?string $country): bool
    {
        $pdo = UtilsDatabase::connect();

        // Prüfen, ob der User diesen Stamp bereits in seiner Sammlung hat
        $stmt = $pdo->prepare("SELECT id FROM user_stamps WHERE user_id = :u AND stamp_code = :s");
        $stmt->execute([':u' => $userId, ':s' => $stampCode]);
        if ($stmt->fetch()) {
            return true; // Bereits vorhanden
        }

        $stamp = new UserStamps([
            'user_id'      => $userId,
            'stamp_code'   => $stampCode,
            'private_key'  => $privateKey,
            'country'      => $country ?? 'Empfangen',
            'was_received' => 1,
            'created_at'   => date('Y-m-d H:i:s')
        ]);

        try {
            $stamp->saveOrUpdate($pdo);
            return true;
        } catch (\Exception $e) {
            UtilsLogging::error("Fehler beim Speichern des empfangenen Stamps: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lädt alle Briefmarken eines Benutzers (Brieftasche).
     * @return UserStamps[]
     */
    public static function getUserWallet(int $userId): array
    {
        $pdo = UtilsDatabase::connect();
        return UserStamps::fetchAllByUserId($pdo, $userId);
    }

    /**
     * Holt einen spezifischen Stamp aus der Sammlung des Users anhand des Codes.
     */
    public static function getStampFromWallet(int $userId, string $stampCode): ?UserStamps
    {
        $pdo = UtilsDatabase::connect();
        $stmt = $pdo->prepare("SELECT * FROM user_stamps WHERE user_id = :u AND stamp_code = :s LIMIT 1");
        $stmt->execute([':u' => $userId, ':s' => $stampCode]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? UserStamps::fromArray($data) : null;
    }
}