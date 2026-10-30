<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'payments';
$financeTitle = 'Payments';
$financeSubtitle = 'All recorded payments';

$summaries = $financeRepository->summaries();
$payments = $financeRepository->payments();
$studentNames = [];
$studentBalances = [];

foreach ($summaries as $row) {
    $studentNames[$row['student']['no']] = $row['student']['name'];
    $studentBalances[$row['student']['no']] = $row['assessment']['remaining'];
}

$paymentError = '';
$selectedStudent = (string) ($_POST['student_no'] ?? ($summaries[0]['student']['no'] ?? ''));
$selectedTerm = (string) ($_POST['term'] ?? 'Prelim');
$selectedMethod = (string) ($_POST['method'] ?? 'Cash');
$selectedAmount = (string) ($_POST['amount'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    if (!auth_verify_csrf(is_string($token) ? $token : null)) {
        $paymentError = 'Your session expired. Refresh the page and try again.';
    } else {
        $result = $financeRepository->recordPayment(
            $selectedStudent,
            $selectedTerm,
            $selectedMethod,
            (float) $selectedAmount
        );

        if (is_array($result)) {
            fin_set_flash(
                'success',
                'Payment recorded. Reference ' . $result['ref'] . ' for ' . fin_money($result['amount']) . '.'
            );
            header('Location: payments.php');
            exit;
        }

        $paymentError = $result;
    }
}

$flash = fin_flash();
$collected = array_sum(array_column($payments, 'amount'));

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero" aria-labelledby="payments-heading">
    <h1 id="payments-heading">Payments</h1>
    <p><?= fin_e($financeSubtitle) ?></p>
</section>

<?php if ($flash !== null): ?>
    <div class="fin-notice" role="status">
        <?= fin_icon($flash['type'] === 'success' ? 'check-circle' : 'alert-circle') ?>
        <span><?= fin_e($flash['message']) ?></span>
    </div>
<?php endif; ?>

<?php if ($paymentError !== ''): ?>
    <div class="fin-alert" role="alert">
        <?= fin_icon('alert-circle') ?>
        <span><?= fin_e($paymentError) ?></span>
    </div>
<?php endif; ?>

<section class="fin-card" aria-labelledby="record-payment-heading">
    <h2 id="record-payment-heading">Record counter payment</h2>

    <?php if ($summaries === []): ?>
        <p class="fin-empty fin-empty--card">Add students to the finance directory before recording a payment.</p>
    <?php else: ?>
        <form class="fin-payment-form" method="post" action="payments.php">
            <input type="hidden" name="csrf_token" value="<?= fin_e($financeCsrf) ?>">

            <label>
                <span>Student</span>
                <select name="student_no" required>
                    <?php foreach ($summaries as $row): ?>
                        <?php $student = $row['student']; ?>
                        <option value="<?= fin_e($student['no']) ?>"<?= $student['no'] === $selectedStudent ? ' selected' : '' ?>>
                            <?= fin_e($student['name']) ?> (<?= fin_e($student['no']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Term</span>
                <select name="term" required>
                    <?php foreach (FinanceRepository::TERMS as $term): ?>
                        <option value="<?= fin_e($term) ?>"<?= $term === $selectedTerm ? ' selected' : '' ?>><?= fin_e($term) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>
                <span>Amount (₱)</span>
                <input name="amount" type="number" min="0.01" step="0.01" value="<?= fin_e($selectedAmount) ?>" required>
            </label>

            <label>
                <span>Method</span>
                <select name="method" required>
                    <?php foreach (FinanceRepository::METHODS as $method): ?>
                        <option value="<?= fin_e($method) ?>"<?= $method === $selectedMethod ? ' selected' : '' ?>><?= fin_e($method) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <button class="fin-button" type="submit">Record payment</button>
        </form>

        <p class="fin-form__note">
            Payments are applied to the selected term. The amount cannot exceed that term's remaining balance.
        </p>
    <?php endif; ?>
</section>

<section class="fin-card fin-card--spaced" aria-labelledby="payment-history-heading">
    <div class="fin-card__head">
        <h2 id="payment-history-heading">Payment history</h2>
        <span class="fin-chip"><?= fin_number(count($payments)) ?> recorded &middot; <?= fin_money($collected) ?></span>
    </div>

    <div class="fin-table-wrap">
        <table class="fin-table">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Reference</th>
                    <th scope="col">Student</th>
                    <th scope="col">Term</th>
                    <th scope="col">Method</th>
                    <th scope="col" class="is-amount">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments === []): ?>
                    <tr>
                        <td colspan="6" class="fin-empty">No payments recorded yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?= fin_e(fin_date($payment['date'])) ?></td>
                            <td class="fin-ref"><?= fin_e($payment['ref']) ?></td>
                            <td><?= fin_e($studentNames[$payment['no']] ?? $payment['no']) ?></td>
                            <td><?= fin_e($payment['term']) ?></td>
                            <td><?= fin_e($payment['method']) ?></td>
                            <td class="is-amount"><?= fin_money($payment['amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
