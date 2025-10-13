# ðŸ“š BookShare

**BookShare** est une plateforme de partage de livres entre particuliers.  
Chaque utilisateur peut prÃªter, emprunter et noter des ouvrages, le tout via une interface moderne et intuitive.  
Le projet a Ã©tÃ© dÃ©veloppÃ© dans le cadre de lâ€™**Ã©valuation ECF â€“ DÃ©veloppeur Web & Web Mobile**.

---

## ðŸš€ DÃ©monstration

ðŸ”— **Application en ligne :** [https://bookshare-655b6c07c913.herokuapp.com](https://bookshare-655b6c07c913.herokuapp.com)

---

## ðŸ§­ Sommaire

1. [ðŸŽ¯ Objectifs du projet](#-objectifs-du-projet)  
2. [ðŸ’» Installation locale](#-installation-locale)  
3. [ðŸ§© FonctionnalitÃ©s principales](#-fonctionnalitÃ©s-principales)  
4. [ðŸ› ï¸ Technologies utilisÃ©es](#ï¸-technologies-utilisÃ©es)  
5. [ðŸ—ï¸ Architecture du projet](#ï¸-architecture-du-projet)  
6. [ðŸ—ƒï¸ Base de donnÃ©es](#ï¸-base-de-donnÃ©es)  
7. [ðŸ” SÃ©curitÃ©](#-sÃ©curitÃ©)  
8. [ðŸ“¬ SystÃ¨me dâ€™e-mails](#-systÃ¨me-de-mails)  
9. [â˜ï¸ DÃ©ploiement Heroku](#ï¸-dÃ©ploiement-heroku)  
10. [ðŸ“¸ Captures dâ€™Ã©cran](#-captures-dÃ©cran)  
11. [ðŸ‘¤ Auteur](#-auteur)

---

## ðŸŽ¯ Objectifs du projet

- DÃ©velopper une **plateforme communautaire de partage de livres**.  
- GÃ©rer des **inscriptions sÃ©curisÃ©es** avec validation par e-mail.  
- Offrir une **interface intuitive** pour la recherche et la rÃ©servation de livres.  
- CrÃ©er un **espace administrateur** pour la modÃ©ration du contenu.  
- IntÃ©grer **MySQL** (structure principale) et **MongoDB** (suivi des logs).

---

## ðŸ’» Installation locale

### ðŸ‹ Option 1 : via Docker (recommandÃ©e)

#### 1ï¸âƒ£ Cloner le dÃ©pÃ´t

```bash
git clone https://github.com/alissonpinault/BookShare.git
cd BookShare

2ï¸âƒ£ Lancer les conteneurs
docker-compose up -d


Cela crÃ©e :

un conteneur PHP/Apache pour le site : http://localhost:8080

un conteneur MySQL : localhost:3306

un conteneur phpMyAdmin : http://localhost:8084

un conteneur MongoDB pour les logs utilisateurs

3ï¸âƒ£ Importer la base MySQL

Depuis phpMyAdmin :

CrÃ©e une base bookshare

Importe le fichier /sql/bookshare.sql fourni.

4ï¸âƒ£ Configurer le fichier db.php
$pdo = new PDO('mysql:host=mysql;dbname=bookshare;charset=utf8', 'user', 'password');
$mongoClient = new MongoDB\Client("mongodb://mongo:27017");
$mongoDB = $mongoClient->bookshare;

5ï¸âƒ£ AccÃ©der Ã  lâ€™application

ðŸ‘‰ http://localhost:8080

ðŸ–¥ï¸ Option 2 : via XAMPP (ou WAMP)

1ï¸âƒ£ Copier le projet dans :

C:\xampp\htdocs\BookShare


2ï¸âƒ£ DÃ©marrer Apache et MySQL depuis le panneau XAMPP.
3ï¸âƒ£ CrÃ©er une base bookshare dans phpMyAdmin.
4ï¸âƒ£ Importer le fichier /sql/bookshare.sql.
5ï¸âƒ£ VÃ©rifier le fichier db.php :

$pdo = new PDO('mysql:host=localhost;dbname=bookshare;charset=utf8', 'root', '');


6ï¸âƒ£ Ouvrir le site dans le navigateur :
ðŸ‘‰ http://localhost/BookShare

ðŸ§© FonctionnalitÃ©s principales
ðŸ‘¥ Utilisateurs

Inscription avec vÃ©rification par e-mail

Connexion sÃ©curisÃ©e (mots de passe hachÃ©s)

RÃ©initialisation du mot de passe

Modification du profil

ðŸ“š Livres

Ajout, Ã©dition, suppression

Recherche et filtres dynamiques

RÃ©servation et notation (1 Ã  5 Ã©toiles)

ðŸ›¡ï¸ Administration

Validation manuelle des inscriptions

ModÃ©ration des avis et signalements

Visualisation des logs depuis MongoDB

ðŸ› ï¸ Technologies utilisÃ©es
Type	Technologies
Front-end	HTML5, CSS3, Tailwind CSS, Font Awesome
Back-end	PHP 8 (POO), PDO, PHPMailer
Base SQL	MySQL
Base NoSQL	MongoDB
HÃ©bergement	Heroku
Versioning	Git / GitHub
Outils	VS Code, phpMyAdmin, Composer
ðŸ—ï¸ Architecture du projet
BookShare/
â”œâ”€â”€ php/                     â†’ Classes PHP (POO)
â”‚   â”œâ”€â”€ UtilisateurPOO.php
â”‚   â”œâ”€â”€ LivrePOO.php
â”‚   â””â”€â”€ ReservationPOO.php
â”œâ”€â”€ utiles/                  â†’ Scripts utilitaires
â”‚   â”œâ”€â”€ db.php
â”‚   â”œâ”€â”€ header.php / footer.php
â”‚   â””â”€â”€ style.css
â”œâ”€â”€ public/assets/images/    â†’ Logos et icÃ´nes
â”œâ”€â”€ auth.css                 â†’ Feuille de style pour les pages dâ€™authentification
â”œâ”€â”€ inscription.php          â†’ Inscription avec e-mail de validation
â”œâ”€â”€ connexion.php            â†’ Connexion utilisateur
â”œâ”€â”€ mdp_oublie.php           â†’ RÃ©initialisation du mot de passe
â”œâ”€â”€ valider.php              â†’ Validation de compte via token
â”œâ”€â”€ index.php                â†’ Page dâ€™accueil
â”œâ”€â”€ admin.php                â†’ Interface dâ€™administration
â”œâ”€â”€ composer.json            â†’ DÃ©pendances (PHPMailer, MongoDB, etc.)
â””â”€â”€ README.md                â†’ Documentation du projet

ðŸ—ƒï¸ Base de donnÃ©es
ðŸ’¾ MySQL (principale)

utilisateurs : identifiants, rÃ´les, tokens, statut

livres : informations sur les ouvrages

reservations : historique des prÃªts/emprunts

ðŸ“Š MongoDB (logs)

logs_connexion : trace les connexions, inscriptions, rÃ©initialisations

ðŸ” SÃ©curitÃ©

Hachage des mots de passe avec password_hash()

Validation serveur + protection CSRF (via tokens)

VÃ©rification des rÃ´les (admin/utilisateur)

Logs dâ€™activitÃ© enregistrÃ©s dans MongoDB

RequÃªtes SQL sÃ©curisÃ©es via PDO prÃ©parÃ©

ðŸ“¬ SystÃ¨me de mails

ðŸ“¤ Envoi via Mailgun SMTP (configurÃ© sur Heroku).
ðŸ“¦ Gestion avec PHPMailer.
ðŸ“Ž E-mails HTML stylÃ©s avec logo intÃ©grÃ©.

Cas dâ€™usage :

Validation du compte aprÃ¨s inscription

RÃ©initialisation du mot de passe (lien unique)

â˜ï¸ DÃ©ploiement Heroku
ðŸ”§ Ã‰tapes de dÃ©ploiement

DÃ©pÃ´t GitHub connectÃ© Ã  Heroku

Add-ons :

JawsDB MySQL

Mailgun

Variables dâ€™environnement :

MAILGUN_SMTP_LOGIN

MAILGUN_SMTP_PASSWORD

MAILGUN_SMTP_SERVER

MAILGUN_SMTP_PORT

DÃ©ploiement :

git push heroku main

ðŸ“¸ Captures dâ€™Ã©cran

(Ã  insÃ©rer plus tard)

Page dâ€™accueil

Formulaire dâ€™inscription

E-mail de validation

Espace administrateur

ðŸ‘¤ Auteur

ðŸ‘©â€ðŸ’» Alisson Pinault
DÃ©veloppeuse Web & Web Mobile
ðŸ“ France
ðŸ“§ pinault.alisson@gmail.com

ðŸ”— GitHub â€“ alissonpinault

ðŸ§¾ Licence

Projet rÃ©alisÃ© dans le cadre dâ€™un examen.
Â© 2025 â€” BookShare â€“ Tous droits rÃ©servÃ©s.
