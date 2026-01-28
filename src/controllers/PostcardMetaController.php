<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\PostcardModel;
use Postcardarchive\Models\PostcardMetaModel;
use Postcardarchive\Utils\UtilsDatabase;
use PDO;

class PostcardMetaController
{
    /**
     * Ruft Wetterinformationen basierend auf Koordinaten ab.
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
                    'weather_code' => $data['current_weather']['weathercode']
                ];
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;
    }

    /**
     * Führt ein Reverse-Geocoding durch, um Land UND Stadt zu ermitteln.
     */
    private static function getLocationDetails($latitude, $longitude)
    {
        if (!$latitude || !$longitude) return null;

        // Zoom 10 liefert eine gute Balance zwischen Stadt-Details und Performance
        $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$latitude}&lon={$longitude}&zoom=10&addressdetails=1&accept-language=de";

        $options = [
            "http" => [
                "header" => "User-Agent: PostcardArchive/1.0 (dein-email@beispiel.de)\r\n",
                "timeout" => 5
            ]
        ];
        
        $context = stream_context_create($options);

        try {
            $response = @file_get_contents($url, false, $context);
            if ($response === false) return null;

            $data = json_decode($response, true);
            $address = $data['address'] ?? [];

            // Ermittlung der Stadt (Nominatim nutzt verschiedene Keys je nach Größe des Ortes)
            $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['suburb'] ?? $address['city_district'] ?? null;
            
            // Ermittlung des Landes
            $country = $address['country'] ?? $address['country_name'] ?? null;

            return [
                'city' => $city,
                'country' => $country
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Erstellt und speichert die Metadaten.
     */
    public static function createPostcardMeta(PostcardModel $postcard, array $metaData)
    {
        $pdo = UtilsDatabase::connect();

        // 1. Wetterdaten abrufen
        $weather = self::getWeatherInformation($postcard->getLatitude(), $postcard->getLongitude());

        // 2. Location Details (Land & Stadt) abrufen
        $location = self::getLocationDetails($postcard->getLatitude(), $postcard->getLongitude());

        // 3. Model instanziieren
        // Hinweis: Stelle sicher, dass dein PostcardMetaModel und die DB-Tabelle das Feld 'city' besitzen
        $meta = new PostcardMetaModel([
            'postcard_id'       => $postcard->getId(),
            'country'           => $location['country'] ?? null,
            'city'              => $location['city'] ?? 'Unbekannt',
            'temperature'       => $weather['temperature'] ?? null,
            'weather_condition' => self::mapWeatherCode($weather['weather_code'] ?? null),
            'travel_mode'       => $metaData['travel_mode'] ?? '🚗'
        ]);

        // 4. In Datenbank persistieren
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
            80 => 'Regenschauer',
            95 => 'Gewitter'
        ];
        return $codes[$code] ?? 'Unbekannt';
    }
}