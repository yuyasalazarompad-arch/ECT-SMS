<?php
declare(strict_types=1);

$financePage = $financePage ?? 'dashboard';
$financeTitle = $financeTitle ?? 'Admin Dashboard';
$financeSubtitle = $financeSubtitle ?? 'Finance overview';

$financeNavigation = [
    'dashboard' => ['label' => 'Dashboard', 'href' => './dashboard.php', 'icon' => 'dashboard'],
    'students' => ['label' => 'Students', 'href' => './students.php', 'icon' => 'students'],
    'fees' => ['label' => 'Fees', 'href' => './fees.php', 'icon' => 'fees'],
    'payments' => ['label' => 'Payments', 'href' => './payments.php', 'icon' => 'payments'],
    'reports' => ['label' => 'Financial Reports', 'href' => './reports.php', 'icon' => 'reports'],
    'notifications' => ['label' => 'Notifications', 'href' => './notifications.php', 'icon' => 'bell'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= fin_e($financeSubtitle) ?> for the Fullbright College finance and reporting workspace.">
    <title><?= fin_e($financeTitle) ?> · Finance &amp; Reporting · Fullbright College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../../../src/assets/css/finance.css?v=20261102-dashboard-compact">
    <link rel="stylesheet" href="../../../src/assets/css/palette.css?v=20261102-palette-v2">
    <script src="../../../src/assets/js/finance.js?v=20261030" defer></script>
</head>
<body>
    <header class="fin-topbar">
        <div class="fin-topbar__inner">
            <a class="fin-brand" href="./dashboard.php" aria-label="Finance and reporting dashboard">
                <span class="fin-brand__seal" aria-hidden="true">
                    <img src="../../../src/assets/images/fbc.png" alt="">
                </span>
                <span class="fin-brand__copy">
                    <strong>Fullbright College</strong>
                    <small>School Management System</small>
                </span>
            </a>

            <div class="fin-context">
                <small>Fees &amp; Payments</small>
                <strong>Finance &amp; Reporting</strong>
            </div>

            <div class="fin-tools">
                <details class="fin-menu" data-fin-menu>
                    <summary class="fin-menu__trigger">
                        <?= fin_icon('menu') ?>
                        <span>Navigation</span>
                        <?= fin_icon('chevron', 'fin-chevron') ?>
                    </summary>
                    <div class="fin-menu__panel">
                        <p class="fin-menu__label">Finance workspace</p>
                        <?php foreach ($financeNavigation as $key => $item): ?>
                            <a href="<?= fin_e($item['href']) ?>"<?= $key === $financePage ? ' aria-current="page"' : '' ?>>
                                <?= fin_icon($item['icon']) ?>
                                <span><?= fin_e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <div class="fin-menu__divider" aria-hidden="true"></div>
                        <a href="../../../dashboard.php">
                            <?= fin_icon('home') ?>
                            <span>Campus dashboard</span>
                        </a>
                    </div>
                </details>

                <time class="fin-clock" datetime="<?= fin_e($financeNow->format(DateTimeInterface::ATOM)) ?>" aria-label="Current date and time in Manila">
                    <span id="fin-clock-date"><?= fin_e($financeNow->format('D, M j')) ?></span>
                    <strong id="fin-clock-time"><?= fin_e($financeNow->format('g:i A')) ?> PHT</strong>
                </time>

                <details class="fin-account" data-fin-menu>
                    <summary class="fin-account__trigger">
                        <span class="fin-avatar" aria-hidden="true"><?= fin_e($financeInitials) ?></span>
                        <span class="fin-account__copy">
                            <strong><?= fin_e($financeUser['name']) ?></strong>
                            <small><?= fin_e($financeRole['short']) ?></small>
                        </span>
                        <?= fin_icon('chevron', 'fin-chevron') ?>
                    </summary>
                    <div class="fin-account__panel">
                        <div class="fin-account__head">
                            <span class="fin-avatar" aria-hidden="true"><?= fin_e($financeInitials) ?></span>
                            <div>
                                <strong><?= fin_e($financeUser['name']) ?></strong>
                                <small><?= fin_e($financeRole['label']) ?></small>
                            </div>
                        </div>
                        <a class="fin-account__link" href="../../../dashboard.php">
                            <?= fin_icon('home') ?>
                            <span>Campus dashboard</span>
                        </a>
                        <form method="post" action="../../../logout.php">
                            <input type="hidden" name="csrf_token" value="<?= fin_e($financeCsrf) ?>">
                            <button class="fin-account__link fin-account__link--danger" type="submit">
                                <?= fin_icon('logout') ?>
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main class="fin-main<?= $financePage === 'dashboard' ? ' fin-main--dashboard' : '' ?>" id="main-content">
