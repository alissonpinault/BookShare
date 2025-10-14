<?php

declare(strict_types=1);

namespace Bookshare\Services\Auth;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Database as MongoDatabase;
use PDO;
use Throwable;

final class LoginService
{
    /**
     * Authenticate a user based on pseudo/password pair.
     *
     * @return array<string, mixed>|null
     */
    public static function authenticate(PDO $pdo, ?MongoDatabase $mongoDB, string $pseudo, string $mdp): ?array
    {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE pseudo = ?');
        $stmt->execute([$pseudo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $authenticated = $user && password_verify($mdp, $user['mot_de_passe'] ?? '');

        if ($mongoDB) {
            try {
                $mongoDB->logs_connexion->insertOne([
                    'utilisateur_id' => $user['utilisateur_id'] ?? null,
                    'pseudo' => $pseudo,
                    'date' => new UTCDateTime(),
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                    'resultat' => $authenticated ? 'succes' : 'echec',
                ]);
            } catch (Throwable $e) {
                error_log('Mongo log error: ' . $e->getMessage());
            }
        }

        return $authenticated ? $user : null;
    }
}
