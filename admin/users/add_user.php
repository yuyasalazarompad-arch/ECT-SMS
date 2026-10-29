<?php

require_once "../../includes/auth_check.php";

include "../../config/database.php";


/* =========================================================
   GET ACTIVE ROLES
========================================================= */

$roles = $conn->query(
    "SELECT
        role_id,
        role_name
     FROM roles
     WHERE status = 'Active'
     ORDER BY role_name ASC"
);

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
        Add User | SMS
    </title>


    <style>

        /* =====================================================
           VARIABLES
        ===================================================== */

        :root {

            --navy: #0B315E;

            --navy-dark: #09284D;

            --blue: #1E5A96;

            --light-blue: #EAF3FA;

            --background: #F4F7FB;

            --border: #E1E8F1;

            --text: #26384A;

            --muted: #6B7785;

            --success: #1D9B61;

            --danger: #D64545;

            --sidebar-width: 260px;

            --sidebar-small: 78px;

        }


        /* =====================================================
           RESET
        ===================================================== */

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


        button,
        input,
        select {

            font-family: inherit;

        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            position: fixed;

            left: 0;

            top: 0;

            bottom: 0;

            width: var(--sidebar-width);

            background:
                linear-gradient(
                    180deg,
                    #0B315E 0%,
                    #09284D 100%
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


        /* =====================================================
           SCHOOL BRAND
        ===================================================== */

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


        /* =====================================================
           SIDEBAR NAVIGATION
        ===================================================== */

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
                rgba(255,255,255,0.18);

            border-radius: 10px;

        }


        .section-title {

            padding:
                12px
                10px
                7px;

            font-size: 9px;

            font-weight: 800;

            letter-spacing: 1px;

            color:
                rgba(255,255,255,0.42);

        }


        .section-title.admin {

            margin-top: 12px;

        }


        .menu-item {

            min-height: 42px;

            padding:
                0 12px;

            margin-bottom: 4px;

            border-radius: 9px;

            display: flex;

            align-items: center;

            gap: 11px;

            color:
                rgba(255,255,255,0.78);

            font-size: 13px;

            font-weight: 600;

            transition:
                background 0.2s ease,
                color 0.2s ease;

        }


        .menu-item:hover {

            background:
                rgba(255,255,255,0.08);

            color: white;

        }


        .menu-item.active {

            background:
                rgba(255,255,255,0.13);

            color: white;

            box-shadow:
                inset 3px 0 0
                #FFFFFF;

        }


        .menu-icon {

            width: 22px;

            min-width: 22px;

            text-align: center;

            font-size: 15px;

            color:
                rgba(255,255,255,0.78);

        }


        .menu-name {

            white-space: nowrap;

            overflow: hidden;

            text-overflow: ellipsis;

        }


        .soon {

            margin-left: auto;

            padding:
                3px 6px;

            border-radius: 5px;

            background:
                rgba(255,255,255,0.08);

            color:
                rgba(255,255,255,0.45);

            font-size: 8px;

            flex-shrink: 0;

        }


        /* =====================================================
           SIDEBAR BOTTOM
        ===================================================== */

        .sidebar-bottom {

            flex-shrink: 0;

            padding: 12px;

            background:
                rgba(0,0,0,0.08);

            border-top:
                1px solid
                rgba(255,255,255,0.08);

        }


        /* =====================================================
           COLLAPSE BUTTON
        ===================================================== */

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


        /* =====================================================
           LOGOUT
        ===================================================== */

        .logout-button {

            width: 100%;

            height: 44px;

            margin-top: 9px;

            border:
                1px solid
                rgba(255,255,255,0.12);

            border-radius: 10px;

            background:
                rgba(255,255,255,0.06);

            color:
                rgba(255,255,255,0.85);

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

            font-size: 12px;

            font-weight: 700;

            transition:
                background 0.2s ease,
                color 0.2s ease;

        }


        .logout-button:hover {

            background:
                rgba(214,69,69,0.18);

            color: white;

        }


        .logout-icon {

            font-size: 15px;

        }


        /* =====================================================
           COLLAPSED SIDEBAR
        ===================================================== */

        .sidebar.collapsed {

            width: var(--sidebar-small);

        }


        .sidebar.collapsed
        .school-brand {

            justify-content: center;

            padding: 20px 10px;

        }


        .sidebar.collapsed
        .school-name {

            display: none;

        }


        .sidebar.collapsed
        .sidebar-nav {

            padding:
                18px 10px;

        }


        .sidebar.collapsed
        .section-title {

            display: none;

        }


        .sidebar.collapsed
        .menu-item {

            justify-content: center;

            padding: 0;

        }


        .sidebar.collapsed
        .menu-name,
        .sidebar.collapsed
        .soon {

            display: none;

        }


        .sidebar.collapsed
        .menu-icon {

            width: auto;

        }


        .sidebar.collapsed
        .sidebar-bottom {

            padding: 10px;

        }


        .sidebar.collapsed
        .collapse-button {

            font-size: 0;

        }


        .sidebar.collapsed
        .collapse-icon {

            font-size: 14px;

        }


        .sidebar.collapsed
        .logout-button {

            font-size: 0;

        }


        .sidebar.collapsed
        .logout-icon {

            font-size: 15px;

        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: var(--sidebar-width);

            min-height: 100vh;

            transition:
                margin-left 0.25s ease;

        }


        .sidebar.collapsed ~ .main {

            margin-left:
                var(--sidebar-small);

        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            min-height: 82px;

            background: white;

            border-bottom:
                1px solid
                var(--border);

            display: flex;

            align-items: center;

            justify-content: space-between;

            padding:
                18px 30px;

        }


        .page-title h1 {

            font-size: 25px;

            color: var(--navy);

            margin-bottom: 5px;

        }


        .page-title p {

            color: var(--muted);

            font-size: 13px;

        }


        .top-badge {

            padding:
                8px 14px;

            border-radius: 20px;

            background:
                var(--light-blue);

            color:
                var(--navy);

            font-size: 11px;

            font-weight: 700;

        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content {

            padding: 28px 30px 40px;

        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .form-card {

            max-width: 900px;

            background: white;

            border:
                1px solid
                var(--border);

            border-radius: 14px;

            padding: 28px;

            box-shadow:
                0 6px 20px
                rgba(18,53,91,0.05);

        }


        .form-card-header {

            padding-bottom: 20px;

            margin-bottom: 24px;

            border-bottom:
                1px solid
                var(--border);

        }


        .form-card-header h2 {

            color: var(--navy);

            font-size: 21px;

            margin-bottom: 6px;

        }


        .form-card-header p {

            color: var(--muted);

            font-size: 13px;

            line-height: 1.5;

        }


        /* =====================================================
           FORM GRID
        ===================================================== */

        .form-grid {

            display: grid;

            grid-template-columns:
                repeat(
                    2,
                    minmax(0, 1fr)
                );

            gap:
                20px 22px;

        }


        .form-group {

            display: flex;

            flex-direction: column;

        }


        .form-group label {

            margin-bottom: 7px;

            color: var(--navy);

            font-size: 12px;

            font-weight: 700;

        }


        .form-group input,
        .form-group select {

            width: 100%;

            min-height: 44px;

            padding:
                10px 13px;

            border:
                1px solid
                #D7E1EA;

            border-radius: 8px;

            background: white;

            color: var(--text);

            font-size: 13px;

            outline: none;

            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;

        }


        .form-group input::placeholder {

            color: #9AA7B4;

        }


        .form-group input:focus,
        .form-group select:focus {

            border-color:
                var(--blue);

            box-shadow:
                0 0 0 3px
                rgba(30,90,150,0.10);

        }


        .form-hint {

            margin-top: 6px;

            color:
                #8793A0;

            font-size: 11px;

        }


        /* =====================================================
           FORM ACTIONS
        ===================================================== */

        .form-actions {

            display: flex;

            justify-content: flex-end;

            align-items: center;

            gap: 10px;

            margin-top: 28px;

            padding-top: 22px;

            border-top:
                1px solid
                var(--border);

        }


        .btn {

            min-height: 40px;

            padding:
                9px 17px;

            border-radius: 8px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 1px solid transparent;

            font-size: 12px;

            font-weight: 700;

            cursor: pointer;

            text-decoration: none;

            transition:
                background 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease;

        }


        .btn-primary {

            background:
                var(--blue);

            color: white;

            border-color:
                var(--blue);

        }


        .btn-primary:hover {

            background:
                #174B7D;

            border-color:
                #174B7D;

        }


        .btn-secondary {

            background: white;

            color:
                #4D5D6C;

            border-color:
                #D7E1EA;

        }


        .btn-secondary:hover {

            background:
                #F4F7FB;

            border-color:
                #C5D2DF;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 850px) {

            .form-grid {

                grid-template-columns: 1fr;

            }

        }


        @media (max-width: 700px) {

            .sidebar {

                width:
                    var(--sidebar-small);

            }


            .school-name,
            .section-title,
            .menu-name,
            .soon {

                display: none;

            }


            .school-brand {

                justify-content: center;

                padding:
                    20px 10px;

            }


            .sidebar-nav {

                padding:
                    18px 10px;

            }


            .menu-item {

                justify-content: center;

                padding: 0;

            }


            .sidebar-bottom {

                padding: 10px;

            }


            .collapse-button {

                font-size: 0;

            }


            .logout-button {

                font-size: 0;

            }


            .main {

                margin-left:
                    var(--sidebar-small);

            }


            .content {

                padding:
                    20px 15px 30px;

            }


            .topbar {

                padding:
                    18px 15px;

            }


            .top-badge {

                display: none;

            }


            .form-card {

                padding: 20px;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<aside class="sidebar" id="sidebar">


    <!-- SCHOOL BRAND -->

    <div class="school-brand">


        <div class="school-logo">


            <div class="school-logo-text">

                FBC

            </div>


        </div>


        <div class="school-name">

            <strong>
                Fullbright College
            </strong>


            <span>
                School Management System
            </span>

        </div>


    </div>


    <!-- NAVIGATION -->

    <nav class="sidebar-nav">


        <!-- MAIN -->

        <div class="section-title">

            MAIN

        </div>


        <a
            href="/SMS/admin/dashboard.php"
            class="menu-item"
        >

            <span class="menu-icon">
                ⌂
            </span>


            <span class="menu-name">
                Dashboard
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ♟
            </span>


            <span class="menu-name">
                Students
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ▣
            </span>


            <span class="menu-name">
                Enrollment
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ♙
            </span>


            <span class="menu-name">
                Faculty
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ◷
            </span>


            <span class="menu-name">
                Attendance
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ▤
            </span>


            <span class="menu-name">
                Grades
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <a
            href="#"
            class="menu-item"
            onclick="return false;"
        >

            <span class="menu-icon">
                ▥
            </span>


            <span class="menu-name">
                Reports
            </span>


            <span class="soon">
                Soon
            </span>

        </a>


        <!-- ADMINISTRATION -->

        <div class="section-title admin">

            ADMINISTRATION

        </div>


        <!-- USERS -->

        <a
            href="/SMS/admin/users/users.php"
            class="menu-item active"
        >

            <span class="menu-icon">
                ♙
            </span>


            <span class="menu-name">
                Users
            </span>

        </a>


        <!-- ROLES -->

        <a
            href="/SMS/admin/roles/roles.php"
            class="menu-item"
        >

            <span class="menu-icon">
                ⚿
            </span>


            <span class="menu-name">
                Roles & Permissions
            </span>

        </a>


        <!-- SCHOOL SETTINGS -->

        <a
            href="/SMS/admin/school_settings/school_settings.php"
            class="menu-item"
        >

            <span class="menu-icon">
                ⚙
            </span>


            <span class="menu-name">
                School Settings
            </span>

        </a>


        <!-- SCHOOL YEAR -->

        <a
            href="/SMS/admin/school_year/school_year.php"
            class="menu-item"
        >

            <span class="menu-icon">
                ▣
            </span>


            <span class="menu-name">
                School Year
            </span>

        </a>


    </nav>


    <!-- SIDEBAR BOTTOM -->

    <div class="sidebar-bottom">


        <!-- COLLAPSE -->

        <button
            type="button"
            class="collapse-button"
            id="collapseButton"
        >

            <span
                class="collapse-icon"
                id="collapseIcon"
            >
                ◀
            </span>


            <span
                id="collapseText"
            >
                Collapse
            </span>

        </button>


        <!-- LOGOUT -->

        <a
            href="/SMS/auth/logout.php"
            class="logout-button"
        >

            <span class="logout-icon">
                ↪
            </span>


            <span>
                Logout
            </span>

        </a>


    </div>


</aside>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="main" id="main">


    <!-- TOPBAR -->

    <header class="topbar">


        <div class="page-title">


            <h1>
                Add User
            </h1>


            <p>
                Create a new system user account
            </p>


        </div>


        <div class="top-badge">

            Administration

        </div>


    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- FORM CARD -->

        <div class="form-card">


            <!-- CARD HEADER -->

            <div class="form-card-header">


                <h2>
                    User Information
                </h2>


                <p>

                    Enter the account details and assign
                    the appropriate system role.

                </p>


            </div>


            <!-- FORM -->

            <form
                method="POST"
                action="save_user.php"
            >


                <div class="form-grid">


                    <!-- FULL NAME -->

                    <div class="form-group">


                        <label for="fullName">

                            Full Name

                        </label>


                        <input
                            type="text"
                            id="fullName"
                            name="full_name"
                            placeholder="Enter full name"
                            autocomplete="name"
                            required
                        >


                    </div>


                    <!-- USERNAME -->

                    <div class="form-group">


                        <label for="username">

                            Username

                        </label>


                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter username"
                            autocomplete="username"
                            required
                        >


                    </div>


                    <!-- EMAIL -->

                    <div class="form-group">


                        <label for="email">

                            Email

                        </label>


                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="Enter email address"
                            autocomplete="email"
                            required
                        >


                    </div>


                    <!-- PASSWORD -->

                    <div class="form-group">


                        <label for="password">

                            Password

                        </label>


                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter password"
                            autocomplete="new-password"
                            required
                        >


                        <span class="form-hint">

                            Use a strong password for this account.

                        </span>


                    </div>


                    <!-- ROLE -->

                    <div class="form-group">


                        <label for="role">

                            Role

                        </label>


                        <select
                            id="role"
                            name="role_id"
                            required
                        >


                            <option value="">

                                Select a role

                            </option>


                            <?php if ($roles && $roles->num_rows > 0): ?>


                                <?php while ($role = $roles->fetch_assoc()): ?>


                                    <option
                                        value="<?php
                                            echo (int) $role["role_id"];
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $role["role_name"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                        ?>

                                    </option>


                                <?php endwhile; ?>


                            <?php endif; ?>


                        </select>


                    </div>


                    <!-- STATUS -->

                    <div class="form-group">


                        <label for="status">

                            Status

                        </label>


                        <select
                            id="status"
                            name="status"
                            required
                        >


                            <option value="Active">

                                Active

                            </option>


                            <option value="Inactive">

                                Inactive

                            </option>


                        </select>


                    </div>


                </div>


                <!-- ACTIONS -->

                <div class="form-actions">


                    <a
                        href="users.php"
                        class="btn btn-secondary"
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Save User

                    </button>


                </div>


            </form>


        </div>


    </section>


</main>


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


const sidebar =
    document.getElementById("sidebar");


const collapseButton =
    document.getElementById("collapseButton");


const collapseIcon =
    document.getElementById("collapseIcon");


const collapseText =
    document.getElementById("collapseText");


/* =========================================================
   LOAD SAVED SIDEBAR STATE
========================================================= */

const savedSidebarState =
    localStorage.getItem(
        "smsSidebarCollapsed"
    );


if (savedSidebarState === "true") {

    sidebar.classList.add("collapsed");

    collapseIcon.textContent = "▶";

    collapseText.textContent = "Expand";

}


/* =========================================================
   COLLAPSE SIDEBAR
========================================================= */

collapseButton.addEventListener(
    "click",
    function () {


        sidebar.classList.toggle(
            "collapsed"
        );


        const isCollapsed =
            sidebar.classList.contains(
                "collapsed"
            );


        localStorage.setItem(
            "smsSidebarCollapsed",
            isCollapsed
        );


        if (isCollapsed) {

            collapseIcon.textContent = "▶";

            collapseText.textContent = "Expand";

        } else {

            collapseIcon.textContent = "◀";

            collapseText.textContent = "Collapse";

        }


    }
);


</script>


</body>

</html>