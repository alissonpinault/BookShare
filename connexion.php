<?php
require_once 'login.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'] ?? '';
    $mdp = $_POST['mdp'] ?? '';

    $user = login($pseudo, $mdp); // maintenant $user contient les infos SQL

    if ($user) {
        if ($user['est_valide'] == 0) {
            $message = "⚠️ Ton compte n'est pas encore activé. Vérifie ton email.";
        } else {
            // Connexion autorisée
            $_SESSION['utilisateur_id'] = $user['utilisateur_id'];
            $_SESSION['pseudo'] = $user['pseudo'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            header("Location: index.php");
            exit;
        }
    } else {
        $message = "❌ Pseudo ou mot de passe incorrect.";
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
<link rel="icon" type="image/jpg" href="images/logo.jpg">
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="images/logo.jpg" alt="Illustration" class="auth-illustration">
    <h2>BookShare</h2>

    <?php if ($message): ?>
        <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="pseudo" placeholder="Pseudo" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>

    <p><a href="mot_de_passe_oublie.php" class="forgot-link">Mot de passe oublié ?</a></p>

    <button class="secondary-btn" onclick="window.location.href='index.php'">Retour à l'accueil</button>
    <button class="secondary-btn" onclick="window.location.href='inscription.php'">Créer un compte</button>
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