<?php
require_once 'db.php'; // pour $pdo et $mongoDB
if (session_status() === PHP_SESSION_NONE) session_start();

// Vérifier et initialiser la connexion MongoDB si nécessaire
if (!isset($mongoDB) || $mongoDB === null) {
    try {
        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
        $mongoDB = $mongoClient->bookshare; // Remplacez 'bookshare' par le nom de votre base MongoDB
    } catch (Exception $e) {
        // Gérer l'erreur de connexion MongoDB
        $mongoDB = null;
    }
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = $_POST['pseudo'] ?? '';
    $email = $_POST['email'] ?? '';
    $mdp = $_POST['mdp'] ?? '';
    $mdp_confirm = $_POST['mdp_confirm'] ?? '';

    if ($mdp !== $mdp_confirm) {
        $message = "Les mots de passe ne correspondent pas";
    } elseif (empty($pseudo) || empty($email) || empty($mdp)) {
        $message = "Tous les champs sont obligatoires";
    } else {
        // Vérifie si pseudo ou email existe déjà
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ? OR email = ?");
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()) {
            $message = "Pseudo ou email déjà utilisé";
        } else {
            // Insertion utilisateur
            $hash = password_hash($mdp, PASSWORD_DEFAULT);
           $pdo->prepare("INSERT INTO utilisateurs (pseudo, email, mot_de_passe, role) VALUES (?, ?, ?, 'utilisateur')")
    ->execute([$pseudo, $email, $hash]);


            // Récupère l'id du nouvel utilisateur
            $user_id = $pdo->lastInsertId();

            // Log MongoDB
            $mongoDB->logs_connexion->insertOne([
                'utilisateur_id' => (int)$user_id,
                'pseudo' => $pseudo,
                'date' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'resultat' => 'inscription'
            ]);

            // Crée session et redirige vers index
            $_SESSION['utilisateur_id'] = $user_id;
            $_SESSION['pseudo'] = $pseudo;
            header('Location: index.php');
            exit;
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
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<link rel="stylesheet" href="style.css">

</head>
<body>

<div class="container">
    <img src="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg" alt="Logo BookShare" class="logo">
    <h2>Créer un compte</h2>

    <?php if ($message): ?>
        <div class="error"><?= htmlspecialchars($message) ?></div>
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

</body>
</html>
