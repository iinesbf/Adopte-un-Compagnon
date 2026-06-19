<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_role('admin');
$titre = 'Administration';

// Traitement : accepter / refuser une demande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_demande'], $_POST['action'])) {
    $idd = (int) $_POST['id_demande'];
    $action = $_POST['action'];

    if (in_array($action, ['acceptee', 'refusee'], true)) {
        $pdo->prepare("UPDATE demande_adoption SET statut = :s WHERE id_demande = :id")
            ->execute(['s' => $action, 'id' => $idd]);

        // Si acceptee : l'animal passe en "reserve"
        if ($action === 'acceptee') {
            $a = $pdo->prepare("SELECT id_animal FROM demande_adoption WHERE id_demande = :id");
            $a->execute(['id' => $idd]);
            if ($idA = $a->fetchColumn()) {
                $pdo->prepare("UPDATE animal SET statut = 'reserve' WHERE id_animal = :a")->execute(['a' => $idA]);
            }
        }
        flash('Demande mise a jour.');
    }
    redirect('admin.php');
}

// Traitement : valider / rejeter un refuge en attente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_refuge'], $_POST['refuge_action'])) {
    $idr = (int) $_POST['id_refuge'];
    if ($_POST['refuge_action'] === 'valider') {
        $pdo->prepare("UPDATE refuge SET valide = 1 WHERE id_refuge = :id")->execute(['id' => $idr]);
        flash('Refuge valide. Il peut desormais publier des annonces.');
    } elseif ($_POST['refuge_action'] === 'rejeter') {
        $pdo->prepare("DELETE FROM refuge WHERE id_refuge = :id")->execute(['id' => $idr]);
        flash('Refuge rejete et supprime.');
    }
    redirect('admin.php');
}

// Refuges en attente de validation (croise refuge + utilisateur)
$refugesAttente = $pdo->query("
    SELECT r.id_refuge, r.nom, r.ville, u.prenom, u.nom AS user_nom, u.email
    FROM refuge r
    LEFT JOIN utilisateur u ON u.id_utilisateur = r.id_utilisateur
    WHERE r.valide = 0
    ORDER BY r.id_refuge DESC
")->fetchAll();

// Statistiques (plusieurs tables)
$stats = [
    'animaux'     => $pdo->query("SELECT COUNT(*) FROM animal")->fetchColumn(),
    'disponibles' => $pdo->query("SELECT COUNT(*) FROM animal WHERE statut='disponible'")->fetchColumn(),
    'refuges'     => $pdo->query("SELECT COUNT(*) FROM refuge")->fetchColumn(),
    'membres'     => $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn(),
    'demandes'    => $pdo->query("SELECT COUNT(*) FROM demande_adoption WHERE statut='en_attente'")->fetchColumn(),
];

// Toutes les demandes (croise 4 tables)
$demandes = $pdo->query("
    SELECT d.id_demande, d.message, d.statut, d.date_demande,
           a.nom AS animal,
           COALESCE(r.nom, CONCAT('Particulier : ', a.detenteur_nom)) AS refuge,
           u.prenom, u.nom, u.email
    FROM demande_adoption d
    JOIN animal a       ON a.id_animal = d.id_animal
    LEFT JOIN refuge r  ON r.id_refuge = a.id_refuge
    JOIN utilisateur u  ON u.id_utilisateur = d.id_utilisateur
    ORDER BY d.statut = 'en_attente' DESC, d.date_demande DESC
")->fetchAll();

$libelle = ['en_attente' => 'En attente', 'acceptee' => 'Acceptee', 'refusee' => 'Refusee'];

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Tableau de bord</h1>
    <p>Vue d'ensemble de la plateforme.</p></div></div>

<section>
    <div class="container">
        <!-- Statistiques -->
        <div class="especes-grid" style="margin-bottom:50px;">
            <div class="espece-card"><span class="emoji">🐾</span><h3><?= (int)$stats['animaux'] ?></h3><p>Animaux au total</p></div>
            <div class="espece-card"><span class="emoji">✅</span><h3><?= (int)$stats['disponibles'] ?></h3><p>Disponibles</p></div>
            <div class="espece-card"><span class="emoji">🏠</span><h3><?= (int)$stats['refuges'] ?></h3><p>Refuges</p></div>
            <div class="espece-card"><span class="emoji">👥</span><h3><?= (int)$stats['membres'] ?></h3><p>Membres</p></div>
            <div class="espece-card"><span class="emoji">📩</span><h3><?= (int)$stats['demandes'] ?></h3><p>Demandes en attente</p></div>
            <a class="espece-card" href="mes-annonces.php"><span class="emoji">⚙️</span><h3>Gerer</h3><p>Toutes les annonces</p></a>
        </div>

        <!-- Refuges en attente de validation -->
        <h2 class="section-title" style="text-align:left;">
            Refuges en attente
            <?php if ($refugesAttente): ?><span style="color:var(--rouge)">(<?= count($refugesAttente) ?>)</span><?php endif; ?>
        </h2>
        <?php if (!$refugesAttente): ?>
            <p style="color:var(--texte-clair);margin-bottom:40px;">Aucun refuge en attente de validation.</p>
        <?php else: ?>
            <table style="margin-bottom:50px;">
                <thead><tr><th>Refuge</th><th>Ville</th><th>Demande par</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($refugesAttente as $r): ?>
                    <tr>
                        <td><?= e($r['nom']) ?></td>
                        <td><?= e($r['ville']) ?></td>
                        <td><?= e(trim($r['prenom'] . ' ' . $r['user_nom'])) ?><br><small style="color:var(--texte-clair)"><?= e($r['email']) ?></small></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="id_refuge" value="<?= (int)$r['id_refuge'] ?>">
                                <button class="btn btn-sm" name="refuge_action" value="valider">Valider</button>
                                <button class="btn btn-sm btn-danger" name="refuge_action" value="rejeter"
                                        onclick="return confirm('Rejeter et supprimer ce refuge ?');">Rejeter</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <h2 class="section-title" style="text-align:left;">Demandes d'adoption</h2>
        <?php if (!$demandes): ?>
            <p style="color:var(--texte-clair);">Aucune demande pour le moment.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Animal</th><th>Refuge</th><th>Demandeur</th><th>Message</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($demandes as $d): ?>
                    <tr>
                        <td><?= e($d['animal']) ?></td>
                        <td><?= e($d['refuge']) ?></td>
                        <td><?= e($d['prenom'] . ' ' . $d['nom']) ?><br><small style="color:var(--texte-clair)"><?= e($d['email']) ?></small></td>
                        <td style="max-width:280px;"><?= nl2br(e($d['message'] ?: '—')) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($d['date_demande']))) ?></td>
                        <td>
                            <?php $cls = $d['statut']==='acceptee'?'disponible':($d['statut']==='refusee'?'adopte':'reserve'); ?>
                            <span class="badge badge-<?= $cls ?>"><?= e($libelle[$d['statut']]) ?></span>
                        </td>
                        <td>
                            <?php if ($d['statut'] === 'en_attente'): ?>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="id_demande" value="<?= (int)$d['id_demande'] ?>">
                                    <button class="btn btn-sm" name="action" value="acceptee">Accepter</button>
                                    <button class="btn btn-sm btn-danger" name="action" value="refusee">Refuser</button>
                                </form>
                            <?php else: ?>
                                <span style="color:var(--texte-clair)">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
