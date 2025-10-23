# 📚 BookShare

**BookShare** est une plateforme de partage de livres entre particuliers.  
Chaque utilisateur peut prêter, emprunter et noter des ouvrages, le tout via une interface moderne et intuitive.  
Le projet a été développé dans le cadre de l’**évaluation ECF – Développeur Web et Web Mobile**.

---

## 🚀 Démonstration

🔗 **Application en ligne :** [https://bookshare-655b6c07c913.herokuapp.com](https://bookshare-655b6c07c913.herokuapp.com)

---

## 🧭 Sommaire

1. [🎯 Objectifs du projet](#-objectifs-du-projet)  
2. [💻 Installation locale](#-installation-locale)  
3. [🧩 Fonctionnalités principales](#-fonctionnalités-principales)  
4. [🛠️ Technologies utilisées](#️-technologies-utilisées)  
5. [🏗️ Architecture du projet](#️-architecture-du-projet)  
6. [🗃️ Base de données](#️-base-de-données)  
7. [🔐 Sécurité](#-sécurité)  
8. [📬 Système d’e-mails](#-système-de-mails)  
9. [☁️ Déploiement Heroku](#️-déploiement-heroku)  
11. [👤 Auteur](#-auteur)

---

## 🎯 Objectifs du projet

- Développer une **plateforme communautaire de partage de livres**.  
- Gérer des **inscriptions sécurisées** avec validation par e-mail.  
- Offrir une **interface intuitive** pour la recherche et la réservation de livres.  
- Créer un **espace administrateur** pour la modération du contenu.  
- Intégrer **MySQL** (structure principale) et **MongoDB** (suivi des logs).

---

## 💻 Installation locale

### 🐋 Option 1 : via Docker (recommandée)

#### 1️⃣ Cloner le dépôt

```bash
git clone https://github.com/alissonpinault/BookShare.git
cd BookShare
2️⃣ Lancer les conteneurs
bash
Copier le code
docker-compose up -d
Cela crée :

un conteneur PHP/Apache pour le site : http://localhost:8080

un conteneur MySQL : localhost:3306

un conteneur phpMyAdmin : http://localhost:8084

un conteneur MongoDB pour les logs utilisateurs

3️⃣ Importer la base MySQL
Depuis phpMyAdmin :

Crée une base bookshare

Importe le fichier /sql/bookshare.sql fourni.

4️⃣ Configurer le fichier Database.php
php
Copier le code
$pdo = new PDO('mysql:host=mysql;dbname=bookshare;charset=utf8', 'user', 'password');
$mongoClient = new MongoDB\Client("mongodb://mongo:27017");
$mongoDB = $mongoClient->bookshare;
5️⃣ Accéder à l’application
👉 http://localhost:8080

🖥️ Option 2 : via XAMPP (ou WAMP)
1️⃣ Copier le projet dans :

makefile
Copier le code
C:\xampp\htdocs\BookShare
2️⃣ Démarrer Apache et MySQL depuis le panneau XAMPP.
3️⃣ Créer une base bookshare dans phpMyAdmin.
4️⃣ Importer le fichier /sql/bookshare.sql.
5️⃣ Vérifier le fichier Database.php :

php
Copier le code
$pdo = new PDO('mysql:host=localhost;dbname=bookshare;charset=utf8', 'root', '');
6️⃣ Ouvrir le site dans le navigateur :
👉 http://localhost/BookShare

🧩 Fonctionnalités principales
👥 Utilisateurs
Inscription avec vérification par e-mail

Connexion sécurisée (mots de passe hachés)

Réinitialisation du mot de passe

Modification du profil

📚 Livres
Ajout, édition, suppression

Recherche et filtres dynamiques

Réservation et notation (1 à 5 étoiles)

🛡️ Administration
Validation manuelle des réservations

Modération des inscriptions

Visualisation des logs depuis MongoDB

🛠️ Technologies utilisées
Type	Technologies
Front-end	HTML5, CSS3, Tailwind CSS, Font Awesome
Back-end	PHP 8 (POO), PDO, PHPMailer
Base SQL	MySQL
Base NoSQL	MongoDB
Hébergement	Heroku
Versioning	Git / GitHub
Outils	VS Code, phpMyAdmin, Composer

## Architecture du projet

```
BookShare/
|- public/
|  |- assets/
|  |  |- css/
|  |  |- images/
|  |  `- js/
|  |- admin.php
|  |- ajouter_livre.php
|  |- catalogue.php
|  |- connexion.php
|  |- deconnexion.php
|  |- index.php
|  |- inscription.php
|  |- livre.php
|  |- mdp_oublie.php
|  |- note_livre.php
|  |- reinitialiser_mdp.php
|  |- reservation.php
|  `- valider.php
|- src/
|  |- Config/
|  |  `- Database.php
|  |- Models/
|  |  |- Livre.php
|  |  |- Reservation.php
|  |  `- Utilisateur.php
|  |- Services/
|  |  |- Auth/
|  |  |  |- LoginService.php
|  |  |  `- LogoutService.php
|  |  `- Notes/
|  |     `- NoteService.php
|  `- bootstrap.php
|- templates/
|  `- partials/
|     |- footer.php
|     `- nav.php
|- sql/
|  |- bookshare.sql
|- composer.json
|- composer.lock
|- docker-compose.yml
|- dockerfile
|- Procfile
`- README.md
```

**Dossiers cles**
- `public/` : points d'entree web, assets et formulaires.
- `src/` : code applicatif (bootstrap, configuration, models et services).
- `templates/partials/` : fragments d'interface reutilisables.
- `sql/` : scripts d'initialisation et d'exemple pour la base.

**Services metier**
- `Bookshare\\Services\\Auth` : authentification centralisee (login/logout).
- `Bookshare\\Services\\Notes` : enregistrement des notes utilisateurs.

Base de données
💾 MySQL (principale)
utilisateurs : identifiants, rôles, tokens, statut

livres : informations sur les ouvrages

reservations : historique des prêts/emprunts

📊 MongoDB (logs)
logs_connexion : trace les connexions, inscriptions, réinitialisations

🔐 Sécurité
Hachage des mots de passe avec password_hash()

Validation serveur + protection CSRF (via tokens)

Vérification des rôles (admin/utilisateur)

Logs d’activité enregistrés dans MongoDB

Requêtes SQL sécurisées via PDO préparé

📬 Système de mails
📤 Envoi via Mailgun SMTP (configuré sur Heroku).
📦 Gestion avec PHPMailer.
📎 E-mails HTML stylés avec logo intégré.

Cas d’usage :

Validation du compte après inscription

Réinitialisation du mot de passe (lien unique)

☁️ Déploiement Heroku
🔧 Étapes de déploiement
Dépôt GitHub connecté à Heroku

Add-ons :

JawsDB MySQL

Mailgun

Variables d’environnement :

MAILGUN_SMTP_LOGIN

MAILGUN_SMTP_PASSWORD

MAILGUN_SMTP_SERVER

MAILGUN_SMTP_PORT

Déploiement :

bash
Copier le code
git push heroku main

👤 Auteur
👩‍💻 Alisson Pinault
Développeuse Web & Web Mobile
📍 France
📧 pinault.alisson@gmail.com
🔗 GitHub – alissonpinault

🧾 Licence
Projet réalisé dans le cadre d’un examen.
© 2025 — BookShare – Tous droits réservés.

