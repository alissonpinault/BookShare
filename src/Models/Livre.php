<?php

declare(strict_types=1);

namespace Bookshare\Models;

use PDO;

class Livre
{
    private PDO $pdo;
    private int $id;
    private array $data = [];

    public function __construct(PDO $pdo, int $id)
    {
        $this->pdo = $pdo;
        $this->id = $id;
        $this->chargerLivre();
    }

    private function chargerLivre(): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM livres WHERE livre_id = ?');
        $stmt->execute([$this->id]);
        $this->data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getId(): ?int
    {
        return $this->data['livre_id'] ?? null;
    }

    public function getTitre(): string
    {
        return $this->data['titre'] ?? '';
    }

    public function getAuteur(): string
    {
        return $this->data['auteur'] ?? '';
    }

    public function getGenre(): string
    {
        return $this->data['genre'] ?? '';
    }

    public function getDescription(): string
    {
        return $this->data['description'] ?? '';
    }

    public function getImageUrl(): string
    {
        return $this->data['image_url'] ?? '';
    }

    public function getDisponibilite(): string
    {
        return $this->data['disponibilite'] ?? 'inconnue';
    }

    private function setDisponibilite(string $etat): void
    {
        $this->data['disponibilite'] = $etat;
        $stmt = $this->pdo->prepare('UPDATE livres SET disponibilite = ? WHERE livre_id = ?');
        $stmt->execute([$etat, $this->id]);
    }

    public function reserver(int $utilisateurId): bool
    {
        if ($this->getDisponibilite() !== 'disponible') {
            return false;
        }

        $reservation = new Reservation($this->pdo);

        if ($reservation->creerReservation($this->getId(), $utilisateurId)) {
            $this->setDisponibilite('indisponible');
            return true;
        }

        return false;
    }

    public function getMoyenneNote(): float
    {
        $stmt = $this->pdo->prepare('SELECT AVG(note) AS moyenne FROM notes WHERE livre_id = ?');
        $stmt->execute([$this->id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($res['moyenne']) ? round((float) $res['moyenne'], 1) : 0.0;
    }

    public function getNombreVotes(): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total_votes FROM notes WHERE livre_id = ?');
        $stmt->execute([$this->id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($res['total_votes'] ?? 0);
    }
}
