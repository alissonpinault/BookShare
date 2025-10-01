<?php if ($totalLivres > 0): ?>
<footer>
    <nav class="pagination">
        <?php if ($page > 1): ?>
            <?php $prevParams = $queryParams; $prevParams['page'] = $page - 1; $prevQuery = http_build_query($prevParams); ?>
            <a href="index.php<?= $prevQuery ? '?' . $prevQuery : '' ?>" class="prev">Précédent</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $pageParams = $queryParams; $pageParams['page'] = $i; $pageQuery = http_build_query($pageParams); ?>
            <a href="index.php<?= $pageQuery ? '?' . $pageQuery : '' ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <?php $nextParams = $queryParams; $nextParams['page'] = $page + 1; $nextQuery = http_build_query($nextParams); ?>
            <a href="index.php<?= $nextQuery ? '?' . $nextQuery : '' ?>" class="next">Suivant</a>
        <?php endif; ?>
    </nav>

    <p class="footer-text">© 2025 BookShare - Tous droits réservés</p>
</footer>
<?php endif; ?>
