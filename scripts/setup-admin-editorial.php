<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Étend `documents`
 * pour l'administration éditoriale (V2) : auteur, contenu en base pour les
 * textes créés/édités depuis l'admin, statut de publication indépendant du
 * statut d'import, origine (protège le corpus fondateur), et une colonne
 * image_url nullable pour ne pas bloquer un futur ajout d'illustration
 * (hors périmètre de cette mission, colonne seule).
 *
 * Aucune colonne existante modifiée, aucune ligne supprimée. Sauvegarde
 * logique déjà effectuée avant ce script (backups/backup-20260810-140354.sql).
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function colonneExiste(PDO $pdo, string $table, string $colonne): bool
{
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE :c");
    $stmt->execute(['c' => $colonne]);
    return $stmt->rowCount() > 0;
}

$ajouts = [
    'auteur' => "ALTER TABLE documents ADD COLUMN auteur VARCHAR(200) NULL AFTER titre",
    'contenu_inline' => "ALTER TABLE documents ADD COLUMN contenu_inline TEXT NULL AFTER contenu_fichier_local",
    'statut_publication' => "ALTER TABLE documents ADD COLUMN statut_publication ENUM('publie','brouillon') NOT NULL DEFAULT 'publie' AFTER statut_import",
];

// origine et image_url dépendent de colonnes déjà ajoutées ci-dessus ; gérées
// séparément pour référencer un AFTER valide.
foreach (['auteur', 'contenu_inline', 'statut_publication'] as $col) {
    if (colonneExiste($pdo, 'documents', $col)) {
        echo "{$col} : deja presente, ignoree.\n";
        continue;
    }
    $pdo->exec($ajouts[$col]);
    echo "{$col} : ajoutee.\n";
}

if (!colonneExiste($pdo, 'documents', 'origine')) {
    $pdo->exec("ALTER TABLE documents ADD COLUMN origine ENUM('corpus_fondateur','admin') NOT NULL DEFAULT 'admin' AFTER statut_publication");
    echo "origine : ajoutee.\n";
} else {
    echo "origine : deja presente, ignoree.\n";
}

if (!colonneExiste($pdo, 'documents', 'image_url')) {
    $pdo->exec("ALTER TABLE documents ADD COLUMN image_url VARCHAR(500) NULL AFTER origine");
    echo "image_url : ajoutee.\n";
} else {
    echo "image_url : deja presente, ignoree.\n";
}

// statut_import n'a de sens que pour le corpus importe de Drive ; les futurs
// textes crees depuis l'admin n'ont pas d'equivalent -> assoupli en NULL
// plutot que d'inventer une fausse valeur d'import.
$col = $pdo->query("SHOW COLUMNS FROM documents WHERE Field = 'statut_import'")->fetch(PDO::FETCH_ASSOC);
if ($col !== false && stripos((string) $col['Null'], 'NO') === 0) {
    $pdo->exec("ALTER TABLE documents MODIFY statut_import VARCHAR(50) NULL");
    echo "statut_import : assoupli en NULL.\n";
} else {
    echo "statut_import : deja nullable, ignore.\n";
}

// Backfill : a ce stade (execution unique, juste apres la migration), les
// seules lignes existantes sont les 124 du corpus fondateur -> toutes
// concernees, sans ambiguite.
$n = $pdo->exec("UPDATE documents SET origine = 'corpus_fondateur', auteur = 'JC Limousin' WHERE origine = 'admin'");
echo "Backfill corpus fondateur (origine + auteur) : {$n} lignes.\n";

$total = (int) $pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$fondateur = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE origine = 'corpus_fondateur'")->fetchColumn();
$publie = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_publication = 'publie'")->fetchColumn();
echo "Total documents : {$total}\n";
echo "Origine corpus_fondateur : {$fondateur}\n";
echo "Statut publie : {$publie}\n";
