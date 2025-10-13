<?php

declare(strict_types=1);

use MongoDB\BSON\UTCDateTime;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];
$mongoDB = $services['mongoDB'] ?? null;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Adresse e-mail invalide.';
    } else {
        $stmt = $pdo->prepare('SELECT utilisateur_id, pseudo, email FROM utilisateurs WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            try {
                $token = bin2hex(random_bytes(32));
                $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

                $update = $pdo->prepare('UPDATE utilisateurs SET reset_token = ?, reset_expires = ? WHERE utilisateur_id = ?');
                $update->execute([$token, $expires, $user['utilisateur_id']]);

                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
                $resetUrl = sprintf('%s://%s%s/reinitialiser_mdp.php?token=%s', $scheme, $host, $path, urlencode($token));

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = getenv('MAILGUN_SMTP_SERVER') ?: 'smtp.mailgun.org';
                $mail->SMTPAuth   = true;
                $mail->Username   = getenv('MAILGUN_SMTP_LOGIN');
                $mail->Password   = getenv('MAILGUN_SMTP_PASSWORD') ?: getenv('MAILGUN_API_KEY');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int) (getenv('MAILGUN_SMTP_PORT') ?: 587);
                $mail->CharSet    = 'UTF-8';
                $mail->Encoding   = 'base64';

                $mail->setFrom(getenv('MAILGUN_SMTP_LOGIN') ?: 'no-reply@bookshare', 'BookShare');
                $mail->addAddress($user['email'], $user['pseudo'] ?? $user['email']);

                $logoPath = __DIR__ . '/assets/images/logo.jpg';
                $imgTag = '';
                if (is_readable($logoPath)) {
                    $mail->addEmbeddedImage($logoPath, 'bookshare_logo', 'logo.jpg');
                    $imgTag = "<img src='cid:bookshare_logo' alt='BookShare' style='width:100px; border-radius:50%; background:white; padding:5px;'>";
                }

                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de ton mot de passe';
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
                            <h2 style='text-align:center; color:#00796b;'>Réinitialisation du mot de passe</h2>
                            <p style='font-size:16px; line-height:1.6; text-align:center;'>
                              Bonjour <strong>{$user['pseudo']}</strong>, tu viens de demander la réinitialisation de ton mot de passe.<br>
                              Clique sur le bouton ci-dessous pour choisir un nouveau mot de passe. Le lien est valable une heure.
                            </p>
                            <div style='text-align:center; margin:30px 0;'>
                              <a href='$resetUrl'
                                 style='background:#00796b; color:white; padding:12px 25px; text-decoration:none; border-radius:8px;
                                        font-weight:bold; display:inline-block; font-size:16px;'>
                                 Réinitialiser mon mot de passe
                              </a>
                            </div>
                            <p style='font-size:15px; color:#555; text-align:center;'>
                              Si le bouton ne fonctionne pas, copie/colle ce lien dans ton navigateur :<br>
                              <a href='$resetUrl' style='color:#00796b;'>$resetUrl</a>
                            </p>
                          </td>
                        </tr>
                        <tr>
                          <td align='center' style='background:#f0f0f0; padding:15px; font-size:13px; color:#555;'>
                            À très vite sur <strong>BookShare</strong><br>
                            <span style='font-size:12px; color:#888;'>&copy; " . date('Y') . " BookShare. Tous droits réservés.</span>
                          </td>
                        </tr>
                      </table>
                    </div>
                ";

                $mail->AltBody = "Bonjour {$user['pseudo']},\n\nPour réinitialiser ton mot de passe, clique sur ce lien : $resetUrl\n\nCe lien expirera dans une heure.";
                $mail->send();

                if ($mongoDB) {
                    try {
                        $mongoDB->logs_connexion->insertOne([
                            'utilisateur_id' => (int) $user['utilisateur_id'],
                            'action' => 'demande_reset_mdp',
                            'date' => new UTCDateTime(),
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        ]);
                    } catch (Throwable $e) {
                        error_log('Erreur log MongoDB : ' . $e->getMessage());
                    }
                }
                    } catch (MailerException|Throwable $e) {
            error_log('Erreur envoi mail reset : ' . $e->getMessage());
            $message = 'Impossible d’envoyer l’e-mail de réinitialisation pour le moment.';
            if (isset($mail)) {
                $message .= '<br><small>' . htmlspecialchars((string) $mail->ErrorInfo, ENT_QUOTES, 'UTF-8') . '</small>';
            } else {
                $message .= '<br><small>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</small>';
            }
        }

        }

        if ($message === '') {
            $success = true;
            $message = 'Si un compte est associé à cette adresse, un e-mail de réinitialisation vient de partir.';
        }
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
<link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
<link rel="stylesheet" href="assets/css/auth.css">
</head>
<body class="auth-body">
<div class="auth-container">
    <img src="assets/images/logo.jpg" alt="Logo" class="auth-illustration">
    <h2>Mot de passe oublié</h2>

    <?php if ($message): ?>
        <div id="alert-message" class="auth-message <?= $success ? 'success' : '' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (!$success): ?>
        <form method="post">
            <input type="email" name="email" placeholder="Ton adresse e-mail" required>
            <button type="submit">Recevoir un lien</button>
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
    }, 6000);
  }
});
</script>
</body>
</html>
