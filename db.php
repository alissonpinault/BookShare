<?php
// --------------------
// Connexion MySQL via JawsDB (Heroku)
// --------------------
$cleardb_url = parse_url(getenv("JAWSDB_URL")); // récupère l'URL de l'addon Heroku
$servername = $cleardb_url["host"];
$username = $cleardb_url["user"];
$password = $cleardb_url["pass"];
$dbname = substr($cleardb_url["path"], 1);

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur MySQL : " . $e->getMessage());
}

// --------------------
// Connexion MongoDB
// --------------------
require __DIR__ . '/vendor/autoload.php'; // Charge l'autoloader de MongoDB

use MongoDB\Client;

// Récupération de l'URI MongoDB (d'abord depuis Heroku, sinon valeur par défaut locale)
$mongoUri = getenv('MONGODB_URI');
if (!$mongoUri) {
    die("Erreur : MONGODB_URI non défini. Configure ton URI MongoDB Atlas sur Heroku.");
}

// Connexion au cluster
try {
    $mongoClient = new Client($mongoUri);

    // Sélection de la base "bookshare"
    $mongoDB = $mongoClient->selectDatabase('bookshare');

} catch (Exception $e) {
    die("Erreur de connexion à MongoDB : " . $e->getMessage());
}
?>
