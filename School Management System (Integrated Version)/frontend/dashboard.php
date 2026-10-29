<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
auth_require_login();

$user = auth_user();
$role = auth_role($user['role']);
$systems = auth_systems_for($user['role']);
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$hour = (int) $now->format('G');
$daypart = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
$loginAt = (new DateTimeImmutable('@' . (int) ($_SESSION['auth_login_at'] ?? time())))
    ->setTimezone(new DateTimeZone('Asia/Manila'));
$initials = auth_initials($user['name']);
$systemNames = array_map(static fn (array $system): string => $system['label'], $systems);
$e = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Role-based campus workspace for Fullbright College.">
    <title><?= $e($role['short']) ?> workspace · Fullbright College</title>
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
                <small>Campus workspace</small>
                <strong>Role-based overview</strong>
            </div>

            <div class="auth-topbar__tools">
                <span class="directory-status">
                    <span class="status-dot" aria-hidden="true"></span>
                    Session active
                </span>

                <time class="auth-clock" datetime="<?= $e($now->format(DateTimeInterface::ATOM)) ?>" aria-label="Current date and time in Manila">
                    <span><?= $e($now->format('D, M j')) ?></span>
                    <strong id="auth-clock-time"><?= $e($now->format('g:i A')) ?> PHT</strong>
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

    <main class="dash-main">
        <section class="dash-hero" aria-labelledby="dashboard-heading">
            <div>
                <p class="eyebrow">Role workspace &mdash; <?= $e($now->format('l, F j, Y')) ?></p>
                <h1 id="dashboard-heading">Good <em><?= $e($daypart) ?>,</em> <?= $e($user['name']) ?>.</h1>
                <p class="auth-lede">
                    You are signed in as <strong><?= $e($role['label']) ?></strong>. Only the workspaces
                    assigned to this role appear below, so every desk stays inside its lane.
                </p>
            </div>

            <div class="session-card" aria-label="Session summary">
                <span class="ping" aria-hidden="true"></span>
                <div>
                    <strong><?= count($systems) ?></strong>
                    <small><?= count($systems) === 1 ? 'workspace assigned' : 'workspaces assigned' ?></small>
                </div>
            </div>
        </section>

        <section class="dash-kpis" aria-label="Account and access summary">
            <article class="dash-kpi accent-navy">
                <span class="kpi-label"><span class="kpi-dot" aria-hidden="true"></span>Role</span>
                <span class="kpi-num is-text"><?= $e($role['short']) ?></span>
                <span class="kpi-hint"><?= $e($role['summary']) ?></span>
            </article>

            <article class="dash-kpi accent-blue">
                <span class="kpi-label"><span class="kpi-dot" aria-hidden="true"></span>Workspaces</span>
                <span class="kpi-num"><?= count($systems) ?></span>
                <span class="kpi-hint">of 5 campus systems</span>
            </article>

            <article class="dash-kpi accent-gold">
                <span class="kpi-label"><span class="kpi-dot" aria-hidden="true"></span>Signed in</span>
                <span class="kpi-num"><?= $e($loginAt->format('g:i A')) ?></span>
                <span class="kpi-hint"><?= $e($loginAt->format('D, M j')) ?> &middot; Asia/Manila</span>
            </article>

            <article class="dash-kpi accent-green">
                <span class="kpi-label"><span class="kpi-dot" aria-hidden="true"></span>Session</span>
                <span class="kpi-num is-text">Active</span>
                <span class="kpi-hint">Expires after two idle hours</span>
            </article>
        </section>

        <section class="dash-section" aria-labelledby="workspaces-heading">
            <div class="section-head">
                <div>
                    <p class="eyebrow">Access map</p>
                    <h2 id="workspaces-heading">Your workspaces</h2>
                </div>
                <span class="chip"><?= count($systems) ?> assigned</span>
            </div>

            <div class="system-grid">
                <?php foreach ($systems as $key => $system): ?>
                    <a class="system-card accent-<?= $e($system['accent']) ?>" href="<?= $e($key === 'finance' ? $system['href'] : 'workspace.php?system=' . $key) ?>">
                        <span class="system-top">
                            <span class="system-dot" aria-hidden="true"></span>
                            <span class="system-context"><?= $e($system['context']) ?></span>
                        </span>
                        <h3><?= $e($system['label']) ?></h3>
                        <p><?= $e($system['description']) ?></p>
                        <span class="system-foot">
                            <span>Access granted</span>
                            <span class="arrow" aria-hidden="true">&rarr;</span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="dash-bottom">
            <section class="panel" aria-labelledby="permissions-heading">
                <div class="panel-head">
                    <h2 id="permissions-heading">Role permissions</h2>
                    <span class="chip"><?= $e($role['short']) ?></span>
                </div>

                <div class="permission-list">
                    <div class="permission-row">
                        <span class="permission-key">Access scope</span>
                        <span class="permission-value"><?= $e(implode(' · ', $systemNames)) ?></span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-key">Account type</span>
                        <span class="permission-value"><?= $e($role['label']) ?></span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-key">Entry point</span>
                        <span class="permission-value">Role dashboard &middot; <?= $e($user['username']) ?></span>
                    </div>
                    <div class="permission-row">
                        <span class="permission-key">Session policy</span>
                        <span class="permission-value">Two-hour idle timeout &middot; sign-out clears the session</span>
                    </div>
                </div>

                <p class="panel-note">
                    System administrators only see their own module. Super Admin and Admin accounts can see every
                    module, while Teacher and Student accounts see only their assigned learning workspaces.
                </p>
            </section>

            <aside class="panel" aria-labelledby="account-heading">
                <div class="panel-head">
                    <h2 id="account-heading">Account</h2>
                </div>

                <div class="account-card">
                    <span class="account-avatar" aria-hidden="true"><?= $e($initials) ?></span>
                    <span class="account-copy">
                        <strong><?= $e($user['name']) ?></strong>
                        <small><?= $e($role['label']) ?></small>
                    </span>
                </div>

                <dl class="account-meta">
                    <div>
                        <dt>Username</dt>
                        <dd><?= $e($user['username']) ?></dd>
                    </div>
                    <?php if (!empty($user['email'])): ?>
                        <div>
                            <dt>Email</dt>
                            <dd><?= $e($user['email']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt>Signed in</dt>
                        <dd><?= $e($loginAt->format('g:i A, M j')) ?></dd>
                    </div>
                    <div>
                        <dt>Session</dt>
                        <dd>Active</dd>
                    </div>
                </dl>

                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= $e(auth_csrf_token()) ?>">
                    <button class="logout-button" type="submit">Sign out</button>
                </form>
            </aside>
        </div>
    </main>

    <footer class="auth-footer">
        <span>Fullbright College &middot; Integrated School Management System</span>
        <span>Signed in as <?= $e($user['username']) ?> &middot; <?= $e($role['short']) ?></span>
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
        })();
    </script>
</body>
</html>
