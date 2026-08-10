<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

$pdo = poesie_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    if ($id > 0 && in_array($action, ['accepter', 'refuser'], true)) {
        $statut = $action === 'accepter' ? 'publie' : 'refuse';
        $stmt = $pdo->prepare('UPDATE soumissions SET statut = :statut, date_traitement = NOW() WHERE id = :id');
        $stmt->execute(['statut' => $statut, 'id' => $id]);
    }
    header('Location: soumissions.php');
    exit;
}

$soumissions = $pdo->query('SELECT * FROM soumissions ORDER BY date_soumission DESC')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Soumissions</title>
<style><?= ADMIN_STYLE ?>
.contenu-apercu { max-width: 300px; max-height: 100px; overflow: hidden; white-space: pre-wrap; color: #555; }
.statut-a_valider { color: #b8860b; font-weight: bold; }
.statut-publie { color: #1a7a1a; }
.statut-refuse { color: #a33; }
</style>
</head>
<body>

<?= admin_nav_html('soumissions') ?>

<h1>Soumissions externes</h1>

<table>
<thead><tr><th>Date</th><th>Auteur</th><th>E-mail</th><th>Titre</th><th>Texte</th><th>Statut</th><th>Action</th></tr></thead>
<tbody>
<?php foreach ($soumissions as $s): ?>
<tr>
<td><?= h((string) $s['date_soumission']) ?></td>
<td><?= h($s['auteur_nom']) ?></td>
<td><?= h($s['email']) ?></td>
<td><?= h($s['titre']) ?></td>
<td class="contenu-apercu"><?= h($s['contenu']) ?></td>
<td class="statut-<?= h($s['statut']) ?>"><?= h($s['statut']) ?></td>
<td>
<?php if ($s['statut'] === 'a_valider'): ?>
<form class="actions-form" method="post"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="accepter"><button class="succes" type="submit">Accepter</button></form>
<form class="actions-form" method="post"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="refuser"><button class="danger" type="submit">Refuser</button></form>
<?php else: ?>
&mdash;
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><?= count($soumissions) ?> soumission(s) au total.</p>

</body>
</html>
