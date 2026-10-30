<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$enrollmentPage = 'home';
$enrollmentTitle = 'Admissions Home';
$enrollmentSubtitle = 'Admissions, requirements, and the student portal in one place';

$programs = $enrollmentRepository->programs();
$programCounts = $enrollmentRepository->programCounts();
$applicationCount = $enrollmentRepository->applicationCount();
$verifiedCount = $enrollmentRepository->verifiedCount();
$steps = enr_steps();
$requirements = enr_requirements();

require __DIR__ . '/partials/header.php';
?>
<section class="enr-hero">
    <div class="enr-hero__copy">
        <p class="enr-eyebrow">Admissions &middot; AY <?= enr_e($enrollmentSchoolYear) ?></p>
        <h1>Enrollment &amp;<br><em>Student Portal</em></h1>
        <p class="enr-hero__lede">
            Apply for admission to Fullbright College, prepare your requirements, and take the
            entrance examination online. Already enrolled? Sign in to your student portal to
            continue where you left off.
        </p>
        <div class="enr-hero__actions">
            <a class="enr-btn enr-btn--primary" href="./enroll.php">
                <?= enr_icon('form') ?>
                <span>Start Enrollment</span>
                <?= enr_icon('arrow-right') ?>
            </a>
            <a class="enr-btn enr-btn--ghost" href="<?= enr_e(enr_login_url()) ?>">
                <?= enr_icon('login') ?>
                <span>Student Login</span>
            </a>
        </div>
        <div class="enr-hero__meta">
            <span class="enr-pill enr-pill--open"><span aria-hidden="true"></span> Applications open</span>
            <span>Four steps &middot; About 20 minutes</span>
        </div>
    </div>

    <aside class="enr-hero__panel" aria-labelledby="admissions-panel-title">
        <header class="enr-hero__panel-head">
            <div>
                <small>Admissions desk</small>
                <h2 id="admissions-panel-title">Key dates</h2>
            </div>
            <span class="enr-chip">AY <?= enr_e($enrollmentSchoolYear) ?></span>
        </header>

        <ol class="enr-dates">
            <li>
                <span class="enr-dates__icon"><?= enr_icon('calendar') ?></span>
                <div>
                    <strong>Application window</strong>
                    <small>Online filing is open now</small>
                </div>
                <span class="enr-dates__state">Open</span>
            </li>
            <li>
                <span class="enr-dates__icon"><?= enr_icon('clock') ?></span>
                <div>
                    <strong>Entrance examination</strong>
                    <small>Auto-generated after requirements</small>
                </div>
                <span class="enr-dates__state">40 items</span>
            </li>
            <li>
                <span class="enr-dates__icon"><?= enr_icon('cap') ?></span>
                <div>
                    <strong>Classes begin</strong>
                    <small>First semester, AY <?= enr_e($enrollmentSchoolYear) ?></small>
                </div>
                <span class="enr-dates__state">Soon</span>
            </li>
            <li>
                <span class="enr-dates__icon"><?= enr_icon('shield') ?></span>
                <div>
                    <strong>Registrar review</strong>
                    <small>Completed applications, in order</small>
                </div>
                <span class="enr-dates__state">2–3 days</span>
            </li>
        </ol>

        <div class="enr-hero__panel-foot">
            <p>Questions before you apply? Visit the registrar or email <a href="mailto:admissions@fullbright.edu.ph">admissions@fullbright.edu.ph</a>.</p>
        </div>
    </aside>
</section>

<section class="enr-stats" aria-label="Enrollment at a glance">
    <article class="enr-stat">
        <small>Programs offered</small>
        <strong><?= count($programs) ?></strong>
        <span>ECT department</span>
    </article>
    <article class="enr-stat">
        <small>Enrollment steps</small>
        <strong><?= count($steps) ?></strong>
        <span>Instructions to exam</span>
    </article>
    <article class="enr-stat">
        <small>Applications on file</small>
        <strong><?= $applicationCount ?></strong>
        <span>This admissions cycle</span>
    </article>
    <article class="enr-stat">
        <small>Verified emails</small>
        <strong><?= $verifiedCount ?></strong>
        <span>Ready for exam</span>
    </article>
</section>

<section class="enr-section" aria-labelledby="process-title">
    <div class="enr-section__head">
        <p class="enr-eyebrow">The process</p>
        <h2 id="process-title">How enrollment works</h2>
        <p>Four clear stages. Finish them in order and your application moves to the registrar for review.</p>
    </div>

    <div class="enr-step-grid">
        <?php foreach ($steps as $index => $step): ?>
            <article class="enr-step">
                <span class="enr-step__num"><?= $index + 1 ?></span>
                <div>
                    <h3><?= enr_e($step['title']) ?></h3>
                    <p><?= enr_e($step['detail']) ?></p>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="enr-split" aria-labelledby="requirements-title">
    <div class="enr-card enr-card--striped">
        <header class="enr-card__head">
            <div>
                <p class="enr-eyebrow">Prepare ahead</p>
                <h2 id="requirements-title">Enrollment requirements</h2>
            </div>
            <span class="enr-chip">4 documents</span>
        </header>

        <ul class="enr-checklist">
            <?php foreach ($requirements as $requirement): ?>
                <li>
                    <span class="enr-checklist__mark"><?= enr_icon('check') ?></span>
                    <div>
                        <strong><?= enr_e($requirement['title']) ?></strong>
                        <small><?= enr_e($requirement['detail']) ?> &middot; <?= enr_e($requirement['note']) ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="enr-hint">
            <?= enr_icon('camera') ?>
            <span>Have digital copies ready before you start &mdash; PDF, JPG, or PNG, up to 8&nbsp;MB each.</span>
        </p>
    </div>

    <div class="enr-card enr-card--quiet enr-portal-card">
        <header class="enr-card__head">
            <div>
                <p class="enr-eyebrow">Already with us?</p>
                <h2>Student portal</h2>
            </div>
        </header>
        <p class="enr-quiet__copy">
            Check your enrollment status, review your submitted requirements, and access your
            student records from the campus portal.
        </p>
        <ul class="enr-mini-list">
            <li><?= enr_icon('check-circle') ?><span>Enrollment status and section assignments</span></li>
            <li><?= enr_icon('check-circle') ?><span>Entrance exam results and receipts</span></li>
            <li><?= enr_icon('check-circle') ?><span>Digital student ID and profile</span></li>
        </ul>
        <a class="enr-btn enr-btn--navy" href="<?= enr_e(enr_login_url()) ?>">
            <?= enr_icon('login') ?>
            <span>Sign in to the portal</span>
            <?= enr_icon('arrow-right') ?>
        </a>
        <p class="enr-portal-card__foot">
            <?= enr_icon('lock') ?>
            <span>Portal access is issued once your enrollment is confirmed by the registrar.</span>
        </p>
    </div>
</section>

<section class="enr-section" aria-labelledby="programs-title">
    <div class="enr-section__head">
        <p class="enr-eyebrow">Programs</p>
        <h2 id="programs-title">Choose your field</h2>
        <p>All programs sit under the Engineering &amp; Communication Technology department.</p>
    </div>

    <div class="enr-program-grid">
        <?php foreach ($programs as $code => $program): ?>
            <?php $onFile = (int) ($programCounts[$code] ?? 0); ?>
            <article class="enr-program">
                <header class="enr-program__head">
                    <span class="enr-program__code"><?= enr_e($code) ?></span>
                    <span class="enr-program__count"><?= $onFile ?> on file</span>
                </header>
                <h3><?= enr_e($program['title']) ?></h3>
                <p><?= enr_e($program['blurb']) ?></p>
                <ul class="enr-program__careers">
                    <?php foreach ($program['careers'] as $career): ?>
                        <li><?= enr_e(trim($career)) ?></li>
                    <?php endforeach; ?>
                </ul>
                <a class="enr-program__link" href="./enroll.php">
                    <span>Apply to <?= enr_e($code) ?></span>
                    <?= enr_icon('arrow-right') ?>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section class="enr-help" aria-labelledby="help-title">
    <div class="enr-help__copy">
        <?= enr_icon('phone', 'enr-help__icon') ?>
        <div>
            <h2 id="help-title">Need help with your application?</h2>
            <p>The admissions team can walk you through your requirements, exam schedule, and portal access.</p>
        </div>
    </div>
    <div class="enr-help__actions">
        <a class="enr-btn enr-btn--ghost" href="mailto:admissions@fullbright.edu.ph">
            <?= enr_icon('mail') ?>
            <span>Email admissions</span>
        </a>
        <a class="enr-btn enr-btn--primary" href="./enroll.php">
            <span>Review step 1</span>
            <?= enr_icon('arrow-right') ?>
        </a>
    </div>
</section>
<?php require __DIR__ . '/partials/footer.php'; ?>
