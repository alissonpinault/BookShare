<?php
require_once 'db.php';
require_once 'classes/LivrePOO.php';
require_once 'classes/UtilisateurPOO.php';
require_once 'classes/ReservationPOO.php';

session_start();

// Sécurité : utilisateur connecté
if (empty($_SESSION['utilisateur_id'])) {
    header('Location: index.php');
    exit;
}

// Récupération de l'utilisateur
$utilisateur = Utilisateur::getById($pdo, (int)$_SESSION['utilisateur_id']);

// Instanciation de la classe Reservation
$reservationObj = new Reservation($pdo);

// Récupération des réservations par statut
$reservationsEnCours = $reservationObj->getReservationsEnCours($utilisateur->getId());
$reservationsArchivees = $reservationObj->getReservationsArchivees($utilisateur->getId());

// Annulation d'une réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler'], $_POST['reservation_id'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET statut = 'archive' WHERE reservation_id = ? AND utilisateur_id = ?");
    $stmt->execute([(int)$_POST['reservation_id'], $utilisateur->getId()]);
    header("Location: reservation.php?message=Réservation annulée avec succès");
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
.list li { display:flex; align-items:center; justify-content: space-between; padding:10px; border-bottom:1px solid #ddd; }
.list li > a.reservation-link { display:flex; align-items:center; text-decoration:none; color:inherit; flex:1; }
.list img { width:50px; height:70px; object-fit:cover; border-radius:4px; margin-right:15px; }
.card-content { display:flex; flex-direction:column; }
.list h3 { margin:0; font-size:1.1em; color:#00796b; }
.list p { margin:2px 0; font-size:0.9em; color:#444; }
.date { font-size:0.8em; color:#666; }
.empty { text-align:center; padding:20px; font-style:italic; color:#666; }

.note-container { display:flex; align-items:center; gap:5px; }
.stars { display:flex; gap:2px; }
.star { font-size:1.5em; color:#ccc; cursor:pointer; transition: color 0.2s; }
.star.hover, .star.selected { color: gold; }
.list li { display:flex; align-items:center; justify-content: space-between; padding:10px; border-bottom:1px solid #ddd; }
.list li > a.reservation-link { display:flex; align-items:center; text-decoration:none; color:inherit; flex:1; }
.list li img { width:50px; height:70px; object-fit:cover; border-radius:4px; margin-right:15px; }
.card-content { display:flex; flex-direction:column; }
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
            <?php if($utilisateur->getRole() === 'admin'): ?>
                <button onclick="window.location.href='admin.php'">Panneau Admin</button>
            <?php endif; ?>
            <button onclick="window.location.href='reservation.php'">Mes Réservations</button>
            <button onclick="window.location.href='deconnexion.php'">Déconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)</button>
        <?php else: ?>
            <button onclick="window.location.href='connexion.php'">Connexion</button>
            <button onclick="window.location.href='inscription.php'">Créer un compte</button>
        <?php endif; ?>
    </div>
</nav>

</style>
</head>
<body>
    <h1>Mes réservations</h1>

    <?php if (isset($message)): ?>
        <p style="color: green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <!-- Onglets -->
    <div class="tabs">
        <div class="tab active" data-target="encours">En cours</div>
        <div class="tab" data-target="archivees">Archivées</div>
    </div>

    <!-- Réservations en cours -->
    <div id="encours" class="tab-content active">
        <?php if (empty($reservationsEnCours)): ?>
            <p>Aucune réservation en cours.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($reservationsEnCours as $res): ?>
                    <li style="margin-bottom: 15px;">
                        <img src="<?= htmlspecialchars($res['image_url']) ?>" alt="Couverture" width="60" style="vertical-align: middle; margin-right: 10px;">
                        <strong><?= htmlspecialchars($res['titre']) ?></strong>
                        de <?= htmlspecialchars($res['auteur']) ?><br>
                        Réservé le : <?= htmlspecialchars($res['date_reservation']) ?>

                        <form method="post" style="display:inline;">
                            <input type="hidden" name="reservation_id" value="<?= (int) $res['reservation_id'] ?>">
                            <button type="submit" name="annuler">Annuler</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Réservations archivées -->
    <div id="archivees" class="tab-content">
        <?php if (empty($reservationsArchivees)): ?>
            <p>Aucune réservation archivée.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($reservationsArchivees as $res): ?>
                    <li style="margin-bottom: 15px;">
                        <img src="<?= htmlspecialchars($res['image_url']) ?>" alt="Couverture" width="60" style="vertical-align: middle; margin-right: 10px;">
                        <strong><?= htmlspecialchars($res['titre']) ?></strong>
                        de <?= htmlspecialchars($res['auteur']) ?><br>
                        Réservé le : <?= htmlspecialchars($res['date_reservation']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <script>
        // Gestion des onglets
        const tabs = document.querySelectorAll(".tab");
        const contents = document.querySelectorAll(".tab-content");

        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                tabs.forEach(t => t.classList.remove("active"));
                contents.forEach(c => c.classList.remove("active"));

                tab.classList.add("active");
                document.getElementById(tab.dataset.target).classList.add("active");
            });
        });
    </script>
</body>
</html>
