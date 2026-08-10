<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$pdo = poesie_db();
$total = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_publication = 'publie'")->fetchColumn();
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

<main class="page">

<section class="hero">
<p class="kicker">Corpus de textes</p>
<h1>Des mots à découvrir, entre satire, poésie et chanson.</h1>
<p><?= $total ?> textes réunis, classés par entrée thématique. Un même texte peut apparaître dans plusieurs catégories.</p>
</section>

<h2>Parcourir par entrée</h2>
<nav class="rubriques">
<?php foreach ($categories as $c): ?>
<a href="<?= $c['slug'] === 'series' ? 'series.php' : 'categorie.php?slug=' . urlencode($c['slug']) ?>"><?= h($c['nom']) ?></a>
<?php endforeach; ?>
</nav>

<p><a href="liste.php">&rarr; Voir la liste complète des textes</a></p>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
