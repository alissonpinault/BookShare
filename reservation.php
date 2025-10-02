<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/php/LivrePOO.php';
require_once __DIR__ . '/php/UtilisateurPOO.php';
require_once __DIR__ . '/php/ReservationPOO.php';

session_start();

if (empty($_SESSION['utilisateur_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $utilisateur = Utilisateur::getById($pdo, (int) $_SESSION['utilisateur_id']);
    if (!$utilisateur) {
        throw new RuntimeException('Utilisateur introuvable.');
    }

    $reservationService = new Reservation($pdo);

    $pageSize = 5;
    $sanitizePage = static function (?string $value): int {
        $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $page ?: 1;
    };

    $pageEncours = $sanitizePage($_GET['page_encours'] ?? null);
    $pageArchivees = $sanitizePage($_GET['page_archivees'] ?? null);

    // Comptages
    $totalEncours = $reservationService->countReservationsEnCours($utilisateur->getId());
    $totalArchivees = $reservationService->countReservationsArchivees($utilisateur->getId());

    $totalPagesEncours = max(1, (int) ceil($totalEncours / $pageSize));
    $totalPagesArchivees = max(1, (int) ceil($totalArchivees / $pageSize));

    $pageEncours = min($pageEncours, $totalPagesEncours);
    $pageArchivees = min($pageArchivees, $totalPagesArchivees);

    $offsetEncours = ($pageEncours - 1) * $pageSize;
    $offsetArchivees = ($pageArchivees - 1) * $pageSize;

    $reservationsEnCours = $reservationService->getReservationsEnCours(
        $utilisateur->getId(),
        $pageSize,
        $offsetEncours
    );
    $reservationsArchivees = $reservationService->getReservationsArchivees(
        $utilisateur->getId(),
        $pageSize,
        $offsetArchivees
    );

} catch (Throwable $e) {
    echo "<h2 style='color:red; text-align:center;'>Erreur : " . htmlspecialchars($e->getMessage()) . '</h2>';
    error_log('reservation.php ERROR: ' . $e->getMessage());
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes Réservations - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'nav.php'; ?>

<div class="container">
    <h1>Mes réservations</h1>

    <?php if (!empty($_GET['message'])): ?>
        <p style="color: green; text-align:center;"><?= htmlspecialchars($_GET['message']) ?></p>
    <?php endif; ?>

    <!-- Boutons onglets -->
    <div class="tab-buttons">
        <button class="tabBtn active" data-tab="encours">En cours</button>
        <button class="tabBtn" data-tab="archivees">Archivées</button>
    </div>

    <!-- Réservations en cours -->
    <div id="encours" class="tabContent" style="display:block;">
        <?php if (empty($reservationsEnCours)): ?>
            <p>Aucune réservation en cours</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reservationsEnCours as $reservation): ?>
                    <li>
                        <img src="<?= htmlspecialchars($reservation->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Couverture">
                        <div class="reservation-info">
                            <strong><?= htmlspecialchars($reservation->getTitre()) ?></strong>
                            <span>Auteur : <?= htmlspecialchars($reservation->getAuteur()) ?></span>
                            <span>Réservé le : <?= htmlspecialchars($reservation->getDateReservation() ?? '') ?></span>
                        </div>
                        <form method="post" class="reservation-actions">
                            <input type="hidden" name="reservation_id" value="<?= (int) $reservation->getId() ?>">
                            <button type="submit" name="annuler">Annuler</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Réservations archivées -->
    <div id="archivees" class="tabContent">
        <?php if (empty($reservationsArchivees)): ?>
            <p>Aucune réservation archivée.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reservationsArchivees as $reservation): ?>
                    <li>
                        <img src="<?= htmlspecialchars($reservation->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Couverture">
                        <div class="reservation-info">
                            <strong><?= htmlspecialchars($reservation->getTitre()) ?></strong>
                            <span>Auteur : <?= htmlspecialchars($reservation->getAuteur()) ?></span>
                            <span>Réservé le : <?= htmlspecialchars($reservation->getDateReservation() ?? '') ?></span>
                        </div>

                        <!-- Étoiles -->
                        <div class="stars" 
                             data-livre="<?= (int)$reservation->getLivreId() ?>" 
                             data-user="<?= (int)$utilisateur->getId() ?>"
                             data-note="<?= (int)$reservation->getNote() ?>">
                            <?php 
                            $note = (int) $reservation->getNote();
                            for ($i = 1; $i <= 5; $i++): 
                                $class = $i <= $note ? "star selected" : "star";
                            ?>
                                <span class="<?= $class ?>" data-value="<?= $i ?>">★</span>
                            <?php endfor; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<script>
// menu burger
document.addEventListener("DOMContentLoaded", () => {
    const burger  = document.querySelector(".burger");
    const actions = document.querySelector(".site-nav .actions");
    if (!burger || !actions) return;
    actions.classList.remove("open");
    burger.addEventListener("click", () => actions.classList.toggle("open"));
});

// ---- Système onglets (même que admin.php) ----
function openTab(tabName, evt) {
    document.querySelectorAll(".tabContent").forEach(c => c.style.display = "none");
    document.querySelectorAll(".tabBtn").forEach(b => b.classList.remove("active"));

    document.getElementById(tabName).style.display = "block";
    if (evt && evt.currentTarget) {
        evt.currentTarget.classList.add("active");
    }
}

document.addEventListener("DOMContentLoaded", () => {
    const defaultTab = "encours";
    const btn = document.querySelector(`.tabBtn[data-tab="${defaultTab}"]`);
    if (btn) openTab(defaultTab, { currentTarget: btn });

    document.querySelectorAll(".tabBtn").forEach(btn => {
        btn.addEventListener("click", e => openTab(btn.dataset.tab, e));
    });
});

// ---- Gestion étoiles ----
document.querySelectorAll('.stars').forEach(starsContainer => {
    const stars = starsContainer.querySelectorAll('.star');
    const livreId = starsContainer.dataset.livre;
    const userId = starsContainer.dataset.user;
    let noteExistante = parseInt(starsContainer.dataset.note);

    if (noteExistante > 0) {
        stars.forEach(star => {
            if (parseInt(star.dataset.value) <= noteExistante) {
                star.classList.add('selected');
            }
        });
        return;
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach(s => s.classList.toggle('hover', parseInt(s.dataset.value) <= val));
        });

        star.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });

        star.addEventListener('click', () => {
            const note = parseInt(star.dataset.value);
            fetch('note_livre.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ livre_id: livreId, utilisateur_id: userId, note: note })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    stars.forEach(s => {
                        s.classList.remove('hover');
                        s.classList.toggle('selected', parseInt(s.dataset.value) <= note);
                    });
                } else {
                    alert("Erreur : " + data.message);
                }
            })
            .catch(err => console.error(err));
        });
    });
});
</script>

<?php include 'footer.php'; ?>
</body>
</html>