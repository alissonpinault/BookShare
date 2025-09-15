<?php
// --------------------
// Connexion MySQL via PDO
// --------------------
try {
    $pdo = new PDO(
        'mysql:host=mysql;dbname=bookshare;charset=utf8',
        'user',
        'password'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur MySQL : " . $e->getMessage());
}

// --------------------
// Connexion MongoDB
// --------------------
require 'vendor/autoload.php';
use MongoDB\Client;

$mongoClient = new Client("mongodb://mongo:27017");
$mongoDB = $mongoClient->bookshare;
?>