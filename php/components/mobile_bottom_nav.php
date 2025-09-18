<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentPath = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$loggedInFromSession = !empty($_SESSION['utilisateur_id'] ?? null);
$loggedInFromScope = isset($utilisateur) && $utilisateur;
$isLoggedIn = $loggedInFromScope || $loggedInFromSession;

$accountUrl = $isLoggedIn ? 'reservation.php' : 'connexion.php';
$accountLabel = $isLoggedIn ? 'Réservations' : 'Connexion';
$accountIcon = $isLoggedIn ? '📚' : '👤';
?>

<nav class="mobile-bottom-nav" aria-label="Navigation rapide mobile">
    <a href="#main-search-form" class="mobile-bottom-nav__link" data-action="focus-search">
        <span class="mobile-bottom-nav__icon" aria-hidden="true">🔍</span>
        <span class="mobile-bottom-nav__text">Recherche</span>
    </a>
    <a href="index.php" class="mobile-bottom-nav__link<?= $currentPath === '' || $currentPath === 'index.php' ? ' is-active' : '' ?>">
        <span class="mobile-bottom-nav__icon" aria-hidden="true">🏠</span>
        <span class="mobile-bottom-nav__text">Accueil</span>
    </a>
    <a href="<?= htmlspecialchars($accountUrl, ENT_QUOTES, 'UTF-8') ?>" class="mobile-bottom-nav__link<?= $isLoggedIn && $currentPath === 'reservation.php' ? ' is-active' : '' ?>">
        <span class="mobile-bottom-nav__icon" aria-hidden="true"><?= $accountIcon ?></span>
        <span class="mobile-bottom-nav__text"><?= htmlspecialchars($accountLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </a>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const triggers = document.querySelectorAll('[data-action="focus-search"]');
        const filtersToggle = document.getElementById('filtersToggle');
        const filtersPanel = document.getElementById('filters-panel');

        const openFiltersPanel = () => {
            if (!filtersPanel) {
                return;
            }
            const isHidden = filtersPanel.hasAttribute('hidden');
            if (isHidden) {
                filtersPanel.removeAttribute('hidden');
                if (filtersToggle) {
                    filtersToggle.setAttribute('aria-expanded', 'true');
                }
            }
        };

        triggers.forEach(trigger => {
            trigger.addEventListener('click', function (event) {
                const searchField = document.getElementById('filter-search') || document.querySelector('#main-search-form input[name="q"]');
                if (!searchField) {
                    return;
                }

                event.preventDefault();
                openFiltersPanel();
                searchField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                searchField.focus({ preventScroll: true });
            });
        });
    });
</script>
