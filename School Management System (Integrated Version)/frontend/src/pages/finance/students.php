<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'students';
$financeTitle = 'Students';
$financeSubtitle = 'All enrolled students';

$summaries = $financeRepository->summaries();

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero fin-hero--row" aria-labelledby="students-heading">
    <div>
        <h1 id="students-heading">Students</h1>
        <p><?= fin_e($financeSubtitle) ?></p>
    </div>
    <label class="fin-search">
        <?= fin_icon('search') ?>
        <span class="fin-sr-only">Search students</span>
        <input type="search" placeholder="Search students…" data-fin-search="#students-table">
    </label>
</section>

<section class="fin-card" aria-label="Student assessments">
    <div class="fin-table-wrap">
        <table class="fin-table" id="students-table">
            <thead>
                <tr>
                    <th scope="col">Student No.</th>
                    <th scope="col">Name</th>
                    <th scope="col">Course</th>
                    <th scope="col" class="is-amount">Assessment</th>
                    <th scope="col" class="is-amount">Paid</th>
                    <th scope="col" class="is-amount">Balance</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($summaries === []): ?>
                    <tr data-empty="true">
                        <td colspan="7" class="fin-empty">No students in the finance directory yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($summaries as $row): ?>
                        <?php
                        $student = $row['student'];
                        $assessment = $row['assessment'];
                        ?>
                        <tr>
                            <td class="fin-ref"><?= fin_e($student['no']) ?></td>
                            <td>
                                <strong><?= fin_e($student['name']) ?></strong>
                                <small class="fin-subline"><?= fin_e($student['email']) ?></small>
                            </td>
                            <td>
                                <?= fin_e($student['course']) ?>
                                <small class="fin-subline"><?= fin_e(fin_year_label((int) $student['year'])) ?> Year &middot; <?= (int) $student['sem'] === 1 ? '1st' : '2nd' ?> Sem</small>
                            </td>
                            <td class="is-amount"><?= fin_money($assessment['tuition']) ?></td>
                            <td class="is-amount"><?= fin_money($assessment['paid']) ?></td>
                            <td class="is-amount"><?= fin_money($assessment['remaining']) ?></td>
                            <td><span class="fin-badge <?= fin_status_class($assessment['status']) ?>"><?= fin_e($assessment['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
