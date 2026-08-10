<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = poesie_db()->prepare("SELECT * FROM soumissions WHERE id = :id AND statut = 'publie'");
$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if ($doc === false) {
    http_response_code(404);
    echo '<p>Contribution introuvable.</p><p><a href="contributions.php">Retour aux contributions</a></p>';
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($doc['titre']) ?> — Corpus</title>
<style><?= POESIE_STYLE ?></style>
</head>
<body>

<?= poesie_nav_html('contributions') ?>

<p><a href="contributions.php">&larr; Contributions</a></p>

<h1><?= h($doc['titre']) ?></h1>
<p class="meta">par <?= h($doc['auteur_nom']) ?></p>

<pre class="contenu"><?= h($doc['contenu']) ?></pre>

</body>
</html>
