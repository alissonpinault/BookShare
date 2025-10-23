-- =============================================
-- SCRIPT D’INITIALISATION — BOOKSHARE (V2 PRO)
-- Base : bookshare
-- Contenu : création de la base, des tables,
--           insertion d'un admin + user test
--           et insertion d’un catalogue de livres.
-- =============================================

-- 1) Suppression et création de la base
DROP DATABASE IF EXISTS `bookshare`;
CREATE DATABASE `bookshare` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `bookshare`;

-- =============================================
-- 2) TABLE : utilisateurs
-- =============================================
CREATE TABLE `utilisateurs` (
  `utilisateur_id` INT NOT NULL AUTO_INCREMENT,
  `pseudo` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `mot_de_passe` VARCHAR(255) NOT NULL,
  `date_inscription` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `role` ENUM('utilisateur','admin') NOT NULL DEFAULT 'utilisateur',
  `est_valide` TINYINT(1) DEFAULT '0',
  `token_validation` VARCHAR(255) DEFAULT NULL,
  `token_reset` VARCHAR(255) DEFAULT NULL,
  `reset_expire` DATETIME DEFAULT NULL,
  PRIMARY KEY (`utilisateur_id`),
  UNIQUE KEY `unique_pseudo` (`pseudo`),
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3) TABLE : livres
-- =============================================
CREATE TABLE `livres` (
  `livre_id` INT NOT NULL AUTO_INCREMENT,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `auteur` VARCHAR(255) NOT NULL,
  `annee_publication` YEAR DEFAULT NULL,
  `genre` VARCHAR(100) DEFAULT NULL,
  `description` TEXT,
  `disponibilite` ENUM('disponible','indisponible') DEFAULT 'disponible',
  PRIMARY KEY (`livre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 4) TABLE : reservations
-- =============================================
CREATE TABLE `reservations` (
  `reservation_id` INT NOT NULL AUTO_INCREMENT,
  `utilisateur_id` INT NOT NULL,
  `livre_id` INT NOT NULL,
  `date_reservation` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `statut` ENUM('en_attente','validee','annulee','terminee') DEFAULT 'en_attente',
  PRIMARY KEY (`reservation_id`),
  KEY `idx_resa_utilisateur` (`utilisateur_id`),
  KEY `idx_resa_livre` (`livre_id`),
  CONSTRAINT `fk_reservation_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reservation_livre` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 5) TABLE : notes
-- =============================================
CREATE TABLE `notes` (
  `note_id` INT NOT NULL AUTO_INCREMENT,
  `livre_id` INT NOT NULL,
  `utilisateur_id` INT NOT NULL,
  `note` TINYINT NOT NULL,
  `date_note` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`note_id`),
  UNIQUE KEY `unique_note_user_livre` (`livre_id`,`utilisateur_id`),
  CONSTRAINT `fk_note_livre` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`livre_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_note_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`utilisateur_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 6) INSERT UTILISATEURS (admin + test)
-- Mots de passe hachés bcrypt
-- admin / admin123
-- test / test123
-- =============================================
INSERT INTO `utilisateurs` (`pseudo`, `email`, `mot_de_passe`, `role`, `est_valide`)
VALUES
('admin', 'admin@bookshare.fr', '$2b$12$9g1rCyv/iH74t2swV.3pG.cHz4WW4YkHn1egbB2Aiu2JyYOkYYyEG', 'admin', 1),
('test', 'test@bookshare.fr', '$2y$12$ftRCFJQZmUDfSEYd2fUa8ONn6iplzuWq3wPJ6z5/6xE.ohApOOT9e', 'utilisateur', 1);

-- =============================================
-- 7) INSERT LIVRES (catalogue de base)
-- =============================================
INSERT INTO `livres` (`image_url`, `titre`, `auteur`, `annee_publication`, `genre`, `description`, `disponibilite`) 
VALUES
('https://cdn.shopify.com/s/files/1/0398/4202/1535/products/Le-Petit-Prince_Rounded.png?v=1669032038', 'Le Petit Prince', 'Antoine de Saint-Exupéry', '1943', 'Conte', 'Le premier soir, je me suis donc endormi sur le sable à mille milles de toute terre habitée. J''étais bien plus isolé qu''un naufragé sur un radeau au milieu de l''océan. Alors, vous imaginez ma surprise, au lever du jour, quand une drôle de petite voix m''a réveillé. Elle disait : "S''il vous plaît...dessine-moi un mouton !" J''ai bien regardé. Et j''ai vu ce petit bonhomme tout à fait extraordinaire qui me considérait gravement..." La version originale du chef-d''œuvre de Saint-Exupéry, suivie d''un cahier spécial pour aller à la rencontre de l''auteur.', 'disponible'),
('https://cdn.cultura.com/cdn-cgi/image/width=830/media/pim/9780141036144.jpg', '1984', 'George Orwell', '1949', 'Dystopie', 'De tous les carrefours importants, le visage à la moustache noire vous fixait du regard. BIG BROTHER VOUS REGARDE, répétait la légende, tandis que le regard des yeux noirs pénétrait les yeux de Winston... Au loin, un hélicoptère glissa entre les toits, plana un moment, telle une mouche bleue, puis repartit comme une flèche, dans un vol courbe. C''était une patrouille qui venait mettre le nez aux fenêtres des gens. Mais les patrouilles n''avaient pas d''importance. Seule comptait la Police de la Pensée.', 'indisponible'),
('https://cdn.cultura.com/cdn-cgi/image/width=830/media/pim/TITELIVE/74_9782070584628_1_75.jpg', 'Harry Potter à l''école des sorciers', 'J.K. Rowling', '1997', 'Fantastique', 'Harry Potter est orphelin. Il mène une vie bien monotone chez son oncle et sa tante et leur horrible fils. Le jour de ses onze ans, son existence bascule : un géant vient le chercher pour l''emmener dans une école de sorciers où une place l''attend depuis toujours. Quel mystère entoure sa naissance ? Et qui est l''effroyable mage dont personne n''aime prononcer le nom ? Harry intègre le collège Poudlard et s''y plaît aussitôt. Voler à cheval sur des balais, jeter des sorts, devenir champion de Quidditch (une sorte de football pour sorciers), combattre les trolls : Harry Potter se révèle un sorcier vraiment doué. Il semble pourtant que tout le monde ne l''apprécie pas...', 'disponible'),
('https://labourseauxlivres.fr/cdn/shop/files/wSC7b0rzf4PFYrda-21kCKc2jmCeDJZE-MINdzU_uIcjC9VyVpstTg-cover-large_55e0e130-1574-4a0b-a850-edf5585c4447.jpg?v=1753803713&width=600', 'Les Royaumes de Feu (Tome 1) - La Prophétie', 'Sutherland, Tui T.', NULL, 'Bande dessinée', 'Une terrible guerre divise les royaumes du monde de Pyrrhia. Selon une mystérieuse prophétie, seuls cinq jeunes dragons nés lors de la Nuit-la-plus-Claire pourront mettre fin aux combats et apporter la paix. Mais les élus, Argil, Tsunami, Gloria, Comète et Sunny, rêvent de voler de leurs propres ailes plutôt que d''accomplir leur destin...', 'disponible'),
('https://m.media-amazon.com/images/I/71x8JyAdUZL._SY385_.jpg', 'Cantique du chaos', 'Mathieu Belezi', NULL, 'Fiction', 'Le monde a atteint son point ultime de folie. Des cataclysmes le ravagent, des régimes totalitaires l''enflamment. Mais un homme, Théo Gracques, se montre indifférent à ces désastres. Réfugié sur une île, il y rencontre une femme, elle aussi rebelle, et ses deux enfants. Tous les quatre s''engagent alors dans un périple à travers l''Europe et les Amériques pour défier le destin, vivre jusqu''au bout leur liberté...\n\nCantique du chaos est un texte d''un lyrisme que l''on n''aurait pas imaginé encore possible dans la littérature française. Dans une langue inouïe, pleine de rage et de douceur, Mathieu Belezi nous offre un road-movie baroque, une incarnation moderne de la figure du desperado. Après la révolte portée par Attaquer la terre et le soleil (Le Tripode, 2022), ce roman inscrit une nouvelle fois l''auteur dans la lignée des grands écrivains sud et nord-américains. On pense à Roberto Bolaño, Jack Kerouac, Gabriel García Márquez, Cormac McCarthy... à tous ceux qui, en réponse aux délires des hommes, font de la littérature un des derniers bastions de la résistance – et la seule échappatoire face à ce qui nous déshumanise.', 'disponible'),
('https://m.media-amazon.com/images/I/91t7YlWwGfL._SY425_.jpg', 'One Piece - Tome 1: À l''aube d''une grande aventure', 'Eiichiro Oda', NULL, 'Manga', 'Le roi des pirates, ce sera lui !\n\nNous sommes à l''ère des pirates. Luffy, un garçon espiègle, rêve de devenir le roi des pirates en trouvant le “One Piece”, un fabuleux trésor. Seulement, Luffy a avalé un fruit du démon qui l''a transformé en homme élastique. Depuis, il est capable de contorsionner son corps dans tous les sens, mais il a perdu la faculté de nager. Avec l''aide de ses précieux amis, il va devoir affronter de redoutables pirates dans des aventures toujours plus rocambolesques.\nÉgalement adapté en dessin animé pour la télévision et le cinéma, One Piece remporte un formidable succès à travers le monde. Les aventures de Luffy au chapeau de paille ont désormais gagné tous les lecteurs, qui se passionnent chaque trimestre pour les aventures exceptionnelles de leurs héros.', 'disponible'),
('https://m.media-amazon.com/images/I/613H6840ArL._SY466_.jpg', 'La femme de ménage - Tome 1', 'Freida McFadden', NULL, 'Roman', 'Chaque jour, Millie fait le ménage dans la belle maison des Winchester, une riche famille new-yorkaise. Elle récupère aussi leur fille à l''école et prépare les repas avant d''aller se coucher dans sa chambre, au grenier. Pour la jeune femme, ce nouveau travail est une chance inespérée. L''occasion de repartir de zéro. Mais, sous des dehors respectables, sa patronne se montre de plus en plus instable et toxique. Et puis il y a aussi cette rumeur dérangeante qui court dans le quartier : Mme Winchester aurait tenté de noyer sa fille quelques années auparavant. Heureusement, le charmant M. Winchester est là pour rendre la situation plus supportable. Mais le danger se tapit parfois sous des apparences trompeuses. Et lorsque Millie découvre que la porte de sa chambre mansardée ne ferme que de l''extérieur, il est peut-être déjà trop tard...', 'disponible'),
('https://m.media-amazon.com/images/I/71RMELKuocL._SY466_.jpg', 'Les secrets de la femme de ménage - Tome 2', 'Freida McFadden', NULL, 'Roman', 'Avec ce nouvel emploi, Millie semble avoir une chance en or. Chez les Garrick, un couple fortuné qui possède un somptueux appartement avec vue sur Manhattan, elle fait le ménage et prépare les repas dans la magnifique cuisine. Mais elle ne tarde pas à déceler quelques ombres au tableau... Son patron, Douglas Garrick, d''humeur de plus en plus changeante. Et pourquoi sa femme Wendy reste-t-elle toujours enfermée dans la chambre d''amis ? Le jour où Millie découvre du sang sur une chemise de nuit, elle ne peut plus rester les bras croisés. Quelque chose se trame dans cette maison. Et cela pourrait bien se retourner contre elle si elle continue à fouiner dans les secrets des autres...', 'disponible'),
('https://m.media-amazon.com/images/I/81TyNzmdo9L._SY466_.jpg', 'Je veux maman - Tome 2', 'Sébastien Theveny', NULL, 'Thriller psychologique', 'Hiver 2023, Missouri, USA.\n\nDe nuit, sur une route enneigée et déserte, une automobiliste manque d’écraser une fillette esseulée, errant sur le bas-côté.\n\nL''enfant d''à peine quatre ans demeure prostrée, mutique. Seuls trois mots s''échappent de sa bouche d''une voix monocorde : « Je veux maman ».\n\nNul ne sait qui elle est, d’où elle vient.\n\nPersonne ne la réclame…\n\nLong Island, New York, USA\n\nKaren Blackstone, journaliste spécialiste des cold cases, quelques mois après avoir résolu l’affaire de La disparition de Veronika Lake, va cette fois se trouver confrontée à l’impensable énigme, l’incroyable vérité au sujet de cette… apparition irrésolue.\n\nUne plongée blanche sur les pentes glissantes de la noirceur humaine…', 'disponible'),
('https://m.media-amazon.com/images/I/81pfxjDmRhL._SY385_.jpg', 'Astérix en Lusitanie - n°41', 'René Goscinny, Albert Uderzo, Fabcaro, Didier Conra', NULL, 'Bande dessinée', 'Par un beau matin de printemps, un inconnu débarque au village. Il arrive de Lusitanie, cette terre de soleil à l’ouest de l’Hispanie qui se trouve également sous la férule de Rome. Cet ancien esclave croisé dans le Domaine des dieux est venu demander de l’aide à nos irréductibles Gaulois car il connaît les effets puissants de la potion magique. Pour Astérix et Obélix, une nouvelle aventure commence !', 'disponible'),
('https://m.media-amazon.com/images/I/51I8wUBrBDL._SY385_.jpg', 'Atlaséco 2008', 'Le Nouvel Observateur', NULL, 'Documentation', 'La force d''Atlaséco, c''est d''abord la richesse incomparable de sa base de données : plus de 60000 chiffres exclusifs régulièrement réactualisés pour les 230 pays du monde, dans tous les domaines, de l''agriculture au chômage en passant par les investissements ou le commerce extérieur... C''est cet ancrage dans les chiffres réels et vérifiés qui donne à l''ouvrage son autorité et son indépendance d''esprit, reconnues depuis plus de vingt-cinq ans. Loin du jargon économique ou des concepts abstraits des idéologues, Atlaséco entend être un manuel à la fois facile d''accès, bourré d''infos qui ne figurent jamais dans les annuaires officiels et qui n''hésite pas à dire toutes les vérités, y compris celles qui fâchent. Les chiffres sont donnés pour les quatre dernières années, afin de comprendre les évolutions, et comparés systématiquement à ceux de la France, que le lecteur connaît mieux. Les rangs mondiaux sont indiqués pour les productions. Pour chacun des pays, un texte concis commente les chiffres et synthétise surtout les derniers développements économiques et politiques importants. Une carte situe chaque pays à l''intérieur de son continent. Tableaux et cartes récapitulatifs permettent la comparaison des pays entre eux d''un coup d''œil.', 'disponible'),
('https://m.media-amazon.com/images/I/81Rf-k6dO6L._SY466_.jpg', 'Le vent souffle sur Little Balmoral', 'Sophie Jomain', NULL, 'Littérature', 'Hériter d’un vieux manoir et d’un joli pécule pour le rénover pourrait paraître excitant lorsqu’on n’a pas le sou. Toutefois, ce n’est pas l’avis de Phèdre Demay. Non seulement elle n’avait pas prévu de venir s’installer à Little Balmoral, théâtre de son plus grand chagrin d’amour, mais en plus, là-bas, le toit craque et les fenêtres s’ouvrent toutes seules. Et quand Adam, le garçon qui lui a brisé le cœur, vient frapper à sa porte et que des messages codés apparaissent mystérieusement aux quatre coins de la maison, Phèdre pourrait avoir toutes les raisons de croire aux fantômes.\n\nEntre retrouvailles inattendues, sculptures de courges et bâtons de sauge, l’automne promet d’être particulièrement mouvementé. À moins que la magie d’Halloween ne s’étire jusqu’à Noël… Figure incontournable de la scène littéraire francophone, Sophie Jomain a écrit trente romans allant de la littérature fantastique à la comédie en passant par le roman contemporain. Ses romances de l’Avent ont rencontré un immense succès.', 'disponible');

-- =============================================
-- FIN DU SCRIPT
-- =============================================