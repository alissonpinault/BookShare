<?php
require_once 'db.php'; // pour $pdo et $mongoDB
if (session_status() === PHP_SESSION_NONE) session_start();

use MongoDB\Client;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php'; // Charge PHPMailer

// Vérifier MongoDB
if (!isset($mongoDB) || $mongoDB === null) {
    try {
        $mongoClient = new Client("mongodb://localhost:27017");
        $mongoDB = $mongoClient->bookshare;
    } catch (Exception $e) {
        $mongoDB = null;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['mdp'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';

    if ($mdp !== $mdp_confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } elseif (empty($pseudo) || empty($email) || empty($mdp)) {
        $message = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse email invalide.";
    } else {
        // Vérifie si pseudo ou email déjà pris
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $message = "Pseudo ou email déjà utilisé.";
        } else {
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(32));

            // Insère utilisateur non validé
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs (pseudo, email, mot_de_passe, role, est_valide, token_validation)
                VALUES (?, ?, ?, 'utilisateur', 0, ?)
            ");
            $stmt->execute([$pseudo, $email, $hash, $token]);
            $user_id = $pdo->lastInsertId();

            // Enregistre l’inscription dans MongoDB
            if ($mongoDB) {
                $mongoDB->logs_connexion->insertOne([
                    'utilisateur_id' => (int)$user_id,
                    'pseudo' => $pseudo,
                    'date' => new MongoDB\BSON\UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'resultat' => 'inscription'
                ]);
            }

            // Prépare le mail
            $lien = "https://bookshare-655b6c07c913.herokuapp.com/valider.php?token=" . $token;
            $sujet = "Validation de ton compte BookShare";
            $contenu = "Bonjour $pseudo,\n\nMerci de t'être inscrit sur BookShare !\nClique sur ce lien pour activer ton compte :\n$lien\n\nÀ très vite sur BookShare !";

            // --- Envoi via Mailgun (SMTP) ---
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = getenv('MAILGUN_SMTP_SERVER') ?: 'smtp.mailgun.org';
                $mail->SMTPAuth = true;
                $mail->Username = getenv('MAILGUN_SMTP_LOGIN'); // login Mailgun
                $mail->Password = getenv('MAILGUN_API_KEY');   // mot de passe Mailgun
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = getenv('MAILGUN_SMTP_PORT') ?: 587;

                $mail->setFrom('noreply@bookshare.com', 'BookShare');
                $mail->addAddress($email, $pseudo);

                $mail->isHTML(false);
                $mail->Subject = $sujet;
                $mail->Body = $contenu;

                $mail->send();

                // Message flash pour redirection
                $_SESSION['flash_message'] = "✅ Inscription réussie. Vérifie ton email pour activer ton compte avant de te connecter.";
                header('Location: connexion.php');
                exit;
            } catch (Exception $e) {
                error_log("Erreur d'envoi de mail : " . $mail->ErrorInfo);
                $message = "Inscription réussie, mais l'email de validation n'a pas pu être envoyé.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Inscription - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="images/logo.jpg">
<link rel="stylesheet" href="auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="images/logo.jpg" alt="Illustration" class="auth-illustration">
    <h2>Créer un compte</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="pseudo" placeholder="Pseudo" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <input type="password" name="mdp_confirm" placeholder="Confirmer mot de passe" required>
        <button type="submit">S'inscrire</button>
    </form>

    <button class="secondary-btn" onclick="window.location.href='index.php'">Retour à l'accueil</button>
    <button class="secondary-btn" onclick="window.location.href='connexion.php'">Déjà un compte ? Connexion</button>
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