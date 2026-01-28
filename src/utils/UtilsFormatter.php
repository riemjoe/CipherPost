<?php

namespace Postcardarchive\Utils;

class UtilsFormatter
{
    private static ?array $countryMapping = null;
    private static string $cachePath = __DIR__ . '/../../data/countries_cache.json';

    /**
     * Holt den ISO 3166-1 Alpha-3 Code via REST Countries API oder Cache.
     */
    public static function countryToIso31661Alpha3(string $countryName): ?string
    {
        if (self::$countryMapping === null) {
            self::loadMapping();
        }

        $countryName = trim(mb_strtolower($countryName, 'UTF-8'));

        // 1. Suche im Mapping
        if (isset(self::$countryMapping[$countryName])) {
            return self::$countryMapping[$countryName];
        }

        // 2. Fallback: Falls der Input bereits ein gültiger Alpha-3 Code ist
        if (strlen($countryName) === 3 && ctype_alpha($countryName)) {
            return strtoupper($countryName);
        }

        return null;
    }

    private static function loadMapping(): void
    {
        // Prüfe, ob Cache existiert und nicht älter als 30 Tage ist
        if (file_exists(self::$cachePath) && (time() - filemtime(self::$cachePath) < 2592000)) {
            self::$countryMapping = json_decode(file_get_contents(self::$cachePath), true);
            return;
        }

        self::refreshCacheFromApi();
    }

    private static function refreshCacheFromApi(): void
    {
        $url = "https://restcountries.com/v3.1/all?fields=name,cca3,translations";
        self::$countryMapping = [];

        try {
            $response = @file_get_contents($url);
            if ($response === false) throw new \Exception("API nicht erreichbar");

            $countries = json_decode($response, true);

            foreach ($countries as $country) {
                $alpha3 = $country['cca3']; // z.B. "DEU"
                
                // Füge englischen Namen hinzu
                $commonName = mb_strtolower($country['name']['common'], 'UTF-8');
                self::$countryMapping[$commonName] = $alpha3;

                // Füge Übersetzungen hinzu (wichtig für "Deutschland", "Germany", etc.)
                if (isset($country['translations'])) {
                    foreach ($country['translations'] as $translation) {
                        $transName = mb_strtolower($translation['common'], 'UTF-8');
                        self::$countryMapping[$transName] = $alpha3;
                    }
                }
            }

            // Speichere in Cache-Datei
            if (!is_dir(dirname(self::$cachePath))) {
                mkdir(dirname(self::$cachePath), 0777, true);
            }
            file_put_contents(self::$cachePath, json_encode(self::$countryMapping));

        } catch (\Exception $e) {
            // Im Fehlerfall leeres Array, um Absturz zu vermeiden
            self::$countryMapping = [];
        }
    }
}