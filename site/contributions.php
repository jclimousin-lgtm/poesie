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

<h1>Contributions</h1>
<p>Textes proposés par des auteur·es extérieur·es, validés pour publication.</p>

<?php if ($contributions === []): ?>
<p><em>Aucune contribution publiée pour le moment.</em></p>
<?php else: ?>
<ul class="textes">
<?php foreach ($contributions as $c): ?>
<li><a href="contribution.php?id=<?= (int) $c['id'] ?>"><?= h($c['titre']) ?></a> <span class="meta">— <?= h($c['auteur_nom']) ?></span></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>

<p><a href="proposer.php">→ Proposer un texte</a></p>

</body>
</html>
