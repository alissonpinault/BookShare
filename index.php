<?php
require_once 'db.php';
require_once __DIR__ . '/php/UtilisateurPOO.php';
session_start();

// Session utilisateur â†’ objet Utilisateur
$utilisateur = null;
if (isset($_SESSION['utilisateur_id'])) {
    $utilisateur = new Utilisateur(
        $_SESSION['utilisateur_id'],
        $_SESSION['pseudo'] ?? '',
        $_SESSION['email'] ?? '',
        $_SESSION['role'] ?? 'user'
    );
}

// RÃ©cupÃ©ration des listes de genres et d'auteurs
$genresStmt = $pdo->query('SELECT DISTINCT genre FROM livres WHERE genre IS NOT NULL AND genre <> "" ORDER BY genre');
$genres = $genresStmt ? $genresStmt->fetchAll(PDO::FETCH_COLUMN) : [];

$auteursStmt = $pdo->query('SELECT DISTINCT auteur FROM livres WHERE auteur IS NOT NULL AND auteur <> "" ORDER BY auteur');
$auteurs = $auteursStmt ? $auteursStmt->fetchAll(PDO::FETCH_COLUMN) : [];

// Recherche de livres et filtres
$q = trim($_GET['q'] ?? '');
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$auteur = isset($_GET['auteur']) ? trim($_GET['auteur']) : '';
$statut = isset($_GET['statut']) ? trim($_GET['statut']) : '';
$noteMin = isset($_GET['note_min']) && $_GET['note_min'] !== '' ? (int)$_GET['note_min'] : null;

if ($noteMin !== null && ($noteMin < 1 || $noteMin > 5)) {
    $noteMin = null;
}

$parPage = 10;
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
if ($q !== '') {
    $queryParams['q'] = $q;
}
if ($genre !== '') {
    $queryParams['genre'] = $genre;
}
if ($auteur !== '') {
    $queryParams['auteur'] = $auteur;
}
if ($statut !== '') {
    $queryParams['statut'] = $statut;
}
if ($noteMin !== null) {
    $queryParams['note_min'] = $noteMin;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>BookShare - Accueil</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<link rel="stylesheet" href="style.css">

</head>
<body>

<nav>
    <div class="logo">
        <img src="images\logo.jpg" alt="Logo BookShare">
        BookShare
    </div>
   <div class="actions">
    <form method="get" action="index.php" id="main-search-form" style="margin:0;">
        <input type="text" id="main-search-input" name="q" placeholder="Rechercher un livre..." value="<?= htmlspecialchars($q) ?>">
        <?php if ($genre !== ''): ?>
            <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
        <?php endif; ?>
        <?php if ($auteur !== ''): ?>
            <input type="hidden" name="auteur" value="<?= htmlspecialchars($auteur) ?>">
        <?php endif; ?>
        <?php if ($statut !== ''): ?>
            <input type="hidden" name="statut" value="<?= htmlspecialchars($statut) ?>">
        <?php endif; ?>
        <?php if ($noteMin !== null): ?>
            <input type="hidden" name="note_min" value="<?= htmlspecialchars((string)$noteMin) ?>">
        <?php endif; ?>
    </form>

    <button onclick="window.location.href='index.php'">Accueil</button>

    <?php if ($utilisateur): ?>
        <?php if($utilisateur->estAdmin()): ?>
            <button onclick="window.location.href='admin.php'">Panneau Admin</button>
        <?php endif; ?>

        <button onclick="window.location.href='reservation.php'">Mes Réservations</button>

        <button onclick="window.location.href='deconnexion.php'">
            DÃ©connexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)
        </button>
    <?php else: ?>
        <button onclick="window.location.href='connexion.php'">Connexion</button>
        <button onclick="window.location.href='inscription.php'">Créer un compte</button>
    <?php endif; ?>
</div>

</nav>

<h1>Bienvenue sur BookShare</h1>

<div class="filters-wrapper">
    <div id="filters-panel" class="filters-panel">
        <h2 class="filters-title">ðŸ” Filtres de recherche</h2>
        <form method="get" action="index.php" class="filters-form">
            <div class="filters-row">
                <label for="filter-search">Recherche
                    <input type="text" id="filter-search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Rechercher un livre...">
                </label>
                <label for="filter-genre">Genre
                    <select id="filter-genre" name="genre">
                        <option value="">Tous les genres</option>
                        <?php foreach ($genres as $optionGenre): ?>
                            <option value="<?= htmlspecialchars($optionGenre) ?>" <?= $optionGenre === $genre ? 'selected' : '' ?>><?= htmlspecialchars($optionGenre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="filter-auteur">Auteur
                    <select id="filter-auteur" name="auteur">
                        <option value="">Tous les auteurs</option>
                        <?php foreach ($auteurs as $optionAuteur): ?>
                            <option value="<?= htmlspecialchars($optionAuteur) ?>" <?= $optionAuteur === $auteur ? 'selected' : '' ?>><?= htmlspecialchars($optionAuteur) ?></option>
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
                            <option value="<?= $note ?>" <?= $noteMin === $note ? 'selected' : '' ?>><?= $note ?> â˜… et plus</option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <div class="filters-actions">
                <button type="submit">Appliquer</button>
            </div>
        </form>
    </div>
</div>

        </form>
    </div>
</div>

<div class="cards-container">
<?php foreach ($livres as $livre): ?>
    <a href="livre.php?id=<?= $livre['livre_id'] ?>" style="text-decoration:none; color:inherit;">
        <div class="card">
            <img src="<?= htmlspecialchars($livre['image_url'] ?: 'images/livre-defaut.jpg') ?>" alt="Livre">
            <div class="card-content">
                <h3><?= htmlspecialchars($livre['titre']) ?></h3>
                <p>Auteur : <?= htmlspecialchars($livre['auteur']) ?></p>
                <p>Genre : <?= htmlspecialchars($livre['genre']) ?></p>
                <p>Statut : <span class="disponibilite"><?= htmlspecialchars($livre['disponibilite']) ?></span></p>
            </div>
        </div>
    </a>
<?php endforeach; ?>
</div>

<?php if ($totalLivres > 0): ?>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <?php $prevParams = $queryParams; $prevParams['page'] = $page - 1; $prevQuery = http_build_query($prevParams); ?>
            <a href="index.php<?= $prevQuery ? '?' . $prevQuery : '' ?>" class="prev">PrÃ©cÃ©dent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $pageParams = $queryParams; $pageParams['page'] = $i; $pageQuery = http_build_query($pageParams); ?>
            <a href="index.php<?= $pageQuery ? '?' . $pageQuery : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <?php $nextParams = $queryParams; $nextParams['page'] = $page + 1; $nextQuery = http_build_query($nextParams); ?>
            <a href="index.php<?= $nextQuery ? '?' . $nextQuery : '' ?>" class="next">Suivant</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>


<script>
function reserverLivre(livreId, btn) {
    fetch('', {
        method:'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded' },
        body: 'livre_id=' + livreId
    })
    .then(res=>res.json())
    .then(data=>{
        alert(data.message);
        if(data.success){
            btn.disabled = true;
            btn.textContent = 'RÃ©servÃ©';
            btn.closest('.card').querySelector('.disponibilite').textContent = 'rÃ©servÃ©';
        }
    })
    .catch(err=>console.error(err));
}

document.addEventListener('DOMContentLoaded', function () {
    const toggleButton = document.getElementById('filtersToggle');
    const panel = document.getElementById('filters-panel');
    const focusableSelector = 'input, select, button';

    if (toggleButton && panel) {
        toggleButton.addEventListener('click', function () {
            const isHidden = panel.hasAttribute('hidden');

            if (isHidden) {
                panel.removeAttribute('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                const focusableElement = panel.querySelector(focusableSelector);
                if (focusableElement) {
                    focusableElement.focus();
                }
            } else {
                panel.setAttribute('hidden', '');
                toggleButton.setAttribute('aria-expanded', 'false');
                toggleButton.focus();
            }
        });

        panel.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                panel.setAttribute('hidden', '');
                toggleButton.setAttribute('aria-expanded', 'false');
                toggleButton.focus();
            }
        });
    }
});
</script>
</body>
</html>
