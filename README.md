# Adopte un Compagnon 🐾

Plateforme web d'adoption animale — Projet final **ESIEE E4FG SI 2026**.
Met en relation des **refuges** (qui publient des annonces d'animaux) et des **adoptants**.

> Binôme : **Rihane Mahroug** — **Inès Ben Fakir**
> Site en ligne : https://ines-rihane-adoption-animal.site.je/
> Github en ligne : https://github.com/iinesbf/Adopte-un-Compagnon/

---

## ⚙️ Installation sur XAMPP

1. Copier **le contenu du dossier `htdocs/`** de ce projet dans `xampp/htdocs/adoption/`
   (l'application — `index.php`, `css/`, `includes/`, `img/`… — doit se retrouver
   directement dans `xampp/htdocs/adoption/`).
2. Démarrer **Apache** et **MySQL** depuis le panneau XAMPP.
3. Importer la base : ouvrir `http://localhost/phpmyadmin`, onglet **Importer**,
   choisir le fichier `dump-adoption-mahroug-benfakir.sql`, puis **Exécuter**.
   *(ou en ligne de commande : `mysql -u root < dump-adoption-mahroug-benfakir.sql`)*
4. Ouvrir `http://localhost/adoption/`.

> `connexion.php` **détecte automatiquement** l'environnement : en local (XAMPP) il utilise
> `root` / mot de passe vide / base `adoption` — **rien à modifier**.

---

## 👤 Comptes disponible

| Rôle | Email | Mot de passe | Accès |
|---|---|---|---|
| **Admin** | `admin@adoption.fr` | `password` | Tout gérer, valider les refuges, accepter/refuser les demandes, stats |
| **Admin (prof)** | `professeur@adoption.fr` | `test` | Idem |
| **Admin (prof)** | `admin@esiee.fr` | `test` | Idem — compte dédié à l'enseignant |
| **Refuge** | `refuge.spa@adoption.fr` | `password` | Publier / modifier / supprimer ses annonces, gérer ses demandes |
| **Refuge** | `refuge.patte@adoption.fr` | `password` | Idem pour son refuge |
| **Adoptant** | `sophie@mail.fr` | `password` | Rechercher et faire des demandes d'adoption |
| **Adoptant** | `thomas@mail.fr` | `password` | Idem |

> Un nouveau compte **refuge** créé via l'inscription est « en attente » : un **admin**
> doit le valider (page *Administration*) avant qu'il puisse publier des annonces.

---

## 🗂️ Structure du projet

> ⚠️ L'application se trouve dans le dossier **`htdocs/`** du dépôt
> (le reste à la racine = dump SQL, README, guides, rapport).

| Fichier (dans `htdocs/`) | Rôle |
|---|---|
| `connexion.php` | Connexion PDO (détection auto local/en-ligne, en un seul endroit) |
| `index.php` | Accueil : hero, 6 catégories, carte des refuges (recherche+filtre), derniers animaux |
| `recherche.php` | Recherche filtrée (espèce, sexe, ville, texte) |
| `animal.php` | Fiche détaillée d'un animal |
| `login.php` / `register.php` / `logout.php` | Authentification (sessions) |
| `mes-annonces.php` | Tableau de bord refuge (liste + actions CRUD) |
| `annonce-form.php` | Ajout / modification d'une annonce (+ upload de photo) |
| `supprimer-annonce.php` | Suppression d'une annonce |
| `demande-adoption.php` / `mes-demandes.php` | Demandes (envoyées + reçues, accepter/refuser) |
| `admin.php` | Back-office admin (stats + validation des refuges + demandes) |
| `includes/` | `header.php`, `footer.php`, `auth.php`, `functions.php` |
| `css/style.css` | Design (palette vert nature + crème) |
| `img/` | Photos des animaux, image d'accueil, dossier `uploads/` |
| `dump-adoption-mahroug-benfakir.sql` *(racine)* | Dump complet (structure + données de test) |

---
