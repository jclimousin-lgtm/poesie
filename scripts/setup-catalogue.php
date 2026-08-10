<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer immédiatement.
 * Crée la table `documents` (CATALOGUE) et l'alimente depuis catalogue.json
 * (produit hors ligne à partir de l'inventaire Google Drive). Ne touche à
 * aucun fichier Google Drive. Lit les identifiants depuis un fichier de
 * config hors docroot, jamais affichés.
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\Throwable $e) {
    http_response_code(500);
    echo "ERREUR connexion DB : " . $e->getMessage() . "\n";
    exit(1);
}

$pdo->exec("CREATE TABLE IF NOT EXISTS documents (
    id_interne VARCHAR(20) PRIMARY KEY,
    titre VARCHAR(500) NOT NULL,
    chemin_drive VARCHAR(500) NOT NULL,
    dossier_parent VARCHAR(200) NOT NULL,
    type_fichier VARCHAR(100) NOT NULL,
    id_drive VARCHAR(100) NOT NULL,
    lien_drive VARCHAR(500) NOT NULL,
    date_modification VARCHAR(50) NOT NULL,
    taille_octets BIGINT NULL,
    categorie VARCHAR(200) NOT NULL,
    contenu_fichier_local VARCHAR(200) NULL,
    statut_import VARCHAR(50) NOT NULL,
    anomalie TEXT NOT NULL,
    doublon_info TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$jsonPath = __DIR__ . '/../poesie-corpus-prive/catalogue.json';
if (!is_file($jsonPath)) {
    http_response_code(500);
    echo "ERREUR : catalogue.json introuvable a {$jsonPath}\n";
    exit(1);
}

$rows = json_decode((string) file_get_contents($jsonPath), true);
if (!is_array($rows)) {
    http_response_code(500);
    echo "ERREUR : catalogue.json illisible\n";
    exit(1);
}

$stmt = $pdo->prepare(
    "REPLACE INTO documents
    (id_interne, titre, chemin_drive, dossier_parent, type_fichier, id_drive, lien_drive, date_modification, taille_octets, categorie, contenu_fichier_local, statut_import, anomalie, doublon_info)
    VALUES (:id_interne,:titre,:chemin_drive,:dossier_parent,:type_fichier,:id_drive,:lien_drive,:date_modification,:taille_octets,:categorie,:contenu_fichier_local,:statut_import,:anomalie,:doublon_info)"
);

$count = 0;
foreach ($rows as $r) {
    $taille = ($r['taille_octets'] ?? '') !== '' ? (int) $r['taille_octets'] : null;
    $doublon = $r['doublon_info'] ?? null;

    $stmt->execute([
        'id_interne' => $r['id_interne'],
        'titre' => $r['titre'],
        'chemin_drive' => $r['chemin_drive'],
        'dossier_parent' => $r['dossier_parent'],
        'type_fichier' => $r['type_fichier'],
        'id_drive' => $r['id_drive'],
        'lien_drive' => $r['lien_drive'],
        'date_modification' => $r['date_modification'],
        'taille_octets' => $taille,
        'categorie' => $r['categorie'],
        'contenu_fichier_local' => $r['contenu_fichier_local'],
        'statut_import' => $r['statut_import'],
        'anomalie' => $r['anomalie'] ?? '',
        'doublon_info' => $doublon !== null ? json_encode($doublon, JSON_UNESCAPED_UNICODE) : null,
    ]);
    $count++;
}

echo "OK - {$count} lignes inserees/mises a jour.\n";
$total = (int) $pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
echo "Total en base : {$total}\n";

$parStatut = $pdo->query('SELECT statut_import, COUNT(*) AS n FROM documents GROUP BY statut_import')->fetchAll(PDO::FETCH_KEY_PAIR);
foreach ($parStatut as $statut => $n) {
    echo "  {$statut} : {$n}\n";
}
