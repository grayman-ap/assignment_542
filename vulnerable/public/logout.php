<?php
require __DIR__ . '/../src/config.php';
// VULN: no CSRF token, no session invalidation, session_destroy only.
session_unset();
session_destroy();
header('Location: login.php');
exit;
