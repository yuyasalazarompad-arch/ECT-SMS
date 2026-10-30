<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$enrollmentPage = 'enroll';
$enrollmentTitle = 'Student Enrollment Form';
$enrollmentSubtitle = 'Step 1 of 4 &middot; Enrollment instructions';

$steps = enr_steps();
$requirements = enr_requirements();

require __DIR__ . '/partials/header.php';
?>
<section class="enr-page-head">
    <div>
        <a class="enr-back" href="./index.php">
            <?= enr_icon('arrow-left') ?>
            <span>Back to admissions home</span>
        </a>
        <p class="enr-eyebrow">Student Enrollment Form</p>
        <h1>Enrollment<br><em>instructions</em></h1>
        <p class="enr-page-head__lede">
            Registration and enrollment are a single process. Read each step carefully, prepare
            your documents, and keep your email inbox open &mdash; your application can only be
            submitted after your email is verified and the entrance exam score is recorded.
        </p>
    </div>
    <aside class="enr-page-head__badge" aria-label="Application progress">
        <small>Step</small>
        <strong>1<span>/4</span></strong>
        <p><?= enr_e($steps[0]['title']) ?></p>
    </aside>
</section>

<ol class="enr-stepper" aria-label="Enrollment steps">
    <?php foreach ($steps as $index => $step): ?>
        <?php $state = $index === 0 ? 'is-current' : 'is-upcoming'; ?>
        <li class="enr-stepper__item <?= $state ?>"<?= $index === 0 ? ' aria-current="step"' : '' ?>>
            <span class="enr-stepper__dot"><?= $index + 1 ?></span>
            <div>
                <strong><?= enr_e($step['title']) ?></strong>
                <small><?= enr_e($step['detail']) ?></small>
            </div>
        </li>
    <?php endforeach; ?>
</ol>

<div class="enr-layout">
    <section class="enr-card enr-card--striped enr-instructions">
        <header class="enr-card__head">
            <div>
                <p class="enr-eyebrow">Step 1 of 4</p>
                <h2>Enrollment Instructions</h2>
            </div>
            <span class="enr-chip">Required reading</span>
        </header>

        <div class="enr-notice" role="note">
            <?= enr_icon('shield') ?>
            <div>
                <strong>Complete the sections in order.</strong>
                <p>Your application is saved as a draft until every requirement, including the entrance examination, is finished.</p>
            </div>
        </div>

        <ol class="enr-instruction-list">
            <li>
                <span class="enr-instruction-list__num">01</span>
                <div>
                    <h3>Email verification</h3>
                    <p>
                        Enter your email address and click the verification link we send you. The link is
                        valid for <strong>24 hours</strong> and can be used once. Use an inbox you check daily.
                    </p>
                </div>
                <span class="enr-instruction-list__icon"><?= enr_icon('mail') ?></span>
            </li>
            <li>
                <span class="enr-instruction-list__num">02</span>
                <div>
                    <h3>Personal information</h3>
                    <p>
                        Fill in your name, birthday, contact numbers, both addresses, and your chosen
                        course or program. Keep your details consistent with your school records.
                    </p>
                </div>
                <span class="enr-instruction-list__icon"><?= enr_icon('user') ?></span>
            </li>
            <li>
                <span class="enr-instruction-list__num">03</span>
                <div>
                    <h3>Required documents</h3>
                    <p>
                        Upload your PSA birth certificate, certificate of good moral character, Form 138,
                        and a 2x2 ID photo. Document limit is <strong>8&nbsp;MB</strong> per file.
                    </p>
                </div>
                <span class="enr-instruction-list__icon"><?= enr_icon('file') ?></span>
            </li>
            <li>
                <span class="enr-instruction-list__num">04</span>
                <div>
                    <h3>Entrance examination</h3>
                    <p>
                        Generate your exam code and QR, then answer the auto-generated Math and English
                        questions. Your score is recorded automatically and completes your application.
                    </p>
                </div>
                <span class="enr-instruction-list__icon"><?= enr_icon('cap') ?></span>
            </li>
        </ol>

        <div class="enr-note-row">
            <?= enr_icon('alert') ?>
            <p>
                Applications with unverified emails, missing documents, or no exam score stay
                <strong>pending</strong>. The registrar reviews completed applications in the order they
                arrive.
            </p>
        </div>

        <footer class="enr-actions">
            <a class="enr-btn enr-btn--ghost" href="./index.php">
                <?= enr_icon('arrow-left') ?>
                <span>Back to Home</span>
            </a>
            <a class="enr-btn enr-btn--primary" href="./application.php">
                <span>Proceed to Application</span>
                <?= enr_icon('arrow-right') ?>
            </a>
        </footer>
    </section>

    <aside class="enr-side">
        <section class="enr-card enr-card--quiet enr-side__card">
            <header class="enr-card__head">
                <h2>Before you begin</h2>
                <span class="enr-chip" data-enr-checklist-count>0/4</span>
            </header>
            <ul class="enr-check" data-enr-checklist>
                <li>
                    <label>
                        <input type="checkbox" value="email">
                        <span class="enr-check__box"><?= enr_icon('check') ?></span>
                        <span>An active email address I can access today</span>
                    </label>
                </li>
                <li>
                    <label>
                        <input type="checkbox" value="documents">
                        <span class="enr-check__box"><?= enr_icon('check') ?></span>
                        <span>Scanned copies of all four requirements</span>
                    </label>
                </li>
                <li>
                    <label>
                        <input type="checkbox" value="photo">
                        <span class="enr-check__box"><?= enr_icon('check') ?></span>
                        <span>A recent 2x2 photo on a white background</span>
                    </label>
                </li>
                <li>
                    <label>
                        <input type="checkbox" value="time">
                        <span class="enr-check__box"><?= enr_icon('check') ?></span>
                        <span>About 20 uninterrupted minutes for the form</span>
                    </label>
                </li>
            </ul>
            <p class="enr-check__hint">Your checklist is saved on this device only.</p>
        </section>

        <section class="enr-card enr-side__card">
            <header class="enr-card__head">
                <h2>Documents to prepare</h2>
            </header>
            <ul class="enr-docs">
                <?php foreach ($requirements as $requirement): ?>
                    <li>
                        <?= enr_icon('file') ?>
                        <div>
                            <strong><?= enr_e($requirement['title']) ?></strong>
                            <small><?= enr_e($requirement['detail']) ?></small>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>

        <section class="enr-side__help">
            <?= enr_icon('phone') ?>
            <div>
                <strong>Admissions help desk</strong>
                <p>Mon&ndash;Fri, 8:00 AM &ndash; 5:00 PM</p>
                <a href="mailto:admissions@fullbright.edu.ph">admissions@fullbright.edu.ph</a>
            </div>
        </section>
    </aside>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
