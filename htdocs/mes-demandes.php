<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_connexion();
$titre = 'Mes demandes';

$uid  = user()['id'];
// Un REFUGE et un PARTICULIER recoivent des demandes sur leurs propres animaux
// (a accepter/refuser ici). L'admin gere la vue GLOBALE dans la page Administration ;
// sa page "Mes demandes" reste donc personnelle, comme pour tout utilisateur.
$gere = a_role('refuge', 'particulier');

// ---------------------------------------------------------------------
//  Traitement : accepter / refuser une demande RECUE (refuge / admin)
//  avec verification que la demande porte bien sur un de NOS animaux.
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_demande'], $_POST['action']) && $gere) {
    $idd    = (int) $_POST['id_demande'];
    $action = $_POST['action'];

    if (in_array($action, ['acceptee', 'refusee'], true)) {
        $chk = $pdo->prepare("
            SELECT a.id_animal, a.id_proprietaire, r.id_utilisateur AS refuge_user
            FROM demande_adoption d
            JOIN animal a ON a.id_animal = d.id_animal
            LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
            WHERE d.id_demande = :id
        ");
        $chk->execute(['id' => $idd]);
        $row = $chk->fetch();

        // L'utilisateur doit etre le refuge proprietaire OU le particulier proprietaire de l'animal
        $aDroit = $row && (((int) $row['refuge_user'] === $uid) || ((int) $row['id_proprietaire'] === $uid));
        if ($aDroit) {
            $pdo->prepare("UPDATE demande_adoption SET statut = :s WHERE id_demande = :id")
                ->execute(['s' => $action, 'id' => $idd]);
            if ($action === 'acceptee') {
                $pdo->prepare("UPDATE animal SET statut = 'reserve' WHERE id_animal = :a")
                    ->execute(['a' => $row['id_animal']]);
            }
            flash('Demande mise a jour.');
        } else {
            http_response_code(403);
            flash('Acces refuse : cette demande ne concerne pas vos animaux.', 'error');
        }
    }
    redirect('mes-demandes.php');
}

// ---------------------------------------------------------------------
//  Demandes RECUES sur mes animaux (refuge => les siens, admin => toutes)
// ---------------------------------------------------------------------
$recues = [];
if ($gere) {
    // Un refuge filtre sur ses refuges ; un particulier filtre sur les animaux qu'il possede.
    $condition = a_role('particulier') ? "a.id_proprietaire = :uid" : "r.id_utilisateur = :uid";
    $st = $pdo->prepare("
        SELECT d.id_demande, d.message, d.statut, d.date_demande,
               a.id_animal, a.nom AS animal,
               COALESCE(r.nom, CONCAT('Particulier : ', a.detenteur_nom)) AS refuge,
               u.prenom, u.nom, u.email
        FROM demande_adoption d
        JOIN animal a      ON a.id_animal = d.id_animal
        LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
        JOIN utilisateur u ON u.id_utilisateur = d.id_utilisateur
        WHERE $condition
        ORDER BY d.statut = 'en_attente' DESC, d.date_demande DESC
    ");
    $st->execute(['uid' => $uid]);
    $recues = $st->fetchAll();
}

// ---------------------------------------------------------------------
//  Mes demandes ENVOYEES (tout le monde)
// ---------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT d.id_demande, d.message, d.statut, d.date_demande,
           a.id_animal, a.nom AS animal, e.nom AS espece,
           COALESCE(r.nom, CONCAT('Particulier : ', a.detenteur_nom)) AS refuge,
           COALESCE(r.ville, a.detenteur_ville) AS ville
    FROM demande_adoption d
    JOIN animal a ON a.id_animal = d.id_animal
    JOIN espece e ON e.id_espece = a.id_espece
    LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
    WHERE d.id_utilisateur = :uid
    ORDER BY d.date_demande DESC
");
$stmt->execute(['uid' => $uid]);
$envoyees = $stmt->fetchAll();

$libelle = ['en_attente' => 'En attente', 'acceptee' => 'Acceptee', 'refusee' => 'Refusee'];
function badge_statut(string $s): string {
    return $s === 'acceptee' ? 'disponible' : ($s === 'refusee' ? 'adopte' : 'reserve');
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Mes demandes</h1>
    <p>Gerez les demandes recues sur vos animaux et suivez celles que vous avez envoyees.</p></div></div>

<section>
    <div class="container">

        <?php if ($gere): ?>
        <!-- ===================== DEMANDES RECUES ===================== -->
        <h2 class="section-title" style="text-align:left;">
            Demandes recues <?php if ($recues): ?><span style="color:var(--vert-moyen)">(<?= count($recues) ?>)</span><?php endif; ?>
        </h2>
        <p class="section-sub" style="text-align:left;margin-bottom:24px;">
            Les demandes d'adoption sur les animaux de votre refuge.
        </p>
        <?php if (!$recues): ?>
            <p style="color:var(--texte-clair);padding:0 0 40px;">Aucune demande recue pour le moment.</p>
        <?php else: ?>
            <table style="margin-bottom:60px;">
                <thead><tr><th>Animal</th><th>Refuge</th><th>Demandeur</th><th>Message</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($recues as $d): ?>
                    <tr>
                        <td><a href="animal.php?id=<?= (int)$d['id_animal'] ?>"><?= e($d['animal']) ?></a></td>
                        <td><?= e($d['refuge']) ?></td>
                        <td><?= e($d['prenom'] . ' ' . $d['nom']) ?><br><small style="color:var(--texte-clair)"><?= e($d['email']) ?></small></td>
                        <td style="max-width:260px;"><?= nl2br(e($d['message'] ?: '—')) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($d['date_demande']))) ?></td>
                        <td><span class="badge badge-<?= badge_statut($d['statut']) ?>"><?= e($libelle[$d['statut']]) ?></span></td>
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
        <?php endif; ?>

        <!-- ===================== MES DEMANDES ENVOYEES ===================== -->
        <h2 class="section-title" style="text-align:left;">Mes demandes envoyees</h2>
        <p class="section-sub" style="text-align:left;margin-bottom:24px;">Les demandes d'adoption que vous avez faites.</p>
        <?php if (!$envoyees): ?>
            <p style="color:var(--texte-clair);padding:20px 0;">
                Vous n'avez envoye aucune demande. <a href="recherche.php">Parcourez les animaux</a>.
            </p>
        <?php else: ?>
            <table>
                <thead><tr><th>Animal</th><th>Refuge / Particulier</th><th>Mon message</th><th>Date</th><th>Statut</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($envoyees as $d): ?>
                    <tr>
                        <td><?= e($d['animal']) ?> <span style="color:var(--texte-clair)">(<?= e($d['espece']) ?>)</span></td>
                        <td><?= e($d['refuge']) ?> — <?= e($d['ville']) ?></td>
                        <td style="max-width:260px;"><?= nl2br(e($d['message'] ?: '—')) ?></td>
                        <td><?= e(date('d/m/Y', strtotime($d['date_demande']))) ?></td>
                        <td><span class="badge badge-<?= badge_statut($d['statut']) ?>"><?= e($libelle[$d['statut']]) ?></span></td>
                        <td><a class="btn btn-sm btn-outline" href="animal.php?id=<?= (int)$d['id_animal'] ?>">Voir l'animal</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
