<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$stmt = poesie_db()->query(
    "SELECT id, auteur_nom, titre FROM soumissions WHERE statut = 'publie' ORDER BY date_traitement DESC"
);
$contributions = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Contributions — Corpus</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('contributions') ?>

<main class="page-large">

<p class="kicker">Voix extérieures</p>
<h1>Contributions</h1>
<p>Textes proposés par des auteur·es extérieur·es, validés pour publication.</p>

<?php if ($contributions === []): ?>
<p class="texte-indisponible">Aucune contribution publiée pour le moment.</p>
<?php else: ?>
<ul class="index-list">
<?php foreach ($contributions as $c): ?>
<li><a class="titre-lien" href="contribution.php?id=<?= (int) $c['id'] ?>"><span><?= h($c['titre']) ?></span> <span class="meta"><?= h($c['auteur_nom']) ?></span></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<p><a href="proposer.php">&rarr; Proposer un texte</a></p>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
