<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

$enrollmentPage = 'enroll';
$enrollmentTitle = 'Student Enrollment Form';
$enrollmentSubtitle = 'Step 2 of 4 &middot; Personal information';

$steps = enr_steps();
$programs = $enrollmentRepository->programs();

$values = [
    'first_name' => '',
    'middle_name' => '',
    'last_name' => '',
    'suffix' => '',
    'email' => '',
    'birthday' => '',
    'contact_no' => '',
    'emergency_contact_name' => '',
    'emergency_contact_no' => '',
    'marital_status' => 'Single',
    'permanent_address' => '',
    'current_address' => '',
    'same_address' => '',
    'program' => '',
];

$errors = [];
$completed = isset($_GET['done']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;

    foreach ($values as $field => $default) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (!auth_verify_csrf(is_string($token) ? $token : null)) {
        $errors['form'] = 'Your session expired. Refresh the page and submit the form again.';
    }

    if ($values['first_name'] === '') {
        $errors['first_name'] = 'Enter your first name.';
    }
    if ($values['last_name'] === '') {
        $errors['last_name'] = 'Enter your last name.';
    }
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif ($enrollmentRepository->emailExists($values['email'])) {
        $errors['email'] = 'An application already exists for this email. Sign in to the student portal instead.';
    }

    $birthday = DateTimeImmutable::createFromFormat('Y-m-d', $values['birthday']);
    if (!$birthday instanceof DateTimeImmutable || $birthday->format('Y-m-d') !== $values['birthday']) {
        $errors['birthday'] = 'Enter your birthday.';
    } elseif ($birthday > $enrollmentNow) {
        $errors['birthday'] = 'Your birthday cannot be in the future.';
    }

    if (!preg_match('/^[0-9+()\s-]{7,30}$/', $values['contact_no'])) {
        $errors['contact_no'] = 'Enter a reachable contact number.';
    }
    if (!preg_match('/^[0-9+()\s-]{7,30}$/', $values['emergency_contact_no'])) {
        $errors['emergency_contact_no'] = 'Enter an emergency contact number.';
    }
    if (!isset($programs[$values['program']])) {
        $errors['program'] = 'Choose a program.';
    }

    if ($values['same_address'] === '1' && $values['permanent_address'] !== '') {
        $values['current_address'] = $values['permanent_address'];
    }

    if ($errors === []) {
        try {
            $studentId = $enrollmentRepository->createApplication($values);
            enr_receipt_set($studentId);
            enr_flash_set('success', $studentId);
            header('Location: application.php?done=1');
            exit;
        } catch (Throwable $exception) {
            $errors['form'] = 'Your application could not be saved. Please try again or email the admissions office.';
        }
    }
}

$studentNumber = $completed ? enr_receipt() : '';

require __DIR__ . '/partials/header.php';
?>
<?php if ($studentNumber !== ''): ?>
    <section class="enr-success">
        <div class="enr-success__icon"><?= enr_icon('check-circle') ?></div>
        <p class="enr-eyebrow">Application received</p>
        <h1>You are on the list.</h1>
        <p class="enr-success__lede">
            We saved your personal information under student number
            <strong class="enr-success__id"><?= enr_e($studentNumber) ?></strong>.
            Keep this number &mdash; you will use it for your documents, entrance exam, and student portal.
        </p>

        <div class="enr-success__steps">
            <article>
                <span>1</span>
                <h2>Verify your email</h2>
                <p>Open the verification link sent to your email address within 24 hours.</p>
            </article>
            <article>
                <span>2</span>
                <h2>Submit requirements</h2>
                <p>The registrar emails your secure upload link for your PSA, good moral, Form 138, and 2x2 photo.</p>
            </article>
            <article>
                <span>3</span>
                <h2>Take the entrance exam</h2>
                <p>Once your documents are checked, your exam code and QR are released automatically.</p>
            </article>
        </div>

        <div class="enr-actions enr-actions--center">
            <a class="enr-btn enr-btn--ghost" href="./enroll.php">
                <?= enr_icon('arrow-left') ?>
                <span>Review instructions</span>
            </a>
            <a class="enr-btn enr-btn--primary" href="<?= enr_e(enr_login_url()) ?>">
                <span>Go to Student Login</span>
                <?= enr_icon('login') ?>
            </a>
        </div>
    </section>
<?php else: ?>
    <section class="enr-page-head enr-page-head--compact">
        <div>
            <a class="enr-back" href="./enroll.php">
                <?= enr_icon('arrow-left') ?>
                <span>Back to step 1</span>
            </a>
            <p class="enr-eyebrow">Student Enrollment Form</p>
            <h1>Personal<br><em>information</em></h1>
            <p class="enr-page-head__lede">
                Tell us who you are and how to reach you. Use the same name and details that appear
                on your school records so the registrar can match your documents.
            </p>
        </div>
        <aside class="enr-page-head__badge" aria-label="Application progress">
            <small>Step</small>
            <strong>2<span>/4</span></strong>
            <p><?= enr_e($steps[1]['title']) ?></p>
        </aside>
    </section>

    <ol class="enr-stepper" aria-label="Enrollment steps">
        <?php foreach ($steps as $index => $step): ?>
            <?php
            $state = match (true) {
                $index === 0 => 'is-complete',
                $index === 1 => 'is-current',
                default => 'is-upcoming',
            };
            ?>
            <li class="enr-stepper__item <?= $state ?>"<?= $index === 1 ? ' aria-current="step"' : '' ?>>
                <span class="enr-stepper__dot"><?= $index === 0 ? enr_icon('check') : $index + 1 ?></span>
                <div>
                    <strong><?= enr_e($step['title']) ?></strong>
                    <small><?= enr_e($step['detail']) ?></small>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>

    <div class="enr-layout">
        <section class="enr-card enr-card--striped">
            <header class="enr-card__head">
                <div>
                    <p class="enr-eyebrow">Step 2 of 4</p>
                    <h2>Applicant details</h2>
                </div>
                <span class="enr-chip">All fields marked * are required</span>
            </header>

            <?php if (isset($errors['form'])): ?>
                <div class="enr-notice enr-notice--danger" role="alert">
                    <?= enr_icon('alert') ?>
                    <div><strong>Application not saved.</strong><p><?= enr_e($errors['form']) ?></p></div>
                </div>
            <?php endif; ?>

            <form class="enr-form" method="post" action="application.php" novalidate>
                <input type="hidden" name="csrf_token" value="<?= enr_e($enrollmentCsrf) ?>">

                <fieldset class="enr-fieldset">
                    <legend>Name</legend>
                    <div class="enr-grid enr-grid--3">
                        <label class="enr-field">
                            <span>First name <em>*</em></span>
                            <input type="text" name="first_name" value="<?= enr_e($values['first_name']) ?>" autocomplete="given-name"<?= isset($errors['first_name']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['first_name'])): ?><small class="enr-field__error"><?= enr_e($errors['first_name']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Middle name</span>
                            <input type="text" name="middle_name" value="<?= enr_e($values['middle_name']) ?>" autocomplete="additional-name">
                        </label>
                        <label class="enr-field">
                            <span>Last name <em>*</em></span>
                            <input type="text" name="last_name" value="<?= enr_e($values['last_name']) ?>" autocomplete="family-name"<?= isset($errors['last_name']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['last_name'])): ?><small class="enr-field__error"><?= enr_e($errors['last_name']) ?></small><?php endif; ?>
                        </label>
                    </div>
                    <div class="enr-grid enr-grid--3">
                        <label class="enr-field">
                            <span>Suffix</span>
                            <select name="suffix">
                                <?php foreach (['' => 'None', 'Jr.' => 'Jr.', 'Sr.' => 'Sr.', 'II' => 'II', 'III' => 'III', 'IV' => 'IV'] as $value => $label): ?>
                                    <option value="<?= enr_e($value) ?>"<?= $values['suffix'] === $value ? ' selected' : '' ?>><?= enr_e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="enr-field">
                            <span>Birthday <em>*</em></span>
                            <input type="date" name="birthday" value="<?= enr_e($values['birthday']) ?>" max="<?= enr_e($enrollmentNow->format('Y-m-d')) ?>"<?= isset($errors['birthday']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['birthday'])): ?><small class="enr-field__error"><?= enr_e($errors['birthday']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Civil status <em>*</em></span>
                            <select name="marital_status">
                                <?php foreach (['Single', 'Married'] as $status): ?>
                                    <option value="<?= enr_e($status) ?>"<?= $values['marital_status'] === $status ? ' selected' : '' ?>><?= enr_e($status) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="enr-fieldset">
                    <legend>Contact</legend>
                    <div class="enr-grid enr-grid--3">
                        <label class="enr-field">
                            <span>Email address <em>*</em></span>
                            <input type="email" name="email" value="<?= enr_e($values['email']) ?>" autocomplete="email" placeholder="juan.delacruz@email.com"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['email'])): ?><small class="enr-field__error"><?= enr_e($errors['email']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Contact number <em>*</em></span>
                            <input type="tel" name="contact_no" value="<?= enr_e($values['contact_no']) ?>" autocomplete="tel" placeholder="0917 123 4567"<?= isset($errors['contact_no']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['contact_no'])): ?><small class="enr-field__error"><?= enr_e($errors['contact_no']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Emergency contact name</span>
                            <input type="text" name="emergency_contact_name" value="<?= enr_e($values['emergency_contact_name']) ?>" placeholder="Maria Dela Cruz">
                        </label>
                    </div>
                    <div class="enr-grid enr-grid--2">
                        <label class="enr-field">
                            <span>Emergency contact number <em>*</em></span>
                            <input type="tel" name="emergency_contact_no" value="<?= enr_e($values['emergency_contact_no']) ?>" placeholder="0917 765 4321"<?= isset($errors['emergency_contact_no']) ? ' aria-invalid="true"' : '' ?>>
                            <?php if (isset($errors['emergency_contact_no'])): ?><small class="enr-field__error"><?= enr_e($errors['emergency_contact_no']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Permanent address</span>
                            <input type="text" name="permanent_address" value="<?= enr_e($values['permanent_address']) ?>" placeholder="No., Street, Barangay, City, Province" autocomplete="street-address">
                        </label>
                    </div>
                    <div class="enr-grid enr-grid--2">
                        <label class="enr-field">
                            <span>Current address</span>
                            <input type="text" name="current_address" value="<?= enr_e($values['current_address']) ?>" placeholder="No., Street, Barangay, City, Province">
                        </label>
                        <label class="enr-check enr-check--inline">
                            <input type="checkbox" name="same_address" value="1"<?= $values['same_address'] === '1' ? ' checked' : '' ?>>
                            <span class="enr-check__box"><?= enr_icon('check') ?></span>
                            <span>Current address is the same as my permanent address.</span>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="enr-fieldset">
                    <legend>Program</legend>
                    <div class="enr-grid enr-grid--2">
                        <label class="enr-field">
                            <span>Course / program <em>*</em></span>
                            <select name="program"<?= isset($errors['program']) ? ' aria-invalid="true"' : '' ?>>
                                <option value="" disabled<?= $values['program'] === '' ? ' selected' : '' ?>>Select a program</option>
                                <?php foreach ($programs as $code => $program): ?>
                                    <option value="<?= enr_e($code) ?>"<?= $values['program'] === $code ? ' selected' : '' ?>><?= enr_e($code . ' — ' . $program['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['program'])): ?><small class="enr-field__error"><?= enr_e($errors['program']) ?></small><?php endif; ?>
                        </label>
                        <label class="enr-field">
                            <span>Department</span>
                            <input type="text" value="<?= enr_e(EnrollmentRepository::DEPARTMENT) ?>" readonly>
                        </label>
                    </div>
                </fieldset>

                <div class="enr-form__foot">
                    <p>
                        <?= enr_icon('lock') ?>
                        <span>Your details are used only for admission and student records.</span>
                    </p>
                    <div class="enr-actions">
                        <a class="enr-btn enr-btn--ghost" href="./enroll.php">
                            <?= enr_icon('arrow-left') ?>
                            <span>Back</span>
                        </a>
                        <button class="enr-btn enr-btn--primary" type="submit">
                            <span>Submit Application</span>
                            <?= enr_icon('arrow-right') ?>
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <aside class="enr-side">
            <section class="enr-card enr-side__card">
                <header class="enr-card__head">
                    <h2>What happens next</h2>
                </header>
                <ol class="enr-next">
                    <li>
                        <span>1</span>
                        <div>
                            <strong>Email verification</strong>
                            <p>A 24-hour link confirms your address before documents are accepted.</p>
                        </div>
                    </li>
                    <li>
                        <span>2</span>
                        <div>
                            <strong>Document upload</strong>
                            <p>The registrar sends a secure link for your four requirements.</p>
                        </div>
                    </li>
                    <li>
                        <span>3</span>
                        <div>
                            <strong>Entrance examination</strong>
                            <p>Your exam code and QR arrive once documents are checked.</p>
                        </div>
                    </li>
                </ol>
            </section>

            <section class="enr-side__help">
                <?= enr_icon('phone') ?>
                <div>
                    <strong>Need a hand?</strong>
                    <p>Call the admissions desk, Mon&ndash;Fri, 8:00 AM &ndash; 5:00 PM.</p>
                    <a href="mailto:admissions@fullbright.edu.ph">admissions@fullbright.edu.ph</a>
                </div>
            </section>
        </aside>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/partials/footer.php'; ?>
