<?php
/**
 * functions.php — fonctions utilitaires partagees.
 */

/** Echappe une valeur pour un affichage HTML securise (anti-XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Demarre la session si ce n'est pas deja fait. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/** Redirige vers une page puis stoppe le script. */
function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

/** Stocke un message flash (affiche une seule fois). */
function flash(string $message, string $type = 'success'): void
{
    start_session();
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

/** Recupere et vide les messages flash. */
function get_flashes(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Libelle lisible d'un statut d'animal. */
function statut_label(string $statut): string
{
    switch ($statut) {
        case 'disponible': return 'Disponible';
        case 'reserve':    return 'Reserve';
        case 'adopte':     return 'Adopte';
        default:           return $statut;
    }
}

/** Libelle lisible du sexe. */
function sexe_label(?string $sexe): string
{
    switch ($sexe) {
        case 'M': return 'Male';
        case 'F': return 'Femelle';
        default:  return 'Non precise';
    }
}
