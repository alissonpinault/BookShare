<?php
$loggedIn = isset($_SESSION['utilisateur_id']);
$role     = $_SESSION['role'] ?? '';
$pseudo   = $_SESSION['pseudo'] ?? '';
?>

<button onclick="window.location.href='index.php'">Accueil</button>

<?php if ($loggedIn): ?>
    <?php if ($role === 'admin'): ?>
        <button onclick="window.location.href='admin.php'">Panneau Admin</button>
    <?php endif; ?>

    <button onclick="window.location.href='reservation.php'">Mes Réservations</button>
    <button id="logout-btn">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</button>
<?php else: ?>
    <button onclick="window.location.href='connexion.php'">Connexion</button>
    <button onclick="window.location.href='inscription.php'">Créer un compte</button>
<?php endif; ?>