<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (auth_check()) {
    header('Location: dashboard.php');
    exit;
}

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$error = '';
$username = '';
$loggedOut = isset($_GET['logged_out']);
$directoryOnline = auth_directory_online();
$showDirectoryAlert = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $csrfToken = $_POST['csrf_token'] ?? null;

    if (!auth_verify_csrf(is_string($csrfToken) ? $csrfToken : null)) {
        $error = 'Your sign-in session expired. Please try again.';
    } elseif (auth_throttle_remaining() <= 0) {
        $error = 'Too many failed attempts. Please wait a few minutes before trying again.';
    } else {
        try {
            $user = auth_attempt($username, $password);
        } catch (Throwable $e) {
            $user = null;
            $directoryOnline = false;
            $showDirectoryAlert = true;
            $error = 'The campus directory is temporarily unavailable. Please try again shortly.';
        }

        if (!empty($user)) {
            auth_login_session($user);
            header('Location: dashboard.php');
            exit;
        }

        if ($error === '') {
            auth_record_failure();
            $error = 'That username or password does not match an active campus account.';
        }
    }
}

$roles = auth_roles();
$systemAdminRoles = array_filter(
    $roles,
    static fn (array $role, string $key): bool => str_ends_with($key, '_admin')
        && !in_array($key, ['super_admin', 'admin'], true),
    ARRAY_FILTER_USE_BOTH
);
$demoAccounts = auth_demo_accounts();
$systemAdminNames = array_map(
    static fn (array $role): string => str_replace(' Admin', '', $role['short']),
    $systemAdminRoles
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Sign in to the Fullbright College integrated school management system. Role-based access for campus administrators, teachers, and students.">
    <title>Sign in · Fullbright College · School Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="src/assets/css/auth.css?v=20261030">
</head>
<body>
    <header class="auth-topbar">
        <div class="auth-topbar__inner">
            <a class="auth-brand" href="login.php" aria-label="Fullbright College school management system sign in">
                <span class="auth-brand__seal" aria-hidden="true">
                    <img src="src/assets/images/fbc.png" alt="">
                </span>
                <span class="auth-brand__copy">
                    <strong>Fullbright College</strong>
                    <small>School Management System</small>
                </span>
            </a>

            <div class="auth-context">
                <small>Campus workspace</small>
                <strong>Secure sign in</strong>
            </div>

            <div class="auth-topbar__tools">
                <span class="directory-status<?= $directoryOnline ? '' : ' is-offline' ?>">
                    <span class="status-dot" aria-hidden="true"></span>
                    <?= $directoryOnline ? 'Directory online' : 'Directory offline' ?>
                </span>
                <time class="auth-clock" datetime="<?= htmlspecialchars($now->format(DateTimeInterface::ATOM), ENT_QUOTES, 'UTF-8') ?>" aria-label="Current date and time in Manila">
                    <span><?= htmlspecialchars($now->format('D, M j'), ENT_QUOTES, 'UTF-8') ?></span>
                    <strong id="auth-clock-time"><?= htmlspecialchars($now->format('g:i A'), ENT_QUOTES, 'UTF-8') ?> PHT</strong>
                </time>
            </div>
        </div>
    </header>

    <main class="auth-main">
        <section class="auth-intro" aria-labelledby="login-heading">
            <p class="eyebrow">Campus workspace &mdash; secure access</p>
            <h1 id="login-heading">Good to see you, <em>Campus.</em></h1>
            <p class="auth-lede">
                One account opens the workspaces your role is cleared for &mdash; from the whole campus overview
                to a single system desk, a faculty gradebook, or the student portal.
            </p>

            <div class="access-grid" aria-label="Role-based campus access">
                <article class="access-card accent-navy">
                    <span class="access-meta"><span class="access-dot" aria-hidden="true"></span>Leadership</span>
                    <strong>Super Admin</strong>
                    <p>Every system, every campus record, and the full overview.</p>
                </article>

                <article class="access-card accent-blue">
                    <span class="access-meta"><span class="access-dot" aria-hidden="true"></span>System administrators</span>
                    <strong><?= count($systemAdminRoles) ?> desks</strong>
                    <p><?= htmlspecialchars(implode(' · ', $systemAdminNames), ENT_QUOTES, 'UTF-8') ?></p>
                </article>

                <article class="access-card accent-gold">
                    <span class="access-meta"><span class="access-dot" aria-hidden="true"></span>Faculty</span>
                    <strong>Teacher</strong>
                    <p>Classes, attendance, and the gradebook for assigned subjects.</p>
                </article>

                <article class="access-card accent-green">
                    <span class="access-meta"><span class="access-dot" aria-hidden="true"></span>Students</span>
                    <strong>Student</strong>
                    <p>Results, records, and the enrollment and student portal.</p>
                </article>
            </div>
        </section>

        <section class="login-panel" aria-labelledby="panel-heading">
            <div class="login-status">
                <span class="dot<?= $directoryOnline ? '' : ' is-offline' ?>" aria-hidden="true"></span>
                <span><?= $directoryOnline ? 'Campus directory online' : 'Campus directory offline' ?></span>
                <span class="sep" aria-hidden="true">&middot;</span>
                <span>Secure session</span>
            </div>

            <p class="panel-kicker">Campus sign in</p>
            <h2 id="panel-heading">Welcome back.</h2>
            <p class="panel-lede">Enter your campus credentials. Your role decides which workspace opens.</p>

            <?php if ($loggedOut): ?>
                <div class="notice" role="status">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                    <span>You have been signed out. Your session is closed.</span>
                </div>
            <?php endif; ?>

            <?php if ($showDirectoryAlert): ?>
                <div class="alert" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.3 4.6 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0Z"/></svg>
                    <span>The campus directory is offline right now. Make sure MySQL is running, then refresh this page.</span>
                </div>
            <?php elseif ($error !== ''): ?>
                <div class="alert" role="alert">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01M10.3 4.6 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.6a2 2 0 0 0-3.4 0Z"/></svg>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form class="login-form" method="post" action="login.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(auth_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

                <div class="field">
                    <label for="username">Username or email</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        value="<?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. fbcadmin"
                        autocomplete="username"
                        autocapitalize="none"
                        spellcheck="false"
                        required
                        autofocus
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <button class="password-toggle" type="button" id="password-toggle" aria-label="Show password" aria-pressed="false">Show</button>
                    </div>
                </div>

                <div class="form-meta">
                    <span class="auto-role">Role is detected automatically</span>
                    <span class="help">Need help? Contact the registrar.</span>
                </div>

                <button class="submit" type="submit">
                    Sign in
                    <span aria-hidden="true">&rarr;</span>
                </button>
            </form>

            <?php if ($demoAccounts): ?>
                <details class="demo">
                    <summary>
                        <span>Demo accounts</span>
                        <small>localhost only &middot; click to fill</small>
                    </summary>
                    <div class="demo-grid">
                        <?php foreach ($demoAccounts as $account): ?>
                            <?php $demoRole = auth_role($account['role']); ?>
                            <button
                                class="demo-account"
                                type="button"
                                data-username="<?= htmlspecialchars($account['username'], ENT_QUOTES, 'UTF-8') ?>"
                                data-password="<?= htmlspecialchars($account['password'], ENT_QUOTES, 'UTF-8') ?>"
                            >
                                <strong><?= htmlspecialchars($demoRole['short'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($account['username'], ENT_QUOTES, 'UTF-8') ?></small>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>

            <p class="secure-note">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 10V8a6 6 0 0 1 12 0v2m-9 0h6a3 3 0 0 1 3 3v5a3 3 0 0 1-3 3H9a3 3 0 0 1-3-3v-5a3 3 0 0 1 3-3Z"/></svg>
                <span>Protected by campus access controls. Unauthorized access is recorded.</span>
            </p>
        </section>
    </main>

    <footer class="auth-footer">
        <span>Fullbright College &middot; Integrated School Management System</span>
        <span>Established 1998 &middot; Asia/Manila</span>
    </footer>

    <script>
        (() => {
            const clock = document.getElementById('auth-clock-time');
            const timeFormat = new Intl.DateTimeFormat('en-PH', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Manila'
            });

            const tick = () => {
                if (clock) {
                    clock.textContent = timeFormat.format(new Date()) + ' PHT';
                }
            };

            tick();
            window.setInterval(tick, 30000);

            const password = document.getElementById('password');
            const toggle = document.getElementById('password-toggle');

            toggle?.addEventListener('click', () => {
                if (!password) return;
                const showing = password.type === 'text';
                password.type = showing ? 'password' : 'text';
                toggle.textContent = showing ? 'Show' : 'Hide';
                toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
                toggle.setAttribute('aria-pressed', showing ? 'false' : 'true');
                password.focus();
            });

            document.querySelectorAll('.demo-account').forEach((button) => {
                button.addEventListener('click', () => {
                    const username = document.getElementById('username');
                    if (username) username.value = button.dataset.username || '';
                    if (password) password.value = button.dataset.password || '';
                    username?.focus();
                });
            });
        })();
    </script>
</body>
</html>
