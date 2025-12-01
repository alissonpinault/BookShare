<?php
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Bookshare\Services\Auth\LogoutService;

require __DIR__ . '/vendor/autoload.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        LogoutService::logout();
        http_response_code(200);
        echo json_encode(['success' => true]);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }

    exit;
}

// Méthode non autorisée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
