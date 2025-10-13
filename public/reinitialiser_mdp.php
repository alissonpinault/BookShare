<?php

declare(strict_types=1);

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$user = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // VÃ©rifie que le token existe et nâ€™est pas expirÃ©
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "âŒ Lien invalide ou expirÃ©.";
    }

    // Si formulaire soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
        $mdp = $_POST['mdp'] ?? '';
        $mdp_confirm = $_POST['mdp_confirm'] ?? '';

        if ($mdp === $mdp_confirm && strlen($mdp) >= 6) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            // Met Ã  jour le mot de passe et nettoie le token
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE utilisateur_id = ?");
            $stmt->execute([$hash, $user['utilisateur_id']]);

            $message = "âœ… Mot de passe rÃ©initialisÃ© avec succÃ¨s. Tu peux maintenant te connecter.";
        } else {
            $message = "âš ï¸ Les mots de passe ne correspondent pas ou sont trop courts.";
        }
    }
} else {
    $message = "âŒ Aucun lien valide fourni.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>RÃ©initialiser le mot de passe - BookShare</title>
<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <h2>RÃ©initialiser le mot de passe</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>


    <?php if ($user): ?>
        <form method="post">
            <input type="password" name="mdp" placeholder="Nouveau mot de passe" required>
            <input type="password" name="mdp_confirm" placeholder="Confirmer le mot de passe" required>
            <button type="submit">Mettre Ã  jour</button>
        </form>
    <?php endif; ?>

    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Retour Ã  la connexion</button>
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

