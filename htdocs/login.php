<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
$titre = 'Connexion';

if (est_connecte()) redirect('index.php');

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $mdp   = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $mdp === '') {
        $erreurs[] = 'Veuillez renseigner votre email et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $u = $stmt->fetch();

        if ($u && password_verify($mdp, $u['mot_de_passe'])) {
            connecter($u);
            flash('Bienvenue ' . $u['prenom'] . ' !');
            redirect('index.php');
        } else {
            $erreurs[] = 'Email ou mot de passe incorrect.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<div class="page-head"><div class="container"><h1>Connexion</h1></div></div>
<section>
    <div class="container">
        <form class="form-card" method="post" action="login.php">
            <?php foreach ($erreurs as $err): ?>
                <div class="flash flash-error"><?= e($err) ?></div>
            <?php endforeach; ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn" style="width:100%;">Se connecter</button>
            <p style="text-align:center; margin-top:18px;">
                Pas encore de compte ? <a href="register.php">Inscrivez-vous</a>
            </p>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
