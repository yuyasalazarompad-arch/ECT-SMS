<?php
declare(strict_types=1);

/**
 * Shared bootstrap for every Finance & Reporting page.
 *
 * Keeps authentication, database access, helpers, and the signed-in account
 * in one place so page files only contain their own content.
 */

require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../../backend/finance/FinanceRepository.php';
require_once __DIR__ . '/partials/icons.php';

// Finance is a guarded workspace: guests go to login, other roles get a 403.
auth_require_system('finance');

$financeUser = auth_user();
$financeRole = auth_role($financeUser['role']);
$financeRepository = new FinanceRepository(db());
$financeSettings = $financeRepository->settings();
$financeNow = new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
$financeInitials = auth_initials((string) $financeUser['name']);
$financeCsrf = auth_csrf_token();

function fin_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fin_money(float|int|string|null $amount): string
{
    return '₱' . number_format((float) $amount, 2, '.', ',');
}

function fin_number(float|int|string|null $value, int $decimals = 0): string
{
    return number_format((float) $value, $decimals, '.', ',');
}

function fin_date(?string $date, string $fallback = '—'): string
{
    $date = trim((string) $date);
    if ($date === '') {
        return $fallback;
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $fallback : date('M j, Y', $timestamp);
}

function fin_status_class(string $status): string
{
    $status = strtolower(trim($status));

    return match ($status) {
        'paid' => 'is-paid',
        'partial' => 'is-partial',
        'overdue' => 'is-overdue',
        default => 'is-unpaid',
    };
}

function fin_year_label(int $year): string
{
    return ['1st', '2nd', '3rd', '4th'][$year - 1] ?? '1st';
}

/**
 * Queue a one-time message for the next finance request.
 */
function fin_set_flash(string $type, string $message): void
{
    auth_boot();
    $_SESSION['finance_flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Read and clear the queued finance message.
 *
 * @return array{type: string, message: string}|null
 */
function fin_flash(): ?array
{
    auth_boot();
    $flash = $_SESSION['finance_flash'] ?? null;
    unset($_SESSION['finance_flash']);

    if (!is_array($flash) || !isset($flash['type'], $flash['message'])) {
        return null;
    }

    return ['type' => (string) $flash['type'], 'message' => (string) $flash['message']];
}
