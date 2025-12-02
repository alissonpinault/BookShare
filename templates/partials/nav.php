<?php
session_start();

$genre   = $genre   ?? '';
$auteur  = $auteur  ?? '';
$statut  = $statut  ?? '';
$noteMin = $noteMin ?? '';
$q       = $q       ?? '';
?>

<nav class="site-nav">
    <!-- Logo et burger : partie statique -->
    <a href="index.php" class="logo" style="text-decoration: none; color: inherit;">
        <img src="assets/images/logo.jpg" alt="Logo BookShare">
        BookShare
    </a>
    <button class="burger" aria-label="Menu">&#9776;</button>

    <!-- Formulaire de recherche : partie statique -->
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
        <?php if ($noteMin !== ''): ?>
            <input type="hidden" name="note_min" value="<?= htmlspecialchars((string)$noteMin) ?>">
        <?php endif; ?>
    </form>

     <!-- Zone dynamique des boutons utilisateur -->
    <div class="actions" id="nav-actions">
        <?php include __DIR__ . '/navdynamique.php'; ?>
    </div>
    
</nav>

<script src="/assets/js/logout.js" defer></script>