<?php

declare(strict_types=1);

/**
 * SCRIPT TEMPORAIRE, à exécuter UNE FOIS puis supprimer. Affecte les
 * décisions éditoriales déjà validées (POESIE-CATEGORIE-PROPOSITION.md,
 * A-DECOUVRIR-PROPOSITION.md) aux catégories `poesie` et `a-decouvrir`
 * déjà existantes dans `document_categories`. Aucune nouvelle table,
 * aucune nouvelle catégorie, aucune analyse : simple transcription.
 */

header('Content-Type: text/plain; charset=utf-8');

$config = require __DIR__ . '/../poesie-config-prive/db.php';
$pdo = new PDO(
    "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
    $config['user'],
    $config['pass'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$poesie = [
    'INV-0039','INV-0041','INV-0043','INV-0044','INV-0045','INV-0046','INV-0047','INV-0048',
    'INV-0049','INV-0050','INV-0051','INV-0052','INV-0053','INV-0054','INV-0055','INV-0056',
    'INV-0057','INV-0058','INV-0059','INV-0060','INV-0062','INV-0063','INV-0064','INV-0104',
    'INV-0105','INV-0106','INV-0107','INV-0108','INV-0109','INV-0110','INV-0111','INV-0112',
    'INV-0113','INV-0114','INV-0115','INV-0116',
];

$aDecouvrir = [
    'INV-0034','INV-0048','INV-0058','INV-0068','INV-0077','INV-0078','INV-0088','INV-0090',
    'INV-0103','INV-0108','INV-0111',
];

// Garde-fous explicites de la mission.
$interdits = array_merge(
    ['INV-0037'],
    array_map(static fn(int $n) => sprintf('INV-%04d', $n), range(117, 123))
);

foreach (array_merge($poesie, $aDecouvrir) as $id) {
    if (in_array($id, $interdits, true)) {
        throw new RuntimeException("id interdit detecte dans les listes fournies : {$id}");
    }
}
if (in_array('INV-0015', $aDecouvrir, true)) {
    throw new RuntimeException('INV-0015 ne doit jamais etre affecte a a-decouvrir');
}

$stmt = $pdo->prepare('REPLACE INTO document_categories (id_interne, categorie_slug) VALUES (:id, :slug)');

$n = 0;
foreach ($poesie as $id) {
    $stmt->execute(['id' => $id, 'slug' => 'poesie']);
    $n++;
}
echo "{$n} affectations -> poesie (attendu 36)\n";

$n = 0;
foreach ($aDecouvrir as $id) {
    $stmt->execute(['id' => $id, 'slug' => 'a-decouvrir']);
    $n++;
}
echo "{$n} affectations -> a-decouvrir (attendu 11)\n";

$total = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE categorie_slug = 'poesie'")->fetchColumn();
echo "Total en base categorie poesie : {$total}\n";
$total = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE categorie_slug = 'a-decouvrir'")->fetchColumn();
echo "Total en base categorie a-decouvrir : {$total}\n";

$check15 = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE id_interne = 'INV-0015' AND categorie_slug = 'a-decouvrir'")->fetchColumn();
echo "Verification INV-0015 absent de a-decouvrir : " . ($check15 === 0 ? 'OK' : 'ECHEC') . "\n";
