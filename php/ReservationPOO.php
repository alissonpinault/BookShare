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

    // === GETTERS === //
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

    // === CRÉER UNE RÉSERVATION === //
    public function creerReservation(int $livreId, int $utilisateurId): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO reservations (livre_id, utilisateur_id, date_reservation, statut)
                VALUES (?, ?, NOW(), 'en_attente')
            ");
            return $stmt->execute([$livreId, $utilisateurId]);
        } catch (Exception $e) {
            error_log('Erreur Reservation::creerReservation → ' . $e->getMessage());
            return false;
        }
    }

    // === ANNULER UNE RÉSERVATION === //
    public function annulerReservation(int $reservationId, int $utilisateurId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT livre_id, statut FROM reservations 
            WHERE reservation_id = ? AND utilisateur_id = ?
        ");
        $stmt->execute([$reservationId, $utilisateurId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reservation || $reservation['statut'] !== 'en_attente') {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            $this->pdo->prepare("
                UPDATE reservations SET statut = 'annulee' WHERE reservation_id = ?
            ")->execute([$reservationId]);

            $this->pdo->prepare("
                UPDATE livres SET disponibilite = 'disponible' WHERE livre_id = ?
            ")->execute([$reservation['livre_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log('Erreur Reservation::annulerReservation → ' . $e->getMessage());
            return false;
        }
    }

    // === RÉSERVATIONS PAR UTILISATEUR === //
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

    // === FILTRES PAR STATUT === //
    public function getReservationsEnAttente(int $utilisateurId, ?int $limit = null, ?int $offset = null): array {
        return $this->getReservationsByStatut($utilisateurId, 'en_attente', $limit, $offset);
    }

    public function getReservationsValidees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array {
        return $this->getReservationsByStatut($utilisateurId, 'validee', $limit, $offset);
    }

    public function getReservationsTerminees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array {
        return $this->getReservationsByStatut($utilisateurId, 'terminee', $limit, $offset);
    }

    // === COMPTEURS === //
    public function countReservationsByStatut(int $utilisateurId, string $statut): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM reservations WHERE utilisateur_id = ? AND statut = ?
        ");
        $stmt->execute([$utilisateurId, $statut]);
        return (int)$stmt->fetchColumn();
    }

    // === UTILITAIRES === //
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
        foreach ($params as $index => $value) {
            $stmt->bindValue($index + 1, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn(array $row) => new self($this->pdo, $row), $rows);
    }

    private function applyLimitOffset(string $sql, array &$params, ?int $limit, ?int $offset): string
    {
        if ($limit !== null) {
            $sql .= ' LIMIT ?';
            $params[] = (int)$limit;
            if ($offset !== null) {
                $sql .= ' OFFSET ?';
                $params[] = (int)$offset;
            }
        } elseif ($offset !== null) {
            $sql .= ' LIMIT 18446744073709551615 OFFSET ?';
            $params[] = (int)$offset;
        }
        return $sql;
    }
}
?>