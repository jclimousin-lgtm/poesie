<?php

declare(strict_types=1);

require __DIR__ . '/../_db.php';

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

const ADMIN_STYLE = '
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; max-width: 1100px; margin: 2rem auto; padding: 0 1rem; color: #1a1a1a; }
h1 { font-size: 1.6rem; }
nav.admin-nav { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #ddd; font-size: 0.9rem; display: flex; align-items: center; flex-wrap: wrap; gap: 0 1.2rem; }
nav.admin-nav a { text-decoration: none; color: #444; }
nav.admin-nav a.actif { font-weight: bold; color: #000; }
nav.admin-nav .qui-suis-je { margin-left: auto; color: #777; font-size: 0.85rem; }
nav.admin-nav .badge-role { display: inline-block; background: #eee; border-radius: 10px; padding: 0.05rem 0.5rem; font-size: 0.75rem; margin-left: 0.3rem; }
nav.admin-nav .badge-role.role-admin { background: #dff0d8; color: #2a6b2a; }
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
input[type=text], input[type=email], input[type=password], input[type=search], textarea, select { padding: 0.5rem; font-size: 0.95rem; font-family: inherit; border: 1px solid #ccc; border-radius: 4px; }
textarea { width: 100%; min-height: 300px; box-sizing: border-box; }
form.editeur label { display: block; margin-top: 1rem; font-weight: bold; font-size: 0.85rem; }
form.editeur input[type=text], form.editeur input[type=password], form.editeur select { width: 100%; box-sizing: border-box; }
.badge { display: inline-block; background: #eee; border-radius: 10px; padding: 0.1rem 0.6rem; font-size: 0.78rem; margin: 0.1rem; }
.badge-fondateur { background: #ffe9c7; }
.msg-erreurs { background: #ffe0e0; color: #a33; padding: 0.8rem 1rem; border-radius: 4px; margin: 1rem 0; }
.msg-succes { background: #e0f5e0; color: #1a7a1a; padding: 0.8rem 1rem; border-radius: 4px; margin: 1rem 0; }
.note-lecture-seule { background: #fff8e1; color: #8a6d00; padding: 0.6rem 1rem; border-radius: 4px; margin: 1rem 0; font-size: 0.88rem; }
.login-box { max-width: 340px; margin: 4rem auto; }
.login-box h1 { text-align: center; }
';

/**
 * Authentification par compte nominatif (table `users`), avec rôle
 * (`admin` = contrôle total, `lecteur` = lecture seule) -- remplace
 * l'ancienne authentification HTTP Basic partagée (un seul mot de passe,
 * aucun rôle). Voir CLAUDE.md, entrée du 2026-08-11.
 */

function auth_start_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user(): ?array
{
    auth_start_session();
    if (!isset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['role'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => (string) $_SESSION['username'],
        'role' => (string) $_SESSION['role'],
    ];
}

function is_admin_role(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'admin';
}

function require_login(): array
{
    $u = current_user();
    if ($u === null) {
        auth_start_session();
        $next = $_SERVER['REQUEST_URI'] ?? 'index.php';
        header('Location: login.php?next=' . urlencode($next));
        exit;
    }
    return $u;
}

/**
 * A appeler en tête de toute page/action réservée au rôle admin (création,
 * modification, suppression, validation...). Renvoie un 403 explicite --
 * jamais un simple masquage côté client, voir règle 4 du CLAUDE.md du
 * Portail (même principe appliqué ici : la protection admin doit être
 * vérifiée côté serveur, pas seulement cachée dans l'interface).
 */
function require_role_admin(): void
{
    $u = require_login();
    if ($u['role'] !== 'admin') {
        http_response_code(403);
        ?>
        <!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Accès refusé</title><style><?= ADMIN_STYLE ?></style></head><body>
        <?= admin_nav_html('') ?>
        <h1>Accès refusé</h1>
        <p class="msg-erreurs">Ton compte (<?= h($u['username']) ?>) a le rôle « lecteur » — lecture seule. Cette action est réservée aux comptes administrateur.</p>
        <p><a href="index.php">Retour au tableau de bord</a></p>
        </body></html>
        <?php
        exit;
    }
}

function admin_nav_html(string $actif = ''): string
{
    $u = current_user();
    $items = [
        'index' => ['index.php', 'Tableau de bord'],
        'textes' => ['textes.php', 'Textes'],
        'categories' => ['categories.php', 'Catégories'],
        'soumissions' => ['soumissions.php', 'Soumissions'],
    ];
    if ($u !== null && $u['role'] === 'admin') {
        $items['utilisateurs'] = ['utilisateurs.php', 'Utilisateurs'];
    }
    $html = '<nav class="admin-nav">';
    foreach ($items as $slug => [$lien, $label]) {
        $html .= '<a href="' . h($lien) . '"' . ($slug === $actif ? ' class="actif"' : '') . '>' . h($label) . '</a>';
    }
    $html .= '<a href="../site/accueil.php" target="_blank">Voir le site &rarr;</a>';
    if ($u !== null) {
        $roleLabel = $u['role'] === 'admin' ? 'admin' : 'lecteur';
        $roleClass = $u['role'] === 'admin' ? ' role-admin' : '';
        $html .= '<span class="qui-suis-je">' . h($u['username'])
            . '<span class="badge-role' . $roleClass . '">' . h($roleLabel) . '</span>'
            . ' &middot; <a href="mon-compte.php">Mon compte</a>'
            . ' &middot; <a href="logout.php">Se déconnecter</a></span>';
    }
    return $html . '</nav>';
}
