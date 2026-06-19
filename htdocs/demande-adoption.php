<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_connexion();
$titre = 'Demande d\'adoption';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// L'animal existe et est disponible ? (croise animal + espece + refuge)
$stmt = $pdo->prepare("
    SELECT a.id_animal, a.nom, a.statut, e.nom AS espece,
           COALESCE(r.nom, CONCAT('Particulier : ', a.detenteur_nom)) AS refuge,
           COALESCE(r.ville, a.detenteur_ville) AS ville
    FROM animal a
    JOIN espece e ON e.id_espece = a.id_espece
    LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
    WHERE a.id_animal = :id
");
$stmt->execute(['id' => $id]);
$animal = $stmt->fetch();

if (!$animal) { flash('Animal introuvable.', 'error'); redirect('recherche.php'); }
if ($animal['statut'] !== 'disponible') { flash('Cet animal n\'est plus disponible.', 'error'); redirect('animal.php?id=' . $id); }

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    if (strlen($message) < 10) {
        $erreurs[] = 'Merci d\'ecrire quelques mots (10 caracteres minimum) pour le refuge.';
    } else {
        // Empeche les demandes en double
        $check = $pdo->prepare("SELECT 1 FROM demande_adoption WHERE id_animal = :a AND id_utilisateur = :u");
        $check->execute(['a' => $id, 'u' => user()['id']]);
        if ($check->fetch()) {
            $erreurs[] = 'Vous avez deja envoye une demande pour cet animal.';
        } else {
            $pdo->prepare("INSERT INTO demande_adoption (id_animal, id_utilisateur, message)
                           VALUES (:a, :u, :m)")
                ->execute(['a' => $id, 'u' => user()['id'], 'm' => $message]);
            flash('Votre demande a bien ete envoyee au refuge !');
            redirect('mes-demandes.php');
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Adopter <?= e($animal['nom']) ?></h1>
    <p><?= e($animal['espece']) ?> · <?= e($animal['refuge']) ?> (<?= e($animal['ville']) ?>)</p></div></div>
<section>
    <div class="container">
        <form class="form-card" method="post">
            <?php foreach ($erreurs as $err): ?>
                <div class="flash flash-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            <p style="margin-bottom:18px;color:var(--texte-clair);">
                Presentez-vous au refuge et expliquez pourquoi vous souhaitez adopter <?= e($animal['nom']) ?>.
            </p>
            <div class="form-group">
                <label for="message">Votre message *</label>
                <textarea id="message" name="message" required><?= e($_POST['message'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn" style="width:100%;">Envoyer ma demande</button>
            <p style="text-align:center;margin-top:16px;"><a href="animal.php?id=<?= (int)$id ?>">Retour a la fiche</a></p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
