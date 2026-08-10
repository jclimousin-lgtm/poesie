<?php

declare(strict_types=1);

require __DIR__ . '/../_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

const ADMIN_STYLE = '
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
h1 { font-size: 1.6rem; }
nav.admin-nav { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd; font-size: 0.9rem; }
nav.admin-nav a { margin-right: 1.2rem; text-decoration: none; color: #444; }
nav.admin-nav a.actif { font-weight: bold; color: #000; }
table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
td, th { text-align: left; padding: 0.5rem; border-bottom: 1px solid #ddd; vertical-align: top; font-size: 0.88rem; }
.stats { display: flex; gap: 1.2rem; margin-bottom: 1.5rem; flex-wrap: wrap; }
.stat { background: #f2f2f2; padding: 0.6rem 1rem; border-radius: 6px; }
.stat strong { display: block; font-size: 1.3rem; }
.actions-form { display: inline; }
button { cursor: pointer; padding: 0.3rem 0.7rem; border: none; border-radius: 4px; font-size: 0.85rem; }
button.primaire { background: #1a1a1a; color: #fff; }
button.succes { background: #1a7a1a; color: #fff; }
button.danger { background: #a33; color: #fff; }
button.neutre { background: #ddd; color: #222; }
input[type=text], input[type=email], input[type=search], textarea, select { padding: 0.5rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc; border-radius: 4px; }
textarea { width: 100%; min-height: 300px; box-sizing: border-box; }
form.editeur label { display: block; margin-top: 1rem; font-weight: bold; font-size: 0.85rem; }
form.editeur input[type=text], form.editeur select { width: 100%; box-sizing: border-box; }
.badge { display: inline-block; background: #eee; border-radius: 10px; padding: 0.1rem 0.6rem; font-size: 0.78rem; margin: 0.1rem; }
.badge-fondateur { background: #ffe9c7; }
.msg-erreurs { background: #ffe0e0; color: #a33; padding: 0.8rem 1rem; border-radius: 4px; margin: 1rem 0; }
.msg-succes { background: #e0f5e0; color: #1a7a1a; padding: 0.8rem 1rem; border-radius: 4px; margin: 1rem 0; }
';

function admin_nav_html(string $actif = ''): string
{
    $items = [
        'index' => ['index.php', 'Tableau de bord'],
        'textes' => ['textes.php', 'Textes'],
        'categories' => ['categories.php', 'Catégories'],
        'soumissions' => ['soumissions.php', 'Soumissions'],
    ];
    $html = '<nav class="admin-nav">';
    foreach ($items as $slug => [$lien, $label]) {
        $html .= '<a href="' . h($lien) . '"' . ($slug === $actif ? ' class="actif"' : '') . '>' . h($label) . '</a>';
    }
    $html .= '<a href="../site/accueil.php" target="_blank">Voir le site &rarr;</a>';
    return $html . '</nav>';
}
