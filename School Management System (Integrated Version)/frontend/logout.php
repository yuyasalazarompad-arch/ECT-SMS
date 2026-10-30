<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$token = $_POST['csrf_token'] ?? null;

if (
    $_SERVER['REQUEST_METHOD'] !== 'POST'
    || !auth_verify_csrf(is_string($token) ? $token : null)
) {
    header('Location: ' . (auth_check() ? 'dashboard.php' : 'login.php'));
    exit;
}

auth_logout();
header('Location: login.php?logged_out=1');
exit;
