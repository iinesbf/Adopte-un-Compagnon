<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_role('refuge', 'admin', 'particulier');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// On recupere l'animal avec, s'il y en a, le proprietaire du refuge et le proprietaire particulier
$stmt = $pdo->prepare("SELECT a.id_animal, a.id_proprietaire, r.id_utilisateur AS refuge_user
                       FROM animal a
                       LEFT JOIN refuge r ON r.id_refuge = a.id_refuge
                       WHERE a.id_animal = :id");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('Annonce introuvable.', 'error');
    redirect('mes-annonces.php');
}

// Droit de suppression : admin, ou refuge proprietaire, ou particulier proprietaire
$uid = user()['id'];
$autorise = a_role('admin')
         || ((int) $row['refuge_user'] === $uid)
         || ((int) $row['id_proprietaire'] === $uid);

if (!$autorise) {
    http_response_code(403);
    flash('Acces refuse : cette annonce ne vous appartient pas.', 'error');
    redirect('mes-annonces.php');
}

$pdo->prepare("DELETE FROM animal WHERE id_animal = :id")->execute(['id' => $id]);
flash('Annonce supprimee.');
redirect('mes-annonces.php');
