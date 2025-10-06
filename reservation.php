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

    // Pagination
    $pageSize = 5;
    $sanitizePage = static function (?string $value): int {
        $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $page ?: 1;
    };

    $pageEnAttente = $sanitizePage($_GET['page_en_attente'] ?? null);
    $pageEnCours = $sanitizePage($_GET['page_en_cours'] ?? null);
    $pageTerminees = $sanitizePage($_GET['page_terminees'] ?? null);

    // Comptages par statut
    $totalEnAttente = $reservationService->countReservationsByStatut($utilisateur->getId(), 'en_attente');
    $totalEnCours = $reservationService->countReservationsByStatut($utilisateur->getId(), 'validee');
    $totalTerminees = $reservationService->countReservationsByStatut($utilisateur->getId(), 'terminee');

    $totalPagesEnAttente = max(1, (int) ceil($totalEnAttente / $pageSize));
    $totalPagesEnCours = max(1, (int) ceil( $totalEnCours / $pageSize));
    $totalPagesTerminees = max(1, (int) ceil($totalTerminees / $pageSize));

    $pageEnAttente = min($pageEnAttente, $totalPagesEnAttente);
    $pageEnCours = min($pageEnCours, $totalPagesEnCours);
    $pageTerminees = min($pageTerminees, $totalPagesTerminees);

    $offsetEnAttente = ($pageEnAttente - 1) * $pageSize;
    $offsetEnCours = ($pageEnCours - 1) * $pageSize;
    $offsetTerminees = ($pageTerminees - 1) * $pageSize;

    // Récupération des réservations selon le statut
    $reservationsEnAttente = $reservationService->getReservationsEnAttente(
        $utilisateur->getId(),
        $pageSize,
        $offsetEnAttente
    );
    $reservationsEnCours = $reservationService->getReservationsValidees(
        $utilisateur->getId(),
        $pageSize,
        $offsetEnCours
    );

    $reservationsTerminees = $reservationService->getReservationsTerminees(
        $utilisateur->getId(),
        $pageSize,
        $offsetTerminees
    );

    // --- Annulation de réservation (POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler'], $_POST['reservation_id'])) {
        $reservationId = (int) $_POST['reservation_id'];
        $annulee = $reservationService->annulerReservation($reservationId, $utilisateur->getId());

        $message = $annulee
            ? 'Réservation annulée avec succès.'
            : "Impossible d'annuler cette réservation (déjà validée ou terminée).";

        header("Location: reservation.php?message=" . urlencode($message));
        exit;
    }

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
<link rel="icon" type="image/jpg" href="images/logo.jpg">
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
        <button class="subTabBtnenattente active" data-tab="enattente">En attente</button>
        <button class="subTabBtnencours" data-tab="encours">En cours</button>
        <button class="subTabBtnarchive" data-tab="archivees">Archivées</button>
    </div>

    <!-- Réservations en attente -->
     <div id="enattente" class="tabContent" style="display:block;">
    <?php if (empty($reservationsEnAttente)): ?>
        <p>Aucune réservation en attente.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($reservationsEnAttente as $reservation): ?>
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
     
    <!-- Emprunt en cours -->
    <div id="encours" class="tabContent">
        <?php if (empty($reservationsEnCours)): ?>
            <p>Aucun emprunt en cours</p>
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
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Réservations terminées -->
    <div id="archivees" class="tabContent">
        <?php if (empty($reservationsTerminees)): ?>
            <p>Aucune réservation archivée.</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reservationsTerminees as $reservation): ?>
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

// ---- Système sous-onglets Réservations ----
document.addEventListener("DOMContentLoaded", () => {
  const subTabButtons = document.querySelectorAll(
    ".subTabBtnenattente, .subTabBtnencours, .subTabBtnarchive"
  );
  const subTabContents = document.querySelectorAll(".tabContent");

  // Masquer tout sauf "En attente"
  subTabContents.forEach((content) => (content.style.display = "none"));
  const defaultTab = document.getElementById("enattente");
  if (defaultTab) defaultTab.style.display = "block";

  // Gestion des clics
  subTabButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      subTabButtons.forEach((b) => b.classList.remove("active"));
      subTabContents.forEach((c) => (c.style.display = "none"));
      btn.classList.add("active");

      const targetId = btn.dataset.tab;
      const target = document.getElementById(targetId);
      if (target) target.style.display = "block";
    });
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