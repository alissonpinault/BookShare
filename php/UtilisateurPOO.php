<?php
class Utilisateur {
    private $id;
    private $pseudo;
    private $email;
    private $role;

    public function __construct($id, $pseudo, $email, $role = 'user') {
        $this->id = $id;
        $this->pseudo = $pseudo;
        $this->email = $email;
        $this->role = $role;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getPseudo() { return $this->pseudo; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }

    // Vérifie si admin
    public function estAdmin() {
        return $this->role === 'admin';
    }

    // Récupérer un utilisateur depuis la base
    public static function getById(PDO $pdo, int $id): ?Utilisateur {
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE utilisateur_id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            return new Utilisateur(
                $data['utilisateur_id'],
                $data['pseudo'],
                $data['email'],
                $data['role']
            );
        }
        return null; // aucun utilisateur trouvé
    }
}
