<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'dashboard';
$financeTitle = 'Admin Dashboard';
$financeSubtitle = 'Finance overview';

$dashboard = $financeRepository->dashboard();
$studentNames = [];
foreach ($financeRepository->students() as $student) {
    $studentNames[$student['no']] = $student['name'];
}

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero" aria-labelledby="finance-heading">
    <h1 id="finance-heading">Admin Dashboard</h1>
    <p><?= fin_e($financeSubtitle) ?></p>
</section>

<section class="fin-stats" aria-label="Finance summary">
    <article class="fin-stat">
        <span class="fin-stat__icon is-blue"><?= fin_icon('students') ?></span>
        <div>
            <span class="fin-stat__label">Students</span>
            <strong><?= fin_number($dashboard['students']) ?></strong>
        </div>
    </article>

    <article class="fin-stat">
        <span class="fin-stat__icon is-gold"><?= fin_icon('wallet') ?></span>
        <div>
            <span class="fin-stat__label">Total Assessment</span>
            <strong><?= fin_money($dashboard['assessed']) ?></strong>
        </div>
    </article>

    <article class="fin-stat">
        <span class="fin-stat__icon is-green"><?= fin_icon('check-circle') ?></span>
        <div>
            <span class="fin-stat__label">Collected</span>
            <strong><?= fin_money($dashboard['collected']) ?></strong>
        </div>
    </article>

    <article class="fin-stat">
        <span class="fin-stat__icon is-red"><?= fin_icon('alert-circle') ?></span>
        <div>
            <span class="fin-stat__label">Students Overdue</span>
            <strong><?= fin_number($dashboard['overdue']) ?></strong>
        </div>
    </article>
</section>

<div class="fin-dashboard-grid">
    <section class="fin-card fin-card--striped" aria-labelledby="recent-payments-heading">
        <h2 id="recent-payments-heading">Recent payments</h2>

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
                    <?php if ($dashboard['recent_payments'] === []): ?>
                        <tr>
                            <td colspan="6" class="fin-empty">No records yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dashboard['recent_payments'] as $payment): ?>
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

    <section class="fin-card fin-card--striped" aria-labelledby="highest-balances-heading">
        <h2 id="highest-balances-heading">Highest balances</h2>

        <?php if ($dashboard['highest_balances'] === []): ?>
            <p class="fin-empty fin-empty--card">No outstanding balances.</p>
        <?php else: ?>
            <ul class="fin-balance-list">
                <?php foreach ($dashboard['highest_balances'] as $row): ?>
                    <li>
                        <div>
                            <strong><?= fin_e($row['student']['name']) ?></strong>
                            <small><?= fin_e($row['student']['no']) ?> &middot; <?= fin_e($row['student']['course']) ?></small>
                        </div>
                        <span><?= fin_money($row['assessment']['remaining']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
