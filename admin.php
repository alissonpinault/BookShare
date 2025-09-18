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

// --- Gestion du POST pour actions AJAX ---
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])){
    if($_POST['action']==='terminer'){
        $id = $_POST['id'] ?? 0;
        $livre_id = $_POST['livre_id'] ?? 0;
        if($id && $livre_id){
            $pdo->prepare("UPDATE reservations SET statut='terminer' WHERE reservation_id=?")->execute([$id]);
            $pdo->prepare("UPDATE livres SET disponibilite='disponible' WHERE livre_id=?")->execute([$livre_id]);
            echo json_encode(['success'=>true]); exit;
        }
    }
    elseif($_POST['action']==='ajouterLivre'){
        $stmt = $pdo->prepare("INSERT INTO livres (titre,auteur,genre,description,image_url,disponibilite) VALUES (?,?,?,?,?,'disponible')");
        $stmt->execute([$_POST['titre'], $_POST['auteur'], $_POST['genre'], $_POST['description'], $_POST['image_url']]);
        echo json_encode(['success'=>true]); exit;
    }
    elseif($_POST['action']==='modifierLivre'){
        $stmt = $pdo->prepare("UPDATE livres SET titre=?, auteur=?, genre=?, description=?, image_url=? WHERE livre_id=?");
        $stmt->execute([$_POST['titre'],$_POST['auteur'],$_POST['genre'],$_POST['description'],$_POST['image_url'],$_POST['livre_id']]);
        echo json_encode(['success'=>true]); exit;
    }
    elseif($_POST['action']==='supprimerLivre'){
        $pdo->prepare("DELETE FROM livres WHERE livre_id=?")->execute([$_POST['livre_id']]);
        echo json_encode(['success'=>true]); exit;
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
                echo json_encode(['success'=>true]); exit;
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
$reservations = $pdo->query("
    SELECT r.*, u.pseudo, l.titre, l.livre_id 
    FROM reservations r
    JOIN utilisateurs u ON r.utilisateur_id=u.utilisateur_id
    JOIN livres l ON r.livre_id=l.livre_id
    ORDER BY r.date_reservation DESC
")->fetchAll(PDO::FETCH_ASSOC);

$livres = $pdo->query("SELECT * FROM livres ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);

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
$utilisateurs = $pdo->query("\n    SELECT utilisateur_id, pseudo, email, role, date_inscription\n    FROM utilisateurs\n    ORDER BY pseudo\n")->fetchAll(PDO::FETCH_ASSOC);
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
button.action { padding:5px 10px; margin:2px; cursor:pointer; border-radius:4px; border:none; color:white; }
button.terminer { background: white; color: #f57c00; border: 2px solid #f57c00; }
button.terminer:hover { background:#f57c00;color: white; }
form input, form textarea { padding:6px; margin:4px; width:200px; }
form button { padding:6px 12px; margin:4px; cursor:pointer; border-radius:4px; border:none; background:#00796b; color:white; }
form button:hover { background:#004d40; }
canvas { background:white; border-radius:10px; padding:20px; margin:20px auto; display:block; max-width:90%; }
@media(max-width:600px){ table, canvas { width:95%; } nav { flex-direction:column; gap:10px; } form input, form textarea { width:90%; } }
button.supprimer {background: white; color: #b71c1c; border: 2px solid #b71c1c;}
button.supprimer:hover { background:#b71c1c ;color: white;}
button.modifier { background: white; color: #52a058ff; border: 2px solid #52a058ff;}
button.modifier:hover { background: #52a058ff;color: white;}
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
    <button class="tabBtn active" onclick="openTab('reservations', event)">Réservations</button>
    <button class="tabBtn" onclick="openTab('gererLivres', event)">Gérer les livres</button>
    <button class="tabBtn" onclick="openTab('utilisateurs', event)">Gestion utilisateurs</button>
    <button class="tabBtn" onclick="openTab('statistiques', event)">Statistiques</button>
</div>

<!-- Onglet Réservations -->
<div id="reservations" class="tabContent" style="display:block;">
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
                    <button class="action terminer" onclick="terminer(<?= $r['reservation_id'] ?>, <?= $r['livre_id'] ?>)">Terminer</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Onglet Gérer les livres -->
<div id="gererLivres" class="tabContent">

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
            <button class="action modifier" data-livre='<?= htmlspecialchars(json_encode($b), ENT_QUOTES, 'UTF-8') ?>'onclick="openEditModal(this)">Modifier</button>
            <button class="action supprimer" onclick="deleteBook(<?= $b['livre_id'] ?>)">Supprimer</button>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>

<!-- Modal Modifier (unique) -->
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
<div id="utilisateurs" class="tabContent">
    <?php if (empty($utilisateurs)): ?>
        <p style="text-align:center;">Aucun utilisateur enregistre.</p>
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
                            <button class="action supprimer" data-user="<?= (int) $u['utilisateur_id'] ?>" data-pseudo="<?= htmlspecialchars($u['pseudo'], ENT_QUOTES, 'UTF-8') ?>" onclick="deleteUser(this.dataset.user, this.dataset.pseudo)">Supprimer</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
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
function openTab(tabName, evt){
    document.querySelectorAll('.tabContent').forEach(c=>c.style.display='none');
    document.getElementById(tabName).style.display='block';
    document.querySelectorAll('.tabBtn').forEach(b=>b.classList.remove('active'));
    evt.currentTarget.classList.add('active');
}

// Réservations
function terminer(reservationId, livreId){
    fetch('admin.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=terminer&id='+reservationId+'&livre_id='+livreId })
    .then(res=>res.json()).then(d=>{ if(d.success) location.reload(); else alert('Erreur'); });
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
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Erreur'); });
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
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Erreur'); });
});

function deleteBook(livreId){
    if(confirm('Supprimer ce livre ?')){
        fetch('admin.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=supprimerLivre&livre_id='+livreId})
        .then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Erreur'); });
    }
}

function deleteUser(utilisateurId, pseudo){
    if(!confirm("Supprimer l'utilisateur "+pseudo+" ?")){
        return;
    }
    const params = new URLSearchParams();
    params.append('action','supprimerUtilisateur');
    params.append('utilisateur_id', utilisateurId);
    fetch('admin.php',{method:'POST', body:params})
        .then(r=>r.json())
        .then(d=>{ if(d.success) location.reload(); else alert(d.message || 'Erreur'); });
}

// Graphiques
let chartsCreated = false;

function openTab(tabName, evt){
    document.querySelectorAll('.tabContent').forEach(c=>c.style.display='none');
    document.getElementById(tabName).style.display='block';
    document.querySelectorAll('.tabBtn').forEach(b=>b.classList.remove('active'));
    evt.currentTarget.classList.add('active');

    if(tabName === 'statistiques' && !chartsCreated){
        createCharts();
        chartsCreated = true;
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
</script>
</body>
</html>
