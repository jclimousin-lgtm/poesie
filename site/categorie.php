<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$slug = trim((string) ($_GET['slug'] ?? ''));

$pdo = poesie_db();
$stmtCat = $pdo->prepare('SELECT slug, nom FROM categories WHERE slug = :slug');
$stmtCat->execute(['slug' => $slug]);
$categorie = $stmtCat->fetch();

if ($categorie === false) {
    http_response_code(404);
    echo '<p>Catégorie introuvable.</p><p><a href="accueil.php">Retour à l\'accueil</a></p>';
    exit;
}

$stmt = $pdo->prepare(
    "SELECT d.id_interne, d.titre, d.auteur
     FROM documents d
     JOIN document_categories dc ON dc.id_interne = d.id_interne
     WHERE dc.categorie_slug = :slug AND d.statut_publication = 'publie'
     ORDER BY d.titre ASC"
);
$stmt->execute(['slug' => $slug]);
$documents = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($categorie['nom']) ?> — Corpus</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html($slug) ?>

<main class="page-large">

<p class="kicker">Entrée thématique</p>
<h1><?= h($categorie['nom']) ?></h1>

<?php if ($documents === []): ?>
<p class="texte-indisponible">Aucun texte n'est encore rattaché à cette catégorie.</p>
<?php else: ?>
<ul class="index-list">
<?php foreach ($documents as $d): ?>
<li><a class="titre-lien" href="fiche.php?id=<?= h($d['id_interne']) ?>"><span><?= h($d['titre']) ?></span> <span class="meta"><?= h((string) $d['auteur']) ?></span></a></li>
<?php endforeach; ?>
</ul>
<p class="compte"><?= count($documents) ?> texte(s) dans cette catégorie.</p>
<?php endif; ?>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
