<?php
declare(strict_types=1);

/**
 * Shared bootstrap for every Attendance & Scheduling page.
 *
 * Authentication, repository access, and small formatting helpers live here
 * so page files only contain their own content.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../../backend/attendance/AttendanceRepository.php';
require_once __DIR__ . '/partials/icons.php';

auth_require_system('attendance');

$attendanceUser = auth_user();
$attendanceRole = auth_role($attendanceUser['role']);
$attendanceRepository = new AttendanceRepository(db());
$attendanceNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$attendanceInitials = auth_initials((string) $attendanceUser['name']);
$attendanceCsrf = auth_csrf_token();
$attendanceToday = $attendanceNow->format('Y-m-d');

function attn_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function attn_time(?string $time): string
{
    $time = trim((string) $time);
    if ($time === '') {
        return '—';
    }

    $timestamp = strtotime($time);

    return $timestamp === false ? $time : date('g:i A', $timestamp);
}

function attn_date(?string $date, string $fallback = '—'): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return $fallback;
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $fallback : date('M j, Y', $timestamp);
}

function attn_year_label(int $year): string
{
    return ['1st', '2nd', '3rd', '4th'][$year - 1] ?? '1st';
}

function attn_initials(string $name): string
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

function attn_status_key(string $status): string
{
    $status = strtolower(trim($status));

    return in_array($status, ['present', 'late', 'absent', 'excused'], true) ? $status : 'unmarked';
}

function attn_flash_set(string $type, string $message): void
{
    auth_boot();
    $_SESSION['attendance_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * @return array{type: string, message: string}|null
 */
function attn_flash(): ?array
{
    auth_boot();
    $flash = $_SESSION['attendance_flash'] ?? null;
    unset($_SESSION['attendance_flash']);

    if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
        return null;
    }

    return ['type' => (string) $flash['type'], 'message' => (string) $flash['message']];
}

/**
 * Query string for the Take Attendance filters.
 *
 * @param array<string, scalar> $overrides
 */
function attn_filter_query(array $overrides = []): string
{
    $parameters = array_merge([
        'subject' => $_GET['subject'] ?? '',
        'section' => $_GET['section'] ?? '',
        'year' => $_GET['year'] ?? '',
        'date' => $_GET['date'] ?? '',
    ], $overrides);

    $parameters = array_filter($parameters, static fn (mixed $value): bool => $value !== '' && $value !== null);

    return http_build_query($parameters);
}
