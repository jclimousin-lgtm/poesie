<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Ajoute les tables
 * de structure éditoriale (categories, series, tables de liaison) à la base
 * existante `nare8592_poesie`, peuplées mécaniquement depuis
 * arborescence.json (transcription directe de l'arborescence éditoriale
 * déjà validée hors de cette mission) — aucune analyse ni décision
 * éditoriale prise ici. Ne touche jamais à la table `documents` existante.
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    slug VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    ordre INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS document_categories (
    id_interne VARCHAR(20) NOT NULL,
    categorie_slug VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_interne, categorie_slug),
    FOREIGN KEY (id_interne) REFERENCES documents(id_interne),
    FOREIGN KEY (categorie_slug) REFERENCES categories(slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS series (
    id VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS document_series (
    id_interne VARCHAR(20) NOT NULL,
    serie_id VARCHAR(50) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id_interne, serie_id),
    FOREIGN KEY (id_interne) REFERENCES documents(id_interne),
    FOREIGN KEY (serie_id) REFERENCES series(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$data = json_decode((string) file_get_contents(__DIR__ . '/arborescence.json'), true);

$stmtCat = $pdo->prepare('REPLACE INTO categories (slug, nom, ordre) VALUES (:slug, :nom, :ordre)');
foreach ($data['categories'] as $c) {
    $stmtCat->execute(['slug' => $c['slug'], 'nom' => $c['nom'], 'ordre' => $c['ordre']]);
}
echo count($data['categories']) . " categories.\n";

$stmtDocCat = $pdo->prepare('REPLACE INTO document_categories (id_interne, categorie_slug) VALUES (:id, :slug)');
$nDocCat = 0;
foreach ($data['document_categories'] as $slug => $ids) {
    foreach ($ids as $id) {
        $stmtDocCat->execute(['id' => $id, 'slug' => $slug]);
        $nDocCat++;
    }
}
echo "{$nDocCat} liaisons document_categories.\n";

$stmtSerie = $pdo->prepare('REPLACE INTO series (id, nom) VALUES (:id, :nom)');
$stmtDocSerie = $pdo->prepare('REPLACE INTO document_series (id_interne, serie_id, position) VALUES (:id_interne, :serie_id, :position)');
$nSerie = 0;
foreach ($data['series'] as $s) {
    $stmtSerie->execute(['id' => $s['id'], 'nom' => $s['nom']]);
    foreach ($s['membres'] as $i => $id) {
        $stmtDocSerie->execute(['id_interne' => $id, 'serie_id' => $s['id'], 'position' => $i + 1]);
        $nSerie++;
    }
}
echo count($data['series']) . " series, {$nSerie} liaisons document_series.\n";

echo "OK.\n";
