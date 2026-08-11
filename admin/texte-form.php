<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';
require_role_admin();
require_once __DIR__ . '/../_seo.php';

$pdo = poesie_db();
$id = trim((string) ($_GET['id'] ?? ($_POST['id_interne'] ?? '')));
$modeEdition = $id !== '';

$doc = null;
$categoriesActuelles = [];
$serieActuelle = '';

if ($modeEdition) {
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE id_interne = :id');
    $stmt->execute(['id' => $id]);
    $doc = $stmt->fetch();
    if ($doc === false) {
        http_response_code(404);
        echo '<p>Texte introuvable.</p><p><a href="textes.php">Retour</a></p>';
        exit;
    }
    $stmt = $pdo->prepare('SELECT categorie_slug FROM document_categories WHERE id_interne = :id');
    $stmt->execute(['id' => $id]);
    $categoriesActuelles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $stmt = $pdo->prepare('SELECT serie_id FROM document_series WHERE id_interne = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $serieActuelle = (string) ($stmt->fetchColumn() ?: '');
}

$contenuActuel = '';
if ($doc !== null) {
    if ($doc['contenu_inline'] !== null) {
        $contenuActuel = $doc['contenu_inline'];
    } elseif ($doc['contenu_fichier_local'] !== null) {
        $chemin = __DIR__ . '/../../poesie-corpus-prive/' . $doc['contenu_fichier_local'];
        if (is_file($chemin)) {
            $contenuActuel = (string) file_get_contents($chemin);
        }
    }
}

$categories = $pdo->query('SELECT slug, nom FROM categories ORDER BY ordre ASC')->fetchAll();
$series = $pdo->query('SELECT id, nom FROM series ORDER BY nom ASC')->fetchAll();

$erreurs = [];
$titre = $doc['titre'] ?? '';
$auteur = $doc['auteur'] ?? '';
$contenu = $contenuActuel;
$statutPublication = $doc['statut_publication'] ?? 'brouillon';
$categoriesChoisies = $categoriesActuelles;
$serieChoisie = $serieActuelle;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $auteur = trim((string) ($_POST['auteur'] ?? ''));
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    $statutPublication = (string) ($_POST['statut_publication'] ?? 'brouillon');
    $categoriesChoisies = array_values(array_filter((array) ($_POST['categories'] ?? [])));
    $serieChoisie = trim((string) ($_POST['serie'] ?? ''));

    if ($titre === '') { $erreurs[] = 'Le titre est obligatoire.'; }
    if ($auteur === '') { $erreurs[] = 'L\'auteur est obligatoire.'; }
    if ($contenu === '') { $erreurs[] = 'Le contenu est obligatoire.'; }
    if (!in_array($statutPublication, ['publie', 'brouillon'], true)) { $erreurs[] = 'Statut invalide.'; }

    if ($erreurs === []) {
        if ($modeEdition) {
            $stmt = $pdo->prepare(
                'UPDATE documents SET titre = :titre, auteur = :auteur, contenu_inline = :contenu, statut_publication = :statut WHERE id_interne = :id'
            );
            $stmt->execute(['titre' => $titre, 'auteur' => $auteur, 'contenu' => $contenu, 'statut' => $statutPublication, 'id' => $id]);
        } else {
            // Genere un identifiant ADM-XXXX, distinct par prefixe des INV-XXXX
            // du corpus fondateur — jamais de collision, jamais de confusion visuelle.
            $dernier = (string) $pdo->query("SELECT id_interne FROM documents WHERE id_interne LIKE 'ADM-%' ORDER BY id_interne DESC LIMIT 1")->fetchColumn();
            $prochain = $dernier !== '' ? ((int) substr($dernier, 4)) + 1 : 1;
            $id = sprintf('ADM-%04d', $prochain);

            $stmt = $pdo->prepare(
                'INSERT INTO documents
                    (id_interne, titre, chemin_drive, dossier_parent, type_fichier, id_drive, lien_drive, date_modification, taille_octets, categorie, contenu_fichier_local, statut_import, statut_publication, origine, auteur, contenu_inline)
                 VALUES
                    (:id, :titre, \'\', \'admin\', \'texte admin\', \'\', \'\', :date, NULL, \'texte admin\', NULL, NULL, :statut, \'admin\', :auteur, :contenu)'
            );
            $stmt->execute([
                'id' => $id,
                'titre' => $titre,
                'date' => date('c'),
                'statut' => $statutPublication,
                'auteur' => $auteur,
                'contenu' => $contenu,
            ]);
        }

        $pdo->prepare('DELETE FROM document_categories WHERE id_interne = :id')->execute(['id' => $id]);
        $stmtCat = $pdo->prepare('REPLACE INTO document_categories (id_interne, categorie_slug) VALUES (:id, :slug)');
        foreach ($categoriesChoisies as $slug) {
            $stmtCat->execute(['id' => $id, 'slug' => $slug]);
        }

        $pdo->prepare('DELETE FROM document_series WHERE id_interne = :id')->execute(['id' => $id]);
        if ($serieChoisie !== '') {
            $pdo->prepare('REPLACE INTO document_series (id_interne, serie_id, position) VALUES (:id, :serie, 1)')->execute(['id' => $id, 'serie' => $serieChoisie]);
        }

        poesie_regenerer_sitemap();
        header('Location: textes.php?msg=' . urlencode(($modeEdition ? 'Texte modifie' : 'Texte cree') . ' : ' . $titre));
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $modeEdition ? 'Modifier' : 'Créer' ?> un texte — Administration</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('textes') ?>

<p><a href="textes.php">&larr; Tous les textes</a></p>

<h1><?= $modeEdition ? 'Modifier' : 'Créer' ?> un texte<?= $modeEdition ? ' — ' . h($id) : '' ?></h1>

<?php if ($doc !== null && $doc['origine'] === 'corpus_fondateur'): ?>
<p class="badge badge-fondateur">Corpus fondateur — la modification du contenu ne touche jamais le fichier original importé de Google Drive.</p>
<?php endif; ?>

<?php if ($erreurs !== []): ?>
<div class="msg-erreurs"><ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form class="editeur" method="post">
<?php if ($modeEdition): ?><input type="hidden" name="id_interne" value="<?= h($id) ?>"><?php endif; ?>

<label for="titre">Titre</label>
<input type="text" id="titre" name="titre" value="<?= h($titre) ?>" required>

<label for="auteur">Auteur</label>
<input type="text" id="auteur" name="auteur" value="<?= h((string) $auteur) ?>" required>

<label for="contenu">Contenu</label>
<textarea id="contenu" name="contenu" required><?= h($contenu) ?></textarea>

<label for="statut_publication">Statut de publication</label>
<select id="statut_publication" name="statut_publication">
<option value="publie" <?= $statutPublication === 'publie' ? 'selected' : '' ?>>Publié</option>
<option value="brouillon" <?= $statutPublication === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
</select>

<label>Catégories</label>
<?php foreach ($categories as $c): ?>
<label style="font-weight: normal; display: inline-block; margin-right: 1rem;">
<input type="checkbox" name="categories[]" value="<?= h($c['slug']) ?>" <?= in_array($c['slug'], $categoriesChoisies, true) ? 'checked' : '' ?>> <?= h($c['nom']) ?>
</label>
<?php endforeach; ?>

<label for="serie">Série (optionnel)</label>
<select id="serie" name="serie">
<option value="">— aucune —</option>
<?php foreach ($series as $s): ?>
<option value="<?= h($s['id']) ?>" <?= $serieChoisie === $s['id'] ? 'selected' : '' ?>><?= h($s['nom']) ?></option>
<?php endforeach; ?>
</select>

<p><button class="primaire" type="submit"><?= $modeEdition ? 'Enregistrer' : 'Créer' ?></button></p>
</form>

</body>
</html>
