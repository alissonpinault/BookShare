<?php

declare(strict_types=1);

namespace Bookshare\Models;

use PDO;

class Utilisateur
{
    private int $id;
    private string $pseudo;
    private string $email;
    private string $role;

    public function __construct(int $id, string $pseudo, string $email, string $role = 'utilisateur')
    {
        $this->id = $id;
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->role = $role;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function estAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public static function getById(PDO $pdo, int $id): ?self
    {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE utilisateur_id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $role = isset($data['role']) && $data['role'] !== '' ? $data['role'] : 'utilisateur';

        return new self(
            (int) $data['utilisateur_id'],
            $data['pseudo'] ?? '',
            $data['email'] ?? '',
            $role
        );
    }
}
