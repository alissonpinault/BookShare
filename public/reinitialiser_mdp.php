<?php

declare(strict_types=1);

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$success = false;
$user = null;
$token = $_GET['token'] ?? '';

if ($token !== '') {
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE token_reset = ? AND reset_expire > NOW()');
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $message = '❌ Lien invalide ou expiré.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['mdp'] ?? '';
        $confirmation = $_POST['mdp_confirm'] ?? '';

        if ($password !== $confirmation) {
            $message = 'Les mots de passe ne correspondent pas.';
        } elseif (strlen($password) < 6) {
            $message = 'Le mot de passe doit contenir au moins 6 caractères.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $update = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, token_reset = NULL, reset_expire = NULL WHERE utilisateur_id = ?');
            $update->execute([$hash, $user['utilisateur_id']]);

            $success = true;
            $message = 'Mot de passe réinitialisé avec succès. Tu peux maintenant te connecter.';
        }
    }
} else {
    $message = '❌ Aucun lien valide fourni.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réinitialiser le mot de passe - BookShare</title>
<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <h2>Réinitialiser le mot de passe</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message <?= $success ? 'success' : '' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($user && !$success): ?>
        <form method="post">
            <input type="password" name="mdp" placeholder="Nouveau mot de passe" required>
            <input type="password" name="mdp_confirm" placeholder="Confirmer le mot de passe" required>
            <button type="submit">Mettre à jour</button>
        </form>
    <?php endif; ?>

    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Retour à la connexion</button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const alert = document.getElementById("alert-message");
  if (alert) {
    setTimeout(() => {
      alert.classList.add("hide");
      setTimeout(() => alert.remove(), 800);
    }, 5000);
  }
});
</script>
</body>
</html>
