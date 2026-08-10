<?php
declare(strict_types=1);
/**
 * Génère un hash .htpasswd compatible avec ce serveur (MD5-crypt standard
 * "$1$", le seul format accepté par l'AuthUserFile Apache d'o2switch —
 * bcrypt "$2y$" testé et refusé). Usage : php generer-htpasswd.php <mot_de_passe>
 */
$pass = $argv[1] ?? null;
if ($pass === null) {
    fwrite(STDERR, "Usage: php generer-htpasswd.php <mot_de_passe>\n");
    exit(1);
}
$salt = '$1$' . substr(str_replace(['+', '='], ['.', ''], base64_encode(random_bytes(6))), 0, 8) . '$';
echo crypt($pass, $salt) . "\n";
