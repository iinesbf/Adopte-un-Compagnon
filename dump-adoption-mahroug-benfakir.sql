-- =====================================================================
--  Projet ESIEE E4FG-SI 2026  —  Site d'adoption animale "Adopte un Compagnon"
--  Dump complet : creation des tables + jeu de donnees de test
--  SGBD : MySQL / MariaDB (XAMPP)
--  Binome : Rihane Mahroug & Ines Ben Fakir
--  A importer dans phpMyAdmin ou : mysql -u root < dump-adoption-mahroug-benfakir.sql
-- =====================================================================

DROP DATABASE IF EXISTS adoption;
CREATE DATABASE adoption CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE adoption;

-- Garantit le bon encodage des caracteres (emojis, accents) a l'import
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  Table : utilisateur  (3 roles : admin, refuge, visiteur)
-- ---------------------------------------------------------------------
CREATE TABLE utilisateur (
    id_utilisateur   INT AUTO_INCREMENT PRIMARY KEY,
    nom              VARCHAR(60)  NOT NULL,
    prenom           VARCHAR(60)  NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255) NOT NULL,
    role             ENUM('admin','refuge','visiteur') NOT NULL DEFAULT 'visiteur',
    date_inscription DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Table : espece  (les 6 categories affichees en cartes sur l'accueil)
-- ---------------------------------------------------------------------
CREATE TABLE espece (
    id_espece   INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(40) NOT NULL UNIQUE,
    emoji       VARCHAR(10) DEFAULT NULL,
    image       VARCHAR(255) DEFAULT NULL,  -- photo representative (cartes accueil)
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Table : refuge  (geolocalise pour les pings de la carte)
-- ---------------------------------------------------------------------
CREATE TABLE refuge (
    id_refuge      INT AUTO_INCREMENT PRIMARY KEY,
    nom            VARCHAR(120) NOT NULL,
    adresse        VARCHAR(200) DEFAULT NULL,
    ville          VARCHAR(80)  NOT NULL,
    code_postal    VARCHAR(10)  DEFAULT NULL,
    region         VARCHAR(80)  DEFAULT NULL,
    latitude       DECIMAL(10,7) DEFAULT NULL,
    longitude      DECIMAL(10,7) DEFAULT NULL,
    telephone      VARCHAR(20)  DEFAULT NULL,
    email          VARCHAR(150) DEFAULT NULL,
    description    TEXT         DEFAULT NULL,
    valide         TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = valide par l'admin, 0 = en attente
    id_utilisateur INT          DEFAULT NULL,
    CONSTRAINT fk_refuge_user FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur(id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Table : animal  (= une annonce d'adoption)
--  Un animal est detenu soit par un REFUGE, soit par un PARTICULIER.
--  - si refuge      : id_refuge renseigne, detenteur_* a NULL
--  - si particulier : id_refuge a NULL, detenteur_* renseignes
-- ---------------------------------------------------------------------
CREATE TABLE animal (
    id_animal           INT AUTO_INCREMENT PRIMARY KEY,
    nom                 VARCHAR(80)  NOT NULL,
    id_espece           INT          NOT NULL,
    type_detenteur      ENUM('refuge','particulier') NOT NULL DEFAULT 'refuge',
    id_refuge           INT          DEFAULT NULL,
    detenteur_nom       VARCHAR(120) DEFAULT NULL,
    detenteur_ville     VARCHAR(80)  DEFAULT NULL,
    detenteur_region    VARCHAR(80)  DEFAULT NULL,
    race                VARCHAR(80)  DEFAULT NULL,
    age_annees          INT          DEFAULT NULL,
    sexe                ENUM('M','F') DEFAULT NULL,
    sterilise           TINYINT(1)   NOT NULL DEFAULT 0,  -- 1 = sterilise/castre, 0 = non
    ancien_proprietaire VARCHAR(150) DEFAULT NULL,
    description         TEXT         DEFAULT NULL,
    photo               VARCHAR(255) DEFAULT NULL,
    statut              ENUM('disponible','reserve','adopte') NOT NULL DEFAULT 'disponible',
    date_publication    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_animal_espece FOREIGN KEY (id_espece)
        REFERENCES espece(id_espece) ON DELETE RESTRICT,
    CONSTRAINT fk_animal_refuge FOREIGN KEY (id_refuge)
        REFERENCES refuge(id_refuge) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Table : demande_adoption
-- ---------------------------------------------------------------------
CREATE TABLE demande_adoption (
    id_demande     INT AUTO_INCREMENT PRIMARY KEY,
    id_animal      INT NOT NULL,
    id_utilisateur INT NOT NULL,
    message        TEXT DEFAULT NULL,
    statut         ENUM('en_attente','acceptee','refusee') NOT NULL DEFAULT 'en_attente',
    date_demande   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dem_animal FOREIGN KEY (id_animal)
        REFERENCES animal(id_animal) ON DELETE CASCADE,
    CONSTRAINT fk_dem_user FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
--  JEU DE DONNEES DE TEST
-- =====================================================================

-- Especes (les 6 cartes de l'accueil)
INSERT INTO espece (nom, emoji, image, description) VALUES
('Chien',   '🐕', 'img/animaux/chien/priscilla-du-preez-TjBp4J5b6jA-unsplash.jpg', 'Fideles compagnons, petits et grands'),
('Chat',    '🐈', 'img/animaux/chat/kabo-ng9yenZfeuI-unsplash.jpg',                'Independants et calins'),
('Lapin',   '🐇', 'img/animaux/lapin/sgalagaev-w0rUWZi7jxM-unsplash.jpg',          'Doux et discrets'),
('Oiseau',  '🦜', 'img/animaux/oiseau/florian-k-mhVhHGllKIM-unsplash.jpg',         'Colores et chanteurs'),
('Rongeur', '🐹', 'img/animaux/rongeur/ricky-kharawala-adK3Vu70DEQ-unsplash.jpg',  'Hamsters, cochons d''Inde, rats...'),
('Reptile', '🦎', 'img/animaux/reptile/verdian-chua-68hC4vYTSFo-unsplash.jpg',     'Serpents, tortues, lezards et geckos');

-- Utilisateurs (mot de passe de tous les comptes = "password")
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES
('Gestionnaire','Admin', 'admin@adoption.fr',       '$2y$12$29wtlFyWtUD.dxvQpEbhqeBd7JiLHiNQHqMpz0PX0DbqCZQ/xaq/.', 'admin'),
('Dupont',   'Marie',   'refuge.spa@adoption.fr',  '$2y$12$29wtlFyWtUD.dxvQpEbhqeBd7JiLHiNQHqMpz0PX0DbqCZQ/xaq/.', 'refuge'),
('Martin',   'Lucas',   'refuge.patte@adoption.fr','$2y$12$29wtlFyWtUD.dxvQpEbhqeBd7JiLHiNQHqMpz0PX0DbqCZQ/xaq/.', 'refuge'),
('Bernard',  'Sophie',  'sophie@mail.fr',          '$2y$12$29wtlFyWtUD.dxvQpEbhqeBd7JiLHiNQHqMpz0PX0DbqCZQ/xaq/.', 'visiteur'),
('Petit',    'Thomas',  'thomas@mail.fr',          '$2y$12$29wtlFyWtUD.dxvQpEbhqeBd7JiLHiNQHqMpz0PX0DbqCZQ/xaq/.', 'visiteur'),
-- Comptes dedies a l'enseignant (mot de passe = "test")
('Grimej',   'Mohamed', 'professeur@adoption.fr',  '$2y$12$G94kKURGC1EPV1GUakCD1ebcGMDCtO5FMAiGvg5/q/ZwsQhYn4pEO', 'admin'),
('Test',     'Admin',   'admin@esiee.fr',          '$2y$12$G94kKURGC1EPV1GUakCD1ebcGMDCtO5FMAiGvg5/q/ZwsQhYn4pEO', 'admin');

-- Refuges (avec region et coordonnees GPS pour la carte)
INSERT INTO refuge (nom, adresse, ville, code_postal, region, latitude, longitude, telephone, email, description, id_utilisateur) VALUES
('Refuge SPA de Gennevilliers', '30 Av. du Gen. de Gaulle', 'Gennevilliers', '92230', 'Ile-de-France',                48.9270000, 2.2940000, '01 47 98 57 40', 'spa92@adoption.fr',   'Le plus grand refuge d''Ile-de-France.', 2),
('Le Refuge de la Patte',       '12 Rue des Lilas',         'Paris',         '75019', 'Ile-de-France',                48.8830000, 2.3820000, '01 42 00 11 22', 'patte@adoption.fr',   'Petite structure associative au coeur de Paris.', 3),
('Refuge des 4 Pattes',         '5 Route de Lyon',          'Montreuil',     '93100', 'Ile-de-France',                48.8610000, 2.4410000, '01 48 70 00 00', 'quatrepattes@adoption.fr', 'Accueille chiens, chats et NAC.', NULL),
('Arche de Noe 78',             '8 Chemin du Bois',         'Versailles',    '78000', 'Ile-de-France',                48.8010000, 2.1300000, '01 39 50 12 34', 'arche78@adoption.fr', 'Specialise dans les animaux ages.', NULL),
('Refuge SPA de Marseille',     '1 Bd Animalier',           'Marseille',     '13014', 'Provence-Alpes-Cote d''Azur',  43.3360000, 5.3900000, '04 91 00 00 00', 'spa13@adoption.fr',   'Refuge du sud de la France.', NULL),
('Refuge de Lyon Gerland',      '20 Quai du Rhone',         'Lyon',          '69007', 'Auvergne-Rhone-Alpes',         45.7300000, 4.8320000, '04 72 00 00 00', 'lyon@adoption.fr',    'Au bord du Rhone.', NULL),
('Refuge de Bordeaux',          '15 Av. de l''Ocean',       'Merignac',      '33700', 'Nouvelle-Aquitaine',           44.8300000,-0.6500000, '05 56 00 00 00', 'bordeaux@adoption.fr','Refuge de la metropole bordelaise.', NULL),
('Refuge des Flandres',         '3 Rue du Nord',            'Lille',         '59000', 'Hauts-de-France',              50.6292000, 3.0573000, '03 20 00 00 00', 'lille@adoption.fr',   'Au service des animaux du Nord.', NULL),
('Refuge Atlantique',           '7 Rue de la Loire',        'Nantes',        '44000', 'Pays de la Loire',             47.2184000,-1.5536000, '02 40 00 00 00', 'nantes@adoption.fr',  'Refuge de l''Ouest.', NULL),
('Centre faune sauvage Alsace', '2 Route de la Foret',      'Strasbourg',    '67000', 'Grand Est',                    48.5734000, 7.7521000, '03 88 00 00 00', 'alsace@adoption.fr',  'Centre de soins et de parrainage.', NULL),
('Refuge du Marais',            '10 Rue des Archives',      'Paris',         '75004', 'Ile-de-France',                48.8559000, 2.3588000, '01 42 11 22 33', 'marais@adoption.fr',  'Refuge associatif du centre de Paris.', NULL),
('Ecole du Chat de Paris 13',   '25 Av. d''Italie',         'Paris',         '75013', 'Ile-de-France',                48.8322000, 2.3555000, '01 45 22 33 44', 'chat13@adoption.fr',  'Specialise dans les chats des rues.', NULL),
('Refuge des Batignolles',      '8 Rue Lemercier',          'Paris',         '75017', 'Ile-de-France',                48.8870000, 2.3190000, '01 46 33 44 55', 'batignolles@adoption.fr', 'Petit refuge du nord-ouest parisien.', NULL),
('Adoption Paris 20',           '14 Rue de Bagnolet',       'Paris',         '75020', 'Ile-de-France',                48.8650000, 2.3980000, '01 43 44 55 66', 'paris20@adoption.fr', 'Refuge de l''est parisien.', NULL),
('SPA Paris Bastille',          '3 Bd Richard Lenoir',      'Paris',         '75011', 'Ile-de-France',                48.8580000, 2.3690000, '01 47 55 66 77', 'bastille@adoption.fr','Antenne parisienne de la SPA.', NULL);

-- Les refuges du jeu de test sont deja valides (etablis). Les nouveaux refuges
-- crees via une inscription seront "en attente" (valide=0) jusqu'a approbation admin.
UPDATE refuge SET valide = 1;

-- =====================================================================
--  Animaux (44 annonces)  —  colonnes :
--  nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville,
--  detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire,
--  description, photo, statut
-- =====================================================================

-- ----- CHIENS (espece 1) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Rex',     1, 'refuge',      1, NULL, NULL, NULL, 'Croise berger',          3, 'M', 1, 'Famille Morel',  'Arrive au refuge apres un demenagement. Tres affectueux.', 'img/animaux/chien/12photostory-9oYVq7vCoXg-unsplash.jpg', 'disponible'),
('Bandit',  1, 'refuge',      2, NULL, NULL, NULL, 'Border Collie',          2, 'M', 0, NULL,             'Plein d''energie, ideal pour une personne sportive.', 'img/animaux/chien/baptist-standaert-mx0DEnfYxic-unsplash.jpg', 'disponible'),
('Mochi',   1, 'particulier', NULL, 'M. Dubois',     'Lyon',      'Auvergne-Rhone-Alpes', 'Carlin',        4, 'M', 1, 'M. Dubois',     'Petit carlin calin, propose par son proprietaire.', 'img/animaux/chien/charlesdeluvio-K4mSJ7kc0As-unsplash.jpg', 'disponible'),
('Pongo',   1, 'refuge',      3, NULL, NULL, NULL, 'Croise bouvier',         5, 'M', 1, 'Ancien refuge',  'Sociable avec les autres chiens.', 'img/animaux/chien/fabio-tovar-valderrama-3P6rPpbPf0o-unsplash.jpg', 'disponible'),
('Lila',    1, 'particulier', NULL, 'Mme Garnier',   'Merignac',  'Nouvelle-Aquitaine',   'Jack Russell',  6, 'F', 1, 'Mme Garnier',   'Petite terrier vive et joueuse.', 'img/animaux/chien/gemma-regalado-XaJozZyfTa0-unsplash.jpg', 'disponible'),
('Shadow',  1, 'refuge',      4, NULL, NULL, NULL, 'Croise',                 7, 'M', 0, 'Abandon',        'Senior tres doux qui cherche la tranquillite.', 'img/animaux/chien/margarita-kosior-qip8Py7fh6I-unsplash.jpg', 'reserve'),
('Oreo',    1, 'refuge',      2, NULL, NULL, NULL, 'Border Collie',          4, 'M', 1, NULL,             'Intelligent et obeissant.', 'img/animaux/chien/paul-gaudriault-XShgTPsoATU-unsplash.jpg', 'disponible'),
('Biscuit', 1, 'particulier', NULL, 'Famille Lefevre','Nantes',   'Pays de la Loire',     'Croise golden retriever', 8, 'M', 1, 'Famille Lefevre', 'Senior adorable, parfait avec les enfants.', 'img/animaux/chien/priscilla-du-preez-TjBp4J5b6jA-unsplash.jpg', 'disponible'),
('Rusty',   1, 'refuge',      9, NULL, NULL, NULL, 'Croise berger',          2, 'M', 0, NULL,             'Jeune chien curieux, en pleine forme.', 'img/animaux/chien/shashwat-narkhede-4SFoRaAno_Y-unsplash.jpg', 'disponible');

-- ----- CHATS (espece 2) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Tigrou',    2, 'refuge',      1, NULL, NULL, NULL, 'Europeen tigre',           2, 'M', 1, NULL,            'Chat tigre tres sociable.', 'img/animaux/chat/acidfern-3dejnak626k-unsplash.jpg', 'disponible'),
('Roux',      2, 'refuge',      6, NULL, NULL, NULL, 'Europeen poil long',       3, 'M', 0, 'Abandon',       'Beau chat roux a poil mi-long.', 'img/animaux/chat/dhaya-eddine-bentaleb-xnyfMfMM6Fk-unsplash.jpg', 'disponible'),
('Patchwork', 2, 'particulier', NULL, 'Mme Roche',  'Marseille', 'Provence-Alpes-Cote d''Azur', 'Europeen ecaille poil long', 9, 'F', 1, 'Mme Roche', 'Chatte ecaille de tortue, majestueuse.', 'img/animaux/chat/ilze-EU-F188r1Ig-unsplash.jpg', 'disponible'),
('Milo',      2, 'refuge',      2, NULL, NULL, NULL, 'Europeen blanc et roux',   1, 'M', 0, NULL,            'Jeune chat curieux et joueur.', 'img/animaux/chat/juan-manuel-sanchez-egk9uKaoNng-unsplash.jpg', 'disponible'),
('Garfield',  2, 'particulier', NULL, 'M. Petit',   'Lille',     'Hauts-de-France',      'Europeen roux',   5, 'M', 1, 'M. Petit',      'Gros matou tranquille qui aime dormir.', 'img/animaux/chat/kabo-ng9yenZfeuI-unsplash.jpg', 'disponible'),
('Bleu',      2, 'refuge',      1, NULL, NULL, NULL, 'Bleu Russe',               3, 'M', 1, NULL,            'Robe grise magnifique, caractere doux.', 'img/animaux/chat/milada-vigerova-0TPAlZ87mzk-unsplash.jpg', 'disponible'),
('Caramel',   2, 'refuge',      5, NULL, NULL, NULL, 'Europeen roux tigre',      4, 'M', 1, 'Abandon',       'Chat roux affectueux, stérilisé.', 'img/animaux/chat/zhang-kaiyv-FJ6R6qhbmbM-unsplash.jpg', 'disponible');

-- ----- LAPINS (espece 3) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Gris-Gris', 3, 'refuge',      3, NULL, NULL, NULL, 'Lapin commun gris',  2, 'M', 0, NULL,           'Lapin gris calme.', 'img/animaux/lapin/ali-kazal-wVv6vIhYZ70-unsplash.jpg', 'disponible'),
('Bugs',      3, 'particulier', NULL, 'M. Leroy',   'Strasbourg','Grand Est',            'Lapin de garenne', 1, 'M', 0, 'M. Leroy',  'Lapin vif, habitue a l''exterieur.', 'img/animaux/lapin/gary-bendig-KvHT4dltPEQ-unsplash.jpg', 'disponible'),
('Noisette',  3, 'refuge',      9, NULL, NULL, NULL, 'Lapin de garenne',   2, 'F', 0, NULL,           'Lapine douce et curieuse.', 'img/animaux/lapin/lance-reis-yGpnXRtbNi8-unsplash.jpg', 'disponible'),
('Dalmatien', 3, 'refuge',      2, NULL, NULL, NULL, 'Lapin nain tachete', 1, 'F', 1, NULL,           'Petit lapin nain noir et blanc.', 'img/animaux/lapin/michael-kater-g0sTDxDUA-c-unsplash.jpg', 'disponible'),
('Flocon',    3, 'particulier', NULL, 'Famille Blanc','Versailles','Ile-de-France',      'Lapin nain blanc', 1, 'F', 0, 'Famille Blanc', 'Adorable lapin nain blanc.', 'img/animaux/lapin/satyabratasm-u_kMWN-BWyU-unsplash.jpg', 'disponible'),
('Praline',   3, 'refuge',      7, NULL, NULL, NULL, 'Lapin nain fauve',   1, 'F', 0, NULL,           'Bebe lapin fauve.', 'img/animaux/lapin/sgalagaev-JMnNRd04bbQ-unsplash.jpg', 'disponible'),
('Cannelle',  3, 'refuge',      7, NULL, NULL, NULL, 'Lapin nain fauve',   1, 'F', 0, NULL,           'Petite soeur de Praline.', 'img/animaux/lapin/sgalagaev-w0rUWZi7jxM-unsplash.jpg', 'disponible'),
('Pompon',    3, 'particulier', NULL, 'Mme Faure',  'Nantes',    'Pays de la Loire',     'Lapin fauve', 2, 'M', 1, 'Mme Faure',   'Lapin sociable qui fait le beau.', 'img/animaux/lapin/stefan-fluck-usoJcs--nEk-unsplash.jpg', 'disponible');

-- ----- OISEAUX (espece 4) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Kiwi',    4, 'particulier', NULL, 'M. Simon',   'Paris',     'Ile-de-France',        'Perruche ondulee',  1, 'F', 0, 'M. Simon',  'Petite perruche apprivoisee.', 'img/animaux/oiseau/danielle-claude-belanger-fl41ilfPOt0-unsplash.jpg', 'disponible'),
('Rio',     4, 'refuge',      5, NULL, NULL, NULL, 'Ara rouge',               12, 'M', 0, 'Cirque',          'Ara recueilli, tres bavard.', 'img/animaux/oiseau/florian-k-mhVhHGllKIM-unsplash.jpg', 'disponible'),
('Mango',   4, 'refuge',      6, NULL, NULL, NULL, 'Ara bleu et or',          15, 'M', 0, NULL,              'Magnifique ara, demande de l''espace.', 'img/animaux/oiseau/karim-manjra-0oCZLHIHBns-unsplash.jpg', 'disponible'),
('Azur',    4, 'refuge',      6, NULL, NULL, NULL, 'Ara bleu et or',          10, 'F', 0, NULL,              'Femelle ara calme.', 'img/animaux/oiseau/karim-manjra-4euubO4CasU-unsplash.jpg', 'reserve'),
('Liberty', 4, 'refuge',      10, NULL, NULL, NULL, 'Aigle a tete blanche',    8, 'M', 0, 'Centre de soins', 'Rapace en parrainage (non relachable).', 'img/animaux/oiseau/richard-lee-MLfe9XFhFwk-unsplash.jpg', 'disponible'),
('Rose',    4, 'particulier', NULL, 'Mme Henry',  'Merignac',  'Nouvelle-Aquitaine',   'Cacatoes rosalbin', 20, 'F', 0, 'Mme Henry', 'Cacatoes tres attachee a l''humain.', 'img/animaux/oiseau/ricke-a9kS49tOqnk-unsplash.jpg', 'disponible'),
('Perle',   4, 'refuge',      9, NULL, NULL, NULL, 'Perruche verte',           2, 'F', 0, NULL,              'Petite perruche verte vive.', 'img/animaux/oiseau/zdenek-machacek-OlKkCmToXEs-unsplash.jpg', 'disponible');

-- ----- RONGEURS (espece 5) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Patate',    5, 'refuge',      3, NULL, NULL, NULL, 'Cochon d''Inde', 2, 'F', 0, NULL,            'Cochon d''Inde roux et blanc.', 'img/animaux/rongeur/jack-catalano-6aY_0S-epZQ-unsplash.jpg', 'disponible'),
('Choco',     5, 'refuge',      3, NULL, NULL, NULL, 'Cochon d''Inde', 1, 'M', 0, NULL,            'Cochon d''Inde tricolore.', 'img/animaux/rongeur/nikolett-emmert-5yDheyMt4S4-unsplash.jpg', 'disponible'),
('Roudoudou', 5, 'particulier', NULL, 'Famille Girard','Strasbourg','Grand Est',          'Cochon d''Inde', 3, 'M', 0, 'Famille Girard', 'Cochon d''Inde roux tres gourmand.', 'img/animaux/rongeur/nils-schirmer-cKYM8KMwaUQ-unsplash.jpg', 'disponible'),
('Boule',     5, 'particulier', NULL, 'M. Garcia',  'Paris',     'Ile-de-France',         'Hamster dore',   1, 'M', 0, 'M. Garcia',  'Hamster dore donne pour cause d''allergie.', 'img/animaux/rongeur/ricky-kharawala-adK3Vu70DEQ-unsplash.jpg', 'disponible'),
('Filou',     5, 'refuge',      9, NULL, NULL, NULL, 'Gerbille',       1, 'M', 0, NULL,            'Petite gerbille active.', 'img/animaux/rongeur/snap-wander-My_eDeFZU2I-unsplash.jpg', 'disponible'),
('Tic',       5, 'refuge',      10, NULL, NULL, NULL, 'Ecureuil roux',  2, 'M', 0, 'Centre de soins', 'Ecureuil en parrainage au centre faune.', 'img/animaux/rongeur/stephanie-gibeault-htvMgXO-qe8-unsplash.jpg', 'disponible'),
('Remy',      5, 'particulier', NULL, 'M. Lopez',   'Montreuil', 'Ile-de-France',         'Rat domestique', 1, 'M', 0, 'M. Lopez',   'Rat domestique tres intelligent et propre.', 'img/animaux/rongeur/nikolett-emmert-6psIlD5l0fM-unsplash.jpg', 'disponible');

-- ----- REPTILES (espece 6) -----
INSERT INTO animal (nom, id_espece, type_detenteur, id_refuge, detenteur_nom, detenteur_ville, detenteur_region, race, age_annees, sexe, sterilise, ancien_proprietaire, description, photo, statut) VALUES
('Caouane', 6, 'refuge',      10, NULL, NULL, NULL, 'Tortue marine',        25, 'F', 0, 'Centre de sauvegarde', 'Tortue en parrainage (non relachable).', 'img/animaux/reptile/abner-abiu-castillo-diaz-N5ByCirHVqw-unsplash.jpg', 'disponible'),
('Zilla',   6, 'particulier', NULL, 'M. Bernard', 'Marseille', 'Provence-Alpes-Cote d''Azur', 'Iguane vert', 6, 'M', 0, 'M. Bernard', 'Iguane vert, terrarium fourni.', 'img/animaux/reptile/alex-CxayZniisNA-unsplash.jpg', 'disponible'),
('Kaa',     6, 'refuge',      5, NULL, NULL, NULL, 'Serpent (Python tapis)', 4, 'M', 0, NULL,           'Python tapis docile, pour eleveur averti.', 'img/animaux/reptile/david-clode-Ws6Tb1cI0co-unsplash.jpg', 'disponible'),
('Pascal',  6, 'particulier', NULL, 'Mme Dupuis', 'Lyon',      'Auvergne-Rhone-Alpes', 'Cameleon',      2, 'M', 0, 'Mme Dupuis', 'Cameleon, change de couleur selon l''humeur.', 'img/animaux/reptile/hasmik-ghazaryan-olson-N_GrR8c2EMk-unsplash.jpg', 'disponible'),
('Sunny',   6, 'particulier', NULL, 'M. Roux',    'Lille',     'Hauts-de-France',      'Gecko leopard', 3, 'F', 0, 'M. Roux',    'Gecko leopard facile a entretenir.', 'img/animaux/reptile/suri-huang-cHP9WBFKm9o-unsplash.jpg', 'disponible'),
('Leo',     6, 'refuge',      7, NULL, NULL, NULL, 'Gecko leopard',          2, 'M', 0, NULL,           'Jeune gecko leopard curieux.', 'img/animaux/reptile/verdian-chua-68hC4vYTSFo-unsplash.jpg', 'disponible');

-- Demandes d'adoption de test
INSERT INTO demande_adoption (id_animal, id_utilisateur, message, statut) VALUES
(1, 4, 'Bonjour, j''ai un grand jardin, Rex serait heureux chez nous !', 'en_attente'),
(10, 5, 'Je recherche un chat sociable, Tigrou me parait parfait.', 'acceptee'),
(2, 5, 'Je suis tres sportif, Bandit me conviendrait bien.', 'en_attente');
