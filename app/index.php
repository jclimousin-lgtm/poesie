<?php

declare(strict_types=1);

require __DIR__ . '/_db.php';

$pdo = poesie_db();

$total = (int) $pdo->query('SELECT COUNT(*) FROM documents')->fetchColumn();
$recuperes = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_import IN ('importe','importe_partiellement')")->fetchColumn();
$aTraiter = (int) $pdo->query("SELECT COUNT(*) FROM documents WHERE statut_import NOT IN ('importe','importe_partiellement')")->fetchColumn();

$q = trim((string) ($_GET['q'] ?? ''));

if ($q !== '') {
    $stmt = $pdo->prepare("SELECT id_interne, titre, dossier_parent, categorie, statut_import FROM documents WHERE titre LIKE :q ORDER BY titre ASC");
    $stmt->execute(['q' => '%' . $q . '%']);
} else {
    $stmt = $pdo->query('SELECT id_interne, titre, dossier_parent, categorie, statut_import FROM documents ORDER BY dossier_parent, titre ASC');
}
$documents = $stmt->fetchAll();

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Catalogue du corpus — poesie.serviceproi.fr</title>
<style>
body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; color: #222; }
.stats { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; }
.stat { background: #f2f2f2; padding: 0.75rem 1rem; border-radius: 6px; }
.stat strong { display: block; font-size: 1.4rem; }
table { border-collapse: collapse; width: 100%; }
td, th { text-align: left; padding: 0.4rem 0.6rem; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
.statut-importe { color: #1a7a1a; }
.statut-importe_partiellement { color: #b8860b; }
.statut-inaccessible, .statut-a_traiter { color: #a33; }
form.search { margin-bottom: 1rem; }
input[type=text] { padding: 0.4rem; width: 300px; }
</style>
</head>
<body>

<h1>Catalogue de travail — corpus de textes</h1>

<div class="stats">
<div class="stat"><strong><?= $total ?></strong>documents inventoriés</div>
<div class="stat"><strong><?= $recuperes ?></strong>textes récupérés</div>
<div class="stat"><strong><?= $aTraiter ?></strong>restant à traiter</div>
</div>

<form class="search" method="get">
<input type="text" name="q" placeholder="Rechercher un titre..." value="<?= h($q) ?>">
<button type="submit">Rechercher</button>
<?php if ($q !== ''): ?> <a href="index.php">réinitialiser</a><?php endif; ?>
</form>

<table>
<thead><tr><th>Titre</th><th>Dossier</th><th>Catégorie</th><th>Statut</th></tr></thead>
<tbody>
<?php foreach ($documents as $d): ?>
<tr>
<td><a href="fiche.php?id=<?= h($d['id_interne']) ?>"><?= h($d['titre']) ?></a></td>
<td><?= h($d['dossier_parent']) ?></td>
<td><?= h($d['categorie']) ?></td>
<td class="statut-<?= h($d['statut_import']) ?>"><?= h($d['statut_import']) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<p><?= count($documents) ?> document(s) affiché(s).</p>

</body>
</html>
