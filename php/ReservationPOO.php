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

    public function getReservationsEnCours(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getReservationsByStatut($utilisateurId, 'en cours', $limit, $offset);
    }

    public function getReservationsTerminees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getReservationsByStatut($utilisateurId, 'terminer', $limit, $offset);
    }

    public function getReservationsArchivees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getReservationsTerminees($utilisateurId, $limit, $offset);
    }

    public function countReservationsEnCours(int $utilisateurId): int
    {
        return $this->countReservationsByStatut($utilisateurId, 'en cours');
    }

    public function countReservationsArchivees(int $utilisateurId): int
    {
        return $this->countReservationsByStatut($utilisateurId, 'terminer');
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

    private function getReservationsByStatut(int $utilisateurId, string $statut, ?int $limit, ?int $offset): array
    {
        $sql = <<<'SQL'
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            INNER JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? AND r.statut = ?
            ORDER BY r.date_reservation DESC
        SQL;

        $params = [$utilisateurId, $statut];
        $sql = $this->applyLimitOffset($sql, $params, $limit, $offset);

        return $this->fetchReservations($sql, $params);
    }

    private function fetchReservations(string $sql, array $params): array
    {
        $stmt = $this->pdo->prepare($sql);

        foreach (array_values($params) as $index => $value) {
            $position = $index + 1;
            $type = PDO::PARAM_STR;

            if (is_int($value)) {
                $type = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            } elseif ($value === null) {
                $type = PDO::PARAM_NULL;
            }

            $stmt->bindValue($position, $value, $type);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row) => new self($this->pdo, $row), $rows);
    }

    private function applyLimitOffset(string $sql, array &$params, ?int $limit, ?int $offset): string
    {
        if ($limit !== null) {
            $sql .= ' LIMIT ?';
            $params[] = (int) $limit;

            if ($offset !== null) {
                $sql .= ' OFFSET ?';
                $params[] = (int) $offset;
            }
        } elseif ($offset !== null) {
            $sql .= ' LIMIT 18446744073709551615 OFFSET ?';
            $params[] = (int) $offset;
        }

        return $sql;
    }

    private function countReservationsByStatut(int $utilisateurId, string $statut): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM reservations WHERE utilisateur_id = ? AND statut = ?'
        );
        $stmt->execute([$utilisateurId, $statut]);

        return (int) $stmt->fetchColumn();
    }
}
?>
