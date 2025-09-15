-- -----------------------------
-- Base de données Bookshare
-- -----------------------------
CREATE DATABASE IF NOT EXISTS bookshare;
USE bookshare;

-- -----------------------------
-- Table utilisateurs
-- -----------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    utilisateur_id INT AUTO_INCREMENT PRIMARY KEY,
    pseudo VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Exemple d'utilisateurs
INSERT INTO utilisateurs (pseudo, email, mot_de_passe) VALUES
('alice', 'alice@example.com', 'password1'),
('bob', 'bob@example.com', 'password2');

-- -----------------------------
-- Table livres
-- -----------------------------
CREATE TABLE IF NOT EXISTS livres (
    livre_id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(255) NOT NULL,
    annee_publication YEAR,
    genre VARCHAR(100),
    disponibilite BOOLEAN DEFAULT TRUE
);

-- Exemple de livres
INSERT INTO livres (titre, auteur, annee_publication, genre) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupéry', 1943, 'Conte'),
('1984', 'George Orwell', 1949, 'Dystopie'),
('Harry Potter à l\'école des sorciers', 'J.K. Rowling', 1997, 'Fantastique');

-- -----------------------------
-- Table reservations
-- -----------------------------
CREATE TABLE IF NOT EXISTS reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    date_reservation DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en cours','terminee','annulee') DEFAULT 'en cours',
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(utilisateur_id),
    FOREIGN KEY (livre_id) REFERENCES livres(livre_id)
);

-- Exemple de réservations
INSERT INTO reservations (utilisateur_id, livre_id, statut) VALUES
(1, 2, 'en cours'),
(2, 1, 'terminee');
