-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : mysql:3306
-- Généré le : lun. 15 sep. 2025 à 14:44
-- Version du serveur : 8.0.43
-- Version de PHP : 8.2.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bookshare`
--

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE `livres` (
  `livre_id` int NOT NULL,
  `image_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `auteur` varchar(255) NOT NULL,
  `annee_publication` year DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `description` text,
  `disponibilite` enum('disponible','indisponible') DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`livre_id`, `image_url`, `titre`, `auteur`, `annee_publication`, `genre`, `description`, `disponibilite`) VALUES
(1, 'https://cdn.shopify.com/s/files/1/0398/4202/1535/products/Le-Petit-Prince_Rounded.png?v=1669032038', 'Le Petit Prince', 'Antoine de Saint-Exupéry', '1943', 'Conte', 'Le premier soir, je me suis donc endormi sur le sable à mille milles de toute terre habitée. J\'étais bien plus isolé qu\'un naufragé sur un radeau au milieu de l\'océan. Alors, vous imaginez ma surprise, au lever du jour, quand une drôle de petite voix m\'a réveillé. Elle disait : \"S\'il vous plaît...dessine-moi un mouton !\" J\'ai bien regardé. Et j\'ai vu ce petit bonhomme tout à fait extraordinaire qui me considérait gravement...\" La version originale du chef-d\'oeuvre de Saint-Exupéry, suivie d\'un cahier spécial pour aller à la rencontre de l\'auteur.', 'indisponible'),
(2, 'https://cdn.cultura.com/cdn-cgi/image/width=830/media/pim/9780141036144.jpg', '1984', 'George Orwell', '1949', 'Dystopie', 'De tous les carrefours importants, le visage à la moustache noire vous fixait du regard. BIG BROTHER VOUS REGARDE, répétait la légende, tandis que le regard des yeux noirs pénétrait les yeux de Winston... Au loin, un hélicoptère glissa entre les toits, plana un moment, telle une mouche bleue, puis repartit comme une flèche, dans un vol courbe. C\'était une patrouille qui venait mettre le nez aux fenêtres des gens. Mais les patrouilles n\'avaient pas d\'importance. Seule comptait la Police de la Pensée.', 'indisponible'),
(3, 'https://cdn.cultura.com/cdn-cgi/image/width=830/media/pim/TITELIVE/74_9782070584628_1_75.jpg', 'Harry Potter à  l\'école des sorciers', 'J.K. Rowling', '1997', 'Fantastique', 'Harry Potter est orphelin. Il mène une vie bien monotone chez son oncle et sa tante et leur horrible fils. Le jour de ses onze ans, son existence bascule : un géant vient le chercher pour l\'emmener dans une école de sorciers où une place l\'attend depuis toujours. Quel mystère entoure sa naissance ? Et qui est l\'effroyable mage dont personne n\'aime prononcer le nom ? Harry intègre le collège Poudlard et s\'y plaît aussitôt. Voler à cheval sur des balais, jeter des sorts, devenir champion de Quidditch (une sorte de football pour sorciers), combattre les trolls : Harry Potter se révèle un sorcier vraiment doué. Il semble pourtant que tout le monde ne l\'apprécie pas...', 'disponible'),
(6, 'https://labourseauxlivres.fr/cdn/shop/files/wSC7b0rzf4PFYrda-21kCKc2jmCeDJZE-MINdzU_uIcjC9VyVpstTg-cover-large_55e0e130-1574-4a0b-a850-edf5585c4447.jpg?v=1753803713&width=600', 'Les Royaumes de Feu (Tome 1) - La Prophétie', 'Sutherland, Tui T.', NULL, 'Jeunesse', 'Une terrible guerre divise les royaumes du monde de Pyrrhia. Selon une mystérieuse prophétie, seuls cinq jeunes dragons nés lors de la Nuit-la-plus-Claire pourront mettre fin aux combats et apporter la paix. Mais les élus, Argil, Tsunami, Gloria, Comète et Sunny, rêvent de voler de leurs propres ailes plutôt que d\'accomplir leur destin...', 'indisponible');

-- --------------------------------------------------------

--
-- Structure de la table `notes`
--

CREATE TABLE `notes` (
  `note_id` int NOT NULL,
  `livre_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `note` tinyint NOT NULL,
  `date_note` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Déchargement des données de la table `notes`
--

INSERT INTO `notes` (`note_id`, `livre_id`, `utilisateur_id`, `note`, `date_note`) VALUES
(2, 6, 3, 3, '2025-08-28 19:27:38');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `livre_id` int NOT NULL,
  `date_reservation` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en cours','terminer') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'en cours'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `utilisateur_id`, `livre_id`, `date_reservation`, `statut`) VALUES
(1, 1, 2, '2025-08-27 15:16:50', 'terminer'),
(2, 2, 1, '2025-08-27 15:16:50', 'terminer'),
(3, 4, 3, '2025-08-28 12:37:40', 'terminer'),
(4, 3, 2, '2025-08-28 12:49:08', 'terminer'),
(5, 3, 6, '2025-08-28 13:58:32', 'terminer'),
(6, 3, 2, '2025-08-28 13:58:47', 'en cours'),
(7, 4, 6, '2025-08-28 17:25:20', 'en cours'),
(8, 3, 3, '2025-08-28 19:42:43', 'terminer'),
(9, 4, 1, '2025-08-29 13:24:07', 'en cours');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `utilisateur_id` int NOT NULL,
  `pseudo` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('utilisateur','admin') NOT NULL DEFAULT 'utilisateur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`utilisateur_id`, `pseudo`, `email`, `mot_de_passe`, `date_inscription`, `role`) VALUES
(1, 'alice', 'alice@example.com', 'password1', '2025-08-27 15:16:50', 'utilisateur'),
(2, 'bob', 'bob@example.com', 'password2', '2025-08-27 15:16:50', 'utilisateur'),
(3, 'Administrateur', 'admin@bookshare.com', '$2y$10$s2RJhY61smQUeeFciwcaJe8lFaZHV2Z7tzDTOAZE7JQzQn.ideCx2', '2025-08-27 17:56:04', 'admin'),
(4, 'Marie', 'marie@bookshare.com', '$2y$10$rk2Mdg79NfgEJXefungLcub1iQ5GOzFQYCW1GWtKQ.QQMT3gbueQC', '2025-08-28 12:34:30', 'utilisateur');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`livre_id`);

--
-- Index pour la table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`note_id`),
  ADD UNIQUE KEY `livre_id` (`livre_id`,`utilisateur_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `livre_id` (`livre_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`utilisateur_id`),
  ADD UNIQUE KEY `pseudo` (`pseudo`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `livre_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `notes`
--
ALTER TABLE `notes`
  MODIFY `note_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `utilisateur_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `notes`
--
ALTER TABLE `notes`
  ADD CONSTRAINT `notes_ibfk_1` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`),
  ADD CONSTRAINT `notes_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
