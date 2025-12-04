<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDB = $services['mongoDB'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier MongoDB
if ($mongoDB === null) {
    try {
        $mongoClient = new Client('mongodb://localhost:27017');
        $mongoDB = $mongoClient->selectDatabase('bookshare');
    } catch (\Throwable $e) {
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
        // Vérifie si pseudo ou email déjà  pris
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

            // Enregistre l'inscription dans MongoDB
            if ($mongoDB) {
                $mongoDB->logs_connexion->insertOne([
                    'utilisateur_id' => (int)$user_id,
                    'pseudo' => $pseudo,
                    'date' => new UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'resultat' => 'inscription'
                ]);
            }

            // --- Prépare le mail de validation ---
            $lien = "https://bookshare-655b6c07c913.herokuapp.com/valider.php?token=" . $token;

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = getenv('MAILGUN_SMTP_SERVER') ?: 'smtp.mailgun.org';
                $mail->SMTPAuth   = true;
                $mail->Username   = getenv('MAILGUN_SMTP_LOGIN');
                $mail->Password   = getenv('MAILGUN_SMTP_PASSWORD') ?: getenv('MAILGUN_API_KEY');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = getenv('MAILGUN_SMTP_PORT') ?: 587;

                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';

                $mail->setFrom(getenv('MAILGUN_SMTP_LOGIN'), 'BookShare');
                $mail->addAddress($email, $pseudo);

                // Image intégrée
                $logoPath = __DIR__ . '/assets/images/logo.jpg';
                $imgTag = '';
                if (is_readable($logoPath)) {
                    $mail->addEmbeddedImage($logoPath, 'bookshare_logo', 'logo.jpg');
                    $imgTag = "<img src='cid:bookshare_logo' alt='BookShare' style='width:100px; border-radius:50%; background:white; padding:5px;'>";
                }

                $mail->isHTML(true);
                $mail->Subject = "Validation de ton compte BookShare";
                $mail->Body = "
                    <div style='background:#f6f9fc; padding:30px 0; font-family:Arial, sans-serif;'>
                      <table align='center' cellpadding='0' cellspacing='0' width='100%' style='max-width:600px; background:white; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.1);'>
                        <tr>
                          <td align='center' style='background:#00796b; padding:20px;'>
                            $imgTag
                            <h1 style='color:white; font-family:\"Great Vibes\", cursive; font-size:32px; margin:10px 0 0;'>BookShare</h1>
                          </td>
                        </tr>
                        <tr>
                          <td style='padding:30px; color:#333;'>
                            <h2 style='text-align:center; color:#00796b;'>Bienvenue, $pseudo !</h2>
                            <p style='font-size:16px; line-height:1.6; text-align:center;'>
                              Merci de t'être inscrit sur <strong>BookShare</strong>.<br>
                              Pour activer ton compte, clique sur le bouton ci-dessous :
                            </p>
                            <div style='text-align:center; margin:30px 0;'>
                              <a href='$lien'
                                 style='background:#00796b; color:white; padding:12px 25px; text-decoration:none; border-radius:8px;
                                        font-weight:bold; display:inline-block; font-size:16px;'>
                                 ✅ Activer mon compte
                              </a>
                            </div>
                            <p style='font-size:15px; color:#555; text-align:center;'>
                              Si le bouton ne fonctionne pas, copie-colle ce lien dans ton navigateur :<br>
                              <a href='$lien' style='color:#00796b;'>$lien</a>
                            </p>
                          </td>
                        </tr>
                        <tr>
                          <td align='center' style='background:#f0f0f0; padding:15px; font-size:13px; color:#555;'>
                            À très vite sur <strong>BookShare</strong><br>
                            <span style='font-size:12px; color:#888;'>©" . date('Y') . " BookShare. Tous droits réservés.</span>
                          </td>
                        </tr>
                      </table>
                    </div>
                ";

                $mail->AltBody = "Bonjour $pseudo,\n\nMerci de t'être inscrit sur BookShare !\nClique sur ce lien pour activer ton compte : $lien\n\nÀ très vite sur BookShare !";
                $mail->send();

                $_SESSION['flash_message'] = "✅ Inscription réussie. Vérifie ton email pour activer ton compte avant de te connecter.";
                header('Location: connexion.php');
                exit;
            } catch (MailerException $e) {
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
<link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">

<div class="auth-container">
    <img src="assets/images/logo.jpg" alt="Illustration" class="auth-illustration">
    <h2>Créer un compte</h2>

    <?php if ($message): ?>
    <div id="alert-message" class="auth-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
    <input type="text" name="pseudo" placeholder="Pseudo" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="mdp" placeholder="Mot de passe" required>
    <input type="password" name="mdp_confirm" placeholder="Confirmer mot de passe" required>

    <!-- CONDITIONS GÉNÉRALES -->
   <label style="display: flex; align-items: center; gap: 8px; margin: 10px 0; white-space: nowrap; text-align: left;">
    <input type="checkbox" name="cgu" required>
    <span>
        Je reconnais avoir lu et accepte les 
        <a href="mentions_legales.php" target="_blank"
           style="text-decoration: none; cursor: pointer;"
           onmouseover="this.style.textDecoration='underline'"
           onmouseout="this.style.textDecoration='none'">
           conditions générales
        </a>.
    </span>
</label>

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
