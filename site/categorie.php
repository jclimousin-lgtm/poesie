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
    'SELECT d.id_interne, d.titre, d.dossier_parent
     FROM documents d
     JOIN document_categories dc ON dc.id_interne = d.id_interne
     WHERE dc.categorie_slug = :slug
     ORDER BY d.titre ASC'
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

<h1><?= h($categorie['nom']) ?></h1>

<?php if ($documents === []): ?>
<p><em>Aucun texte n'est encore rattaché à cette catégorie.</em></p>
<?php else: ?>
<ul class="textes">
<?php foreach ($documents as $d): ?>
<li><a href="fiche.php?id=<?= h($d['id_interne']) ?>"><?= h($d['titre']) ?></a> <span class="meta">(<?= h($d['dossier_parent']) ?>)</span></li>
<?php endforeach; ?>
</ul>
<p><?= count($documents) ?> texte(s) dans cette catégorie.</p>
<?php endif; ?>

</body>
</html>
