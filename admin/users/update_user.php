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

$adminCheck->bind_param("i", $currentUserId);
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

    die("Access denied. Only an Administrator can update system users.");
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
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$user_id = intval($_POST["user_id"] ?? 0);

$full_name = trim($_POST["full_name"] ?? "");

$username = trim($_POST["username"] ?? "");

$email = trim($_POST["email"] ?? "");

$password = $_POST["password"] ?? "";

$role_id = intval($_POST["role_id"] ?? 0);

$status = $_POST["status"] ?? "Active";


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $user_id <= 0 ||
    $full_name === "" ||
    $username === "" ||
    $email === "" ||
    $role_id <= 0
) {

    die("Please complete all required fields.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE NAME
|--------------------------------------------------------------------------
*/

if (mb_strlen($full_name) < 2) {

    die("Please enter a valid full name.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE USERNAME
|--------------------------------------------------------------------------
*/

if (!preg_match('/^[A-Za-z0-9._-]+$/', $username)) {

    die(
        "Username may only contain letters, numbers, periods, underscores, and hyphens."
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL
|--------------------------------------------------------------------------
*/

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    die("Please enter a valid email address.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE STATUS
|--------------------------------------------------------------------------
*/

if (!in_array($status, ["Active", "Inactive"], true)) {

    die("Invalid user status.");
}


/*
|--------------------------------------------------------------------------
| CHECK TARGET USER
|--------------------------------------------------------------------------
*/

$userCheck = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

$userCheck->bind_param(
    "i",
    $user_id
);

$userCheck->execute();

$userResult = $userCheck->get_result();

if ($userResult->num_rows !== 1) {

    $userCheck->close();

    die("The user you are trying to update does not exist.");
}

$userCheck->close();


/*
|--------------------------------------------------------------------------
| CHECK USERNAME
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE username = ?
     AND user_id != ?
     LIMIT 1"
);

$stmt->bind_param(
    "si",
    $username,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    die("This username already exists.");
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| CHECK EMAIL
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE email = ?
     AND user_id != ?
     LIMIT 1"
);

$stmt->bind_param(
    "si",
    $email,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    die("This email is already registered.");
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| CHECK ROLE
|--------------------------------------------------------------------------
|
| The selected role must exist and must be Active.
|
*/

$roleCheck = $conn->prepare(
    "SELECT role_id
     FROM roles
     WHERE role_id = ?
     AND status = 'Active'
     LIMIT 1"
);

$roleCheck->bind_param(
    "i",
    $role_id
);

$roleCheck->execute();

$roleResult = $roleCheck->get_result();

if ($roleResult->num_rows !== 1) {

    $roleCheck->close();

    die("The selected role is invalid or inactive.");
}

$roleCheck->close();


/*
|--------------------------------------------------------------------------
| PREVENT ADMIN FROM DISABLING THEIR OWN ACCOUNT
|--------------------------------------------------------------------------
*/

if (
    $user_id === $currentUserId &&
    $status === "Inactive"
) {

    die("You cannot deactivate your own Administrator account.");
}


/*
|--------------------------------------------------------------------------
| UPDATE USER WITH PASSWORD
|--------------------------------------------------------------------------
*/

if ($password !== "") {

    /*
    | Password validation
    */

    if (strlen($password) < 8) {

        die("New password must be at least 8 characters long.");
    }


    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );


    if ($hashed_password === false) {

        die("Unable to secure the new password.");
    }


    $stmt = $conn->prepare(
        "UPDATE users
         SET full_name = ?,
             username = ?,
             email = ?,
             password = ?,
             role_id = ?,
             status = ?
         WHERE user_id = ?"
    );


    if (!$stmt) {

        die("Unable to prepare user update.");
    }


    $stmt->bind_param(
        "ssssisi",
        $full_name,
        $username,
        $email,
        $hashed_password,
        $role_id,
        $status,
        $user_id
    );

}


/*
|--------------------------------------------------------------------------
| UPDATE USER WITHOUT CHANGING PASSWORD
|--------------------------------------------------------------------------
*/

else {

    $stmt = $conn->prepare(
        "UPDATE users
         SET full_name = ?,
             username = ?,
             email = ?,
             role_id = ?,
             status = ?
         WHERE user_id = ?"
    );


    if (!$stmt) {

        die("Unable to prepare user update.");
    }


    $stmt->bind_param(
        "sssisi",
        $full_name,
        $username,
        $email,
        $role_id,
        $status,
        $user_id
    );
}


/*
|--------------------------------------------------------------------------
| EXECUTE UPDATE
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    $stmt->close();

    /*
    | If the Administrator updated their own account,
    | refresh the session information.
    */

    if ($user_id === $currentUserId) {

        $_SESSION["full_name"] = $full_name;

        $_SESSION["username"] = $username;

        $_SESSION["email"] = $email;

        $_SESSION["role_id"] = $role_id;


        $roleNameStmt = $conn->prepare(
            "SELECT role_name
             FROM roles
             WHERE role_id = ?
             LIMIT 1"
        );

        $roleNameStmt->bind_param(
            "i",
            $role_id
        );

        $roleNameStmt->execute();

        $roleNameResult = $roleNameStmt->get_result();

        if ($roleNameResult->num_rows === 1) {

            $updatedRole = $roleNameResult->fetch_assoc();

            $_SESSION["role_name"] =
                $updatedRole["role_name"];
        }

        $roleNameStmt->close();
    }


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

die("Unable to update user: " . $error);

?>