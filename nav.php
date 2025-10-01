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