<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';
require_role_admin();
require_once __DIR__ . '/../_seo.php';

$pdo = poesie_db();
$id = trim((string) ($_GET['id'] ?? ($_POST['id'] ?? '')));

$stmt = $pdo->prepare('SELECT * FROM documents WHERE id_interne = :id');
$stmt->execute(['id' => $id]);
$doc = $stmt->fetch();

if ($doc === false) {
    http_response_code(404);
    echo '<p>Texte introuvable.</p><p><a href="textes.php">Retour</a></p>';
    exit;
}

// Protection permanente du corpus fondateur : jamais supprimable, ni ici ni
// via l'action en lot, quelle que soit la confirmation fournie.
if ($doc['origine'] === 'corpus_fondateur') {
    http_response_code(403);
    ?>
    <!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Suppression refusée</title><style><?= ADMIN_STYLE ?></style></head><body>
    <?= admin_nav_html('textes') ?>
    <h1>Suppression refusée</h1>
    <p class="msg-erreurs">« <?= h($doc['titre']) ?> » fait partie du corpus fondateur (116 textes protégés) et ne peut jamais être supprimé depuis cette administration.</p>
    <p><a href="textes.php">Retour à la liste</a></p>
    </body></html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirmer'] ?? '') === '1') {
    $pdo->prepare('DELETE FROM document_categories WHERE id_interne = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM document_series WHERE id_interne = :id')->execute(['id' => $id]);
    $pdo->prepare('DELETE FROM documents WHERE id_interne = :id')->execute(['id' => $id]);
    poesie_regenerer_sitemap();
    header('Location: textes.php?msg=' . urlencode('Texte supprimé : ' . $doc['titre']));
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

<?= admin_nav_html('textes') ?>

<h1>Confirmer la suppression</h1>

<p class="msg-erreurs">Vous êtes sur le point de supprimer définitivement « <?= h($doc['titre']) ?> » (<?= h($id) ?>, auteur : <?= h((string) $doc['auteur']) ?>). Cette action est irréversible.</p>

<form method="post">
<input type="hidden" name="id" value="<?= h($id) ?>">
<input type="hidden" name="confirmer" value="1">
<button class="danger" type="submit">Confirmer la suppression</button>
<a href="textes.php"><button class="neutre" type="button">Annuler</button></a>
</form>

</body>
</html>
