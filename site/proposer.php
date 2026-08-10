<?php

declare(strict_types=1);

require __DIR__ . '/_layout.php';

$erreurs = [];
$succes = false;

$auteurNom = '';
$titre = '';
$contenu = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auteurNom = trim((string) ($_POST['auteur_nom'] ?? ''));
    $titre = trim((string) ($_POST['titre'] ?? ''));
    $contenu = trim((string) ($_POST['contenu'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $declaration = isset($_POST['declaration']);

    if ($auteurNom === '') {
        $erreurs[] = 'Le nom ou pseudonyme est obligatoire.';
    }
    if ($titre === '') {
        $erreurs[] = 'Le titre est obligatoire.';
    }
    if ($contenu === '') {
        $erreurs[] = 'Le texte est obligatoire.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = 'Une adresse e-mail valide est obligatoire.';
    }
    if (!$declaration) {
        $erreurs[] = 'Vous devez confirmer être autorisé·e à proposer ce texte.';
    }

    if ($erreurs === []) {
        $stmt = poesie_db()->prepare(
            'INSERT INTO soumissions (auteur_nom, titre, contenu, email, declaration_autorisation, statut)
             VALUES (:auteur_nom, :titre, :contenu, :email, 1, :statut)'
        );
        $stmt->execute([
            'auteur_nom' => $auteurNom,
            'titre' => $titre,
            'contenu' => $contenu,
            'email' => $email,
            'statut' => 'a_valider',
        ]);
        $succes = true;
        $auteurNom = $titre = $contenu = $email = '';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Proposer un texte — Corpus</title>
<style><?= POESIE_STYLE ?>
form.proposition label { display: block; margin-top: 1rem; font-family: system-ui, sans-serif; font-weight: bold; }
form.proposition input[type=text], form.proposition input[type=email], form.proposition textarea { width: 100%; padding: 0.5rem; font-family: inherit; font-size: 1rem; box-sizing: border-box; }
form.proposition textarea { min-height: 250px; }
form.proposition button { margin-top: 1.5rem; padding: 0.6rem 1.5rem; font-size: 1rem; }
.erreurs { color: #a33; }
.succes { color: #1a7a1a; }
</style>
</head>
<body>

<?= poesie_nav_html('proposer') ?>

<h1>Proposer un texte</h1>

<p>Votre texte sera examiné avant toute publication. La validation reste humaine.</p>

<?php if ($succes): ?>
<p class="succes">Votre proposition a bien été enregistrée. Elle sera examinée avant publication. Merci.</p>
<?php else: ?>

<?php if ($erreurs !== []): ?>
<div class="erreurs">
<ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form class="proposition" method="post">
<label for="auteur_nom">Nom ou pseudonyme</label>
<input type="text" id="auteur_nom" name="auteur_nom" value="<?= h($auteurNom) ?>" required>

<label for="titre">Titre du texte</label>
<input type="text" id="titre" name="titre" value="<?= h($titre) ?>" required>

<label for="contenu">Texte intégral</label>
<textarea id="contenu" name="contenu" required><?= h($contenu) ?></textarea>

<label for="email">Adresse e-mail de contact</label>
<input type="email" id="email" name="email" value="<?= h($email) ?>" required>

<label><input type="checkbox" name="declaration" value="1"> Je confirme être l'auteur·e de ce texte, ou autorisé·e à le proposer à la publication.</label>

<button type="submit">Envoyer</button>
</form>

<?php endif; ?>

</body>
</html>
