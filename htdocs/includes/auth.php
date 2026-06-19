<?php
/**
 * auth.php — gestion de l'authentification et des roles via session.
 */
require_once __DIR__ . '/functions.php';

start_session();

/** Utilisateur connecte ? */
function est_connecte(): bool
{
    return isset($_SESSION['user']);
}

/** Donnees de l'utilisateur connecte (ou null). */
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Role de l'utilisateur connecte (ou null). */
function role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

/** Verifie que l'utilisateur a l'un des roles donnes. */
function a_role(string ...$roles): bool
{
    return est_connecte() && in_array($_SESSION['user']['role'], $roles, true);
}

/** Exige une connexion ; sinon redirige vers le login. */
function exiger_connexion(): void
{
    if (!est_connecte()) {
        flash('Vous devez etre connecte pour acceder a cette page.', 'error');
        redirect('login.php');
    }
}

/** Exige l'un des roles donnes ; sinon bloque. */
function exiger_role(string ...$roles): void
{
    exiger_connexion();
    if (!a_role(...$roles)) {
        http_response_code(403);
        flash('Acces refuse : droits insuffisants.', 'error');
        redirect('index.php');
    }
}

/** Connecte l'utilisateur (stocke ses infos en session). */
function connecter(array $utilisateur): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'     => (int) $utilisateur['id_utilisateur'],
        'nom'    => $utilisateur['nom'],
        'prenom' => $utilisateur['prenom'],
        'email'  => $utilisateur['email'],
        'role'   => $utilisateur['role'],
    ];
}

/** Deconnecte l'utilisateur. */
function deconnecter(): void
{
    $_SESSION = [];
    session_destroy();
}
