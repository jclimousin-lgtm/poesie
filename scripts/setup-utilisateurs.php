<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Crée la table
 * `users` (comptes de l'administration éditoriale, distincts des visiteurs
 * du site public qui n'ont besoin d'aucun compte) et amorce le premier
 * compte administrateur. Remplace l'authentification HTTP Basic partagée
 * (.htaccess / .htpasswd, un seul mot de passe pour tout le monde, aucun
 * rôle) par des comptes nominatifs avec rôle (`admin` = contrôle total,
 * `lecteur` = lecture seule) — demande explicite de l'utilisateur
 * (2026-08-11) : "niveaux users admin: full control ... user: reader only".
 *
 * Idempotent : peut être relancé sans casser une installation existante
 * (CREATE TABLE IF NOT EXISTS, seed uniquement si aucun compte n'existe).
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(60) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('admin','lecteur') NOT NULL DEFAULT 'lecteur',
        cree_le DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);
echo "table users : ok (creee ou deja presente)\n";

$nb = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($nb === 0) {
    // Premier compte demande explicitement par l'utilisateur : id "admin",
    // mot de passe "ClefAdmin" (identique en esprit a la cle ClefAdmin deja
    // utilisee sur le Portail Politicorama et d'autres projets de ce compte
    // -- modifiable ensuite depuis l'interface, page "Mon compte").
    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)');
    $stmt->execute([
        'u' => 'admin',
        'p' => password_hash('ClefAdmin', PASSWORD_DEFAULT),
        'r' => 'admin',
    ]);
    echo "compte admin initial : cree (username=admin, role=admin)\n";
} else {
    echo "compte(s) deja present(s) ({$nb}) : seed ignore\n";
}

$total = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "Total comptes users : {$total}\n";
