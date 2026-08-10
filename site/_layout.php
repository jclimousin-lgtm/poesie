<?php

declare(strict_types=1);

require __DIR__ . '/../_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

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
  <a class="marque" href="accueil.php">Corpus<span class="marque-point">.</span></a>
  <div class="nav-defilement"><nav class="nav-liens">' . $liens . '</nav></div>
</header>';
}

function poesie_pied_html(): string
{
    return '
<footer class="site-pied">
  <a href="accueil.php">&uarr; Retour à l\'accueil</a>
  <span class="site-pied-sep">·</span>
  <span>Un corpus de textes en cours de publication</span>
</footer>';
}

const POESIE_STYLE = '
/* ===== Corpus — direction visuelle « cahier d\'encre » ===== */
:root {
  --papier: #faf6ef;
  --papier-ombre: #f0e9db;
  --encre: #201c16;
  --encre-douce: #6b6255;
  --encre-legere: #a49a89;
  --accent: #a3291e;
  --accent-douce: #f3e2d9;
  --ligne: #e4d9c6;
  --rayon: 3px;
  --serif: Georgia, "Iowan Old Style", "Palatino Linotype", "Times New Roman", serif;
  --sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
@media (prefers-color-scheme: dark) {
  :root {
    --papier: #1a1712;
    --papier-ombre: #221e17;
    --encre: #ece5d8;
    --encre-douce: #b3a996;
    --encre-legere: #7a715f;
    --accent: #e0776a;
    --accent-douce: #3a2620;
    --ligne: #382f22;
  }
}
* { box-sizing: border-box; }
html { -webkit-text-size-adjust: 100%; }
body {
  margin: 0;
  background: var(--papier);
  color: var(--encre);
  font-family: var(--serif);
  font-size: 1.05rem;
  line-height: 1.6;
}
a { color: var(--accent); }
a:hover { text-decoration: none; }
h1, h2, h3 { font-family: var(--serif); font-weight: normal; color: var(--encre); }

/* --- En-tête / navigation --- */
.site-entete {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--ligne);
  background: var(--papier);
  position: sticky;
  top: 0;
  z-index: 10;
}
.marque {
  font-family: var(--serif);
  font-size: 1.4rem;
  font-weight: bold;
  color: var(--encre);
  text-decoration: none;
  white-space: nowrap;
}
.marque-point { color: var(--accent); }
.nav-defilement { overflow-x: auto; flex: 1; -ms-overflow-style: none; scrollbar-width: none; }
.nav-defilement::-webkit-scrollbar { display: none; }
.nav-liens { display: flex; gap: 1.4rem; white-space: nowrap; font-family: var(--sans); font-size: 0.85rem; letter-spacing: 0.02em; text-transform: uppercase; }
.nav-liens a { color: var(--encre-douce); text-decoration: none; padding: 0.3rem 0; border-bottom: 2px solid transparent; }
.nav-liens a.actif { color: var(--encre); border-bottom-color: var(--accent); }
.nav-liens a.nav-cta { color: var(--accent); font-weight: bold; }

/* --- Conteneurs génériques --- */
.page { max-width: 760px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
.page-large { max-width: 920px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }

/* --- Accueil --- */
.hero { padding: 1rem 0 2.5rem; border-bottom: 1px solid var(--ligne); margin-bottom: 2.5rem; }
.hero h1 { font-size: 2.6rem; line-height: 1.15; margin: 0 0 0.75rem; }
.hero p { color: var(--encre-douce); font-size: 1.1rem; max-width: 32rem; }
.kicker { font-family: var(--sans); font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent); margin: 0 0 0.4rem; }
.rubriques { display: grid; grid-template-columns: repeat(auto-fill, minmax(11rem, 1fr)); gap: 0.25rem 1.5rem; margin: 1rem 0 2rem; }
.rubriques a { display: block; padding: 0.6rem 0; font-size: 1.2rem; color: var(--encre); text-decoration: none; border-bottom: 1px solid var(--ligne); }
.rubriques a:hover { color: var(--accent); }

/* --- Listes / index --- */
.index-list { list-style: none; margin: 0; padding: 0; }
.index-list li { border-bottom: 1px solid var(--ligne); }
.index-list a.titre-lien { display: flex; justify-content: space-between; align-items: baseline; gap: 1rem; padding: 0.9rem 0; text-decoration: none; color: var(--encre); font-size: 1.15rem; }
.index-list a.titre-lien:hover { color: var(--accent); }
.index-list .meta { font-family: var(--sans); font-size: 0.78rem; color: var(--encre-legere); text-transform: uppercase; letter-spacing: 0.03em; white-space: nowrap; }
.compte { font-family: var(--sans); font-size: 0.85rem; color: var(--encre-legere); margin-top: 1.25rem; }

/* --- Recherche --- */
.recherche { display: flex; gap: 0.6rem; margin: 1.5rem 0 2rem; }
.recherche input[type=text] { flex: 1; padding: 0.6rem 0.8rem; border: 1px solid var(--ligne); border-radius: var(--rayon); background: var(--papier-ombre); color: var(--encre); font-family: var(--sans); font-size: 1rem; }
.recherche button { padding: 0.6rem 1.2rem; border: none; border-radius: var(--rayon); background: var(--encre); color: var(--papier); font-family: var(--sans); font-size: 0.9rem; cursor: pointer; }
.recherche-reset { font-family: var(--sans); font-size: 0.85rem; align-self: center; }

/* --- Fiche de lecture --- */
.lecture { max-width: 640px; margin: 0 auto; padding: 2.5rem 1.25rem 4rem; }
.lecture-retour { font-family: var(--sans); font-size: 0.85rem; }
.lecture-entete { margin: 1.5rem 0 2rem; }
.lecture-entete h1 { font-size: 2.3rem; line-height: 1.2; margin: 0 0 0.5rem; }
.lecture-auteur { font-family: var(--sans); font-size: 0.95rem; color: var(--encre-douce); }
.pastilles { display: flex; flex-wrap: wrap; gap: 0.4rem; margin: 1rem 0; }
.pastille { display: inline-block; background: var(--accent-douce); color: var(--accent); border-radius: 999px; padding: 0.2rem 0.8rem; font-size: 0.78rem; font-family: var(--sans); text-decoration: none; text-transform: uppercase; letter-spacing: 0.03em; }
.pastille:hover { text-decoration: underline; }
.lien-associe { font-family: var(--sans); font-size: 0.85rem; color: var(--encre-douce); font-style: italic; }
.corps-texte { white-space: pre-wrap; font-family: var(--serif); font-size: 1.2rem; line-height: 1.85; color: var(--encre); margin-top: 2rem; }
.corps-texte:first-letter { font-size: 2.6rem; line-height: 1; color: var(--accent); }
.texte-indisponible { color: var(--encre-douce); font-style: italic; }

/* --- Formulaires --- */
.formulaire label { display: block; margin-top: 1.4rem; font-family: var(--sans); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em; color: var(--encre-douce); }
.formulaire input[type=text], .formulaire input[type=email], .formulaire textarea {
  width: 100%; margin-top: 0.4rem; padding: 0.7rem; font-family: var(--serif); font-size: 1.05rem;
  border: 1px solid var(--ligne); border-radius: var(--rayon); background: var(--papier-ombre); color: var(--encre);
}
.formulaire textarea { min-height: 280px; line-height: 1.6; }
.formulaire .case { display: flex; align-items: flex-start; gap: 0.6rem; margin-top: 1.4rem; font-family: var(--sans); font-size: 0.9rem; text-transform: none; color: var(--encre); }
.formulaire .case input { margin-top: 0.2rem; }
.formulaire button { margin-top: 1.8rem; padding: 0.75rem 1.8rem; border: none; border-radius: var(--rayon); background: var(--accent); color: #fff; font-family: var(--sans); font-size: 1rem; cursor: pointer; }
.msg-succes { background: var(--accent-douce); color: var(--accent); padding: 1rem 1.25rem; border-radius: var(--rayon); font-family: var(--sans); }
.msg-erreurs { background: var(--accent-douce); color: var(--accent); padding: 1rem 1.25rem; border-radius: var(--rayon); font-family: var(--sans); font-size: 0.9rem; }
.msg-erreurs ul { margin: 0; padding-left: 1.2rem; }

/* --- Pied de page --- */
.site-pied { max-width: 920px; margin: 0 auto; padding: 2rem 1.25rem 3rem; font-family: var(--sans); font-size: 0.8rem; color: var(--encre-legere); border-top: 1px solid var(--ligne); }
.site-pied a { color: var(--encre-douce); }
.site-pied-sep { margin: 0 0.5rem; }

@media (max-width: 640px) {
  .hero h1 { font-size: 2rem; }
  .lecture-entete h1 { font-size: 1.8rem; }
  .corps-texte { font-size: 1.1rem; }
  .site-entete { gap: 1rem; padding: 0.9rem 1rem; }
}
';
