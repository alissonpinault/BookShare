<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/php/LivrePOO.php';
require_once __DIR__ . '/php/UtilisateurPOO.php';
require_once __DIR__ . '/php/ReservationPOO.php';

session_start();

if (empty($_SESSION['utilisateur_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $utilisateur = Utilisateur::getById($pdo, (int) $_SESSION['utilisateur_id']);
    if (!$utilisateur) {
        throw new RuntimeException('Utilisateur introuvable.');
    }

    $reservationService = new Reservation($pdo);

    $pageSize = 5;
    $allowedTabs = ['encours', 'archivees'];
    $sanitizePage = static function (?string $value): int {
        $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $page ?: 1;
    };

    $pageEncours = $sanitizePage($_GET['page_encours'] ?? null);
    $pageArchivees = $sanitizePage($_GET['page_archivees'] ?? null);

    $requestedTab = $_GET['tab'] ?? '';
    $hasExplicitTab = in_array($requestedTab, $allowedTabs, true);
    $activeTab = $hasExplicitTab ? $requestedTab : 'encours';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler'], $_POST['reservation_id'])) {
        $reservationId = (int) $_POST['reservation_id'];
        $annulee = $reservationService->annulerReservation($reservationId, $utilisateur->getId());

        $message = $annulee
            ? 'Réservation annulée avec succès.'
            : "Impossible d'annuler cette réservation.";

        $redirectParams = ['message' => $message];

        if (isset($_POST['page_encours'])) {
            $redirectParams['page_encours'] = $sanitizePage($_POST['page_encours']);
        }
        if (isset($_POST['page_archivees'])) {
            $redirectParams['page_archivees'] = $sanitizePage($_POST['page_archivees']);
        }
        if (!empty($_POST['tab']) && in_array($_POST['tab'], $allowedTabs, true)) {
            $redirectParams['tab'] = $_POST['tab'];
        }

        $redirectUrl = 'reservation.php';
        $queryString = http_build_query($redirectParams);
        if ($queryString !== '') {
            $redirectUrl .= '?' . $queryString;
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    $totalEncours = $reservationService->countReservationsEnCours($utilisateur->getId());
    $totalArchivees = $reservationService->countReservationsArchivees($utilisateur->getId());

    $totalPagesEncours = max(1, (int) ceil($totalEncours / $pageSize));
    $totalPagesArchivees = max(1, (int) ceil($totalArchivees / $pageSize));

    $pageEncours = min($pageEncours, $totalPagesEncours);
    $pageArchivees = min($pageArchivees, $totalPagesArchivees);

    if (!$hasExplicitTab && isset($_GET['page_archivees']) && (!isset($_GET['page_encours']) || $pageArchivees > 1)) {
        $activeTab = 'archivees';
    }

    $offsetEncours = ($pageEncours - 1) * $pageSize;
    $offsetArchivees = ($pageArchivees - 1) * $pageSize;

    $reservationsEnCours = $reservationService->getReservationsEnCours(
        $utilisateur->getId(),
        $pageSize,
        $offsetEncours
    );
    $reservationsArchivees = $reservationService->getReservationsArchivees(
        $utilisateur->getId(),
        $pageSize,
        $offsetArchivees
    );

    $baseQueryParams = $_GET;
    $baseQueryParams['page_encours'] = $pageEncours;
    $baseQueryParams['page_archivees'] = $pageArchivees;
    $baseQueryParams['tab'] = $activeTab;

    $reservationBuildQuery = static function (array $overrides = []) use ($baseQueryParams): string {
        $params = array_merge($baseQueryParams, $overrides);

        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
            }
        }

        return http_build_query($params);
    };
} catch (Throwable $e) {
    echo "<h2 style='color:red; text-align:center;'>Erreur : " . htmlspecialchars($e->getMessage()) . '</h2>';
    error_log('reservation.php ERROR: ' . $e->getMessage());
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mes Réservations - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg">
<style>
body { font-family:'Roboto', sans-serif; background: linear-gradient(135deg, #a8edea, #fed6e3); margin:0; }
.container { max-width:900px; margin:20px auto; padding:20px; }
h1 { text-align:center; color:#00796b; font-family:'Great Vibes', cursive; font-size:2.5em; margin-bottom:30px; }

nav { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background:#00796b; color:white; flex-wrap:wrap; }
nav .logo { display:flex; align-items:center; gap:10px; font-weight:bold; font-size:1.5em; }
nav .logo img { height:40px; }
nav .actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
nav input[type="text"] { padding:6px 10px; border:none; border-radius:4px; width:200px; }
nav button { background:#004d40; border:none; color:white; padding:6px 12px; border-radius:4px; cursor:pointer; transition: all 0.3s; }
nav button:hover { background:#00332c; transform: translateY(-2px); }

.tabs { display:flex; justify-content:center; margin-bottom:20px; }
.tab { padding:10px 20px; border:none; cursor:pointer; background:#eee; margin:0 5px; border-radius:6px; }
.tab.active { background:#00796b; color:white; }
.tab-content { display:none; }
.tab-content.active { display:block; }

.list { list-style:none; padding:0; margin:0; }
.list li { display:flex; align-items:center; justify-content: space-between; padding:10px; border-bottom:1px solid #ddd; gap:15px; }
.list li img { width:60px; height:80px; object-fit:cover; border-radius:4px; }
.reservation-info { flex:1; display:flex; flex-direction:column; }
.reservation-actions { display:flex; align-items:center; gap:10px; }
.reservation-actions button { padding:6px 12px; background:#f57c00; border:none; border-radius:4px; color:white; cursor:pointer; }
.reservation-actions button:hover { background:#c76a05; }

.stars {
    display: flex;
    gap: 4px;
    cursor: pointer;
}
.star {
    font-size: 1.5em;
    color: #ccc; /* vide */
    transition: color 0.2s;
}
.star.hover,
.star.selected {
    color: gold; /* survolée ou validée */
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-top: 15px;
    flex-wrap: wrap;
}

.pagination .page-link {
    padding: 6px 12px;
    border-radius: 4px;
    background: #eee;
    color: #333;
    text-decoration: none;
    transition: background 0.3s, color 0.3s;
}

.pagination .page-link:hover {
    background: #d4d4d4;
}

.pagination .page-link.active {
    background: #00796b;
    color: #fff;
}

.pagination .page-link.disabled {
    pointer-events: none;
    opacity: 0.5;
    background: #ccc;
    color: #666;
}

</style>
</head>
<body>

<nav>
    <div class="logo">
        <img src="https://img.freepik.com/vecteurs-premium/lire-logo-du-livre_7888-13.jpg" alt="Logo BookShare">
        BookShare
    </div>
    <div class="actions">
        <form method="get" action="index.php" style="margin:0;">
            <input type="text" name="q" placeholder="Rechercher un livre...">
        </form>
        <button onclick="window.location.href='index.php'">Accueil</button>
        <?php if ($utilisateur): ?>
            <?php if ($utilisateur->estAdmin()): ?>
                <button onclick="window.location.href='admin.php'">Panneau Admin</button>
            <?php endif; ?>
            <button onclick="window.location.href='reservation.php'">Mes Reservations</button>
            <button onclick="window.location.href='deconnexion.php'">Deconnexion (<?= htmlspecialchars($utilisateur->getPseudo()) ?>)</button>
        <?php else: ?>
            <button onclick="window.location.href='connexion.php'">Connexion</button>
            <button onclick="window.location.href='inscription.php'">Creer un compte</button>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <h1>Mes réservations</h1>

    <?php if (!empty($_GET['message'])): ?>
        <p style="color: green; text-align:center;"><?= htmlspecialchars($_GET['message']) ?></p>
    <?php endif; ?>

    <div class="tabs">
        <button class="tab<?= $activeTab === 'encours' ? ' active' : '' ?>" data-target="encours">En cours</button>
        <button class="tab<?= $activeTab === 'archivees' ? ' active' : '' ?>" data-target="archivees">Archivées</button>
    </div>

    <div id="encours" class="tab-content<?= $activeTab === 'encours' ? ' active' : '' ?>">
        <?php if (empty($reservationsEnCours)): ?>
            <p>Aucune réservation en cours</p>
        <?php else: ?>
            <ul class="list">
                <?php foreach ($reservationsEnCours as $reservation): ?>
                    <li>
                        <img src="<?= htmlspecialchars($reservation->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Couverture">
                        <div class="reservation-info">
                            <strong><?= htmlspecialchars($reservation->getTitre()) ?></strong>
                            <span>Auteur : <?= htmlspecialchars($reservation->getAuteur()) ?></span>
                            <span>Reserve le : <?= htmlspecialchars($reservation->getDateReservation() ?? '') ?></span>
                        </div>
                        <form method="post" class="reservation-actions">
                            <input type="hidden" name="reservation_id" value="<?= (int) $reservation->getId() ?>">
                            <input type="hidden" name="page_encours" value="<?= $pageEncours ?>">
                            <input type="hidden" name="page_archivees" value="<?= $pageArchivees ?>">
                            <input type="hidden" name="tab" value="encours">
                            <button type="submit" name="annuler">Annuler</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($totalPagesEncours > 1): ?>
                <div class="pagination">
                    <?php if ($pageEncours > 1): ?>
                        <?php $query = $reservationBuildQuery([
                            'page_encours' => $pageEncours - 1,
                            'tab' => 'encours',
                        ]); ?>
                        <a class="page-link" href="reservation.php?<?= htmlspecialchars($query) ?>">Précédent</a>
                    <?php else: ?>
                        <span class="page-link disabled">Précédent</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPagesEncours; $i++): ?>
                        <?php $query = $reservationBuildQuery([
                            'page_encours' => $i,
                            'tab' => 'encours',
                        ]); ?>
                        <a class="page-link<?= $i === $pageEncours ? ' active' : '' ?>" href="reservation.php?<?= htmlspecialchars($query) ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($pageEncours < $totalPagesEncours): ?>
                        <?php $query = $reservationBuildQuery([
                            'page_encours' => $pageEncours + 1,
                            'tab' => 'encours',
                        ]); ?>
                        <a class="page-link" href="reservation.php?<?= htmlspecialchars($query) ?>">Suivant</a>
                    <?php else: ?>
                        <span class="page-link disabled">Suivant</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div id="archivees" class="tab-content<?= $activeTab === 'archivees' ? ' active' : '' ?>">
    <?php if (empty($reservationsArchivees)): ?>
        <p>Aucune réservation archivée.</p>
    <?php else: ?>
        <ul class="list">
            <?php foreach ($reservationsArchivees as $reservation): ?>
                <li>
                    <img src="<?= htmlspecialchars($reservation->getImageUrl() ?: 'images/livre-defaut.jpg') ?>" alt="Couverture">
                    <div class="reservation-info">
                        <strong><?= htmlspecialchars($reservation->getTitre()) ?></strong>
                        <span>Auteur : <?= htmlspecialchars($reservation->getAuteur()) ?></span>
                        <span>Réservé le : <?= htmlspecialchars($reservation->getDateReservation() ?? '') ?></span>
                    </div>

                    <!-- Bloc étoiles -->
                    <div class="stars" 
                         data-livre="<?= (int)$reservation->getLivreId() ?>" 
                         data-user="<?= (int)$utilisateur->getId() ?>"
                         data-note="<?= (int)$reservation->getNote() ?>">
                        <?php 
                        $note = (int) $reservation->getNote();
                        for ($i = 1; $i <= 5; $i++): 
                            $class = $i <= $note ? "star selected" : "star";
                        ?>
                            <span class="<?= $class ?>" data-value="<?= $i ?>">★</span>
                        <?php endfor; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($totalPagesArchivees > 1): ?>
            <div class="pagination">
                <?php if ($pageArchivees > 1): ?>
                    <?php $query = $reservationBuildQuery([
                        'page_archivees' => $pageArchivees - 1,
                        'tab' => 'archivees',
                    ]); ?>
                    <a class="page-link" href="reservation.php?<?= htmlspecialchars($query) ?>">Précédent</a>
                <?php else: ?>
                    <span class="page-link disabled">Précédent</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPagesArchivees; $i++): ?>
                    <?php $query = $reservationBuildQuery([
                        'page_archivees' => $i,
                        'tab' => 'archivees',
                    ]); ?>
                    <a class="page-link<?= $i === $pageArchivees ? ' active' : '' ?>" href="reservation.php?<?= htmlspecialchars($query) ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($pageArchivees < $totalPagesArchivees): ?>
                    <?php $query = $reservationBuildQuery([
                        'page_archivees' => $pageArchivees + 1,
                        'tab' => 'archivees',
                    ]); ?>
                    <a class="page-link" href="reservation.php?<?= htmlspecialchars($query) ?>">Suivant</a>
                <?php else: ?>
                    <span class="page-link disabled">Suivant</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const tabs = document.querySelectorAll('.tab');
const contents = document.querySelectorAll('.tab-content');

const setActiveTab = targetId => {
    tabs.forEach(tab => {
        const isActive = tab.dataset.target === targetId;
        tab.classList.toggle('active', isActive);
    });

    contents.forEach(content => {
        const isActive = content.id === targetId;
        content.classList.toggle('active', isActive);
    });
};

const updateTabParam = targetId => {
    const url = new URL(window.location);
    url.searchParams.set('tab', targetId);
    window.history.replaceState({}, '', url);
};

const resolveInitialTab = () => {
    const params = new URLSearchParams(window.location.search);
    const explicit = params.get('tab');
    if (explicit === 'encours' || explicit === 'archivees') {
        return explicit;
    }

    const pageEncours = parseInt(params.get('page_encours') || '1', 10);
    const pageArchivees = parseInt(params.get('page_archivees') || '1', 10);

    if (params.has('page_archivees') && (!params.has('page_encours') || pageArchivees > 1)) {
        return 'archivees';
    }

    if (!Number.isNaN(pageArchivees) && pageArchivees > Math.max(pageEncours, 1)) {
        return 'archivees';
    }

    return 'encours';
};

const initialTab = resolveInitialTab();
setActiveTab(initialTab);

const currentUrl = new URL(window.location);
if (currentUrl.searchParams.get('tab') !== initialTab) {
    currentUrl.searchParams.set('tab', initialTab);
    window.history.replaceState({}, '', currentUrl);
}

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        const target = tab.dataset.target;
        setActiveTab(target);
        updateTabParam(target);
    });
});

// Gestion des étoiles
document.querySelectorAll('.stars').forEach(starsContainer => {
    const stars = starsContainer.querySelectorAll('.star');
    const livreId = starsContainer.dataset.livre;
    const userId = starsContainer.dataset.user;
    let noteExistante = parseInt(starsContainer.dataset.note);

    // Si une note existe déjà → fige l'affichage
    if (noteExistante > 0) {
        stars.forEach(star => {
            if (parseInt(star.dataset.value) <= noteExistante) {
                star.classList.add('selected');
            }
        });
        return; // pas d'interaction possible
    }

    // Sinon interaction active
    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.dataset.value);
            stars.forEach(s => s.classList.toggle('hover', parseInt(s.dataset.value) <= val));
        });

        star.addEventListener('mouseleave', () => {
            stars.forEach(s => s.classList.remove('hover'));
        });

        star.addEventListener('click', () => {
            const note = parseInt(star.dataset.value);

            // Envoi vers PHP
            fetch('note_livre.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ livre_id: livreId, utilisateur_id: userId, note: note })
            })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    // fige l'affichage
                    stars.forEach(s => {
                        s.classList.remove('hover');
                        s.classList.toggle('selected', parseInt(s.dataset.value) <= note);
                    });
                } else {
                    alert("Erreur : " + data.message);
                }
            })
            .catch(err => console.error(err));
        });
    });
});

</script>
</body>
</html>
