<?php
session_start();

$genre   = $genre   ?? '';
$auteur  = $auteur  ?? '';
$statut  = $statut  ?? '';
$noteMin = $noteMin ?? '';
$q       = $q       ?? '';
?>

<nav class="site-nav" style="padding: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <!-- Logo -->
    <a href="index.php" class="logo" style="text-decoration: none; color: inherit; display: flex; align-items: center; margin-right: 20px;">
        <img src="assets/images/logo.jpg" alt="Logo BookShare" style="height: 40px; margin-right: 10px;">
        BookShare
    </a>

    <!-- BOUTON BURGER (affiché en mobile) -->
    <button class="burger" id="burger-btn" aria-label="Menu">
        &#9776;
    </button>

    <!-- Zone boutons + recherche -->
    <div class="actions" id="nav-actions" style="align-items: center; gap: 10px; flex-wrap: wrap;">
        
        <!-- Barre de recherche -->
        <form method="get" action="index.php" id="main-search-form" style="display: flex; align-items: center; margin: 0;">
            <input type="text" id="main-search-input" name="q" placeholder="Rechercher un livre..." value="<?= htmlspecialchars($q) ?>" style="padding: 6px 10px; border-radius: 4px; border: 1px solid #ccc;">
            <?php if ($genre !== ''): ?>
                <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
            <?php endif; ?>
            <?php if ($auteur !== ''): ?>
                <input type="hidden" name="auteur" value="<?= htmlspecialchars($auteur) ?>">
            <?php endif; ?>
            <?php if ($statut !== ''): ?>
                <input type="hidden" name="statut" value="<?= htmlspecialchars($statut) ?>">
            <?php endif; ?>
            <?php if ($noteMin !== ''): ?>
                <input type="hidden" name="note_min" value="<?= htmlspecialchars((string)$noteMin) ?>">
            <?php endif; ?>
        </form>

        <!-- Autres boutons utilisateur (connexion/admin/mes réservations) -->
        <?php include __DIR__ . '/navdynamique.php'; ?>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const burger = document.getElementById("burger-btn");
    const actions = document.getElementById("nav-actions");

    burger.addEventListener("click", () => {
        actions.classList.toggle("open");
    });
});
</script>

<script src="/assets/js/logout.js" defer></script>