<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$attendancePage = 'rooms';
$attendanceTitle = 'Room Monitoring';
$attendanceSubtitle = 'Live floor plan, occupancy, and room availability.';

$buildings = $attendanceRepository->buildings();
$building = strtoupper(trim((string) ($_GET['building'] ?? '')));
if ($building === '' || !in_array($building, $buildings, true)) {
    $building = in_array('D', $buildings, true) ? 'D' : ($buildings[0] ?? '');
}

$floors = $building !== '' ? $attendanceRepository->floors($building) : [];
$floor = (int) ($_GET['floor'] ?? 0);
if (!in_array($floor, $floors, true)) {
    $floor = in_array(3, $floors, true) ? 3 : ($floors[count($floors) - 1] ?? 1);
}

$search = trim((string) ($_GET['q'] ?? ''));
$allRooms = $building !== '' ? $attendanceRepository->roomBoard($building, $floor) : [];
$rooms = $allRooms;

if ($search !== '') {
    $needle = strtolower($search);
    $rooms = array_values(array_filter(
        $rooms,
        static fn (array $room): bool => str_contains(strtolower($room['name']), $needle)
            || str_contains(strtolower($room['type']), $needle)
    ));
}

$inUse = 0;
$available = 0;
$weeklyBookings = 0;
foreach ($allRooms as $room) {
    if ($room['classes'] !== []) {
        $inUse++;
    } else {
        $available++;
    }
    $weeklyBookings += (int) $room['weekly'];
}

$occupancy = $allRooms !== [] ? (int) round(($inUse / count($allRooms)) * 100) : 0;
$hallAfter = min(3, count($rooms));
$updatedAt = $attendanceNow->format('g:i A');

require __DIR__ . '/partials/header.php';
?>
<div class="attn-page-head">
    <div class="attn-page-head__copy">
        <p class="attn-breadcrumb">Attendance &amp; Scheduling <span>/</span> <strong>Room Monitoring</strong></p>
        <h1>Room Monitoring</h1>
        <p><?= attn_e($attendanceSubtitle) ?></p>
    </div>
    <div class="attn-page-head__actions">
        <span class="attn-updated">Updated <?= attn_e($updatedAt) ?> PHT &middot; <?= attn_e($attendanceNow->format('D, M j')) ?></span>
        <a class="attn-btn attn-btn--ghost" href="./rooms.php?<?= attn_e(http_build_query(['building' => $building, 'floor' => $floor])) ?>">
            <?= attn_icon('refresh') ?>
            <span>Refresh</span>
        </a>
    </div>
</div>

<form class="attn-filterbar" method="get" action="rooms.php" aria-label="Room filters">
    <div class="attn-segment-label">
        <span>Building</span>
        <div class="attn-segment" role="group" aria-label="Select building">
            <?php foreach ($buildings as $row): ?>
                <a
                    class="<?= $building === $row ? 'is-active' : '' ?>"
                    href="./rooms.php?<?= attn_e(http_build_query(['building' => $row, 'q' => $search])) ?>"
                    <?= $building === $row ? 'aria-current="page"' : '' ?>
                >Building <?= attn_e($row) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="attn-segment-label">
        <span>Floor</span>
        <div class="attn-segment" role="group" aria-label="Select floor">
            <?php foreach ($floors as $row): ?>
                <a
                    class="<?= $floor === $row ? 'is-active' : '' ?>"
                    href="./rooms.php?<?= attn_e(http_build_query(['building' => $building, 'floor' => $row, 'q' => $search])) ?>"
                    <?= $floor === $row ? 'aria-current="page"' : '' ?>
                >Level <?= (int) $row ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <span class="attn-filterbar__spacer" aria-hidden="true"></span>

    <label class="attn-search attn-field--grow">
        <span class="attn-sr-only">Search rooms</span>
        <?= attn_icon('search') ?>
        <input
            type="search"
            name="q"
            value="<?= attn_e($search) ?>"
            placeholder="Search room or type…"
            data-attn-room-search
            autocomplete="off"
        >
        <input type="hidden" name="building" value="<?= attn_e($building) ?>">
        <input type="hidden" name="floor" value="<?= (int) $floor ?>">
    </label>

    <a class="attn-btn attn-btn--ghost" href="./rooms.php?<?= attn_e(http_build_query(['building' => $building, 'floor' => $floor])) ?>">
        <?= attn_icon('close') ?>
        <span>Clear</span>
    </a>
</form>

<section class="attn-stats" aria-label="Room occupancy summary">
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('door') ?></span>
        <div class="attn-stat__body">
            <strong data-attn-room-total><?= count($rooms) ?></strong>
            <span>Rooms shown</span>
        </div>
    </article>
    <article class="attn-stat attn-stat--present">
        <span class="attn-stat__icon"><?= attn_icon('users') ?></span>
        <div class="attn-stat__body">
            <strong><?= $inUse ?></strong>
            <span>In use today</span>
        </div>
    </article>
    <article class="attn-stat">
        <span class="attn-stat__icon"><?= attn_icon('check-circle') ?></span>
        <div class="attn-stat__body">
            <strong><?= $available ?></strong>
            <span>Available today</span>
        </div>
    </article>
    <article class="attn-stat attn-stat--late">
        <span class="attn-stat__icon"><?= attn_icon('layers') ?></span>
        <div class="attn-stat__body">
            <strong><?= $occupancy ?>%</strong>
            <span>Current occupancy</span>
        </div>
    </article>
</section>

<section class="attn-card attn-card--accent attn-floorplan-card" aria-labelledby="floorplan-title">
    <div class="attn-card__head">
        <div>
            <h2 id="floorplan-title">Floor Plan &middot; Building <?= attn_e($building) ?>, Level <?= (int) $floor ?></h2>
            <p class="attn-card__sub"><?= count($allRooms) ?> rooms &middot; <?= $weeklyBookings ?> weekly booking<?= $weeklyBookings === 1 ? '' : 's' ?> &middot; <?= attn_e($attendanceNow->format('l')) ?> view</p>
        </div>
        <div class="attn-legend" aria-label="Floor plan legend">
            <span class="attn-legend__item"><span class="attn-legend__dot"></span> In use</span>
            <span class="attn-legend__item"><span class="attn-legend__dot attn-legend__dot--free"></span> Available</span>
            <span class="attn-legend__item"><span class="attn-legend__dot attn-legend__dot--facility"></span> Facility</span>
        </div>
    </div>

    <?php if ($rooms === []): ?>
        <div class="attn-floorplan-empty">
            <?= $search !== '' ? 'No rooms match “' . attn_e($search) . '” on this floor.' : 'No rooms are registered for this building and floor.' ?>
        </div>
    <?php else: ?>
        <div class="attn-floorplan">
            <div class="attn-floorplan__rooms">
                <?php foreach ($rooms as $index => $room): ?>
                    <?php require __DIR__ . '/partials/room-card.php'; ?>
                    <?php if (($index + 1) === $hallAfter): ?>
                        <div class="attn-floorplan__hall" data-attn-room-hall>
                            <span>Hallway</span>
                            <span class="attn-floorplan__stairs">Stairs</span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <p class="attn-floorplan-empty" data-attn-room-empty hidden>No rooms match your search.</p>
    <?php endif; ?>

    <p class="attn-note">
        <?= attn_icon('building') ?>
        <span>Room state reflects today's timetable. Use the building and level controls to inspect every floor; the search box filters room tiles instantly.</span>
    </p>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
