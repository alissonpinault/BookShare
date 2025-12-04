<?php
session_start();

$genre   = $genre   ?? '';
$auteur  = $auteur  ?? '';
$statut  = $statut  ?? '';
$noteMin = $noteMin ?? '';
$q       = $q       ?? '';
?>

<nav class="site-nav">
    <!-- Logo -->
    <a href="index.php" class="logo">
        <img src="assets/images/logo.jpg" alt="Logo BookShare">
        BookShare
    </a>

    <!-- Bouton burger (mobile) -->
    <button class="burger" id="burger-btn" aria-label="Menu">
        &#9776;
    </button>

    <!-- Zone boutons + recherche -->
    <div class="actions" id="nav-actions">
        <!-- Formulaire de recherche -->
        <form method="get" action="index.php" id="main-search-form">
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
            <?php if ($noteMin !== ''): ?>
                <input type="hidden" name="note_min" value="<?= htmlspecialchars((string)$noteMin) ?>">
            <?php endif; ?>
            <button type="submit" style="display:none;">Rechercher</button>
        </form>

        <!-- Autres boutons utilisateur -->
        <?php include __DIR__ . '/navdynamique.php'; ?>
    </div>
</nav>

<!-- Script burger inline -->
<script defer>
document.addEventListener("DOMContentLoaded", () => {
    const burger = document.getElementById("burger-btn");
    const actions = document.getElementById("nav-actions");

    burger.addEventListener("click", () => {
        actions.classList.toggle("open");
    });
});
</script>