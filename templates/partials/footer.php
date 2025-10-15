<?php

if (!function_exists('renderFooter')) {
    /**
     * Affiche le pied de page du site avec une éventuelle pagination.
     *
     * @param array{
     *     baseUrl?: string,
     *     pagination?: array{
     *         total_items?: int,
     *         total_pages?: int,
     *         current_page?: int,
     *         query_params?: array<string, scalar>
     *     }|null
     * } $options
     */
    function renderFooter(array $options = []): void
    {
        $baseUrl = $options['baseUrl'] ?? 'index.php';
        $pagination = $options['pagination'] ?? null;

        $totalItems = $pagination['total_items'] ?? null;
        $totalPages = $pagination['total_pages'] ?? null;
        $currentPage = $pagination['current_page'] ?? null;
        $queryParams = $pagination['query_params'] ?? [];

        $hasPagination = is_array($pagination)
            && isset($totalItems, $totalPages, $currentPage)
            && $totalItems > 0
            && $totalPages > 1;

        $buildUrl = static function (array $params) use ($baseUrl): string {
            $queryString = http_build_query($params);
            return $baseUrl . ($queryString ? '?' . $queryString : '');
        };
        ?>
<footer>
    <?php if ($hasPagination): ?>
        <nav class="pagination">
            <?php if ($currentPage > 1): ?>
                <?php $prevParams = $queryParams; $prevParams['page'] = $currentPage - 1; ?>
                <a href="<?= htmlspecialchars($buildUrl($prevParams), ENT_QUOTES, 'UTF-8') ?>" class="prev">Précédent</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php $pageParams = $queryParams; $pageParams['page'] = $i; ?>
                <a href="<?= htmlspecialchars($buildUrl($pageParams), ENT_QUOTES, 'UTF-8') ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <?php $nextParams = $queryParams; $nextParams['page'] = $currentPage + 1; ?>
                <a href="<?= htmlspecialchars($buildUrl($nextParams), ENT_QUOTES, 'UTF-8') ?>" class="next">Suivant</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <p class="footer-text">© 2025 BookShare - Tous droits réservés</p>
</footer>

<!-- Script principal de l'interface admin -->
<script src="/assets/js/admin.js"></script>

<?php
    }
}

?>
