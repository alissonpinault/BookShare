<?php
require_once 'db.php';
require_once __DIR__ . 'php/classes/utilisateurPOO.php';
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

// Recherche de livres
$q = $_GET['q'] ?? '';
if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE titre LIKE :q OR auteur LIKE :q OR genre LIKE :q");
    $stmt->execute([':q' => "%$q%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM livres");
}
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
body { margin:0; font-family:'Roboto', sans-serif; background: linear-gradient(135deg, #a8edea, #fed6e3); }
nav { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:#00796b; color:white; flex-wrap:wrap; }
nav .logo { display:flex; align-items:center; gap:10px; font-weight:bold; font-size:1.5em; }
nav .logo img { height:40px; }
nav .actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
nav input[type="text"] { padding:6px 10px; border:none; border-radius:4px; width:200px; }
nav button { background:#004d40; border:none; color:white; padding:6px 12px; border-radius:4px; cursor:pointer; transition: all 0.3s; }
nav button:hover { background:#00332c; transform: translateY(-2px); }
h1 { text-align:center; color:#00796b; margin:20px 0; font-family:'Great Vibes', cursive; font-size:3em; }
.cards-container { display:flex; flex-wrap:wrap; gap:20px; justify-content:center; padding: 20px; }
.card { background:white; border-radius:10px; box-shadow:0 4px 8px rgba(0,0,0,0.2); width:250px; overflow:hidden; transition: transform 0.2s; }
.card:hover { transform: scale(1.05); }
.card img { width:100%; height:150px; object-fit:cover; }
.card-content { padding:15px; text-align:center; }
.card-content h3 { margin:5px 0; color:#00796b; }
.card-content p { margin:5px 0; font-size:14px; }
.card-content button { padding:8px 12px; background:#00796b; color:white; border:none; border-radius:5px; cursor:pointer; margin-top:10px; }
.card-content button:disabled { background:#ccc; cursor:default; }
@media (max-width:600px) { nav { flex-direction: column; gap:10px; } nav input[type="text"] { width:100%; } .cards-container { flex-direction:column; align-items:center; } .card { width:90%; } }
</style>
</head>
<body>

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

<h1>Bienvenue sur BookShare</h1>

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
</script>

</body>
</html>
