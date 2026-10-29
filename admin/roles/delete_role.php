<?php

require_once "../../includes/auth_check.php";
require_once "../../config/database.php";


/*
|--------------------------------------------------------------------------
| ADMINISTRATOR ONLY
|--------------------------------------------------------------------------
*/

if (($currentRoleName ?? "") !== "Administrator") {

    http_response_code(403);

    die("Access denied. Administrator privileges are required.");
}


/*
|--------------------------------------------------------------------------
| GET ROLE ID
|--------------------------------------------------------------------------
*/

$role_id = filter_input(
    INPUT_GET,
    "id",
    FILTER_VALIDATE_INT
);

if (!$role_id || $role_id < 1) {

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| PROTECT BUILT-IN SYSTEM ROLES
|--------------------------------------------------------------------------
|
| Role IDs 1–4 are the original system roles:
|
| 1 - Administrator
| 2 - Registrar
| 3 - Faculty
| 4 - Student
|
| These roles must never be deleted.
|
*/

if ($role_id <= 4) {

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK IF ROLE EXISTS
|--------------------------------------------------------------------------
*/

$checkStmt = $conn->prepare("
    SELECT role_id
    FROM roles
    WHERE role_id = ?
    LIMIT 1
");

$checkStmt->bind_param("i", $role_id);
$checkStmt->execute();

$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows !== 1) {

    $checkStmt->close();

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}

$checkStmt->close();


/*
|--------------------------------------------------------------------------
| CHECK IF USERS ARE USING THIS ROLE
|--------------------------------------------------------------------------
|
| The users table has a foreign key with ON DELETE RESTRICT.
| This means a role cannot be deleted while users are assigned
| to it.
|
*/

$userStmt = $conn->prepare("
    SELECT COUNT(*) AS user_count
    FROM users
    WHERE role_id = ?
");

$userStmt->bind_param("i", $role_id);
$userStmt->execute();

$userResult = $userStmt->get_result();
$userData = $userResult->fetch_assoc();

$userStmt->close();

$userCount = (int) ($userData["user_count"] ?? 0);

if ($userCount > 0) {

    die(
        "This role cannot be deleted because users are currently assigned to it."
    );
}


/*
|--------------------------------------------------------------------------
| DELETE ROLE
|--------------------------------------------------------------------------
*/

$deleteStmt = $conn->prepare("
    DELETE FROM roles
    WHERE role_id = ?
");

$deleteStmt->bind_param("i", $role_id);

$deleteStmt->execute();

$deleteStmt->close();


/*
|--------------------------------------------------------------------------
| RETURN TO ROLES PAGE
|--------------------------------------------------------------------------
*/

header("Location: /SMS/admin/roles/roles.php");
exit;

?>