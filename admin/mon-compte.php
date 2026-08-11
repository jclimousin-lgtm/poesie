<?php

declare(strict_types=1);

require __DIR__ . '/_admin.php';

$u = current_user();
$pdo = poesie_db();

$erreurs = [];
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motDePasseActuel = (string) ($_POST['mot_de_passe_actuel'] ?? '');
    $nouveau = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
    $confirmation = (string) ($_POST['confirmation'] ?? '');

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
    $stmt->execute(['id' => $u['id']]);
    $hashActuel = (string) $stmt->fetchColumn();

    if (!password_verify($motDePasseActuel, $hashActuel)) {
        $erreurs[] = 'Mot de passe actuel incorrect.';
    }
    if (strlen($nouveau) < 6) {
        $erreurs[] = 'Le nouveau mot de passe doit faire au moins 6 caractères.';
    }
    if ($nouveau !== $confirmation) {
        $erreurs[] = 'La confirmation ne correspond pas au nouveau mot de passe.';
    }

    if ($erreurs === []) {
        $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id')
            ->execute(['h' => password_hash($nouveau, PASSWORD_DEFAULT), 'id' => $u['id']]);
        $succes = 'Mot de passe modifié.';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mon compte — Administration</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<?= admin_nav_html('') ?>

<h1>Mon compte</h1>

<p>Identifiant : <strong><?= h($u['username']) ?></strong> — rôle : <strong><?= h($u['role']) ?></strong></p>

<?php if ($succes !== ''): ?>
<p class="msg-succes"><?= h($succes) ?></p>
<?php endif; ?>
<?php if ($erreurs !== []): ?>
<div class="msg-erreurs"><ul><?php foreach ($erreurs as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<h2>Changer mon mot de passe</h2>
<form class="editeur" method="post" style="max-width: 360px;">
<label for="mot_de_passe_actuel">Mot de passe actuel</label>
<input type="password" id="mot_de_passe_actuel" name="mot_de_passe_actuel" required>
<label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
<input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required minlength="6">
<label for="confirmation">Confirmer le nouveau mot de passe</label>
<input type="password" id="confirmation" name="confirmation" required minlength="6">
<p><button class="primaire" type="submit">Enregistrer</button></p>
</form>

</body>
</html>
