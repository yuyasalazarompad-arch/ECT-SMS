<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$financePage = 'notifications';
$financeTitle = 'Notifications';
$financeSubtitle = 'Activity & announcements';

$notes = $financeRepository->notesFor('ADMIN');

require __DIR__ . '/partials/header.php';
?>
<section class="fin-hero" aria-labelledby="notifications-heading">
    <h1 id="notifications-heading">Notifications</h1>
    <p><?= fin_e($financeSubtitle) ?></p>
</section>

<?php if ($notes === []): ?>
    <section class="fin-card">
        <p class="fin-empty fin-empty--card">No notifications yet. Payment activity will appear here.</p>
    </section>
<?php else: ?>
    <section class="fin-notification-list" aria-label="Finance notifications">
        <?php foreach ($notes as $note): ?>
            <article class="fin-notification<?= $note['read'] ? '' : ' is-unread' ?>">
                <span class="fin-notification__icon"><?= fin_icon('bell') ?></span>
                <div>
                    <div class="fin-notification__head">
                        <strong><?= fin_e($note['title']) ?></strong>
                        <time datetime="<?= fin_e($note['date']) ?>"><?= fin_e(fin_date($note['date'])) ?></time>
                    </div>
                    <p><?= fin_e($note['message']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
