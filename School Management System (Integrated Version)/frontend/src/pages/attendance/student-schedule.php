<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$attendancePage = 'student';
$attendanceTitle = 'Student Schedule';
$attendanceSubtitle = 'Section timetable, meeting days, and weekly classroom allocation.';

$sections = $attendanceRepository->sections();

$programs = [];
foreach ($sections as $row) {
    if ($row['course'] !== '') {
        $programs[$row['course']] = true;
    }
}
$programs = array_keys($programs);
sort($programs);

$programLabel = static fn (string $value): string => str_ireplace('Bachelor of Science in ', 'BS ', $value);

$program = trim((string) ($_GET['program'] ?? ''));
if ($program === '' || !in_array($program, $programs, true)) {
    $program = '';

    foreach ($programs as $row) {
        if (stripos($row, 'Computer Science') !== false) {
            $program = $row;
            break;
        }
    }

    $program = $program !== '' ? $program : ($programs[0] ?? '');
}

$years = [];
foreach ($sections as $row) {
    if ($row['course'] === $program) {
        $years[(int) $row['year']] = true;
    }
}
$years = array_keys($years);
sort($years);

$year = (int) ($_GET['year'] ?? 0);
if (!in_array($year, $years, true)) {
    $year = $years[0] ?? 1;
}

$sectionOptions = array_values(array_filter(
    $sections,
    static fn (array $row): bool => $row['course'] === $program && $row['year'] === $year
));

$section = trim((string) ($_GET['section'] ?? ''));
$selectedSection = null;
foreach ($sectionOptions as $row) {
    if ($row['section'] === $section) {
        $selectedSection = $row;
        break;
    }
}
if ($selectedSection === null && $sectionOptions !== []) {
    foreach ($sectionOptions as $row) {
        if ($row['section'] === 'BSCS-1A') {
            $selectedSection = $row;
            break;
        }
    }
    $selectedSection ??= $sectionOptions[0];
}

$dayFilter = (string) ($_GET['days'] ?? 'all');
$dayFilters = [
    'all' => ['label' => 'All meeting days', 'test' => static fn (string $day): bool => true],
    'weekday' => ['label' => 'Weekdays', 'test' => static fn (string $day): bool => !in_array($day, ['Saturday', 'Sunday'], true)],
    'saturday' => ['label' => 'Saturday', 'test' => static fn (string $day): bool => $day === 'Saturday'],
];
if (!isset($dayFilters[$dayFilter])) {
    $dayFilter = 'all';
}
$dayTest = $dayFilters[$dayFilter]['test'];

$sectionName = $selectedSection['section'] ?? '';
$schedules = $sectionName !== '' ? $attendanceRepository->sectionSchedules($sectionName) : [];
$visibleSchedules = array_values(array_filter(
    $schedules,
    static fn (array $row): bool => $dayTest((string) $row['day'])
));

$meetingDays = [];
foreach (AttendanceRepository::DAYS as $dayName) {
    foreach ($visibleSchedules as $row) {
        if ($row['day'] === $dayName) {
            $meetingDays[] = $dayName;
            break;
        }
    }
}

$startTimes = array_values(array_unique(array_column($visibleSchedules, 'start_time')));
sort($startTimes);

$byDayTime = [];
foreach ($visibleSchedules as $row) {
    $byDayTime[$row['day'] . '|' . $row['start_time']] = $row;
}

$allocation = [];
foreach ($meetingDays as $dayName) {
    $dayRows = array_values(array_filter($visibleSchedules, static fn (array $row): bool => $row['day'] === $dayName));
    $minutes = 0;
    $rooms = [];
    foreach ($dayRows as $row) {
        $minutes += max(0, strtotime('1970-01-01 ' . $row['end_time']) - strtotime('1970-01-01 ' . $row['start_time']));
        if ($row['room'] !== '') {
            $rooms[$row['room']] = true;
        }
    }
    $allocation[] = [
        'day' => $dayName,
        'classes' => count($dayRows),
        'minutes' => $minutes,
        'rooms' => count($rooms),
        'status' => count($dayRows) > 4 ? 'Heavy' : (count($dayRows) > 0 ? 'Scheduled' : 'Free'),
    ];
}

$subjectCount = count(array_unique(array_column($visibleSchedules, 'subject_id')));
$roomCount = count(array_unique(array_filter(array_column($visibleSchedules, 'room'))));
$minutes = 0;
foreach ($visibleSchedules as $row) {
    $minutes += max(0, strtotime('1970-01-01 ' . $row['end_time']) - strtotime('1970-01-01 ' . $row['start_time']));
}

$queryBase = [
    'program' => $program,
    'year' => $year,
    'section' => $sectionName,
    'days' => $dayFilter,
];

require __DIR__ . '/partials/header.php';
?>
<div class="attn-page-head">
    <div class="attn-page-head__copy">
        <p class="attn-breadcrumb">Attendance &amp; Scheduling <span>/</span> <strong>Student Schedule</strong></p>
        <h1>Student Schedule</h1>
        <p><?= attn_e($attendanceSubtitle) ?></p>
    </div>
    <div class="attn-page-head__actions">
        <a class="attn-btn attn-btn--ghost" href="./student-schedule.php?<?= attn_e(http_build_query($queryBase)) ?>">
            <?= attn_icon('calendar') ?>
            <span>Week view</span>
        </a>
        <button class="attn-btn attn-btn--primary" type="button" data-attn-print>
            <?= attn_icon('print') ?>
            <span>Print schedule</span>
        </button>
    </div>
</div>

<form class="attn-filterbar" method="get" action="student-schedule.php" aria-label="Student schedule filters">
    <label class="attn-field">
        <span>Program</span>
        <select name="program" data-attn-auto-submit>
            <?php foreach ($programs as $row): ?>
                <option value="<?= attn_e($row) ?>" <?= $program === $row ? 'selected' : '' ?>><?= attn_e($programLabel($row)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="attn-field">
        <span>Year level</span>
        <select name="year" data-attn-auto-submit>
            <?php foreach ($years as $row): ?>
                <option value="<?= (int) $row ?>" <?= $year === $row ? 'selected' : '' ?>>Year <?= (int) $row ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="attn-field attn-field--grow">
        <span>Section</span>
        <select name="section" data-attn-auto-submit>
            <?php if ($sectionOptions === []): ?>
                <option value="">No sections available</option>
            <?php endif; ?>
            <?php foreach ($sectionOptions as $row): ?>
                <option value="<?= attn_e($row['section']) ?>" <?= $selectedSection !== null && $selectedSection['section'] === $row['section'] ? 'selected' : '' ?>>
                    <?= attn_e($row['section'] . ' · ' . $row['students'] . ' students') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label class="attn-field">
        <span>Days</span>
        <select name="days" data-attn-auto-submit>
            <?php foreach ($dayFilters as $key => $option): ?>
                <option value="<?= attn_e($key) ?>" <?= $dayFilter === $key ? 'selected' : '' ?>><?= attn_e($option['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <span class="attn-filterbar__spacer" aria-hidden="true"></span>

    <div class="attn-legend" aria-label="Week legend">
        <span class="attn-legend__item"><span class="attn-legend__dot"></span> Scheduled class</span>
        <span class="attn-legend__item"><span class="attn-legend__dot attn-legend__dot--free"></span> No class</span>
    </div>
</form>

<section class="attn-stats" aria-label="Section summary">
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('users') ?></span>
        <div class="attn-stat__body">
            <strong><?= (int) ($selectedSection['students'] ?? 0) ?></strong>
            <span>Enrolled <?= (int) ($selectedSection['students'] ?? 0) === 1 ? 'student' : 'students' ?></span>
        </div>
    </article>
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('calendar') ?></span>
        <div class="attn-stat__body">
            <strong><?= count($visibleSchedules) ?></strong>
            <span>Class meetings</span>
        </div>
    </article>
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('layers') ?></span>
        <div class="attn-stat__body">
            <strong><?= $subjectCount ?></strong>
            <span>Subjects</span>
        </div>
    </article>
    <article class="attn-stat attn-stat--late">
        <span class="attn-stat__icon"><?= attn_icon('clock') ?></span>
        <div class="attn-stat__body">
            <strong><?= attn_e(number_format($minutes / 3600, 1)) ?></strong>
            <span>Weekly hours</span>
        </div>
    </article>
</section>

<section class="attn-card attn-card--accent" aria-labelledby="week-grid-title">
    <div class="attn-card__head">
        <div>
            <h2 id="week-grid-title"><?= attn_e($sectionName !== '' ? $sectionName : 'No section selected') ?></h2>
            <p class="attn-card__sub">
                <?php if ($selectedSection !== null): ?>
                    <?= attn_e($selectedSection['course']) ?> &middot; Year <?= (int) $selectedSection['year'] ?> &middot;
                <?php endif; ?>
                <?= count($meetingDays) ?> meeting day<?= count($meetingDays) === 1 ? '' : 's' ?> &middot; <?= $roomCount ?> room<?= $roomCount === 1 ? '' : 's' ?>
            </p>
        </div>
        <span class="attn-pill attn-pill--info"><?= attn_e($dayFilters[$dayFilter]['label']) ?></span>
    </div>

    <?php if ($visibleSchedules === [] || $meetingDays === []): ?>
        <p class="attn-empty attn-empty--card">No classes are scheduled for this section and day filter.</p>
    <?php else: ?>
        <div class="attn-week">
            <div class="attn-week__grid" style="--attn-week-days: <?= count($meetingDays) ?>;">
                <div class="attn-week__corner">Time</div>
                <?php foreach ($meetingDays as $dayName): ?>
                    <div class="attn-week__dayhead">
                        <strong><?= attn_e($dayName) ?></strong>
                        <small><?= count(array_filter($visibleSchedules, static fn (array $row): bool => $row['day'] === $dayName)) ?> class<?= count(array_filter($visibleSchedules, static fn (array $row): bool => $row['day'] === $dayName)) === 1 ? '' : 'es' ?></small>
                    </div>
                <?php endforeach; ?>

                <?php foreach ($startTimes as $startTime): ?>
                    <div class="attn-week__time"><?= attn_e(attn_time($startTime)) ?></div>
                    <?php foreach ($meetingDays as $dayName): ?>
                        <?php $cell = $byDayTime[$dayName . '|' . $startTime] ?? null; ?>
                        <div class="attn-week__cell">
                            <?php if ($cell !== null): ?>
                                <article class="attn-week__class">
                                    <small><?= attn_e(attn_time($cell['start_time'])) ?> &ndash; <?= attn_e(attn_time($cell['end_time'])) ?></small>
                                    <strong><?= attn_e($cell['subject_code']) ?></strong>
                                    <span><?= attn_e($cell['subject_name']) ?></span>
                                    <span><?= attn_e($cell['teacher_name']) ?> &middot; <?= attn_e($cell['room'] !== '' ? $cell['room'] : 'Room TBA') ?></span>
                                </article>
                            <?php else: ?>
                                <div class="attn-week__empty">Open</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <p class="attn-note">
        <?= attn_icon('calendar') ?>
        <span>Only meeting days with scheduled classes are shown. Time rows follow the section's actual class start times.</span>
    </p>
</section>

<section class="attn-card attn-card--accent" aria-labelledby="weekly-allocation-title">
    <div class="attn-card__head">
        <div>
            <h2 id="weekly-allocation-title">Weekly Allocation</h2>
            <p class="attn-card__sub">Time and room allocation by meeting day</p>
        </div>
        <span class="attn-pill attn-pill--info"><?= count($visibleSchedules) ?> meetings</span>
    </div>

    <?php if ($allocation === []): ?>
        <p class="attn-empty attn-empty--card">No allocation to show for this filter.</p>
    <?php else: ?>
        <div class="attn-table-wrap">
            <table class="attn-table attn-table--cards">
                <thead>
                    <tr>
                        <th scope="col">Day</th>
                        <th scope="col">Classes</th>
                        <th scope="col">Time Allocated</th>
                        <th scope="col">Rooms Allocated</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allocation as $row): ?>
                        <tr>
                            <td class="attn-table__day" data-label="Day"><?= attn_e($row['day']) ?></td>
                            <td data-label="Classes"><?= (int) $row['classes'] ?></td>
                            <td data-label="Time"><?= attn_e(number_format($row['minutes'] / 3600, 1)) ?> h</td>
                            <td data-label="Rooms"><?= (int) $row['rooms'] ?></td>
                            <td data-label="Status"><span class="attn-pill is-<?= attn_e(strtolower($row['status'])) ?>"><?= attn_e($row['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="attn-table__total">
                        <td data-label="Day">Weekly total</td>
                        <td data-label="Classes"><?= count($visibleSchedules) ?></td>
                        <td data-label="Time"><?= attn_e(number_format($minutes / 3600, 1)) ?> h</td>
                        <td data-label="Rooms"><?= $roomCount ?></td>
                        <td data-label="Contact"><?= attn_e(number_format($minutes / 3600, 1)) ?> h contact</td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p class="attn-note">
        <?= attn_icon('layers') ?>
        <span>Allocation reflects the selected section only. Switch the program, year, or section filters to review another timetable.</span>
    </p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
