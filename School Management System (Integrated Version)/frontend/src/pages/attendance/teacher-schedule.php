<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$attendancePage = 'teacher';
$attendanceTitle = 'Teacher Schedule';
$attendanceSubtitle = 'Hour-by-hour teaching timeline, availability, and daily load.';

$departments = $attendanceRepository->departments();
$teachers = $attendanceRepository->teachers();

$department = trim((string) ($_GET['department'] ?? ''));
if ($department !== '' && !in_array($department, $departments, true)) {
    $department = '';
}

$filteredTeachers = $department === ''
    ? $teachers
    : array_values(array_filter($teachers, static fn (array $row): bool => $row['department'] === $department));

$teacherId = (int) ($_GET['teacher'] ?? 0);
$selectedTeacher = null;
foreach ($filteredTeachers as $teacher) {
    if ($teacher['id'] === $teacherId) {
        $selectedTeacher = $teacher;
        break;
    }
}
if ($selectedTeacher === null) {
    foreach ($filteredTeachers as $teacher) {
        if ($teacher['employee_number'] === 'EMP-001') {
            $selectedTeacher = $teacher;
            break;
        }
    }
}
$selectedTeacher ??= $filteredTeachers[0] ?? null;

$dayOptions = AttendanceRepository::DAYS;
$todayName = $attendanceNow->format('l');
$day = trim((string) ($_GET['day'] ?? ''));
if (!in_array($day, $dayOptions, true)) {
    $day = '';
}

$schedules = $selectedTeacher !== null
    ? $attendanceRepository->teacherSchedules((int) $selectedTeacher['id'])
    : [];

if ($day === '') {
    $choice = in_array($todayName, $dayOptions, true) ? $todayName : 'Monday';
    $hasToday = false;

    foreach ($schedules as $row) {
        if ($row['day'] === $todayName) {
            $hasToday = true;
            break;
        }
    }

    if (!$hasToday) {
        foreach ($dayOptions as $option) {
            foreach ($schedules as $row) {
                if ($row['day'] === $option) {
                    $choice = $option;
                    break 2;
                }
            }
        }
    }

    $day = $choice;
}

$daySchedules = array_values(array_filter(
    $schedules,
    static fn (array $row): bool => $row['day'] === $day
));
usort($daySchedules, static fn (array $a, array $b): int => strcmp((string) $a['start_time'], (string) $b['start_time']));

$load = $selectedTeacher !== null
    ? $attendanceRepository->dailyLoad((int) $selectedTeacher['id'])
    : [];
$dayLoad = $load[$day] ?? [
    'classes' => 0,
    'subjects' => 0,
    'units' => 0.0,
    'minutes' => 0,
    'free_slots' => 10,
    'status' => 'Free',
];

$timelineStart = 7 * 60;
$timelineEnd = 17 * 60;
$timelineSpan = $timelineEnd - $timelineStart;

$position = static function (string $time) use ($timelineStart, $timelineSpan): float {
    $parts = explode(':', $time);
    $minutes = ((int) ($parts[0] ?? 0)) * 60 + ((int) ($parts[1] ?? 0));

    return max(0, min(100, (($minutes - $timelineStart) / $timelineSpan) * 100));
};

$blocks = [];
foreach ($daySchedules as $schedule) {
    $start = $position((string) $schedule['start_time']);
    $end = $position((string) $schedule['end_time']);
    $blocks[] = ['start' => $start, 'end' => max($start + 1.2, $end)];
}

$freeGaps = [];
$cursor = 0.0;
foreach ($blocks as $block) {
    if ($block['start'] - $cursor > 0.2) {
        $freeGaps[] = [$cursor, $block['start']];
    }
    $cursor = max($cursor, $block['end']);
}
if (100 - $cursor > 0.2) {
    $freeGaps[] = [$cursor, 100];
}

$rooms = [];
$minutes = 0;
foreach ($daySchedules as $schedule) {
    if ($schedule['room'] !== '') {
        $rooms[$schedule['room']] = true;
    }
    $minutes += max(0, strtotime('1970-01-01 ' . $schedule['end_time']) - strtotime('1970-01-01 ' . $schedule['start_time']));
}

$weeklyClasses = count($schedules);
$weeklySubjects = count(array_unique(array_column($schedules, 'subject_id')));
$weeklyUnits = array_sum(array_column($schedules, 'units'));
$weeklyMinutes = 0;
foreach ($schedules as $schedule) {
    $weeklyMinutes += max(0, strtotime('1970-01-01 ' . $schedule['end_time']) - strtotime('1970-01-01 ' . $schedule['start_time']));
}

require __DIR__ . '/partials/header.php';
?>
<div class="attn-page-head">
    <div class="attn-page-head__copy">
        <p class="attn-breadcrumb">Attendance &amp; Scheduling <span>/</span> <strong>Teacher Schedule</strong></p>
        <h1>Teacher Schedule</h1>
        <p><?= attn_e($attendanceSubtitle) ?></p>
    </div>
    <div class="attn-page-head__actions">
        <a class="attn-btn attn-btn--ghost" href="./teacher-schedule.php?<?= attn_e(http_build_query(['department' => $department, 'teacher' => $selectedTeacher['id'] ?? '', 'day' => $day])) ?>">
            <?= attn_icon('calendar') ?>
            <span>This week</span>
        </a>
        <button class="attn-btn attn-btn--primary" type="button" data-attn-print>
            <?= attn_icon('print') ?>
            <span>Print schedule</span>
        </button>
    </div>
</div>

<form class="attn-filterbar" method="get" action="teacher-schedule.php" aria-label="Teacher schedule filters">
    <label class="attn-field">
        <span>Department</span>
        <select name="department" data-attn-auto-submit>
            <option value="">All departments</option>
            <?php foreach ($departments as $row): ?>
                <option value="<?= attn_e($row) ?>" <?= $department === $row ? 'selected' : '' ?>><?= attn_e($row) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="attn-field attn-field--grow">
        <span>Teacher</span>
        <select name="teacher" data-attn-auto-submit>
            <?php if ($filteredTeachers === []): ?>
                <option value="0">No teachers in this department</option>
            <?php endif; ?>
            <?php foreach ($filteredTeachers as $teacher): ?>
                <option value="<?= (int) $teacher['id'] ?>" <?= $selectedTeacher !== null && $selectedTeacher['id'] === $teacher['id'] ? 'selected' : '' ?>>
                    <?= attn_e($teacher['name'] . ' · ' . $teacher['employee_number']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="attn-field">
        <span>Day</span>
        <select name="day" data-attn-auto-submit>
            <?php foreach ($dayOptions as $option): ?>
                <option value="<?= attn_e($option) ?>" <?= $day === $option ? 'selected' : '' ?>><?= attn_e($option) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <span class="attn-filterbar__spacer" aria-hidden="true"></span>

    <div class="attn-legend" aria-label="Timeline legend">
        <span class="attn-legend__item"><span class="attn-legend__dot"></span> Busy</span>
        <span class="attn-legend__item"><span class="attn-legend__dot attn-legend__dot--available"></span> Available</span>
    </div>
</form>

<?php if ($selectedTeacher === null): ?>
    <section class="attn-card attn-card--accent">
        <p class="attn-empty attn-empty--card">No teachers are registered for this department.</p>
    </section>
<?php else: ?>
    <div class="attn-split">
        <section class="attn-card attn-card--accent attn-timeline" aria-labelledby="teacher-timeline-title">
            <div class="attn-card__head">
                <div>
                    <h2 id="teacher-timeline-title"><?= attn_e($selectedTeacher['name']) ?> · <?= attn_e($day) ?></h2>
                    <p class="attn-card__sub"><?= attn_e($selectedTeacher['department'] !== '' ? $selectedTeacher['department'] : 'Faculty') ?> &middot; <?= attn_e($selectedTeacher['employee_number']) ?></p>
                </div>
                <span class="attn-pill <?= count($daySchedules) === 0 ? 'attn-pill--neutral' : 'attn-pill--info' ?>">
                    <?= count($daySchedules) ?> class<?= count($daySchedules) === 1 ? '' : 'es' ?> today
                </span>
            </div>

            <div class="attn-timeline__daybar">
                <strong><?= attn_e($day) ?> timeline</strong>
                <span>7:00 AM &ndash; 5:00 PM</span>
            </div>

            <div class="attn-timeline__body">
                <div class="attn-timeline__labels" aria-hidden="true">
                    <?php for ($hour = 7; $hour <= 17; $hour++): ?>
                        <?php
                        $top = (($hour * 60) - $timelineStart) / $timelineSpan * 100;
                        $labelClass = $hour === 7 ? ' is-first' : ($hour === 17 ? ' is-last' : '');
                        ?>
                        <span class="attn-timeline__label<?= $labelClass ?>" style="top: <?= attn_e(number_format($top, 3, '.', '')) ?>%;">
                            <?= $hour === 12 ? '12 PM' : ($hour > 12 ? ($hour - 12) . ' PM' : $hour . ' AM') ?>
                        </span>
                    <?php endfor; ?>
                </div>

                <div class="attn-timeline__track">
                    <?php foreach ($freeGaps as [$gapStart, $gapEnd]): ?>
                        <?php
                        $gapHeight = $gapEnd - $gapStart;
                        $short = $gapHeight < 5.2;
                        ?>
                        <div
                            class="attn-timeline__free<?= $short ? ' is-short' : '' ?>"
                            style="top: <?= attn_e(number_format($gapStart, 3, '.', '')) ?>%; height: <?= attn_e(number_format($gapHeight, 3, '.', '')) ?>%;"
                            aria-hidden="true"
                        ><?= $short ? '' : 'Available' ?></div>
                    <?php endforeach; ?>

                    <?php foreach ($daySchedules as $schedule): ?>
                        <?php
                        $top = $position((string) $schedule['start_time']);
                        $bottom = $position((string) $schedule['end_time']);
                        $height = max(4.5, $bottom - $top);
                        $compact = $height < 11;
                        ?>
                        <article class="attn-timeline__class<?= $compact ? ' is-compact' : '' ?>" style="top: <?= attn_e(number_format($top, 3, '.', '')) ?>%; height: <?= attn_e(number_format($height, 3, '.', '')) ?>%;">
                            <time><?= attn_e(attn_time($schedule['start_time'])) ?> &ndash; <?= attn_e(attn_time($schedule['end_time'])) ?></time>
                            <strong><?= attn_e($schedule['subject_code']) ?> &middot; <?= attn_e($schedule['section']) ?></strong>
                            <span><?= attn_e($schedule['room'] !== '' ? $schedule['room'] : 'Room TBA') ?> &middot; <?= attn_e($schedule['subject_name']) ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>

            <p class="attn-note">
                <?= attn_icon('clock') ?>
                <span>Classes are positioned on the official timetable. Open blocks inside 7:00 AM &ndash; 5:00 PM are marked as available for consultation or make-up sessions.</span>
            </p>
        </section>

        <aside class="attn-card attn-card--accent" aria-labelledby="day-glance-title">
            <div class="attn-card__head">
                <div>
                    <h2 id="day-glance-title">Day at a glance</h2>
                    <p class="attn-card__sub"><?= attn_e($day) ?>, <?= attn_e($attendanceNow->format('M j, Y')) ?></p>
                </div>
            </div>

            <div class="attn-glance">
                <div class="attn-glance__hero">
                    <small>Selected teacher</small>
                    <strong><?= attn_e($selectedTeacher['name']) ?></strong>
                    <span><?= attn_e($selectedTeacher['employee_number']) ?> &middot; <?= attn_e($selectedTeacher['department'] !== '' ? $selectedTeacher['department'] : 'Faculty') ?></span>
                </div>

                <div class="attn-glance__grid">
                    <div class="attn-glance__cell">
                        <span>Classes</span>
                        <strong><?= (int) $dayLoad['classes'] ?></strong>
                    </div>
                    <div class="attn-glance__cell">
                        <span>Subjects</span>
                        <strong><?= (int) $dayLoad['subjects'] ?></strong>
                    </div>
                    <div class="attn-glance__cell">
                        <span>Units</span>
                        <strong><?= attn_e(number_format((float) $dayLoad['units'], (fmod((float) $dayLoad['units'], 1.0) === 0.0 ? 0 : 1))) ?></strong>
                    </div>
                    <div class="attn-glance__cell">
                        <span>Free slots</span>
                        <strong><?= (int) $dayLoad['free_slots'] ?></strong>
                    </div>
                </div>

                <ul class="attn-glance__list">
                    <li><span>First class</span><strong><?= $daySchedules !== [] ? attn_e(attn_time($daySchedules[0]['start_time'])) : '—' ?></strong></li>
                    <li><span>Last class ends</span><strong><?= $daySchedules !== [] ? attn_e(attn_time($daySchedules[count($daySchedules) - 1]['end_time'])) : '—' ?></strong></li>
                    <li><span>Contact hours</span><strong><?= attn_e(number_format($minutes / 3600, 1)) ?> h</strong></li>
                    <li><span>Rooms used</span><strong><?= count($rooms) ?></strong></li>
                </ul>

                <p class="attn-note">
                    <?= attn_icon('shield-check') ?>
                    <span>Load status: <strong><?= attn_e($dayLoad['status']) ?></strong>. The daily cap is 6 classes and 6 subjects per teacher.</span>
                </p>
            </div>
        </aside>
    </div>

    <section class="attn-card attn-card--accent" aria-labelledby="daily-load-title">
        <div class="attn-card__head">
            <div>
                <h2 id="daily-load-title">Daily Load</h2>
                <p class="attn-card__sub">Weekly distribution for <?= attn_e($selectedTeacher['name']) ?></p>
            </div>
            <span class="attn-pill attn-pill--info"><?= $weeklyClasses ?> weekly classes</span>
        </div>

        <?php if ($load === []): ?>
            <p class="attn-empty attn-empty--card">No classes are assigned to this teacher yet.</p>
        <?php else: ?>
            <div class="attn-table-wrap">
                <table class="attn-table attn-table--cards">
                    <thead>
                        <tr>
                            <th scope="col">Day</th>
                            <th scope="col">Classes</th>
                            <th scope="col">Subjects</th>
                            <th scope="col">Units</th>
                            <th scope="col">Free Slots</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($load as $row): ?>
                            <tr<?= $row['day'] === $day ? ' class="is-selected"' : '' ?>>
                                <td class="attn-table__day" data-label="Day"><?= attn_e($row['day']) ?></td>
                                <td data-label="Classes"><?= (int) $row['classes'] ?></td>
                                <td data-label="Subjects"><?= (int) $row['subjects'] ?></td>
                                <td data-label="Units"><?= attn_e(number_format((float) $row['units'], (fmod((float) $row['units'], 1.0) === 0.0 ? 0 : 1))) ?></td>
                                <td data-label="Free slots"><?= (int) $row['free_slots'] ?></td>
                                <td data-label="Status"><span class="attn-pill is-<?= attn_e(strtolower(str_replace(' ', '-', $row['status']))) ?>"><?= attn_e($row['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="attn-table__total">
                            <td data-label="Day">Weekly total</td>
                            <td data-label="Classes"><?= $weeklyClasses ?></td>
                            <td data-label="Subjects"><?= $weeklySubjects ?></td>
                            <td data-label="Units"><?= attn_e(number_format($weeklyUnits, (fmod($weeklyUnits, 1.0) === 0.0 ? 0 : 1))) ?></td>
                            <td data-label="Free slots"><?= array_sum(array_column($load, 'free_slots')) ?></td>
                            <td data-label="Contact"><?= attn_e(number_format($weeklyMinutes / 3600, 1)) ?> h contact</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="attn-note">
            <?= attn_icon('calendar') ?>
            <span>Free slots count open blocks of 30 minutes or longer between 7:00 AM and 5:00 PM. Load status compares each teaching day with the scheduling policy.</span>
        </p>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
