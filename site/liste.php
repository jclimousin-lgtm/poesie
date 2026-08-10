<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$pdo = poesie_db();
$q = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT id_interne, titre, dossier_parent FROM documents WHERE statut_import IN ('importe','importe_partiellement')";
$params = [];
if ($q !== '') {
    $sql .= ' AND titre LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY titre ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tous les textes — Corpus</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('liste') ?>

<main class="page-large">

<p class="kicker">Index</p>
<h1>Tous les textes</h1>

<form class="recherche" method="get">
<input type="text" name="q" placeholder="Rechercher un titre..." value="<?= h($q) ?>">
<button type="submit">Rechercher</button>
<?php if ($q !== ''): ?><a class="recherche-reset" href="liste.php">réinitialiser</a><?php endif; ?>
</form>

<ul class="index-list">
<?php foreach ($documents as $d): ?>
<li><a class="titre-lien" href="fiche.php?id=<?= h($d['id_interne']) ?>"><span><?= h($d['titre']) ?></span> <span class="meta"><?= h($d['dossier_parent']) ?></span></a></li>
<?php endforeach; ?>
</ul>

<p class="compte"><?= count($documents) ?> texte(s).</p>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
