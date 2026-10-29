<?php

require_once "../../includes/auth_check.php";

include "../../config/database.php";


/*
|--------------------------------------------------------------------------
| ADMINISTRATOR AUTHORIZATION
|--------------------------------------------------------------------------
*/

$currentUserId = $_SESSION["user_id"] ?? 0;

$adminCheck = $conn->prepare(
    "SELECT r.role_name
     FROM users u
     INNER JOIN roles r
        ON u.role_id = r.role_id
     WHERE u.user_id = ?
     LIMIT 1"
);

$adminCheck->bind_param(
    "i",
    $currentUserId
);

$adminCheck->execute();

$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows !== 1) {

    $adminCheck->close();

    http_response_code(403);

    die("Access denied.");
}

$currentRole = $adminResult->fetch_assoc();

$adminCheck->close();


if ($currentRole["role_name"] !== "Administrator") {

    http_response_code(403);

    die("Access denied. Only an Administrator can change user status.");
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: /SMS/admin/users/users.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| GET USER ID
|--------------------------------------------------------------------------
*/

$user_id = intval($_POST["user_id"] ?? 0);

if ($user_id <= 0) {

    die("Invalid user.");
}


/*
|--------------------------------------------------------------------------
| PREVENT ADMIN FROM CHANGING THEIR OWN STATUS
|--------------------------------------------------------------------------
*/

if ($user_id === $currentUserId) {

    die("You cannot change the status of your own Administrator account.");
}


/*
|--------------------------------------------------------------------------
| GET CURRENT USER STATUS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT status
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

if (!$stmt) {

    die("Unable to prepare user status check.");
}


$stmt->bind_param(
    "i",
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    $stmt->close();

    die("User not found.");
}


$user = $result->fetch_assoc();

$stmt->close();


/*
|--------------------------------------------------------------------------
| SWITCH STATUS
|--------------------------------------------------------------------------
*/

if ($user["status"] === "Active") {

    $new_status = "Inactive";

} elseif ($user["status"] === "Inactive") {

    $new_status = "Active";

} else {

    die("Invalid current user status.");
}


/*
|--------------------------------------------------------------------------
| UPDATE USER STATUS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "UPDATE users
     SET status = ?
     WHERE user_id = ?"
);

if (!$stmt) {

    die("Unable to prepare user status update.");
}


$stmt->bind_param(
    "si",
    $new_status,
    $user_id
);


/*
|--------------------------------------------------------------------------
| EXECUTE UPDATE
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    $stmt->close();

    header("Location: /SMS/admin/users/users.php");

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE FAILED
|--------------------------------------------------------------------------
*/

$error = $stmt->error;

$stmt->close();

die("Unable to update user status: " . $error);

?>