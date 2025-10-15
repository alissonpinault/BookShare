<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

// 🔎 Gestion des erreurs visuelles (utile sur Heroku)
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<pre style='color:red;'>PHP ERROR [$errno] : $errstr in $errfile on line $errline</pre>";
    return true;
});

use Bookshare\Models\Utilisateur;

$container = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $container['pdo'];
$mongoDB = $container['mongoDB'] ?? null;

session_start();

// ✅ Vérification du rôle admin
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
function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

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
    return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
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
        $html .= '<a class="page prev" href="' . e(buildPaginationUrl([$param => $currentPage - 1], $anchor)) . '">&laquo; Précédent</a>';
    }
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === $currentPage) $html .= '<span class="page current">' . $i . '</span>';
        else $html .= '<a class="page" href="' . e(buildPaginationUrl([$param => $i], $anchor)) . '">' . $i . '</a>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<a class="page next" href="' . e(buildPaginationUrl([$param => $currentPage + 1], $anchor)) . '">Suivant &raquo;</a>';
    }
    return $html . '</div>';
}

/* ==============================
   GESTION DES ACTIONS AJAX
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !empty($_POST['action'])) {

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

// --- Réservations
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

// --- Livres
$livresStmt = $pdo->prepare("SELECT * FROM livres ORDER BY titre LIMIT :limit OFFSET :offset");
$livresStmt->bindValue(':limit', $livresPagination['per_page'], PDO::PARAM_INT);
$livresStmt->bindValue(':offset', $livresPagination['offset'], PDO::PARAM_INT);
$livresStmt->execute();
$livres = $livresStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// --- Utilisateurs
$utilisateursStmt = $pdo->prepare("
    SELECT utilisateur_id, pseudo, email, role, date_inscription
    FROM utilisateurs
    ORDER BY pseudo
    LIMIT :limit OFFSET :offset
");
$utilisateursStmt->bindValue(':limit', $utilisateursPagination['per_page'], PDO::PARAM_INT);
$utilisateursStmt->bindValue(':offset', $utilisateursPagination['offset'], PDO::PARAM_INT);
$utilisateursStmt->execute();
$utilisateurs = $utilisateursStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// --- Statistiques
$chartLivres = $pdo->query("
    SELECT l.titre, COUNT(r.reservation_id) AS total
    FROM livres l
    LEFT JOIN reservations r ON l.livre_id = r.livre_id
    GROUP BY l.livre_id
")->fetchAll(PDO::FETCH_ASSOC);

$chartUsers = $pdo->query("
    SELECT u.pseudo, COUNT(r.reservation_id) AS total
    FROM utilisateurs u
    LEFT JOIN reservations r ON u.utilisateur_id = r.utilisateur_id
    GROUP BY u.utilisateur_id
")->fetchAll(PDO::FETCH_ASSOC);

// --- Messages flash
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
            <?= e($flashMessage) ?>
        </div>
    <?php endif; ?>
</div>

<!-- ===================== ONGLET STATISTIQUES ===================== -->
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
          closeBtn.addEventListener('click', closeAddModal);
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
<script src="<?= e($adminJsPath . '?v=' . $adminJsVersion) ?>"></script>
</body>
</html>
<?php ob_end_flush(); ?>