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
| POST REQUEST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET ROLE ID
|--------------------------------------------------------------------------
*/

$role_id = filter_input(
    INPUT_POST,
    "role_id",
    FILTER_VALIDATE_INT
);

if (!$role_id || $role_id < 1) {

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| CHECK IF ROLE EXISTS
|--------------------------------------------------------------------------
*/

$roleStmt = $conn->prepare("
    SELECT role_id
    FROM roles
    WHERE role_id = ?
    LIMIT 1
");

$roleStmt->bind_param("i", $role_id);
$roleStmt->execute();

$roleResult = $roleStmt->get_result();

if ($roleResult->num_rows !== 1) {

    $roleStmt->close();

    header("Location: /SMS/admin/roles/roles.php");
    exit;
}

$roleStmt->close();


/*
|--------------------------------------------------------------------------
| GET SUBMITTED PERMISSIONS
|--------------------------------------------------------------------------
*/

$permissions = $_POST["permissions"] ?? [];

if (!is_array($permissions)) {
    $permissions = [];
}


/*
|--------------------------------------------------------------------------
| VALIDATE PERMISSION IDs
|--------------------------------------------------------------------------
*/

$validPermissions = [];

$permissionStmt = $conn->prepare("
    SELECT permission_id
    FROM permissions
");

$permissionStmt->execute();

$permissionResult = $permissionStmt->get_result();

while ($permission = $permissionResult->fetch_assoc()) {

    $validPermissions[] = (int) $permission["permission_id"];
}

$permissionStmt->close();


/*
|--------------------------------------------------------------------------
| CLEAN SUBMITTED PERMISSIONS
|--------------------------------------------------------------------------
*/

$cleanPermissions = [];

foreach ($permissions as $permission_id) {

    $permission_id = filter_var(
        $permission_id,
        FILTER_VALIDATE_INT
    );

    if (
        $permission_id &&
        in_array($permission_id, $validPermissions, true)
    ) {

        $cleanPermissions[] = $permission_id;
    }
}


/*
|--------------------------------------------------------------------------
| REMOVE OLD PERMISSIONS
|--------------------------------------------------------------------------
*/

$conn->begin_transaction();

try {

    $deleteStmt = $conn->prepare("
        DELETE FROM role_permissions
        WHERE role_id = ?
    ");

    $deleteStmt->bind_param("i", $role_id);
    $deleteStmt->execute();

    $deleteStmt->close();


    /*
    |--------------------------------------------------------------------------
    | ADD NEW PERMISSIONS
    |--------------------------------------------------------------------------
    */

    if (!empty($cleanPermissions)) {

        $insertStmt = $conn->prepare("
            INSERT INTO role_permissions
            (role_id, permission_id)
            VALUES (?, ?)
        ");

        foreach ($cleanPermissions as $permission_id) {

            $insertStmt->bind_param(
                "ii",
                $role_id,
                $permission_id
            );

            $insertStmt->execute();
        }

        $insertStmt->close();
    }


    /*
    |--------------------------------------------------------------------------
    | SAVE CHANGES
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | RETURN TO PERMISSIONS PAGE
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /SMS/admin/roles/permissions.php?id=" . $role_id
    );

    exit;


} catch (Exception $e) {

    $conn->rollback();

    http_response_code(500);

    die("Unable to save permissions. Please try again.");
}

?>