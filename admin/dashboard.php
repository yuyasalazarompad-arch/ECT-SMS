<?php

require_once "../includes/auth_check.php";

if (($_SESSION["role_name"] ?? "") !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Administrator access is required.");
}

include "../config/database.php";

/*
|--------------------------------------------------------------------------
| Logged-in user information
|--------------------------------------------------------------------------
*/

$userId = $_SESSION["user_id"];

$loggedInUser = $_SESSION["full_name"] ?? "User";
$loggedInRole = $_SESSION["role_name"] ?? "User";

/*
|--------------------------------------------------------------------------
| Get current user's profile information
|--------------------------------------------------------------------------
*/

$profilePicture = "";
$contactNumber = "";
$userEmail = "";

$userQuery = $conn->prepare("
    SELECT 
        full_name,
        email,
        contact_number,
        profile_picture
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$userQuery->bind_param("i", $userId);
$userQuery->execute();

$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {

    $currentUser = $userResult->fetch_assoc();

    $loggedInUser = $currentUser["full_name"];
    $userEmail = $currentUser["email"];
    $contactNumber = $currentUser["contact_number"] ?? "";
    $profilePicture = $currentUser["profile_picture"] ?? "";

}


/*
|--------------------------------------------------------------------------
| Profile picture / initials
|--------------------------------------------------------------------------
*/

$profileImageUrl = "";

if (!empty($profilePicture)) {

    $profileImageUrl =
        "/SMS/assets/uploads/users/" .
        rawurlencode($profilePicture);

}


/*
|--------------------------------------------------------------------------
| Generate user initials
|--------------------------------------------------------------------------
*/

$nameParts = preg_split(
    '/\s+/',
    trim($loggedInUser)
);

$initials = "";

if (!empty($nameParts[0])) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
}

if (count($nameParts) > 1) {
    $initials .= strtoupper(
        substr($nameParts[count($nameParts) - 1], 0, 1)
    );
}

if ($initials === "") {
    $initials = "U";
}
/* Profile picture is loaded from the authenticated user record above. */


/* =========================================================
   USER INITIALS
========================================================= */

$userInitials = "U";

$nameParts = preg_split('/\s+/', trim($loggedInUser));

if (!empty($nameParts[0])) {
    $userInitials = strtoupper(substr($nameParts[0], 0, 1));

    if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
        $userInitials .= strtoupper(
            substr($nameParts[count($nameParts) - 1], 0, 1)
        );
    }
}


/* =========================================================
   PROFILE PICTURE PATH
========================================================= */

$profilePicturePath = "";

if (!empty($profilePicture)) {

    $profilePicturePath =
        "/SMS/assets/uploads/users/" .
        rawurlencode($profilePicture);
}


/* =========================================================
   SCHOOL SETTINGS
========================================================= */

$school_name = "Fullbright College";
$school_code = "FBC";
$school_tagline = "School Management System";
$school_description =
    "Manage your school's users, roles, settings, and academic information from one place.";

$school_logo = "";
$school_banner = "";


$schoolResult = $conn->query(
    "SELECT *
     FROM school_settings
     ORDER BY setting_id ASC
     LIMIT 1"
);


if ($schoolResult && $schoolResult->num_rows > 0) {

    $school = $schoolResult->fetch_assoc();

    if (!empty($school["school_name"])) {
        $school_name = $school["school_name"];
    }

    if (!empty($school["school_code"])) {
        $school_code = $school["school_code"];
    }

    if (!empty($school["school_tagline"])) {
        $school_tagline = $school["school_tagline"];
    }

    if (!empty($school["description"])) {
        $school_description = $school["description"];
    }

    if (!empty($school["logo_filename"])) {
        $school_logo = $school["logo_filename"];
    }

    if (!empty($school["banner_filename"])) {
        $school_banner = $school["banner_filename"];
    }
}


/* =========================================================
   DASHBOARD DATA
========================================================= */

$users_count = 0;
$roles_count = 0;
$school_years_count = 0;
$active_users_count = 0;


/* TOTAL USERS */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users"
);

if ($result) {

    $row = $result->fetch_assoc();

    $users_count = $row["total"];
}


/* TOTAL ROLES */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM roles"
);

if ($result) {

    $row = $result->fetch_assoc();

    $roles_count = $row["total"];
}


/* TOTAL SCHOOL YEARS */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM school_years"
);

if ($result) {

    $row = $result->fetch_assoc();

    $school_years_count = $row["total"];
}


/* ACTIVE USERS */

$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE status = 'Active'"
);

if ($result) {

    $row = $result->fetch_assoc();

    $active_users_count = $row["total"];
}


/* =========================================================
   SAFE DISPLAY VALUES
========================================================= */

$displaySchoolName =
    htmlspecialchars(
        $school_name,
        ENT_QUOTES,
        "UTF-8"
    );


$displaySchoolCode =
    htmlspecialchars(
        $school_code,
        ENT_QUOTES,
        "UTF-8"
    );


$displaySchoolTagline =
    htmlspecialchars(
        $school_tagline,
        ENT_QUOTES,
        "UTF-8"
    );


$displaySchoolDescription =
    htmlspecialchars(
        $school_description,
        ENT_QUOTES,
        "UTF-8"
    );


$displayUserName =
    htmlspecialchars(
        $loggedInUser,
        ENT_QUOTES,
        "UTF-8"
    );


$displayUserRole =
    htmlspecialchars(
        $loggedInRole,
        ENT_QUOTES,
        "UTF-8"
    );


$displayInitials =
    htmlspecialchars(
        $userInitials,
        ENT_QUOTES,
        "UTF-8"
    );


/* =========================================================
   SCHOOL LOGO PATH
========================================================= */

$logoPath = "";

if (!empty($school_logo)) {

    $logoPath =
        "/SMS/assets/uploads/school/" .
        rawurlencode($school_logo);
}


/* =========================================================
   SCHOOL BANNER PATH
========================================================= */

$bannerPath = "";

if (!empty($school_banner)) {

    $bannerPath =
        "/SMS/assets/uploads/school/" .
        rawurlencode($school_banner);
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Dashboard | <?php echo $displaySchoolName; ?>
    </title>


<style>

/* =========================================================
   SMS DASHBOARD
========================================================= */

:root {

    --navy: #0b2850;
    --navy-dark: #071d3b;

    --blue: #1769d1;
    --blue-light: #eaf3ff;

    --white: #ffffff;

    --background: #f4f7fb;

    --text: #172033;
    --muted: #718096;

    --border: #e1e8f1;

    --success: #1d9b61;
    --danger: #d64545;

    --sidebar-width: 260px;
    --sidebar-small: 78px;
}


/* =========================================================
   RESET
========================================================= */

* {

    margin: 0;
    padding: 0;

    box-sizing: border-box;
}


html,
body {

    width: 100%;
    min-height: 100%;

    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background: var(--background);

    color: var(--text);
}


a {

    text-decoration: none;

    color: inherit;
}


button {

    font-family: inherit;
}


/* =========================================================
   SIDEBAR
========================================================= */

.sidebar {

    position: fixed;

    left: 0;
    top: 0;
    bottom: 0;

    width: var(--sidebar-width);

    background:
        linear-gradient(
            180deg,
            #0b315e 0%,
            #09284d 100%
        );

    color: white;

    z-index: 1000;

    display: flex;

    flex-direction: column;

    box-shadow:
        5px 0 20px
        rgba(0, 0, 0, 0.08);

    transition:
        width 0.25s ease;

    overflow: hidden;
}


/* =========================================================
   SCHOOL BRAND
========================================================= */

.school-brand {

    min-height: 105px;

    padding: 20px;

    display: flex;

    align-items: center;

    gap: 12px;

    flex-shrink: 0;

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);
}


.school-logo {

    width: 52px;
    height: 52px;

    border-radius: 14px;

    background: white;

    color: var(--navy);

    display: flex;

    align-items: center;
    justify-content: center;

    font-weight: 800;

    font-size: 13px;

    flex-shrink: 0;

    overflow: hidden;
}


.school-logo img {

    width: 100%;
    height: 100%;

    object-fit: contain;

    padding: 5px;
}


.school-logo-text {

    text-align: center;

    line-height: 1.1;
}


.school-name {

    min-width: 0;

    white-space: nowrap;

    overflow: hidden;
}


.school-name strong {

    display: block;

    font-size: 17px;

    margin-bottom: 4px;

    overflow: hidden;

    text-overflow: ellipsis;
}


.school-name span {

    display: block;

    font-size: 10px;

    color:
        rgba(255,255,255,0.62);

    overflow: hidden;

    text-overflow: ellipsis;
}


/* =========================================================
   SIDEBAR NAVIGATION
========================================================= */

.sidebar-nav {

    flex: 1;

    min-height: 0;

    padding: 18px 12px;

    overflow-y: auto;

    overflow-x: hidden;
}


.sidebar-nav::-webkit-scrollbar {

    width: 5px;
}


.sidebar-nav::-webkit-scrollbar-thumb {

    background:
        rgba(255,255,255,0.15);

    border-radius: 10px;
}


.section-title {

    font-size: 10px;

    font-weight: 800;

    letter-spacing: 1.3px;

    color:
        rgba(255,255,255,0.38);

    padding: 8px 12px;

    margin-bottom: 5px;
}


.section-title.admin {

    margin-top: 20px;
}


/* =========================================================
   MENU ITEM
========================================================= */

.menu-item {

    height: 48px;

    width: 100%;

    margin-bottom: 5px;

    border-radius: 11px;

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 0 13px;

    color:
        rgba(255,255,255,0.72);

    font-size: 13px;

    font-weight: 600;

    white-space: nowrap;

    transition:
        background 0.2s ease,
        color 0.2s ease;
}


.menu-item:hover {

    background:
        rgba(255,255,255,0.09);

    color: white;
}


.menu-item.active {

    background: var(--blue);

    color: white;

    box-shadow:
        0 5px 14px
        rgba(23,105,209,0.25);
}


.menu-icon {

    width: 25px;
    height: 25px;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 17px;

    flex-shrink: 0;
}


.menu-name {

    flex: 1;

    overflow: hidden;

    text-overflow: ellipsis;
}


.soon {

    font-size: 8px;

    padding: 3px 6px;

    border-radius: 5px;

    background:
        rgba(255,255,255,0.08);

    color:
        rgba(255,255,255,0.45);
}


/* =========================================================
   SIDEBAR BOTTOM
========================================================= */

.sidebar-bottom {

    flex-shrink: 0;

    padding: 12px;

    background:
        rgba(0,0,0,0.08);

    border-top:
        1px solid
        rgba(255,255,255,0.08);
}


/* =========================================================
   COLLAPSE BUTTON
========================================================= */

.collapse-button {

    width: 100%;

    height: 44px;

    border:
        1px solid
        rgba(255,255,255,0.15);

    border-radius: 10px;

    background:
        rgba(255,255,255,0.09);

    color: white;

    cursor: pointer;

    display: flex;

    align-items: center;
    justify-content: center;

    gap: 8px;

    font-size: 12px;

    font-weight: 700;

    transition:
        background 0.2s ease;
}


.collapse-button:hover {

    background: var(--blue);
}


.collapse-icon {

    font-size: 14px;
}


/* =========================================================
   USER PROFILE
========================================================= */

.admin-profile {

    margin-top: 9px;

    height: 54px;

    padding: 7px;

    display: flex;

    align-items: center;

    gap: 9px;

    border-radius: 10px;

    background:
        rgba(255,255,255,0.06);
}


.admin-avatar {

    width: 38px;
    height: 38px;

    border-radius: 50%;

    background: white;

    color: var(--navy);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 10px;

    font-weight: 800;

    flex-shrink: 0;

    overflow: hidden;
}


.admin-avatar img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}


.admin-info {

    min-width: 0;

    white-space: nowrap;
}


.admin-info strong {

    display: block;

    font-size: 11px;

    overflow: hidden;

    text-overflow: ellipsis;
}


.admin-info span {

    display: block;

    margin-top: 3px;

    font-size: 9px;

    color:
        rgba(255,255,255,0.48);

    overflow: hidden;

    text-overflow: ellipsis;
}




/* =========================================================
   LOGOUT BUTTON
========================================================= */

.logout-link {

    width: 100%;

    height: 42px;

    margin-top: 8px;

    border: 1px solid rgba(255,255,255,0.12);

    border-radius: 10px;

    background: rgba(214,69,69,0.10);

    color: rgba(255,255,255,0.78);

    display: flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    font-size: 12px;

    font-weight: 700;

    transition: background 0.2s ease, color 0.2s ease;
}

.logout-link:hover {

    background: var(--danger);

    color: white;
}

.logout-icon {

    font-size: 15px;

    line-height: 1;
}

/* =========================================================
   MAIN CONTENT
========================================================= */

.main {

    margin-left: var(--sidebar-width);

    min-height: 100vh;

    transition:
        margin-left 0.25s ease;
}


/* =========================================================
   TOPBAR
========================================================= */

.topbar {

    height: 82px;

    background: white;

    border-bottom:
        1px solid var(--border);

    display: flex;

    align-items: center;

    justify-content: space-between;

    padding: 0 30px;
}


.page-title h1 {

    font-size: 25px;

    color: var(--navy);

    margin-bottom: 5px;
}


.page-title p {

    font-size: 12px;

    color: var(--muted);
}


.top-actions {

    display: flex;

    align-items: center;

    gap: 13px;
}


.notification {

    width: 39px;
    height: 39px;

    border-radius: 50%;

    border:
        1px solid var(--border);

    background: white;

    cursor: pointer;

    position: relative;

    font-size: 17px;
}


.notification-dot {

    position: absolute;

    right: 7px;
    top: 7px;

    width: 6px;
    height: 6px;

    border-radius: 50%;

    background: var(--danger);
}


.top-avatar {

    width: 40px;
    height: 40px;

    border-radius: 50%;

    background: var(--navy);

    color: white;

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 10px;

    font-weight: 800;

    overflow: hidden;
}


.top-avatar img {

    width: 100%;
    height: 100%;

    object-fit: cover;
}


/* =========================================================
   PAGE
========================================================= */

.page {

    padding: 28px;
}


/* =========================================================
   WELCOME BANNER
========================================================= */

.welcome {

    min-height: 220px;

    border-radius: 18px;

    padding: 30px;

    background:
        linear-gradient(
            135deg,
            #071e3e 0%,
            #0c3973 60%,
            #1769d1 100%
        );

    color: white;

    position: relative;

    overflow: hidden;

    margin-bottom: 24px;

    box-shadow:
        0 10px 30px
        rgba(7,26,61,0.12);

    display: flex;

    align-items: center;
}


.welcome.has-banner {

    background-size: cover;

    background-position: center;

    background-repeat: no-repeat;
}


.welcome.has-banner::before {

    content: "";

    position: absolute;

    inset: 0;

    background:
        linear-gradient(
            90deg,
            rgba(5, 24, 52, 0.92) 0%,
            rgba(7, 30, 62, 0.76) 48%,
            rgba(7, 30, 62, 0.35) 100%
        );

    z-index: 1;
}


.welcome-content {

    position: relative;

    z-index: 3;

    max-width: 700px;
}


.welcome-label {

    font-size: 10px;

    letter-spacing: 1.5px;

    font-weight: 800;

    color:
        rgba(255,255,255,0.65);
}


.welcome h2 {

    font-size: 28px;

    margin-top: 10px;

    margin-bottom: 9px;
}


.welcome p {

    font-size: 13px;

    line-height: 1.6;

    color:
        rgba(255,255,255,0.78);

    max-width: 700px;
}


/* =========================================================
   DECORATION
========================================================= */

.circle {

    position: absolute;

    border:
        1px solid
        rgba(255,255,255,0.1);

    border-radius: 50%;

    z-index: 2;
}


.circle.one {

    width: 270px;
    height: 270px;

    right: -100px;
    top: -45px;
}


.circle.two {

    width: 190px;
    height: 190px;

    right: 30px;
    top: 20px;
}


.circle.three {

    width: 105px;
    height: 105px;

    right: 90px;
    top: 62px;
}


/* =========================================================
   STATISTICS
========================================================= */

.stats {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 18px;

    margin-bottom: 24px;
}


.stat {

    background: white;

    border:
        1px solid var(--border);

    border-radius: 15px;

    padding: 20px;

    min-height: 155px;

    box-shadow:
        0 4px 16px
        rgba(7,26,61,0.03);
}


.stat-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;
}


.stat-label {

    font-size: 9px;

    letter-spacing: 0.8px;

    font-weight: 800;

    color: var(--muted);
}


.stat-number {

    font-size: 30px;

    font-weight: 800;

    color: var(--navy);

    margin-top: 7px;
}


.stat-icon {

    width: 43px;
    height: 43px;

    border-radius: 11px;

    background: var(--blue-light);

    color: var(--blue);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 18px;
}


.stat-description {

    margin-top: 20px;

    font-size: 10px;

    color: var(--muted);
}


/* =========================================================
   GENERAL CARD
========================================================= */

.card {

    background: white;

    border:
        1px solid var(--border);

    border-radius: 16px;

    padding: 24px;

    box-shadow:
        0 4px 16px
        rgba(7,26,61,0.03);

    margin-bottom: 24px;
}


.card-heading {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-bottom: 18px;
}


.card-heading h2 {

    font-size: 18px;

    color: var(--navy);
}


.card-heading p {

    margin-top: 5px;

    color: var(--muted);

    font-size: 11px;
}


/* =========================================================
   QUICK LINKS
========================================================= */

.quick-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 12px;
}


.quick-link {

    min-height: 75px;

    padding: 14px;

    border:
        1px solid var(--border);

    border-radius: 12px;

    display: flex;

    align-items: center;

    gap: 12px;

    transition:
        transform 0.2s ease,
        box-shadow 0.2s ease,
        border-color 0.2s ease;
}


.quick-link:hover {

    transform: translateY(-2px);

    border-color: #b7cff0;

    box-shadow:
        0 8px 20px
        rgba(7,26,61,0.06);
}


.quick-icon {

    width: 42px;
    height: 42px;

    border-radius: 10px;

    background: var(--blue-light);

    color: var(--blue);

    display: flex;

    align-items: center;
    justify-content: center;

    font-size: 18px;

    flex-shrink: 0;
}


.quick-text {

    min-width: 0;
}


.quick-text strong {

    display: block;

    font-size: 12px;

    color: var(--navy);
}


.quick-text span {

    display: block;

    margin-top: 4px;

    font-size: 10px;

    color: var(--muted);
}


.arrow {

    margin-left: auto;

    color: var(--blue);

    font-size: 18px;
}


/* =========================================================
   SYSTEM OVERVIEW
========================================================= */

.system-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 14px;
}


.system-item {

    background: #f8faff;

    border:
        1px solid var(--border);

    border-radius: 10px;

    padding: 15px;
}


.system-item span {

    display: block;

    font-size: 9px;

    color: var(--muted);

    margin-bottom: 6px;
}


.system-item strong {

    display: block;

    font-size: 11px;

    color: var(--navy);

    word-break: break-word;
}


.system-status {

    padding: 6px 10px;

    border-radius: 20px;

    background: #e8f7ef;

    color: var(--success);

    font-size: 9px;

    font-weight: 800;
}


/* =========================================================
   COLLAPSED SIDEBAR
========================================================= */

body.collapsed .sidebar {

    width: var(--sidebar-small);
}


body.collapsed .main {

    margin-left: var(--sidebar-small);
}


body.collapsed .school-brand {

    justify-content: center;

    padding-left: 10px;
    padding-right: 10px;
}


body.collapsed .school-name {

    display: none;
}


body.collapsed .section-title {

    display: none;
}


body.collapsed .menu-item {

    justify-content: center;

    padding-left: 0;
    padding-right: 0;
}


body.collapsed .menu-name,
body.collapsed .soon {

    display: none;
}


body.collapsed .sidebar-bottom {

    padding: 10px;
}


body.collapsed .collapse-button {

    width: 46px;

    margin-left: auto;
    margin-right: auto;

    padding: 0;
}


body.collapsed .collapse-text {

    display: none;
}


body.collapsed .admin-profile {

    justify-content: center;

    padding: 7px;
}


body.collapsed .admin-info {

    display: none;
}




body.collapsed .logout-link {

    width: 44px;

    margin-left: auto;
    margin-right: auto;

    padding: 0;
}


body.collapsed .logout-text {

    display: none;
}

/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1100px) {

    .stats {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }


    .system-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }
}


@media (max-width: 800px) {

    .sidebar {

        width: var(--sidebar-small);
    }


    .main {

        margin-left: var(--sidebar-small);
    }


    .school-name,
    .section-title,
    .menu-name,
    .soon,
    .collapse-text,
    .admin-info {

        display: none;
    }


    .school-brand {

        justify-content: center;
    }


    .menu-item {

        justify-content: center;

        padding-left: 0;
        padding-right: 0;
    }


    .collapse-button {

        width: 46px;

        margin: auto;
    }


    .admin-profile {

        justify-content: center;
    }


    .page {

        padding: 20px;
    }
}


@media (max-width: 600px) {

    .stats {

        grid-template-columns: 1fr;
    }


    .quick-grid {

        grid-template-columns: 1fr;
    }


    .system-grid {

        grid-template-columns: 1fr;
    }


    .welcome {

        padding: 23px;

        min-height: 210px;
    }


    .welcome h2 {

        font-size: 23px;
    }


    .circle {

        display: none;
    }


    .topbar {

        padding: 0 18px;
    }


    .page-title h1 {

        font-size: 21px;
    }
}



/* =========================================================
   POLISHED FBC DASHBOARD OVERRIDES
   Visual-only updates; existing PHP/database logic is preserved.
========================================================= */
:root {
    --navy: #12355B;
    --navy-dark: #08264A;
    --blue: #1E5A96;
    --blue-light: #EAF3FA;
    --background: #F1F7FD;
    --text: #17324F;
    --muted: #6B8199;
    --border: #DCE9F6;
}

html, body {
    background: var(--background);
}

.main {
    background:
        radial-gradient(ellipse at 85% 4%, rgba(214,233,252,.62), transparent 36%),
        linear-gradient(135deg, #F5F9FE 0%, #EAF4FD 100%);
}

.topbar {
    min-height: 76px;
    height: auto;
    background: rgba(255,255,255,.94);
    border-bottom: 1px solid #D8E7F6;
    box-shadow: 0 3px 16px rgba(18,53,91,.035);
    padding: 16px 30px;
}

.page-title h1 {
    color: #0D4488;
    font-size: 24px;
    font-weight: 750;
    letter-spacing: -.45px;
}

.page-title p {
    color: #587DA8;
    font-size: 13px;
    line-height: 1.5;
}

.top-actions { gap: 14px; }

.notification {
    border-color: #DCE9F6;
    color: #164B87;
    box-shadow: 0 2px 8px rgba(18,53,91,.04);
}

.notification-dot {
    width: 7px;
    height: 7px;
    right: 6px;
    top: 6px;
}

.top-avatar {
    border: 2px solid #E0ECF8;
    box-shadow: 0 2px 7px rgba(18,53,91,.08);
}

.page {
    display: grid;
    grid-template-columns: minmax(0, 1.35fr) minmax(290px, 1fr);
    align-items: start;
    gap: 22px;
    padding: 28px;
}

.welcome,
.stats {
    grid-column: 1 / -1;
}

.welcome {
    min-height: 148px;
    margin-bottom: 0;
    padding: 28px 32px;
    border: 1px solid rgba(255,255,255,.38);
    border-radius: 16px;
    background: linear-gradient(120deg, #0C3263 0%, #14549A 67%, #2B78C5 100%);
    box-shadow: 0 8px 24px rgba(20,75,135,.12);
}

.welcome.has-banner {
    background-position: center 48%;
}

.welcome.has-banner::before {
    background: linear-gradient(90deg, rgba(7,32,67,.94) 0%, rgba(12,55,105,.79) 56%, rgba(12,55,105,.34) 100%);
}

.welcome-label {
    display: inline-block;
    font-size: 10px;
    line-height: 1.4;
    letter-spacing: 1.35px;
    text-transform: uppercase;
    color: rgba(255,255,255,.82);
}

.welcome h2 {
    font-size: clamp(24px, 2vw, 30px);
    line-height: 1.2;
    letter-spacing: -.45px;
    margin-top: 9px;
    margin-bottom: 7px;
}

.welcome p {
    max-width: 660px;
    font-size: 13px;
    line-height: 1.6;
    color: rgba(255,255,255,.9);
}

.stats {
    gap: 16px;
    margin-bottom: 0;
}

.stat {
    position: relative;
    min-height: 136px;
    padding: 19px;
    border: 1px solid #DCE9F6;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(18,53,91,.045);
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.stat:hover {
    transform: translateY(-2px);
    border-color: #C5DCF3;
    box-shadow: 0 8px 20px rgba(18,53,91,.075);
}

.stat-label {
    color: #617B98;
    font-size: 10px;
    letter-spacing: .55px;
}

.stat-number {
    color: #123F78;
    font-size: 30px;
    letter-spacing: -.5px;
    margin-top: 9px;
}

.stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #E5F0FF;
    color: #1764C0;
    font-size: 20px;
}

.stat:nth-child(2) .stat-icon { background: #F0E7FF; color: #7442C8; }
.stat:nth-child(3) .stat-icon { background: #DDF7EB; color: #159765; }
.stat:nth-child(4) .stat-icon { background: #FFF0D5; color: #C9800C; }

.stat-description {
    margin-top: 13px;
    font-size: 11px;
    color: #6E86A2;
}

.card {
    min-width: 0;
    margin-bottom: 0;
    padding: 22px;
    border: 1px solid #DCE9F6;
    border-radius: 15px;
    box-shadow: 0 4px 16px rgba(18,53,91,.045);
}

.card-heading {
    gap: 14px;
    margin-bottom: 20px;
}

.card-heading h2 {
    color: #123F78;
    font-size: 17px;
    letter-spacing: -.2px;
}

.card-heading p {
    color: #7188A3;
    font-size: 11px;
    line-height: 1.5;
}

.quick-grid {
    grid-template-columns: 1fr;
    gap: 10px;
}

.quick-link {
    min-height: 67px;
    padding: 12px 13px;
    border-color: #DFEAF6;
    border-radius: 11px;
    background: #FFFFFF;
}

.quick-link:hover {
    border-color: #BBD5F0;
    background: #FAFCFF;
    box-shadow: 0 5px 14px rgba(18,53,91,.055);
}

.quick-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #E8F2FF;
    color: #1E5A96;
}

.quick-text strong {
    color: #183E6D;
    font-size: 12px;
}

.quick-text span {
    color: #7489A1;
    font-size: 10px;
    line-height: 1.4;
}

.arrow { color: #1E5A96; }

.system-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 12px;
}

.system-item {
    min-width: 0;
    background: #F7FAFE;
    border-color: #E1EBF6;
    border-radius: 11px;
    padding: 15px;
}

.system-item span {
    color: #7187A0;
    font-size: 10px;
}

.system-item strong {
    color: #183E6D;
    font-size: 12px;
    line-height: 1.5;
}

.system-status {
    flex-shrink: 0;
    padding: 7px 10px;
    border: 1px solid #C9EEDB;
    background: #E9F9F0;
    color: #168553;
    font-size: 10px;
}

@media (max-width: 1100px) {
    .page { grid-template-columns: minmax(0, 1fr); }
    .welcome, .stats { grid-column: 1; }
    .page > .card { grid-column: 1; }
    .quick-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 800px) {
    .topbar { padding: 14px 20px; }
    .page { gap: 16px; padding: 20px; }
    .welcome { padding: 24px; }
}

@media (max-width: 600px) {
    .stats { grid-template-columns: 1fr; }
    .quick-grid, .system-grid { grid-template-columns: 1fr; }
    .welcome { min-height: 175px; }
    .card { padding: 18px; }
}



/* =========================================================
   FINAL WELCOME BANNER REFINEMENT
   Intentionally overrides the earlier banner rules above.
========================================================= */
.welcome {
    position: relative;
    isolation: isolate;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    min-height: 184px;
    padding: 28px 34px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,.18);
    border-radius: 18px;
    background:
        radial-gradient(circle at 82% 18%, rgba(94,174,255,.25), transparent 28%),
        linear-gradient(112deg, #08264D 0%, #104982 58%, #1E69AE 100%);
    box-shadow: 0 12px 28px rgba(12,48,91,.16);
}

.welcome::after {
    content: "";
    position: absolute;
    z-index: 0;
    right: 18%;
    top: -72px;
    width: 250px;
    height: 250px;
    border: 1px solid rgba(255,255,255,.13);
    border-radius: 50%;
    box-shadow: 0 0 0 26px rgba(255,255,255,.035), 0 0 0 54px rgba(255,255,255,.025);
    pointer-events: none;
}

.welcome.has-banner {
    background-size: cover;
    background-position: center 48%;
}

.welcome.has-banner::before {
    background: linear-gradient(100deg, rgba(5,24,51,.95) 0%, rgba(8,39,78,.84) 54%, rgba(12,55,105,.53) 100%);
}

.welcome-content {
    position: relative;
    z-index: 2;
    max-width: 700px;
    padding-left: 17px;
    border-left: 3px solid #E53945;
}

.welcome-label {
    display: inline-block;
    font-size: 10px;
    line-height: 1.5;
    letter-spacing: 1.7px;
    font-weight: 800;
    text-transform: uppercase;
    color: #DCEBFA;
}

.welcome h2 {
    margin: 8px 0 8px;
    font-size: clamp(27px, 2.4vw, 34px);
    line-height: 1.15;
    letter-spacing: -.65px;
    font-weight: 750;
    color: #fff;
}

.welcome p {
    max-width: 620px;
    font-size: 13px;
    line-height: 1.65;
    color: rgba(255,255,255,.88);
}

.welcome-mark {
    position: relative;
    z-index: 2;
    flex: 0 0 176px;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    min-height: 122px;
    padding: 17px 18px;
    border: 1px solid rgba(255,255,255,.27);
    border-radius: 13px;
    background: rgba(255,255,255,.09);
    box-shadow: inset 0 1px 0 rgba(255,255,255,.12);
    backdrop-filter: blur(3px);
}

.welcome-mark-kicker {
    color: #fff;
    font-size: 25px;
    line-height: 1;
    font-weight: 800;
    letter-spacing: 1.5px;
}

.welcome-mark-rule {
    width: 34px;
    height: 3px;
    margin: 10px 0;
    border-radius: 4px;
    background: #E53945;
}

.welcome-mark-title {
    color: rgba(255,255,255,.94);
    font-size: 9px;
    line-height: 1.5;
    letter-spacing: 1.15px;
    font-weight: 800;
}

.welcome-mark-foot {
    margin-top: 9px;
    color: rgba(255,255,255,.65);
    font-size: 8px;
    letter-spacing: 1.3px;
    font-weight: 700;
}

@media (max-width: 800px) {
    .welcome {
        gap: 18px;
        padding: 24px;
    }
    .welcome-mark {
        flex-basis: 145px;
        padding: 14px;
    }
    .welcome-content {
        padding-left: 13px;
    }
}

@media (max-width: 600px) {
    .welcome {
        min-height: 0;
        padding: 23px 20px;
    }
    .welcome-mark {
        display: none;
    }
    .welcome h2 {
        font-size: 25px;
    }
}


/* =========================================================
   FINAL WELCOME BANNER RIBBON OVERRIDES
========================================================= */
.welcome {
    position: relative;
    isolation: isolate;
    overflow: hidden;
}
.welcome-ribbon {
    position: absolute;
    z-index: 1;
    top: 0;
    right: 0%;
    width: 43%;
    height: 100%;
    display: block;
    pointer-events: none;
}
.welcome-content,
.welcome-mark {
    position: relative;
    z-index: 2;
}
@media (max-width: 800px) {
    .welcome-ribbon {
        right: 8%;
        width: 52%;
        opacity: .8;
    }
}
@media (max-width: 600px) {
    .welcome-ribbon {
        right: 0%;
        width: 65%;
        opacity: .55;
    }
}


/* Shared interactive topbar menus */
.top-actions, .top-user { position: relative; }
.notification, .top-avatar { cursor: pointer; }
.top-avatar { border: 0; position: relative; }
.top-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.topbar-menu {
    position: absolute; top: calc(100% + 12px); right: 0; z-index: 2000;
    width: min(320px, calc(100vw - 32px)); background: #fff;
    border: 1px solid #DCE7F2; border-radius: 13px;
    box-shadow: 0 14px 38px rgba(10,37,68,.16); padding: 8px;
    color: #172033; display: none; text-align: left;
}
.topbar-menu.open { display: block; animation: menuIn .15s ease-out; }
.topbar-menu-title { padding: 12px 13px 10px; font-weight: 800; color: #12355B; font-size: 13px; }
.topbar-menu-copy { padding: 0 13px 13px; color: #64748B; font-size: 12px; line-height: 1.55; }
.topbar-menu-divider { height: 1px; background: #E8EEF5; margin: 4px 0; }
.topbar-menu-user { padding: 12px 13px; display: flex; flex-direction: column; gap: 4px; }
.topbar-menu-user strong { color: #12355B; font-size: 13px; overflow-wrap: anywhere; }
.topbar-menu-user span { color: #64748B; font-size: 11px; }
.topbar-menu-link { display: flex; align-items: center; min-height: 42px; padding: 0 13px; border-radius: 8px; font-size: 12px; font-weight: 700; color: #12355B; text-decoration: none; }
.topbar-menu-link:hover { background: #EAF3FA; }
.topbar-menu-link.logout-link { color: #C9343D; }
.topbar-menu-link.logout-link:hover { background: #FFF0F0; }
.notification:focus-visible, .top-avatar:focus-visible { outline: 3px solid #8FC4F2; outline-offset: 3px; }
@keyframes menuIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
@media (prefers-reduced-motion: reduce) { .topbar-menu.open { animation: none; } }

</style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php include "../includes/admin_sidebar.php"; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="page-title">

            <h1>
                Dashboard
            </h1>

            <p>

                Welcome to the

                <?php echo $displaySchoolName; ?>

                School Management System.

            </p>

        </div>


        <div class="top-actions">


            <button
                type="button"
                class="notification"
            >

                🔔

                <span class="notification-dot"></span>

            </button>


            <!-- TOP USER AVATAR -->

            <div class="top-avatar">

                <?php if ($profilePicturePath !== ""): ?>

                    <img
                        src="<?php echo htmlspecialchars($profilePicturePath, ENT_QUOTES, "UTF-8"); ?>"
                        alt="Profile Picture"
                    >

                <?php else: ?>

                    <?php echo $displayInitials; ?>

                <?php endif; ?>

            </div>


        </div>


    </header>


    <!-- PAGE -->

    <section class="page">


        <!-- WELCOME -->

        <div
            class="welcome <?php echo $bannerPath !== "" ? 'has-banner' : ''; ?>"

            <?php if ($bannerPath !== ""): ?>

                style="background-image: url('<?php echo htmlspecialchars($bannerPath, ENT_QUOTES, "UTF-8"); ?>');"

            <?php endif; ?>

        >

            <!-- Fullbright College flowing ribbon artwork -->
            <svg class="welcome-ribbon" viewBox="0 0 800 240" preserveAspectRatio="none" aria-hidden="true">
                <!-- Solid blue field: its upper edge sweeps down to a sharp point at the lower-left. -->
                <path d="M 0,240
                         C 230,205 420,105 590,-24
                         L 820,-24 L 820,264 L 0,264 Z"
                      fill="#1458B8"/>
                <!-- Smooth parallel bands that follow the same rising curve. -->
                <path d="M 0,240
                         C 210,211 398,112 560,-24
                         L 590,-24
                         C 430,125 235,220 0,240 Z"
                      fill="#0C3973"/>
                <path d="M 0,240
                         C 225,208 410,109 578,-24
                         L 600,-24
                         C 440,125 245,220 0,240 Z"
                      fill="#75B8F0"/>
                <path d="M 0,240
                         C 245,205 432,100 612,-24
                         L 640,-24
                         C 470,125 265,222 0,240 Z"
                      fill="#FFFFFF"/>
                <path d="M 0,240
                         C 265,201 455,92 642,-24
                         L 675,-24
                         C 500,120 290,222 0,240 Z"
                      fill="#E31837"/>
            </svg>

            <div class="welcome-content">


                <span class="welcome-label">

                    <?php echo $displaySchoolName; ?>

                </span>


                <h2>

                    Good day,
                    <?php echo $displayUserName; ?>

                </h2>


                <p>

                    <?php echo $displaySchoolDescription; ?>

                </p>


            </div>


            <div class="welcome-mark" aria-label="School management system">
                <span class="welcome-mark-kicker">FBC</span>
                <span class="welcome-mark-rule"></span>
                <span class="welcome-mark-title">SCHOOL<br>MANAGEMENT<br>SYSTEM</span>
                <span class="welcome-mark-foot">ADMIN CONSOLE</span>
            </div>

        </div>


        <!-- STATISTICS -->

        <div class="stats">


            <!-- USERS -->

            <div class="stat">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            TOTAL USERS
                        </div>

                        <div class="stat-number">

                            <?php echo $users_count; ?>

                        </div>

                    </div>

                    <div class="stat-icon">
                        ♙
                    </div>

                </div>

                <div class="stat-description">
                    Registered system users
                </div>

            </div>


            <!-- ACTIVE USERS -->

            <div class="stat">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            ACTIVE USERS
                        </div>

                        <div class="stat-number">

                            <?php echo $active_users_count; ?>

                        </div>

                    </div>

                    <div class="stat-icon">
                        ✓
                    </div>

                </div>

                <div class="stat-description">
                    Currently active accounts
                </div>

            </div>


            <!-- ROLES -->

            <div class="stat">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            ROLES
                        </div>

                        <div class="stat-number">

                            <?php echo $roles_count; ?>

                        </div>

                    </div>

                    <div class="stat-icon">
                        ⚿
                    </div>

                </div>

                <div class="stat-description">
                    System roles configured
                </div>

            </div>


            <!-- SCHOOL YEARS -->

            <div class="stat">

                <div class="stat-top">

                    <div>

                        <div class="stat-label">
                            SCHOOL YEARS
                        </div>

                        <div class="stat-number">

                            <?php echo $school_years_count; ?>

                        </div>

                    </div>

                    <div class="stat-icon">
                        ▣
                    </div>

                </div>

                <div class="stat-description">
                    Academic years recorded
                </div>

            </div>


        </div>


        <!-- QUICK ACCESS -->

        <div class="card">


            <div class="card-heading">

                <div>

                    <h2>
                        Quick Access
                    </h2>

                    <p>
                        Frequently used administration modules
                    </p>

                </div>

            </div>


            <div class="quick-grid">


                <!-- USERS -->

                <a
                    href="/SMS/admin/users/users.php"
                    class="quick-link"
                >

                    <div class="quick-icon">
                        ♙
                    </div>

                    <div class="quick-text">

                        <strong>
                            Users
                        </strong>

                        <span>
                            Manage user accounts
                        </span>

                    </div>

                    <div class="arrow">
                        →
                    </div>

                </a>


                <!-- ROLES -->

                <a
                    href="/SMS/admin/roles/roles.php"
                    class="quick-link"
                >

                    <div class="quick-icon">
                        ⚿
                    </div>

                    <div class="quick-text">

                        <strong>
                            Roles & Permissions
                        </strong>

                        <span>
                            Control system access
                        </span>

                    </div>

                    <div class="arrow">
                        →
                    </div>

                </a>


                <!-- SCHOOL SETTINGS -->

                <a
                    href="/SMS/admin/school_settings/school_settings.php"
                    class="quick-link"
                >

                    <div class="quick-icon">
                        ⚙
                    </div>

                    <div class="quick-text">

                        <strong>
                            School Settings
                        </strong>

                        <span>
                            Manage school information
                        </span>

                    </div>

                    <div class="arrow">
                        →
                    </div>

                </a>


                <!-- SCHOOL YEAR -->

                <a
                    href="/SMS/admin/school_year/school_year.php"
                    class="quick-link"
                >

                    <div class="quick-icon">
                        ▣
                    </div>

                    <div class="quick-text">

                        <strong>
                            School Year
                        </strong>

                        <span>
                            Manage academic years
                        </span>

                    </div>

                    <div class="arrow">
                        →
                    </div>

                </a>


            </div>

        </div>


        <!-- SYSTEM OVERVIEW -->

        <div class="card">


            <div class="card-heading">

                <div>

                    <h2>
                        System Overview
                    </h2>

                    <p>
                        Current SMS administration status
                    </p>

                </div>


                <span class="system-status">
                    System Active
                </span>

            </div>


            <div class="system-grid">


                <div class="system-item">

                    <span>
                        System
                    </span>

                    <strong>
                        School Management System
                    </strong>

                </div>


                <div class="system-item">

                    <span>
                        Institution
                    </span>

                    <strong>
                        <?php echo $displaySchoolName; ?>
                    </strong>

                </div>


                <div class="system-item">

                    <span>
                        School Code
                    </span>

                    <strong>
                        <?php echo $displaySchoolCode; ?>
                    </strong>

                </div>


                <div class="system-item">

                    <span>
                        Current Module
                    </span>

                    <strong>
                        Administration
                    </strong>

                </div>


            </div>


        </div>


    </section>


</main>


<!-- =========================================================
     COLLAPSE SCRIPT
========================================================= -->





<script>
(function () {
    const userName = <?php echo json_encode((string) ($_SESSION["full_name"] ?? $currentUserName ?? $loggedInUser ?? "Administrator"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const userRole = <?php echo json_encode((string) ($_SESSION["role_name"] ?? $currentUserRole ?? $loggedInRole ?? "Administrator"), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const logoutUrl = "/SMS/logout.php";

    const topActions = document.querySelector(".top-actions");
    const topUser = document.querySelector(".top-user");
    let notificationButton = document.querySelector(".notification");
    let avatar = document.querySelector(".top-avatar");

    // Pages without a notification bell get one in the same topbar action area.
    if (!notificationButton) {
        const host = topActions || topUser;
        if (host) {
            notificationButton = document.createElement("button");
            notificationButton.type = "button";
            notificationButton.className = "notification";
            notificationButton.setAttribute("aria-label", "Open notifications");
            notificationButton.setAttribute("aria-expanded", "false");
            notificationButton.innerHTML = '🔔<span class="notification-dot" aria-hidden="true"></span>';
            if (avatar && avatar.parentNode === host) host.insertBefore(notificationButton, avatar);
            else host.prepend(notificationButton);
        }
    }

    if (notificationButton) {
        notificationButton.setAttribute("aria-haspopup", "true");
        notificationButton.setAttribute("aria-expanded", "false");
        let notificationMenu = document.getElementById("smsNotificationMenu");
        if (!notificationMenu) {
            notificationMenu = document.createElement("div");
            notificationMenu.className = "topbar-menu";
            notificationMenu.id = "smsNotificationMenu";
            notificationMenu.setAttribute("role", "dialog");
            notificationMenu.setAttribute("aria-label", "Notifications");
            notificationMenu.innerHTML = '<div class="topbar-menu-title">Notifications</div><div class="topbar-menu-copy">You’re all caught up. System notifications will appear here when available.</div>';
            notificationButton.insertAdjacentElement("afterend", notificationMenu);
        }
        notificationButton.addEventListener("click", function (event) {
            event.stopPropagation();
            const willOpen = !notificationMenu.classList.contains("open");
            closeMenus();
            if (willOpen) {
                notificationMenu.classList.add("open");
                notificationButton.setAttribute("aria-expanded", "true");
                const dot = notificationButton.querySelector(".notification-dot");
                if (dot) dot.style.display = "none";
            }
        });
    }

    // Make the current avatar a keyboard-accessible account-menu trigger.
    if (avatar) {
        avatar.setAttribute("role", "button");
        avatar.setAttribute("tabindex", "0");
        avatar.setAttribute("aria-label", "Open administrator account menu");
        avatar.setAttribute("aria-haspopup", "true");
        avatar.setAttribute("aria-expanded", "false");
        const host = avatar.parentElement;
        if (host && getComputedStyle(host).position === "static") host.style.position = "relative";

        let accountMenu = document.getElementById("smsAccountMenu");
        if (!accountMenu) {
            accountMenu = document.createElement("div");
            accountMenu.className = "topbar-menu";
            accountMenu.id = "smsAccountMenu";
            accountMenu.setAttribute("role", "menu");
            accountMenu.innerHTML =
                '<div class="topbar-menu-user"><strong></strong><span></span></div>' +
                '<div class="topbar-menu-divider"></div>' +
                '<a class="topbar-menu-link logout-link" role="menuitem" href="' + logoutUrl + '">↪ &nbsp; Log out</a>';
            avatar.insertAdjacentElement("afterend", accountMenu);
        }
        accountMenu.querySelector("strong").textContent = userName || "Administrator";
        accountMenu.querySelector("span").textContent = userRole || "Administrator";

        function toggleAccount(event) {
            if (event) event.stopPropagation();
            const willOpen = !accountMenu.classList.contains("open");
            closeMenus();
            if (willOpen) {
                accountMenu.classList.add("open");
                avatar.setAttribute("aria-expanded", "true");
            }
        }
        avatar.addEventListener("click", toggleAccount);
        avatar.addEventListener("keydown", function (event) {
            if (event.key === "Enter" || event.key === " ") {
                event.preventDefault();
                toggleAccount(event);
            }
        });
    }

    function closeMenus() {
        document.querySelectorAll(".topbar-menu.open").forEach(function (menu) {
            menu.classList.remove("open");
        });
        if (notificationButton) notificationButton.setAttribute("aria-expanded", "false");
        if (avatar) avatar.setAttribute("aria-expanded", "false");
    }

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".topbar-menu") &&
            !event.target.closest(".notification") &&
            !event.target.closest(".top-avatar")) closeMenus();
    });
    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeMenus();
    });
})();
</script>

</body>

</html>