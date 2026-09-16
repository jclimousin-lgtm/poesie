<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$pdo = poesie_db();
$total = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_publication = 'publie'")->fetchColumn();
$categories = poesie_categories();

$description = 'Un corpus de ' . $total . ' textes — poésie, chansons, satire — classés par entrée thématique.';
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => 'Corpus',
    'url' => poesie_url('accueil.php'),
    'inLanguage' => 'fr',
    'description' => $description,
];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Corpus — Accueil</title>
<?= poesie_meta_html('Corpus', $description, 'accueil.php') ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('accueil') ?>

<main class="page-large">

<div class="accueil-layout">
<div>

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

</div>

<?= poesie_livres_nav_html() ?>

</div>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
