<?php

namespace Postcardarchive\Models;

use PDO;

class PostcardMetaModel
{
    private $postcard_id;
    private $country;
    private $temperature;
    private $weather_condition;
    private $travel_mode;

    public function __construct(array $parameters)
    {
        $this->postcard_id       = $parameters['postcard_id']       ?? null;
        $this->country           = $parameters['country']           ?? null;
        $this->temperature       = $parameters['temperature']       ?? null;
        $this->weather_condition = $parameters['weather_condition'] ?? null;
        $this->travel_mode       = $parameters['travel_mode']       ?? null;
    }

    // Getters
    public function getPostcardId() { return $this->postcard_id; }
    public function getCountry() { return $this->country; }
    public function getTemperature() { return $this->temperature; }
    public function getWeatherCondition() { return $this->weather_condition; }
    public function getTravelMode() { return $this->travel_mode; }

    // Setters
    public function setPostcardId($postcard_id) { $this->postcard_id = $postcard_id; }
    public function setCountry($country) { $this->country = $country; }
    public function setTemperature($temperature) { $this->temperature = $temperature; }
    public function setWeatherCondition($weather_condition) { $this->weather_condition = $weather_condition; }
    public function setTravelMode($travel_mode) { $this->travel_mode = $travel_mode; }

    /**
     * Speichert oder aktualisiert die Metadaten in der Datenbank.
     * @param PDO $pdo
     */
    public function saveOrUpdate(PDO $pdo)
    {
        // Wir prüfen, ob bereits Metadaten für diese Postcard_ID existieren (1:1 Beziehung)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM postcard_meta WHERE postcard_id = :id");
        $stmtCheck->execute([':id' => $this->postcard_id]);
        $exists = $stmtCheck->fetchColumn() > 0;

        if (!$exists) 
        {
            $stmt = $pdo->prepare("INSERT INTO postcard_meta (postcard_id, country, temperature, weather_condition, travel_mode) VALUES (:postcard_id, :country, :temperature, :weather_condition, :travel_mode)");
        } 
        else 
        {
            $stmt = $pdo->prepare("UPDATE postcard_meta SET country = :country, temperature = :temperature, weather_condition = :weather_condition, travel_mode = :travel_mode WHERE postcard_id = :postcard_id");
        }

        $stmt->execute([
            ':postcard_id'       => $this->postcard_id,
            ':country'           => $this->country,
            ':temperature'       => $this->temperature,
            ':weather_condition' => $this->weather_condition,
            ':travel_mode'       => $this->travel_mode,
        ]);
    }

    /**
     * Wandelt das Model in ein assoziatives Array um.
     */
    public function toArray(): array
    {
        return [
            'postcard_id'       => $this->postcard_id,
            'country'           => $this->country,
            'temperature'       => $this->temperature,
            'weather_condition' => $this->weather_condition,
            'travel_mode'       => $this->travel_mode,
        ];
    }

    /**
     * Lädt die Metadaten basierend auf der ID der Postkarte.
     * @param PDO $pdo
     * @param int $postcard_id
     * @return PostcardMetaModel|null
     */
    public static function fromPostcardId(PDO $pdo, int $postcard_id): ?PostcardMetaModel
    {
        $stmt = $pdo->prepare("SELECT * FROM postcard_meta WHERE postcard_id = :postcard_id");
        $stmt->execute([':postcard_id' => $postcard_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $data ? new PostcardMetaModel($data) : null;
    }
}