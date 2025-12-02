<?php
$loggedIn = isset($_SESSION['utilisateur_id']);
$role     = $_SESSION['role'] ?? '';
$pseudo   = $_SESSION['pseudo'] ?? '';
?>

<button onclick="window.location.href='index.php'">Accueil</button>

<?php if ($loggedIn): ?>
    <?php if ($role === 'admin'): ?>
        <button class="user-btn" data-type="admin" onclick="window.location.href='admin.php'">Panneau Admin</button>
    <?php endif; ?>

    <button class="user-btn" data-type="reservation" onclick="window.location.href='reservation.php'">Mes Réservations</button>
    <button id="logout-btn" class="user-btn" data-type="logout">Déconnexion (<?= htmlspecialchars($pseudo) ?>)</button>
<?php else: ?>
    <button class="user-btn" data-type="login" onclick="window.location.href='connexion.php'">Connexion</button>
    <button class="user-btn" data-type="signup" onclick="window.location.href='inscription.php'">Créer un compte</button>
<?php endif; ?>