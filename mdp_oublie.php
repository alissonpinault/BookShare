<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiration = date('d-m-Y H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE utilisateurs SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expiration, $email]);

            $resetLink = "https://ton-app.herokuapp.com/reinitialiser.php?token=" . $token;

            $sujet = "Réinitialisation du mot de passe - BookShare";
            $contenu = "Bonjour {$user['pseudo']},\n\nTu as demandé à réinitialiser ton mot de passe.\nClique sur ce lien pour le réinitialiser :\n$resetLink\n\nCe lien expirera dans 1 heure.";

            // Envoi du mail
            @mail($email, $sujet, $contenu, "From: no-reply@bookshare.com");

            $message = "Si cette adresse est enregistrée, un lien de réinitialisation a été envoyé.";
        } else {
            $message = "Si cette adresse est enregistrée, un lien de réinitialisation a été envoyé.";
        }
    } else {
        $message = "Merci d’entrer ton adresse email.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mot de passe oublié - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="images/logo.jpg">
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <h2>Mot de passe oublié</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

    <form method="post">
        <input type="email" name="email" placeholder="Entrez votre email" required>
        <button type="submit">Envoyer le lien</button>
    </form>
    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Retour</button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const alert = document.getElementById("alert-message");
  if (alert) {
    setTimeout(() => {
      alert.classList.add("hide");
      setTimeout(() => alert.remove(), 800); // le retire après la transition
    }, 5000); // 5 secondes avant disparition
  }
});
</script>
</body>
</html>