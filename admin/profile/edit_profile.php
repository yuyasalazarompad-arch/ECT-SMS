<?php

session_start();

include "../../config/database.php";

$userId = $_SESSION["user_id"] ?? 1;

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.full_name,
        u.username,
        u.email,
        u.contact_number,
        u.profile_picture,
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

<title>Edit Profile | SMS</title>

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
    max-width: 1000px;
    margin: auto;
}

/* =========================
   PROFILE HEADER
========================= */

.profile-header {
    background: linear-gradient(135deg, #0b477f, #1b75c8);
    border-radius: 14px;
    padding: 25px;
    color: white;
    display: flex;
    align-items: center;
    gap: 20px;
}

.profile-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: white;
    color: #0b477f;
    border: 4px solid rgba(255,255,255,0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: 27px;
    font-weight: bold;
    flex-shrink: 0;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-header h2 {
    margin: 0 0 5px;
    font-size: 22px;
}

.profile-header p {
    margin: 0;
    font-size: 12px;
    opacity: 0.85;
}

/* =========================
   FORM CARD
========================= */

.form-card {
    margin-top: 20px;
    background: white;
    border: 1px solid #e5eaf0;
    border-radius: 12px;
    padding: 25px;
}

.form-card h3 {
    margin: 0;
    font-size: 17px;
    color: #082b55;
}

.form-description {
    margin: 5px 0 22px;
    color: #7b8794;
    font-size: 11px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    font-size: 11px;
    font-weight: 600;
    color: #405268;
    margin-bottom: 7px;
}

.form-group input {
    height: 42px;
    border: 1px solid #d9e1ea;
    border-radius: 8px;
    padding: 0 12px;
    font-size: 12px;
    outline: none;
    color: #26384d;
    background: white;
}

.form-group input:focus {
    border-color: #1d70c9;
    box-shadow: 0 0 0 3px rgba(29,112,201,0.08);
}

.form-group input[readonly] {
    background: #f5f7fa;
    color: #7b8794;
    cursor: not-allowed;
}

.field-note {
    margin-top: 5px;
    color: #8a96a3;
    font-size: 10px;
}

/* =========================
   PROFILE PHOTO
========================= */

.photo-section {
    display: flex;
    align-items: center;
    gap: 18px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #edf1f5;
}

.photo-preview {
    width: 82px;
    height: 82px;
    border-radius: 50%;
    background: #e6f0fa;
    color: #1769aa;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    font-size: 25px;
    font-weight: bold;
    flex-shrink: 0;
}

.photo-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-info strong {
    display: block;
    font-size: 12px;
    color: #27364a;
    margin-bottom: 4px;
}

.photo-info span {
    display: block;
    font-size: 10px;
    color: #8a96a3;
    margin-bottom: 10px;
}

.file-input {
    font-size: 11px;
}

/* =========================
   BUTTONS
========================= */

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #edf1f5;
}

.btn {
    border: none;
    border-radius: 8px;
    padding: 10px 18px;
    font-size: 11px;
    cursor: pointer;
    text-decoration: none;
}

.btn-primary {
    background: #1769aa;
    color: white;
}

.btn-primary:hover {
    background: #0f5b98;
}

.btn-secondary {
    background: #edf3f8;
    color: #31526f;
}

.btn-secondary:hover {
    background: #e1ebf4;
}

/* =========================
   RESPONSIVE
========================= */

@media (max-width: 750px) {

    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-group.full {
        grid-column: auto;
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

    .content {
        padding: 15px;
    }

    .topbar {
        padding: 0 15px;
    }

    .top-user {
        display: none;
    }

    .profile-header {
        padding: 20px;
    }

    .profile-avatar {
        width: 72px;
        height: 72px;
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

            <h1>Edit Profile</h1>

            <p>
                Update your personal account information
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

            <div>

                <h2>
                    <?= htmlspecialchars($user["full_name"]) ?>
                </h2>

                <p>
                    @<?= htmlspecialchars($user["username"]) ?>
                    &nbsp; • &nbsp;
                    <?= htmlspecialchars($user["role_name"] ?? "User") ?>
                </p>

            </div>

        </div>


        <!-- EDIT FORM -->

        <div class="form-card">

            <h3>Personal Information</h3>

            <p class="form-description">
                Update the information you want to keep current in your SMS account.
            </p>

            <form
                action="update_profile.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="photo-section">

                    <div class="photo-preview">

                        <?php if ($profilePicture): ?>

                            <img
                                src="<?= htmlspecialchars($profilePicture) ?>"
                                alt="Current Profile Picture"
                            >

                        <?php else: ?>

                            <?= htmlspecialchars($initials) ?>

                        <?php endif; ?>

                    </div>

                    <div class="photo-info">

                        <strong>Profile Picture</strong>

                        <span>
                            JPG, JPEG, PNG or WEBP. Maximum recommended size: 2 MB.
                        </span>

                        <input
                            type="file"
                            name="profile_picture"
                            class="file-input"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        >

                    </div>

                </div>


                <div class="form-grid">

                    <div class="form-group">

                        <label for="full_name">
                            Full Name
                        </label>

                        <input
                            type="text"
                            id="full_name"
                            name="full_name"
                            value="<?= htmlspecialchars($user["full_name"]) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="username">
                            Username
                        </label>

                        <input
                            type="text"
                            id="username"
                            value="<?= htmlspecialchars($user["username"]) ?>"
                            readonly
                        >

                        <span class="field-note">
                            Username cannot be changed here.
                        </span>

                    </div>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($user["email"]) ?>"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="contact_number">
                            Contact Number
                        </label>

                        <input
                            type="text"
                            id="contact_number"
                            name="contact_number"
                            value="<?= htmlspecialchars($user["contact_number"] ?? "") ?>"
                            placeholder="Enter your contact number"
                        >

                    </div>

                </div>


                <div class="form-actions">

                    <a
                        href="profile.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>

                </div>

            </form>

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