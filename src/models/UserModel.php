<?php

namespace Postcardarchive\Models;

use PDO;

class UserModel
{
    private $id;
    private $username;
    private $passwordHash;
    private $email;
    private $created_at;

    public function __construct(array $parameters)
    {
        $this->id           = $parameters['id']            ?? null;
        $this->username     = $parameters['username']      ?? null;
        $this->passwordHash = $parameters['password_hash'] ?? null;
        $this->email        = $parameters['email']         ?? null;
        $this->created_at   = $parameters['created_at']    ?? null;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getPasswordHash() { return $this->passwordHash; }
    public function getEmail() { return $this->email; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setUsername($username) { $this->username = $username; }
    public function setPasswordHash($passwordHash) { $this->passwordHash = $passwordHash; }
    public function setEmail($email) { $this->email = $email; }

    /**
     * Speichert oder aktualisiert den Benutzer in der Datenbank.
     */
    public function saveOrUpdate(PDO $pdo)
    {
        if ($this->id === null) 
        {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, created_at) VALUES (:username, :password_hash, :email, :created_at)");
            $stmt->execute([
                ':username'      => $this->username,
                ':password_hash' => $this->passwordHash,
                ':email'         => $this->email,
                ':created_at'    => $this->created_at,
            ]);
            $this->id = $pdo->lastInsertId();
        } 
        else 
        {
            $stmt = $pdo->prepare("UPDATE users SET username = :username, password_hash = :password_hash, email = :email WHERE id = :id");
            $stmt->execute([
                ':username'      => $this->username,
                ':password_hash' => $this->passwordHash,
                ':email'         => $this->email,
                ':id'            => $this->id,
            ]);
        }
    }

    /**
     * Löscht den Benutzer aus der Datenbank.
     */
    public function delete(PDO $pdo)
    {
        if ($this->id !== null) 
        {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute([':id' => $this->id]);
            $this->id = null;
        }
    }

    /**
     * Gibt die Benutzerdaten als assoziatives Array zurück.
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'username'      => $this->username,
            'password_hash' => $this->passwordHash,
            'email'         => $this->email,
            'created_at'    => $this->created_at,
        ];
    }

    /**
     * Erstellt eine UserModel-Instanz aus einem assoziativen Array.
     */
    public static function fromArray(array $data): UserModel
    {
        return new UserModel($data);
    }

    /**
     * Holt einen Benutzer anhand seiner ID.
     */
    public static function fromId(PDO $pdo, int $id): ?UserModel
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new UserModel($data) : null;
    }

    /**
     * Holt einen Benutzer anhand seines Benutzernamens.
     */
    public static function fromUsername(PDO $pdo, string $username): ?UserModel
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new UserModel($data) : null;
    }

    public static function searchByUsername(PDO $pdo, string $query): array
    {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username LIKE :query LIMIT 10");
        $stmt->execute([':query' => '%' . $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Holt alle Benutzer aus der Datenbank.
     */
    public static function fetchAll(PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT * FROM users");
        $users = [];
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) 
        {
            $users[] = new UserModel($data);
        }
        return $users;
    }
}