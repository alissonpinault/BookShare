<?php
require_once 'db.php';

function login(string $pseudo, string $mdp): bool
{
    global $pdo, $mongoDB;

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE pseudo = ?');
    $stmt->execute([$pseudo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $authenticated = $user && password_verify($mdp, $user['mot_de_passe'] ?? '');

    if ($authenticated) {
        $_SESSION['utilisateur_id'] = $user['utilisateur_id'];
        $_SESSION['pseudo'] = $user['pseudo'];
        $_SESSION['role'] = $user['role'] ?? 'utilisateur';
        $_SESSION['email'] = $user['email'] ?? '';
    }

    if ($mongoDB) {
        try {
            $mongoDB->logs_connexion->insertOne([
                'utilisateur_id' => $user['utilisateur_id'] ?? null,
                'pseudo' => $pseudo,
                'date' => new MongoDB\BSON\UTCDateTime(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'resultat' => $authenticated ? 'succes' : 'echec',
            ]);
        } catch (Throwable $e) {
            error_log('Mongo log error: ' . $e->getMessage());
        }
    }

    return $authenticated;
}
?>
