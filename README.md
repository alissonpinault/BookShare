📚 BookShare

BookShare est une application web de gestion et de partage de livres, permettant aux utilisateurs de réserver des ouvrages, de gérer leurs emprunts et de donner leur avis.
Le projet a été conçu dans le cadre d’un exercice de développement web full-stack, en combinant PHP, MySQL, MongoDB, Docker et TailwindCSS.

🚀 Fonctionnalités principales

Authentification sécurisée

Création de compte, connexion/déconnexion

Gestion des rôles (utilisateur / admin)

Gestion des livres

Consultation des livres disponibles avec titre, auteur, image

Filtrage et recherche dynamique

Gestion des réservations

Réserver un livre disponible

Affichage des réservations en cours et archivées (onglets dynamiques)

Annulation d’une réservation en cours

Archivage automatique des réservations terminées

Espace utilisateur

Informations personnelles

Gestion de ses réservations

Attribution de notes aux ouvrages

Administration (rôle admin)

Consultation et gestion des utilisateurs

Suivi des réservations

Validation des contenus

🛠️ Technologies utilisées
Front-End

HTML5 / CSS3 / JavaScript

TailwindCSS (design responsive et moderne)

Font Awesome (icônes)

Back-End

PHP 8 (POO)

MySQL (stockage des données principales : utilisateurs, livres, réservations)

MongoDB (stockage complémentaire : avis, logs, interactions dynamiques)

PDO (requêtes SQL sécurisées avec requêtes préparées)

Environnement & outils

Docker (conteneurs pour Apache/PHP, MySQL, MongoDB)

phpMyAdmin (administration de MySQL)

Composer (gestion des dépendances PHP)

Git/GitHub (gestion de version)

⚙️ Installation
1. Cloner le projet
git clone https://github.com/votre-utilisateur/bookshare.git
cd bookshare

2. Lancer Docker

Assurez-vous que Docker est installé puis exécutez :

docker-compose up -d


Cela démarre les services suivants :

Apache/PHP → http://localhost:8080

MySQL → port 3306

MongoDB → port 27017

phpMyAdmin → http://localhost:8081

3. Base de données

Importez le fichier bookshare.sql dans MySQL via phpMyAdmin.

Vérifiez la configuration de la connexion dans db.php.

4. Accéder à l’application

Ouvrez http://localhost:8080
 dans votre navigateur.

📂 Structure du projet
bookshare/
│── classes/            # Classes PHP (POO : Utilisateur, Livre, Reservation…)
│── php/             # Fichiers accessibles (index.php, reservation.php, etc.)
│── docker-compose.yml  # Configuration Docker
│── db.php              # Connexion base MySQL
│── README.md           # Documentation projet
│── images/             # Images, logos, CSS custom

👤 Comptes de test

Utilisateur standard

Email : user@bookshare.fr

Mot de passe : user123

Administrateur

Email : admin@bookshare.fr

Mot de passe : admin123

📸 Captures d’écran

Des captures d’écran (front + back) sont disponibles en annexe :

Page d’accueil avec liste des livres

Formulaire de connexion

Espace utilisateur avec onglets réservations

phpMyAdmin (tables MySQL)

Docker (conteneurs en cours d’exécution)

✨ Objectif pédagogique

BookShare a été développé pour :

Mettre en pratique les concepts front-end (HTML/CSS/JS) et back-end (PHP/MySQL/MongoDB).

Appliquer une architecture orientée objet (POO) côté PHP.

Expérimenter le déploiement en conteneurs Docker.

Illustrer une approche full-stack dans un projet concret.