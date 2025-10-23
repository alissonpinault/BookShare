<?php

declare(strict_types=1);

use Bookshare\Models\Livre;
use Bookshare\Models\Utilisateur;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

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
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    if (!$utilisateurId) {
        $message = 'Veuillez vous connecter pour réserver.';
        $messageType = 'error';
    } elseif ($livre->reserver((int) $utilisateurId)) {
        header('Location: reservation.php?message=' . urlencode('Réservation effectuée avec succès.') . '&status=success');
        exit;
    } else {
        $message = 'Ce livre est déjé réservé.';
        $messageType = 'error';
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
<link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/templates/partials/nav.php'; ?>

<div class="container">
    <h1><?= htmlspecialchars($livre->getTitre()) ?></h1>
    <?php
    $imagePath = $livre->getImageUrl() ?: 'assets/images/livre-defaut.jpg';
    if (str_starts_with($imagePath, 'images/')) {
        $imagePath = 'assets/' . $imagePath;
    }
    ?>
    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Livre">
    <p><strong>Auteur :</strong> <?= htmlspecialchars($livre->getAuteur()) ?></p>
    <p><strong>Genre :</strong> <?= htmlspecialchars($livre->getGenre()) ?></p>
    <p><strong>Description :</strong><br><?= str_replace("\n", "<br>", htmlspecialchars($livre->getDescription() ?: 'Aucune description.')) ?></p>
    <p><strong>Statut :</strong> <?= $livre->getDisponibilite() === 'disponible' ? 'Disponible' : 'Réservé' ?></p>

    <div class="moyenne-notes">
        <?php
        $etoilesRemplies = (int) floor($moyenne);
        $etoilesRestantes = 5 - $etoilesRemplies;
        ?>
        <div class="stars">
            <?php for ($i = 0; $i < $etoilesRemplies; $i++): ?>
                <span class="star selected">&#9733;</span>
            <?php endfor; ?>
            <?php for ($i = 0; $i < $etoilesRestantes; $i++): ?>
                <span class="star">&#9734;</span>
            <?php endfor; ?>
        </div>
        <span>(<?= number_format($moyenne, 1) ?> / 5 - <?= $totalVotes ?> vote<?= $totalVotes > 1 ? 's' : '' ?>)</span>
    </div>

    <?php if ($message !== ''): ?>
        <div class="flash-message <?= $messageType === 'error' ? 'error' : '' ?>" data-auto-dismiss="5000">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

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

<script>
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".flash-message[data-auto-dismiss]").forEach((el) => {
    const delay = parseInt(el.dataset.autoDismiss, 10);
    const timeout = Number.isFinite(delay) ? delay : 5000;
    setTimeout(() => {
      if (el.classList.contains("hide")) return;
      el.classList.add("hide");
      setTimeout(() => el.remove(), 800);
    }, timeout);
  });
});
// menu burger
document.addEventListener("DOMContentLoaded", () => {
  const burger  = document.querySelector(".burger");
  const actions = document.querySelector(".site-nav .actions");
  if (!burger || !actions) return;
  actions.classList.remove("open");
  burger.addEventListener("click", () => actions.classList.toggle("open"));
});
</script>

<?php
require_once dirname(__DIR__) . '/templates/partials/footer.php';
renderFooter([
    'baseUrl' => 'livre.php',
    'pagination' => [
        'total_items' => 0,
        'total_pages' => 0,
        'current_page' => 1,
        'query_params' => [],
    ],
]);
?>
</body>
</html>
