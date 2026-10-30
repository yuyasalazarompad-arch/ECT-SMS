<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'reports';
$financeTitle = 'Financial Reports';
$financeSubtitle = 'Collections summary';

$dashboard = $financeRepository->dashboard();
$termCollections = $financeRepository->termCollections();
$methodCollections = $financeRepository->methodCollections();
$outstanding = max(0.0, $dashboard['assessed'] - $dashboard['collected']);
$collectionRate = $dashboard['assessed'] > 0
    ? (int) round($dashboard['collected'] / $dashboard['assessed'] * 100)
    : 0;

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero" aria-labelledby="reports-heading">
    <h1 id="reports-heading">Financial Reports</h1>
    <p><?= fin_e($financeSubtitle) ?></p>
</section>

<section class="fin-report-summary" aria-label="Collection summary">
    <article>
        <span>Expected collections</span>
        <strong><?= fin_money($dashboard['assessed']) ?></strong>
    </article>
    <article>
        <span>Collected</span>
        <strong class="is-green"><?= fin_money($dashboard['collected']) ?></strong>
    </article>
    <article>
        <span>Outstanding</span>
        <strong class="is-red"><?= fin_money($outstanding) ?></strong>
    </article>
    <article>
        <span>Collection rate</span>
        <strong><?= $collectionRate ?>%</strong>
    </article>
</section>

<div class="fin-report-grid">
    <section class="fin-card" aria-labelledby="term-collections-heading">
        <h2 id="term-collections-heading">Collections per term</h2>

        <div class="fin-table-wrap">
            <table class="fin-table">
                <thead>
                    <tr>
                        <th scope="col">Term</th>
                        <th scope="col" class="is-amount">Expected</th>
                        <th scope="col" class="is-amount">Collected</th>
                        <th scope="col" class="is-amount">Outstanding</th>
                        <th scope="col" class="is-amount">Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($termCollections as $term): ?>
                        <tr>
                            <td><strong><?= fin_e($term['term']) ?></strong></td>
                            <td class="is-amount"><?= fin_money($term['expected']) ?></td>
                            <td class="is-amount"><?= fin_money($term['collected']) ?></td>
                            <td class="is-amount"><?= fin_money($term['outstanding']) ?></td>
                            <td class="is-amount"><?= (int) $term['rate'] ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="fin-table__total">
                        <td>Total</td>
                        <td class="is-amount"><?= fin_money(array_sum(array_column($termCollections, 'expected'))) ?></td>
                        <td class="is-amount"><?= fin_money(array_sum(array_column($termCollections, 'collected'))) ?></td>
                        <td class="is-amount"><?= fin_money(array_sum(array_column($termCollections, 'outstanding'))) ?></td>
                        <td class="is-amount"><?= $collectionRate ?>%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="fin-card" aria-labelledby="method-collections-heading">
        <h2 id="method-collections-heading">Payments by method</h2>

        <?php if ($methodCollections === []): ?>
            <p class="fin-empty fin-empty--card">No payments recorded yet.</p>
        <?php else: ?>
            <ul class="fin-method-list">
                <?php foreach ($methodCollections as $method): ?>
                    <li>
                        <span><?= fin_e($method['method']) ?><small><?= fin_number($method['count']) ?> transaction<?= $method['count'] === 1 ? '' : 's' ?></small></span>
                        <strong><?= fin_money($method['total']) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
