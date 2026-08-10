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
    $html = '<nav class="nav"><a href="accueil.php"' . ($slugActif === 'accueil' ? ' class="actif"' : '') . '>Accueil</a> ';
    $html .= '<a href="liste.php"' . ($slugActif === 'liste' ? ' class="actif"' : '') . '>Tous les textes</a> ';
    foreach (poesie_categories() as $c) {
        $lien = $c['slug'] === 'series' ? 'series.php' : 'categorie.php?slug=' . urlencode($c['slug']);
        $html .= '<a href="' . h($lien) . '"' . ($slugActif === $c['slug'] ? ' class="actif"' : '') . '>' . h($c['nom']) . '</a> ';
    }
    return $html . '</nav>';
}

const POESIE_STYLE = '
body { font-family: Georgia, serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; color: #222; line-height: 1.5; }
h1, h2 { font-family: system-ui, sans-serif; }
nav.nav { margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd; font-family: system-ui, sans-serif; font-size: 0.95rem; }
nav.nav a { margin-right: 1rem; text-decoration: none; color: #444; }
nav.nav a.actif { font-weight: bold; color: #000; }
ul.textes { list-style: none; padding: 0; }
ul.textes li { padding: 0.4rem 0; border-bottom: 1px solid #eee; }
ul.textes a { text-decoration: none; color: #222; font-size: 1.05rem; }
ul.textes .meta { color: #888; font-size: 0.85rem; font-family: system-ui, sans-serif; }
pre.contenu { white-space: pre-wrap; background: #f7f7f7; padding: 1.2rem; border-radius: 6px; font-family: Georgia, serif; font-size: 1.05rem; }
.badges a { display: inline-block; background: #eee; border-radius: 12px; padding: 0.15rem 0.7rem; margin: 0.15rem; font-size: 0.85rem; text-decoration: none; color: #444; font-family: system-ui, sans-serif; }
';
