<?php
require_once 'login.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'] ?? '';
    $mdp = $_POST['mdp'] ?? '';

    if (login($pseudo, $mdp)) {
        header('Location: index.php');
        exit;
    } else {
        $message = "Pseudo ou mot de passe incorrect";
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
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<style>
    body {
        margin: 0;
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(135deg, #a8edea, #fed6e3);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }

    .container {
        background: rgba(255,255,255,0.95);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        width: 380px;
        text-align: center;
        animation: fadeIn 0.8s ease;
        position: relative;
    }

    h2 {
        font-family: 'Great Vibes', cursive;
        font-size: 2.5em;
        color: #00796b;
        margin-bottom: 30px;
    }

    input {
        width: 100%;
        padding: 12px;
        margin-bottom: 20px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 16px;
        transition: all 0.3s;
    }

    input:focus {
        border-color: #00796b;
        box-shadow: 0 0 5px rgba(0,121,107,0.5);
        outline: none;
    }

    button {
        width: 100%;
        padding: 12px;
        background: #00796b;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 10px;
    }

    button:hover {
        background: #004d40;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .secondary-btn {
        background: #ccc;
        color: #333;
    }
    .secondary-btn:hover {
        background: #bbb;
    }

    .error {
        color: #d32f2f;
        margin-bottom: 15px;
        font-weight: bold;
        font-size: 14px;
        animation: shake 0.4s;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        20%, 60% { transform: translateX(-5px); }
        40%, 80% { transform: translateX(5px); }
    }

    /* Illustration */
    .illustration {
        position: absolute;
        top: -50px;
        right: -50px;
        width: 100px;
        opacity: 0.2;
    }

    @media (max-width: 500px) {
        .container {
            width: 90%;
            padding: 30px;
        }
        .illustration {
            display: none;
        }
    }
</style>
</head>
<body>

<div class="container">
    <img src="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg" alt="Livres" class="illustration">
    <h2>BookShare</h2>

    <?php if ($message): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <input type="text" name="pseudo" placeholder="Pseudo" required>
        <input type="password" name="mdp" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>

    <button class="secondary-btn" onclick="window.location.href='index.php'">Retour à l'accueil</button>
    <button class="secondary-btn" onclick="window.location.href='inscription.php'">Créer un compte</button>
</div>

</body>
</html>
