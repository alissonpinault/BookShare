<?php
$utilisateur = $_SESSION['utilisateur_id'] ?? null;
?>

<button onclick="window.location.href='index.php'">Accueil</button>

<?php if ($utilisateur): ?>
    <?php if ($_SESSION['role'] === 'admin'): ?>
        <button onclick="window.location.href='admin.php'">Panneau Admin</button>
    <?php endif; ?>

    <button onclick="window.location.href='reservation.php'">Mes Réservations</button>
    <button id="logout-btn">Déconnexion (<?= htmlspecialchars($_SESSION['pseudo']) ?>)</button>
<?php else: ?>
    <button onclick="window.location.href='connexion.php'">Connexion</button>
    <button onclick="window.location.href='inscription.php'">Créer un compte</button>
<?php endif; ?>
