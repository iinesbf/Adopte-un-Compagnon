<?php
require_once __DIR__ . '/auth.php';
$titre = $titre ?? 'Adopte un Compagnon';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titre) ?></title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="css/style.css?v=7">
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo">Adopte un <span>Compagnon</span></a>
        <nav>
            <a href="index.php">Accueil</a>
            <a href="recherche.php">Adopter</a>
            <a href="index.php#refuges">Refuges</a>
            <?php if (est_connecte()): ?>
                <?php if (a_role('admin')): ?>
                    <a href="admin.php">Administration</a>
                <?php elseif (a_role('refuge', 'particulier')): ?>
                    <a href="mes-annonces.php">Mes annonces</a>
                <?php endif; ?>
                <a href="mes-demandes.php">Mes demandes</a>
                <a href="logout.php" class="btn btn-sm">Deconnexion (<?= e(user()['prenom']) ?>)</a>
            <?php else: ?>
                <a href="login.php">Connexion</a>
                <a href="register.php" class="btn btn-sm">Inscription</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php foreach (get_flashes() as $f): ?>
    <div class="container" style="padding-top:18px;">
        <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    </div>
<?php endforeach; ?>
