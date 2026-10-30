<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

header('Location: ' . (auth_check() ? 'dashboard.php' : 'login.php'));
exit;
