<?php

session_start();

include "../config/database.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";


// Check if fields are empty
if ($username === "" || $password === "") {
    $_SESSION["login_error"] = "Please enter your username and password.";
    header("Location: login.php");
    exit;
}


// Find the user
$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.contact_number,
        u.profile_picture,
        u.password,
        u.role_id,
        u.status,
        r.role_name
    FROM users u
    INNER JOIN roles r 
        ON u.role_id = r.role_id
    WHERE u.username = ?
    LIMIT 1
");

$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();


// User does not exist
if ($result->num_rows !== 1) {

    $_SESSION["login_error"] = "Invalid username or password.";

    $stmt->close();
    $conn->close();

    header("Location: login.php");
    exit;
}


$user = $result->fetch_assoc();


// Check account status
if ($user["status"] !== "Active") {

    $_SESSION["login_error"] = "Your account is currently inactive. Please contact the administrator.";

    $stmt->close();
    $conn->close();

    header("Location: login.php");
    exit;
}


// Verify password
if (!password_verify($password, $user["password"])) {

    $_SESSION["login_error"] = "Invalid username or password.";

    $stmt->close();
    $conn->close();

    header("Location: login.php");
    exit;
}


// Regenerate session ID for security
session_regenerate_id(true);


// Store logged-in user's information
$_SESSION["user_id"] = $user["user_id"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["username"] = $user["username"];
$_SESSION["email"] = $user["email"];
$_SESSION["contact_number"] = $user["contact_number"];
$_SESSION["profile_picture"] = $user["profile_picture"];
$_SESSION["role_id"] = $user["role_id"];
$_SESSION["role_name"] = $user["role_name"];


// Close database connection
$stmt->close();
$conn->close();


// Go to dashboard
header("Location: /SMS/admin/dashboard.php");
exit;
?>