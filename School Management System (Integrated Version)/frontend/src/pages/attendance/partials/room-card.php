<?php
declare(strict_types=1);

$roomName = (string) ($room['name'] ?? '');
$roomClasses = $room['classes'] ?? [];
$isFacility = (string) ($room['type'] ?? '') === 'Facility';
$isFree = $roomClasses === [] && !$isFacility;
$cardClass = $isFacility ? ' is-facility' : ($isFree ? ' is-free' : '');
$stateLabel = $isFacility ? 'Facility' : ($roomClasses === [] ? 'Available' : 'In use');
$roomKey = strtolower($roomName . ' ' . (string) ($room['type'] ?? ''));
?>
<article
    class="attn-room-card<?= $cardClass ?>"
    data-attn-room-card
    data-room-key="<?= attn_e($roomKey) ?>"
>
    <div class="attn-room-card__top">
        <div>
            <h3 class="attn-room-card__name"><?= attn_e($roomName) ?></h3>
            <span class="attn-room-card__type"><?= attn_e((string) ($room['type'] ?? 'Room')) ?> &middot; Level <?= (int) ($room['floor'] ?? 1) ?></span>
        </div>
        <span class="attn-room-card__state"><?= attn_e($stateLabel) ?></span>
    </div>

    <div class="attn-room-card__meta">
        <span><strong><?= (int) ($room['weekly'] ?? 0) ?></strong> this week</span>
        <span><strong><?= count($roomClasses) ?></strong> today</span>
    </div>

    <?php if ($roomClasses !== []): ?>
        <?php $firstClass = $roomClasses[0]; ?>
        <div class="attn-room-card__class">
            <strong><?= attn_e(attn_time($firstClass['start_time'])) ?> &ndash; <?= attn_e(attn_time($firstClass['end_time'])) ?></strong>
            <span><?= attn_e($firstClass['subject_code'] . ' · ' . $firstClass['section']) ?></span>
            <?php if (count($roomClasses) > 1): ?>
                <span>+<?= count($roomClasses) - 1 ?> more class<?= count($roomClasses) - 1 === 1 ? '' : 'es' ?> today</span>
            <?php endif; ?>
        </div>
    <?php elseif ($isFacility): ?>
        <p class="attn-room-card__empty"><?= attn_e((string) ($room['description'] ?? 'Shared facility')) ?></p>
    <?php else: ?>
        <p class="attn-room-card__empty">Available for the whole day.</p>
    <?php endif; ?>
</article>
