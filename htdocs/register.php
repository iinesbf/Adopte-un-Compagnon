<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
$titre = 'Inscription';

if (est_connecte()) redirect('index.php');

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $mdp    = $_POST['mot_de_passe'] ?? '';
    $mdp2   = $_POST['mot_de_passe2'] ?? '';
    $role   = in_array($_POST['role'] ?? '', ['refuge', 'particulier'], true) ? $_POST['role'] : 'visiteur';
    $nom_refuge   = trim($_POST['nom_refuge'] ?? '');
    $ville_refuge = trim($_POST['ville_refuge'] ?? '');

    // Validation des donnees
    if ($nom === '' || $prenom === '')          $erreurs[] = 'Le nom et le prenom sont obligatoires.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Adresse email invalide.';
    if (strlen($mdp) < 6)                        $erreurs[] = 'Le mot de passe doit faire au moins 6 caracteres.';
    if ($mdp !== $mdp2)                          $erreurs[] = 'Les deux mots de passe ne correspondent pas.';
    if ($role === 'refuge' && ($nom_refuge === '' || $ville_refuge === '')) {
        $erreurs[] = 'Pour un compte refuge, le nom et la ville du refuge sont obligatoires.';
    }

    if (!$erreurs) {
        // Email deja utilise ?
        $check = $pdo->prepare("SELECT 1 FROM utilisateur WHERE email = :email");
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $erreurs[] = 'Cette adresse email est deja utilisee.';
        } else {
            $ins = $pdo->prepare("
                INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role)
                VALUES (:nom, :prenom, :email, :mdp, :role)
            ");
            $ins->execute([
                'nom'    => $nom,
                'prenom' => $prenom,
                'email'  => $email,
                'mdp'    => password_hash($mdp, PASSWORD_DEFAULT),
                'role'   => $role,
            ]);
            $idUser = $pdo->lastInsertId();

            // Compte refuge : on cree son refuge "en attente de validation" (valide=0)
            if ($role === 'refuge') {
                $pdo->prepare("INSERT INTO refuge (nom, ville, valide, id_utilisateur)
                               VALUES (:nom, :ville, 0, :uid)")
                    ->execute(['nom' => $nom_refuge, 'ville' => $ville_refuge, 'uid' => $idUser]);
            }

            $u = ['id_utilisateur' => $idUser, 'nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'role' => $role];
            connecter($u);
            if ($role === 'refuge') {
                flash('Compte refuge cree ! Votre refuge "' . $nom_refuge . '" doit etre valide par un administrateur avant de pouvoir publier des annonces.');
            } elseif ($role === 'particulier') {
                flash('Compte particulier cree ! Vous pouvez des maintenant proposer vos animaux a l\'adoption depuis « Mes annonces ».');
            } else {
                flash('Compte cree avec succes. Bienvenue ' . $prenom . ' !');
            }
            redirect('index.php');
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Creer un compte</h1></div></div>
<section>
    <div class="container">
        <form class="form-card" method="post" action="register.php">
            <?php foreach ($erreurs as $err): ?>
                <div class="flash flash-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            <div class="form-group">
                <label for="prenom">Prenom</label>
                <input type="text" id="prenom" name="prenom" required value="<?= e($_POST['prenom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required value="<?= e($_POST['nom'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="role">Type de compte</label>
                <select id="role" name="role" onchange="document.getElementById('refuge-fields').style.display = this.value==='refuge' ? 'block' : 'none';">
                    <option value="visiteur">Adoptant (je souhaite adopter)</option>
                    <option value="particulier" <?= ($_POST['role'] ?? '') === 'particulier' ? 'selected' : '' ?>>Particulier (je propose mon animal)</option>
                    <option value="refuge" <?= ($_POST['role'] ?? '') === 'refuge' ? 'selected' : '' ?>>Refuge (je publie des annonces)</option>
                </select>
            </div>
            <div id="refuge-fields" style="display:<?= ($_POST['role'] ?? '') === 'refuge' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="nom_refuge">Nom du refuge</label>
                    <input type="text" id="nom_refuge" name="nom_refuge" value="<?= e($_POST['nom_refuge'] ?? '') ?>" placeholder="Ex : Refuge de la Patte">
                </div>
                <div class="form-group">
                    <label for="ville_refuge">Ville du refuge</label>
                    <input type="text" id="ville_refuge" name="ville_refuge" value="<?= e($_POST['ville_refuge'] ?? '') ?>" placeholder="Ex : Paris">
                </div>
                <p style="font-size:.85rem;color:var(--texte-clair);margin-bottom:14px;">
                    Votre refuge devra etre valide par un administrateur avant de pouvoir publier des annonces.
                </p>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="form-group">
                <label for="mot_de_passe2">Confirmer le mot de passe</label>
                <input type="password" id="mot_de_passe2" name="mot_de_passe2" required>
            </div>
            <button type="submit" class="btn" style="width:100%;">Creer mon compte</button>
            <p style="text-align:center; margin-top:18px;">
                Deja inscrit ? <a href="login.php">Connectez-vous</a>
            </p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
