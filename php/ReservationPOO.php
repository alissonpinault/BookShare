<?php
class Reservation {
    private PDO $pdo;
    private array $data;

    public function __construct(PDO $pdo, array $data = []) {
        $this->pdo = $pdo;
        $this->data = $data;
    }

    // Getters
    public function getId(): ?int { return $this->data['reservation_id'] ?? null; }
    public function getUtilisateurId(): ?int { return $this->data['utilisateur_id'] ?? null; }
    public function getLivreId(): ?int { return $this->data['livre_id'] ?? null; }
    public function getDateReservation(): ?string { return $this->data['date_reservation'] ?? null; }
    public function getStatut(): ?string { return $this->data['statut'] ?? null; }
    public function getNote(): ?float { return $this->data['note'] ?? null; }

    public function getTitre(): string { return $this->data['titre'] ?? ''; }
    public function getAuteur(): string { return $this->data['auteur'] ?? ''; }
    public function getGenre(): string { return $this->data['genre'] ?? ''; }
    public function getImageUrl(): string { return $this->data['image_url'] ?? ''; }

    // 🔎 Toutes les réservations d’un utilisateur
    public function getReservationsByUtilisateur(int $utilisateurId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ?
            ORDER BY r.date_reservation DESC
        ");
        $stmt->execute([$utilisateurId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn($r) => new Reservation($this->pdo, $r), $rows);
    }

    // Réservations en cours
    public function getReservationsEnCours(int $utilisateurId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? AND r.statut = 'en cours'
            ORDER BY r.date_reservation DESC
        ");
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Réservations terminées
    public function getReservationsTerminees(int $utilisateurId): array {
        $stmt = $this->pdo->prepare("
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? AND r.statut = 'terminer'
            ORDER BY r.date_reservation DESC
        ");
        $stmt->execute([$utilisateurId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Annuler une réservation (passe de "en cours" à "terminer")
    public function annulerReservation(int $reservationId, int $utilisateurId): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ? AND utilisateur_id = ? AND statut = 'en cours'");
        $stmt->execute([$reservationId, $utilisateurId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            // ✅ Mettre à jour le statut plutôt que DELETE
            $update = $this->pdo->prepare("UPDATE reservations SET statut = 'terminer' WHERE reservation_id = ?");
            $update->execute([$reservationId]);

            // ✅ Rendre le livre disponible
            $livreUpdate = $this->pdo->prepare("UPDATE livres SET disponibilite = 'disponible' WHERE livre_id = ?");
            $livreUpdate->execute([$reservation['livre_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>