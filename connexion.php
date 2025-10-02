<?php
require_once 'login.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'] ?? '';
    $mdp = $_POST['mdp'] ?? '';

    if (login($pseudo, $mdp)) {
        header('Location: index.php');
        exit;
    } else {
        $message = "Pseudo ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Connexion - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg" alt="Illustration" class="auth-illustration">
    <h2>BookShare</h2>

    <?php if ($message): ?>
        <div class="auth-error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="pseudo" placeholder="Pseudo" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>

    <button class="secondary-btn" onclick="window.location.href='index.php'">Retour à l'accueil</button>
    <button class="secondary-btn" onclick="window.location.href='inscription.php'">Créer un compte</button>
</div>

</body>
</html>