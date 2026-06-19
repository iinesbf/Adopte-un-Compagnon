<?php
/**
 * connexion.php
 * Parametres de connexion a la base de donnees (un SEUL endroit, comme demande).
 * Utilise PDO.
 *
 * Le fichier detecte automatiquement l'environnement :
 *   - en LOCAL (XAMPP, localhost)      -> base "adoption", user root
 *   - EN LIGNE (InfinityFree)          -> base if0_..., hostname sqlXXX...
 * Le meme fichier fonctionne donc aux deux endroits, sans rien changer.
 */

$host = $_SERVER['HTTP_HOST'] ?? '';
// On considere "en ligne" UNIQUEMENT le domaine d'hebergement InfinityFree.
// Tout le reste (localhost, 127.0.0.1, nom de machine, IP, sous-dossier XAMPP...) = LOCAL.
$enLigne = (strpos($host, 'infinityfree') !== false)
        || (strpos($host, 'epizy') !== false)
        || (strpos($host, '.site.je') !== false);

if (!$enLigne) {
    // ---- Environnement LOCAL (XAMPP) ----
    $DB_HOST = 'localhost';
    $DB_NAME = 'adoption';
    $DB_USER = 'root';
    $DB_PASS = '';                 // XAMPP : mot de passe root vide par defaut
    $DB_PORT = '3306';
} else {
    // ---- Environnement EN LIGNE (InfinityFree - compte if0_42217380) ----
    $DB_HOST = 'sql311.infinityfree.com';
    $DB_NAME = 'if0_42217380_adoption';
    $DB_USER = 'if0_42217380';
    $DB_PASS = 'dHFQUZVTrLe';
    $DB_PORT = '3306';
}

try {
    $pdo = new PDO(
        'mysql:host=' . $DB_HOST . ';port=' . $DB_PORT . ';dbname=' . $DB_NAME . ';charset=utf8mb4',
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('Erreur de connexion a la base de donnees : ' . htmlspecialchars($e->getMessage()));
}
