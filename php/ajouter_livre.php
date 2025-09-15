<?php
require __DIR__ . '/vendor/autoload.php';
use MongoDB\Client;

$pdo = new PDO('mysql:host=mysql;dbname=bookshare;charset=utf8', 'user', 'password', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$mongoClient = new Client("mongodb://mongo:27017");
$mongoDb = $mongoClient->bookshare;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $auteur = $_POST['auteur'];
    $genre = $_POST['genre'];
    $proprietaire_id = 1; // exemple fixe pour l'utilisateur

    $stmt = $pdo->prepare("INSERT INTO livres (titre,auteur,genre,proprietaire_id) VALUES (?,?,?,?)");
    $stmt->execute([$titre,$auteur,$genre,$proprietaire_id]);

    // Log dans MongoDB
    $mongoDb->logs->insertOne([
        'utilisateur_id' => $proprietaire_id,
        'action' => 'ajout_livre',
        'titre' => $titre,
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);

    header("Location: index.php");
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
