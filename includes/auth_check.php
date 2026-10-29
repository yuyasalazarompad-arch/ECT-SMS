<?php

/*
|--------------------------------------------------------------------------
| SMS Authentication Check
|--------------------------------------------------------------------------
| This file protects pages that require a logged-in user.
|
| Usage:
| require_once "../../includes/auth_check.php";
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| Check if the user is logged in
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION["user_id"])) {

    header("Location: /SMS/auth/login.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Basic session information
|--------------------------------------------------------------------------
| These values are available to protected pages.
|--------------------------------------------------------------------------
*/

$currentUserId = $_SESSION["user_id"];
$currentUserName = $_SESSION["full_name"] ?? "User";
$currentUsername = $_SESSION["username"] ?? "";
$currentRoleId = $_SESSION["role_id"] ?? null;
$currentRoleName = $_SESSION["role_name"] ?? "";


/*
|--------------------------------------------------------------------------
| Optional account status check
|--------------------------------------------------------------------------
| This prevents an account that has been deactivated after login
| from continuing to use the system.
|--------------------------------------------------------------------------
*/

require_once __DIR__ . "/../config/database.php";

$statusStmt = $conn->prepare("
    SELECT status
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$statusStmt->bind_param("i", $currentUserId);
$statusStmt->execute();

$statusResult = $statusStmt->get_result();

if ($statusResult->num_rows !== 1) {

    session_unset();
    session_destroy();

    header("Location: /SMS/auth/login.php");
    exit;
}

$currentUser = $statusResult->fetch_assoc();

$statusStmt->close();


if ($currentUser["status"] !== "Active") {

    session_unset();
    session_destroy();

    header("Location: /SMS/auth/login.php");
    exit;
}
?>