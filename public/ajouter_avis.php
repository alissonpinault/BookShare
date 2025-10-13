<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$mongoDb = $services['mongoDB'] ?? null;

if ($mongoDb === null) {
    $mongoClient = new Client('mongodb://localhost:27017');
    $mongoDb = $mongoClient->selectDatabase('bookshare');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateurId = 1;
    $livreId = $_POST['livre_id'];
    $commentaire = $_POST['commentaire'];
    $note = (int) $_POST['note'];

    $mongoDb->feedbacks->insertOne([
        'utilisateur_id' => $utilisateurId,
        'livre_id' => $livreId,
        'commentaire' => $commentaire,
        'note' => $note,
        'timestamp' => new UTCDateTime(),
    ]);

    header('Location: index.php');
    exit;
}

$livres = [
    ['id' => 1, 'titre' => '1984'],
    ['id' => 2, 'titre' => 'Le Petit Prince'],
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Laisser un avis</title>
</head>
<body>
<h1>Laisser un avis</h1>
<form method="post">
    <label>Livre :
        <select name="livre_id">
            <?php foreach ($livres as $livre): ?>
                <option value="<?= $livre['id'] ?>"><?= $livre['titre'] ?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <label>Commentaire : <textarea name="commentaire" required></textarea></label><br>
    <label>Note (1-5) : <input type="number" name="note" min="1" max="5" required></label><br>
    <button type="submit">Envoyer</button>
</form>
<a href="index.php">Retour</a>
</body>
</html>
