<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\PostcardModel;
use Postcardarchive\Models\PostcardMetaModel;
use Postcardarchive\Utils\Database; // Angenommene Database-Utility Klasse
use PDO;
use Postcardarchive\Utils\UtilsDatabase;

class PostcardMetaController
{
    /**
     * Ruft Wetterinformationen basierend auf Koordinaten ab.
     * Nutzt die Open-Meteo API (Open Source, kein Key benötigt).
     */
    private static function getWeatherInformation($latitude, $longitude)
    {
        if (!$latitude || !$longitude) return null;

        $url = "https://api.open-meteo.com/v1/forecast?latitude={$latitude}&longitude={$longitude}&current_weather=true";
        
        try {
            $response = @file_get_contents($url);
            if ($response === false) return null;

            $data = json_decode($response, true);
            if (isset($data['current_weather'])) {
                return [
                    'temperature' => $data['current_weather']['temperature'],
                    'weather_code' => $data['current_weather']['weathercode'] // Code für Symbole (z.B. 0 = Klar)
                ];
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    private static function getCountryFromCoordinates($latitude, $longitude)
    {
        if (!$latitude || !$longitude) return null;

        // Parameter &accept-language=de hinzugefügt für deutsche Rückgabe
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=3&addressdetails=1&accept-language=de";

        $options = [
            "http" => [
                "header" => "User-Agent: PostcardArchive/1.0 (dein-email@beispiel.de)\r\n",
                "timeout" => 5 // Timeout hinzufügen, damit die Seite nicht hängen bleibt
            ]
        ];
        
        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);
            
            // Nominatim liefert bei zoom=3 meist 'country', 
            // sicherheitshalber prüfen wir auch 'country_name'
            if (isset($data['address']['country'])) {
                return $data['address']['country'];
            } elseif (isset($data['address']['country_name'])) {
                return $data['address']['country_name'];
            }
            
        } catch (\Exception $e) {
            return null;
        }
        
        return null;
    }

    /**
     * Erstellt und speichert die Metadaten für eine existierende Postkarte.
     * * @param PostcardModel $postcard
     * @param array $metaData Enthält z.B. ['country', 'travel_mode']
     */
    public static function createPostcardMeta(PostcardModel $postcard, array $metaData)
    {
        $pdo = UtilsDatabase::connect();

        // 1. Wetterdaten live abrufen
        $weather = self::getWeatherInformation($postcard->getLatitude(), $postcard->getLongitude());

        // 2. Model instanziieren
        $meta = new PostcardMetaModel([
            'postcard_id'       => $postcard->getId(),
            'country'           => self::getCountryFromCoordinates($postcard->getLatitude(), $postcard->getLongitude()),
            'temperature'       => $weather['temperature'] ?? null,
            'weather_condition' => self::mapWeatherCode($weather['weather_code'] ?? null),
            'travel_mode'       => $metaData['travel_mode'] ?? '🚗'
        ]);

        // 3. In Datenbank persistieren
        $meta->saveOrUpdate($pdo);

        return $meta;
    }

    /**
     * Holt die Metadaten zu einer Postkarten-ID.
     */
    public static function getPostcardMetaByPostcardId(int $postcardId)
    {
        $pdo = UtilsDatabase::connect();
        return PostcardMetaModel::fromPostcardId($pdo, $postcardId);
    }

    /**
     * Hilfsmethode: Wandelt Wetter-Codes (WMO) in lesbaren Text um.
     */
    private static function mapWeatherCode($code)
    {
        $codes = [
            0 => 'Sonnig',
            1 => 'Leicht bewölkt',
            2 => 'Teils bewölkt',
            3 => 'Bedeckt',
            45 => 'Nebelig',
            61 => 'Leichter Regen',
            95 => 'Gewitter'
        ];
        return $codes[$code] ?? 'Unbekannt';
    }
}