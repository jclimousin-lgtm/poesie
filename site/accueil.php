<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$pdo = poesie_db();
$total = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_import IN ('importe','importe_partiellement')")->fetchColumn();
$categories = poesie_categories();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Corpus — Accueil</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('accueil') ?>

<h1>Corpus de textes</h1>
<p><?= $total ?> textes réunis, classés par entrée thématique. Un même texte peut apparaître dans plusieurs catégories.</p>

<h2>Parcourir par catégorie</h2>
<div class="badges">
<?php foreach ($categories as $c): ?>
<a href="<?= $c['slug'] === 'series' ? 'series.php' : 'categorie.php?slug=' . urlencode($c['slug']) ?>"><?= h($c['nom']) ?></a>
<?php endforeach; ?>
</div>

<p><a href="liste.php">→ Voir la liste complète des textes</a></p>

</body>
</html>
