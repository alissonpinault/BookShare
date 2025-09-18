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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler'], $_POST['reservation_id'])) {
        $reservationId = (int) $_POST['reservation_id'];
        $annulee = $reservationService->annulerReservation($reservationId, $utilisateur->getId());

        $message = $annulee
            ? 'Réservation annulée avec succès.'
            : "Impossible d'annuler cette réservation.";

        header('Location: reservation.php?message=' . urlencode($message));
        exit;
    }

    $reservationsEnCours = $reservationService->getReservationsEnCours($utilisateur->getId());
    $reservationsArchivees = $reservationService->getReservationsArchivees($utilisateur->getId());
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
<style>
body { font-family:'Roboto', sans-serif; background: linear-gradient(135deg, #a8edea, #fed6e3); margin:0; }
.container { max-width:900px; margin:20px auto; padding:20px; }
h1 { text-align:center; color:#00796b; font-family:'Great Vibes', cursive; font-size:2.5em; margin-bottom:30px; }

nav { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:#00796b; color:white; flex-wrap:wrap; }
nav .logo { display:flex; align-items:center; gap:10px; font-weight:bold; font-size:1.5em; }
nav .logo img { height:40px; }
nav .actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
nav input[type="text"] { padding:6px 10px; border:none; border-radius:4px; width:200px; }
nav button { background:#004d40; border:none; color:white; padding:6px 12px; border-radius:4px; cursor:pointer; transition: all 0.3s; }
nav button:hover { background:#00332c; transform: translateY(-2px); }

.tabs { display:flex; justify-content:center; margin-bottom:20px; }
.tab { padding:10px 20px; border:none; cursor:pointer; background:#eee; margin:0 5px; border-radius:6px; }
.tab.active { background:#00796b; color:white; }
.tab-content { display:none; }
.tab-content.active { display:block; }

.list { list-style:none; padding:0; margin:0; }
.list li { display:flex; align-items:center; justify-content: space-between; padding:10px; border-bottom:1px solid #ddd; gap:15px; }
.list li img { width:60px; height:80px; object-fit:cover; border-radius:4px; }
.reservation-info { flex:1; display:flex; flex-direction:column; }
.reservation-actions { display:flex; align-items:center; gap:10px; }
.reservation-actions button { padding:6px 12px; background:#f57c00; border:none; border-radius:4px; color:white; cursor:pointer; }
.reservation-actions button:hover { background:#c76a05; }

.stars {
    display: flex;
    gap: 4px;
    cursor: pointer;
}
.star {
    font-size: 1.5em;
    color: #ccc; /* vide */
    transition: color 0.2s;
}
.star.hover,
.star.selected {
    color: gold; /* survolée ou validée */
}

</style>
</head>
<body>

<nav>
    <div class="logo">
        <img src="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg" alt="Logo BookShare">
        BookShare
    </div>
    <div class="actions">
        <form method="get" action="index.php" style="margin:0;">
            <input type="text" name="q" placeholder="Rechercher un livre...">
        </form>
        <button onclick="window.location.href='index.php'">Accueil</button>
        <?php if ($utilisateur): ?>
            <?php if ($utilisateur->estAdmin()): ?>
                <button onclick="window.location.href='admin.php'">Panneau Admin</button>
            <?php endif; ?>
            <button onclick="window.location.href='reservation.php'">Mes Reservations</button>
            <button onclick="window.location.href='deconnexion.php'">Deconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)</button>
        <?php else: ?>
            <button onclick="window.location.href='connexion.php'">Connexion</button>
            <button onclick="window.location.href='inscription.php'">Creer un compte</button>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <h1>Mes réservations</h1>

    <?php if (!empty($_GET['message'])): ?>
        <p style="color: green; text-align:center;"><?= htmlspecialchars($_GET['message']) ?></p>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab active" data-target="encours">En cours</button>
        <button class="tab" data-target="archivees">Archivées</button>
    </div>

    <div id="encours" class="tab-content active">
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
                            <span>Reserve le : <?= htmlspecialchars($reservation->getDateReservation() ?? '') ?></span>
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

    <div id="archivees" class="tab-content">
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

                    <!-- Bloc étoiles -->
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

<script>
const tabs = document.querySelectorAll('.tab');
const contents = document.querySelectorAll('.tab-content');

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        contents.forEach(c => c.classList.remove('active'));

        tab.classList.add('active');
        document.getElementById(tab.dataset.target).classList.add('active');
    });
});

// Gestion des étoiles
document.querySelectorAll('.stars').forEach(starsContainer => {
    const stars = starsContainer.querySelectorAll('.star');
    const livreId = starsContainer.dataset.livre;
    const userId = starsContainer.dataset.user;
    let noteExistante = parseInt(starsContainer.dataset.note);

    // Si une note existe déjà → fige l'affichage
    if (noteExistante > 0) {
        stars.forEach(star => {
            if (parseInt(star.dataset.value) <= noteExistante) {
                star.classList.add('selected');
            }
        });
        return; // pas d'interaction possible
    }

    // Sinon interaction active
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

            // Envoi vers PHP
            fetch('note_livre.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ livre_id: livreId, utilisateur_id: userId, note: note })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    // fige l'affichage
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
</body>
</html>
