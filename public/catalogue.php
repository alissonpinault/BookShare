<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDB = $services['mongoDB'] ?? null;

if ($mongoDB === null) {
    $mongoClient = new Client('mongodb://localhost:27017');
    $mongoDB = $mongoClient->selectDatabase('bookshare');
}

session_start();

$utilisateurId = $_SESSION['utilisateur_id'] ?? null;
$pseudo = $_SESSION['pseudo'] ?? 'guest';

$stmt = $pdo->query('SELECT * FROM livres ORDER BY titre');
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($livres as $livre) {
    echo "<h3>{$livre['titre']} - {$livre['auteur']}</h3>";
    echo "<p>Genre: {$livre['genre']} | Annee: {$livre['annee_publication']}</p>";
    echo '<p>Disponibilite: ' . ($livre['disponibilite'] ? 'Oui' : 'Non') . "</p><hr>";

    $mongoDB->stats_livres->updateOne(
        ['livre_id' => (int) $livre['livre_id']],
        [
            '$inc' => ['vues' => 1],
            '$set' => ['dernier_acces' => new UTCDateTime()],
        ],
        ['upsert' => true]
    );
}

if ($utilisateurId) {
    $mongoDB->logs_reservation->insertOne([
        'utilisateur_id' => $utilisateurId,
        'action' => 'consultation_catalogue',
        'date' => new UTCDateTime(),
    ]);
}
