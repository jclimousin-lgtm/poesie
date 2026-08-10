<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Ajoute la table
 * `soumissions` (contributions externes) à la base existante
 * `nare8592_poesie`. Ne touche jamais `documents`/`categories`/`series`.
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS soumissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auteur_nom VARCHAR(200) NOT NULL,
    titre VARCHAR(500) NOT NULL,
    contenu TEXT NOT NULL,
    email VARCHAR(255) NOT NULL,
    declaration_autorisation TINYINT(1) NOT NULL DEFAULT 0,
    statut ENUM('a_valider','publie','refuse') NOT NULL DEFAULT 'a_valider',
    date_soumission DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "OK - table soumissions prete.\n";
$n = (int) $pdo->query('SELECT COUNT(*) FROM soumissions')->fetchColumn();
echo "Lignes existantes : {$n}\n";
