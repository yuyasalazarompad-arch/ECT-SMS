<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'fees';
$financeTitle = 'Fees';
$financeSubtitle = 'Fee settings & per-term assessments';

$feeError = '';
$formRate = $financeSettings['rate'];
$formEnroll = $financeSettings['enroll'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    $formRate = (float) ($_POST['rate'] ?? 0);
    $formEnroll = (float) ($_POST['enroll'] ?? -1);

    if (!auth_verify_csrf(is_string($token) ? $token : null)) {
        $feeError = 'Your session expired. Refresh the page and try again.';
    } else {
        $feeError = $financeRepository->saveSettings($formRate, $formEnroll) ?? '';

        if ($feeError === '') {
            header('Location: fees.php?saved=1');
            exit;
        }
    }
}

$saved = isset($_GET['saved']);
$summaries = $financeRepository->summaries();

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero" aria-labelledby="fees-heading">
    <h1 id="fees-heading">Fees</h1>
    <p><?= fin_e($financeSubtitle) ?></p>
</section>

<?php if ($saved): ?>
    <div class="fin-notice" role="status">
        <?= fin_icon('check-circle') ?>
        <span>Fee settings saved. Student assessments now use the updated rate.</span>
    </div>
<?php endif; ?>

<?php if ($feeError !== ''): ?>
    <div class="fin-alert" role="alert">
        <?= fin_icon('alert-circle') ?>
        <span><?= fin_e($feeError) ?></span>
    </div>
<?php endif; ?>

<div class="fin-fees-grid">
    <form class="fin-card fin-form" method="post" action="fees.php">
        <h2>Fee settings</h2>
        <input type="hidden" name="csrf_token" value="<?= fin_e($financeCsrf) ?>">

        <div class="fin-form__grid">
            <label>
                <span>Rate per unit (₱)</span>
                <input name="rate" type="number" min="1" step="0.01" value="<?= fin_e($formRate) ?>" required>
            </label>
            <label>
                <span>Enrollment fee (₱)</span>
                <input name="enroll" type="number" min="0" step="0.01" value="<?= fin_e($formEnroll) ?>" required>
            </label>
            <div class="fin-readout">
                <span>School year</span>
                <strong><?= fin_e($financeSettings['school_year']) ?></strong>
            </div>
        </div>

        <p class="fin-form__note">
            The balance after the enrollment fee is split across the three terms:
            50% Prelim, 25% Midterm, and 25% Finals.
        </p>

        <button class="fin-button" type="submit">Save settings</button>
    </form>

    <aside class="fin-card fin-summary-card" aria-labelledby="fee-summary-heading">
        <h2 id="fee-summary-heading">Current setup</h2>
        <dl class="fin-summary-list">
            <div>
                <dt>Rate per unit</dt>
                <dd><?= fin_money($financeSettings['rate']) ?></dd>
            </div>
            <div>
                <dt>Enrollment fee</dt>
                <dd><?= fin_money($financeSettings['enroll']) ?></dd>
            </div>
            <div>
                <dt>Assessment terms</dt>
                <dd>Prelim &middot; Midterm &middot; Finals</dd>
            </div>
            <div>
                <dt>Students assessed</dt>
                <dd><?= fin_number(count($summaries)) ?></dd>
            </div>
        </dl>
    </aside>
</div>

<section class="fin-card fin-card--spaced" aria-labelledby="assessments-heading">
    <h2 id="assessments-heading">Assessments per term</h2>

    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    <th scope="col">Student</th>
                    <th scope="col">Term</th>
                    <th scope="col">Due</th>
                    <th scope="col" class="is-amount">Amount</th>
                    <th scope="col" class="is-amount">Paid</th>
                    <th scope="col" class="is-amount">Balance</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($summaries === []): ?>
                    <tr>
                        <td colspan="7" class="fin-empty">No assessments yet. Add students to the finance directory to begin.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summaries as $row): ?>
                        <?php foreach ($row['assessment']['terms'] as $term): ?>
                            <tr>
                                <td><?= fin_e($row['student']['name']) ?></td>
                                <td><?= fin_e($term['name']) ?></td>
                                <td><?= fin_e(fin_date($term['due'])) ?></td>
                                <td class="is-amount"><?= fin_money($term['amount']) ?></td>
                                <td class="is-amount"><?= fin_money($term['paid']) ?></td>
                                <td class="is-amount"><?= fin_money($term['balance']) ?></td>
                                <td><span class="fin-badge <?= fin_status_class($term['status']) ?>"><?= fin_e($term['status']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
