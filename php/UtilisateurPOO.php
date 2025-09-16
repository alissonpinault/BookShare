<?php
class Utilisateur {
    private int $id;
    private string $pseudo;
    private string $email;
    private string $role;

    public function __construct(int $id, string $pseudo, string $email, string $role = 'utilisateur') {
        $this->id = $id;
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->role = $role;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getPseudo(): string { return $this->pseudo; }
    public function getEmail(): string { return $this->email; }
    public function getRole(): string { return $this->role; }

    // Vérifie si admin
    public function estAdmin(): bool {
        return $this->role === 'admin';
    }

    // Récupérer un utilisateur depuis la base
    public static function getById(PDO $pdo, int $id): ?Utilisateur {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE utilisateur_id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return new Utilisateur(
                (int)$data['utilisateur_id'],
                $data['pseudo'],
                $data['email'],
                $data['role'] // ⚡ correspond à ENUM('utilisateur','admin')
            );
        }
        return null;
    }
}
