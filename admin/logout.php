<?php

declare(strict_types=1);

require __DIR__ . '/_lib.php';
auth_start_session();

$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
