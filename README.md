# ðŸ“š BookShare

**BookShare** est une plateforme de partage de livres entre particuliers.  
Chaque utilisateur peut prêter, emprunter et noter des ouvrages, le tout via une interface moderne et intuitive.  
Le projet a été développé dans le cadre de l'**évaluation ECF â€“ Développeur Web & Web Mobile**.

---

## ðŸš€ Démonstration

ðŸ”— **Application en ligne :** [https://bookshare-655b6c07c913.herokuapp.com](https://bookshare-655b6c07c913.herokuapp.com)

---

## ðŸ§­ Sommaire

1. [ðŸŽ¯ Objectifs du projet](#-objectifs-du-projet)  
2. [ðŸ’» Installation locale](#-installation-locale)  
3. [ðŸ§© Fonctionnalités principales](#-fonctionnalités-principales)  
4. [ðŸ› ï¸ Technologies utilisées](#ï¸-technologies-utilisées)  
5. [ðŸ—ï¸ Architecture du projet](#ï¸-architecture-du-projet)  
6. [ðŸ—ƒï¸ Base de données](#ï¸-base-de-données)  
7. [ðŸ” Sécurité](#-sécurité)  
8. [ðŸ“¬ Système d'e-mails](#-système-de-mails)  
9. [â˜ï¸ Déploiement Heroku](#ï¸-déploiement-heroku)  
10. [ðŸ“¸ Captures d'écran](#-captures-décran)  
11. [ðŸ‘¤ Auteur](#-auteur)

---

## ðŸŽ¯ Objectifs du projet

- Développer une **plateforme communautaire de partage de livres**.  
- Gérer des **inscriptions sécurisées** avec validation par e-mail.  
- Offrir une **interface intuitive** pour la recherche et la réservation de livres.  
- Créer un **espace administrateur** pour la modération du contenu.  
- Intégrer **MySQL** (structure principale) et **MongoDB** (suivi des logs).

---

## ðŸ’» Installation locale

### ðŸ‹ Option 1 : via Docker (recommandée)

#### 1ï¸âƒ£ Cloner le dépôt

```bash
git clone https://github.com/alissonpinault/BookShare.git
cd BookShare

2ï¸âƒ£ Lancer les conteneurs
docker-compose up -d


Cela crée :

un conteneur PHP/Apache pour le site : http://localhost:8080

un conteneur MySQL : localhost:3306

un conteneur phpMyAdmin : http://localhost:8084

un conteneur MongoDB pour les logs utilisateurs

3ï¸âƒ£ Importer la base MySQL

Depuis phpMyAdmin :

Crée une base bookshare

Importe le fichier /sql/bookshare.sql fourni.

4ï¸âƒ£ Configurer le fichier db.php
$pdo = new PDO('mysql:host=mysql;dbname=bookshare;charset=utf8', 'user', 'password');
$mongoClient = new MongoDB\Client("mongodb://mongo:27017");
$mongoDB = $mongoClient->bookshare;

5ï¸âƒ£ Accéder à l'application

ðŸ‘‰ http://localhost:8080

ðŸ–¥ï¸ Option 2 : via XAMPP (ou WAMP)

1ï¸âƒ£ Copier le projet dans :

C:\xampp\htdocs\BookShare


2ï¸âƒ£ Démarrer Apache et MySQL depuis le panneau XAMPP.
3ï¸âƒ£ Créer une base bookshare dans phpMyAdmin.
4ï¸âƒ£ Importer le fichier /sql/bookshare.sql.
5ï¸âƒ£ Vérifier le fichier db.php :

$pdo = new PDO('mysql:host=localhost;dbname=bookshare;charset=utf8', 'root', '');


6ï¸âƒ£ Ouvrir le site dans le navigateur :
ðŸ‘‰ http://localhost/BookShare

ðŸ§© Fonctionnalités principales
ðŸ‘¥ Utilisateurs

Inscription avec vérification par e-mail

Connexion sécurisée (mots de passe hachés)

Réinitialisation du mot de passe

Modification du profil

ðŸ“š Livres

Ajout, édition, suppression

Recherche et filtres dynamiques

Réservation et notation (1 à 5 étoiles)

ðŸ›¡ï¸ Administration

Validation manuelle des inscriptions

Modération des avis et signalements

Visualisation des logs depuis MongoDB

ðŸ› ï¸ Technologies utilisées
Type	Technologies
Front-end	HTML5, CSS3, Tailwind CSS, Font Awesome
Back-end	PHP 8 (POO), PDO, PHPMailer
Base SQL	MySQL
Base NoSQL	MongoDB
Hébergement	Heroku
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
â”œâ”€â”€ public/assets/images/    â†’ Logos et icônes
â”œâ”€â”€ auth.css                 â†’ Feuille de style pour les pages d'authentification
â”œâ”€â”€ inscription.php          â†’ Inscription avec e-mail de validation
â”œâ”€â”€ connexion.php            â†’ Connexion utilisateur
â”œâ”€â”€ mdp_oublie.php           â†’ Réinitialisation du mot de passe
â”œâ”€â”€ valider.php              â†’ Validation de compte via token
â”œâ”€â”€ index.php                â†’ Page d'accueil
â”œâ”€â”€ admin.php                â†’ Interface d'administration
â”œâ”€â”€ composer.json            â†’ Dépendances (PHPMailer, MongoDB, etc.)
â””â”€â”€ README.md                â†’ Documentation du projet

ðŸ—ƒï¸ Base de données
ðŸ’¾ MySQL (principale)

utilisateurs : identifiants, rôles, tokens, statut

livres : informations sur les ouvrages

reservations : historique des prêts/emprunts

ðŸ“Š MongoDB (logs)

logs_connexion : trace les connexions, inscriptions, réinitialisations

ðŸ” Sécurité

Hachage des mots de passe avec password_hash()

Validation serveur + protection CSRF (via tokens)

Vérification des rôles (admin/utilisateur)

Logs d'activité enregistrés dans MongoDB

Requêtes SQL sécurisées via PDO préparé

ðŸ“¬ Système de mails

ðŸ“¤ Envoi via Mailgun SMTP (configuré sur Heroku).
ðŸ“¦ Gestion avec PHPMailer.
ðŸ“Ž E-mails HTML stylés avec logo intégré.

Cas d'usage :

Validation du compte après inscription

Réinitialisation du mot de passe (lien unique)

â˜ï¸ Déploiement Heroku
ðŸ”§ Ã‰tapes de déploiement

Dépôt GitHub connecté à Heroku

Add-ons :

JawsDB MySQL

Mailgun

Variables d'environnement :

MAILGUN_SMTP_LOGIN

MAILGUN_SMTP_PASSWORD

MAILGUN_SMTP_SERVER

MAILGUN_SMTP_PORT

Déploiement :

git push heroku main

ðŸ“¸ Captures d'écran

(à insérer plus tard)

Page d'accueil

Formulaire d'inscription

E-mail de validation

Espace administrateur

ðŸ‘¤ Auteur

ðŸ‘©â€ðŸ’» Alisson Pinault
Développeuse Web & Web Mobile
ðŸ“ France
ðŸ“§ pinault.alisson@gmail.com

ðŸ”— GitHub â€“ alissonpinault

ðŸ§¾ Licence

Projet réalisé dans le cadre d'un examen.
Â© 2025 â€” BookShare â€“ Tous droits réservés.
