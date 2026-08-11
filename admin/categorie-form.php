<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';
require_once __DIR__ . '/../_seo.php';

$pdo = poesie_db();
$slug = trim((string) ($_GET['slug'] ?? ($_POST['slug'] ?? '')));
$voir = isset($_GET['voir']);

// Le mode "voir" (liste des textes d'une catégorie) est en lecture seule et
// reste accessible au rôle lecteur ; l'édition (formulaire ou tout POST)
// reste réservée aux admins.
if (!$voir || $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role_admin();
}

$stmt = $pdo->prepare('SELECT * FROM categories WHERE slug = :slug');
$stmt->execute(['slug' => $slug]);
$categorie = $stmt->fetch();

if ($categorie === false) {
    http_response_code(404);
    echo '<p>Catégorie introuvable.</p><p><a href="categories.php">Retour</a></p>';
    exit;
}

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$voir) {
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $ordre = (int) ($_POST['ordre'] ?? 0);
    if ($nom === '') {
        $erreurs[] = 'Le nom est obligatoire.';
    } else {
        $pdo->prepare('UPDATE categories SET nom = :nom, ordre = :ordre WHERE slug = :slug')
            ->execute(['nom' => $nom, 'ordre' => $ordre, 'slug' => $slug]);
        poesie_regenerer_sitemap();
        header('Location: categories.php?msg=' . urlencode('Catégorie modifiée : ' . $nom));
        exit;
    }
}

$textes = $pdo->prepare(
    'SELECT d.id_interne, d.titre, d.auteur, d.origine
     FROM documents d JOIN document_categories dc ON dc.id_interne = d.id_interne
     WHERE dc.categorie_slug = :slug ORDER BY d.titre ASC'
);
$textes->execute(['slug' => $slug]);
$textesListe = $textes->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($categorie['nom']) ?> — Administration</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('categories') ?>

<p><a href="categories.php">&larr; Catégories</a></p>

<h1><?= h($categorie['nom']) ?></h1>

<?php if ($erreurs !== []): ?>
<div class="msg-erreurs"><ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!$voir): ?>
<form class="editeur" method="post">
<input type="hidden" name="slug" value="<?= h($slug) ?>">
<label for="nom">Nom affiché</label>
<input type="text" id="nom" name="nom" value="<?= h($categorie['nom']) ?>" required>
<label>Slug (non modifiable)</label>
<input type="text" value="<?= h($slug) ?>" disabled>
<label for="ordre">Ordre d'affichage</label>
<input type="text" id="ordre" name="ordre" value="<?= (int) $categorie['ordre'] ?>">
<p><button class="primaire" type="submit">Enregistrer</button></p>
</form>
<?php endif; ?>

<h2>Textes associés (<?= count($textesListe) ?>)</h2>
<table>
<thead><tr><th>ID</th><th>Titre</th><th>Auteur</th><th>Origine</th><th></th></tr></thead>
<tbody>
<?php foreach ($textesListe as $t): ?>
<tr>
<td><?= h($t['id_interne']) ?></td>
<td><a href="texte-form.php?id=<?= h($t['id_interne']) ?>"><?= h($t['titre']) ?></a></td>
<td><?= h((string) $t['auteur']) ?></td>
<td><span class="badge<?= $t['origine'] === 'corpus_fondateur' ? ' badge-fondateur' : '' ?>"><?= h($t['origine']) ?></span></td>
<td>
<form class="actions-form" method="post" action="texte-action.php">
<input type="hidden" name="ids[]" value="<?= h($t['id_interne']) ?>">
<input type="hidden" name="action" value="retirer_categorie">
<input type="hidden" name="categorie_slug" value="<?= h($slug) ?>">
<button class="neutre" type="submit">Retirer de cette catégorie</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

</body>
</html>
