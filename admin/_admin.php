<?php

declare(strict_types=1);

require __DIR__ . '/_lib.php';

// Chaque page d'administration inclut ce fichier en tête -- point de
// passage unique qui impose d'être connecté. `login.php` n'inclut PAS ce
// fichier (il inclut directement `_lib.php`) pour éviter une boucle de
// redirection vers lui-même.
require_login();
