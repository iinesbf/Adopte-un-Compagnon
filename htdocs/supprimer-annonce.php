<?php
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/includes/auth.php';
exiger_role('refuge', 'admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Verifie l'existence et la propriete avant suppression
$stmt = $pdo->prepare("SELECT a.id_animal, r.id_utilisateur
                       FROM animal a JOIN refuge r ON r.id_refuge = a.id_refuge
                       WHERE a.id_animal = :id");
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    flash('Annonce introuvable.', 'error');
    redirect('mes-annonces.php');
}
if (!a_role('admin') && (int) $row['id_utilisateur'] !== user()['id']) {
    http_response_code(403);
    flash('Acces refuse : cette annonce ne vous appartient pas.', 'error');
    redirect('mes-annonces.php');
}

$pdo->prepare("DELETE FROM animal WHERE id_animal = :id")->execute(['id' => $id]);
flash('Annonce supprimee.');
redirect('mes-annonces.php');
