<?php

session_start();

include "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get logged-in user
|--------------------------------------------------------------------------
*/

$userId = $_SESSION["user_id"] ?? 1;

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.contact_number,
        u.profile_picture,
        u.status,
        r.role_name
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.role_id
    WHERE u.user_id = ?
    LIMIT 1
");

$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User profile not found.");
}

$profilePicture = !empty($user["profile_picture"])
    ? "/SMS/assets/uploads/users/" . $user["profile_picture"]
    : null;

$initials = "";

$nameParts = explode(" ", trim($user["full_name"]));

foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper(substr($part, 0, 1));
    }

    if (strlen($initials) >= 2) {
        break;
    }
}

if (empty($initials)) {
    $initials = "U";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>My Profile | SMS</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    background: #f4f8fc;
    color: #1f2937;
}

/* =========================
   SIDEBAR
========================= */

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100vh;
    background: #082b55;
    color: white;
    padding: 20px 14px;
    transition: width 0.25s ease;
    z-index: 1000;
}

.sidebar.collapsed {
    width: 78px;
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 4px 10px 25px;
}

.logo-box {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: white;
    color: #082b55;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 17px;
    flex-shrink: 0;
}

.logo-text {
    overflow: hidden;
    white-space: nowrap;
}

.logo-text strong {
    display: block;
    font-size: 17px;
}

.logo-text span {
    display: block;
    font-size: 11px;
    opacity: 0.75;
    margin-top: 2px;
}

.sidebar.collapsed .logo-text {
    display: none;
}

.menu-title {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.55;
    padding: 0 12px;
    margin: 12px 0 8px;
}

.sidebar.collapsed .menu-title {
    display: none;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 13px;
    height: 44px;
    padding: 0 12px;
    margin-bottom: 4px;
    color: #dce9f8;
    text-decoration: none;
    border-radius: 9px;
    font-size: 13px;
    transition: 0.2s;
}

.menu-item:hover {
    background: rgba(255,255,255,0.09);
    color: white;
}

.menu-item.active {
    background: #1d70c9;
    color: white;
}

.menu-icon {
    width: 24px;
    text-align: center;
    font-size: 16px;
    flex-shrink: 0;
}

.menu-label {
    white-space: nowrap;
}

.sidebar.collapsed .menu-item {
    justify-content: center;
    padding: 0;
}

.sidebar.collapsed .menu-label {
    display: none;
}

.collapse-btn {
    position: absolute;
    bottom: 20px;
    left: 14px;
    right: 14px;
    height: 40px;
    border: 0;
    border-radius: 8px;
    background: rgba(255,255,255,0.08);
    color: white;
    cursor: pointer;
}

.sidebar.collapsed .collapse-btn {
    left: 14px;
    right: 14px;
}

/* =========================
   MAIN
========================= */

.main {
    margin-left: 250px;
    min-height: 100vh;
    transition: margin-left 0.25s ease;
}

.sidebar.collapsed ~ .main {
    margin-left: 78px;
}

/* =========================
   TOPBAR
========================= */

.topbar {
    height: 72px;
    background: white;
    border-bottom: 1px solid #e5eaf0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
}

.page-title h1 {
    margin: 0;
    font-size: 21px;
    color: #082b55;
}

.page-title p {
    margin: 4px 0 0;
    font-size: 12px;
    color: #7b8794;
}

.user-area {
    display: flex;
    align-items: center;
    gap: 10px;
}

.top-avatar {
    width: 39px;
    height: 39px;
    border-radius: 50%;
    background: #dcecff;
    color: #07549a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: bold;
    overflow: hidden;
}

.top-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.top-user strong {
    display: block;
    font-size: 12px;
}

.top-user span {
    font-size: 10px;
    color: #7b8794;
}

/* =========================
   CONTENT
========================= */

.content {
    padding: 30px;
    max-width: 1200px;
    margin: auto;
}

.profile-cover {
    background: linear-gradient(135deg, #0b477f, #1b75c8);
    border-radius: 14px;
    min-height: 180px;
    position: relative;
    overflow: hidden;
    padding: 28px;
    color: white;
}

.profile-cover::after {
    content: "";
    position: absolute;
    width: 250px;
    height: 250px;
    right: -70px;
    top: -100px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
}

.profile-header {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 22px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: white;
    color: #0b477f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 30px;
    font-weight: bold;
    border: 5px solid rgba(255,255,255,0.75);
    overflow: hidden;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-name h2 {
    margin: 0 0 6px;
    font-size: 24px;
}

.profile-name p {
    margin: 0;
    opacity: 0.85;
    font-size: 13px;
}

.role-badge {
    display: inline-block;
    margin-top: 10px;
    padding: 5px 10px;
    border-radius: 20px;
    background: rgba(255,255,255,0.16);
    font-size: 11px;
}

/* =========================
   CARDS
========================= */

.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 20px;
}

.card {
    background: white;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    padding: 23px;
}

.card h3 {
    margin: 0 0 4px;
    color: #082b55;
    font-size: 16px;
}

.card-subtitle {
    color: #8a96a3;
    font-size: 11px;
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 12px 0;
    border-bottom: 1px solid #edf1f5;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    color: #7b8794;
    font-size: 11px;
}

.info-value {
    color: #27364a;
    font-size: 12px;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}

/* =========================
   ACTION CARD
========================= */

.profile-actions {
    margin-top: 20px;
    background: white;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    padding: 22px;
}

.profile-actions h3 {
    margin: 0 0 5px;
    color: #082b55;
    font-size: 16px;
}

.profile-actions p {
    margin: 0 0 18px;
    color: #7b8794;
    font-size: 11px;
}

.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 10px 16px;
    font-size: 11px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background: #1769aa;
    color: white;
}

.btn-secondary {
    background: #eef4fa;
    color: #24547c;
}

@media (max-width: 800px) {

    .grid {
        grid-template-columns: 1fr;
    }

    .content {
        padding: 20px;
    }

    .top-user {
        display: none;
    }

    .profile-header {
        align-items: flex-start;
    }

}

@media (max-width: 600px) {

    .sidebar {
        width: 78px;
    }

    .sidebar .logo-text,
    .sidebar .menu-title,
    .sidebar .menu-label {
        display: none;
    }

    .sidebar .menu-item {
        justify-content: center;
        padding: 0;
    }

    .main {
        margin-left: 78px;
    }

    .topbar {
        padding: 0 15px;
    }

    .content {
        padding: 15px;
    }

    .profile-cover {
        padding: 22px;
    }

    .profile-avatar {
        width: 78px;
        height: 78px;
    }

    .profile-name h2 {
        font-size: 19px;
    }

}

</style>

</head>

<body>

<!-- =========================
     SIDEBAR
========================= -->

<aside class="sidebar" id="sidebar">

    <div class="logo-area">

        <div class="logo-box">
            FBC
        </div>

        <div class="logo-text">
            <strong>SMS</strong>
            <span>School Management System</span>
        </div>

    </div>

    <div class="menu-title">Main Menu</div>

    <a href="/SMS/admin/dashboard.php" class="menu-item">
        <span class="menu-icon">⌂</span>
        <span class="menu-label">Dashboard</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">♙</span>
        <span class="menu-label">Students</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">▣</span>
        <span class="menu-label">Enrollment</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">♟</span>
        <span class="menu-label">Faculty</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">◫</span>
        <span class="menu-label">Attendance</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">▥</span>
        <span class="menu-label">Grades</span>
    </a>

    <a href="#" class="menu-item">
        <span class="menu-icon">▤</span>
        <span class="menu-label">Reports</span>
    </a>

    <div class="menu-title">Administration</div>

    <a href="/SMS/admin/users/users.php" class="menu-item">
        <span class="menu-icon">♙</span>
        <span class="menu-label">Users</span>
    </a>

    <a href="/SMS/admin/roles/roles.php" class="menu-item">
        <span class="menu-icon">⚿</span>
        <span class="menu-label">Roles & Permissions</span>
    </a>

    <a href="/SMS/admin/school_settings/school_settings.php" class="menu-item">
        <span class="menu-icon">⚙</span>
        <span class="menu-label">School Settings</span>
    </a>

    <a href="/SMS/admin/school_year/school_year.php" class="menu-item">
        <span class="menu-icon">▣</span>
        <span class="menu-label">School Year</span>
    </a>

    <div class="menu-title">Account</div>

    <a href="/SMS/admin/profile/profile.php" class="menu-item active">
        <span class="menu-icon">●</span>
        <span class="menu-label">My Profile</span>
    </a>

    <button class="collapse-btn" id="collapseBtn" type="button">
        ◀ &nbsp; Collapse
    </button>

</aside>


<!-- =========================
     MAIN
========================= -->

<main class="main">

    <header class="topbar">

        <div class="page-title">

            <h1>My Profile</h1>

            <p>
                Manage your personal account information
            </p>

        </div>

        <div class="user-area">

            <div class="top-avatar">

                <?php if ($profilePicture): ?>

                    <img
                        src="<?= htmlspecialchars($profilePicture) ?>"
                        alt="Profile Picture"
                    >

                <?php else: ?>

                    <?= htmlspecialchars($initials) ?>

                <?php endif; ?>

            </div>

            <div class="top-user">

                <strong>
                    <?= htmlspecialchars($user["full_name"]) ?>
                </strong>

                <span>
                    <?= htmlspecialchars($user["role_name"] ?? "User") ?>
                </span>

            </div>

        </div>

    </header>


    <section class="content">

        <!-- PROFILE HEADER -->

        <div class="profile-cover">

            <div class="profile-header">

                <div class="profile-avatar">

                    <?php if ($profilePicture): ?>

                        <img
                            src="<?= htmlspecialchars($profilePicture) ?>"
                            alt="Profile Picture"
                        >

                    <?php else: ?>

                        <?= htmlspecialchars($initials) ?>

                    <?php endif; ?>

                </div>

                <div class="profile-name">

                    <h2>
                        <?= htmlspecialchars($user["full_name"]) ?>
                    </h2>

                    <p>
                        @<?= htmlspecialchars($user["username"]) ?>
                    </p>

                    <span class="role-badge">
                        <?= htmlspecialchars($user["role_name"] ?? "User") ?>
                    </span>

                </div>

            </div>

        </div>


        <!-- INFORMATION -->

        <div class="grid">

            <div class="card">

                <h3>Personal Information</h3>

                <div class="card-subtitle">
                    Your basic account information
                </div>

                <div class="info-row">

                    <span class="info-label">
                        Full Name
                    </span>

                    <span class="info-value">
                        <?= htmlspecialchars($user["full_name"]) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Username
                    </span>

                    <span class="info-value">
                        <?= htmlspecialchars($user["username"]) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Role
                    </span>

                    <span class="info-value">
                        <?= htmlspecialchars($user["role_name"] ?? "User") ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Account Status
                    </span>

                    <span class="info-value">
                        <?= htmlspecialchars($user["status"]) ?>
                    </span>

                </div>

            </div>


            <div class="card">

                <h3>Contact Information</h3>

                <div class="card-subtitle">
                    Your registered contact details
                </div>

                <div class="info-row">

                    <span class="info-label">
                        Email
                    </span>

                    <span class="info-value">
                        <?= htmlspecialchars($user["email"]) ?>
                    </span>

                </div>

                <div class="info-row">

                    <span class="info-label">
                        Contact Number
                    </span>

                    <span class="info-value">

                        <?= !empty($user["contact_number"])
                            ? htmlspecialchars($user["contact_number"])
                            : "Not provided"
                        ?>

                    </span>

                </div>

            </div>

        </div>


        <!-- ACTIONS -->

        <div class="profile-actions">

            <h3>Account Settings</h3>

            <p>
                Update your profile information or change your account password.
            </p>

            <div class="action-buttons">

                <a
                    href="edit_profile.php"
                    class="btn btn-primary"
                >
                    Edit Profile
                </a>

                <a
                    href="change_password.php"
                    class="btn btn-secondary"
                >
                    Change Password
                </a>

            </div>

        </div>

    </section>

</main>


<script>

const sidebar = document.getElementById("sidebar");
const collapseBtn = document.getElementById("collapseBtn");

const savedState = localStorage.getItem("smsSidebarCollapsed");

if (savedState === "true") {

    sidebar.classList.add("collapsed");
    collapseBtn.innerHTML = "▶";

}

collapseBtn.addEventListener("click", function () {

    sidebar.classList.toggle("collapsed");

    const collapsed =
        sidebar.classList.contains("collapsed");

    localStorage.setItem(
        "smsSidebarCollapsed",
        collapsed
    );

    if (collapsed) {

        collapseBtn.innerHTML = "▶";

    } else {

        collapseBtn.innerHTML = "◀ &nbsp; Collapse";

    }

});

</script>

</body>

</html>