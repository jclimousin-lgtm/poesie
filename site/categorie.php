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

$cheminPage = 'categorie.php?slug=' . $slug;

// Rubrique « Personnel » : protégée par mot de passe, indépendamment du
// statut de publication des textes qu'elle contient. Le retour éventuel
// (venant d'une fiche accédée directement) est validé strictement pour
// éviter toute redirection ouverte.
$estPersonnel = $slug === 'personnel';
$retourParam = (string) ($_GET['retour'] ?? '');
$retourValide = $estPersonnel && preg_match('/^fiche\.php\?id=[A-Za-z0-9_-]+$/', $retourParam) === 1;
$retourEchec = $cheminPage . ($retourValide ? '&retour=' . urlencode($retourParam) : '');
$retourSucces = $retourValide ? $retourParam : $cheminPage;
if ($estPersonnel) {
    poesie_personnel_traiter_soumission($retourSucces, $retourEchec);
}
$accesAutorise = !$estPersonnel || poesie_personnel_authentifie();

$documents = [];
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $categorie['nom'],
    'url' => poesie_url($cheminPage),
    'inLanguage' => 'fr',
];

if ($accesAutorise) {
    $stmt = $pdo->prepare(
        "SELECT d.id_interne, d.titre, d.auteur
         FROM documents d
         JOIN document_categories dc ON dc.id_interne = d.id_interne
         WHERE dc.categorie_slug = :slug AND d.statut_publication = 'publie'
         ORDER BY d.titre ASC"
    );
    $stmt->execute(['slug' => $slug]);
    $documents = $stmt->fetchAll();

    $jsonLd['hasPart'] = array_map(
        static fn(array $d): array => ['@type' => 'CreativeWork', 'name' => $d['titre'], 'url' => poesie_url('fiche.php?id=' . $d['id_interne'])],
        $documents
    );
}

$description = $accesAutorise
    ? 'Catégorie « ' . $categorie['nom'] . ' » du corpus : ' . count($documents) . ' texte(s).'
    : 'Catégorie « ' . $categorie['nom'] . ' » du corpus — accès protégé par mot de passe.';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($categorie['nom']) ?> — Corpus</title>
<?= poesie_meta_html($categorie['nom'], $description, $cheminPage) ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html($slug) ?>

<main class="page-large">

<div class="layout-livres">
<div>

<p class="kicker">Entrée thématique</p>
<h1><?= h($categorie['nom']) ?></h1>

<?php if (!$accesAutorise): ?>
<?= poesie_personnel_formulaire_html() ?>
<?php elseif ($documents === []): ?>
<p class="texte-indisponible">Aucun texte n'est encore rattaché à cette catégorie.</p>
<?php else: ?>
<ul class="index-list">
<?php foreach ($documents as $d): ?>
<li><a class="titre-lien" href="fiche.php?id=<?= h($d['id_interne']) ?>"><span><?= h($d['titre']) ?></span> <span class="meta"><?= h((string) $d['auteur']) ?></span></a></li>
<?php endforeach; ?>
</ul>
<p class="compte"><?= count($documents) ?> texte(s) dans cette catégorie.</p>
<?php endif; ?>

</div>

<?= poesie_livres_nav_html($slug) ?>

</div>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
