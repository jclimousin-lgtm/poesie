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
<?= poesie_meta_html('Proposer un texte', 'Proposez un texte à la publication sur le corpus — validation humaine avant mise en ligne.', 'proposer.php') ?>
<style><?= POESIE_STYLE ?></style>
<style>
/* ===== CORPUS — couche graphique isolée pour proposer.php uniquement =====
   Variables et classes préfixées corpus- ; scoping via .corpus-propose sur
   le <body> pour ne rien changer aux autres pages (mission
   MISSION-INTEGRATEUR.md du 2026-09-16, pack corpus-charte-integration). */
.corpus-propose {
  --corpus-paper: #F5F1E8;
  --corpus-surface: #FFFDF8;
  --corpus-ink: #242321;
  --corpus-black: #181715;
  --corpus-muted: #716C63;
  --corpus-red: #8C3028;
  --corpus-line: #BEB6AA;
  --corpus-serif: Georgia, "Times New Roman", serif;
  background: var(--corpus-paper);
  color: var(--corpus-ink);
}
.corpus-propose .site-entete { background: var(--corpus-paper); border-bottom-color: var(--corpus-line); }
.corpus-propose .site-entete-large { max-width: 1180px; }
.corpus-propose .marque { font-family: var(--corpus-serif); font-size: 1.55rem; letter-spacing: 0.02em; color: var(--corpus-black); }
.corpus-propose .marque:hover { color: var(--corpus-red); }
.corpus-propose .nav-liens a { color: var(--corpus-muted); }
.corpus-propose .nav-liens a.actif { color: var(--corpus-red); border-bottom-color: var(--corpus-red); }
.corpus-propose .nav-liens a.nav-cta { background: var(--corpus-red); color: var(--corpus-surface); }
.corpus-propose .nav-liens a.nav-cta:hover { background: #74251F; opacity: 1; }

.corpus-propose .page { max-width: 1180px; }
.corpus-propose .corpus-breadcrumb { color: var(--corpus-muted); font-size: 0.8rem; margin: 0 0 1.6rem; }
.corpus-propose .corpus-breadcrumb a { color: var(--corpus-muted); text-decoration: none; }
.corpus-propose .corpus-breadcrumb a:hover { color: var(--corpus-red); }

.corpus-propose .corpus-hero { display: grid; grid-template-columns: 22% 1fr 150px; gap: 2.2rem; align-items: start; margin-bottom: 2.5rem; }
.corpus-propose .corpus-sidequote { border-left: 2px solid var(--corpus-red); padding: 0.3rem 0 0.3rem 1.4rem; font-family: var(--corpus-serif); font-style: italic; font-size: 1.15rem; line-height: 1.4; color: var(--corpus-ink); }
.corpus-propose .corpus-sidequote small { display: block; margin-top: 1.2rem; font-family: var(--sans); font-style: normal; letter-spacing: 0.2em; text-transform: uppercase; font-size: 0.65rem; color: var(--corpus-muted); }
.corpus-propose .corpus-logo { display: block; width: min(320px, 60vw); height: auto; margin: 0 0 0.6rem; }
.corpus-propose h1 { margin: 0; font-family: var(--corpus-serif); font-weight: 400; font-size: clamp(2.2rem, 5vw, 3.4rem); line-height: 1; letter-spacing: -0.01em; color: var(--corpus-black); }
.corpus-propose .corpus-lead { margin: 0.9rem 0 0; font-family: var(--corpus-serif); font-size: 1.35rem; color: var(--corpus-red); }
.corpus-propose .corpus-stamp { width: 120px; margin-top: 0.2rem; transform: rotate(2deg); }

.corpus-propose .corpus-form-card {
  max-width: 900px;
  background: rgba(255, 253, 248, 0.78);
  border: 1px solid #DDD5C9;
  box-shadow: 0 14px 34px rgba(36, 35, 33, 0.06);
  padding: 2.4rem 2.6rem 2.6rem;
  position: relative;
}
.corpus-propose .corpus-form-card:before { content: ""; position: absolute; inset: 10px; border: 1px solid rgba(190, 182, 170, 0.35); pointer-events: none; }
.corpus-propose .corpus-form-head { display: flex; justify-content: space-between; gap: 1.6rem; border-bottom: 1px solid var(--corpus-line); padding-bottom: 1.4rem; position: relative; z-index: 1; }
.corpus-propose .corpus-kicker { font-size: 0.68rem; letter-spacing: 0.3em; text-transform: uppercase; color: var(--corpus-muted); }
.corpus-propose .corpus-number { margin-top: 0.4rem; color: var(--corpus-red); font-family: var(--corpus-serif); font-size: 1.4rem; letter-spacing: 0.08em; }

.corpus-propose .formulaire { position: relative; z-index: 1; padding-top: 1.4rem; }
.corpus-propose .formulaire label { font-family: var(--corpus-serif); font-size: 0.98rem; font-weight: 400; text-transform: none; letter-spacing: normal; color: var(--corpus-ink); margin-top: 1.3rem; }
.corpus-propose .formulaire input[type=text], .corpus-propose .formulaire input[type=email], .corpus-propose .formulaire textarea {
  border: 1px solid #BEB7AD; border-radius: 0; background: rgba(255, 255, 255, 0.45); color: var(--corpus-ink);
  font: 1.02rem/1.5 var(--corpus-serif);
}
.corpus-propose .formulaire input:focus, .corpus-propose .formulaire textarea:focus { border-color: var(--corpus-red); box-shadow: 0 0 0 2px rgba(140, 48, 40, 0.1); }
.corpus-propose .formulaire textarea { min-height: 260px; }
.corpus-propose .formulaire .case { font-family: var(--corpus-serif); }
.corpus-propose .formulaire .case input { accent-color: var(--corpus-red); }
.corpus-propose .formulaire button { background: var(--corpus-red); border-radius: 0; }
.corpus-propose .formulaire button:hover { background: #74251F; opacity: 1; }

.corpus-propose .msg-succes { background: var(--corpus-surface); color: var(--corpus-ink); border: 1px solid var(--corpus-line); border-left: 3px solid var(--corpus-red); border-radius: 0; }
.corpus-propose .msg-erreurs { background: var(--corpus-surface); color: var(--corpus-ink); border: 1px solid var(--corpus-line); border-left: 3px solid var(--corpus-red); border-radius: 0; }

.corpus-propose .site-pied { border-top-color: var(--corpus-ink); color: var(--corpus-muted); }
.corpus-propose .site-pied a { color: var(--corpus-ink); }

@media (max-width: 900px) {
  .corpus-propose .corpus-hero { grid-template-columns: 1fr; }
  .corpus-propose .corpus-sidequote, .corpus-propose .corpus-stamp { display: none; }
  .corpus-propose .corpus-form-card { padding: 1.6rem 1.3rem; }
  .corpus-propose .corpus-form-head { flex-direction: column; gap: 0.6rem; }
}
</style>
</head>
<body class="corpus-propose">

<?= poesie_nav_html('proposer') ?>

<main class="page">

<p class="corpus-breadcrumb"><a href="accueil.php">Accueil</a> &nbsp;›&nbsp; Proposer un texte</p>

<section class="corpus-hero">
  <aside class="corpus-sidequote">
    « Un texte trouve parfois sa place là où on ne l’attendait pas. »
    <small>Corpus</small>
  </aside>

  <div>
    <img class="corpus-logo" src="assets/corpus/logo-corpus.svg" alt="CORPUS — Textes, voix & fragments">
    <h1>Proposer un texte</h1>
    <p class="corpus-lead">Votre texte sera examiné avant toute publication.</p>
    <p>La validation reste humaine.</p>
  </div>

  <img class="corpus-stamp" src="assets/corpus/stamp-corpus.svg" alt="" aria-hidden="true">
</section>

<?php if ($succes): ?>
<p class="msg-succes">Votre proposition a bien été enregistrée. Elle sera examinée avant publication. Merci.</p>
<?php else: ?>

<?php if ($erreurs !== []): ?>
<div class="msg-erreurs">
<ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<section class="corpus-form-card">
  <div class="corpus-form-head">
    <div>
      <div class="corpus-kicker">Dossier de contribution</div>
      <div class="corpus-number">Nouveau texte</div>
    </div>
  </div>

<form class="formulaire" method="post">
<label for="auteur_nom">Nom ou pseudonyme</label>
<input type="text" id="auteur_nom" name="auteur_nom" value="<?= h($auteurNom) ?>" required>

<label for="titre">Titre du texte</label>
<input type="text" id="titre" name="titre" value="<?= h($titre) ?>" required>

<label for="contenu">Texte intégral</label>
<textarea id="contenu" name="contenu" required><?= h($contenu) ?></textarea>

<label for="email">Adresse e-mail de contact</label>
<input type="email" id="email" name="email" value="<?= h($email) ?>" required>

<label class="case"><input type="checkbox" name="declaration" value="1"> Je confirme être l'auteur·e de ce texte, ou autorisé·e à le proposer à la publication.</label>

<button type="submit">Envoyer</button>
</form>
</section>

<?php endif; ?>

</main>

<?= poesie_pied_html() ?>

</body>
</html>
