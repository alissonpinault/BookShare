<?php
require_once 'db.php';
session_start();

// Vérifier que l'utilisateur est admin
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    header('Location: index.php');
    exit;
}
$pseudo = $_SESSION['pseudo'];
$utilisateur_id = $_SESSION['utilisateur_id'] ?? null;
$role = $_SESSION['role'];

function readPageParam($name){
    if(isset($_GET[$name])){
        $value = filter_var($_GET[$name], FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if($value !== false){
            return $value;
        }
    }
    return 1;
}

function computePagination($total, $perPage, $page){
    $total = max(0, (int) $total);
    $perPage = max(1, (int) $perPage);
    $page = max(1, (int) $page);

    $totalPages = (int) ceil($total / $perPage);
    if($totalPages < 1){
        $totalPages = 1;
    }
    if($page > $totalPages){
        $page = $totalPages;
    }

    $offset = ($page - 1) * $perPage;
    if($offset < 0){
        $offset = 0;
    }

    return [
        'page'=>$page,
        'per_page'=>$perPage,
        'total_items'=>$total,
        'total_pages'=>$totalPages,
        'offset'=>$offset,
    ];
}

function buildPaginationUrl(array $updates, $anchor = ''){
    $params = $_GET;
    foreach($updates as $key=>$value){
        if($value === null){
            unset($params[$key]);
        }else{
            $params[$key] = $value;
        }
    }

    $query = http_build_query($params);
    $url = 'admin.php';
    if($query !== ''){
        $url .= '?' . $query;
    }
    if($anchor !== ''){
        $url .= '#' . $anchor;
    }
    return $url;
}

function renderPagination($param, array $pagination, $anchor){
    $totalPages = isset($pagination['total_pages']) ? (int) $pagination['total_pages'] : 1;
    $currentPage = isset($pagination['page']) ? (int) $pagination['page'] : 1;
    if($totalPages <= 1){
        return '';
    }

    $html = '<div class="pagination">';
    if($currentPage > 1){
        $html .= '<a class="page prev" href="' . htmlspecialchars(buildPaginationUrl([$param => $currentPage - 1], $anchor), ENT_QUOTES, 'UTF-8') . '">&laquo; Précédent</a>';
    }

    for($i = 1; $i <= $totalPages; $i++){
        if($i === $currentPage){
            $html .= '<span class="page current">' . $i . '</span>';
        }else{
            $html .= '<a class="page" href="' . htmlspecialchars(buildPaginationUrl([$param => $i], $anchor), ENT_QUOTES, 'UTF-8') . '">' . $i . '</a>';
        }
    }

    if($currentPage < $totalPages){
        $html .= '<a class="page next" href="' . htmlspecialchars(buildPaginationUrl([$param => $currentPage + 1], $anchor), ENT_QUOTES, 'UTF-8') . '">Suivant &raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}

// --- Gestion du POST pour actions AJAX ---
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    if($_POST['action']==='terminer'){
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $livre_id = isset($_POST['livre_id']) ? (int) $_POST['livre_id'] : 0;
        if($id && $livre_id){
            try{
                $pdo->prepare("UPDATE reservations SET statut='terminer' WHERE reservation_id=?")->execute([$id]);
                $pdo->prepare("UPDATE livres SET disponibilite='disponible' WHERE livre_id=?")->execute([$livre_id]);
                echo json_encode(['success'=>true,'reservation_id'=>$id,'livre_id'=>$livre_id,'statut'=>'terminer']); exit;
            }catch(Throwable $e){
                error_log('Admin terminer reservation error: '. $e->getMessage());
            }
        }
        echo json_encode(['success'=>false,'message'=>'Impossible de terminer la réservation.']); exit;
    }
    elseif($_POST['action']==='ajouterLivre'){
        $titre = $_POST['titre'] ?? '';
        $auteur = $_POST['auteur'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $description = $_POST['description'] ?? '';
        $imageUrl = $_POST['image_url'] ?? '';
        try{
            $stmt = $pdo->prepare("INSERT INTO livres (titre,auteur,genre,description,image_url,disponibilite) VALUES (?,?,?,?,?,'disponible')");
            $stmt->execute([$titre, $auteur, $genre, $description, $imageUrl]);
            $livreId = (int) $pdo->lastInsertId();
            echo json_encode([
                'success'=>true,
                'book'=>[
                    'livre_id'=>$livreId,
                    'titre'=>$titre,
                    'auteur'=>$auteur,
                    'genre'=>$genre,
                    'description'=>$description,
                    'image_url'=>$imageUrl,
                    'disponibilite'=>'disponible'
                ]
            ]); exit;
        }catch(Throwable $e){
            error_log('Admin add book error: '. $e->getMessage());
            echo json_encode(['success'=>false,'message'=>"L'ajout du livre a échoué."]); exit;
        }
    }
    elseif($_POST['action']==='modifierLivre'){
        $livreId = isset($_POST['livre_id']) ? (int) $_POST['livre_id'] : 0;
        if(!$livreId){
            echo json_encode(['success'=>false,'message'=>'Livre introuvable.']); exit;
        }
        $titre = $_POST['titre'] ?? '';
        $auteur = $_POST['auteur'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $description = $_POST['description'] ?? '';
        $imageUrl = $_POST['image_url'] ?? '';
        try{
            $stmt = $pdo->prepare("UPDATE livres SET titre=?, auteur=?, genre=?, description=?, image_url=? WHERE livre_id=?");
            $stmt->execute([$titre,$auteur,$genre,$description,$imageUrl,$livreId]);
            echo json_encode([
                'success'=>true,
                'book'=>[
                    'livre_id'=>$livreId,
                    'titre'=>$titre,
                    'auteur'=>$auteur,
                    'genre'=>$genre,
                    'description'=>$description,
                    'image_url'=>$imageUrl
                ]
            ]); exit;
        }catch(Throwable $e){
            error_log('Admin edit book error: '. $e->getMessage());
            echo json_encode(['success'=>false,'message'=>'La mise à jour du livre a échoué.']); exit;
        }
    }
    elseif($_POST['action']==='supprimerLivre'){
        $livreId = isset($_POST['livre_id']) ? (int) $_POST['livre_id'] : 0;
        if($livreId){
            try{
                $pdo->prepare("DELETE FROM livres WHERE livre_id=?")->execute([$livreId]);
                echo json_encode(['success'=>true,'livre_id'=>$livreId]); exit;
            }catch(Throwable $e){
                error_log('Admin delete book error: '. $e->getMessage());
            }
        }
        echo json_encode(['success'=>false,'message'=>'Suppression du livre impossible.']); exit;
    }
    elseif($_POST['action']==='supprimerUtilisateur'){
        $userId = isset($_POST['utilisateur_id']) ? (int) $_POST['utilisateur_id'] : 0;
        if($userId){
            if($utilisateur_id && $userId === (int)$utilisateur_id){
                echo json_encode(['success'=>false,'message'=>"Impossible de supprimer votre propre compte."]); exit;
            }
            try{
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM notes WHERE utilisateur_id=?")->execute([$userId]);
                $pdo->prepare("DELETE FROM reservations WHERE utilisateur_id=?")->execute([$userId]);
                $pdo->prepare("DELETE FROM utilisateurs WHERE utilisateur_id=?")->execute([$userId]);
                $pdo->commit();
                echo json_encode(['success'=>true,'utilisateur_id'=>$userId]); exit;
            }catch(Throwable $e){
                $pdo->rollBack();
                error_log('Admin delete user error: '. $e->getMessage());
                echo json_encode(['success'=>false,'message'=>'Suppression impossible.']); exit;
            }
        }
        echo json_encode(['success'=>false,'message'=>'Utilisateur introuvable.']); exit;
    }
}

// --- Récupération des données ---
$reservationsPerPage = 10;
$livresPerPage = 10;
$utilisateursPerPage = 10;

$reservationsPage = readPageParam('reservations_page');
$livresPage = readPageParam('livres_page');
$utilisateursPage = readPageParam('utilisateurs_page');

$totalReservations = (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$totalLivres = (int) $pdo->query("SELECT COUNT(*) FROM livres")->fetchColumn();
$totalUtilisateurs = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();

$reservationsPagination = computePagination($totalReservations, $reservationsPerPage, $reservationsPage);
$livresPagination = computePagination($totalLivres, $livresPerPage, $livresPage);
$utilisateursPagination = computePagination($totalUtilisateurs, $utilisateursPerPage, $utilisateursPage);

$reservationsStmt = $pdo->prepare("
    SELECT r.*, u.pseudo, l.titre, l.livre_id
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id=u.utilisateur_id
    JOIN livres l ON r.livre_id=l.livre_id
    ORDER BY r.date_reservation DESC
    LIMIT :limit OFFSET :offset
");
$reservationsStmt->bindValue(':limit', $reservationsPagination['per_page'], PDO::PARAM_INT);
$reservationsStmt->bindValue(':offset', $reservationsPagination['offset'], PDO::PARAM_INT);
$reservationsStmt->execute();
$reservations = $reservationsStmt->fetchAll(PDO::FETCH_ASSOC);

$livresStmt = $pdo->prepare("SELECT * FROM livres ORDER BY titre LIMIT :limit OFFSET :offset");
$livresStmt->bindValue(':limit', $livresPagination['per_page'], PDO::PARAM_INT);
$livresStmt->bindValue(':offset', $livresPagination['offset'], PDO::PARAM_INT);
$livresStmt->execute();
$livres = $livresStmt->fetchAll(PDO::FETCH_ASSOC);

$chartLivres = $pdo->query("
    SELECT l.titre, COUNT(r.reservation_id) as total
    FROM livres l
    LEFT JOIN reservations r ON l.livre_id = r.livre_id
    GROUP BY l.livre_id
")->fetchAll(PDO::FETCH_ASSOC);

$chartUsers = $pdo->query("
    SELECT u.pseudo, COUNT(r.reservation_id) as total
    FROM utilisateurs u
    LEFT JOIN reservations r ON u.utilisateur_id = r.utilisateur_id
    GROUP BY u.utilisateur_id
")->fetchAll(PDO::FETCH_ASSOC);
$utilisateursStmt = $pdo->prepare("\n    SELECT utilisateur_id, pseudo, email, role, date_inscription\n    FROM utilisateurs\n    ORDER BY pseudo\n    LIMIT :limit OFFSET :offset\n");
$utilisateursStmt->bindValue(':limit', $utilisateursPagination['per_page'], PDO::PARAM_INT);
$utilisateursStmt->bindValue(':offset', $utilisateursPagination['offset'], PDO::PARAM_INT);
$utilisateursStmt->execute();
$utilisateurs = $utilisateursStmt->fetchAll(PDO::FETCH_ASSOC);
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
<style>
body { margin:0; font-family:'Roboto', sans-serif; background: linear-gradient(135deg,#a8edea,#fed6e3); }
/* === NAVIGATION BAR === */
nav { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:#00796b; color:white; flex-wrap:wrap; }
nav .logo { display:flex; align-items:center; gap:10px; font-weight:bold; font-size:1.5em; }
nav .logo img { height:40px; }
nav .actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
nav input[type="text"] { padding:6px 10px; border:none; border-radius:4px; width:200px; }
nav button { background:#004d40; border:none; color:white; padding:6px 12px; border-radius:4px; cursor:pointer; transition: all 0.3s; }
nav button:hover { background:#00332c; transform: translateY(-2px); }
h1 { text-align:center; color:#00796b; margin:20px 0; font-family:'Great Vibes', cursive; font-size:3em; }
.tab-buttons { text-align:center; margin-bottom:20px; }
.tab-buttons button { margin:0 5px; padding:8px 12px; cursor:pointer; border-radius:5px; border:none; background:#00796b; color:white; transition:all 0.3s; }
.tab-buttons button:hover, .tab-buttons button.active { background:#004d40; }
.tabContent { display:none; max-width:95%; margin:0 auto 30px auto; }
table { border-collapse: collapse; width:100%; margin-bottom:30px; background:white; border-radius:10px; overflow:hidden; }
th, td { border:1px solid #00796b; padding:8px; text-align:center; }
th { background:#00796b; color:white; }
.pagination { display:flex; justify-content:center; align-items:center; gap:8px; flex-wrap:wrap; margin:10px 0 30px; }
.pagination .page { display:inline-flex; align-items:center; justify-content:center; padding:6px 12px; border-radius:4px; border:1px solid #00796b; color:#00796b; text-decoration:none; background:white; min-width:36px; transition:all 0.3s; font-weight:500; }
.pagination .page:hover { background:#00796b; color:white; }
.pagination .current { background:#00796b; color:white; border-color:#00796b; font-weight:700; cursor:default; }
.pagination-info { text-align:center; margin-bottom:10px; color:#004d40; font-weight:500; }
button.action { padding:5px 10px; margin:2px; cursor:pointer; border-radius:4px; border:none; color:white; }
button.terminer { background: white; color: #f57c00; border: 2px solid #f57c00; }
button.terminer:hover { background:#f57c00;color: white; }
form input, form textarea { padding:6px; margin:4px; width:200px; }
form button { padding:6px 12px; margin:4px; cursor:pointer; border-radius:4px; border:none; background:#00796b; color:white; }
form button:hover { background:#004d40; }
canvas { background:white; border-radius:10px; padding:20px; margin:20px auto; display:block; max-width:90%; }
button.supprimer {background: white; color: #b71c1c; border: 2px solid #b71c1c;}
button.supprimer:hover { background:#b71c1c ;color: white;}
button.modifier { background: white; color: #52a058ff; border: 2px solid #52a058ff;}
button.modifier:hover { background: #52a058ff;color: white;}
.mobile-bottom-nav { display:none; }
.mobile-bottom-nav__link { color:#004d40; text-decoration:none; font-size:0.75rem; font-weight:600; display:flex; flex-direction:column; align-items:center; gap:6px; }
.mobile-bottom-nav__icon { width:54px; height:54px; border-radius:50%; background:linear-gradient(135deg,#00796b,#00acc1); color:#ffffff; display:flex; align-items:center; justify-content:center; font-size:1.4rem; box-shadow:0 8px 16px rgba(0,0,0,0.15); transition:transform 0.2s ease, box-shadow 0.2s ease; }
.mobile-bottom-nav__link:focus-visible .mobile-bottom-nav__icon,
.mobile-bottom-nav__link:hover .mobile-bottom-nav__icon,
.mobile-bottom-nav__link.is-active .mobile-bottom-nav__icon { transform:translateY(-2px); box-shadow:0 10px 20px rgba(0,0,0,0.2); }
.mobile-bottom-nav__text { text-shadow:0 1px 2px rgba(255,255,255,0.6); }
@media(max-width:600px){
    body { padding-bottom:88px; }
    table, canvas { width:95%; }
    nav { flex-direction:column; gap:10px; }
    form input, form textarea { width:90%; }
    .mobile-bottom-nav { position:fixed; bottom:0; left:0; right:0; display:flex; justify-content:space-around; padding:12px; background:rgba(255,255,255,0.8); backdrop-filter: blur(6px); border-top:1px solid rgba(0,0,0,0.1); z-index:1000; }
}
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
    <form method="get" action="index.php" id="main-search-form" style="margin:0;">
        <input type="text" id="main-search-input" name="q" placeholder="Rechercher un livre...">
    </form>

     <button onclick="window.location.href='index.php'">Accueil</button>

    <?php if ($utilisateur_id): ?>
        <?php if($role==='admin'): ?>
            <button onclick="window.location.href='admin.php'">Panneau Admin</button>
        <?php endif; ?>

        <button onclick="window.location.href='reservation.php'">Mes Réservations</button>

        <button onclick="window.location.href='deconnexion.php'">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</button>
    <?php else: ?>
        <button onclick="window.location.href='connexion.php'">Connexion</button>
        <button onclick="window.location.href='inscription.php'">Créer un compte</button>
    <?php endif; ?>
</div>
</nav>

<h1>Panneau Administrateur</h1>

<div class="tab-buttons">
    <button class="tabBtn active" data-tab="reservations" onclick="openTab('reservations', event)">Réservations</button>
    <button class="tabBtn" data-tab="gererLivres" onclick="openTab('gererLivres', event)">Gérer les livres</button>
    <button class="tabBtn" data-tab="utilisateurs" onclick="openTab('utilisateurs', event)">Gestion utilisateurs</button>
    <button class="tabBtn" data-tab="statistiques" onclick="openTab('statistiques', event)">Statistiques</button>
</div>

<!-- Onglet Réservations -->
<div id="reservations" class="tabContent" data-page-param="reservations_page" data-current-page="<?= (int) $reservationsPagination['page'] ?>" data-total-pages="<?= (int) $reservationsPagination['total_pages'] ?>" style="display:block;">
    <table>
        <tr>
            <th>Utilisateur</th><th>Livre</th><th>Date</th><th>Statut</th><th>Actions</th>
        </tr>
        <?php foreach($reservations as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['pseudo']) ?></td>
            <td><?= htmlspecialchars($r['titre']) ?></td>
            <td><?= date('Y-m-d', strtotime($r['date_reservation'])) ?></td>
            <td><?= $r['statut'] ?></td>
            <td>
                <?php if($r['statut'] === 'en cours'): ?>
                    <button class="action terminer" data-reservation="<?= (int) $r['reservation_id'] ?>" data-livre="<?= (int) $r['livre_id'] ?>" onclick="terminer(this, <?= (int) $r['reservation_id'] ?>, <?= (int) $r['livre_id'] ?>)">Terminer</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php
        $reservationsCount = count($reservations);
        $reservationsStart = $reservationsCount ? $reservationsPagination['offset'] + 1 : 0;
        $reservationsEnd = $reservationsCount ? $reservationsPagination['offset'] + $reservationsCount : 0;
    ?>
    <div class="pagination-info">
        <?php if($reservationsCount): ?>
            Affichage de <?= htmlspecialchars($reservationsStart, ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars($reservationsEnd, ENT_QUOTES, 'UTF-8') ?> sur <?= htmlspecialchars($reservationsPagination['total_items'], ENT_QUOTES, 'UTF-8') ?> réservations.
        <?php else: ?>
            Aucune réservation trouvée.
        <?php endif; ?>
    </div>
    <?= renderPagination('reservations_page', $reservationsPagination, 'reservations'); ?>
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
    <div style="background:white; padding:30px; border-radius:15px; max-width:500px; width:90%; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3); transform:scale(0.9); transition: transform 0.3s ease;">
        <span style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:1.5em;" onclick="closeAddModal()">✖</span>
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
    <button onclick="filterBooks()">🔍</button>
</div>

<table id="booksTable">
    <tr><th>Titre</th><th>Auteur</th><th>Genre</th><th>Actions</th></tr>
    <?php foreach($livres as $b): ?>
    <tr data-id="<?= $b['livre_id'] ?>">
        <td><?= htmlspecialchars($b['titre']) ?></td>
        <td><?= htmlspecialchars($b['auteur']) ?></td>
        <td><?= htmlspecialchars($b['genre']) ?></td>
        <td>
            <button class="action modifier" data-livre='<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>' onclick="openEditModal(this)">Modifier</button>
            <button class="action supprimer" onclick="deleteBook(this, <?= (int) $b['livre_id'] ?>)">Supprimer</button>
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
        Affichage de <?= htmlspecialchars($livresStart, ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars($livresEnd, ENT_QUOTES, 'UTF-8') ?> sur <?= htmlspecialchars($livresPagination['total_items'], ENT_QUOTES, 'UTF-8') ?> livres.
    <?php else: ?>
        Aucun livre trouvé.
    <?php endif; ?>
</div>
<?= renderPagination('livres_page', $livresPagination, 'gererLivres'); ?>
</div>

<!-- Modal Modifier -->
<div id="modalEdit" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:1000;">
    <div style="background:white; padding:30px; border-radius:15px; max-width:500px; width:90%; position:relative; box-shadow:0 10px 30px rgba(0,0,0,0.3); transform:scale(0.9); transition: transform 0.3s ease;">
        <span style="position:absolute; top:15px; right:20px; cursor:pointer; font-size:1.5em;" onclick="closeEditModal()">✖</span>
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
            <tr><th>Pseudo</th><th>Email</th><th>Role</th><th>Inscription</th><th>Actions</th></tr>
            <?php foreach ($utilisateurs as $u): ?>
                <?php
                    $roleUtilisateur = $u['role'] ?? 'utilisateur';
                    $isSelf = $utilisateur_id && (int) $u['utilisateur_id'] === (int) $utilisateur_id;
                    $isAdminRole = $roleUtilisateur === 'admin';
                    $dateInscription = isset($u['date_inscription']) ? date('Y-m-d', strtotime($u['date_inscription'])) : '-';
                ?>
                <tr>
                    <td><?= htmlspecialchars($u['pseudo']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($roleUtilisateur) ?></td>
                    <td><?= htmlspecialchars($dateInscription) ?></td>
                    <td>
                        <?php if ($isSelf): ?>
                            <em>Compte actuel</em>
                        <?php elseif ($isAdminRole): ?>
                            <em>Administrateur</em>
                        <?php else: ?>
                            <button class="action supprimer" data-user="<?= (int) $u['utilisateur_id'] ?>" data-pseudo="<?= htmlspecialchars($u['pseudo'], ENT_QUOTES, 'UTF-8') ?>" onclick="deleteUser(this, this.dataset.user, this.dataset.pseudo)">Supprimer</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <div class="pagination-info">
        <?php if($utilisateursCount): ?>
            Affichage de <?= htmlspecialchars($utilisateursStart, ENT_QUOTES, 'UTF-8') ?> à <?= htmlspecialchars($utilisateursEnd, ENT_QUOTES, 'UTF-8') ?> sur <?= htmlspecialchars($utilisateursPagination['total_items'], ENT_QUOTES, 'UTF-8') ?> utilisateurs.
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
// Utilitaires
const DEFAULT_ERROR_MESSAGE = 'Une erreur est survenue. Veuillez réessayer.';

function showError(message){
    alert(message || DEFAULT_ERROR_MESSAGE);
}

function toJson(response){
    if(!response.ok){
        throw new Error('Réponse réseau invalide');
    }
    return response.json();
}

function handleRequestError(error){
    console.error(error);
    showError("La requête a échoué. Veuillez réessayer.");
}

function getSectionMeta(sectionId){
    const section = document.getElementById(sectionId);
    if(!section){
        return null;
    }
    let page = parseInt(section.dataset.currentPage || '1', 10);
    if(!page || page < 1){
        page = 1;
    }
    const param = section.dataset.pageParam || '';
    return { section, page, param };
}

function refreshSection(sectionId){
    const meta = getSectionMeta(sectionId);
    const url = new URL(window.location.href);
    if(meta && meta.param){
        if(meta.page > 1){
            url.searchParams.set(meta.param, meta.page);
        }else{
            url.searchParams.delete(meta.param);
        }
    }
    url.hash = sectionId;
    window.location.href = url.toString();
}

// Réservations
function terminer(button, reservationId, livreId){
    const params = new URLSearchParams();
    params.append('action','terminer');
    params.append('id', reservationId);
    params.append('livre_id', livreId);

    fetch('admin.php', { method:'POST', body:params })
        .then(toJson)
        .then(d=>{
            if(d.success){
                const row = button.closest('tr');
                if(row){
                    if(row.cells[3]){
                        row.cells[3].textContent = d.statut || 'terminer';
                    }
                    if(row.cells[4]){
                        row.cells[4].textContent = '';
                    }
                }
            }else{
                showError(d.message);
            }
        })
        .catch(handleRequestError);
}

// Livres
function filterBooks(){
    const input = document.getElementById('searchBook').value.toLowerCase();
    document.querySelectorAll('#booksTable tr').forEach((row, i) => {
        if(i === 0) return;
        const title = row.cells[0] ? row.cells[0].innerText.toLowerCase() : '';
        const author = row.cells[1] ? row.cells[1].innerText.toLowerCase() : '';
        const genre = row.cells[2] ? row.cells[2].innerText.toLowerCase() : '';
        const searchableText = `${title} ${author} ${genre}`.toLowerCase();
        row.style.display = searchableText.includes(input) ? '' : 'none';
    });
}

function openAddModal(){
    const modal = document.getElementById('modalAdd');
    const box = modal.firstElementChild;
    modal.style.display = 'flex';
    setTimeout(()=>{ box.style.transform = 'scale(1)'; box.style.opacity = '1'; }, 10);
}
function closeAddModal(){
    const modal = document.getElementById('modalAdd');
    const box = modal.firstElementChild;
    box.style.transform = 'scale(0.9)'; box.style.opacity = '0';
    setTimeout(()=> modal.style.display = 'none', 300);
}

document.getElementById('formAddBook').addEventListener('submit', e=>{
    e.preventDefault();
    const data = new URLSearchParams(new FormData(e.target));
    data.append('action','ajouterLivre');
    fetch('admin.php',{method:'POST', body:data})
        .then(toJson)
        .then(d=>{
            if(d.success){
                e.target.reset();
                closeAddModal();
                refreshSection('gererLivres');
            }else{
                showError(d.message);
            }
        })
        .catch(handleRequestError);
});

function openEditModal(button) {
    const livre = JSON.parse(button.dataset.livre);
    const modal = document.getElementById('modalEdit');
    document.getElementById('modal_edit_livre_id').value = livre.livre_id;
    document.getElementById('modal_edit_titre').value = livre.titre;
    document.getElementById('modal_edit_auteur').value = livre.auteur;
    document.getElementById('modal_edit_genre').value = livre.genre;
    document.getElementById('modal_edit_description').value = livre.description;
    document.getElementById('modal_edit_image_url').value = livre.image_url;

    modal.style.display = 'flex';
    setTimeout(() => {
        modal.firstElementChild.style.transform = 'scale(1)';
        modal.firstElementChild.style.opacity = '1';
    }, 10);
}

function closeEditModal() {
    const modal = document.getElementById('modalEdit');
    const box = modal.firstElementChild;
    box.style.transform = 'scale(0.9)'; box.style.opacity = '0';
    setTimeout(()=> modal.style.display='none', 300);
}

document.getElementById('formEditBookModal').addEventListener('submit', e => {
    e.preventDefault();
    const data = new URLSearchParams(new FormData(e.target));
    data.append('action','modifierLivre');
    fetch('admin.php',{method:'POST', body:data})
        .then(toJson)
        .then(d=>{
            if(d.success && d.book){
                const row = document.querySelector(`#booksTable tr[data-id="${d.book.livre_id}"]`);
                if(row){
                    if(row.cells[0]) row.cells[0].textContent = d.book.titre || '';
                    if(row.cells[1]) row.cells[1].textContent = d.book.auteur || '';
                    if(row.cells[2]) row.cells[2].textContent = d.book.genre || '';
                    const editBtn = row.querySelector('button.modifier');
                    if(editBtn){
                        editBtn.dataset.livre = JSON.stringify(d.book);
                    }
                }
                closeEditModal();
            }else{
                showError(d.message);
            }
        })
        .catch(handleRequestError);
});

function deleteBook(button, livreId){
    if(!confirm('Supprimer ce livre ?')){
        return;
    }
    const params = new URLSearchParams();
    params.append('action','supprimerLivre');
    params.append('livre_id', livreId);
    fetch('admin.php',{method:'POST', body:params})
        .then(toJson)
        .then(d=>{
            if(d.success){
                const editInput = document.getElementById('modal_edit_livre_id');
                if(editInput){
                    const editModalId = parseInt(editInput.value, 10);
                    if(editModalId === Number(livreId)){
                        closeEditModal();
                    }
                }
                refreshSection('gererLivres');
            }else{
                showError(d.message);
            }
        })
        .catch(handleRequestError);
}

function deleteUser(button, utilisateurId, pseudo){
    if(!confirm("Supprimer l'utilisateur "+pseudo+" ?")){
        return;
    }
    const params = new URLSearchParams();
    params.append('action','supprimerUtilisateur');
    params.append('utilisateur_id', utilisateurId);
    fetch('admin.php',{method:'POST', body:params})
        .then(toJson)
        .then(d=>{
            if(d.success){
                refreshSection('utilisateurs');
            }else{
                showError(d.message);
            }
        })
        .catch(handleRequestError);
}

// Graphiques
let chartsCreated = false;

function openTab(tabName, evt, skipHashUpdate){
    document.querySelectorAll('.tabContent').forEach(c=>c.style.display='none');
    const target = document.getElementById(tabName);
    if(target){
        target.style.display = 'block';
    }

    document.querySelectorAll('.tabBtn').forEach(b=>b.classList.remove('active'));
    if(evt && evt.currentTarget){
        evt.currentTarget.classList.add('active');
    }else{
        const button = document.querySelector(`.tabBtn[data-tab="${tabName}"]`);
        if(button){
            button.classList.add('active');
        }
    }

    if(tabName === 'statistiques' && !chartsCreated){
        createCharts();
        chartsCreated = true;
    }

    if(!skipHashUpdate){
        try{
            const url = new URL(window.location.href);
            url.hash = tabName;
            history.replaceState(null, '', url);
        }catch(error){
            window.location.hash = tabName;
        }
    }
}

function createCharts(){
    const livresData = <?= json_encode(array_column($chartLivres,'total')) ?>;
    const livresLabels = <?= json_encode(array_column($chartLivres,'titre')) ?>;
    const usersData = <?= json_encode(array_column($chartUsers,'total')) ?>;
    const usersLabels = <?= json_encode(array_column($chartUsers,'pseudo')) ?>;

    const noDataMsg = document.getElementById('noDataMessage');

    // Vérification si aucune donnée
    if(livresData.length === 0 && usersData.length === 0){
        noDataMsg.style.display = 'block';
        return;
    } else {
        noDataMsg.style.display = 'none';
    }

    const chartOptions = {
        responsive:true,
        plugins:{
            legend:{display:false},
            tooltip:{enabled:true}
        },
        scales:{
            y:{beginAtZero:true, ticks:{stepSize:1}},
            x:{ticks:{autoSkip:false, maxRotation:45, minRotation:0}}
        }
    };

    if(livresData.length > 0){
        const canvasLivres = document.getElementById('graphLivres');
        canvasLivres.style.display = 'block';
        new Chart(canvasLivres, {
            type:'bar',
            data:{
                labels:livresLabels,
                datasets:[{
                    label:'Réservations',
                    data:livresData,
                    backgroundColor:'#52a058cc',
                    borderColor:'#52a058',
                    borderWidth:1,
                    borderRadius:6,
                    barPercentage:0.6
                }]
            },
            options:chartOptions
        });
    }

    if(usersData.length > 0){
        const canvasUsers = document.getElementById('graphUsers');
        canvasUsers.style.display = 'block';
        new Chart(canvasUsers, {
            type:'bar',
            data:{
                labels:usersLabels,
                datasets:[{
                    label:'Réservations',
                    data:usersData,
                    backgroundColor:'#f5a623cc',
                    borderColor:'#f57c00',
                    borderWidth:1,
                    borderRadius:6,
                    barPercentage:0.6
                }]
            },
            options:chartOptions
        });
    }
}

function toggleMenu(){
  document.getElementById('navMenu').classList.toggle('show');
}

document.addEventListener('DOMContentLoaded', () => {
    const initialHash = window.location.hash ? window.location.hash.substring(1) : 'reservations';
    const targetTab = document.getElementById(initialHash) ? initialHash : 'reservations';
    const button = document.querySelector(`.tabBtn[data-tab="${targetTab}"]`);
    openTab(targetTab, { currentTarget: button }, !window.location.hash);
});
</script>

<?php require __DIR__ . '/php/components/mobile_bottom_nav.php'; ?>

</body>
</html>
