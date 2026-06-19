<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// READ : detail d'un animal en croisant animal + espece + refuge (3 tables)
$stmt = $pdo->prepare("
    SELECT a.*, e.nom AS espece, e.emoji,
           r.nom AS refuge_nom, r.telephone, r.email AS refuge_email,
           COALESCE(r.ville, a.detenteur_ville)   AS ville,
           COALESCE(r.region, a.detenteur_region) AS region,
           r.code_postal
    FROM animal a
    JOIN espece e ON e.id_espece = a.id_espece
    LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
    WHERE a.id_animal = :id
");
$stmt->execute(['id' => $id]);
$a = $stmt->fetch();

if (!$a) {
    http_response_code(404);
    $titre = 'Animal introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding:60px 0;text-align:center;">
            <h1>Animal introuvable</h1>
            <p><a href="recherche.php" class="btn" style="margin-top:20px;">Retour a la recherche</a></p>
          </div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$titre = $a['nom'] . ' — a adopter';
require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="container">
        <p><a href="recherche.php" style="color:var(--vert-clair);">&larr; Retour aux animaux</a></p>
        <h1><?= e($a['nom']) ?></h1>
    </div>
</div>

<section>
    <div class="container detail-grid">
        <div class="detail-photo" <?= $a['photo'] ? 'style="background-image:url(\'' . e($a['photo']) . '\')"' : '' ?>>
            <?= $a['photo'] ? '' : e($a['emoji']) ?>
        </div>
        <div class="detail-info">
            <span class="badge badge-<?= e($a['statut']) ?>"><?= e(statut_label($a['statut'])) ?></span>
            <h1><?= e($a['nom']) ?></h1>
            <ul class="fiche">
                <li><strong>Espece</strong> <?= e($a['espece']) ?></li>
                <li><strong>Race</strong> <?= e($a['race'] ?: 'Non precise') ?></li>
                <li><strong>Age</strong> <?= $a['age_annees'] !== null ? (int) $a['age_annees'] . ' an(s)' : 'Non precise' ?></li>
                <li><strong>Sexe</strong> <?= e(sexe_label($a['sexe'])) ?></li>
                <li><strong>Sterilise/castre</strong> <?= $a['sterilise'] ? 'Oui' : 'Non' ?></li>
                <li><strong>Ancien proprietaire</strong> <?= e($a['ancien_proprietaire'] ?: 'Inconnu') ?></li>
                <?php if ($a['type_detenteur'] === 'refuge'): ?>
                    <li><strong>Refuge</strong> <?= e($a['refuge_nom']) ?></li>
                    <li><strong>Contact</strong> <?= e($a['telephone'] ?: $a['refuge_email']) ?></li>
                <?php else: ?>
                    <li><strong>Particulier</strong> <?= e($a['detenteur_nom']) ?></li>
                <?php endif; ?>
                <li><strong>Localisation</strong> <?= e($a['ville']) ?><?= $a['code_postal'] ? ' (' . e($a['code_postal']) . ')' : '' ?><?= $a['region'] ? ' — ' . e($a['region']) : '' ?></li>
            </ul>
            <p><?= nl2br(e($a['description'] ?: 'Aucune description.')) ?></p>

            <div style="margin-top:26px;">
                <?php if ($a['statut'] === 'disponible'): ?>
                    <a href="demande-adoption.php?id=<?= (int) $a['id_animal'] ?>" class="btn">Faire une demande d'adoption</a>
                <?php else: ?>
                    <p style="color:var(--texte-clair);">Cet animal n'est plus disponible a l'adoption.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
