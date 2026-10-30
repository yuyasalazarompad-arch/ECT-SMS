<?php
declare(strict_types=1);

$attendancePage = $attendancePage ?? 'overview';
$attendanceTitle = $attendanceTitle ?? 'Attendance Overview';
$attendanceSubtitle = $attendanceSubtitle ?? 'Take attendance and review daily operations';

$attendanceNavigation = [
    'overview' => ['label' => 'Attendance Overview', 'href' => './dashboard.php', 'icon' => 'overview'],
    'teacher' => ['label' => 'Teacher Schedule', 'href' => './teacher-schedule.php', 'icon' => 'teacher'],
    'student' => ['label' => 'Student Schedule', 'href' => './student-schedule.php', 'icon' => 'student'],
    'rooms' => ['label' => 'Room Monitoring', 'href' => './rooms.php', 'icon' => 'rooms'],
    'conflicts' => ['label' => 'Conflict Detection', 'href' => './conflicts.php', 'icon' => 'conflict'],
];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= attn_e($attendanceSubtitle) ?> for Fullbright College attendance and scheduling.">
    <title><?= attn_e($attendanceTitle) ?> · Attendance &amp; Scheduling · Fullbright College</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../../../src/assets/css/attendance.css?v=20261102-topbar-v2">
    <link rel="stylesheet" href="../../../src/assets/css/palette.css?v=20261102-palette-v2">
    <script src="../../../src/assets/js/attendance.js?v=20261102" defer></script>
</head>
<body>
    <header class="attn-topbar">
        <div class="attn-topbar__inner">
            <a class="attn-brand" href="./dashboard.php" aria-label="Attendance and scheduling dashboard">
                <span class="attn-brand__seal" aria-hidden="true">
                    <img src="../../../src/assets/images/fbc.png" alt="">
                </span>
                <span class="attn-brand__copy">
                    <strong>School Management System</strong>
                    <small>Attendance &amp; Scheduling Dashboard</small>
                </span>
            </a>

            <div class="attn-tools">
                <details class="attn-menu" data-attn-menu>
                    <summary class="attn-menu__trigger">
                        <?= attn_icon('menu') ?>
                        <span>Menu</span>
                        <?= attn_icon('chevron', 'attn-chevron') ?>
                    </summary>
                    <div class="attn-menu__panel">
                        <p class="attn-menu__label">Attendance workspace</p>
                        <?php foreach ($attendanceNavigation as $key => $item): ?>
                            <a href="<?= attn_e($item['href']) ?>"<?= $key === $attendancePage ? ' aria-current="page"' : '' ?>>
                                <?= attn_icon($item['icon']) ?>
                                <span><?= attn_e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                        <div class="attn-menu__divider" aria-hidden="true"></div>
                        <a href="../../../dashboard.php">
                            <?= attn_icon('home') ?>
                            <span>Campus dashboard</span>
                        </a>
                    </div>
                </details>

                <time class="attn-clock" datetime="<?= attn_e($attendanceNow->format(DateTimeInterface::ATOM)) ?>" aria-label="Current date and time in Manila">
                    <span id="attn-clock-date"><?= attn_e($attendanceNow->format('D, M j')) ?></span>
                    <strong id="attn-clock-time"><?= attn_e($attendanceNow->format('g:i A')) ?> PHT</strong>
                </time>

                <details class="attn-account" data-attn-menu>
                    <summary class="attn-account__trigger">
                        <span class="attn-avatar" aria-hidden="true"><?= attn_e($attendanceInitials) ?></span>
                        <span class="attn-account__copy">
                            <strong><?= attn_e($attendanceUser['name']) ?></strong>
                            <small><?= attn_e($attendanceRole['short']) ?></small>
                        </span>
                        <?= attn_icon('chevron', 'attn-chevron') ?>
                    </summary>
                    <div class="attn-account__panel">
                        <div class="attn-account__head">
                            <span class="attn-avatar" aria-hidden="true"><?= attn_e($attendanceInitials) ?></span>
                            <div>
                                <strong><?= attn_e($attendanceUser['name']) ?></strong>
                                <small><?= attn_e($attendanceRole['label']) ?></small>
                            </div>
                        </div>
                        <a class="attn-account__link" href="../../../dashboard.php">
                            <?= attn_icon('home') ?>
                            <span>Campus dashboard</span>
                        </a>
                        <form method="post" action="../../../logout.php">
                            <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
                            <button class="attn-account__link attn-account__link--danger" type="submit">
                                <?= attn_icon('logout') ?>
                                <span>Sign out</span>
                            </button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main class="attn-main" id="main-content">
