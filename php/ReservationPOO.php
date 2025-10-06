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
    public function getId(): ?int { return isset($this->data['reservation_id']) ? (int)$this->data['reservation_id'] : null; }
    public function getUtilisateurId(): ?int { return isset($this->data['utilisateur_id']) ? (int)$this->data['utilisateur_id'] : null; }
    public function getLivreId(): ?int { return isset($this->data['livre_id']) ? (int)$this->data['livre_id'] : null; }
    public function getDateReservation(): ?string { return $this->data['date_reservation'] ?? null; }
    public function getStatut(): ?string { return $this->data['statut'] ?? null; }
    public function getNote(): ?float { return isset($this->data['note']) ? (float)$this->data['note'] : null; }
    public function getTitre(): string { return $this->data['titre'] ?? ''; }
    public function getAuteur(): string { return $this->data['auteur'] ?? ''; }
    public function getGenre(): string { return $this->data['genre'] ?? ''; }
    public function getImageUrl(): string { return $this->data['image_url'] ?? ''; }

    // === CRÉER UNE RÉSERVATION === //
    public function creerReservation(int $livreId, int $utilisateurId): bool
    {
        try {
            $this->pdo->beginTransaction();

            // 1️⃣ Créer la réservation en attente
            $stmt = $this->pdo->prepare("
                INSERT INTO reservations (livre_id, utilisateur_id, date_reservation, statut)
                VALUES (?, ?, NOW(), 'en_attente')
            ");
            $stmt->execute([$livreId, $utilisateurId]);

            // 2️⃣ Rendre le livre indisponible
            $updateLivre = $this->pdo->prepare("
                UPDATE livres SET statut = 'indisponible' WHERE livre_id = ?
            ");
            $updateLivre->execute([$livreId]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
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

        // Autoriser l'annulation uniquement si la réservation est encore en attente
        if (!$reservation || $reservation['statut'] !== 'en_attente') {
            return false;
        }

        try {
            $this->pdo->beginTransaction();

            // Marquer la réservation comme annulée
            $updateReservation = $this->pdo->prepare("
                UPDATE reservations SET statut = 'annulee' WHERE reservation_id = ?
            ");
            $updateReservation->execute([$reservationId]);

            // Remettre le livre disponible
            $updateLivre = $this->pdo->prepare("
                UPDATE livres SET statut = 'disponible' WHERE livre_id = ?
            ");
            $updateLivre->execute([$reservation['livre_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
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
    public function getReservationsEnAttente(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getReservationsByStatut($utilisateurId, 'en_attente', $limit, $offset);
    }

    public function getReservationsValidees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        return $this->getReservationsByStatut($utilisateurId, 'validee', $limit, $offset);
    }

    // 🟢 IMPORTANT : seules les "terminées" apparaissent dans l’onglet Archivées
    public function getReservationsTerminees(int $utilisateurId, ?int $limit = null, ?int $offset = null): array
    {
        $sql = <<<'SQL'
            SELECT r.*, l.titre, l.auteur, l.genre, l.image_url, n.note
            FROM reservations r
            INNER JOIN livres l ON r.livre_id = l.livre_id
            LEFT JOIN notes n ON r.livre_id = n.livre_id AND r.utilisateur_id = n.utilisateur_id
            WHERE r.utilisateur_id = ? 
            AND r.statut = 'terminee'
            ORDER BY r.date_reservation DESC
        SQL;

        $params = [$utilisateurId];
        $sql = $this->applyLimitOffset($sql, $params, $limit, $offset);

        return $this->fetchReservations($sql, $params);
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

        foreach (array_values($params) as $index => $value) {
            $position = $index + 1;
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
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