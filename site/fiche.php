<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$id = trim((string) ($_GET['id'] ?? ''));

$pdo = poesie_db();
$stmt = $pdo->prepare("SELECT * FROM documents WHERE id_interne = :id AND statut_publication = 'publie'");
$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if ($doc === false) {
    http_response_code(404);
    echo '<p>Texte introuvable.</p><p><a href="liste.php">Retour à la liste</a></p>';
    exit;
}

$stmtCat = $pdo->prepare(
    'SELECT c.slug, c.nom FROM categories c
     JOIN document_categories dc ON dc.categorie_slug = c.slug
     WHERE dc.id_interne = :id ORDER BY c.ordre ASC'
);
$stmtCat->execute(['id' => $id]);
$categories = $stmtCat->fetchAll();

// Un texte rattaché à la rubrique « Personnel » ne doit pas être lisible
// via un lien direct sans être passé par le mot de passe de la rubrique.
$estPersonnel = false;
foreach ($categories as $c) {
    if ($c['slug'] === 'personnel') {
        $estPersonnel = true;
        break;
    }
}
if ($estPersonnel && !poesie_personnel_authentifie()) {
    header('Location: categorie.php?slug=personnel&retour=' . urlencode('fiche.php?id=' . $doc['id_interne']));
    exit;
}

$stmtSer = $pdo->prepare(
    'SELECT s.id, s.nom FROM series s
     JOIN document_series ds ON ds.serie_id = s.id
     WHERE ds.id_interne = :id'
);
$stmtSer->execute(['id' => $id]);
$series = $stmtSer->fetchAll();

$contenu = null;
if ($doc['contenu_inline'] !== null) {
    $contenu = $doc['contenu_inline'];
} elseif ($doc['contenu_fichier_local'] !== null) {
    $chemin = __DIR__ . '/../../poesie-corpus-prive/' . $doc['contenu_fichier_local'];
    if (is_file($chemin)) {
        $contenu = file_get_contents($chemin);
    }
}

$doublons = $doc['doublon_info'] !== null ? json_decode($doc['doublon_info'], true) : null;

$cheminPage = 'fiche.php?id=' . $doc['id_interne'];
$extrait = $contenu !== null ? poesie_extrait($contenu) : $doc['titre'];

$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'CreativeWork',
    'name' => $doc['titre'],
    'url' => poesie_url($cheminPage),
    'inLanguage' => 'fr',
];
if (!empty($doc['auteur'])) {
    $jsonLd['author'] = ['@type' => 'Person', 'name' => $doc['auteur']];
}
if ($extrait !== '') {
    $jsonLd['description'] = $extrait;
}
if (!empty($doc['date_modification'])) {
    $jsonLd['dateModified'] = $doc['date_modification'];
}
if ($categories !== []) {
    $jsonLd['genre'] = array_column($categories, 'nom');
}
if ($series !== []) {
    $jsonLd['isPartOf'] = ['@type' => 'CreativeWorkSeries', 'name' => $series[0]['nom']];
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($doc['titre']) ?><?= !empty($doc['auteur']) ? ' — ' . h($doc['auteur']) : '' ?> — Corpus</title>
<?= poesie_meta_html($doc['titre'], $extrait, $cheminPage, 'article') ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html() ?>

<article class="lecture">

<p class="lecture-retour"><a href="liste.php">&larr; Tous les textes</a></p>

<header class="lecture-entete">
<p class="kicker"><?= h($doc['dossier_parent']) ?></p>
<h1><?= h($doc['titre']) ?></h1>
<?php if (!empty($doc['auteur'])): ?>
<p class="lecture-auteur"><a href="auteur.php?nom=<?= urlencode($doc['auteur']) ?>" rel="author"><?= h($doc['auteur']) ?></a></p>
<?php endif; ?>
<?php if ($categories !== [] || $series !== []): ?>
<div class="pastilles">
<?php foreach ($categories as $c): ?>
<a class="pastille" href="categorie.php?slug=<?= h($c['slug']) ?>"><?= h($c['nom']) ?></a>
<?php endforeach; ?>
<?php foreach ($series as $s): ?>
<a class="pastille" href="series.php">Série : <?= h($s['nom']) ?></a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php if ($doublons !== null): ?>
<p class="lien-associe">Texte lié (<?= h($doublons['type']) ?>) : <?= h(implode(', ', $doublons['lié_a'])) ?></p>
<?php endif; ?>
</header>

<?php if ($contenu !== null): ?>
<div class="corps-texte"><?= h($contenu) ?></div>
<?php else: ?>
<p class="texte-indisponible">Contenu non disponible pour ce texte.</p>
<?php endif; ?>

</article>

<?= poesie_pied_html() ?>

</body>
</html>
