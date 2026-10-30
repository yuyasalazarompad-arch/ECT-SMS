<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$attendancePage = 'overview';
$attendanceTitle = 'Attendance Overview';
$attendanceSubtitle = 'Take attendance and review daily operations';

$subjects = $attendanceRepository->subjects();
$sections = $attendanceRepository->sections();

// Resolve the selected subject, section, year, and date.
$subjectId = (int) ($_GET['subject'] ?? 0);
$selectedSubject = null;
foreach ($subjects as $subject) {
    if ($subject['id'] === $subjectId) {
        $selectedSubject = $subject;
        break;
    }
}
if ($selectedSubject === null) {
    foreach ($subjects as $subject) {
        if ($subject['code'] === 'CS101') {
            $selectedSubject = $subject;
            break;
        }
    }
}
$selectedSubject ??= $subjects[0] ?? null;

$section = trim((string) ($_GET['section'] ?? ''));
$selectedSection = null;
foreach ($sections as $row) {
    if ($row['section'] === $section) {
        $selectedSection = $row;
        break;
    }
}
if ($selectedSection === null && $sections !== []) {
    foreach ($sections as $row) {
        if ($row['section'] === 'BSCS-1A') {
            $selectedSection = $row;
            break;
        }
    }
    $selectedSection ??= $sections[0];
}

$year = (int) ($_GET['year'] ?? 0);
if ($selectedSection !== null) {
    $year = $selectedSection['year'];
}
if ($year < 1 || $year > 4) {
    $year = $selectedSubject['year'] ?? 1;
}
if ($selectedSection === null || $selectedSection['year'] !== $year) {
    $selectedSection = null;
    foreach ($sections as $row) {
        if ($row['year'] === $year) {
            $selectedSection = $row;
            break;
        }
    }
    $selectedSection ??= $sections[0] ?? null;
    if ($selectedSection !== null) {
        $year = $selectedSection['year'];
    }
}

$date = (string) ($_GET['date'] ?? '');
if (!$attendanceRepository->isDate($date)) {
    $date = $attendanceToday;
}

$sectionName = $selectedSection['section'] ?? '';
$schedule = $selectedSubject !== null && $sectionName !== ''
    ? $attendanceRepository->scheduleFor((int) $selectedSubject['id'], $sectionName)
    : null;
$roster = $sectionName !== '' ? $attendanceRepository->students($sectionName) : [];
$marks = $schedule !== null ? $attendanceRepository->attendanceFor((int) $schedule['schedule_id'], $date) : [];
$dayName = $attendanceRepository->dayOf($date);

$flash = attn_flash();

// Save posted attendance. Only statuses for students in the visible roster are
// accepted, and the schedule must match the selected subject/section.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $postedSchedule = (int) ($_POST['schedule_id'] ?? 0);
    $postedDate = (string) ($_POST['date'] ?? '');
    $postedStatuses = $_POST['status'] ?? [];

    if (!auth_verify_csrf(is_string($token) ? $token : null)) {
        attn_flash_set('error', 'Your session expired. Refresh the page and try again.');
    } elseif (
        $schedule === null
        || $postedSchedule !== (int) $schedule['schedule_id']
        || !$attendanceRepository->isDate($postedDate)
        || !is_array($postedStatuses)
    ) {
        attn_flash_set('error', 'Attendance could not be saved because the class selection is not valid.');
    } else {
        $allowed = array_map(static fn (array $student): int => (int) $student['id'], $roster);
        $clean = [];

        foreach ($postedStatuses as $studentId => $status) {
            $studentId = (int) $studentId;
            if (in_array($studentId, $allowed, true)) {
                $clean[$studentId] = (string) $status;
            }
        }

        try {
            $saved = $attendanceRepository->save((int) $schedule['schedule_id'], $postedDate, $clean);
            attn_flash_set(
                'success',
                $saved . ' attendance ' . ($saved === 1 ? 'record' : 'records') . ' saved for ' . attn_date($postedDate) . '.'
            );
        } catch (Throwable $exception) {
            attn_flash_set('error', 'Attendance could not be saved. Please try again.');
        }
    }

    header('Location: dashboard.php?' . attn_filter_query());
    exit;
}

$counts = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'Excused' => 0];
foreach ($roster as $student) {
    $status = $marks[(int) $student['id']] ?? '';
    if (isset($counts[$status])) {
        $counts[$status]++;
    }
}
$totalStudents = count($roster);

if ($schedule === null) {
    $scheduleNotice = 'No class is scheduled for this subject and section yet.';
} elseif (strcasecmp((string) $schedule['day'], $dayName) !== 0) {
    $scheduleNotice = 'No class scheduled for ' . $dayName . '.';
} else {
    $scheduleNotice = $schedule['subject_code'] . ' is scheduled for ' . $schedule['day'] . ', '
        . attn_time((string) $schedule['start_time']) . ' – ' . attn_time((string) $schedule['end_time'])
        . ' in ' . ($schedule['room'] !== '' ? $schedule['room'] : 'an unassigned room') . '.';
}

$query = attn_filter_query();
$saveDisabled = $schedule === null || $roster === [];
$dayMismatch = $schedule !== null && strcasecmp((string) $schedule['day'], $dayName) !== 0;

require __DIR__ . '/partials/header.php';
?>
<div class="attn-page-head">
    <div class="attn-page-head__copy">
        <p class="attn-breadcrumb">Attendance &amp; Scheduling <span>/</span> <strong>Attendance Overview</strong></p>
        <h1>Attendance Overview</h1>
        <p><?= attn_e($attendanceSubtitle) ?></p>
    </div>
    <div class="attn-page-head__actions">
        <span class="attn-pill attn-pill--info"><?= attn_e($attendanceNow->format('D, M j, Y')) ?></span>
        <a class="attn-btn attn-btn--ghost" href="export.php?<?= attn_e($query) ?>">
            <?= attn_icon('download') ?>
            <span>Export CSV</span>
        </a>
    </div>
</div>

<section class="attn-card attn-card--accent">
    <div class="attn-card__head">
        <div>
            <h2>Take Attendance</h2>
            <p class="attn-card__sub"><?= attn_e($scheduleNotice) ?></p>
        </div>
        <span class="attn-pill <?= $saveDisabled || $dayMismatch ? 'attn-pill--neutral' : 'attn-pill--success' ?>">
            <?= $saveDisabled ? 'No class selected' : ($dayMismatch ? 'No class today' : 'Ready to record') ?>
        </span>
    </div>

    <?php if ($flash !== null): ?>
        <div class="attn-notice<?= $flash['type'] === 'error' ? ' is-error' : '' ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= attn_icon($flash['type'] === 'error' ? 'alert' : 'check-circle') ?>
            <span><?= attn_e($flash['message']) ?></span>
        </div>
    <?php endif; ?>

    <form class="attn-filterbar" method="get" action="dashboard.php" data-attn-filters>
        <label class="attn-field attn-field--grow">
            <span>Subject</span>
            <select name="subject" data-attn-subject>
                <?php foreach ($subjects as $subject): ?>
                    <option
                        value="<?= (int) $subject['id'] ?>"
                        data-year="<?= (int) $subject['year'] ?>"
                        <?= $selectedSubject !== null && $selectedSubject['id'] === $subject['id'] ? 'selected' : '' ?>
                    ><?= attn_e($subject['code'] . ' – ' . $subject['name'] . ' (Year ' . $subject['year'] . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="attn-field">
            <span>Section</span>
            <select name="section" data-attn-section>
                <?php foreach ($sections as $row): ?>
                    <option
                        value="<?= attn_e($row['section']) ?>"
                        data-year="<?= (int) $row['year'] ?>"
                        <?= $selectedSection !== null && $selectedSection['section'] === $row['section'] ? 'selected' : '' ?>
                    ><?= attn_e($row['section']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="attn-field">
            <span>Year level</span>
            <select name="year" data-attn-year>
                <?php for ($level = 1; $level <= 4; $level++): ?>
                    <option value="<?= $level ?>" <?= $year === $level ? 'selected' : '' ?>>Year <?= $level ?></option>
                <?php endfor; ?>
            </select>
        </label>

        <label class="attn-field">
            <span>Date</span>
            <input type="date" name="date" value="<?= attn_e($date) ?>" data-attn-date>
        </label>

        <span class="attn-filterbar__spacer" aria-hidden="true"></span>

        <div class="attn-filterbar__actions">
            <button class="attn-btn attn-btn--primary" type="submit" form="attendance-form"<?= $saveDisabled ? ' disabled' : '' ?>>
                <?= attn_icon('save') ?>
                <span>Save Attendance</span>
            </button>
        </div>
    </form>

    <div class="attn-stats" aria-label="Attendance summary">
        <article class="attn-stat attn-stat--present">
            <span class="attn-stat__icon"><?= attn_icon('check-circle') ?></span>
            <div class="attn-stat__body">
                <strong id="attn-present"><?= $counts['Present'] ?></strong>
                <span>Present</span>
            </div>
        </article>
        <article class="attn-stat attn-stat--absent">
            <span class="attn-stat__icon"><?= attn_icon('alert') ?></span>
            <div class="attn-stat__body">
                <strong id="attn-absent"><?= $counts['Absent'] ?></strong>
                <span>Absent</span>
            </div>
        </article>
        <article class="attn-stat attn-stat--late">
            <span class="attn-stat__icon"><?= attn_icon('clock') ?></span>
            <div class="attn-stat__body">
                <strong id="attn-late"><?= $counts['Late'] ?></strong>
                <span>Late</span>
            </div>
        </article>
        <article class="attn-stat">
            <span class="attn-stat__icon"><?= attn_icon('users') ?></span>
            <div class="attn-stat__body">
                <strong id="attn-total"><?= $totalStudents ?></strong>
                <span>Total students</span>
            </div>
        </article>
    </div>

    <form id="attendance-form" method="post" action="dashboard.php?<?= attn_e($query) ?>">
        <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
        <input type="hidden" name="schedule_id" value="<?= (int) ($schedule['schedule_id'] ?? 0) ?>">
        <input type="hidden" name="date" value="<?= attn_e($date) ?>">

        <div class="attn-table-wrap">
            <table class="attn-table">
                <thead>
                    <tr>
                        <th scope="col">Student</th>
                        <th scope="col">Student Number</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="attn-table__mark">Mark</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($roster === []): ?>
                        <tr>
                            <td colspan="4" class="attn-empty">No students are assigned to this section.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($roster as $student): ?>
                            <?php
                            $status = $marks[(int) $student['id']] ?? 'Unmarked';
                            $statusKey = attn_status_key($status);
                            ?>
                            <tr data-attn-row data-student-id="<?= (int) $student['id'] ?>">
                                <td>
                                    <div class="attn-student">
                                        <span class="attn-student__avatar" aria-hidden="true"><?= attn_e(attn_initials($student['name'])) ?></span>
                                        <div>
                                            <strong><?= attn_e($student['name']) ?></strong>
                                            <small><?= attn_e($student['course']) ?> &middot; <?= attn_e($student['section']) ?> &middot; Year <?= (int) $student['year'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="attn-number"><?= attn_e($student['no']) ?></td>
                                <td>
                                    <span class="attn-pill is-<?= attn_e($statusKey) ?>" data-attn-chip><?= attn_e($status) ?></span>
                                </td>
                                <td>
                                    <div class="attn-marks" role="group" aria-label="Attendance for <?= attn_e($student['name']) ?>">
                                        <?php foreach (['P' => 'Present', 'L' => 'Late', 'A' => 'Absent', 'E' => 'Excused'] as $letter => $label): ?>
                                            <?php
                                            $checked = strcasecmp($status, $label) === 0 ? ' checked' : '';
                                            $disabled = $saveDisabled ? ' disabled' : '';
                                            ?>
                                            <label class="attn-mark" title="<?= attn_e($label) ?>">
                                                <input type="radio" name="status[<?= (int) $student['id'] ?>]" value="<?= attn_e($label) ?>"<?= $checked ?><?= $disabled ?>>
                                                <span><?= attn_e($letter) ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>

    <p class="attn-note">
        <?= attn_icon('calendar') ?>
        <span>Marks are saved against the selected class and date. Export CSV uses the same subject, section, year, and date filters.</span>
    </p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
