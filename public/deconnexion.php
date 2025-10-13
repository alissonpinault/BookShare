<?php

declare(strict_types=1);

session_start();

// Supprime toutes les variables de session
$_SESSION = [];

// Détruit la session
session_destroy();

// Redirection vers la page d'accueil
header('Location: index.php');
exit;
