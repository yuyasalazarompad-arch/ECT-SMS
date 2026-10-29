<?php
declare(strict_types=1);

/**
 * Campus authentication and role-based access control.
 *
 * Accounts live in school_management.campus_users. A user signs in once and
 * the role stored on that account decides which workspaces are available.
 */

require_once __DIR__ . '/../../backend/config/database.php';

/**
 * Start the campus session once per request.
 */
function auth_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('FBCSMS_SESSION');
    @session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    @session_start();
}

/**
 * Absolute URL to a file inside frontend/, so guards also work for module
 * pages that live in subdirectories.
 */
function auth_frontend_url(string $path = ''): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $marker = '/frontend/';
    $position = strpos($script, $marker);
    $base = $position !== false ? substr($script, 0, $position + strlen($marker)) : '/';

    return $base . ltrim($path, '/');
}

/**
 * Role catalogue. "systems" is the list of workspaces that role may open.
 */
function auth_roles(): array
{
    return [
        'super_admin' => [
            'label' => 'Super Administrator',
            'short' => 'Super Admin',
            'summary' => 'Every campus system and record.',
            'accent' => 'navy',
            'systems' => ['academic', 'attendance', 'assessment', 'finance', 'enrollment'],
        ],
        'admin' => [
            'label' => 'Campus Administrator',
            'short' => 'Administrator',
            'summary' => 'All workspaces for day-to-day operations.',
            'accent' => 'blue',
            'systems' => ['academic', 'attendance', 'assessment', 'finance', 'enrollment'],
        ],
        'academic_admin' => [
            'label' => 'Academic Administrator',
            'short' => 'Academic Admin',
            'summary' => 'Curriculum, classes, and academic records.',
            'accent' => 'blue',
            'systems' => ['academic'],
        ],
        'attendance_admin' => [
            'label' => 'Attendance Administrator',
            'short' => 'Attendance Admin',
            'summary' => 'Sessions, rosters, rooms, and attendance.',
            'accent' => 'green',
            'systems' => ['attendance'],
        ],
        'assessment_admin' => [
            'label' => 'Assessment Administrator',
            'short' => 'Assessment Admin',
            'summary' => 'Assessments, scores, and report cards.',
            'accent' => 'red',
            'systems' => ['assessment'],
        ],
        'finance_admin' => [
            'label' => 'Finance Administrator',
            'short' => 'Finance Admin',
            'summary' => 'Fees, invoices, payments, and reports.',
            'accent' => 'gold',
            'systems' => ['finance'],
        ],
        'enrollment_admin' => [
            'label' => 'Enrollment Administrator',
            'short' => 'Enrollment Admin',
            'summary' => 'Admissions, entrance exams, and student records.',
            'accent' => 'navy',
            'systems' => ['enrollment'],
        ],
        'teacher' => [
            'label' => 'Teacher',
            'short' => 'Teacher',
            'summary' => 'Classes, attendance, and gradebooks.',
            'accent' => 'gold',
            'systems' => ['attendance', 'assessment'],
        ],
        'student' => [
            'label' => 'Student',
            'short' => 'Student',
            'summary' => 'Results, records, and enrollment.',
            'accent' => 'green',
            'systems' => ['assessment', 'enrollment'],
        ],
    ];
}

/**
 * Workspace catalogue shared by the login access map and the dashboard.
 */
function auth_systems(): array
{
    return [
        'academic' => [
            'label' => 'Academic Management',
            'context' => 'Curriculum & classes',
            'description' => 'Programs, subjects, sections, and class schedules.',
            'accent' => 'blue',
            'href' => 'src/pages/academics/index.php',
        ],
        'attendance' => [
            'label' => 'Attendance & Scheduling',
            'context' => 'Daily operations',
            'description' => 'Sessions, rosters, rooms, and attendance records.',
            'accent' => 'green',
            'href' => 'src/pages/attendance/attendance.php',
        ],
        'assessment' => [
            'label' => 'Assessment & Grades',
            'context' => 'Teaching & records',
            'description' => 'Assessments, scores, and report cards.',
            'accent' => 'red',
            'href' => 'src/pages/grading/index.php',
        ],
        'finance' => [
            'label' => 'Finance & Reporting',
            'context' => 'Fees & payments',
            'description' => 'Fee structures, invoices, payments, and reports.',
            'accent' => 'gold',
            'href' => 'src/pages/finance/',
        ],
        'enrollment' => [
            'label' => 'Enrollment & Student Portal',
            'context' => 'Admissions & exams',
            'description' => 'Applications, entrance exams, and student records.',
            'accent' => 'navy',
            'href' => 'src/pages/students/index.php',
        ],
    ];
}

/**
 * Convert a database role label into a role key.
 */
function auth_role_key(?string $dbRole): string
{
    $normalized = preg_replace('/\s+/', '_', strtolower(trim((string) $dbRole))) ?? '';
    $aliases = [
        'super_administrator' => 'super_admin',
        'administrator' => 'admin',
        'faculty' => 'teacher',
        'student_portal' => 'student',
    ];
    $normalized = $aliases[$normalized] ?? $normalized;

    return isset(auth_roles()[$normalized]) ? $normalized : 'student';
}

/**
 * Role definition for a key, with a safe student fallback.
 */
function auth_role(?string $key): array
{
    $roles = auth_roles();
    $key = isset($roles[(string) $key]) ? (string) $key : 'student';

    return ['key' => $key] + $roles[$key];
}

/**
 * Workspaces assigned to a role, in catalogue order.
 */
function auth_systems_for(?string $roleKey): array
{
    $role = auth_role($roleKey);
    $systems = auth_systems();
    $allowed = [];

    foreach ($role['systems'] as $key) {
        if (isset($systems[$key])) {
            $allowed[$key] = $systems[$key];
        }
    }

    return $allowed;
}

/**
 * CSRF token for the login and logout forms.
 */
function auth_csrf_token(): string
{
    auth_boot();

    if (empty($_SESSION['auth_csrf'])) {
        $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['auth_csrf'];
}

function auth_verify_csrf(?string $token): bool
{
    auth_boot();

    return is_string($token)
        && $token !== ''
        && isset($_SESSION['auth_csrf'])
        && hash_equals((string) $_SESSION['auth_csrf'], $token);
}

/**
 * Verify a username/email + password pair against the campus directory.
 * Returns the user data on success, or null for any invalid/inactive account.
 */
function auth_attempt(string $identifier, string $password): ?array
{
    $identifier = trim($identifier);

    if ($identifier === '' || $password === '') {
        return null;
    }

    $pdo = db();
    $statement = $pdo->prepare(
        'SELECT user_id, username, email, password_hash, full_name, role, status
           FROM campus_users
          WHERE username = :username OR email = :email
          LIMIT 1'
    );
    $statement->execute([
        ':username' => $identifier,
        ':email' => $identifier,
    ]);
    $row = $statement->fetch();

    if (
        !$row
        || $row['status'] !== 'Active'
        || !password_verify($password, (string) $row['password_hash'])
    ) {
        return null;
    }

    // Upgrade old hashes transparently on the next successful sign-in.
    if (password_needs_rehash((string) $row['password_hash'], PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE campus_users SET password_hash = :hash WHERE user_id = :id');
        $update->execute([':hash' => $newHash, ':id' => (int) $row['user_id']]);
    }

    $roleKey = auth_role_key((string) $row['role']);

    return [
        'id' => (int) $row['user_id'],
        'username' => (string) $row['username'],
        'email' => (string) ($row['email'] ?? ''),
        'name' => trim((string) ($row['full_name'] ?? '')) ?: (string) $row['username'],
        'db_role' => (string) $row['role'],
        'role' => $roleKey,
    ];
}

/**
 * Publish the authenticated user into the session.
 */
function auth_login_session(array $user): void
{
    auth_boot();
    session_regenerate_id(true);

    $role = auth_role((string) ($user['role'] ?? 'student'));
    $user['role'] = $role['key'];
    $user['role_label'] = $role['label'];

    $_SESSION['auth_user'] = $user;
    $_SESSION['auth_login_at'] = time();
    $_SESSION['auth_last_seen'] = time();

    // Keys used by the shared module bar and future module pages.
    $_SESSION['user_role'] = $role['key'];
    $_SESSION['display_name'] = (string) $user['name'];
    $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));

    auth_clear_attempts();

    try {
        $update = db()->prepare('UPDATE campus_users SET last_login_at = NOW() WHERE user_id = :id');
        $update->execute([':id' => (int) $user['id']]);
    } catch (Throwable $e) {
        // A directory write failure should not block an otherwise valid login.
    }
}

/**
 * Current signed-in user, or null. Sessions expire after two idle hours.
 */
function auth_user(): ?array
{
    auth_boot();

    $user = $_SESSION['auth_user'] ?? null;
    if (!is_array($user) || empty($user['id'])) {
        return null;
    }

    $lastSeen = (int) ($_SESSION['auth_last_seen'] ?? 0);
    if ($lastSeen > 0 && time() - $lastSeen > 7200) {
        auth_logout();
        return null;
    }

    $_SESSION['auth_last_seen'] = time();
    $role = auth_role((string) ($user['role'] ?? 'student'));
    $user['role'] = $role['key'];
    $user['role_label'] = $role['label'];

    return $user;
}

function auth_check(): bool
{
    return auth_user() !== null;
}

/**
 * Page guard: send guests to the login screen.
 */
function auth_require_login(): void
{
    if (auth_check()) {
        return;
    }

    header('Location: ' . auth_frontend_url('login.php'));
    exit;
}

/**
 * Module guard for future workspace pages.
 */
function auth_require_system(string $system): void
{
    auth_require_login();
    $user = auth_user();

    if (!$user || !in_array($system, auth_role($user['role'])['systems'], true)) {
        $dashboardUrl = htmlspecialchars(auth_frontend_url('dashboard.php'), ENT_QUOTES, 'UTF-8');
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Access denied</title></head>'
            . '<body style="margin:0;display:grid;min-height:100vh;place-items:center;background:#f3eee4;color:#13233b;'
            . 'font:16px/1.6 system-ui,sans-serif"><main style="max-width:32rem;padding:2rem">'
            . '<p style="color:#c8102e;font-weight:700;letter-spacing:.16em;text-transform:uppercase">403 &middot; Access denied</p>'
            . '<h1 style="font-family:Georgia,serif;font-size:2rem">This workspace is outside your role.</h1>'
            . '<p>Your account does not have permission to open this module. Return to your dashboard or contact the campus administrator.</p>'
            . '<p><a href="' . $dashboardUrl . '" style="color:#075eb4">Back to my dashboard</a></p>'
            . '</main></body></html>';
        exit;
    }
}

/**
 * Destroy the campus session.
 */
function auth_logout(): void
{
    auth_boot();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => (bool) $params['secure'],
                'httponly' => (bool) $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/* -------------------------------------------------------------------------
 * Login throttling (five failed attempts per ten minutes, per session)
 * ---------------------------------------------------------------------- */

function auth_throttle_remaining(): int
{
    auth_boot();

    $now = time();
    $attempts = $_SESSION['auth_attempts'] ?? [];
    $attempts = is_array($attempts) ? $attempts : [];
    $attempts = array_values(array_filter(
        $attempts,
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 600
    ));
    $_SESSION['auth_attempts'] = $attempts;

    return max(0, 5 - count($attempts));
}

function auth_record_failure(): void
{
    auth_boot();
    $_SESSION['auth_attempts'] = array_merge(
        is_array($_SESSION['auth_attempts'] ?? null) ? $_SESSION['auth_attempts'] : [],
        [time()]
    );
}

function auth_clear_attempts(): void
{
    auth_boot();
    unset($_SESSION['auth_attempts']);
}

/* -------------------------------------------------------------------------
 * Demo helpers
 * ---------------------------------------------------------------------- */

function auth_demo_mode(): bool
{
    $flag = getenv('APP_DEMO_MODE');
    if ($flag !== false && trim($flag) !== '') {
        return filter_var($flag, FILTER_VALIDATE_BOOLEAN);
    }

    // Only the loopback client sees demo credentials. The request Host header
    // is not trusted for this decision.
    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    return in_array($remote, ['127.0.0.1', '::1'], true);
}

/**
 * Demo credentials shown only on localhost (or when APP_DEMO_MODE=1).
 */
function auth_demo_accounts(): array
{
    if (!auth_demo_mode()) {
        return [];
    }

    return [
        ['role' => 'super_admin', 'username' => 'fbcadmin', 'password' => 'fbcadmin123'],
        ['role' => 'admin', 'username' => 'admin', 'password' => 'admin123'],
        ['role' => 'academic_admin', 'username' => 'academic.admin', 'password' => 'academic123'],
        ['role' => 'attendance_admin', 'username' => 'attendance.admin', 'password' => 'attendance123'],
        ['role' => 'assessment_admin', 'username' => 'assessment.admin', 'password' => 'assessment123'],
        ['role' => 'finance_admin', 'username' => 'finance.admin', 'password' => 'finance123'],
        ['role' => 'enrollment_admin', 'username' => 'enrollment.admin', 'password' => 'enrollment123'],
        ['role' => 'teacher', 'username' => 'teacher', 'password' => 'teacher123'],
        ['role' => 'student', 'username' => 'student', 'password' => 'student123'],
    ];
}

function auth_directory_online(): bool
{
    try {
        db();
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function auth_initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $initials = '';

    foreach (array_slice($words, 0, 2) as $word) {
        if ($word !== '') {
            $initials .= strtoupper(substr($word, 0, 1));
        }
    }

    return $initials !== '' ? $initials : 'FC';
}

auth_boot();
