<?php
require_once 'db.php';

function login($pseudo, $mdp) {
    global $pdo, $mongoDB;
    
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE pseudo = ?");
    $stmt->execute([$pseudo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($mdp, $user['mot_de_passe'])) {
        $_SESSION['utilisateur_id'] = $user['utilisateur_id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'];

        // Log MongoDB
        $mongoDB->logs_connexion->insertOne([
            'utilisateur_id' => $user['utilisateur_id'],
            'pseudo' => $user['pseudo'],
            'date' => new MongoDB\BSON\UTCDateTime(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'resultat' => 'succès'
        ]);

        return true;
    } else {
        // Log échec
        $mongoDB->logs_connexion->insertOne([
            'pseudo' => $pseudo,
            'date' => new MongoDB\BSON\UTCDateTime(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'resultat' => 'échec'
        ]);
        return false;
    }
}

?>
