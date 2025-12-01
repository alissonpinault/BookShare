<?php
declare(strict_types=1);

use Bookshare\Services\Auth\LogoutService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    LogoutService::logout();
    
    // Réponse pour Fetch
    http_response_code(200);
    echo json_encode(['success' => true]);
    exit;
}

// Méthode non autorisée
http_response_code(405);
echo json_encode(['error' => 'Méthode non autorisée']);
