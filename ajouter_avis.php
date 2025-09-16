<?php
require __DIR__ . '/vendor/autoload.php';
use MongoDB\Client;

$mongoClient = new Client("mongodb://mongo:27017");
$mongoDb = $mongoClient->bookshare;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $utilisateur_id = 1;
    $livre_id = $_POST['livre_id'];
    $commentaire = $_POST['commentaire'];
    $note = (int)$_POST['note'];

    $mongoDb->feedbacks->insertOne([
        'utilisateur_id' => $utilisateur_id,
        'livre_id' => $livre_id,
        'commentaire' => $commentaire,
        'note' => $note,
        'timestamp' => new MongoDB\BSON\UTCDateTime()
    ]);

    header("Location: index.php");
    exit;
}

// Pour l'exemple simple, on met une liste fixe de livres
$livres = [
    ['id'=>1,'titre'=>'1984'],
    ['id'=>2,'titre'=>'Le Petit Prince']
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
