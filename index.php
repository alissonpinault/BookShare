<?php
require_once 'db.php';
require_once __DIR__ . '/php/UtilisateurPOO.php';
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
<style>
body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #a8edea, #fed6e3);
}

/* === Barre de navigation === */
nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    background: #00796b;
    color: white;
    flex-wrap: wrap;
}
nav .logo {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    font-size: 1.5em;
}
nav .logo img { height: 40px; }
nav .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
nav input[type="text"] {
    padding: 6px 10px;
    border: none;
    border-radius: 4px;
    width: 200px;
}
nav button {
    background: #004d40;
    border: none;
    color: white;
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}
nav button:hover {
    background: #00332c;
    transform: translateY(-2px);
}

/* === Titre principal === */
h1 {
    text-align: center;
    color: #00796b;
    margin: 20px 0;
    font-family: 'Great Vibes', cursive;
    font-size: 3em;
}

/* === Carte filtres === */
.filters-panel {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 10px;
    padding: 20px;
    margin: 15px auto;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    border: 1px solid #c8e6c9;
    max-width: 960px;
}

.filters-form {
    display: flex;
    flex-wrap: wrap;
    gap: 18px;
    justify-content: space-between;
    align-items: flex-end;
}

.filters-form label {
    flex: 1;
    min-width: 180px;
    display: flex;
    flex-direction: column;
    font-weight: 600;
    color: #004d40;
    font-size: 0.95em;
}

.filters-form select,
.filters-form input[type="text"] {
    margin-top: 6px;
    padding: 8px 10px;
    border-radius: 6px;
    border: 1px solid #b2dfdb;
    font-size: 0.95em;
    background-color: #f3faf9;
}

.filters-actions {
    width: 100%;
    display: flex;
    justify-content: center;
    margin-top: 15px;
}

.filters-actions button {
    background: #00796b;
    color: #ffffff;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s, transform 0.2s;
}
.filters-actions button:hover {
    background: #004d40;
    transform: translateY(-1px);
}

/* === Cartes de livres === */
.cards-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    justify-content: center;
    padding: 20px;
}
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    width: 250px;
    overflow: hidden;
    transition: transform 0.2s;
}
.card:hover { transform: scale(1.05); }
.card img { width: 100%; height: 150px; object-fit: cover; }
.card-content { padding: 15px; text-align: center; }
.card-content h3 { margin: 5px 0; color: #00796b; }
.card-content p { margin: 5px 0; font-size: 14px; }
.card-content button {
    padding: 8px 12px;
    background: #00796b;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 10px;
}
.card-content button:disabled {
    background: #ccc;
    cursor: default;
}

/* === Pagination === */
.pagination {
    margin: 20px auto 40px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}
.pagination a {
    padding: 8px 12px;
    border-radius: 4px;
    background: #ffffff;
    color: #00796b;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    transition: background 0.3s, color 0.3s;
}
.pagination a:hover { background: #00796b; color: #ffffff; }
.pagination a.active {
    background: #004d40;
    color: #ffffff;
    pointer-events: none;
}

/* === Navigation mobile en bas === */
.mobile-bottom-nav { display: none; }
.mobile-bottom-nav__link {
    color: #004d40;
    text-decoration: none;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}
.mobile-bottom-nav__icon {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: linear-gradient(135deg, #00796b, #00acc1);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.mobile-bottom-nav__link:focus-visible .mobile-bottom-nav__icon,
.mobile-bottom-nav__link:hover .mobile-bottom-nav__icon,
.mobile-bottom-nav__link.is-active .mobile-bottom-nav__icon {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.mobile-bottom-nav__text { text-shadow: 0 1px 2px rgba(255,255,255,0.6); }

/* === Responsive === */
@media (max-width:600px) {
    nav { flex-direction: column; gap: 10px; }
    nav input[type="text"] { width: 100%; }

    .filters-form { flex-direction: column; }
    .filters-actions { justify-content: stretch; }
    .filters-actions button { width: 100%; }

    .cards-container { flex-direction: column; align-items: center; }
    .card { width: 90%; }

    .pagination { width: 100%; gap: 6px; margin: 20px auto; }
    .pagination a { flex: 1 1 48px; font-size: 14px; }

    body { padding-bottom: 88px; }
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        display: flex;
        justify-content: space-around;
        padding: 12px;
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(6px);
        border-top: 1px solid rgba(0,0,0,0.1);
        z-index: 1000;
    }
}
</style>

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
            Déconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)
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
        <form method="get" action="index.php" class="filters-form">
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
                        <option value="<?= $note ?>" <?= $noteMin === $note ? 'selected' : '' ?>><?= $note ?> ★ et plus</option>
                    <?php endfor; ?>
                </select>
            </label>
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
            <a href="index.php<?= $prevQuery ? '?' . $prevQuery : '' ?>" class="prev">Précédent</a>
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
            btn.textContent = 'Réservé';
            btn.closest('.card').querySelector('.disponibilite').textContent = 'réservé';
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

<?php require __DIR__ . '/php/components/mobile_bottom_nav.php'; ?>

</body>
</html>
