<?php

namespace Postcardarchive\Controllers;

use Postcardarchive\Models\UserModel;
use Postcardarchive\Utils\UtilsDatabase;
use PDO;

class UserController
{
    /**
     * Registriert einen neuen Benutzer
     */
    public static function register(string $username, string $email, string $password): bool
    {
        $pdo = UtilsDatabase::connect();

        // Prüfen, ob Benutzer bereits existiert
        if (UserModel::fromUsername($pdo, $username)) {
            return false;
        }

        $user = new UserModel([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $user->saveOrUpdate($pdo);
        return true;
    }

    /**
     * Authentifiziert einen Benutzer
     */
    public static function login(string $username, string $password): ?UserModel
    {
        $pdo = UtilsDatabase::connect();
        $user = UserModel::fromUsername($pdo, $username);

        if ($user && password_verify($password, $user->getPasswordHash())) {
            $_SESSION['user_id'] = $user->getId();
            $_SESSION['username'] = $user->getUsername();
            return $user;
        }

        return null;
    }

    public static function logout()
    {
        session_destroy();
        header("Location: login.php");
        exit;
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
}