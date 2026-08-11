<?php

declare(strict_types=1);

require __DIR__ . '/_lib.php';
auth_start_session();

// Deja connecte : inutile de repasser par le formulaire.
if (current_user() !== null) {
    header('Location: index.php');
    exit;
}

$next = (string) ($_GET['next'] ?? $_POST['next'] ?? 'index.php');
// N'accepte qu'un chemin relatif interne a /admin/ -- jamais une URL absolue
// ou externe (protection open-redirect basique).
if (!preg_match('#^[a-zA-Z0-9_\-]+\.php(\?[^\s]*)?$#', $next)) {
    $next = 'index.php';
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $pdo = poesie_db();
    $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :u');
    $stmt->execute(['u' => $username]);
    $user = $stmt->fetch();

    if ($user !== false && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: ' . $next);
        exit;
    }
    // Message volontairement generique -- ne revele pas si c'est
    // l'identifiant ou le mot de passe qui est incorrect.
    $erreur = 'Identifiant ou mot de passe incorrect.';
}
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — Administration du corpus</title>
<style><?= ADMIN_STYLE ?></style>
</head>
<body>

<div class="login-box">
<h1>Administration du corpus</h1>

<?php if ($erreur !== ''): ?>
<div class="msg-erreurs"><?= h($erreur) ?></div>
<?php endif; ?>

<form class="editeur" method="post">
<input type="hidden" name="next" value="<?= h($next) ?>">
<label for="username">Identifiant</label>
<input type="text" id="username" name="username" required autofocus>
<label for="password">Mot de passe</label>
<input type="password" id="password" name="password" required>
<p><button class="primaire" type="submit" style="width: 100%;">Se connecter</button></p>
</form>
</div>

</body>
</html>
