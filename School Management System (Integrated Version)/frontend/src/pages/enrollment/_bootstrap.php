<?php
declare(strict_types=1);

/**
 * Shared bootstrap for the public Enrollment & Student Portal.
 *
 * These pages are intentionally public: applicants do not need an account to
 * read the instructions or file an application. Signed-in students still get
 * a shortcut back to the campus portal.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../../backend/enrollment/EnrollmentRepository.php';
require_once __DIR__ . '/partials/icons.php';

$enrollmentRepository = new EnrollmentRepository(db());
$enrollmentNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$enrollmentUser = auth_user();
$enrollmentSignedIn = $enrollmentUser !== null;
$enrollmentIsStudent = $enrollmentSignedIn && auth_role_key($enrollmentUser['role']) === 'student';
$enrollmentCsrf = auth_csrf_token();
$enrollmentYear = $enrollmentNow->format('Y');
$enrollmentSchoolYear = $enrollmentNow->format('Y') . '–' . ((int) $enrollmentNow->format('Y') + 1);

function enr_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function enr_login_url(): string
{
    return auth_frontend_url('login.php');
}

function enr_dashboard_url(): string
{
    return auth_frontend_url('dashboard.php');
}

function enr_initials(string $name): string
{
    $words = preg_split('/[\s,]+/', trim($name)) ?: [];
    $words = array_values(array_filter($words, static fn (string $word): bool => $word !== ''));

    if ($words === []) {
        return '?';
    }

    $initials = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }

    return $initials !== '' ? $initials : '?';
}

/**
 * @return array{type: string, message: string}|null
 */
function enr_flash(): ?array
{
    auth_boot();
    $flash = $_SESSION['enrollment_flash'] ?? null;
    unset($_SESSION['enrollment_flash']);

    if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
        return null;
    }

    return ['type' => (string) $flash['type'], 'message' => (string) $flash['message']];
}

function enr_flash_set(string $type, string $message): void
{
    auth_boot();
    $_SESSION['enrollment_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Student number of the most recently filed application in this session.
 */
function enr_receipt(): string
{
    auth_boot();

    return (string) ($_SESSION['enrollment_receipt'] ?? '');
}

function enr_receipt_set(string $studentNumber): void
{
    auth_boot();
    $_SESSION['enrollment_receipt'] = $studentNumber;
}

/**
 * Requirements shown on the home page and step 1.
 *
 * @return list<array{title: string, detail: string, note: string}>
 */
function enr_requirements(): array
{
    return [
        [
            'title' => 'PSA Birth Certificate',
            'detail' => 'Original or certified true copy',
            'note' => 'Clear scan or photo, PDF or image',
        ],
        [
            'title' => 'Certificate of Good Moral Character',
            'detail' => 'Issued by your senior high school',
            'note' => 'Signed by the school principal or guidance office',
        ],
        [
            'title' => 'Form 138 (Report Card)',
            'detail' => 'Original senior high school record',
            'note' => 'Must show final grades for all subjects',
        ],
        [
            'title' => '2x2 ID Pictures',
            'detail' => 'Three (3) recent coloured copies',
            'note' => 'White background, taken within the last six months',
        ],
    ];
}

/**
 * The four enrollment stages used by the stepper.
 *
 * @return list<array{title: string, detail: string}>
 */
function enr_steps(): array
{
    return [
        ['title' => 'Instructions', 'detail' => 'Read the process and prepare your documents.'],
        ['title' => 'Personal Information', 'detail' => 'Tell us who you are and where to reach you.'],
        ['title' => 'Requirements', 'detail' => 'Upload your documents and 2x2 photo.'],
        ['title' => 'Entrance Examination', 'detail' => 'Take the online exam and receive your score.'],
    ];
}
