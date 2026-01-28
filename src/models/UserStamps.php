<?php

namespace Postcardarchive\Models;

use PDO;

class UserStamps
{
    private $id;
    private $user_id;
    private $country;
    private $stamp_code;
    private $private_key;
    private $was_received;
    private $created_at;

    public function __construct(array $parameters)
    {
        $this->id           = $parameters['id']           ?? null;
        $this->user_id      = $parameters['user_id']      ?? null;
        $this->country      = $parameters['country']      ?? null;
        $this->stamp_code   = $parameters['stamp_code']   ?? null;
        $this->private_key  = $parameters['private_key']  ?? null;
        $this->was_received = $parameters['was_received'] ?? 0;
        $this->created_at   = $parameters['created_at']   ?? null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->user_id; }
    public function getCountry() { return $this->country; }
    public function getStampCode() { return $this->stamp_code; }
    public function getPrivateKey() { return $this->private_key; }
    public function getWasReceived() { return $this->was_received; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setUserId($user_id) { $this->user_id = $user_id; }
    public function setCountry($country) { $this->country = $country; }
    public function setStampCode($stamp_code) { $this->stamp_code = $stamp_code; }
    public function setPrivateKey($private_key) { $this->private_key = $private_key; }
    public function setWasReceived($was_received) { $this->was_received = $was_received; }

    /**
     * Speichert oder aktualisiert den Stamp-Eintrag in der Datenbank.
     */
    public function saveOrUpdate(PDO $pdo)
    {
        if ($this->id === null) 
        {
            $stmt = $pdo->prepare("INSERT INTO user_stamps (user_id, country, stamp_code, private_key, was_received, created_at) VALUES (:user_id, :country, :stamp_code, :private_key, :was_received, :created_at)");
            $stmt->execute([
                ':user_id'      => $this->user_id,
                ':country'      => $this->country,
                ':stamp_code'   => $this->stamp_code,
                ':private_key'  => $this->private_key,
                ':was_received' => $this->was_received,
                ':created_at'   => $this->created_at,
            ]);
            $this->id = $pdo->lastInsertId();
        } 
        else 
        {
            $stmt = $pdo->prepare("UPDATE user_stamps SET user_id = :user_id, country = :country, stamp_code = :stamp_code, private_key = :private_key, was_received = :was_received WHERE id = :id");
            $stmt->execute([
                ':user_id'      => $this->user_id,
                ':country'      => $this->country,
                ':stamp_code'   => $this->stamp_code,
                ':private_key'  => $this->private_key,
                ':was_received' => $this->was_received,
                ':id'           => $this->id,
            ]);
        }
    }

    /**
     * Löscht den Stamp-Eintrag aus der Datenbank.
     */
    public function delete(PDO $pdo)
    {
        if ($this->id !== null) 
        {
            $stmt = $pdo->prepare("DELETE FROM user_stamps WHERE id = :id");
            $stmt->execute([':id' => $this->id]);
            $this->id = null;
        }
    }

    /**
     * Gibt die Daten als assoziatives Array zurück.
     */
    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'user_id'      => $this->user_id,
            'country'      => $this->country,
            'stamp_code'   => $this->stamp_code,
            'private_key'  => $this->private_key,
            'was_received' => $this->was_received,
            'created_at'   => $this->created_at,
        ];
    }

    /**
     * Erstellt eine Instanz aus einem assoziativen Array.
     */
    public static function fromArray(array $data): UserStamps
    {
        return new UserStamps($data);
    }

    /**
     * Holt alle Stamps für einen bestimmten Benutzer.
     */
    public static function fetchAllByUserId(PDO $pdo, int $user_id): array
    {
        $stmt = $pdo->prepare("SELECT * FROM user_stamps WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt->execute([':user_id' => $user_id]);
        $stamps = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) 
        {
            $stamps[] = new UserStamps($data);
        }
        return $stamps;
    }

    /**
     * Prüft, ob ein Benutzer bereits einen bestimmten Stamp-Code besitzt.
     */
    public static function fromStampCode(PDO $pdo, string $stamp_code): ?UserStamps
    {
        $stmt = $pdo->prepare("SELECT * FROM user_stamps WHERE stamp_code = :stamp_code");
        $stmt->execute([':stamp_code' => $stamp_code]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new UserStamps($data) : null;
    }
}