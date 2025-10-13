<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use PDO;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDB = $services['mongoDB'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($mongoDB === null) {
    try {
        $mongoClient = new Client('mongodb://localhost:27017');
        $mongoDB = $mongoClient->selectDatabase('bookshare');
    } catch (\Throwable $e) {
        $mongoDB = null;
    }
}

$token = $_GET['token'] ?? '';
$message = '';

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE token_validation = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        if ((int)($user['est_valide'] ?? 0) === 1) {
            $message = '[OK] Ton compte est deja active ! Tu peux te connecter.';
        } else {
            $update = $pdo->prepare('UPDATE utilisateurs SET est_valide = 1, token_validation = NULL WHERE utilisateur_id = ?');
            $update->execute([$user['utilisateur_id']]);

            if ($mongoDB) {
                try {
                    $mongoDB->logs_connexion->insertOne([
                        'utilisateur_id' => (int)$user['utilisateur_id'],
                        'pseudo' => $user['pseudo'],
                        'date' => new UTCDateTime(),
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        'resultat' => 'validation_compte',
                    ]);
                } catch (\Throwable $e) {
                    error_log('Mongo log error: ' . $e->getMessage());
                }
            }

            $message = '[OK] Compte active avec succes ! Tu peux maintenant te connecter.';
        }
    } else {
        $message = '[ERREUR] Lien invalide ou deja utilise.';
    }
} else {
    $message = '[ERREUR] Aucun token fourni.';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Validation du compte - BookShare</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
    <link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <img src="assets/images/logo.jpg" alt="Logo" class="auth-illustration">
    <h2>Validation du compte</h2>

    <?php if ($message): ?>
        <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Se connecter</button>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const alert = document.getElementById('alert-message');
  if (alert) {
    setTimeout(() => {
      alert.classList.add('hide');
      setTimeout(() => alert.remove(), 800);
    }, 6000);
  }
});
</script>
</body>
</html>
