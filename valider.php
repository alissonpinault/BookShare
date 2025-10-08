<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Rechercher l'utilisateur avec ce token
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE token_validation = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Vérifie si déjà validé
        if ($user['est_valide'] == 1) {
            $message = "✅ Ton compte est déjà activé. Tu peux te connecter.";
        } else {
            // Active le compte
            $stmt = $pdo->prepare("UPDATE utilisateurs SET est_valide = 1, token_validation = NULL WHERE utilisateur_id = ?");
            $stmt->execute([$user['utilisateur_id']]);
            $message = "🎉 Ton compte est maintenant activé ! Tu peux te connecter.";
        }
    } else {
        $message = "❌ Lien de validation invalide ou expiré.";
    }
} else {
    $message = "❌ Aucun lien de validation fourni.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Validation - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="images/logo.jpg">
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="images/logo.jpg" alt="Illustration" class="auth-illustration">
    <h2>Activation du compte</h2>

    <?php if ($message): ?>
        <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Se connecter</button>
    <button class="secondary-btn" onclick="window.location.href='index.php'">Retour à l'accueil</button>
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