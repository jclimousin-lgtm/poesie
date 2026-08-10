<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$pdo = poesie_db();
$series = $pdo->query('SELECT id, nom FROM series ORDER BY nom ASC')->fetchAll();

$stmtMembres = $pdo->prepare(
    'SELECT d.id_interne, d.titre
     FROM documents d
     JOIN document_series ds ON ds.id_interne = d.id_interne
     WHERE ds.serie_id = :serie_id
     ORDER BY ds.position ASC'
);
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Séries — Corpus</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('series') ?>

<h1>Séries</h1>

<?php foreach ($series as $s): ?>
<h2><?= h($s['nom']) ?></h2>
<?php
$stmtMembres->execute(['serie_id' => $s['id']]);
$membres = $stmtMembres->fetchAll();
?>
<ul class="textes">
<?php foreach ($membres as $m): ?>
<li><a href="fiche.php?id=<?= h($m['id_interne']) ?>"><?= h($m['titre']) ?></a></li>
<?php endforeach; ?>
</ul>
<?php endforeach; ?>

</body>
</html>
