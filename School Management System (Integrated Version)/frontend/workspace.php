<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
auth_require_login();

$systemKey = (string) ($_GET['system'] ?? '');
$allSystems = auth_systems();

if (!isset($allSystems[$systemKey])) {
    header('Location: dashboard.php');
    exit;
}

// Enforces the role boundary: a student cannot open the finance workspace, etc.
auth_require_system($systemKey);

// Finance now has a real workspace; keep the old access URL working.
if ($systemKey === 'finance') {
    header('Location: ' . auth_frontend_url('src/pages/finance/'));
    exit;
}

$user = auth_user();
$role = auth_role($user['role']);
$system = $allSystems[$systemKey];
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$initials = auth_initials($user['name']);
$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$words = preg_split('/\s+/', $system['label']) ?: [$system['label']];
$lastWord = count($words) > 1 ? array_pop($words) : '';
$labelHead = implode(' ', $words);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Role-protected workspace access for Fullbright College.">
    <title><?= $e($system['label']) ?> · Fullbright College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="src/assets/css/auth.css?v=20261030">
    <link rel="stylesheet" href="src/assets/css/dashboard.css?v=20261030">
</head>
<body>
    <header class="auth-topbar">
        <div class="auth-topbar__inner">
            <a class="auth-brand" href="dashboard.php" aria-label="Fullbright College role workspace">
                <span class="auth-brand__seal" aria-hidden="true">
                    <img src="src/assets/images/fbc.png" alt="">
                </span>
                <span class="auth-brand__copy">
                    <strong>Fullbright College</strong>
                    <small>School Management System</small>
                </span>
            </a>

            <div class="auth-context">
                <small>Workspace access</small>
                <strong><?= $e($system['label']) ?></strong>
            </div>

            <div class="auth-topbar__tools">
                <span class="directory-status">
                    <span class="status-dot" aria-hidden="true"></span>
                    Access cleared
                </span>

                <time class="auth-clock" datetime="<?= $e($now->format(DateTimeInterface::ATOM)) ?>" aria-label="Current date and time in Manila">
                    <span><?= $e($now->format('D, M j')) ?></span>
                    <strong><?= $e($now->format('g:i A')) ?> PHT</strong>
                </time>

                <div class="dash-user">
                    <span class="dash-user__avatar" aria-hidden="true"><?= $e($initials) ?></span>
                    <span class="dash-user__copy">
                        <strong><?= $e($user['name']) ?></strong>
                        <small><?= $e($role['short']) ?></small>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <main class="workspace-main">
        <section class="workspace-hero" aria-labelledby="workspace-heading">
            <p class="eyebrow"><?= $e($system['context']) ?> &mdash; <?= $e($now->format('l, F j, Y')) ?></p>
            <h1 id="workspace-heading">
                <?= $labelHead !== '' ? $e($labelHead) . ' ' : '' ?><em><?= $e($lastWord !== '' ? $lastWord : $system['label']) ?>.</em>
            </h1>
            <p class="auth-lede">
                <?= $e($system['description']) ?> Your <?= $e($role['short']) ?> account has been cleared for this
                workspace, and every request is checked against the campus access directory.
            </p>
        </section>

        <div class="workspace-grid">
            <section class="workspace-panel" aria-labelledby="granted-heading">
                <h2 id="granted-heading">Access granted</h2>
                <div class="access-granted">
                    <span class="status-dot" aria-hidden="true"></span>
                    <span><?= $e($role['label']) ?> &middot; <?= $e($user['username']) ?></span>
                </div>
                <p>
                    The route <strong><?= $e($systemKey) ?></strong> is inside your role scope. The guard checks your
                    signed session on every request, so opening this URL with a different account is refused.
                </p>
                <a class="back-link" href="dashboard.php">&larr; Back to my dashboard</a>
            </section>

            <section class="workspace-panel" aria-labelledby="module-heading">
                <h2 id="module-heading">Module status</h2>
                <p>
                    <?= $e($system['label']) ?> is connected to the campus access directory. Its module screens will mount
                    here as the integrated workspace is finalized.
                </p>
                <p>
                    Until then, this page is the access checkpoint for the role. It confirms who is signed in, which
                    workspace was requested, and whether the role is allowed to open it.
                </p>
            </section>
        </div>
    </main>

    <footer class="auth-footer">
        <span>Fullbright College &middot; Integrated School Management System</span>
        <span><?= $e($system['label']) ?> &middot; <?= $e($role['short']) ?></span>
    </footer>
</body>
</html>
