<?php

declare(strict_types=1);

require __DIR__ . '/_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$id = trim((string) ($_GET['id'] ?? ''));

$pdo = poesie_db();
$stmt = $pdo->prepare('SELECT * FROM documents WHERE id_interne = :id');
$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if ($doc === false) {
    http_response_code(404);
    echo '<p>Document introuvable.</p><p><a href="index.php">Retour au catalogue</a></p>';
    exit;
}

$contenu = null;
if ($doc['contenu_fichier_local'] !== null) {
    $chemin = __DIR__ . '/../poesie-corpus-prive/' . $doc['contenu_fichier_local'];
    if (is_file($chemin)) {
        $contenu = file_get_contents($chemin);
    }
}

$doublons = $doc['doublon_info'] !== null ? json_decode($doc['doublon_info'], true) : null;
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title><?= h($doc['titre']) ?> — Catalogue du corpus</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 800px; margin: 2rem auto; padding: 0 1rem; color: #222; }
dl { display: grid; grid-template-columns: 10rem 1fr; gap: 0.3rem 1rem; font-size: 0.9rem; }
dt { font-weight: bold; color: #555; }
pre.contenu { white-space: pre-wrap; background: #f7f7f7; padding: 1rem; border-radius: 6px; line-height: 1.5; }
.anomalie { color: #a33; }
</style>
</head>
<body>

<p><a href="index.php">&larr; Retour au catalogue</a></p>

<h1><?= h($doc['titre']) ?></h1>

<dl>
<dt>ID catalogue</dt><dd><?= h($doc['id_interne']) ?></dd>
<dt>Dossier parent</dt><dd><?= h($doc['dossier_parent']) ?></dd>
<dt>Chemin Drive</dt><dd><?= h($doc['chemin_drive']) ?></dd>
<dt>Catégorie</dt><dd><?= h($doc['categorie']) ?></dd>
<dt>Type de fichier</dt><dd><?= h($doc['type_fichier']) ?></dd>
<dt>Date de modification</dt><dd><?= h($doc['date_modification']) ?></dd>
<dt>Taille (octets)</dt><dd><?= $doc['taille_octets'] !== null ? h((string) $doc['taille_octets']) : '—' ?></dd>
<dt>Statut d'import</dt><dd><?= h($doc['statut_import']) ?></dd>
<dt>Document source</dt><dd><a href="<?= h($doc['lien_drive']) ?>" target="_blank" rel="noopener">Ouvrir dans Google Drive</a> (id <?= h($doc['id_drive']) ?>)</dd>
<?php if ($doc['anomalie'] !== ''): ?>
<dt>Anomalie</dt><dd class="anomalie"><?= h($doc['anomalie']) ?></dd>
<?php endif; ?>
<?php if ($doublons !== null): ?>
<dt>Relation détectée</dt><dd><?= h($doublons['type']) ?> avec <?= h(implode(', ', $doublons['lié_a'])) ?></dd>
<?php endif; ?>
</dl>

<h2>Contenu</h2>
<?php if ($contenu !== null): ?>
<pre class="contenu"><?= h($contenu) ?></pre>
<?php else: ?>
<p><em>Contenu non disponible localement (statut : <?= h($doc['statut_import']) ?>). Consulter le document source Google Drive ci-dessus.</em></p>
<?php endif; ?>

</body>
</html>
