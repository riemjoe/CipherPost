<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Postcardarchive\Utils\UtilsDatabase;
use Postcardarchive\Models\UserModel;

header('Content-Type: application/json');
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$pdo = UtilsDatabase::connect();
echo json_encode(UserModel::searchByUsername($pdo, $query));