<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDb = $services['mongoDB'] ?? null;

if ($mongoDb === null) {
    $mongoClient = new Client('mongodb://localhost:27017');
    $mongoDb = $mongoClient->selectDatabase('bookshare');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $genre = $_POST['genre'];
    $proprietaireId = 1;

    $stmt = $pdo->prepare('INSERT INTO livres (titre, auteur, genre, proprietaire_id) VALUES (?, ?, ?, ?)');
    $stmt->execute([$titre, $auteur, $genre, $proprietaireId]);

    if ($mongoDb) {
        $mongoDb->logs->insertOne([
            'utilisateur_id' => $proprietaireId,
            'action' => 'ajout_livre',
            'titre' => $titre,
            'timestamp' => new UTCDateTime(),
        ]);
    }

    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un livre</title>
</head>
<body>
<h1>Ajouter un livre</h1>
<form method="post">
    <label>Titre : <input type="text" name="titre" required></label><br>
    <label>Auteur : <input type="text" name="auteur" required></label><br>
    <label>Genre : <input type="text" name="genre" required></label><br>
    <button type="submit">Ajouter</button>
</form>
<a href="index.php">Retour</a>
</body>
</html>
