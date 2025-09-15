<?php
require_once 'db.php';
session_start();

$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;
$pseudo = $_SESSION['pseudo'] ?? 'guest';

// Récupérer livres MySQL
$stmt = $pdo->query("SELECT * FROM livres ORDER BY titre");
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($livres as $livre) {
    echo "<h3>{$livre['titre']} - {$livre['auteur']}</h3>";
    echo "<p>Genre: {$livre['genre']} | Année: {$livre['annee_publication']}</p>";
    echo "<p>Disponibilité: " . ($livre['disponibilite'] ? 'Oui' : 'Non') . "</p><hr>";

    // Stats MongoDB
    $mongoDB->stats_livres->updateOne(
        ['livre_id' => (int)$livre['livre_id']],
        [
            '$inc' => ['vues' => 1],
            '$set' => ['dernier_acces' => new MongoDB\BSON\UTCDateTime()]
        ],
        ['upsert' => true]
    );
}

// Log global consultation catalogue
if ($utilisateur_id) {
    $mongoDB->logs_reservation->insertOne([
        'utilisateur_id' => $utilisateur_id,
        'action' => 'consultation_catalogue',
        'date' => new MongoDB\BSON\UTCDateTime()
    ]);
}
