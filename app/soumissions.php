<?php

declare(strict_types=1);

require __DIR__ . '/_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

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
<title>Administration — Soumissions</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
table { border-collapse: collapse; width: 100%; }
td, th { text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd; vertical-align: top; font-size: 0.9rem; }
.contenu-apercu { max-width: 300px; max-height: 100px; overflow: hidden; white-space: pre-wrap; color: #555; }
.statut-a_valider { color: #b8860b; font-weight: bold; }
.statut-publie { color: #1a7a1a; }
.statut-refuse { color: #a33; }
form.action { display: inline; }
button.accepter { background: #1a7a1a; color: #fff; border: none; padding: 0.3rem 0.7rem; border-radius: 4px; cursor: pointer; }
button.refuser { background: #a33; color: #fff; border: none; padding: 0.3rem 0.7rem; border-radius: 4px; cursor: pointer; margin-left: 0.3rem; }
</style>
</head>
<body>

<p><a href="index.php">&larr; Catalogue</a></p>

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
<form class="action" method="post"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="accepter"><button class="accepter" type="submit">Accepter</button></form>
<form class="action" method="post"><input type="hidden" name="id" value="<?= (int) $s['id'] ?>"><input type="hidden" name="action" value="refuser"><button class="refuser" type="submit">Refuser</button></form>
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
