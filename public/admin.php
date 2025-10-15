<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<pre style='color:red;'>PHP ERROR [$errno] : $errstr in $errfile on line $errline</pre>";
    return true;
});

use Bookshare\Models\Utilisateur;

$container = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $container['pdo'];
$mongoDB = $container['mongoDB'] ?? null;

session_start();

// Vérifier que l'utilisateur est admin
if (empty($_SESSION['utilisateur_id'])) {
    header('Location: index.php');
    exit;
}

$utilisateur = new Utilisateur(
    $_SESSION['utilisateur_id'],
    $_SESSION['pseudo'] ?? '',
    $_SESSION['email'] ?? '',
    $_SESSION['role'] ?? 'user'
);
$utilisateurId = $utilisateur->getId();

if (!$utilisateur->estAdmin()) {
    header('Location: index.php');
    exit;
}

/* ==============================
   FONCTIONS UTILITAIRES
================================ */
function readPageParam($name)
{
    if (isset($_GET[$name])) {
        $value = filter_var($_GET[$name], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value !== false) return $value;
    }
    return 1;
}

function computePagination($total, $perPage, $page)
{
    $total = max(0, (int)$total);
    $perPage = max(1, (int)$perPage);
    $page = max(1, (int)$page);
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages < 1) $totalPages = 1;
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;
    return [
        'page' => $page,
        'per_page' => $perPage,
        'total_items' => $total,
        'total_pages' => $totalPages,
        'offset' => max(0, $offset)
    ];
}

function formatReservationStatus($status)
{
    $map = [
        'en_attente' => 'En attente',
        'validee' => 'Validée',
        'refusee' => 'Refusée',
        'terminee' => 'Terminée',
    ];

    $key = strtolower((string)$status);

    if (isset($map[$key])) {
        return $map[$key];
    }

    $key = str_replace('_', ' ', $key);
    return ucfirst($key);
}


function buildPaginationUrl(array $updates, $anchor = '')
{
    $params = $_GET;
    foreach ($updates as $key => $value) {
        if ($value === null) unset($params[$key]);
        else $params[$key] = $value;
    }
    $query = http_build_query($params);
    $url = 'admin.php';
    if ($query !== '') $url .= '?' . $query;
    if ($anchor !== '') $url .= '#' . $anchor;
    return $url;
}

function renderPagination($param, array $pagination, $anchor)
{
    $totalPages = (int)($pagination['total_pages'] ?? 1);
    $currentPage = (int)($pagination['page'] ?? 1);
    if ($totalPages <= 1) return '';
    $html = '<div class="pagination">';
    if ($currentPage > 1) {
        $html .= '<a class="page prev" href="' . htmlspecialchars(buildPaginationUrl([$param => $currentPage - 1], $anchor)) . '">&laquo; Précédent</a>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $currentPage) $html .= '<span class="page current">' . $i . '</span>';
        else $html .= '<a class="page" href="' . htmlspecialchars(buildPaginationUrl([$param => $i], $anchor)) . '">' . $i . '</a>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<a class="page next" href="' . htmlspecialchars(buildPaginationUrl([$param => $currentPage + 1], $anchor)) . '">Suivant &raquo;</a>';
    }
    return $html . '</div>';
}

/* ==============================
   GESTION DES ACTIONS AJAX
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($_POST['action'])) {

    // Ne traite que les appels AJAX
    if (
        !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
    ) {
        ob_clean();
        header('Content-Type: application/json');
        $action = $_POST['action'];

        try {
            switch ($action) {
                case 'valider':
                    $id = (int)$_POST['reservation_id'];
                    $livre_id = (int)$_POST['livre_id'];
                    if ($id && $livre_id) {
                        $pdo->beginTransaction();
                        $pdo->prepare("UPDATE reservations SET statut='validee' WHERE reservation_id=?")->execute([$id]);
                        $pdo->prepare("UPDATE livres SET disponibilite='indisponible' WHERE livre_id=?")->execute([$livre_id]);
                        $pdo->commit();
                        echo json_encode(['success' => true, 'statut' => 'validee', 'statut_label' => 'Validée']);
                        exit;
                    }
                    break;

                case 'refuser':
                    $id = (int)$_POST['reservation_id'];
                    $livre_id = (int)$_POST['livre_id'];
                    if ($id && $livre_id) {
                        $pdo->beginTransaction();
                        $pdo->prepare("UPDATE reservations SET statut='refusee' WHERE reservation_id=?")->execute([$id]);
                        $pdo->prepare("UPDATE livres SET disponibilite='disponible' WHERE livre_id=?")->execute([$livre_id]);
                        $pdo->commit();
                        echo json_encode(['success' => true, 'statut' => 'refusee', 'statut_label' => 'Refusée']);
                        exit;
                    }
                    break;

                case 'terminer':
                    $id = (int)$_POST['reservation_id'];
                    $livre_id = (int)$_POST['livre_id'];
                    if ($id && $livre_id) {
                        $pdo->beginTransaction();
                        $pdo->prepare("UPDATE reservations SET statut='terminee' WHERE reservation_id=?")->execute([$id]);
                        $pdo->prepare("UPDATE livres SET disponibilite='disponible' WHERE livre_id=?")->execute([$livre_id]);
                        $pdo->commit();
                        echo json_encode(['success' => true, 'statut' => 'terminee', 'statut_label' => 'Terminée']);
                        exit;
                    }
                    break;
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Action invalide ou incomplète.']);
        exit;
    }
}

/* ==============================
   CHARGEMENT DES DONNÉES
================================ */
$reservationsPerPage = 10;
$livresPerPage = 10;
$utilisateursPerPage = 10;

$reservationsPage = readPageParam('reservations_page');
$livresPage = readPageParam('livres_page');
$utilisateursPage = readPageParam('utilisateurs_page');

$totalReservations = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$totalLivres = (int)$pdo->query("SELECT COUNT(*) FROM livres")->fetchColumn();
$totalUtilisateurs = (int)$pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();

$reservationsPagination = computePagination($totalReservations, $reservationsPerPage, $reservationsPage);
$livresPagination = computePagination($totalLivres, $livresPerPage, $livresPage);
$utilisateursPagination = computePagination($totalUtilisateurs, $utilisateursPerPage, $utilisateursPage);

// Données principales
$reservationsEnAttente = $pdo->query("
    SELECT r.*, u.pseudo, l.titre, l.livre_id
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.utilisateur_id
    JOIN livres l ON r.livre_id = l.livre_id
    WHERE r.statut = 'en_attente'
    ORDER BY r.date_reservation DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reservationsValidees = $pdo->query("
    SELECT r.*, u.pseudo, l.titre, l.livre_id
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.utilisateur_id
    JOIN livres l ON r.livre_id = l.livre_id
    WHERE r.statut = 'validee'
    ORDER BY r.date_reservation DESC
")->fetchAll(PDO::FETCH_ASSOC);

$reservationsTerminees = $pdo->query("
    SELECT r.*, u.pseudo, l.titre, l.livre_id
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id = u.utilisateur_id
    JOIN livres l ON r.livre_id = l.livre_id
    WHERE r.statut = 'terminee'
    ORDER BY r.date_reservation DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Chargement des livres ---
$livresStmt = $pdo->prepare("
    SELECT * 
    FROM livres 
    ORDER BY titre 
    LIMIT :limit OFFSET :offset
");
$livresStmt->bindValue(':limit', $livresPagination['per_page'], PDO::PARAM_INT);
$livresStmt->bindValue(':offset', $livresPagination['offset'], PDO::PARAM_INT);
$livresStmt->execute();
$livres = $livresStmt->fetchAll(PDO::FETCH_ASSOC);
if (!is_array($livres)) $livres = [];

// --- Chargement des utilisateurs ---
$utilisateursStmt = $pdo->prepare("
    SELECT utilisateur_id, pseudo, email, role, date_inscription
    FROM utilisateurs
    ORDER BY pseudo
    LIMIT :limit OFFSET :offset
");
$utilisateursStmt->bindValue(':limit', $utilisateursPagination['per_page'], PDO::PARAM_INT);
$utilisateursStmt->bindValue(':offset', $utilisateursPagination['offset'], PDO::PARAM_INT);
$utilisateursStmt->execute();
$utilisateurs = $utilisateursStmt->fetchAll(PDO::FETCH_ASSOC);
if (!is_array($utilisateurs)) $utilisateurs = [];

// --- Messages flash par défaut
$flashMessage = $flashMessage ?? '';
$flashStatus = $flashStatus ?? 'success';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Admin - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/templates/partials/nav.php'; ?>

<h1>Panneau Administrateur</h1>

<div id="flash-container" class="flash-container">
    <?php if ($flashMessage !== ''): ?>
        <div class="flash-message <?= $flashStatus === 'error' ? 'error' : '' ?>" data-auto-dismiss="5000">
            <?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
</div>

<div class="tab-buttons">
  <button type="button" class="tabBtn active" data-tab="reservations">Réservations</button>
  <button type="button" class="tabBtn" data-tab="gererLivres">Gérer les livres</button>
  <button type="button" class="tabBtn" data-tab="utilisateurs">Gestion utilisateurs</button>
  <button type="button" class="tabBtn" data-tab="statistiques">Statistiques</button>
</div>

<!-- ===================== CONTENU ONGLET RÉSERVATIONS ===================== -->
<div id="reservations" class="tabContent active">

  <!-- ===================== SOUS-ONGLETS ===================== -->
  <div class="sub-tabs">
  <button class="subTabBtn subTabBtnenattente active" data-subtab="attente">En attente</button>
  <button class="subTabBtn subTabBtnencours" data-subtab="encours">En cours</button>
  <button class="subTabBtn subTabBtnarchive" data-subtab="archive">Archivées</button>
</div>

    <!-- Réservations en attente -->
    <div id="attente" class="subTabContent subTabContentenattente active">
        <?php if (empty($reservationsEnAttente)): ?>
            <p>Aucune réservation en attente.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Utilisateur</th>
                    <th>Livre</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($reservationsEnAttente as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['pseudo']) ?></td>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['date_reservation'])) ?></td>
                    <td data-cell="statut"><?= htmlspecialchars(formatReservationStatus($r['statut'])) ?></td>
                    <td data-cell="actions">
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['reservation_id'] ?>">
                            <input type="hidden" name="livre_id" value="<?= (int)$r['livre_id'] ?>">
                            <button type="submit" name="action" value="valider" class="valider">Valider</button>
                            <button type="submit" name="action" value="refuser" class="refuser">Refuser</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- Réservations validées -->
    <div id="validees" class="subTabContent subTabContentencours">
        <?php if (empty($reservationsValidees)): ?>
            <p>Aucune réservation validée.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Utilisateur</th>
                    <th>Livre</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($reservationsValidees as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['pseudo']) ?></td>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['date_reservation'])) ?></td>
                    <td data-cell="statut"><?= htmlspecialchars(formatReservationStatus($r['statut'])) ?></td>
                    <td data-cell="actions">
                        <form method="post">
                            <input type="hidden" name="reservation_id" value="<?= (int)$r['reservation_id'] ?>">
                            <input type="hidden" name="livre_id" value="<?= (int)$r['livre_id'] ?>">
                            <button type="submit" name="action" value="terminer" class="terminer">Terminer</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <!-- Réservations terminées -->
    <div id="terminees" class="subTabContent subTabContentarchive">
        <?php if (empty($reservationsTerminees)): ?>
            <p>Aucune réservation terminée.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Utilisateur</th>
                    <th>Livre</th>
                    <th>Date</th>
                    <th>Statut</th>
                </tr>
                <?php foreach ($reservationsTerminees as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['pseudo']) ?></td>
                    <td><?= htmlspecialchars($r['titre']) ?></td>
                    <td><?= date('d-m-Y', strtotime($r['date_reservation'])) ?></td>
                    <td data-cell="statut"><?= htmlspecialchars(formatReservationStatus($r['statut'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Onglet Gérer les livres -->
<div id="gererLivres" class="tabContent" data-page-param="livres_page" data-current-page="<?= (int) $livresPagination['page'] ?>" data-total-pages="<?= (int) $livresPagination['total_pages'] ?>">

<div style="text-align:center; margin-bottom:20px;">
    <button onclick="openAddModal()" style=" 
        padding:12px 25px; font-size:16px; font-weight:bold; border:none;
        background:#00796b; color:white; border-radius:8px; cursor:pointer;
        box-shadow:0 4px 8px rgba(0,0,0,0.2); transition: all 0.3s;"
        onmouseover="this.style.background='#004d40'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 12px rgba(0,0,0,0.3)';"
        onmouseout="this.style.background='#00796b'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)';">
        Ajouter un livre
    </button>
</div>

<!-- Modal Ajouter un livre -->
<div id="modalAdd" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:15px; max-width:500px; width:90%; 
        position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3); transform:scale(0.9); transition: transform 0.3s ease;">
        
        <span class="modal-close" id="modalAddCloseBtn">&#10006;</span>
        <h2 style="font-family: 'Great Vibes', cursive; text-align:center; font-size:2.5em; margin-bottom:25px;">Ajouter un livre</h2>
        <form id="formAddBook" style="max-width:400px; margin:0 auto; text-align:center;">
            <input type="text" name="titre" placeholder="Titre" required>
            <input type="text" name="auteur" placeholder="Auteur" required>
            <input type="text" name="genre" placeholder="Genre">
            <textarea name="description" placeholder="Description"></textarea>
            <input type="text" name="image_url" placeholder="URL de l'image">
            <button type="submit">Ajouter</button>
        </form>
    </div>
</div>

<!-- Barre de recherche -->
<div style="margin-bottom:15px; text-align:center;">
    <input type="text" id="searchBook" placeholder="Rechercher un livre...">
    <button onclick="filterBooks()">ðŸ”</button>
</div>

<table id="booksTable">
    <tr>
        <th>Titre</th>
        <th>Auteur</th>
        <th>Genre</th>
        <th>Actions</th>
    </tr>
    <?php foreach($livres as $b): ?>
    <tr data-id="<?= $b['livre_id'] ?>">
        <td data-label="Titre"><?= htmlspecialchars($b['titre']) ?></td>
        <td data-label="Auteur"><?= htmlspecialchars($b['auteur']) ?></td>
        <td data-label="Genre"><?= htmlspecialchars($b['genre']) ?></td>
        <td data-label="Actions">
            <button class="action modifier"
                    data-livre='<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>'
                    onclick="openEditModal(this)">
                Modifier
            </button>
            <button class="action supprimer"
                    onclick="deleteBook(this, <?= (int) $b['livre_id'] ?>)">
                Supprimer
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php
    $livresCount = count($livres);
    $livresStart = $livresCount ? $livresPagination['offset'] + 1 : 0;
    $livresEnd = $livresCount ? $livresPagination['offset'] + $livresCount : 0;
?>
<div class="pagination-info">
    <?php if($livresCount): ?>
        Affichage de <?= htmlspecialchars($livresStart, ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars($livresEnd, ENT_QUOTES, 'UTF-8') ?> sur <?= htmlspecialchars((string)$livresPagination['total_items'], ENT_QUOTES, 'UTF-8') ?>
 livres.
    <?php else: ?>
        Aucun livre trouvé.
    <?php endif; ?>
</div>
<?= renderPagination('livres_page', $livresPagination, 'gererLivres'); ?>
</div>

<!-- Modal Modifier -->
<div id="modalEdit" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:15px; max-width:500px; width:90%; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3); transform:scale(0.9); transition: transform 0.3s ease;">
        <span style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:1.5em;" onclick="closeEditModal()">âœ–</span>
        <h2 style="font-family: 'Great Vibes', cursive; text-align:center; font-size:2.5em; margin-bottom:25px;">Modifier le livre</h2>
        <form id="formEditBookModal" style="max-width:400px; margin:0 auto; text-align:center;">
            <input type="hidden" name="livre_id" id="modal_edit_livre_id">
            <input type="text" name="titre" id="modal_edit_titre" placeholder="Titre" required>
            <input type="text" name="auteur" id="modal_edit_auteur" placeholder="Auteur" required>
            <input type="text" name="genre" id="modal_edit_genre" placeholder="Genre">
            <textarea name="description" id="modal_edit_description" placeholder="Description"></textarea>
            <input type="text" name="image_url" id="modal_edit_image_url" placeholder="URL de l'image">
            <button type="submit">Enregistrer</button>
        </form>
    </div>
</div>

<!-- Onglet Gestion utilisateurs -->
<div id="utilisateurs" class="tabContent" data-page-param="utilisateurs_page" data-current-page="<?= (int) $utilisateursPagination['page'] ?>" data-total-pages="<?= (int) $utilisateursPagination['total_pages'] ?>">
    <?php
        $utilisateursCount = count($utilisateurs);
        $utilisateursStart = $utilisateursCount ? $utilisateursPagination['offset'] + 1 : 0;
        $utilisateursEnd = $utilisateursCount ? $utilisateursPagination['offset'] + $utilisateursCount : 0;
    ?>
    <?php if ($utilisateursCount === 0): ?>
        <p style="text-align:center;">Aucun utilisateur enregistré.</p>
    <?php else: ?>
        <table>
    <tr>
        <th>Pseudo</th>
        <th>Email</th>
        <th>Role</th>
        <th>Inscription</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($utilisateurs as $u): ?>
        <?php
            $roleUtilisateur = $u['role'] ?? 'utilisateur';
            $isSelf = $utilisateurId && (int) $u['utilisateur_id'] === (int) $utilisateurId;
            $isAdminRole = $roleUtilisateur === 'admin';
            $dateInscription = isset($u['date_inscription']) ? date('d-m-Y', strtotime($u['date_inscription'])) : '-';
        ?>
        <tr>
            <td data-label="Pseudo"><?= htmlspecialchars($u['pseudo']) ?></td>
            <td data-label="Email"><?= htmlspecialchars($u['email']) ?></td>
            <td data-label="Role"><?= htmlspecialchars($roleUtilisateur) ?></td>
            <td data-label="Inscription"><?= htmlspecialchars($dateInscription) ?></td>
            <td data-label="Actions">
                <?php if ($isSelf): ?>
                    <em>Compte actuel</em>
                <?php elseif ($isAdminRole): ?>
                    <em>Administrateur</em>
                <?php else: ?>
                    <button class="action supprimer"
                            data-user="<?= (int) $u['utilisateur_id'] ?>"
                            data-pseudo="<?= htmlspecialchars($u['pseudo'], ENT_QUOTES, 'UTF-8') ?>"
                            onclick="deleteUser(this, this.dataset.user, this.dataset.pseudo)">
                        Supprimer
                    </button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

    <?php endif; ?>
    <div class="pagination-info">
        <?php if($utilisateursCount): ?>
            Affichage de <?= htmlspecialchars($utilisateursStart, ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars($utilisateursEnd, ENT_QUOTES, 'UTF-8') ?> sur <?= htmlspecialchars((string)$utilisateursPagination['total_items'], ENT_QUOTES, 'UTF-8') ?> utilisateurs.
        <?php else: ?>
            Aucun utilisateur à afficher.
        <?php endif; ?>
    </div>
    <?= renderPagination('utilisateurs_page', $utilisateursPagination, 'utilisateurs'); ?>
</div>
<!-- Onglet Statistiques -->
<div id="statistiques" class="tabContent">
    <div id="statsContent" style="text-align:center;">
        <p id="noDataMessage" style="color:#b71c1c; font-weight:bold; display:none;">Aucune réservation pour générer des statistiques.</p>
        <canvas id="graphLivres" height="200" style="display:none;"></canvas>
        <canvas id="graphUsers" height="200" style="display:none;"></canvas>
    </div>
</div>
<script>
  window.chartData = {
    livres: {
      data: <?= json_encode(array_column($chartLivres,'total')) ?>,
      labels: <?= json_encode(array_column($chartLivres,'titre')) ?>
    },
    users: {
      data: <?= json_encode(array_column($chartUsers,'total')) ?>,
      labels: <?= json_encode(array_column($chartUsers,'pseudo')) ?>
    }
  };

document.addEventListener('DOMContentLoaded', function() {
    var closeBtn = document.getElementById('modalAddCloseBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeAddModal();
        });
    }
});
</script>

<?php
require_once dirname(__DIR__) . '/templates/partials/footer.php';
renderFooter(['baseUrl' => 'admin.php']);

$adminJsPath = 'assets/js/admin.js';
$adminJsFullPath = __DIR__ . '/' . $adminJsPath;
$adminJsVersion = file_exists($adminJsFullPath) ? (string) filemtime($adminJsFullPath) : (string) time();
?>

<script src="<?= htmlspecialchars($adminJsPath . '?v=' . $adminJsVersion, ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>
<?php ob_end_flush(); ?>
