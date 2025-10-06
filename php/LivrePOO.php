<?php
class Livre {
    private PDO $pdo;
    private int $id;
    private array $data = [];

    public function __construct(PDO $pdo, int $id) {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->chargerLivre();
    }

    private function chargerLivre(): void {
        $stmt = $this->pdo->prepare("SELECT * FROM livres WHERE livre_id = ?");
        $stmt->execute([$this->id]);
        $this->data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getId(): ?int {
        return $this->data['livre_id'] ?? null;
    }

    public function getTitre(): string {
        return $this->data['titre'] ?? '';
    }

    public function getAuteur(): string {
        return $this->data['auteur'] ?? '';
    }

    public function getGenre(): string {
        return $this->data['genre'] ?? '';
    }

    public function getDescription(): string {
        return $this->data['description'] ?? '';
    }

    public function getImageUrl(): string {
        return $this->data['image_url'] ?? '';
    }

    public function getDisponibilite(): string {
        return $this->data['disponibilite'] ?? 'inconnu';
    }

    /* Réserver un livre */
    public function reserver(int $utilisateur_id): bool {
        if ($this->getDisponibilite() !== 'disponible') {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            // Marquer le livre indisponible
            $update = $this->pdo->prepare("UPDATE livres SET disponibilite = 'indisponible' WHERE livre_id = ?");
            $update->execute([$this->id]);

            // Ajouter la réservation avec statut explicite = 'en cours'
            $insert = $this->pdo->prepare("
                INSERT INTO reservations (utilisateur_id, livre_id, date_reservation, statut) 
                VALUES (?, ?, NOW(), 'en cours')
            ");
            $insert->execute([$utilisateur_id, $this->id]);

            $this->pdo->commit();

            $this->data['disponibilite'] = 'indisponible';
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getMoyenneNote(): float {
        $stmt = $this->pdo->prepare("SELECT AVG(note) AS moyenne FROM notes WHERE livre_id = ?");
        $stmt->execute([$this->id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($res['moyenne']) ? round((float)$res['moyenne'], 1) : 0.0;
    }

    public function getNombreVotes(): int {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total_votes FROM notes WHERE livre_id = ?");
        $stmt->execute([$this->id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($res['total_votes'] ?? 0);
    }
}
