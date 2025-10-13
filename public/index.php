<?php
declare(strict_types=1);

use Bookshare\Models\Utilisateur;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

session_start();

// Session utilisateur → objet Utilisateur
$utilisateur = null;
if (isset($_SESSION['utilisateur_id'])) {
    $utilisateur = new Utilisateur(
        $_SESSION['utilisateur_id'],
        $_SESSION['pseudo'] ?? '',
        $_SESSION['email'] ?? '',
        $_SESSION['role'] ?? 'user'
    );
}

// Récupération des listes de genres et d'auteurs
$genresStmt = $pdo->query('SELECT DISTINCT genre FROM livres WHERE genre IS NOT NULL AND genre <> "" ORDER BY genre');
$genres = $genresStmt ? $genresStmt->fetchAll(PDO::FETCH_COLUMN) : [];

$auteursStmt = $pdo->query('SELECT DISTINCT auteur FROM livres WHERE auteur IS NOT NULL AND auteur <> "" ORDER BY auteur');
$auteurs = $auteursStmt ? $auteursStmt->fetchAll(PDO::FETCH_COLUMN) : [];

// Recherche de livres et filtres
$q = trim($_GET['q'] ?? '');
$genre = $_GET['genre'] ?? '';
$auteur = $_GET['auteur'] ?? '';
$statut = $_GET['statut'] ?? '';
$noteMin = isset($_GET['note_min']) && $_GET['note_min'] !== '' ? (int)$_GET['note_min'] : null;

if ($noteMin !== null && ($noteMin < 1 || $noteMin > 5)) {
    $noteMin = null;
}

$parPage = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $parPage;

$conditions = [];
$params = [];
$joinSql = '';
$selectColumns = 'SELECT l.*';

if ($q !== '') {
    $conditions[] = '(l.titre LIKE :search OR l.auteur LIKE :search OR l.genre LIKE :search)';
    $params['search'] = "%$q%";
}

if ($genre !== '') {
    $conditions[] = 'l.genre = :genre';
    $params['genre'] = $genre;
}

if ($auteur !== '') {
    $conditions[] = 'l.auteur = :auteur';
    $params['auteur'] = $auteur;
}

if ($statut === 'disponible' || $statut === 'indisponible') {
    $conditions[] = 'l.disponibilite = :statut';
    $params['statut'] = $statut;
} else {
    $statut = '';
}

if ($noteMin !== null) {
    $joinSql = ' LEFT JOIN (SELECT livre_id, AVG(note) AS moyenne_note FROM notes GROUP BY livre_id) n ON n.livre_id = l.livre_id';
    $conditions[] = '(n.moyenne_note >= :note_min OR n.moyenne_note IS NULL)';
    $params['note_min'] = $noteMin;
    $selectColumns .= ', n.moyenne_note';
}

$whereSql = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$baseFromSql = ' FROM livres l' . $joinSql;

$countSql = 'SELECT COUNT(DISTINCT l.livre_id)' . $baseFromSql . $whereSql;
$countStmt = $pdo->prepare($countSql);

foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $countStmt->bindValue(':' . $key, $value, $paramType);
}
$countStmt->execute();
$totalLivres = (int)$countStmt->fetchColumn();

$totalPages = (int)ceil($totalLivres / $parPage);
if ($totalPages === 0) {
    $page = 1;
    $offset = 0;
} elseif ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $parPage;
}

$selectSql = $selectColumns . $baseFromSql . $whereSql . ' ORDER BY l.titre ASC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($selectSql);

foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue(':' . $key, $value, $paramType);
}

$stmt->bindValue(':limit', $parPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

$queryParams = [];
if ($q !== '') $queryParams['q'] = $q;
if ($genre !== '') $queryParams['genre'] = $genre;
if ($auteur !== '') $queryParams['auteur'] = $auteur;
if ($statut !== '') $queryParams['statut'] = $statut;
if ($noteMin !== null) $queryParams['note_min'] = $noteMin;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>BookShare - Accueil</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/templates/partials/nav.php'; ?>

<h1>Bienvenue sur BookShare</h1>

<main class="layout">
    <!-- Filtres (colonne desktop/tablette) -->
    <aside class="filters-wrapper">
        <div id="filters-panel" class="filters-panel">
            <h2 class="filters-title">Filtres de recherche</h2>
            <form method="get" action="index.php" class="filters-form">
                <label for="filter-genre">Genre
                    <select id="filter-genre" name="genre">
                        <option value="">Tous les genres</option>
                        <?php foreach ($genres as $optionGenre): ?>
                            <option value="<?= htmlspecialchars($optionGenre) ?>" <?= $optionGenre === $genre ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optionGenre) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label for="filter-auteur">Auteur
                    <select id="filter-auteur" name="auteur">
                        <option value="">Tous les auteurs</option>
                        <?php foreach ($auteurs as $optionAuteur): ?>
                            <option value="<?= htmlspecialchars($optionAuteur) ?>" <?= $optionAuteur === $auteur ? 'selected' : '' ?>>
                                <?= htmlspecialchars($optionAuteur) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label for="filter-statut">Statut
                    <select id="filter-statut" name="statut">
                        <option value="">Tous les statuts</option>
                        <option value="disponible" <?= $statut === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                        <option value="indisponible" <?= $statut === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
                    </select>
                </label>

                <label for="filter-note">Note minimale
                    <select id="filter-note" name="note_min">
                        <option value="">Aucune note minimale</option>
                        <?php for ($note = 1; $note <= 5; $note++): ?>
                            <option value="<?= $note ?>" <?= $noteMin === $note ? 'selected' : '' ?>>
                                <?= $note ?> ★ et plus
                            </option>
                        <?php endfor; ?>
                    </select>
                </label>

                <div class="filters-actions">
                    <button type="submit">Appliquer</button>
                </div>
            </form>
        </div>
    </aside>

    <!-- Bouton filtres (mobile uniquement) -->
    <button class="filters-toggle">Filtres</button>

    <!-- Liste des livres -->
    <section class="cards-container">
        <?php foreach ($livres as $livre): ?>
            <a href="livre.php?id=<?= $livre['livre_id'] ?>" style="text-decoration:none; color:inherit;">
                <div class="card">
                    <?php
                    $imagePath = $livre['image_url'] ?: 'assets/images/livre-defaut.jpg';
                    if (str_starts_with($imagePath, 'images/')) {
                        $imagePath = 'assets/' . $imagePath;
                    }
                    ?>
                    <img src="<?= htmlspecialchars($imagePath) ?>" alt="Livre">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                        <p>Statut : <span class="disponibilite"><?= htmlspecialchars($livre['disponibilite']) ?></span></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
</main>

<?php
require_once dirname(__DIR__) . '/templates/partials/footer.php';
renderFooter([
    'baseUrl' => 'index.php',
    'pagination' => [
        'total_items' => $totalLivres,
        'total_pages' => $totalPages,
        'current_page' => $page,
        'query_params' => $queryParams,
    ],
]);
?>

<script>
// menu burger
document.addEventListener("DOMContentLoaded", () => {
  const burger  = document.querySelector(".burger");
  const actions = document.querySelector(".site-nav .actions");
  if (!burger || !actions) return;
  actions.classList.remove("open");
  burger.addEventListener("click", () => actions.classList.toggle("open"));
});

// Filtres (mobile)
document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.querySelector(".filters-toggle");
  const panel = document.querySelector(".filters-wrapper .filters-panel");

  // Crée le modal
  const modal = document.createElement("div");
  modal.className = "filters-modal";

  // Clone du panneau
  const modalContent = panel.cloneNode(true);

  // Ajout du bouton Fermer
  const closeBtn = document.createElement("button");
  closeBtn.className = "close-btn";
  closeBtn.innerHTML = "&times;"; // croix
  modalContent.appendChild(closeBtn);

  modal.appendChild(modalContent);
  document.body.appendChild(modal);

  // Ouvrir modal
  toggleBtn.addEventListener("click", () => {
    modal.classList.add("open");
  });

  // Fermer modal (croix)
  closeBtn.addEventListener("click", () => {
    modal.classList.remove("open");
  });

  // Fermer si on clique hors du panneau
  modal.addEventListener("click", e => {
    if(e.target === modal) modal.classList.remove("open");
  });
});
</script>
</body>
</html>
