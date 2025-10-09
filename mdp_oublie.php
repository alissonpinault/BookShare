<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $confirm = $_POST['mdp_confirm'] ?? '';

    if ($mdp !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (strlen($mdp) < 6) {
        $message = "Le mot de passe doit contenir au moins 6 caractères.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE token_reset = ? AND reset_expire > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE utilisateurs SET mot_de_passe=?, token_reset=NULL, reset_expire=NULL WHERE utilisateur_id=?")
                ->execute([$hash, $user['utilisateur_id']]);

            $_SESSION['flash_message'] = "✅ Ton mot de passe a bien été réinitialisé. Tu peux te connecter.";
            header('Location: connexion.php');
            exit;
        } else {
            $message = "❌ Lien invalide ou expiré.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Réinitialisation du mot de passe - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="images/logo.jpg" alt="Logo" class="auth-illustration">
    <h2>Réinitialiser le mot de passe</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($token): ?>
    <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <input type="password" name="mdp" placeholder="Nouveau mot de passe" required>
        <input type="password" name="mdp_confirm" placeholder="Confirmer le mot de passe" required>
        <button type="submit">Mettre à jour</button>
    </form>
    <?php else: ?>
    <p>Lien de réinitialisation invalide.</p>
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
    }, 6000);
  }
});
</script>

</body>
</html>