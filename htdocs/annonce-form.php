<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_role('refuge', 'admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$edition = $id > 0;
$titre = $edition ? 'Modifier une annonce' : 'Nouvelle annonce';

// Refuges accessibles : admin = tous ; refuge = uniquement les SIENS et VALIDES
if (a_role('admin')) {
    $refuges = $pdo->query("SELECT id_refuge, nom FROM refuge ORDER BY nom")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id_refuge, nom FROM refuge WHERE id_utilisateur = :uid AND valide = 1 ORDER BY nom");
    $stmt->execute(['uid' => user()['id']]);
    $refuges = $stmt->fetchAll();
}
$especes = $pdo->query("SELECT id_espece, nom FROM espece ORDER BY nom")->fetchAll();

// Valeurs par defaut
$animal = [
    'nom' => '', 'id_espece' => '', 'id_refuge' => '', 'race' => '',
    'age_annees' => '', 'sexe' => '', 'sterilise' => 0, 'ancien_proprietaire' => '',
    'description' => '', 'statut' => 'disponible', 'photo' => null,
];

// En edition : charger l'animal (et verifier la propriete)
if ($edition) {
    $stmt = $pdo->prepare("SELECT a.* FROM animal a JOIN refuge r ON r.id_refuge = a.id_refuge WHERE a.id_animal = :id");
    $stmt->execute(['id' => $id]);
    $animal = $stmt->fetch();
    if (!$animal) { flash('Annonce introuvable.', 'error'); redirect('mes-annonces.php'); }
    // Le refuge ne peut editer que ses propres animaux
    if (!a_role('admin')) {
        $own = $pdo->prepare("SELECT 1 FROM refuge WHERE id_refuge = :r AND id_utilisateur = :uid");
        $own->execute(['r' => $animal['id_refuge'], 'uid' => user()['id']]);
        if (!$own->fetch()) { http_response_code(403); flash('Acces refuse.', 'error'); redirect('mes-annonces.php'); }
    }
}

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal = [
        'nom'        => trim($_POST['nom'] ?? ''),
        'id_espece'  => (int) ($_POST['id_espece'] ?? 0),
        'id_refuge'  => (int) ($_POST['id_refuge'] ?? 0),
        'race'       => trim($_POST['race'] ?? ''),
        'age_annees' => $_POST['age_annees'] !== '' ? (int) $_POST['age_annees'] : null,
        'sexe'       => in_array($_POST['sexe'] ?? '', ['M', 'F'], true) ? $_POST['sexe'] : null,
        'sterilise'  => isset($_POST['sterilise']) ? 1 : 0,
        'ancien_proprietaire' => trim($_POST['ancien_proprietaire'] ?? ''),
        'description'=> trim($_POST['description'] ?? ''),
        'statut'     => in_array($_POST['statut'] ?? '', ['disponible','reserve','adopte'], true) ? $_POST['statut'] : 'disponible',
    ];

    // Validation
    if ($animal['nom'] === '')        $erreurs[] = 'Le nom est obligatoire.';
    if (!$animal['id_espece'])        $erreurs[] = 'Veuillez choisir une espece.';
    if (!$animal['id_refuge'])        $erreurs[] = 'Veuillez choisir un refuge.';
    if ($animal['age_annees'] !== null && $animal['age_annees'] < 0) $erreurs[] = 'Age invalide.';

    // Verifie que le refuge choisi appartient bien a l'utilisateur (sauf admin)
    if (!a_role('admin') && $animal['id_refuge']) {
        $own = $pdo->prepare("SELECT 1 FROM refuge WHERE id_refuge = :r AND id_utilisateur = :uid AND valide = 1");
        $own->execute(['r' => $animal['id_refuge'], 'uid' => user()['id']]);
        if (!$own->fetch()) $erreurs[] = 'Refuge invalide.';
    }

    // Gestion de la photo : on garde l'existante en edition, on remplace si nouvel upload
    if ($edition) {
        $cur = $pdo->prepare("SELECT photo FROM animal WHERE id_animal = :id");
        $cur->execute(['id' => $id]);
        $animal['photo'] = $cur->fetchColumn() ?: null;
    }
    if (!empty($_FILES['photo']['name']) && (int)($_FILES['photo']['error'] ?? 4) === UPLOAD_ERR_OK) {
        $f = $_FILES['photo'];
        $info = @getimagesize($f['tmp_name']);
        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if (!$info || !isset($types[$info['mime']])) {
            $erreurs[] = 'La photo doit etre une image (jpg, png, webp ou gif).';
        } elseif ($f['size'] > 3 * 1024 * 1024) {
            $erreurs[] = 'La photo est trop lourde (max 3 Mo).';
        } else {
            $dir = __DIR__ . '/img/uploads';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $fname = 'animal_' . uniqid() . '.' . $types[$info['mime']];
            if (move_uploaded_file($f['tmp_name'], $dir . '/' . $fname)) {
                $animal['photo'] = 'img/uploads/' . $fname;
            } else {
                $erreurs[] = 'Echec de l\'enregistrement de la photo.';
            }
        }
    }

    if (!$erreurs) {
        if ($edition) {
            $sql = "UPDATE animal SET nom=:nom, id_espece=:id_espece, id_refuge=:id_refuge,
                       race=:race, age_annees=:age, sexe=:sexe, sterilise=:sterilise,
                       ancien_proprietaire=:ancien, description=:desc, statut=:statut, photo=:photo
                    WHERE id_animal=:id";
            $params = ['id' => $id];
        } else {
            $sql = "INSERT INTO animal (nom, id_espece, id_refuge, race, age_annees, sexe,
                                        sterilise, ancien_proprietaire, description, statut, photo)
                    VALUES (:nom, :id_espece, :id_refuge, :race, :age, :sexe,
                            :sterilise, :ancien, :desc, :statut, :photo)";
            $params = [];
        }
        $params += [
            'nom' => $animal['nom'], 'id_espece' => $animal['id_espece'], 'id_refuge' => $animal['id_refuge'],
            'race' => $animal['race'] ?: null, 'age' => $animal['age_annees'], 'sexe' => $animal['sexe'],
            'sterilise' => $animal['sterilise'], 'ancien' => $animal['ancien_proprietaire'] ?: null,
            'desc' => $animal['description'] ?: null, 'statut' => $animal['statut'],
            'photo' => $animal['photo'] ?: null,
        ];
        $pdo->prepare($sql)->execute($params);
        flash($edition ? 'Annonce modifiee.' : 'Annonce publiee avec succes.');
        redirect('mes-annonces.php');
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1><?= e($titre) ?></h1></div></div>
<section>
    <div class="container">
        <?php if (!$refuges): ?>
            <div class="form-card">
                <p>Vous n'avez pas (encore) de refuge <strong>valide</strong>.</p>
                <p style="color:var(--texte-clair);margin-top:10px;">
                    Votre refuge est en <strong>attente de validation</strong> par un administrateur.
                    Une fois valide, vous pourrez publier des annonces ici.
                </p>
            </div>
        <?php else: ?>
        <form class="form-card" method="post" enctype="multipart/form-data">
            <?php foreach ($erreurs as $err): ?>
                <div class="flash flash-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            <div class="form-group">
                <label for="nom">Nom de l'animal *</label>
                <input type="text" id="nom" name="nom" required value="<?= e($animal['nom']) ?>">
            </div>
            <div class="form-group">
                <label for="id_espece">Espece *</label>
                <select id="id_espece" name="id_espece" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($especes as $esp): ?>
                        <option value="<?= (int)$esp['id_espece'] ?>" <?= (int)$animal['id_espece'] === (int)$esp['id_espece'] ? 'selected' : '' ?>>
                            <?= e($esp['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_refuge">Refuge *</label>
                <select id="id_refuge" name="id_refuge" required>
                    <option value="">— Choisir —</option>
                    <?php foreach ($refuges as $r): ?>
                        <option value="<?= (int)$r['id_refuge'] ?>" <?= (int)$animal['id_refuge'] === (int)$r['id_refuge'] ? 'selected' : '' ?>>
                            <?= e($r['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="race">Race</label>
                <input type="text" id="race" name="race" value="<?= e($animal['race']) ?>">
            </div>
            <div class="form-group">
                <label for="age_annees">Age (annees)</label>
                <input type="number" id="age_annees" name="age_annees" min="0" max="40" value="<?= e($animal['age_annees']) ?>">
            </div>
            <div class="form-group">
                <label for="sexe">Sexe</label>
                <select id="sexe" name="sexe">
                    <option value="">Non precise</option>
                    <option value="M" <?= $animal['sexe'] === 'M' ? 'selected' : '' ?>>Male</option>
                    <option value="F" <?= $animal['sexe'] === 'F' ? 'selected' : '' ?>>Femelle</option>
                </select>
            </div>
            <div class="form-group">
                <label for="ancien_proprietaire">Ancien proprietaire</label>
                <input type="text" id="ancien_proprietaire" name="ancien_proprietaire"
                       value="<?= e($animal['ancien_proprietaire']) ?>" placeholder="Ex : Famille Martin, abandon...">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="sterilise" value="1" style="width:auto;margin-right:8px;"
                           <?= $animal['sterilise'] ? 'checked' : '' ?>>
                    Animal sterilise / castre
                </label>
            </div>
            <div class="form-group">
                <label for="statut">Statut</label>
                <select id="statut" name="statut">
                    <option value="disponible" <?= $animal['statut'] === 'disponible' ? 'selected' : '' ?>>Disponible</option>
                    <option value="reserve"    <?= $animal['statut'] === 'reserve' ? 'selected' : '' ?>>Reserve</option>
                    <option value="adopte"     <?= $animal['statut'] === 'adopte' ? 'selected' : '' ?>>Adopte</option>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">Photo de l'animal</label>
                <?php if (!empty($animal['photo'])): ?>
                    <img src="<?= e($animal['photo']) ?>" alt="photo actuelle"
                         style="max-width:160px;border-radius:12px;margin-bottom:8px;display:block;">
                    <small style="color:var(--texte-clair)">Laissez vide pour conserver la photo actuelle.</small>
                <?php endif; ?>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= e($animal['description']) ?></textarea>
            </div>
            <button type="submit" class="btn" style="width:100%;"><?= $edition ? 'Enregistrer les modifications' : 'Publier l\'annonce' ?></button>
            <p style="text-align:center;margin-top:16px;"><a href="mes-annonces.php">Annuler</a></p>
        </form>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
