<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
$titre = 'Adopter — Recherche';

// Filtres recus (GET)
$f_espece = isset($_GET['espece']) && $_GET['espece'] !== '' ? (int) $_GET['espece'] : null;
$f_sexe   = $_GET['sexe']   ?? '';
$f_ville  = trim($_GET['ville'] ?? '');
$f_texte  = trim($_GET['q'] ?? '');

// Construction dynamique de la requete (requete preparee, croise 3 tables)
$sql = "SELECT a.id_animal, a.nom, a.race, a.age_annees, a.sexe, a.statut, a.photo,
               e.nom AS espece, e.emoji,
               COALESCE(r.ville, a.detenteur_ville) AS ville
        FROM animal a
        JOIN espece e ON e.id_espece = a.id_espece
        LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
        WHERE 1=1";
$params = [];

if ($f_espece) { $sql .= " AND a.id_espece = :espece"; $params['espece'] = $f_espece; }
if ($f_sexe === 'M' || $f_sexe === 'F') { $sql .= " AND a.sexe = :sexe"; $params['sexe'] = $f_sexe; }
if ($f_ville !== '') { $sql .= " AND COALESCE(r.ville, a.detenteur_ville) LIKE :ville"; $params['ville'] = '%' . $f_ville . '%'; }
if ($f_texte !== '') {
    $sql .= " AND (a.nom LIKE :q_nom OR a.race LIKE :q_race)";
    $params['q_nom']  = '%' . $f_texte . '%';
    $params['q_race'] = '%' . $f_texte . '%';
}

$sql .= " ORDER BY a.statut = 'disponible' DESC, a.date_publication DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$animaux = $stmt->fetchAll();

$especes = $pdo->query("SELECT id_espece, nom FROM espece ORDER BY nom")->fetchAll();

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="container">
        <h1>Trouvez votre compagnon</h1>
        <p><?= count($animaux) ?> animal(aux) correspondant a votre recherche.</p>
    </div>
</div>

<section>
    <div class="container">
        <!-- Formulaire de filtres -->
        <form class="search-bar" method="get" action="recherche.php">
            <div class="form-group">
                <label for="q">Recherche</label>
                <input type="text" id="q" name="q" value="<?= e($f_texte) ?>" placeholder="Nom, race...">
            </div>
            <div class="form-group">
                <label for="espece">Espece</label>
                <select id="espece" name="espece">
                    <option value="">Toutes</option>
                    <?php foreach ($especes as $esp): ?>
                        <option value="<?= (int) $esp['id_espece'] ?>" <?= $f_espece === (int) $esp['id_espece'] ? 'selected' : '' ?>>
                            <?= e($esp['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="sexe">Sexe</label>
                <select id="sexe" name="sexe">
                    <option value="">Tous</option>
                    <option value="M" <?= $f_sexe === 'M' ? 'selected' : '' ?>>Male</option>
                    <option value="F" <?= $f_sexe === 'F' ? 'selected' : '' ?>>Femelle</option>
                </select>
            </div>
            <div class="form-group">
                <label for="ville">Ville</label>
                <input type="text" id="ville" name="ville" value="<?= e($f_ville) ?>" placeholder="Paris...">
            </div>
            <div class="form-group" style="flex:0;">
                <button type="submit" class="btn">Filtrer</button>
            </div>
        </form>

        <!-- Resultats -->
        <?php if (!$animaux): ?>
            <p style="text-align:center; color:var(--texte-clair); padding:40px 0;">
                Aucun animal ne correspond a votre recherche.
                <a href="recherche.php">Reinitialiser les filtres</a>.
            </p>
        <?php else: ?>
            <div class="animaux-grid">
                <?php foreach ($animaux as $a): ?>
                    <a class="animal-card" href="animal.php?id=<?= (int) $a['id_animal'] ?>">
                        <div class="photo" <?= $a['photo'] ? 'style="background-image:url(\'' . e($a['photo']) . '\')"' : '' ?>>
                            <?= $a['photo'] ? '' : e($a['emoji']) ?>
                        </div>
                        <div class="body">
                            <span class="badge badge-<?= e($a['statut']) ?>"><?= e(statut_label($a['statut'])) ?></span>
                            <h3><?= e($a['nom']) ?></h3>
                            <div class="meta">
                                <?= e($a['espece']) ?><?= $a['race'] ? ' · ' . e($a['race']) : '' ?><br>
                                <?= $a['age_annees'] !== null ? (int) $a['age_annees'] . ' an(s)' : '' ?>
                                · <?= e(sexe_label($a['sexe'])) ?> · <?= e($a['ville']) ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
