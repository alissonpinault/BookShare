<?php

declare(strict_types=1);

namespace Bookshare\Services\Notes;

use PDO;

final class NoteService
{
    /** Stores or updates a user's rating for a book. */
    public static function save(PDO $pdo, int $livreId, int $utilisateurId, int $note): bool
    {
        $stmt = $pdo->prepare(
            'INSERT INTO notes (livre_id, utilisateur_id, note)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE note = VALUES(note)'
        );

        return $stmt->execute([$livreId, $utilisateurId, $note]);
    }
}
