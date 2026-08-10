<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Sauvegarde logique
 * (dump SQL) des tables du corpus avant la mission Design V2 + Administration
 * éditoriale — jamais de modification, lecture seule. Écrit hors docroot.
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$tables = ['documents', 'categories', 'document_categories', 'series', 'document_series', 'soumissions'];
$horodatage = date('Ymd-His');
$dossierBackup = __DIR__ . '/../poesie-corpus-prive/backups';
if (!is_dir($dossierBackup)) {
    mkdir($dossierBackup, 0770, true);
}
$cheminSortie = "{$dossierBackup}/backup-{$horodatage}.sql";
$handle = fopen($cheminSortie, 'w');

foreach ($tables as $table) {
    $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
    fwrite($handle, "-- Table {$table}\n");
    fwrite($handle, "DROP TABLE IF EXISTS `{$table}_restauration_test`;\n");
    fwrite($handle, str_replace("CREATE TABLE `{$table}`", "CREATE TABLE `{$table}_restauration_test`", $create['Create Table']) . ";\n\n");

    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $colonnes = array_keys($row);
        $valeurs = array_map(static function ($v) use ($pdo) {
            return $v === null ? 'NULL' : $pdo->quote((string) $v);
        }, array_values($row));
        fwrite($handle, "INSERT INTO `{$table}_restauration_test` (`" . implode('`,`', $colonnes) . "`) VALUES (" . implode(',', $valeurs) . ");\n");
    }
    fwrite($handle, "\n");
    echo "{$table} : " . count($rows) . " lignes sauvegardees.\n";
}

fclose($handle);
echo "Fichier ecrit : {$cheminSortie}\n";
echo "Taille : " . filesize($cheminSortie) . " octets\n";
