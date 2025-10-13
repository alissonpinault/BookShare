<?php

declare(strict_types=1);

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

$data = json_decode(file_get_contents('php://input'), true);

if(!$data) exit(json_encode(['success'=>false,'message'=>'Aucune donnÃ©e']));

$livre_id = $data['livre_id'];
$utilisateur_id = $data['utilisateur_id'];
$note = $data['note'];

// Upsert SQL : si la note existe, update sinon insert
$stmt = $pdo->prepare("
    INSERT INTO notes (livre_id, utilisateur_id, note) 
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE note = ?
");
$success = $stmt->execute([$livre_id, $utilisateur_id, $note, $note]);

echo json_encode(['success'=>$success]);

