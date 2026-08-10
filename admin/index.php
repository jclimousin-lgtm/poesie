<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

$pdo = poesie_db();
$total = (int) $pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$publies = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_publication = 'publie'")->fetchColumn();
$brouillons = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_publication = 'brouillon'")->fetchColumn();
$fondateur = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE origine = 'corpus_fondateur'")->fetchColumn();
$admin = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE origine = 'admin'")->fetchColumn();
$aValider = (int) $pdo->query("SELECT COUNT(*) FROM soumissions WHERE statut = 'a_valider'")->fetchColumn();
$nbCategories = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$nbSeries = (int) $pdo->query('SELECT COUNT(*) FROM series')->fetchColumn();

// Repères de protection du corpus fondateur — vérification rapide en direct.
$poesieCount = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE categorie_slug = 'poesie'")->fetchColumn();
$aDecouvrirCount = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE categorie_slug = 'a-decouvrir'")->fetchColumn();
$inv0015DansDecouvrir = (int) $pdo->query("SELECT COUNT(*) FROM document_categories WHERE categorie_slug = 'a-decouvrir' AND id_interne = 'INV-0015'")->fetchColumn();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Corpus</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('index') ?>

<h1>Tableau de bord</h1>

<div class="stats">
<div class="stat"><strong><?= $total ?></strong>documents au total</div>
<div class="stat"><strong><?= $publies ?></strong>publiés</div>
<div class="stat"><strong><?= $brouillons ?></strong>brouillons</div>
<div class="stat"><strong><?= $fondateur ?></strong>corpus fondateur</div>
<div class="stat"><strong><?= $admin ?></strong>ajoutés depuis l'admin</div>
<div class="stat"><strong><?= $aValider ?></strong>soumissions à valider</div>
<div class="stat"><strong><?= $nbCategories ?></strong>catégories</div>
<div class="stat"><strong><?= $nbSeries ?></strong>séries</div>
</div>

<h2>Repères de protection du corpus (vérification rapide)</h2>
<ul>
<li>Poésie : <?= $poesieCount ?> (attendu 36)</li>
<li>À découvrir : <?= $aDecouvrirCount ?> (attendu 11)</li>
<li>INV-0015 dans À découvrir : <?= $inv0015DansDecouvrir === 0 ? 'absent (OK)' : 'PRÉSENT — ANOMALIE' ?></li>
</ul>

<p><a href="textes.php">→ Gérer les textes</a> · <a href="categories.php">→ Gérer les catégories</a> · <a href="soumissions.php">→ Modérer les soumissions</a></p>

</body>
</html>
