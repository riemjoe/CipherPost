<?php

namespace Postcardarchive\Utils;
class UtilsDatabase
{
    public static function connect()
    {
        $config = new UtilsConfiguration("app");
        $dbPath = $config->get("database-path");

        $dbPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . $dbPath;

        if (!file_exists($dbPath)) 
        {
            $dir = dirname($dbPath);
            if (!is_dir($dir)) 
            {
                mkdir($dir, 0777, true);
            }
        }

        $pdo = new \PDO("sqlite:" . $dbPath);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    public static function initializeDatabase()
    {
        $pdo = self::connect();
        
        // Postkarte
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS postcards (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                stamp_code TEXT UNIQUE,
                front_image BLOB,
                back_image BLOB,
                latitude REAL,
                longitude REAL,
                created_at TEXT
            );
        ");

        // Postkarten-Metadaten
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS postcard_meta (
                postcard_id INTEGER PRIMARY KEY,
                country TEXT,
                temperature REAL,
                weather_condition TEXT,
                travel_mode TEXT,
                FOREIGN KEY(postcard_id) REFERENCES postcards(id) ON DELETE CASCADE
            );
        ");
    }
}


?>