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
<link rel="stylesheet" href="style.css">

</head>
<body>

<nav>
    <div class="logo">
        <img src="images/logo.jpg" alt="Logo BookShare">
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
<?php include __DIR__ . '/php/partials/header.php'; ?>

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

<?php require __DIR__ . '/php/components/mobile_bottom_nav.php'; ?>

<script src="js/navigation.js"></script>
</div>
</body>
</html>