<?php

declare(strict_types=1);

require __DIR__ . '/../_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

const POESIE_DOMAINE = 'https://poesie.serviceproi.fr';

/** Construit l'URL canonique absolue d'une page du site public. */
function poesie_url(string $cheminRelatif): string
{
    return POESIE_DOMAINE . '/site/' . ltrim($cheminRelatif, '/');
}

/**
 * Extrait un résumé court et propre à partir du contenu réel d'un texte
 * (jamais inventé) — pour meta description / Open Graph / JSON-LD. Retire
 * les titres markdown éventuels (# ...), aplati les retours à la ligne.
 */
function poesie_extrait(string $texte, int $longueur = 155): string
{
    $texte = preg_replace('/^#+\s*.*$/m', '', $texte) ?? $texte;
    $texte = trim(preg_replace('/\s+/', ' ', $texte) ?? $texte);
    if (mb_strlen($texte) <= $longueur) {
        return $texte;
    }
    $coupe = mb_substr($texte, 0, $longueur);
    $dernierEspace = mb_strrpos($coupe, ' ');
    if ($dernierEspace !== false) {
        $coupe = mb_substr($coupe, 0, $dernierEspace);
    }
    return rtrim($coupe) . '…';
}

/**
 * Bloc de balises meta communes (description, canonical, Open Graph,
 * Twitter Card) — centralisé pour rester cohérent sur toutes les pages
 * publiques. Aucune image (aucune n'est disponible dans le corpus actuel).
 *
 * @param 'website'|'article' $type
 */
function poesie_meta_html(string $titre, string $description, string $cheminRelatif, string $type = 'website'): string
{
    $url = poesie_url($cheminRelatif);
    return '
<meta name="description" content="' . h($description) . '">
<link rel="canonical" href="' . h($url) . '">
<meta property="og:type" content="' . h($type) . '">
<meta property="og:site_name" content="Corpus">
<meta property="og:title" content="' . h($titre) . '">
<meta property="og:description" content="' . h($description) . '">
<meta property="og:url" content="' . h($url) . '">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="' . h($titre) . '">
<meta name="twitter:description" content="' . h($description) . '">';
}

/** @return list<array{slug:string,nom:string,ordre:int}> */
function poesie_categories(): array
{
    return poesie_db()->query('SELECT slug, nom, ordre FROM categories ORDER BY ordre ASC')->fetchAll();
}

function poesie_nav_html(string $slugActif = ''): string
{
    $liens = '<a href="liste.php"' . ($slugActif === 'liste' ? ' class="actif"' : '') . '>Tous les textes</a>';
    foreach (poesie_categories() as $c) {
        $lien = $c['slug'] === 'series' ? 'series.php' : 'categorie.php?slug=' . urlencode($c['slug']);
        $liens .= '<a href="' . h($lien) . '"' . ($slugActif === $c['slug'] ? ' class="actif"' : '') . '>' . h($c['nom']) . '</a>';
    }
    // Contributions externes — lien fixe, hors arborescence éditoriale validée
    // (ne provient jamais de la table `categories`, jamais mélangé au corpus fondateur).
    $liens .= '<a href="contributions.php"' . ($slugActif === 'contributions' ? ' class="actif"' : '') . '>Contributions</a>';
    $liens .= '<a href="proposer.php" class="nav-cta' . ($slugActif === 'proposer' ? ' actif' : '') . '">Proposer un texte</a>';

    return '
<header class="site-entete">
  <div class="site-entete-large">
    <a class="marque" href="accueil.php">CORPUS</a>
    <nav class="nav-liens">' . $liens . '</nav>
  </div>
</header>';
}

/**
 * Verrou par mot de passe partagé pour la rubrique « Personnel » —
 * indépendant du système de comptes admin/lecteur (admin/_lib.php), qui
 * ne gère que l'accès à l'espace d'administration, pas la lecture
 * publique. Hash stocké (pas le mot de passe en clair) : demandé
 * 2026-09-16.
 */
const POESIE_PERSONNEL_MDP_HASH = '$2y$10$nfACaorKSUQCeoDFQRy6LOTdV/RCaBV3E7KwerUx7/u9lRoDY9X0q';

function poesie_personnel_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function poesie_personnel_authentifie(): bool
{
    poesie_personnel_session();
    return ($_SESSION['personnel_ok'] ?? false) === true;
}

/**
 * À appeler avant tout envoi HTML sur une page qui affiche le formulaire.
 * Valide une éventuelle soumission POST (PRG, pour éviter un repost du
 * mot de passe au rechargement) : succès → $retourSucces (peut être la
 * fiche d'origine si on vient d'une redirection depuis fiche.php), échec
 * → $retourEchec (toujours la page du formulaire elle-même, pour ne pas
 * faire rebondir inutilement par fiche.php).
 */
function poesie_personnel_traiter_soumission(string $retourSucces, string $retourEchec): void
{
    poesie_personnel_session();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['personnel_mdp'])) {
        if (password_verify((string) $_POST['personnel_mdp'], POESIE_PERSONNEL_MDP_HASH)) {
            $_SESSION['personnel_ok'] = true;
            header('Location: ' . $retourSucces);
            exit;
        }
        $_SESSION['personnel_erreur'] = true;
        header('Location: ' . $retourEchec);
        exit;
    }
}

function poesie_personnel_formulaire_html(): string
{
    poesie_personnel_session();
    $erreur = !empty($_SESSION['personnel_erreur']);
    unset($_SESSION['personnel_erreur']);
    return '
<p class="texte-indisponible">Cette rubrique est protégée par un mot de passe.</p>
<form class="formulaire" method="post">
<label for="personnel_mdp">Mot de passe</label>
<input type="password" name="personnel_mdp" id="personnel_mdp" required autofocus>
' . ($erreur ? '<p class="msg-erreurs">Mot de passe incorrect.</p>' : '') . '
<button type="submit">Accéder</button>
</form>';
}

function poesie_pied_html(): string
{
    return '
<footer class="site-pied">
  <a href="accueil.php">&uarr; Retour à l\'accueil</a>
  <span class="site-pied-sep">·</span>
  <span>Un corpus de textes en cours de publication</span>
  <span class="site-pied-sep">·</span>
  <a href="../admin/login.php">Administration</a>
</footer>';
}

const POESIE_STYLE = '
/* ===== Corpus — direction visuelle « Kiosque » : sans-serif, graphique, un seul accent en aplat ===== */
:root {
  --fond: #ffffff;
  --fond-alt: #f4f4f2;
  --encre: #111110;
  --encre-douce: #5a5854;
  --encre-legere: #9c988f;
  --accent: #ff4d2e;
  --accent-encre: #ffffff;
  --ligne: #e7e5e0;
  --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}
@media (prefers-color-scheme: dark) {
  :root {
    --fond: #121212;
    --fond-alt: #1b1b1a;
    --encre: #f2f1ee;
    --encre-douce: #b7b3aa;
    --encre-legere: #726e66;
    --accent: #ff6a4d;
    --accent-encre: #111110;
    --ligne: #2c2b28;
  }
}
* { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body {
  margin: 0;
  background: var(--fond);
  color: var(--encre);
  font-family: var(--sans);
  font-size: 1.02rem;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}
a { color: var(--encre); }
a:hover { color: var(--accent); }
h1, h2, h3 { font-family: var(--sans); font-weight: 800; letter-spacing: -0.01em; color: var(--encre); margin: 0; }

/* --- En-tete / navigation ---
   Lentete occupait toute la largeur de lecran, avec la nav des rubriques
   compressee sur une seule ligne defilante et sa barre de defilement
   volontairement masquee (-ms-overflow-style/scrollbar-width/::-webkit-
   scrollbar) -- aucune rubrique netait jamais coupee, mais rien
   nindiquait quil fallait defiler pour les atteindre, ce qui les rendait
   de fait indecouvrables au clic/tactile. Remplace par un habillage large
   (960px, comme .site-pied) qui passe a la ligne (flex-wrap) plutot que de
   defiler : toutes les rubriques restent visibles et cliquables directement,
   sur toutes les tailles decran. Signale par lutilisateur 2026-08-12. */
.site-entete {
  padding: 0 1.25rem;
  border-bottom: 3px solid var(--encre);
  background: var(--fond);
  position: sticky;
  top: 0;
  z-index: 10;
}
.site-entete-large {
  max-width: 960px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.6rem 1.75rem;
  padding: 1.1rem 0;
}
.marque {
  font-family: var(--sans);
  font-size: 1.3rem;
  font-weight: 900;
  letter-spacing: 0.02em;
  color: var(--encre);
  text-decoration: none;
  white-space: nowrap;
}
.marque:hover { color: var(--accent); }
.nav-liens { display: flex; flex-wrap: wrap; gap: 0.5rem 1.3rem; font-size: 0.82rem; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; }
.nav-liens a { color: var(--encre-douce); text-decoration: none; padding: 0.3rem 0; border-bottom: 3px solid transparent; }
.nav-liens a.actif { color: var(--encre); border-bottom-color: var(--accent); }
.nav-liens a.nav-cta { color: var(--fond); background: var(--accent); padding: 0.35rem 0.8rem; border-radius: 2px; border-bottom: 3px solid transparent; }
.nav-liens a.nav-cta:hover { color: var(--fond); opacity: 0.85; }

/* --- Conteneurs génériques --- */
.page { max-width: 760px; margin: 0 auto; padding: 3rem 1.25rem 4rem; }
.page-large { max-width: 960px; margin: 0 auto; padding: 3rem 1.25rem 4rem; }

/* --- Accueil --- */
.hero { padding: 0 0 2.5rem; margin-bottom: 2.5rem; border-bottom: 1px solid var(--ligne); }
.hero h1 { font-size: clamp(2.4rem, 6vw, 4rem); line-height: 0.98; margin: 0 0 1rem; text-wrap: balance; }
.hero p { color: var(--encre-douce); font-size: 1.15rem; max-width: 32rem; margin: 0; }
.kicker { display: inline-block; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent-encre); background: var(--accent); padding: 0.2rem 0.55rem; margin: 0 0 0.9rem; }
.rubriques { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: 0; margin: 0 0 2rem; border-top: 1px solid var(--ligne); }
.rubriques a { display: block; padding: 1.1rem 0.25rem; font-size: 1.35rem; font-weight: 800; color: var(--encre); text-decoration: none; border-bottom: 1px solid var(--ligne); }
.rubriques a:hover { color: var(--accent); }

/* --- Listes / index numéroté --- */
.index-list { list-style: none; margin: 0; padding: 0; counter-reset: item-index; border-top: 1px solid var(--ligne); }
.index-list li { border-bottom: 1px solid var(--ligne); counter-increment: item-index; }
.index-list a.titre-lien { display: flex; align-items: baseline; gap: 1rem; padding: 1rem 0; text-decoration: none; color: var(--encre); }
.index-list a.titre-lien::before { content: counter(item-index, decimal-leading-zero); font-size: 0.78rem; font-weight: 700; color: var(--accent); flex-shrink: 0; width: 2rem; }
.index-list a.titre-lien span:first-of-type { font-size: 1.2rem; font-weight: 700; flex: 1; }
.index-list a.titre-lien:hover span:first-of-type { color: var(--accent); }
.index-list .meta { font-size: 0.78rem; font-weight: 600; color: var(--encre-legere); text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; }
.compte { font-size: 0.85rem; color: var(--encre-legere); margin-top: 1.25rem; }

/* --- Recherche --- */
.recherche { display: flex; gap: 0.6rem; margin: 1.5rem 0 2rem; }
.recherche input[type=text] { flex: 1; padding: 0.7rem 0.9rem; border: 2px solid var(--encre); border-radius: 2px; background: var(--fond); color: var(--encre); font-family: var(--sans); font-size: 1rem; }
.recherche button { padding: 0.7rem 1.3rem; border: 2px solid var(--encre); border-radius: 2px; background: var(--encre); color: var(--fond); font-weight: 700; font-size: 0.9rem; cursor: pointer; }
.recherche-reset { font-size: 0.85rem; align-self: center; }

/* --- Fiche de lecture --- */
.lecture { max-width: 660px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
.lecture-retour { font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; }
.lecture-entete { margin: 1.5rem 0 2rem; padding-bottom: 1.75rem; border-bottom: 4px solid var(--encre); }
.lecture-entete h1 { font-size: clamp(1.9rem, 5vw, 2.7rem); line-height: 1.05; margin: 0 0 0.6rem; }
.lecture-auteur { font-size: 1rem; font-weight: 700; color: var(--encre-douce); margin: 0; }
.lecture-auteur::before { content: "— "; color: var(--accent); }
.pastilles { display: flex; flex-wrap: wrap; gap: 0.4rem; margin: 1rem 0 0; }
.pastille { display: inline-block; background: var(--fond-alt); color: var(--encre); border-radius: 2px; padding: 0.25rem 0.7rem; font-size: 0.75rem; font-weight: 700; text-decoration: none; text-transform: uppercase; letter-spacing: 0.02em; }
.pastille:hover { background: var(--accent); color: #fff; }
.lien-associe { font-size: 0.85rem; color: var(--encre-douce); margin-top: 0.75rem; }
.corps-texte { white-space: pre-wrap; font-family: var(--sans); font-size: 1.15rem; line-height: 1.8; color: var(--encre); margin-top: 2rem; }
.texte-indisponible { color: var(--encre-douce); font-style: italic; }

/* --- Formulaires --- */
.formulaire label { display: block; margin-top: 1.4rem; font-size: 0.8rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: var(--encre-douce); }
.formulaire input[type=text], .formulaire input[type=email], .formulaire textarea {
  width: 100%; margin-top: 0.4rem; padding: 0.7rem; font-family: var(--sans); font-size: 1.05rem;
  border: 2px solid var(--ligne); border-radius: 2px; background: var(--fond); color: var(--encre);
}
.formulaire input:focus, .formulaire textarea:focus { outline: none; border-color: var(--accent); }
.formulaire textarea { min-height: 280px; line-height: 1.6; }
.formulaire .case { display: flex; align-items: flex-start; gap: 0.6rem; margin-top: 1.4rem; font-size: 0.9rem; font-weight: 400; text-transform: none; color: var(--encre); }
.formulaire .case input { margin-top: 0.2rem; }
.formulaire button { margin-top: 1.8rem; padding: 0.8rem 2rem; border: none; border-radius: 2px; background: var(--accent); color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; font-size: 0.95rem; cursor: pointer; }
.formulaire button:hover { opacity: 0.9; }
.msg-succes { background: var(--encre); color: var(--fond); padding: 1rem 1.25rem; border-radius: 2px; font-weight: 600; }
.msg-erreurs { background: var(--accent); color: #fff; padding: 1rem 1.25rem; border-radius: 2px; font-size: 0.9rem; font-weight: 600; }
.msg-erreurs ul { margin: 0; padding-left: 1.2rem; }

/* --- Pied de page --- */
.site-pied { max-width: 960px; margin: 0 auto; padding: 2rem 1.25rem 3rem; font-size: 0.8rem; color: var(--encre-legere); border-top: 1px solid var(--ligne); }
.site-pied a { color: var(--encre-douce); font-weight: 700; }
.site-pied-sep { margin: 0 0.5rem; }

@media (max-width: 640px) {
  .lecture-entete h1 { font-size: 1.7rem; }
  .corps-texte { font-size: 1.08rem; }
  .site-entete { padding: 0 1rem; }
  .site-entete-large { gap: 0.5rem 1rem; padding: 0.9rem 0; }
  .rubriques a { font-size: 1.1rem; }
}
';
