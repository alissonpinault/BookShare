<?php
class Reservation
{
    private PDO $pdo;
    private array $data;

    public function __construct(PDO $pdo, array $data = [])
    {
        $this->pdo = $pdo;
        $this->data = $data;
    }

    // Basic getters for reservation fields
    public function getId(): ?int { return isset($this->data['reservation_id']) ? (int) $this->data['reservation_id'] : null; }
    public function getUtilisateurId(): ?int { return isset($this->data['utilisateur_id']) ? (int) $this->data['utilisateur_id'] : null; }
    public function getLivreId(): ?int { return isset($this->data['livre_id']) ? (int) $this->data['livre_id'] : null; }
    public function getDateReservation(): ?string { return $this->data['date_reservation'] ?? null; }
    public function getStatut(): ?string { return $this->data['statut'] ?? null; }
    public function getNote(): ?float { return isset($this->data['note']) ? (float) $this->data['note'] : null; }
    public function getTitre(): string { return $this->data['titre'] ?? ''; }
    public function getAuteur(): string { return $this->data['auteur'] ?? ''; }
    public function getGenre(): string { return $this->data['genre'] ?? ''; }
    public function getImageUrl(): string { return $this->data['image_url'] ?? ''; }

    /**
     * Retourne toutes les reservations pour un utilisateur donne.
     */
    public function getReservationsByUtilisateur(int $utilisateurId): array
    {
        $sql = <<<'SQL'
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            INNER JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ?
            ORDER BY r.date_reservation DESC
        SQL;

        return $this->fetchReservations($sql, [$utilisateurId]);
    }

    public function getReservationsEnCours(int $utilisateurId): array
    {
        $sql = <<<'SQL'
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            INNER JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? AND r.statut = 'en cours'
            ORDER BY r.date_reservation DESC
        SQL;

        return $this->fetchReservations($sql, [$utilisateurId]);
    }

    public function getReservationsTerminees(int $utilisateurId): array
    {
        $sql = <<<'SQL'
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            INNER JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? AND r.statut = 'terminer'
            ORDER BY r.date_reservation DESC
        SQL;

        return $this->fetchReservations($sql, [$utilisateurId]);
    }

    public function getReservationsArchivees(int $utilisateurId): array
    {
        return $this->getReservationsTerminees($utilisateurId);
    }

    /**
     * Annule une reservation et remet le livre a disposition.
     */
    public function annulerReservation(int $reservationId, int $utilisateurId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT livre_id FROM reservations WHERE reservation_id = ? AND utilisateur_id = ? AND statut = 'en cours'"
        );
        $stmt->execute([$reservationId, $utilisateurId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation) {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $updateReservation = $this->pdo->prepare(
                "UPDATE reservations SET statut = 'terminer' WHERE reservation_id = ?"
            );
            $updateReservation->execute([$reservationId]);

            $updateBook = $this->pdo->prepare(
                "UPDATE livres SET disponibilite = 'disponible' WHERE livre_id = ?"
            );
            $updateBook->execute([$reservation['livre_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    private function fetchReservations(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row) => new self($this->pdo, $row), $rows);
    }
}
?>
