<?php
declare(strict_types=1);

use Bookshare\Models\Utilisateur;

$services = require dirname(__DIR__) . '/src/bootstrap.php';
$pdo = $services['pdo'];

session_start();

// Session utilisateur → objet Utilisateur
$utilisateur = null;
if (isset($_SESSION['utilisateur_id'])) {
    $utilisateur = new Utilisateur(
        $_SESSION['utilisateur_id'],
        $_SESSION['pseudo'] ?? '',
        $_SESSION['email'] ?? '',
        $_SESSION['role'] ?? 'user'
    );
}

// Récupération des listes de genres et d'auteurs (pour la nav comme sur index.php)
$genresStmt = $pdo->query('SELECT DISTINCT genre FROM livres WHERE genre IS NOT NULL AND genre <> "" ORDER BY genre');
$genres = $genresStmt ? $genresStmt->fetchAll(PDO::FETCH_COLUMN) : [];

$auteursStmt = $pdo->query('SELECT DISTINCT auteur FROM livres WHERE auteur IS NOT NULL AND auteur <> "" ORDER BY auteur');
$auteurs = $auteursStmt ? $auteursStmt->fetchAll(PDO::FETCH_COLUMN) : [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Mentions légales - BookShare</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="icon" type="image/jpg" href="assets/images/logo.jpg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include dirname(__DIR__) . '/templates/partials/nav.php'; ?>

<main class="layout" 
      style="padding:2rem; max-width:900px; margin:0 auto; box-sizing:border-box;
             display:block; text-align:left;">

    <style>
        /* Force les titres à gauche */
        main h1, 
        main h2 {
            text-align: left !important;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }

        /* Supprime tout comportement flex hérité */
        main section {
            display: block !important;
        }
    </style>

    <h1>Mentions légales</h1>

    <section>
        <h2>Éditeur du site</h2>
        <p>Site édité par <strong>BookShare</strong>.</p>
        <p>Siège social : 123 Rue de la Bibliothèque, 75000 Paris, France</p>
        <p>Contact : <a href="mailto:contact@bookshare.com">contact@bookshare.com</a></p>
        <p>Directeur de la publication : l'équipe BookShare</p>
    </section>

    <section>
        <h2>Hébergement</h2>
        <p>Site hébergé par <strong>HEROKU</strong>.</p>
    </section>

    <hr style="margin:3rem 0;">

    <h1>Politique de confidentialité</h1>

    <section>
        <h2>1. Données collectées</h2>
        <p>
            Nous collectons les données nécessaires au fonctionnement du service : identifiants de compte 
            (pseudo, email), données de réservations et préférences. Aucune donnée sensible n’est collectée 
            sans consentement explicite.
        </p>

        <h2>2. Finalités</h2>
        <p>
            Les données sont utilisées pour : gestion des comptes, traitement des réservations, notifications 
            par email et amélioration du service.
        </p>

        <h2>3. Conservation</h2>
        <p>
            Les données sont conservées aussi longtemps que nécessaire pour fournir le service ou selon les 
            obligations légales applicables.
        </p>

        <h2>4. Droits des utilisateurs</h2>
        <p>
            Vous pouvez demander l'accès, la rectification ou la suppression de vos données en contactant 
            <a href="mailto:contact@bookshare.example">contact@bookshare.example</a>.
        </p>

        <h2>5. Cookies</h2>
        <p>
            Le site utilise des cookies techniques pour la session et des cookies optionnels pour améliorer 
            l'expérience.
        </p>
    </section>

    <hr style="margin:3rem 0;">

    <h1>Conditions d'utilisation</h1>

    <section>
        <h2>1. Objet</h2>
        <p>
            Les présentes conditions régissent l’accès et l’utilisation du site BookShare.
        </p>

        <h2>2. Compte utilisateur</h2>
        <p>
            Chaque utilisateur est responsable de la confidentialité de ses identifiants.
        </p>

        <h2>3. Contenu</h2>
        <p>
            Les informations publiées doivent respecter la loi et ne pas porter atteinte aux tiers.
        </p>

        <h2>4. Responsabilité</h2>
        <p>
            BookShare met en œuvre des moyens raisonnables pour assurer la disponibilité du service.
        </p>

        <h2>5. Modification des conditions</h2>
        <p>
            Nous pouvons modifier ces conditions. Les changements seront appliqués dès publication.
        </p>

        <h2>6. Droit applicable</h2>
        <p>
            Les présentes conditions sont soumises au droit français.
        </p>
    </section>

</main>

<?php
require_once dirname(__DIR__) . '/templates/partials/footer.php';
renderFooter([
    'baseUrl' => 'mentions_legales.php',
    'pagination' => null,
]);
?>

</body>
</html>