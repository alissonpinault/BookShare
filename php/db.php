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
require 'vendor/autoload.php';
use MongoDB\Client;

$mongoUri = getenv('MONGODB_URI') ?: 'mongodb://mongo:27017'; // URI locale ou Heroku
$mongoClient = new Client($mongoUri);
$mongoDB = $mongoClient->bookshare;
?>
