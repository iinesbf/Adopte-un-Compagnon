<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_role('refuge', 'admin', 'particulier');
$titre = 'Mes annonces';

// L'admin voit tout ; un refuge voit les animaux de ses refuges ;
// un particulier voit les animaux qu'il a publies lui-meme.
if (a_role('admin')) {
    $animaux = $pdo->query("
        SELECT a.*, e.nom AS espece,
               COALESCE(r.nom, CONCAT('Particulier : ', a.detenteur_nom)) AS refuge
        FROM animal a
        JOIN espece e ON e.id_espece = a.id_espece
        LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
        ORDER BY a.date_publication DESC
    ")->fetchAll();
} elseif (a_role('particulier')) {
    $stmt = $pdo->prepare("
        SELECT a.*, e.nom AS espece, CONCAT('Particulier : ', a.detenteur_nom) AS refuge
        FROM animal a
        JOIN espece e ON e.id_espece = a.id_espece
        WHERE a.id_proprietaire = :uid
        ORDER BY a.date_publication DESC
    ");
    $stmt->execute(['uid' => user()['id']]);
    $animaux = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT a.*, e.nom AS espece, r.nom AS refuge
        FROM animal a
        JOIN espece e ON e.id_espece = a.id_espece
        JOIN refuge r ON r.id_refuge = a.id_refuge
        WHERE r.id_utilisateur = :uid
        ORDER BY a.date_publication DESC
    ");
    $stmt->execute(['uid' => user()['id']]);
    $animaux = $stmt->fetchAll();
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head">
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
        <div><h1>Mes annonces</h1><p>Gerez les animaux que vous proposez a l'adoption.</p></div>
        <a href="annonce-form.php" class="btn btn-light">+ Nouvelle annonce</a>
    </div>
</div>
<section>
    <div class="container">
        <?php if (!$animaux): ?>
            <p style="text-align:center;color:var(--texte-clair);padding:40px 0;">
                Vous n'avez pas encore publie d'annonce.
                <a href="annonce-form.php">Creez-en une</a>.
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Nom</th><th>Espece</th><th>Refuge</th><th>Statut</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($animaux as $a): ?>
                    <tr>
                        <td><?= e($a['nom']) ?></td>
                        <td><?= e($a['espece']) ?></td>
                        <td><?= e($a['refuge']) ?></td>
                        <td><span class="badge badge-<?= e($a['statut']) ?>"><?= e(statut_label($a['statut'])) ?></span></td>
                        <td>
                            <a class="btn btn-sm btn-outline" href="animal.php?id=<?= (int)$a['id_animal'] ?>">Voir</a>
                            <a class="btn btn-sm" href="annonce-form.php?id=<?= (int)$a['id_animal'] ?>">Modifier</a>
                            <a class="btn btn-sm btn-danger" href="supprimer-annonce.php?id=<?= (int)$a['id_animal'] ?>"
                               onclick="return confirm('Supprimer definitivement cette annonce ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
