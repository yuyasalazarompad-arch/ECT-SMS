<?php

session_start();

include "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT
                u.user_id,
                u.full_name,
                u.username,
                u.email,
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

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if ($user["status"] !== "Active") {

                $error = "Your account is inactive. Please contact the administrator.";

            } elseif (password_verify($password, $user["password"])) {

                /*
                 * Regenerate the session ID after successful login.
                 * This helps protect against session fixation.
                 */
                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["role_name"] = $user["role_name"];

                /*
                 * Redirect according to the user's role.
                 */
                if ($user["role_name"] === "Administrator") {

                    header("Location: ../admin/dashboard.php");
                    exit;

                } elseif ($user["role_name"] === "Student") {

                    /*
                     * Student dashboard will be created later.
                     * For now, redirect to a temporary page.
                     */
                    header("Location: ../student/dashboard.php");
                    exit;

                } elseif ($user["role_name"] === "Faculty") {

                    /*
                     * Faculty dashboard will be created later.
                     */
                    header("Location: ../faculty/dashboard.php");
                    exit;

                } elseif ($user["role_name"] === "Registrar") {

                    /*
                     * Registrar dashboard will be created later.
                     */
                    header("Location: ../registrar/dashboard.php");
                    exit;

                } else {

                    $error = "Your account role is not configured.";

                }

            } else {

                $error = "Invalid username or password.";

            }

        } else {

            $error = "Invalid username or password.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | School Management System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                linear-gradient(
                    135deg,
                    #12355B 0%,
                    #1E5A96 100%
                );

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 24px;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
        }

        .login-card {
            background: #ffffff;

            border-radius: 18px;

            padding: 38px;

            box-shadow:
                0 18px 45px rgba(0, 0, 0, 0.18);
        }

        .brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            width: 70px;
            height: 70px;

            margin: 0 auto 15px;

            border-radius: 16px;

            background: #EAF3FA;

            display: flex;
            align-items: center;
            justify-content: center;

            color: #12355B;

            font-size: 25px;
            font-weight: 700;
        }

        .brand h1 {
            margin: 0;

            color: #12355B;

            font-size: 24px;
        }

        .brand p {
            margin: 7px 0 0;

            color: #6b7280;

            font-size: 14px;
        }

        .error-message {
            background: #fff1f2;

            border: 1px solid #fecdd3;

            color: #be123c;

            padding: 12px 14px;

            border-radius: 9px;

            font-size: 13px;

            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;

            margin-bottom: 7px;

            color: #263238;

            font-size: 13px;

            font-weight: 600;
        }

        input {
            width: 100%;

            padding: 13px 14px;

            border: 1px solid #d7dee7;

            border-radius: 9px;

            font-size: 14px;

            outline: none;

            transition: 0.2s;
        }

        input:focus {
            border-color: #1E5A96;

            box-shadow:
                0 0 0 3px rgba(30, 90, 150, 0.10);
        }

        .login-button {
            width: 100%;

            border: none;

            border-radius: 9px;

            padding: 13px;

            background: #12355B;

            color: #ffffff;

            font-size: 14px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }

        .login-button:hover {
            background: #0d2947;
        }

        .login-footer {
            text-align: center;

            margin-top: 22px;

            color: #7b8794;

            font-size: 12px;
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="brand">

            <div class="brand-logo">
                FBC
            </div>

            <h1>School Management System</h1>

            <p>Fullbright College</p>

        </div>

        <?php if ($error !== ""): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    autocomplete="username"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                    required
                >

            </div>

            <button
                type="submit"
                class="login-button"
            >
                Sign In
            </button>

        </form>

        <div class="login-footer">
            Fullbright College · School Management System
        </div>

    </div>

</div>

</body>
</html>