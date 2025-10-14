<?php

declare(strict_types=1);

use Bookshare\Services\Notes\NoteService;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    exit(json_encode(['success' => false, 'message' => 'Aucune donnee']));
}

$livreId = (int)($data['livre_id'] ?? 0);
$utilisateurId = (int)($data['utilisateur_id'] ?? 0);
$note = (int)($data['note'] ?? 0);

$success = $livreId > 0 && $utilisateurId > 0 && $note > 0
    ? NoteService::save($pdo, $livreId, $utilisateurId, $note)
    : false;

echo json_encode(['success' => $success]);
