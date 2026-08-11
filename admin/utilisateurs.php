<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';
require_role_admin();

$moi = current_user();
$pdo = poesie_db();

function nb_admins(PDO $pdo): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

$erreurs = [];
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'creer') {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? 'lecteur');

        if (!preg_match('/^[a-zA-Z0-9_.\-]{3,60}$/', $username)) {
            $erreurs[] = 'Identifiant invalide (3 à 60 caractères, lettres/chiffres/._- uniquement).';
        }
        if (strlen($password) < 6) {
            $erreurs[] = 'Le mot de passe doit faire au moins 6 caractères.';
        }
        if (!in_array($role, ['admin', 'lecteur'], true)) {
            $erreurs[] = 'Rôle invalide.';
        }
        if ($erreurs === []) {
            $stmtCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u');
            $stmtCheck->execute(['u' => $username]);
            if ((int) $stmtCheck->fetchColumn() > 0) {
                $erreurs[] = 'Cet identifiant existe déjà.';
            } else {
                $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)')
                    ->execute(['u' => $username, 'p' => password_hash($password, PASSWORD_DEFAULT), 'r' => $role]);
                $succes = "Compte créé : {$username} ({$role})";
            }
        }
    } elseif ($action === 'changer_role') {
        $id = (int) ($_POST['id'] ?? 0);
        $role = (string) ($_POST['role'] ?? '');
        if (!in_array($role, ['admin', 'lecteur'], true)) {
            $erreurs[] = 'Rôle invalide.';
        } elseif ($id === $moi['id'] && $role !== 'admin' && nb_admins($pdo) <= 1) {
            $erreurs[] = 'Impossible de retirer ton propre rôle admin : tu es le dernier administrateur.';
        } else {
            $pdo->prepare('UPDATE users SET role = :r WHERE id = :id')->execute(['r' => $role, 'id' => $id]);
            $succes = 'Rôle mis à jour.';
        }
    } elseif ($action === 'reinitialiser_mdp') {
        $id = (int) ($_POST['id'] ?? 0);
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 6) {
            $erreurs[] = 'Le nouveau mot de passe doit faire au moins 6 caractères.';
        } else {
            $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
                ->execute(['h' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
            $succes = 'Mot de passe réinitialisé.';
        }
    } elseif ($action === 'supprimer') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === $moi['id']) {
            $erreurs[] = 'Impossible de supprimer ton propre compte.';
        } else {
            $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $roleCible = $stmt->fetchColumn();
            if ($roleCible === 'admin' && nb_admins($pdo) <= 1) {
                $erreurs[] = 'Impossible de supprimer le dernier compte administrateur.';
            } else {
                $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $id]);
                $succes = 'Compte supprimé.';
            }
        }
    }
}

$utilisateurs = $pdo->query('SELECT id, username, role, cree_le FROM users ORDER BY cree_le ASC')->fetchAll();
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Administration — Utilisateurs</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('utilisateurs') ?>

<h1>Utilisateurs</h1>

<p>Rôle <strong>admin</strong> : contrôle total (validation, création, modification, suppression). Rôle <strong>lecteur</strong> : consultation uniquement, aucune action de modification.</p>

<?php if ($succes !== ''): ?>
<p class="msg-succes"><?= h($succes) ?></p>
<?php endif; ?>
<?php if ($erreurs !== []): ?>
<div class="msg-erreurs"><ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<table>
<thead><tr><th>Identifiant</th><th>Rôle</th><th>Créé le</th><th>Changer le rôle</th><th>Réinitialiser le mot de passe</th><th>Supprimer</th></tr></thead>
<tbody>
<?php foreach ($utilisateurs as $user): ?>
<tr>
<td><?= h($user['username']) ?><?= (int) $user['id'] === $moi['id'] ? ' <span class="badge">toi</span>' : '' ?></td>
<td><span class="badge<?= $user['role'] === 'admin' ? ' badge-fondateur' : '' ?>"><?= h($user['role']) ?></span></td>
<td><?= h((string) $user['cree_le']) ?></td>
<td>
<form class="actions-form" method="post">
<input type="hidden" name="action" value="changer_role">
<input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
<select name="role" onchange="this.form.submit()">
<option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
<option value="lecteur" <?= $user['role'] === 'lecteur' ? 'selected' : '' ?>>lecteur</option>
</select>
</form>
</td>
<td>
<form class="actions-form" method="post" onsubmit="const p = prompt('Nouveau mot de passe pour <?= h($user['username']) ?> (6 caractères minimum) :'); if (!p || p.length < 6) { return false; } this.querySelector('[name=password]').value = p;">
<input type="hidden" name="action" value="reinitialiser_mdp">
<input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
<input type="hidden" name="password" value="">
<button class="neutre" type="submit">Réinitialiser</button>
</form>
</td>
<td>
<?php if ((int) $user['id'] !== $moi['id']): ?>
<form class="actions-form" method="post" onsubmit="return confirm('Supprimer le compte <?= h($user['username']) ?> ?')">
<input type="hidden" name="action" value="supprimer">
<input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
<button class="danger" type="submit">Supprimer</button>
</form>
<?php else: ?>
&mdash;
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h2>Créer un compte</h2>
<form class="editeur" method="post" style="max-width: 360px;">
<input type="hidden" name="action" value="creer">
<label for="username">Identifiant</label>
<input type="text" id="username" name="username" required pattern="[a-zA-Z0-9_.\-]{3,60}">
<label for="password">Mot de passe</label>
<input type="password" id="password" name="password" required minlength="6">
<label for="role">Rôle</label>
<select id="role" name="role">
<option value="lecteur">lecteur (lecture seule)</option>
<option value="admin">admin (contrôle total)</option>
</select>
<p><button class="primaire" type="submit">Créer</button></p>
</form>

</body>
</html>
