<?php
require_once 'db.php';
require_once 'php/LivrePOO.php';
require_once 'php/UtilisateurPOO.php';
require_once 'php/ReservationPOO.php';

session_start();

// Session utilisateur
$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;
$utilisateur = null;

if ($utilisateur_id) {
    require_once 'php/UtilisateurPOO.php';
    $utilisateur = new Utilisateur(
    $pdo,
    $utilisateur_id,
    $_SESSION['pseudo'] ?? '',
    $_SESSION['email'] ?? '',
    $_SESSION['role'] ?? 'user'
);
}


// Récupérer l'id du livre depuis l'URL
$livre_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$livre_id) {
    header("Location: index.php");
    exit;
}

// Instancier l'objet Livre
$livre = new Livre($pdo, $livre_id);

// Vérifier que le livre existe
if (!$livre->getId()) {
    echo "Livre introuvable.";
    exit;
}

// Stats
$moyenne = $livre->getMoyenneNote();
$total_votes = $livre->getNombreVotes();

$message = "";

// --- TRAITEMENT DE LA RÉSERVATION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    if (!$utilisateur_id) {
        $message = "<p style='color:red; text-align:center;'>Veuillez vous connecter pour réserver.</p>";
    } else {
        if ($livre->reserver($utilisateur_id)) {
            header("Location: livre.php?id={$livre_id}");
            exit;
        } else {
            $message = "<p style='color:red; text-align:center;'>Ce livre est déjà réservé.</p>";
        }
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
.moyenne-notes .star.selected { color: gold; }
</style>
</head>
<body>

<!-- BARRE DE NAVIGATION -->
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
        <?php if($utilisateur->estAdmin()): ?>
            <button onclick="window.location.href='admin.php'">Panneau Admin</button>
        <?php endif; ?>

        <button onclick="window.location.href='reservation.php'">Mes Réservations</button>

        <button onclick="window.location.href='deconnexion.php'">
            Déconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)
        </button>
    <?php else: ?>
        <button onclick="window.location.href='connexion.php'">Connexion</button>
        <button onclick="window.location.href='inscription.php'">Créer un compte</button>
    <?php endif; ?>
</div>

</nav>

<!-- CONTENU DU LIVRE -->
<div class="container">
    <h1><?= htmlspecialchars($livre->getTitre()) ?></h1>
    <img src="<?= htmlspecialchars($livre->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Livre">
    <p><strong>Auteur :</strong> <?= htmlspecialchars($livre->getAuteur()) ?></p>
    <p><strong>Genre :</strong> <?= htmlspecialchars($livre->getGenre()) ?></p>
    <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($livre->getDescription() ?: 'Aucune description.')) ?></p>
    <p><strong>Statut :</strong> <?= $livre->getDisponibilite() === 'disponible' ? 'Disponible' : 'Réservé' ?></p>

    <!--- GESTION DES NOTES --->
    <div class="moyenne-notes" aria-hidden="false">
        <?php
        $etoiles_remplies = (int)floor($moyenne);
        $etoiles_restantes = 5 - $etoiles_remplies;
        ?>
        <?php for($i=0; $i<$etoiles_remplies; $i++): ?>
            <span class="star selected">★</span>
        <?php endfor; ?>
        <?php for($i=0; $i<$etoiles_restantes; $i++): ?>
            <span class="star">☆</span>
        <?php endfor; ?>
        <span>(<?= $moyenne ?> / 5 - <?= $total_votes ?> vote<?= $total_votes>1?'s':'' ?>)</span>
    </div>

    <?= $message ?>

    <?php if(!$utilisateur_id): ?>
        <p style="color:red; font-weight:bold;">Veuillez vous connecter pour réserver</p>
    <?php elseif($livre->getDisponibilite() === 'disponible'): ?>
        <form method="post">
            <input type="hidden" name="livre_id" value="<?= $livre->getId() ?>">
            <button type="submit" name="reserver">Réserver</button>
        </form>
    <?php else: ?>
        <button disabled>Déjà réservé</button>
    <?php endif; ?>
</div>

</body>
</html>
