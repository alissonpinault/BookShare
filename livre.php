<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/php/LivrePOO.php';
require_once __DIR__ . '/php/UtilisateurPOO.php';
require_once __DIR__ . '/php/ReservationPOO.php';

session_start();

$utilisateurId = $_SESSION['utilisateur_id'] ?? null;
$utilisateur = null;

if ($utilisateurId) {
    $utilisateur = Utilisateur::getById($pdo, (int) $utilisateurId);
}

$livreId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($livreId <= 0) {
    header('Location: index.php');
    exit;
}

$livre = new Livre($pdo, $livreId);
if (!$livre->getId()) {
    echo 'Livre introuvable.';
    exit;
}

$moyenne = $livre->getMoyenneNote();
$totalVotes = $livre->getNombreVotes();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    if (!$utilisateurId) {
        $message = "<p style='color:red; text-align:center;'>Veuillez vous connecter pour reserver.</p>";
    } elseif ($livre->reserver((int) $utilisateurId)) {
        header('Location: livre.php?id=' . $livreId);
        exit;
    } else {
        $message = "<p style='color:red; text-align:center;'>Ce livre est deja reserve.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($livre->getTitre()) ?> - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<style>
body { font-family:'Roboto', sans-serif; background: linear-gradient(135deg, #a8edea, #fed6e3); margin:0; }
nav { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:#00796b; color:white; flex-wrap:wrap; }
nav .logo { display:flex; align-items:center; gap:10px; font-weight:bold; font-size:1.5em; }
nav .logo img { height:40px; }
nav .actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
nav input[type="text"] { padding:6px 10px; border:none; border-radius:4px; width:200px; }
nav button { background:#004d40; border:none; color:white; padding:6px 12px; border-radius:4px; cursor:pointer; transition: all 0.3s; }
nav button:hover { background:#00332c; transform: translateY(-2px); }
.container { max-width:700px; margin:20px auto; background:white; border-radius:10px; padding:20px; box-shadow:0 4px 8px rgba(0,0,0,0.2); text-align:center; }
h1 { color:#00796b; font-family:'Great Vibes', cursive; font-size:2.5em; margin-bottom:20px; }
img { width:200px; height:auto; border-radius:8px; display:block; margin:0 auto 20px; }
button { padding:10px 15px; background:#00796b; color:white; border:none; border-radius:5px; cursor:pointer; }
button:disabled { background:#ccc; cursor:default; }
.moyenne-notes { margin-top:10px; font-size:1.2em; color:#444; display:flex; align-items:center; gap:8px; justify-content:center; }
.moyenne-notes .star { font-size:1.5em; color:#ccc; }
.moyenne-notes .star.filled { color: gold; }
<<<<<<< ours
<<<<<<< ours
.mobile-bottom-nav { display:none; }
.mobile-bottom-nav__link { color:#004d40; text-decoration:none; font-size:0.75rem; font-weight:600; display:flex; flex-direction:column; align-items:center; gap:6px; }
.mobile-bottom-nav__icon { width:54px; height:54px; border-radius:50%; background:linear-gradient(135deg,#00796b,#00acc1); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; box-shadow:0 8px 16px rgba(0,0,0,0.15); transition:transform 0.2s ease, box-shadow 0.2s ease; }
.mobile-bottom-nav__link:focus-visible .mobile-bottom-nav__icon,
.mobile-bottom-nav__link:hover .mobile-bottom-nav__icon,
.mobile-bottom-nav__link.is-active .mobile-bottom-nav__icon { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.2); }
.mobile-bottom-nav__text { text-shadow:0 1px 2px rgba(255,255,255,0.6); }
@media (max-width:600px) {
    body { padding-bottom:88px; }
    nav { flex-direction:column; gap:10px; }
    nav input[type="text"] { width:100%; }
    .container { margin:10px auto 100px; width:92%; }
    .mobile-bottom-nav { position:fixed; bottom:0; left:0; right:0; display:flex; justify-content:space-around; padding:12px; background:rgba(255,255,255,0.8); backdrop-filter: blur(6px); border-top:1px solid rgba(0,0,0,0.1); z-index:1000; }
=======
@media (max-width:600px) {
    nav { flex-wrap:nowrap; gap:12px; }
    nav .logo { font-size:1.3em; }
    .mobile-menu-toggle { display:inline-flex; align-items:center; justify-content:center; }
    nav .actions { display:none; }
>>>>>>> theirs
}
=======
>>>>>>> theirs
</style>
</head>
<body>

<<<<<<< ours
<<<<<<< ours
=======
>>>>>>> theirs
<nav>
    <div class="logo">
        <img src="images/logo.jpg" alt="Logo BookShare">
        BookShare
    </div>
    <div class="actions">
<<<<<<< ours
        <form method="get" action="index.php" id="main-search-form" style="margin:0;">
            <input type="text" id="main-search-input" name="q" placeholder="Rechercher un livre...">
=======
        <form method="get" action="index.php" style="margin:0;">
            <input type="text" name="q" placeholder="Rechercher un livre...">
>>>>>>> theirs
        </form>

        <button onclick="window.location.href='index.php'">Accueil</button>

        <?php if ($utilisateur): ?>
            <?php if ($utilisateur->estAdmin()): ?>
                <button onclick="window.location.href='admin.php'">Panneau Admin</button>
            <?php endif; ?>

            <button onclick="window.location.href='reservation.php'">Mes Réservations</button>

            <button onclick="window.location.href='deconnexion.php'">
                Deconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)
            </button>
        <?php else: ?>
            <button onclick="window.location.href='connexion.php'">Connexion</button>
            <button onclick="window.location.href='inscription.php'">Créer un compte</button>
        <?php endif; ?>
    </div>
</nav>
<<<<<<< ours
=======
<?php include __DIR__ . '/php/partials/header.php'; ?>
>>>>>>> theirs
=======
>>>>>>> theirs

<div class="container">
    <h1><?= htmlspecialchars($livre->getTitre()) ?></h1>
    <img src="<?= htmlspecialchars($livre->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Livre">
    <p><strong>Auteur :</strong> <?= htmlspecialchars($livre->getAuteur()) ?></p>
    <p><strong>Genre :</strong> <?= htmlspecialchars($livre->getGenre()) ?></p>
    <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($livre->getDescription() ?: 'Aucune description.')) ?></p>
    <p><strong>Statut :</strong> <?= $livre->getDisponibilite() === 'disponible' ? 'Disponible' : 'Reserve' ?></p>

    <div class="moyenne-notes" aria-hidden="false">
        <?php
        $etoilesRemplies = (int) floor($moyenne);
        $etoilesRestantes = 5 - $etoilesRemplies;
        ?>
        <?php for ($i = 0; $i < $etoilesRemplies; $i++): ?>
            <span class="star filled">&#9733;</span>
        <?php endfor; ?>
        <?php for ($i = 0; $i < $etoilesRestantes; $i++): ?>
            <span class="star">&#9734;</span>
        <?php endfor; ?>
        <span>(<?= $moyenne ?> / 5 - <?= $totalVotes ?> vote<?= $totalVotes > 1 ? 's' : '' ?>)</span>
    </div>

    <?= $message ?>

    <?php if (!$utilisateurId): ?>
        <p style="color:red; font-weight:bold;">Veuillez vous connecter pour réserver.</p>
    <?php elseif ($livre->getDisponibilite() === 'disponible'): ?>
        <form method="post">
            <input type="hidden" name="livre_id" value="<?= $livre->getId() ?>">
            <button type="submit" name="reserver">Réserver</button>
        </form>
    <?php else: ?>
        <button disabled>Déjà réservé</button>
    <?php endif; ?>
</div>

<<<<<<< ours
<<<<<<< ours
<?php require __DIR__ . '/php/components/mobile_bottom_nav.php'; ?>
=======
<script src="js/navigation.js"></script>
>>>>>>> theirs

=======
>>>>>>> theirs
</body>
</html>
