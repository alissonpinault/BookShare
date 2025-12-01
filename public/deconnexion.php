<?php
declare(strict_types=1);

header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// chemin correct vers autoload
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    echo json_encode(['error' => 'Autoload not found']);
    http_response_code(500);
    exit;
}
require $autoload;

use Bookshare\Services\Auth\LogoutService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        LogoutService::logout();
        echo json_encode(['success' => true]);
        http_response_code(200);
    } catch (\Throwable $e) {
        echo json_encode(['error' => $e->getMessage()]);
        http_response_code(500);
    }

    exit;
}

// Méthode non autorisée
echo json_encode(['error' => 'Méthode non autorisée']);
http_response_code(405);
