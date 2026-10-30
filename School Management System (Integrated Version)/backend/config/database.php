<?php


if (!function_exists('env')) {
    function env(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);

        if ($value === false) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? $default : $value;
    }
}

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', (int) env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'school_management'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

/**
 * Returns a shared PDO connection to the school_management database.
 *
 * @throws PDOException when the connection cannot be established.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    return $pdo;
}

// Convenience variable for scripts that include this file directly. The
// connection error is kept so pages can show a friendly "directory offline"
// message instead of a fatal error.
$pdo = null;
try {
    $pdo = db();
} catch (PDOException $e) {
    $dbConnectionError = $e;
}
