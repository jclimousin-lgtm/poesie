<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

$pdo = poesie_db();
$q = trim((string) ($_GET['q'] ?? ''));

$sql = "SELECT d.id_interne, d.titre, d.auteur, d.origine, d.statut_publication,
               GROUP_CONCAT(c.nom ORDER BY c.ordre SEPARATOR ', ') AS categories_txt
        FROM documents d
        LEFT JOIN document_categories dc ON dc.id_interne = d.id_interne
        LEFT JOIN categories c ON c.slug = dc.categorie_slug";
$params = [];
if ($q !== '') {
    $sql .= ' WHERE d.titre LIKE :q OR d.auteur LIKE :q OR d.id_interne LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' GROUP BY d.id_interne ORDER BY d.titre ASC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

$categories = $pdo->query('SELECT slug, nom FROM categories ORDER BY ordre ASC')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Textes</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('textes') ?>

<h1>Textes</h1>

<?php if (($msg = trim((string) ($_GET['msg'] ?? ''))) !== ''): ?>
<p class="msg-succes"><?= h($msg) ?></p>
<?php endif; ?>

<p><a href="texte-form.php"><button class="primaire" type="button">+ Créer un texte</button></a></p>

<form method="get">
<input type="search" name="q" placeholder="Rechercher titre, auteur, ID..." value="<?= h($q) ?>" style="width: 320px;">
<button class="neutre" type="submit">Rechercher</button>
<?php if ($q !== ''): ?> <a href="textes.php">réinitialiser</a><?php endif; ?>
</form>

<form method="post" action="texte-action.php" id="form-lot">
<table>
<thead><tr>
<th><input type="checkbox" onclick="document.querySelectorAll('.case-texte').forEach(c => c.checked = this.checked)"></th>
<th>ID</th><th>Titre</th><th>Auteur</th><th>Origine</th><th>Statut</th><th>Catégories</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($documents as $d): ?>
<tr>
<td><input class="case-texte" type="checkbox" name="ids[]" value="<?= h($d['id_interne']) ?>" form="form-lot"></td>
<td><?= h($d['id_interne']) ?></td>
<td><?= h($d['titre']) ?></td>
<td><?= h((string) $d['auteur']) ?></td>
<td><span class="badge<?= $d['origine'] === 'corpus_fondateur' ? ' badge-fondateur' : '' ?>"><?= h($d['origine']) ?></span></td>
<td><?= h($d['statut_publication']) ?></td>
<td><?= h((string) $d['categories_txt']) ?></td>
<td>
<a href="texte-form.php?id=<?= h($d['id_interne']) ?>">Modifier</a>
&middot;
<?php
$autreStatut = $d['statut_publication'] === 'publie' ? 'depublier' : 'publier';
$libelleStatut = $d['statut_publication'] === 'publie' ? 'Dépublier' : 'Publier';
?>
<form class="actions-form" method="post" action="texte-action.php"><input type="hidden" name="ids[]" value="<?= h($d['id_interne']) ?>"><input type="hidden" name="action" value="<?= $autreStatut ?>"><button class="neutre" type="submit"><?= $libelleStatut ?></button></form>
<?php if ($d['origine'] !== 'corpus_fondateur'): ?>
&middot; <a href="texte-supprimer.php?id=<?= h($d['id_interne']) ?>">Supprimer</a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h2>Action en lot (sur les lignes cochées)</h2>
<p>
<select name="action">
<option value="publier">Publier</option>
<option value="depublier">Dépublier</option>
<option value="ajouter_categorie">Ajouter une catégorie</option>
<option value="retirer_categorie">Retirer une catégorie</option>
<option value="supprimer">Supprimer (corpus fondateur toujours ignoré)</option>
</select>
<select name="categorie_slug">
<option value="">— catégorie (pour ajouter/retirer) —</option>
<?php foreach ($categories as $c): ?>
<option value="<?= h($c['slug']) ?>"><?= h($c['nom']) ?></option>
<?php endforeach; ?>
</select>
<button class="primaire" type="submit" onclick="return confirm('Confirmer cette action sur les textes sélectionnés ?')">Appliquer</button>
</p>
</form>

<p><?= count($documents) ?> texte(s).</p>

</body>
</html>
