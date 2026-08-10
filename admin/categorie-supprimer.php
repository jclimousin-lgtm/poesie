<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

// Les 9 catégories de l'arborescence éditoriale validée ne sont jamais
// supprimables depuis cette administration — protection permanente, comme
// pour les 116 textes du corpus fondateur. Seules les catégories créées
// ensuite par un administrateur peuvent être supprimées.
const CATEGORIES_PROTEGEES = [
    'poesie', 'satire', 'chansons', 'pastiches-parodies', 'reflexion',
    'series', 'formes-contraintes', 'personnel', 'a-decouvrir',
];

$pdo = poesie_db();
$slug = trim((string) ($_GET['slug'] ?? ($_POST['slug'] ?? '')));

$stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$categorie = $stmt->fetch();

if ($categorie === false) {
    http_response_code(404);
    echo '<p>Catégorie introuvable.</p><p><a href="categories.php">Retour</a></p>';
    exit;
}

if (in_array($slug, CATEGORIES_PROTEGEES, true)) {
    http_response_code(403);
    ?>
    <!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Suppression refusée</title><style><?= ADMIN_STYLE ?></style></head><body>
    <?= admin_nav_html('categories') ?>
    <h1>Suppression refusée</h1>
    <p class="msg-erreurs">« <?= h($categorie['nom']) ?> » fait partie des 9 catégories de l'arborescence éditoriale validée et ne peut jamais être supprimée depuis cette administration.</p>
    <p><a href="categories.php">Retour</a></p>
    </body></html>
    <?php
    exit;
}

$stmtTextes = $pdo->prepare(
    'SELECT d.id_interne, d.titre FROM documents d
     JOIN document_categories dc ON dc.id_interne = d.id_interne
     WHERE dc.categorie_slug = :slug ORDER BY d.titre ASC'
);
$stmtTextes->execute(['slug' => $slug]);
$textesAffectes = $stmtTextes->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirmer'] ?? '') === '1') {
    // Retire uniquement les liaisons et la catégorie elle-même : les textes
    // eux-mêmes ne sont jamais supprimés.
    $pdo->prepare('DELETE FROM document_categories WHERE categorie_slug = :slug')->execute(['slug' => $slug]);
    $pdo->prepare('DELETE FROM categories WHERE slug = :slug')->execute(['slug' => $slug]);
    header('Location: categories.php?msg=' . urlencode('Catégorie supprimée : ' . $categorie['nom'] . ' (' . count($textesAffectes) . ' texte(s) jamais touché(s), seulement désaffecté(s))'));
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Confirmer la suppression — Administration</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('categories') ?>

<h1>Confirmer la suppression</h1>

<p class="msg-erreurs">
Vous êtes sur le point de supprimer la catégorie « <?= h($categorie['nom']) ?> ».
<?php if ($textesAffectes !== []): ?>
<?= count($textesAffectes) ?> texte(s) y sont actuellement rattaché(s) — ils ne seront jamais supprimés, seulement retirés de cette catégorie :
<ul><?php foreach ($textesAffectes as $t): ?><li><?= h($t['titre']) ?></li><?php endforeach; ?></ul>
<?php else: ?>
Aucun texte n'y est actuellement rattaché.
<?php endif; ?>
</p>

<form method="post">
<input type="hidden" name="slug" value="<?= h($slug) ?>">
<input type="hidden" name="confirmer" value="1">
<button class="danger" type="submit">Confirmer la suppression de la catégorie</button>
<a href="categories.php"><button class="neutre" type="button">Annuler</button></a>
</form>

</body>
</html>
