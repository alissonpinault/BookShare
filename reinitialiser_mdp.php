<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$user = null;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Vérifie que le token existe et n’est pas expiré
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = "❌ Lien invalide ou expiré.";
    }

    // Si formulaire soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
        $mdp = $_POST['mdp'] ?? '';
        $mdp_confirm = $_POST['mdp_confirm'] ?? '';

        if ($mdp === $mdp_confirm && strlen($mdp) >= 6) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);

            // Met à jour le mot de passe et nettoie le token
            $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE utilisateur_id = ?");
            $stmt->execute([$hash, $user['utilisateur_id']]);

            $message = "✅ Mot de passe réinitialisé avec succès. Tu peux maintenant te connecter.";
        } else {
            $message = "⚠️ Les mots de passe ne correspondent pas ou sont trop courts.";
        }
    }
} else {
    $message = "❌ Aucun lien valide fourni.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réinitialiser le mot de passe - BookShare</title>
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <h2>Réinitialiser le mot de passe</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>


    <?php if ($user): ?>
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