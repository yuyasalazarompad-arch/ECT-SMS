<?php

require_once "../../includes/auth_check.php";
require_once "../../config/database.php";


/*
|--------------------------------------------------------------------------
| GET SCHOOL SETTINGS
|--------------------------------------------------------------------------
*/

$result = $conn->query("
    SELECT *
    FROM school_settings
    ORDER BY setting_id ASC
    LIMIT 1
");

if (!$result || $result->num_rows === 0) {
    die("School settings not found.");
}

$settings = $result->fetch_assoc();


/*
|--------------------------------------------------------------------------
| SAFE VALUES
|--------------------------------------------------------------------------
*/

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}


$schoolName = e(
    $settings["school_name"] ?? ""
);

$schoolCode = e(
    $settings["school_code"] ?? ""
);

$tagline = e(
    $settings["school_tagline"] ?? ""
);

$address = e(
    $settings["address"] ?? ""
);

$contact = e(
    $settings["contact_number"] ?? ""
);

$email = e(
    $settings["email"] ?? ""
);

$website = e(
    $settings["website"] ?? ""
);

$facebook = e(
    $settings["facebook_url"] ?? ""
);

$instagram = e(
    $settings["instagram_url"] ?? ""
);

$otherSocial = e(
    $settings["other_social_url"] ?? ""
);

$description = e(
    $settings["description"] ?? ""
);


$logoFilename =
    $settings["logo_filename"] ?? "";

$bannerFilename =
    $settings["banner_filename"] ?? "";


/*
|--------------------------------------------------------------------------
| IMAGE PATHS
|--------------------------------------------------------------------------
*/

$logoPath = "";

if ($logoFilename !== "") {

    $logoPath =
        "/SMS/assets/uploads/school/" .
        rawurlencode($logoFilename);
}


$bannerPath = "";

if ($bannerFilename !== "") {

    $bannerPath =
        "/SMS/assets/uploads/school/" .
        rawurlencode($bannerFilename);
}


/*
|--------------------------------------------------------------------------
| SCHOOL INITIALS
|--------------------------------------------------------------------------
*/

$schoolInitials = "FBC";

if (!empty($settings["school_code"])) {

    $schoolInitials =
        strtoupper(
            substr(
                $settings["school_code"],
                0,
                4
            )
        );
}


$currentUserName = trim((string) ($_SESSION["full_name"] ?? "Administrator"));
$currentUserRole = (string) ($_SESSION["role_name"] ?? "Administrator");

/*
|--------------------------------------------------------------------------
| ADMIN INITIALS
|--------------------------------------------------------------------------
*/

$nameParts = preg_split(
    "/\s+/",
    trim($currentUserName)
);

$adminInitials = "";

foreach ($nameParts as $part) {

    if ($part !== "") {

        $adminInitials .= strtoupper(
            substr($part, 0, 1)
        );
    }
}

$adminInitials =
    substr($adminInitials, 0, 2);

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>School Settings | SMS</title>


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }


        body {

            font-family:
                "Segoe UI",
                Arial,
                sans-serif;

            background: #f5f7fb;

            color: #1f2937;

            min-height: 100vh;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed;

            top: 0;
            left: 0;
            bottom: 0;

            width: 255px;

            background: #12355B;

            color: white;

            display: flex;

            flex-direction: column;

            z-index: 1000;

            transition:
                width 0.25s ease,
                transform 0.25s ease;

            box-shadow:
                3px 0 15px rgba(0, 0, 0, 0.08);
        }


        .sidebar.collapsed {

            width: 78px;
        }


        /* BRAND */

        .brand {

            height: 82px;

            display: flex;

            align-items: center;

            gap: 12px;

            padding: 0 20px;

            border-bottom:
                1px solid
                rgba(255,255,255,0.10);

            overflow: hidden;

            flex-shrink: 0;
        }


        .brand-logo {

            width: 42px;
            height: 42px;

            border-radius: 10px;

            background: white;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 13px;

            font-weight: 800;

            overflow: hidden;

            flex-shrink: 0;
        }


        .brand-logo img {

            width: 100%;
            height: 100%;

            object-fit: contain;

            padding: 3px;
        }


        .brand-text {

            min-width: 0;

            white-space: nowrap;

            transition:
                opacity 0.2s ease;
        }


        .brand-title {

            font-size: 16px;

            font-weight: 700;

            letter-spacing: 0.2px;
        }


        .brand-subtitle {

            font-size: 11px;

            color:
                rgba(255,255,255,0.65);

            margin-top: 2px;
        }


        .sidebar.collapsed .brand-text {

            opacity: 0;

            width: 0;

            overflow: hidden;
        }


        /* NAVIGATION */

        .nav {

            padding: 20px 12px;

            flex: 1;

            overflow-y: auto;
        }


        .nav-section {

            font-size: 10px;

            font-weight: 700;

            text-transform: uppercase;

            letter-spacing: 1px;

            color:
                rgba(255,255,255,0.45);

            padding:
                0 12px 9px;

            white-space: nowrap;

            overflow: hidden;

            transition:
                opacity 0.2s ease;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 13px;

            min-height: 45px;

            padding: 0 13px;

            margin-bottom: 5px;

            color:
                rgba(255,255,255,0.78);

            text-decoration: none;

            border-radius: 9px;

            font-size: 13px;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .nav-link:hover {

            background:
                rgba(255,255,255,0.08);

            color: white;
        }


        .nav-link.active {

            background: #1E5A96;

            color: white;

            font-weight: 600;

            box-shadow:
                inset 3px 0 0 #D9A441;
        }


        .nav-icon {

            width: 22px;

            text-align: center;

            font-size: 17px;

            flex-shrink: 0;
        }


        .nav-label {

            white-space: nowrap;

            overflow: hidden;

            transition:
                opacity 0.2s ease;
        }


        .sidebar.collapsed .nav-label {

            opacity: 0;

            width: 0;
        }


        .sidebar.collapsed .nav-section {

            opacity: 0;

            height: 0;

            padding: 0;
        }


        .sidebar.collapsed .nav-link {

            justify-content: center;

            padding: 0;
        }


        /* PROFILE */

        .sidebar-profile {

            padding: 14px;

            border-top:
                1px solid
                rgba(255,255,255,0.10);

            flex-shrink: 0;
        }


        .profile-box {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 10px;

            border-radius: 10px;

            background:
                rgba(255,255,255,0.06);

            overflow: hidden;
        }


        .profile-avatar {

            width: 36px;
            height: 36px;

            border-radius: 50%;

            background: #D9A441;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 800;

            flex-shrink: 0;
        }


        .profile-info {

            min-width: 0;

            white-space: nowrap;
        }


        .profile-name {

            font-size: 12px;

            font-weight: 600;

            overflow: hidden;

            text-overflow: ellipsis;
        }


        .profile-role {

            font-size: 10px;

            color:
                rgba(255,255,255,0.55);

            margin-top: 2px;
        }


        .sidebar.collapsed .profile-info {

            display: none;
        }


        .logout {

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 7px;

            margin-top: 9px;

            height: 38px;

            border-radius: 8px;

            color:
                rgba(255,255,255,0.70);

            text-decoration: none;

            font-size: 12px;

            transition:
                background 0.2s ease,
                color 0.2s ease;
        }


        .logout:hover {

            background:
                rgba(255,255,255,0.08);

            color: white;
        }


        .sidebar.collapsed
        .logout span:last-child {

            display: none;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: 255px;

            min-height: 100vh;

            transition:
                margin-left 0.25s ease;
        }


        .main.expanded {

            margin-left: 78px;
        }


        /* TOPBAR */

        .topbar {

            height: 70px;

            background: white;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding: 0 28px;
        }


        .topbar-left {

            display: flex;

            align-items: center;

            gap: 16px;
        }


        .toggle-btn {

            width: 38px;
            height: 38px;

            border: 1px solid #e5e7eb;

            background: white;

            border-radius: 8px;

            cursor: pointer;

            font-size: 18px;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .toggle-btn:hover {

            background: #f3f6fa;
        }


        .page-title {

            font-size: 18px;

            font-weight: 700;

            color: #12355B;
        }


        .top-user {

            display: flex;

            align-items: center;

            gap: 10px;
        }


        .top-avatar {

            width: 38px;
            height: 38px;

            border-radius: 50%;

            background: #EAF3FA;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 12px;

            font-weight: 800;
        }


        .top-user-name {

            font-size: 12px;

            font-weight: 600;

            color: #374151;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            padding: 28px;
        }


        .settings-page {

            width: 100%;

            max-width: 1180px;

            margin: 0 auto;
        }


        .breadcrumb {

            font-size: 12px;

            color: #6b7280;

            margin-bottom: 7px;
        }


        .breadcrumb strong {

            color: #12355B;
        }


        .settings-header {

            margin-bottom: 24px;
        }


        .settings-header h1 {

            margin: 0 0 6px;

            color: #12355B;

            font-size: 26px;

            font-weight: 700;

            letter-spacing: -0.3px;
        }


        .settings-header p {

            margin: 0;

            color: #6b7280;

            font-size: 13px;

            line-height: 1.5;
        }


        /* =====================================================
           PROFILE PREVIEW
        ===================================================== */

        .profile-preview {

            display: flex;

            align-items: center;

            gap: 16px;

            padding: 16px;

            margin-bottom: 20px;

            border-radius: 12px;

            background: white;

            border: 1px solid #e2e8f0;

            box-shadow:
                0 3px 12px
                rgba(18,53,91,0.035);
        }


        .profile-logo {

            width: 64px;
            height: 64px;

            flex: 0 0 64px;

            border-radius: 12px;

            background: white;

            border: 1px solid #dce4ed;

            overflow: hidden;

            display: flex;

            align-items: center;

            justify-content: center;
        }


        .profile-logo img {

            width: 100%;
            height: 100%;

            object-fit: contain;

            padding: 5px;
        }


        .profile-logo-fallback {

            width: 100%;
            height: 100%;

            background: #12355B;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            font-weight: 700;
        }


        .profile-information {

            min-width: 0;
        }


        .profile-information h3 {

            margin: 0 0 4px;

            color: #12355B;

            font-size: 15px;

            font-weight: 700;
        }


        .profile-information p {

            margin: 0;

            color: #718096;

            font-size: 11px;
        }


        /* =====================================================
           CARDS
        ===================================================== */

        .settings-card {

            background: white;

            border: 1px solid #e5eaf0;

            border-radius: 14px;

            padding: 26px;

            margin-bottom: 20px;

            box-shadow:
                0 4px 16px
                rgba(18,53,91,0.045);
        }


        /* =====================================================
           SECTION HEADING
        ===================================================== */

        .section-heading {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            margin-bottom: 22px;
        }


        .section-icon {

            width: 36px;
            height: 36px;

            flex: 0 0 36px;

            border-radius: 9px;

            background: #eef5fb;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 14px;

            font-weight: 700;
        }


        .section-title {

            margin: 0 0 4px;

            color: #12355B;

            font-size: 17px;

            font-weight: 700;
        }


        .section-description {

            margin: 0;

            color: #7a8491;

            font-size: 12px;

            line-height: 1.5;
        }


        /* =====================================================
           BRANDING
        ===================================================== */

        .branding-layout {

            display: grid;

            grid-template-columns:
                300px minmax(0, 1fr);

            gap: 22px;
        }


        .branding-box {

            border: 1px solid #e2e8f0;

            border-radius: 12px;

            background: #f8fafc;

            padding: 18px;
        }


        .branding-label {

            margin-bottom: 5px;

            color: #12355B;

            font-size: 13px;

            font-weight: 700;
        }


        .branding-help {

            margin-bottom: 15px;

            color: #7b8491;

            font-size: 11px;

            line-height: 1.5;
        }


        /* =====================================================
           LOGO
        ===================================================== */

        .logo-preview-wrap {

            width: 150px;
            height: 150px;

            margin-bottom: 16px;

            border-radius: 16px;

            background: white;

            border: 1px solid #dce4ed;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;
        }


        .logo-preview {

            width: 100%;
            height: 100%;

            object-fit: contain;

            padding: 10px;
        }


        .logo-placeholder {

            width: 100%;
            height: 100%;

            background: #12355B;

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

            font-weight: 700;

            letter-spacing: 1px;
        }


        /* =====================================================
           BANNER
        ===================================================== */

        .banner-preview-wrap {

            width: 100%;
            height: 150px;

            margin-bottom: 16px;

            border-radius: 12px;

            overflow: hidden;

            background: #12355B;

            border: 1px solid #dce4ed;
        }


        .banner-preview {

            width: 100%;
            height: 100%;

            object-fit: cover;

            display: block;
        }


        .banner-placeholder {

            width: 100%;
            height: 100%;

            background:
                linear-gradient(
                    135deg,
                    #12355B,
                    #2d6ca2
                );

            display: flex;

            align-items: center;

            justify-content: center;

            text-align: center;

            padding: 20px;

            color: white;
        }


        .banner-placeholder strong {

            display: block;

            font-size: 20px;

            margin-bottom: 4px;
        }


        .banner-placeholder span {

            font-size: 11px;

            opacity: 0.85;
        }


        /* =====================================================
           FILE INPUT
        ===================================================== */

        .file-input {

            width: 100%;

            font-size: 11px;

            color: #4b5563;
        }


        .field-note {

            margin-top: 7px;

            color: #8a94a3;

            font-size: 10px;

            line-height: 1.4;
        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .settings-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 18px 20px;
        }


        .full-width {

            grid-column: 1 / -1;
        }


        .form-group {

            display: flex;

            flex-direction: column;

            min-width: 0;
        }


        .form-group label {

            margin-bottom: 7px;

            color: #374151;

            font-size: 12px;

            font-weight: 600;
        }


        .form-group input,
        .form-group textarea {

            width: 100%;

            padding: 11px 13px;

            border: 1px solid #d6dde6;

            border-radius: 8px;

            background: white;

            color: #1f2937;

            font-family: inherit;

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s,
                box-shadow 0.2s;
        }


        .form-group input::placeholder,
        .form-group textarea::placeholder {

            color: #a0a8b3;
        }


        .form-group input:focus,
        .form-group textarea:focus {

            border-color: #2d6ca2;

            box-shadow:
                0 0 0 3px
                rgba(45,108,162,0.10);
        }


        .form-group textarea {

            min-height: 130px;

            resize: vertical;

            line-height: 1.5;
        }


        /* =====================================================
           SOCIAL LINKS
        ===================================================== */

        .social-input {

            position: relative;
        }


        .social-badge {

            position: absolute;

            left: 10px;
            top: 50%;

            transform: translateY(-50%);

            width: 22px;
            height: 22px;

            border-radius: 6px;

            background: #eef5fb;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 10px;

            font-weight: 700;

            pointer-events: none;
        }


        .social-input input {

            padding-left: 40px;
        }


        /* =====================================================
           SAVE AREA
        ===================================================== */

        .save-area {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            padding-top: 8px;
        }


        .save-note {

            color: #7b8491;

            font-size: 11px;

            line-height: 1.4;
        }


        .save-button {

            border: none;

            border-radius: 8px;

            padding: 11px 21px;

            background: #12355B;

            color: white;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;

            transition:
                background 0.2s,
                transform 0.15s;
        }


        .save-button:hover {

            background: #1E5A96;
        }


        .save-button:active {

            transform: translateY(1px);
        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 950px) {

            .branding-layout {

                grid-template-columns: 1fr;
            }
        }


        @media (max-width: 760px) {

            .sidebar {

                transform:
                    translateX(-100%);

                width: 255px;
            }


            .sidebar.mobile-open {

                transform:
                    translateX(0);
            }


            .main,
            .main.expanded {

                margin-left: 0;
            }


            .content {

                padding: 20px 16px;
            }


            .topbar {

                padding: 0 16px;
            }


            .top-user-name {

                display: none;
            }


            .settings-grid {

                grid-template-columns: 1fr;
            }


            .full-width {

                grid-column: auto;
            }


            .save-area {

                align-items: stretch;

                flex-direction: column;
            }


            .save-button {

                width: 100%;
            }
        }


        @media (max-width: 480px) {

            .settings-header h1 {

                font-size: 22px;
            }


            .settings-card {

                padding: 16px;

                border-radius: 11px;
            }


            .profile-preview {

                align-items: flex-start;
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

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- YOUR SCHOOL SETTINGS CONTENT -->


<!-- =====================================================
     SIDEBAR
===================================================== -->



<!-- =====================================================
     MAIN
===================================================== -->

<main
    class="main"
    id="main"
>


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="topbar-left">


            


            <div class="page-title">
                School Settings
            </div>


        </div>


        <div class="top-user">


            <div class="top-avatar">

                <?= e($adminInitials) ?>

            </div>


            <div class="top-user-name">

                <?= e($currentUserName) ?>

            </div>


        </div>


    </header>


    <!-- =====================================================
         CONTENT
    ===================================================== -->

    <section class="content">


        <div class="settings-page">


            <!-- BREADCRUMB -->

            <div class="breadcrumb">

                Administration
                /
                <strong>
                    School Settings
                </strong>

            </div>


            <!-- HEADER -->

            <div class="settings-header">


                <h1>
                    School Settings
                </h1>


                <p>
                    Manage the official school profile,
                    information, branding, and online presence
                    used throughout SMS.
                </p>


            </div>


            <!-- =================================================
                 SCHOOL PROFILE
            ================================================== -->

            <div class="profile-preview">


                <div class="profile-logo">


                    <?php if ($logoPath !== ""): ?>

                        <img
                            src="<?= e($logoPath) ?>"
                            alt="School Logo"
                        >

                    <?php else: ?>

                        <div class="profile-logo-fallback">

                            <?= e($schoolInitials) ?>

                        </div>

                    <?php endif; ?>


                </div>


                <div class="profile-information">


                    <h3>

                        <?php

                        echo $schoolName !== ""
                            ? $schoolName
                            : "School Profile";

                        ?>

                    </h3>


                    <p>


                        <?php if ($schoolCode !== ""): ?>

                            <?= $schoolCode ?>

                        <?php endif; ?>


                        <?php if (
                            $schoolCode !== "" &&
                            $tagline !== ""
                        ): ?>

                            &nbsp;•&nbsp;

                        <?php endif; ?>


                        <?php if ($tagline !== ""): ?>

                            <?= $tagline ?>

                        <?php endif; ?>


                    </p>


                </div>


            </div>


            <!-- =================================================
                 FORM
            ================================================== -->

            <form
                method="POST"
                action="save_settings.php"
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="setting_id"
                    value="<?= (int) $settings["setting_id"] ?>"
                >


                <!-- =================================================
                     SCHOOL BRANDING
                ================================================== -->

                <div class="settings-card">


                    <div class="section-heading">


                        <div class="section-icon">
                            ◆
                        </div>


                        <div>


                            <h2 class="section-title">
                                School Branding
                            </h2>


                            <p class="section-description">
                                Manage the official logo and cover
                                image that can be displayed
                                throughout the system.
                            </p>


                        </div>


                    </div>


                    <div class="branding-layout">


                        <!-- LOGO -->

                        <div class="branding-box">


                            <div class="branding-label">
                                School Logo
                            </div>


                            <div class="branding-help">

                                Use the official school logo.
                                PNG, JPG, or WEBP is recommended.

                            </div>


                            <div
                                class="logo-preview-wrap"
                                id="logoPreviewWrap"
                            >


                                <?php if ($logoPath !== ""): ?>

                                    <img
                                        src="<?= e($logoPath) ?>"
                                        alt="Current School Logo"
                                        class="logo-preview"
                                        id="logoPreview"
                                    >

                                <?php else: ?>

                                    <div
                                        class="logo-placeholder"
                                        id="logoPlaceholder"
                                    >
                                        <?= e($schoolInitials) ?>
                                    </div>


                                    <img
                                        src=""
                                        alt="Logo Preview"
                                        class="logo-preview"
                                        id="logoPreview"
                                        style="display:none;"
                                    >

                                <?php endif; ?>


                            </div>


                            <input
                                type="file"
                                name="school_logo"
                                id="school_logo"
                                class="file-input"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <div class="field-note">

                                Maximum file size: 2 MB.

                            </div>


                        </div>


                        <!-- BANNER -->

                        <div class="branding-box">


                            <div class="branding-label">
                                School Banner / Cover
                            </div>


                            <div class="branding-help">

                                This can later be used on the
                                dashboard, login page, reports,
                                and school profile.

                            </div>


                            <div
                                class="banner-preview-wrap"
                                id="bannerPreviewWrap"
                            >


                                <?php if ($bannerPath !== ""): ?>

                                    <img
                                        src="<?= e($bannerPath) ?>"
                                        alt="Current School Banner"
                                        class="banner-preview"
                                        id="bannerPreview"
                                    >

                                <?php else: ?>

                                    <div
                                        class="banner-placeholder"
                                        id="bannerPlaceholder"
                                    >

                                        <div>

                                            <strong>

                                                <?= $schoolName !== ""
                                                    ? $schoolName
                                                    : "Fullbright College"
                                                ?>

                                            </strong>


                                            <span>
                                                School Banner / Cover
                                            </span>

                                        </div>

                                    </div>


                                    <img
                                        src=""
                                        alt="Banner Preview"
                                        class="banner-preview"
                                        id="bannerPreview"
                                        style="display:none;"
                                    >

                                <?php endif; ?>


                            </div>


                            <input
                                type="file"
                                name="school_banner"
                                id="school_banner"
                                class="file-input"
                                accept=".jpg,.jpeg,.png,.webp"
                            >


                            <div class="field-note">

                                Maximum file size: 4 MB.

                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     SCHOOL INFORMATION
                ================================================== -->

                <div class="settings-card">


                    <div class="section-heading">


                        <div class="section-icon">
                            ID
                        </div>


                        <div>

                            <h2 class="section-title">
                                School Information
                            </h2>


                            <p class="section-description">

                                Official identity and contact
                                information for the institution.

                            </p>

                        </div>


                    </div>


                    <div class="settings-grid">


                        <div class="form-group">

                            <label for="school_name">
                                School Name
                            </label>


                            <input
                                type="text"
                                id="school_name"
                                name="school_name"
                                value="<?= $schoolName ?>"
                                placeholder="Enter official school name"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="school_code">
                                School Code
                            </label>


                            <input
                                type="text"
                                id="school_code"
                                name="school_code"
                                value="<?= $schoolCode ?>"
                                placeholder="Example: FBC"
                            >

                        </div>


                        <div class="form-group full-width">

                            <label for="school_tagline">
                                School Tagline / Motto
                            </label>


                            <input
                                type="text"
                                id="school_tagline"
                                name="school_tagline"
                                value="<?= $tagline ?>"
                                placeholder="Enter the official school tagline or motto"
                            >

                        </div>


                        <div class="form-group full-width">

                            <label for="address">
                                School Address
                            </label>


                            <input
                                type="text"
                                id="address"
                                name="address"
                                value="<?= $address ?>"
                                placeholder="Enter complete school address"
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
                                value="<?= $contact ?>"
                                placeholder="Enter official contact number"
                            >

                        </div>


                        <div class="form-group">

                            <label for="email">
                                Official Email
                            </label>


                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= $email ?>"
                                placeholder="school@example.com"
                            >

                        </div>


                        <div class="form-group full-width">

                            <label for="website">
                                Official Website
                            </label>


                            <input
                                type="url"
                                id="website"
                                name="website"
                                value="<?= $website ?>"
                                placeholder="https://www.example.com"
                            >

                        </div>


                    </div>


                </div>


                <!-- =================================================
                     SOCIAL MEDIA
                ================================================== -->

                <div class="settings-card">


                    <div class="section-heading">


                        <div class="section-icon">
                            @
                        </div>


                        <div>

                            <h2 class="section-title">
                                Official Social / Online Pages
                            </h2>


                            <p class="section-description">

                                Connect the official online pages
                                of the school with the system.

                            </p>

                        </div>


                    </div>


                    <div class="settings-grid">


                        <div class="form-group">


                            <label for="facebook_url">
                                Facebook Page
                            </label>


                            <div class="social-input">


                                <div class="social-badge">
                                    f
                                </div>


                                <input
                                    type="url"
                                    id="facebook_url"
                                    name="facebook_url"
                                    value="<?= $facebook ?>"
                                    placeholder="https://facebook.com/..."
                                >


                            </div>


                        </div>


                        <div class="form-group">


                            <label for="instagram_url">
                                Instagram
                            </label>


                            <div class="social-input">


                                <div class="social-badge">
                                    ◎
                                </div>


                                <input
                                    type="url"
                                    id="instagram_url"
                                    name="instagram_url"
                                    value="<?= $instagram ?>"
                                    placeholder="https://instagram.com/..."
                                >


                            </div>


                        </div>


                        <div class="form-group full-width">


                            <label for="other_social_url">
                                Other Official Page
                            </label>


                            <div class="social-input">


                                <div class="social-badge">
                                    +
                                </div>


                                <input
                                    type="url"
                                    id="other_social_url"
                                    name="other_social_url"
                                    value="<?= $otherSocial ?>"
                                    placeholder="YouTube, LinkedIn, or another official page"
                                >


                            </div>


                        </div>


                    </div>


                </div>


                <!-- =================================================
                     DESCRIPTION
                ================================================== -->

                <div class="settings-card">


                    <div class="section-heading">


                        <div class="section-icon">
                            ≡
                        </div>


                        <div>

                            <h2 class="section-title">
                                School Description
                            </h2>


                            <p class="section-description">

                                Provide a short official description
                                of the institution.

                            </p>

                        </div>


                    </div>


                    <div class="form-group">


                        <label for="description">
                            About the School
                        </label>


                        <textarea
                            id="description"
                            name="description"
                            placeholder="Enter a short official description about the school..."
                        ><?= $description ?></textarea>


                    </div>


                    <div class="save-area">


                        <div class="save-note">

                            Changes made here will be used by
                            school-branded areas of SMS.

                        </div>


                        <button
                            type="submit"
                            class="save-button"
                        >

                            Save School Settings

                        </button>


                    </div>


                </div>


            </form>


        </div>


    </section>


</main>


<script>


/*
|--------------------------------------------------------------------------
| LOGO LIVE PREVIEW
|--------------------------------------------------------------------------
*/

const logoInput =
    document.getElementById(
        "school_logo"
    );

const logoPreview =
    document.getElementById(
        "logoPreview"
    );

const logoPlaceholder =
    document.getElementById(
        "logoPlaceholder"
    );


if (logoInput) {

    logoInput.addEventListener(
        "change",
        function () {

            const file =
                this.files &&
                this.files[0];


            if (!file) {
                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {


                    if (logoPreview) {

                        logoPreview.src =
                            event.target.result;

                        logoPreview.style.display =
                            "block";
                    }


                    if (logoPlaceholder) {

                        logoPlaceholder.style.display =
                            "none";
                    }

                };


            reader.readAsDataURL(file);

        }
    );
}


/*
|--------------------------------------------------------------------------
| BANNER LIVE PREVIEW
|--------------------------------------------------------------------------
*/

const bannerInput =
    document.getElementById(
        "school_banner"
    );

const bannerPreview =
    document.getElementById(
        "bannerPreview"
    );

const bannerPlaceholder =
    document.getElementById(
        "bannerPlaceholder"
    );


if (bannerInput) {

    bannerInput.addEventListener(
        "change",
        function () {

            const file =
                this.files &&
                this.files[0];


            if (!file) {
                return;
            }


            const reader =
                new FileReader();


            reader.onload =
                function (event) {


                    if (bannerPreview) {

                        bannerPreview.src =
                            event.target.result;

                        bannerPreview.style.display =
                            "block";
                    }


                    if (bannerPlaceholder) {

                        bannerPlaceholder.style.display =
                            "none";
                    }

                };


            reader.readAsDataURL(file);

        }
    );
}


</script>



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