<?php

require_once "../../includes/auth_check.php";

include "../../config/database.php";


/* =========================================================
   ALLOW ADMINISTRATOR ONLY
========================================================= */

$currentUserId = $_SESSION["user_id"] ?? 0;


if ($currentUserId <= 0) {

    header("Location: /SMS/auth/login.php");
    exit;

}


/* GET CURRENT USER ROLE FROM DATABASE */

$adminCheck = $conn->prepare(
    "SELECT
        r.role_name
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

$adminResult =
    $adminCheck->get_result();


if ($adminResult->num_rows !== 1) {

    $adminCheck->close();

    die("Unable to verify your administrator account.");

}


$currentRole =
    $adminResult->fetch_assoc();

$adminCheck->close();


if ($currentRole["role_name"] !== "Administrator") {

    http_response_code(403);

    die(
        "Access denied. Only an Administrator can create system users."
    );

}


/* =========================================================
   REQUEST METHOD
========================================================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: users.php");

    exit;

}


/* =========================================================
   GET FORM DATA
========================================================= */

$full_name =
    trim($_POST["full_name"] ?? "");

$username =
    trim($_POST["username"] ?? "");

$email =
    trim($_POST["email"] ?? "");

$password =
    $_POST["password"] ?? "";

$role_id =
    intval($_POST["role_id"] ?? 0);

$status =
    $_POST["status"] ?? "Active";


/* =========================================================
   BASIC VALIDATION
========================================================= */

if (
    $full_name === "" ||
    $username === "" ||
    $email === "" ||
    $password === "" ||
    $role_id <= 0
) {

    die(
        "Please complete all required fields."
    );

}


/* =========================================================
   FULL NAME VALIDATION
========================================================= */

if (mb_strlen($full_name) < 2) {

    die(
        "Please enter a valid full name."
    );

}


if (mb_strlen($full_name) > 150) {

    die(
        "Full name is too long."
    );

}


/* =========================================================
   USERNAME VALIDATION
========================================================= */

if (mb_strlen($username) < 3) {

    die(
        "Username must be at least 3 characters long."
    );

}


if (mb_strlen($username) > 100) {

    die(
        "Username is too long."
    );

}


/* =========================================================
   USERNAME CHARACTER VALIDATION
========================================================= */

if (
    !preg_match(
        "/^[A-Za-z0-9._-]+$/",
        $username
    )
) {

    die(
        "Username may only contain letters, numbers, periods, underscores, and hyphens."
    );

}


/* =========================================================
   EMAIL VALIDATION
========================================================= */

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    die(
        "Please enter a valid email address."
    );

}


if (mb_strlen($email) > 150) {

    die(
        "Email address is too long."
    );

}


/* =========================================================
   PASSWORD VALIDATION
========================================================= */

if (strlen($password) < 8) {

    die(
        "Password must be at least 8 characters long."
    );

}


/* =========================================================
   STATUS VALIDATION
========================================================= */

if (
    !in_array(
        $status,
        ["Active", "Inactive"],
        true
    )
) {

    die(
        "Invalid user status."
    );

}


/* =========================================================
   VERIFY SELECTED ROLE
========================================================= */

$roleCheck = $conn->prepare(
    "SELECT
        role_id,
        role_name
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

$roleResult =
    $roleCheck->get_result();


if ($roleResult->num_rows !== 1) {

    $roleCheck->close();

    die(
        "The selected role is invalid or inactive."
    );

}


$selectedRole =
    $roleResult->fetch_assoc();

$roleCheck->close();


/* =========================================================
   CHECK USERNAME
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        user_id
     FROM users
     WHERE username = ?
     LIMIT 1"
);

$stmt->bind_param(
    "s",
    $username
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    die(
        "This username already exists."
    );

}

$stmt->close();


/* =========================================================
   CHECK EMAIL
========================================================= */

$stmt = $conn->prepare(
    "SELECT
        user_id
     FROM users
     WHERE email = ?
     LIMIT 1"
);

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result =
    $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    die(
        "This email is already registered."
    );

}

$stmt->close();


/* =========================================================
   HASH PASSWORD
========================================================= */

$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


if ($hashed_password === false) {

    die(
        "Unable to securely create the password."
    );

}


/* =========================================================
   INSERT USER
========================================================= */

$stmt = $conn->prepare(
    "INSERT INTO users
    (
        full_name,
        username,
        email,
        password,
        role_id,
        status
    )
    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )"
);


$stmt->bind_param(
    "ssssis",
    $full_name,
    $username,
    $email,
    $hashed_password,
    $role_id,
    $status
);


/* =========================================================
   SAVE USER
========================================================= */

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: /SMS/admin/users/users.php"
    );

    exit;

}


/* =========================================================
   DATABASE ERROR
========================================================= */

$error =
    $stmt->error;

$stmt->close();


die(
    "Unable to save user: " .
    htmlspecialchars(
        $error,
        ENT_QUOTES,
        "UTF-8"
    )
);

?>