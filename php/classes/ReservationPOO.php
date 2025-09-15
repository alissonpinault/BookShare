<?php
class Reservation {
    private PDO $pdo;
    private array $data;

    public function __construct(PDO $pdo, array $data = []) {
        $this->pdo = $pdo;
        $this->data = $data;
    }

    // Getters (accèdent au tableau $data)
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

    // 🔎 Récupérer toutes les réservations (en cours + archivées)
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

    // Récupérer uniquement les réservations en cours
public function getReservationsEnCours($utilisateurId) {
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

// Récupérer uniquement les réservations archivées
public function getReservationsArchivees($utilisateurId) {
    $stmt = $this->pdo->prepare("
        SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
        FROM reservations r
        JOIN livres l ON r.livre_id = l.livre_id
        LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
        WHERE r.utilisateur_id = ? AND r.statut = 'archive'
        ORDER BY r.date_reservation DESC
    ");
    $stmt->execute([$utilisateurId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

    // Annuler une réservation en cours
    public function annulerReservation(int $reservationId, int $utilisateurId): bool {
        // Vérifier que la réservation appartient à l'utilisateur et est en cours
        $stmt = $this->pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ? AND utilisateur_id = ? AND statut = 'en cours'");
        $stmt->execute([$reservationId, $utilisateurId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            return false; // Réservation non trouvée ou pas en cours
        }

        try {
            $this->pdo->beginTransaction();

            // Supprimer la réservation
            $delete = $this->pdo->prepare("DELETE FROM reservations WHERE reservation_id = ?");
            $delete->execute([$reservationId]);

            // Mettre à jour la disponibilité du livre
            $update = $this->pdo->prepare("UPDATE livres SET disponibilite = 'disponible' WHERE livre_id = ?");
            $update->execute([$reservation['livre_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
?>
