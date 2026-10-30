<?php
declare(strict_types=1);

$enrollmentPage = $enrollmentPage ?? 'home';
$enrollmentTitle = $enrollmentTitle ?? 'Enrollment & Student Portal';
$enrollmentSubtitle = $enrollmentSubtitle ?? 'Apply for admission to Fullbright College';

$enrollmentNavigation = [
    'home' => ['label' => 'Home', 'href' => './index.php', 'icon' => 'home'],
    'enroll' => ['label' => 'Student Enrollment Form', 'href' => './enroll.php', 'icon' => 'form'],
];

if ($enrollmentSignedIn) {
    $enrollmentNavigation['portal'] = [
        'label' => 'My Portal',
        'href' => '../../../dashboard.php',
        'icon' => 'home',
    ];
} else {
    $enrollmentNavigation['login'] = [
        'label' => 'Student Login',
        'href' => enr_login_url(),
        'icon' => 'login',
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= enr_e($enrollmentSubtitle) ?> at Fullbright College.">
    <title><?= enr_e($enrollmentTitle) ?> · Enrollment &amp; Student Portal · Fullbright College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../../../src/assets/css/enrollment.css?v=20261102-layout-v2">
    <link rel="stylesheet" href="../../../src/assets/css/palette.css?v=20261102-palette-v2">
    <script src="../../../src/assets/js/enrollment.js?v=20261030" defer></script>
</head>
<body>
    <header class="enr-topbar">
        <div class="enr-topbar__inner">
            <a class="enr-brand" href="./index.php" aria-label="Enrollment and student portal home">
                <span class="enr-brand__seal" aria-hidden="true">
                    <img src="../../../src/assets/images/fbc.png" alt="">
                </span>
                <span class="enr-brand__copy">
                    <strong>Fullbright College</strong>
                    <small>School Management System</small>
                </span>
            </a>

            <div class="enr-context">
                <small>Admissions</small>
                <strong>Enrollment &amp; Student Portal</strong>
            </div>

            <div class="enr-tools">
                <details class="enr-menu" data-enr-menu>
                    <summary class="enr-menu__trigger">
                        <?= enr_icon('menu') ?>
                        <span>Navigation</span>
                        <?= enr_icon('chevron', 'enr-chevron') ?>
                    </summary>
                    <div class="enr-menu__panel">
                        <p class="enr-menu__label">Enrollment portal</p>
                        <?php foreach ($enrollmentNavigation as $key => $item): ?>
                            <a href="<?= enr_e($item['href']) ?>"<?= $key === $enrollmentPage ? ' aria-current="page"' : '' ?>>
                                <?= enr_icon($item['icon']) ?>
                                <span><?= enr_e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <div class="enr-menu__divider" aria-hidden="true"></div>
                        <a href="../../../dashboard.php">
                            <?= enr_icon('home') ?>
                            <span>Campus dashboard</span>
                        </a>
                    </div>
                </details>

                <time class="enr-clock" datetime="<?= enr_e($enrollmentNow->format(DateTimeInterface::ATOM)) ?>" aria-label="Current date and time in Manila">
                    <span id="enr-clock-date"><?= enr_e($enrollmentNow->format('D, M j')) ?></span>
                    <strong id="enr-clock-time"><?= enr_e($enrollmentNow->format('g:i A')) ?> PHT</strong>
                </time>

                <?php if ($enrollmentSignedIn): ?>
                    <details class="enr-account" data-enr-menu>
                        <summary class="enr-account__trigger">
                            <span class="enr-avatar" aria-hidden="true"><?= enr_e(enr_initials((string) $enrollmentUser['name'])) ?></span>
                            <span class="enr-account__copy">
                                <strong><?= enr_e($enrollmentUser['name']) ?></strong>
                                <small><?= enr_e(auth_role($enrollmentUser['role'])['short']) ?></small>
                            </span>
                            <?= enr_icon('chevron', 'enr-chevron') ?>
                        </summary>
                        <div class="enr-account__panel">
                            <div class="enr-account__head">
                                <span class="enr-avatar" aria-hidden="true"><?= enr_e(enr_initials((string) $enrollmentUser['name'])) ?></span>
                                <div>
                                    <strong><?= enr_e($enrollmentUser['name']) ?></strong>
                                    <small><?= enr_e(auth_role($enrollmentUser['role'])['label']) ?></small>
                                </div>
                            </div>
                            <a class="enr-account__link" href="../../../dashboard.php">
                                <?= enr_icon('home') ?>
                                <span>Campus dashboard</span>
                            </a>
                            <form method="post" action="../../../logout.php">
                                <input type="hidden" name="csrf_token" value="<?= enr_e($enrollmentCsrf) ?>">
                                <button class="enr-account__link enr-account__link--danger" type="submit">
                                    <?= enr_icon('lock') ?>
                                    <span>Sign out</span>
                                </button>
                            </form>
                        </div>
                    </details>
                <?php else: ?>
                    <a class="enr-login" href="<?= enr_e(enr_login_url()) ?>">
                        <?= enr_icon('login') ?>
                        <span>Student Login</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="enr-main" id="main-content">
