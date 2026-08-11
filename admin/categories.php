<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';
require_once __DIR__ . '/../_seo.php';

$pdo = poesie_db();

$erreurs = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer') {
    require_role_admin();
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $nom = trim((string) ($_POST['nom'] ?? ''));
    $ordre = (int) ($_POST['ordre'] ?? 0);

    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $erreurs[] = 'Le slug doit être en minuscules, chiffres et tirets uniquement.';
    }
    if ($nom === '') {
        $erreurs[] = 'Le nom est obligatoire.';
    }
    if ($erreurs === []) {
        $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM categories WHERE slug = :slug');
        $stmtCheck->execute(['slug' => $slug]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            $erreurs[] = 'Ce slug existe déjà.';
        } else {
            $pdo->prepare('INSERT INTO categories (slug, nom, ordre) VALUES (:slug, :nom, :ordre)')
                ->execute(['slug' => $slug, 'nom' => $nom, 'ordre' => $ordre]);
            poesie_regenerer_sitemap();
            header('Location: categories.php?msg=' . urlencode('Catégorie créée : ' . $nom));
            exit;
        }
    }
}

$categories = $pdo->query(
    'SELECT c.slug, c.nom, c.ordre, COUNT(dc.id_interne) AS nb_textes
     FROM categories c
     LEFT JOIN document_categories dc ON dc.categorie_slug = c.slug
     GROUP BY c.slug ORDER BY c.ordre ASC'
)->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Catégories</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('categories') ?>

<h1>Catégories</h1>

<?php if (($msg = trim((string) ($_GET['msg'] ?? ''))) !== ''): ?>
<p class="msg-succes"><?= h($msg) ?></p>
<?php endif; ?>
<?php if ($erreurs !== []): ?>
<div class="msg-erreurs"><ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<?php if (!is_admin_role()): ?>
<p class="note-lecture-seule">Rôle lecteur — consultation uniquement, aucune action de modification disponible ici.</p>
<?php endif; ?>

<table>
<thead><tr><th>Ordre</th><th>Nom</th><th>Slug</th><th>Textes</th><?php if (is_admin_role()): ?><th>Actions</th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($categories as $c): ?>
<tr>
<td><?= (int) $c['ordre'] ?></td>
<td><?= h($c['nom']) ?></td>
<td><?= h($c['slug']) ?></td>
<td><a href="categorie-form.php?slug=<?= h($c['slug']) ?>&voir=1"><?= (int) $c['nb_textes'] ?> texte(s)</a></td>
<?php if (is_admin_role()): ?>
<td>
<a href="categorie-form.php?slug=<?= h($c['slug']) ?>">Modifier</a>
&middot;
<a href="categorie-supprimer.php?slug=<?= h($c['slug']) ?>">Supprimer</a>
</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<?php if (is_admin_role()): ?>
<h2>Créer une catégorie</h2>
<form class="editeur" method="post">
<input type="hidden" name="action" value="creer">
<label for="nom">Nom affiché</label>
<input type="text" id="nom" name="nom" required>
<label for="slug">Slug (identifiant technique, ex. "essais")</label>
<input type="text" id="slug" name="slug" pattern="[a-z0-9-]+" required>
<label for="ordre">Ordre d'affichage</label>
<input type="text" id="ordre" name="ordre" value="99">
<p><button class="primaire" type="submit">Créer</button></p>
</form>
<?php endif; ?>

</body>
</html>
