<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$attendancePage = 'conflicts';
$attendanceTitle = 'Conflict Detection';
$attendanceSubtitle = 'Automatic timetable checks and manually reported scheduling issues.';

$tab = (string) ($_GET['tab'] ?? 'automatic');
$tab = $tab === 'manual' ? 'manual' : 'automatic';

$types = ['Room', 'Teacher', 'Section', 'Other'];
$type = trim((string) ($_GET['type'] ?? ''));
if ($type !== '' && !in_array($type, $types, true)) {
    $type = '';
}
$showAcknowledged = isset($_GET['ack']) && (string) $_GET['ack'] === '1';

$automaticAll = $attendanceRepository->automaticConflicts();
$manualAll = $attendanceRepository->manualConflicts();
$acknowledged = $attendanceRepository->acknowledgedKeys();

$flash = attn_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $action = (string) ($_POST['action'] ?? '');

    if (!auth_verify_csrf(is_string($token) ? $token : null)) {
        attn_flash_set('error', 'Your session expired. Refresh the page and try again.');
    } else {
        switch ($action) {
            case 'rescan':
                $found = count($attendanceRepository->automaticConflicts());
                attn_flash_set(
                    'success',
                    $found === 0
                        ? 'Schedule re-scanned. No automatic conflicts were detected.'
                        : 'Schedule re-scanned. ' . $found . ' conflict' . ($found === 1 ? '' : 's') . ' found.'
                );
                break;

            case 'acknowledge':
                $key = trim((string) ($_POST['key'] ?? ''));
                $note = trim((string) ($_POST['note'] ?? ''));
                if ($key !== '' && $attendanceRepository->acknowledgeConflict($key, $note)) {
                    attn_flash_set('success', 'Conflict acknowledged and moved out of the open queue.');
                } else {
                    attn_flash_set('error', 'The conflict could not be acknowledged.');
                }
                break;

            case 'resolve':
                $id = (int) ($_POST['id'] ?? 0);
                $note = trim((string) ($_POST['note'] ?? ''));
                if ($attendanceRepository->resolveManualConflict($id, $note)) {
                    attn_flash_set('success', 'Manual conflict marked as resolved.');
                } else {
                    attn_flash_set('error', 'The conflict could not be resolved.');
                }
                break;

            case 'create':
                $created = $attendanceRepository->createManualConflict(
                    (string) ($_POST['conflict_type'] ?? 'Other'),
                    (string) ($_POST['day'] ?? ''),
                    trim((string) ($_POST['reference'] ?? '')),
                    trim((string) ($_POST['title'] ?? '')),
                    trim((string) ($_POST['description'] ?? '')),
                    (string) ($_POST['severity'] ?? 'warn'),
                    (string) $attendanceUser['name']
                );

                if ($created > 0) {
                    attn_flash_set('success', 'Conflict reported. It is now in the manual review queue.');
                } else {
                    attn_flash_set('error', 'The conflict could not be reported. Add a title and try again.');
                }
                break;
        }
    }

    header('Location: conflicts.php?tab=' . ($action === 'rescan' ? 'automatic' : 'manual'));
    exit;
}

$automatic = $automaticAll;
$manual = $manualAll;

if ($type !== '') {
    $automatic = array_values(array_filter($automatic, static fn (array $row): bool => $row['type'] === $type));
    $manual = array_values(array_filter($manual, static fn (array $row): bool => $row['type'] === $type));
}

if (!$showAcknowledged) {
    $automatic = array_values(array_filter($automatic, static fn (array $row): bool => empty($row['acknowledged'])));
    $manual = array_values(array_filter($manual, static fn (array $row): bool => empty($row['acknowledged'])));
}

$automaticOpen = count(array_filter($automaticAll, static fn (array $row): bool => empty($row['acknowledged'])));
$manualOpen = count(array_filter($manualAll, static fn (array $row): bool => empty($row['acknowledged'])));
$acknowledgedCount = count($acknowledged);

$policy = $attendanceRepository->policyReport();
$passedRules = count(array_filter($policy, static fn (array $row): bool => (bool) $row['pass']));
$allRulesMet = $passedRules === count($policy);

$filterQuery = array_filter([
    'tab' => $tab,
    'type' => $type,
    'ack' => $showAcknowledged ? '1' : '',
], static fn (mixed $value): bool => $value !== '' && $value !== null);

require __DIR__ . '/partials/header.php';
?>
<div class="attn-page-head">
    <div class="attn-page-head__copy">
        <p class="attn-breadcrumb">Attendance &amp; Scheduling <span>/</span> <strong>Conflict Detection</strong></p>
        <h1>Conflict Detection</h1>
        <p><?= attn_e($attendanceSubtitle) ?></p>
    </div>
    <div class="attn-page-head__actions">
        <form method="post" action="conflicts.php">
            <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
            <input type="hidden" name="action" value="rescan">
            <button class="attn-btn attn-btn--ghost" type="submit">
                <?= attn_icon('refresh') ?>
                <span>Re-scan Schedule</span>
            </button>
        </form>
        <button class="attn-btn attn-btn--danger" type="button" data-attn-open-dialog="report-conflict">
            <?= attn_icon('plus') ?>
            <span>Report Conflict</span>
        </button>
    </div>
</div>

<?php if ($flash !== null): ?>
    <div class="attn-notice<?= $flash['type'] === 'error' ? ' is-error' : '' ?>" role="<?= $flash['type'] === 'error' ? 'alert' : 'status' ?>">
        <?= attn_icon($flash['type'] === 'error' ? 'alert' : 'check-circle') ?>
        <span><?= attn_e($flash['message']) ?></span>
    </div>
<?php endif; ?>

<section class="attn-stats" aria-label="Conflict summary">
    <article class="attn-stat attn-stat--absent">
        <span class="attn-stat__icon"><?= attn_icon('conflict') ?></span>
        <div class="attn-stat__body">
            <strong><?= $automaticOpen ?></strong>
            <span>Automatic conflicts</span>
        </div>
    </article>
    <article class="attn-stat attn-stat--late">
        <span class="attn-stat__icon"><?= attn_icon('alert') ?></span>
        <div class="attn-stat__body">
            <strong><?= $manualOpen ?></strong>
            <span>Manual open items</span>
        </div>
    </article>
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('check-circle') ?></span>
        <div class="attn-stat__body">
            <strong><?= $acknowledgedCount ?></strong>
            <span>Acknowledged</span>
        </div>
    </article>
    <article class="attn-stat attn-stat--present">
        <span class="attn-stat__icon"><?= attn_icon('shield-check') ?></span>
        <div class="attn-stat__body">
            <strong><?= $passedRules ?>/<?= count($policy) ?></strong>
            <span>Policy rules met</span>
        </div>
    </article>
</section>

<nav class="attn-tabs" aria-label="Conflict views">
    <a class="attn-tab<?= $tab === 'automatic' ? ' is-active' : '' ?>" href="./conflicts.php?<?= attn_e(http_build_query(['tab' => 'automatic', 'type' => $type, 'ack' => $showAcknowledged ? '1' : ''])) ?>">
        Automatic
        <span class="attn-tab__count"><?= $automaticOpen ?></span>
    </a>
    <a class="attn-tab<?= $tab === 'manual' ? ' is-active' : '' ?>" href="./conflicts.php?<?= attn_e(http_build_query(['tab' => 'manual', 'type' => $type, 'ack' => $showAcknowledged ? '1' : ''])) ?>">
        Manual
        <span class="attn-tab__count"><?= $manualOpen ?></span>
    </a>
</nav>

<?php
$toolbar = static function () use ($tab, $type, $types, $showAcknowledged, $automatic, $manual): void {
    ?>
    <form class="attn-toolbar" method="get" action="conflicts.php" aria-label="Conflict filters">
        <input type="hidden" name="tab" value="<?= attn_e($tab) ?>">
        <label class="attn-field">
            <span>Type</span>
            <select name="type" data-attn-auto-submit>
                <option value="">All types</option>
                <?php foreach ($types as $option): ?>
                    <option value="<?= attn_e($option) ?>" <?= $type === $option ? 'selected' : '' ?>><?= attn_e($option) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="attn-toggle">
            <input type="checkbox" name="ack" value="1" <?= $showAcknowledged ? 'checked' : '' ?> data-attn-auto-submit>
            <span class="attn-toggle__track" aria-hidden="true"></span>
            <span>Show acknowledged</span>
        </label>

        <span class="attn-toolbar__spacer" aria-hidden="true"></span>
        <span class="attn-pill attn-pill--neutral"><?= count($tab === 'automatic' ? $automatic : $manual) ?> shown</span>
    </form>
    <?php
};
?>

<?php if ($tab === 'automatic'): ?>
    <div class="attn-conflict-layout">
        <section class="attn-card attn-card--accent" aria-labelledby="automatic-conflicts-title">
            <div class="attn-card__head">
                <div>
                    <h2 id="automatic-conflicts-title">Automatic checks</h2>
                    <p class="attn-card__sub">Room, teacher, and section overlaps detected in the timetable.</p>
                </div>
            </div>

            <?php $toolbar(); ?>

            <?php if ($automatic === []): ?>
                <div class="attn-empty-state">
                    <span class="attn-empty-state__icon"><?= attn_icon('check-circle') ?></span>
                    <h3><?= $automaticOpen === 0 ? 'No conflicts detected' : 'No conflicts match this filter' ?></h3>
                    <p>
                        <?= $automaticOpen === 0
                            ? 'The timetable has no overlapping rooms, teachers, or sections. Automatic checks run against the current schedule.'
                            : 'Adjust the type filter or turn on acknowledged items to review the remaining records.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="attn-table-wrap">
                    <table class="attn-table attn-table--cards">
                        <thead>
                            <tr>
                                <th scope="col">Type</th>
                                <th scope="col">Day</th>
                                <th scope="col">Conflict</th>
                                <th scope="col">Reference</th>
                                <th scope="col">Status</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($automatic as $conflict): ?>
                                <tr>
                                    <td data-label="Type"><span class="attn-conflict-type is-<?= attn_e(strtolower($conflict['type'])) ?>"><?= attn_e($conflict['type']) ?></span></td>
                                    <td data-label="Day"><?= attn_e($conflict['day'] !== '' ? $conflict['day'] : '—') ?></td>
                                    <td data-label="Conflict">
                                        <div class="attn-conflict-title">
                                            <strong><?= attn_e($conflict['title']) ?></strong>
                                            <span><?= attn_e($conflict['description']) ?></span>
                                        </div>
                                    </td>
                                    <td data-label="Reference"><?= attn_e($conflict['reference'] !== '' ? $conflict['reference'] : '—') ?></td>
                                    <td data-label="Status"><span class="attn-pill <?= !empty($conflict['acknowledged']) ? 'is-acknowledged' : 'is-error' ?>"><?= !empty($conflict['acknowledged']) ? 'Acknowledged' : 'Open' ?></span></td>
                                    <td data-label="Actions">
                                        <?php if (empty($conflict['acknowledged'])): ?>
                                            <form method="post" action="conflicts.php">
                                                <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
                                                <input type="hidden" name="action" value="acknowledge">
                                                <input type="hidden" name="key" value="<?= attn_e($conflict['key']) ?>">
                                                <button class="attn-btn attn-btn--ghost attn-btn--tiny" type="submit">Acknowledge</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <aside class="attn-card attn-card--accent" aria-labelledby="policy-title">
            <div class="attn-card__head">
                <div>
                    <h2 id="policy-title">Scheduling Policy</h2>
                    <p class="attn-card__sub">Rules applied by the conflict scanner</p>
                </div>
            </div>

            <div class="attn-policy">
                <div class="attn-policy__banner<?= $allRulesMet ? '' : ' is-review' ?>">
                    <?= attn_icon($allRulesMet ? 'shield-check' : 'alert') ?>
                    <span><?= $allRulesMet ? 'All rules met' : 'Review required' ?></span>
                </div>

                <?php foreach ($policy as $rule): ?>
                    <div class="attn-policy__rule">
                        <div class="attn-policy__rule-top">
                            <strong><?= attn_e($rule['label']) ?></strong>
                            <span class="attn-pill <?= $rule['pass'] ? 'attn-pill--success' : 'attn-pill--danger' ?>"><?= $rule['pass'] ? 'Pass' : 'Review' ?></span>
                        </div>
                        <p><?= attn_e($rule['rule']) ?></p>
                        <span class="attn-policy__actual">Current: <?= attn_e($rule['actual']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="attn-note">
                <?= attn_icon('filter') ?>
                <span>Automatic checks compare every schedule pair for the same day and overlapping time block.</span>
            </p>
        </aside>
    </div>
<?php else: ?>
    <section class="attn-card attn-card--accent" aria-labelledby="manual-conflicts-title">
        <div class="attn-card__head">
            <div>
                <h2 id="manual-conflicts-title">Manual review queue</h2>
                <p class="attn-card__sub">Issues reported by faculty, facilities, and the registrar.</p>
            </div>
        </div>

        <?php $toolbar(); ?>

        <?php if ($manual === []): ?>
            <div class="attn-empty-state">
                <span class="attn-empty-state__icon"><?= attn_icon('check-circle') ?></span>
                <h3>No manual conflicts to review</h3>
                <p>Every reported issue has been acknowledged or resolved. New reports appear here immediately.</p>
            </div>
        <?php else: ?>
            <div class="attn-table-wrap">
                <table class="attn-table attn-table--cards">
                    <thead>
                        <tr>
                            <th scope="col">Type</th>
                            <th scope="col">Day</th>
                            <th scope="col">Conflict</th>
                            <th scope="col">Reference</th>
                            <th scope="col">Status</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manual as $conflict): ?>
                            <tr>
                                <td data-label="Type"><span class="attn-conflict-type is-<?= attn_e(strtolower($conflict['type'])) ?>"><?= attn_e($conflict['type']) ?></span></td>
                                <td data-label="Day"><?= attn_e($conflict['day'] !== '' ? $conflict['day'] : '—') ?></td>
                                <td data-label="Conflict">
                                    <div class="attn-conflict-title">
                                        <strong><?= attn_e($conflict['title']) ?></strong>
                                        <span><?= attn_e($conflict['description'] !== '' ? $conflict['description'] : 'No additional details provided.') ?></span>
                                        <?php if ($conflict['reported_by'] !== ''): ?>
                                            <span class="attn-ack-note">Reported by <?= attn_e($conflict['reported_by']) ?> &middot; <?= attn_e(attn_date(substr($conflict['created_at'], 0, 10))) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td data-label="Reference"><?= attn_e($conflict['reference'] !== '' ? $conflict['reference'] : '—') ?></td>
                                <td data-label="Status">
                                    <?php if (!empty($conflict['acknowledged'])): ?>
                                        <span class="attn-pill is-acknowledged">Acknowledged</span>
                                    <?php else: ?>
                                        <span class="attn-pill <?= $conflict['severity'] === 'error' ? 'is-error' : 'is-warn' ?>"><?= $conflict['severity'] === 'error' ? 'High' : 'Moderate' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Actions">
                                    <div class="attn-row-actions">
                                        <?php if (empty($conflict['acknowledged'])): ?>
                                            <form method="post" action="conflicts.php">
                                                <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
                                                <input type="hidden" name="action" value="acknowledge">
                                                <input type="hidden" name="key" value="<?= attn_e($conflict['key']) ?>">
                                                <button class="attn-btn attn-btn--ghost attn-btn--tiny" type="submit">Acknowledge</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="conflicts.php">
                                            <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
                                            <input type="hidden" name="action" value="resolve">
                                            <input type="hidden" name="id" value="<?= (int) $conflict['id'] ?>">
                                            <button class="attn-btn attn-btn--soft-danger attn-btn--tiny" type="submit">Resolve</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="attn-note">
            <?= attn_icon('alert') ?>
            <span>Manual reports are kept separate from automatic overlap checks. Use “Report Conflict” to add a new item.</span>
        </p>
    </section>
<?php endif; ?>

<dialog class="attn-dialog" id="report-conflict" aria-labelledby="report-conflict-title">
    <form method="post" action="conflicts.php">
        <input type="hidden" name="csrf_token" value="<?= attn_e($attendanceCsrf) ?>">
        <input type="hidden" name="action" value="create">

        <div class="attn-dialog__head">
            <div>
                <h2 id="report-conflict-title">Report Conflict</h2>
                <p>Log a scheduling issue for manual review.</p>
            </div>
            <button class="attn-dialog__close" type="button" data-attn-close-dialog aria-label="Close">
                <?= attn_icon('close') ?>
            </button>
        </div>

        <div class="attn-dialog__body">
            <label class="attn-field">
                <span>Type</span>
                <select name="conflict_type" required>
                    <?php foreach ($types as $option): ?>
                        <option value="<?= attn_e($option) ?>"><?= attn_e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="attn-field">
                <span>Day</span>
                <select name="day">
                    <option value="">Not day-specific</option>
                    <?php foreach (AttendanceRepository::DAYS as $option): ?>
                        <option value="<?= attn_e($option) ?>"><?= attn_e($option) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="attn-field">
                <span>Reference</span>
                <input type="text" name="reference" maxlength="150" placeholder="Room, teacher, or section">
            </label>

            <label class="attn-field">
                <span>Severity</span>
                <select name="severity">
                    <option value="warn">Moderate</option>
                    <option value="error">High</option>
                </select>
            </label>

            <label class="attn-field attn-field--full">
                <span>Title</span>
                <input type="text" name="title" maxlength="150" placeholder="Short summary of the issue" required>
            </label>

            <label class="attn-field attn-field--full">
                <span>Description</span>
                <textarea name="description" maxlength="2000" placeholder="Add the details the reviewer needs."></textarea>
            </label>
        </div>

        <div class="attn-dialog__foot">
            <button class="attn-btn attn-btn--ghost" type="button" data-attn-close-dialog>Cancel</button>
            <button class="attn-btn attn-btn--danger" type="submit">Submit report</button>
        </div>
    </form>
</dialog>
<?php require __DIR__ . '/partials/footer.php'; ?>
