<?php

require_once "../../includes/auth_check.php";
require_once "../../config/database.php";


/*
|--------------------------------------------------------------------------
| FETCH SCHOOL YEARS
|--------------------------------------------------------------------------
*/

$search = trim($_GET["search"] ?? "");
$statusFilter = trim($_GET["status"] ?? "");


$sql = "
    SELECT
        school_year_id,
        school_year,
        start_date,
        end_date,
        status,
        created_at
    FROM school_years
    WHERE 1=1
";

$params = [];
$types = "";


if ($search !== "") {

    $sql .= "
        AND (
            school_year LIKE ?
            OR status LIKE ?
        )
    ";

    $searchValue = "%" . $search . "%";

    $params[] = $searchValue;
    $params[] = $searchValue;

    $types .= "ss";
}


if (
    $statusFilter !== "" &&
    in_array(
        $statusFilter,
        ["Active", "Upcoming", "Archived"],
        true
    )
) {

    $sql .= " AND status = ?";

    $params[] = $statusFilter;
    $types .= "s";
}


$sql .= "
    ORDER BY
        start_date DESC
";


$stmt = $conn->prepare($sql);


if (!empty($params)) {

    $stmt->bind_param(
        $types,
        ...$params
    );
}


$stmt->execute();

$result = $stmt->get_result();

$schoolYears = [];

while ($row = $result->fetch_assoc()) {

    $schoolYears[] = $row;
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$totalSchoolYears = 0;
$activeSchoolYears = 0;
$upcomingSchoolYears = 0;
$archivedSchoolYears = 0;


$countResult = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Active') AS active_count,
        SUM(status = 'Upcoming') AS upcoming_count,
        SUM(status = 'Archived') AS archived_count
    FROM school_years
");


if ($countResult) {

    $counts = $countResult->fetch_assoc();

    $totalSchoolYears = (int) ($counts["total"] ?? 0);
    $activeSchoolYears = (int) ($counts["active_count"] ?? 0);
    $upcomingSchoolYears = (int) ($counts["upcoming_count"] ?? 0);
    $archivedSchoolYears = (int) ($counts["archived_count"] ?? 0);
}


/*
|--------------------------------------------------------------------------
| HELPER
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

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>School Year | SMS</title>


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


        /* =========================================================
           SIDEBAR
        ========================================================= */

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
                1px solid rgba(255,255,255,0.10);

            overflow: hidden;
        }


        .brand-logo {

            width: 42px;
            height: 42px;

            border-radius: 10px;

            background: #ffffff;

            color: #12355B;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 15px;

            font-weight: 800;

            flex-shrink: 0;
        }


        .brand-text {

            white-space: nowrap;

            transition: opacity 0.2s ease;
        }


        .brand-title {

            font-size: 16px;

            font-weight: 700;

            letter-spacing: 0.2px;
        }


        .brand-subtitle {

            font-size: 11px;

            color: rgba(255,255,255,0.65);

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

            color: rgba(255,255,255,0.45);

            padding: 0 12px 9px;

            white-space: nowrap;

            overflow: hidden;
        }


        .nav-link {

            display: flex;

            align-items: center;

            gap: 13px;

            min-height: 45px;

            padding: 0 13px;

            margin-bottom: 5px;

            color: rgba(255,255,255,0.78);

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

            transition: opacity 0.2s ease;
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
                1px solid rgba(255,255,255,0.10);
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

            font-size: 13px;

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

            color: rgba(255,255,255,0.55);

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

            color: rgba(255,255,255,0.70);

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


        .sidebar.collapsed .logout span:last-child {

            display: none;
        }


        /* =========================================================
           MAIN
        ========================================================= */

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


        /* =========================================================
           CONTENT
        ========================================================= */

        .content {

            padding: 28px;
        }


        .breadcrumb {

            font-size: 12px;

            color: #6b7280;

            margin-bottom: 7px;
        }


        .breadcrumb strong {

            color: #12355B;
        }


        .heading-row {

            display: flex;

            justify-content: space-between;

            align-items: flex-end;

            gap: 20px;

            margin-bottom: 25px;
        }


        .heading-title {

            font-size: 25px;

            font-weight: 750;

            color: #12355B;
        }


        .heading-description {

            margin-top: 6px;

            font-size: 13px;

            color: #6b7280;
        }


        .add-btn {

            border: none;

            background: #12355B;

            color: white;

            padding: 11px 17px;

            border-radius: 8px;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            text-decoration: none;

            display: inline-flex;

            align-items: center;

            gap: 7px;
        }


        .add-btn:hover {

            background: #1E5A96;
        }


        /* =========================================================
           STAT CARDS
        ========================================================= */

        .stats {

            display: grid;

            grid-template-columns:
                repeat(4, minmax(0, 1fr));

            gap: 16px;

            margin-bottom: 22px;
        }


        .stat-card {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            padding: 18px;

            display: flex;

            justify-content: space-between;

            align-items: center;
        }


        .stat-label {

            font-size: 11px;

            color: #6b7280;

            margin-bottom: 5px;
        }


        .stat-number {

            font-size: 25px;

            font-weight: 750;

            color: #12355B;
        }


        .stat-icon {

            width: 42px;
            height: 42px;

            border-radius: 10px;

            background: #EAF3FA;

            color: #1E5A96;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 18px;
        }


        /* =========================================================
           TABLE PANEL
        ========================================================= */

        .panel {

            background: white;

            border: 1px solid #e5e7eb;

            border-radius: 12px;

            overflow: hidden;
        }


        .panel-header {

            padding: 18px 20px;

            border-bottom:
                1px solid #e5e7eb;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            flex-wrap: wrap;
        }


        .panel-title {

            font-size: 15px;

            font-weight: 700;

            color: #12355B;
        }


        .filters {

            display: flex;

            align-items: center;

            gap: 8px;

            flex-wrap: wrap;
        }


        .search-box,
        .filter-select {

            height: 38px;

            border: 1px solid #d9dee7;

            border-radius: 7px;

            padding: 0 11px;

            font-size: 12px;

            outline: none;

            background: white;
        }


        .search-box {

            width: 210px;
        }


        .search-box:focus,
        .filter-select:focus {

            border-color: #1E5A96;

            box-shadow:
                0 0 0 3px rgba(30,90,150,0.08);
        }


        .filter-btn {

            height: 38px;

            border: none;

            border-radius: 7px;

            padding: 0 14px;

            background: #12355B;

            color: white;

            font-size: 12px;

            font-weight: 600;

            cursor: pointer;
        }


        .clear-btn {

            height: 38px;

            padding: 0 13px;

            border-radius: 7px;

            border: 1px solid #d9dee7;

            background: white;

            color: #4b5563;

            text-decoration: none;

            display: flex;

            align-items: center;

            font-size: 12px;
        }


        .table-wrapper {

            width: 100%;

            overflow-x: auto;
        }


        table {

            width: 100%;

            border-collapse: collapse;

            min-width: 760px;
        }


        th {

            text-align: left;

            padding: 13px 20px;

            background: #f8fafc;

            color: #6b7280;

            font-size: 10px;

            text-transform: uppercase;

            letter-spacing: 0.5px;

            font-weight: 700;

            border-bottom:
                1px solid #e5e7eb;
        }


        td {

            padding: 15px 20px;

            font-size: 12px;

            color: #374151;

            border-bottom:
                1px solid #eef1f5;
        }


        tr:last-child td {

            border-bottom: none;
        }


        tr:hover td {

            background: #fbfcfe;
        }


        .school-year-name {

            font-weight: 700;

            color: #12355B;
        }


        .date-text {

            color: #6b7280;
        }


        .status {

            display: inline-flex;

            align-items: center;

            padding: 5px 9px;

            border-radius: 999px;

            font-size: 10px;

            font-weight: 700;
        }


        .status.active {

            background: #e7f7ed;

            color: #18733a;
        }


        .status.upcoming {

            background: #fff6df;

            color: #956b00;
        }


        .status.archived {

            background: #eef0f3;

            color: #5f6670;
        }


        .actions {

            display: flex;

            align-items: center;

            gap: 7px;
        }


        .action-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            height: 32px;

            padding: 0 10px;

            border-radius: 6px;

            font-size: 11px;

            font-weight: 600;

            text-decoration: none;

            border: 1px solid #dce2ea;

            background: white;

            color: #374151;
        }


        .action-btn:hover {

            background: #f4f7fa;
        }


        .action-btn.edit {

            color: #1E5A96;

            border-color: #cfe0f0;
        }


        .action-btn.active-btn {

            color: #18733a;

            border-color: #cce8d6;

            background: #f5fbf7;
        }


        .empty {

            text-align: center;

            padding: 45px 20px;

            color: #9ca3af;

            font-size: 13px;
        }


        /* =========================================================
           RESPONSIVE
        ========================================================= */

        @media (max-width: 1000px) {

            .stats {

                grid-template-columns:
                    repeat(2, minmax(0, 1fr));
            }
        }


        @media (max-width: 760px) {

            .sidebar {

                transform: translateX(-100%);

                width: 255px;
            }


            .sidebar.mobile-open {

                transform: translateX(0);
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


            .heading-row {

                align-items: flex-start;

                flex-direction: column;
            }


            .stats {

                grid-template-columns: 1fr;
            }


            .search-box {

                width: 100%;
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

<?php include "../../includes/admin_sidebar.php"; ?>


<!-- =========================================================
     MAIN
========================================================= -->

<main
    class="main"
    id="main"
>


    <!-- TOPBAR -->
<?php
$currentUserName = trim((string) ($_SESSION["full_name"] ?? $currentUserName ?? "Administrator"));
$currentUserRole = (string) ($_SESSION["role_name"] ?? "Administrator");

$nameParts = preg_split('/\s+/', $currentUserName, -1, PREG_SPLIT_NO_EMPTY);

$initials = '';

foreach ($nameParts as $part) {
    $initials .= strtoupper(substr($part, 0, 1));

    if (strlen($initials) >= 2) {
        break;
    }
}

if ($initials === '') {
    $initials = 'AD';
}
?>
    <header class="topbar">


        <div class="topbar-left">

            


            <div class="page-title">
                School Year
            </div>

        </div>


        <div class="top-user">

            <div class="top-avatar">

                <?= e(substr($initials, 0, 2)) ?>

            </div>


            <div class="top-user-name">

                <?= e($currentUserName) ?>

            </div>

        </div>
        

    </header>


    <!-- CONTENT -->

    <section class="content">


        <div class="breadcrumb">

            Administration
            /
            <strong>School Year</strong>

        </div>


        <div class="heading-row">


            <div>

                <h1 class="heading-title">
                    School Year
                </h1>

                <p class="heading-description">
                    Manage academic school years and their current status.
                </p>

            </div>


            <button
                type="button"
                class="add-btn"
                id="openAddModal"
            >

                <span>+</span>

                Add School Year

            </button>

        </div>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <div class="stats">


            <div class="stat-card">

                <div>

                    <div class="stat-label">
                        Total School Years
                    </div>

                    <div class="stat-number">
                        <?= $totalSchoolYears ?>
                    </div>

                </div>

                <div class="stat-icon">
                    ▣
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <div class="stat-label">
                        Active
                    </div>

                    <div class="stat-number">
                        <?= $activeSchoolYears ?>
                    </div>

                </div>

                <div class="stat-icon">
                    ✓
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <div class="stat-label">
                        Upcoming
                    </div>

                    <div class="stat-number">
                        <?= $upcomingSchoolYears ?>
                    </div>

                </div>

                <div class="stat-icon">
                    →
                </div>

            </div>


            <div class="stat-card">

                <div>

                    <div class="stat-label">
                        Archived
                    </div>

                    <div class="stat-number">
                        <?= $archivedSchoolYears ?>
                    </div>

                </div>

                <div class="stat-icon">
                    ◷
                </div>

            </div>

        </div>


        <!-- =====================================================
             SCHOOL YEAR TABLE
        ====================================================== -->

        <div class="panel">


            <div class="panel-header">


                <div class="panel-title">

                    School Year Records

                </div>


                <form
                    method="GET"
                    class="filters"
                >


                    <input
                        type="text"
                        name="search"
                        class="search-box"
                        placeholder="Search school year..."
                        value="<?= e($search) ?>"
                    >


                    <select
                        name="status"
                        class="filter-select"
                    >

                        <option value="">
                            All Status
                        </option>

                        <option
                            value="Active"
                            <?= $statusFilter === "Active" ? "selected" : "" ?>
                        >
                            Active
                        </option>

                        <option
                            value="Upcoming"
                            <?= $statusFilter === "Upcoming" ? "selected" : "" ?>
                        >
                            Upcoming
                        </option>

                        <option
                            value="Archived"
                            <?= $statusFilter === "Archived" ? "selected" : "" ?>
                        >
                            Archived
                        </option>

                    </select>


                    <button
                        type="submit"
                        class="filter-btn"
                    >
                        Filter
                    </button>


                    <?php if ($search !== "" || $statusFilter !== ""): ?>

                        <a
                            href="/SMS/admin/school_year/school_year.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    <?php endif; ?>


                </form>

            </div>


            <div class="table-wrapper">


                <?php if (!empty($schoolYears)): ?>


                    <table>

                        <thead>

                            <tr>

                                <th>
                                    School Year
                                </th>

                                <th>
                                    Start Date
                                </th>

                                <th>
                                    End Date
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($schoolYears as $row): ?>


                                <tr>


                                    <td>

                                        <span class="school-year-name">

                                            <?= e($row["school_year"]) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="date-text">

                                            <?= e(
                                                date(
                                                    "F d, Y",
                                                    strtotime($row["start_date"])
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="date-text">

                                            <?= e(
                                                date(
                                                    "F d, Y",
                                                    strtotime($row["end_date"])
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>


                                        <?php

                                        $statusClass =
                                            strtolower(
                                                $row["status"]
                                            );

                                        ?>


                                        <span
                                            class="status <?= e($statusClass) ?>"
                                        >

                                            <?= e($row["status"]) ?>

                                        </span>


                                    </td>


                                    <td>


                                        <div class="actions">


                                            <a
                                                href="/SMS/admin/school_year/edit_school_year.php?id=<?= (int) $row["school_year_id"] ?>"
                                                class="action-btn edit"
                                            >
                                                Edit
                                            </a>


                                            <?php if ($row["status"] !== "Active"): ?>


                                                <form
                                                    method="POST"
                                                    action="/SMS/admin/school_year/set_active.php"
                                                    style="display:inline;"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="school_year_id"
                                                        value="<?= (int) $row["school_year_id"] ?>"
                                                    >


                                                    <button
                                                        type="submit"
                                                        class="action-btn active-btn"
                                                    >
                                                        Set Active
                                                    </button>

                                                </form>


                                            <?php endif; ?>


                                        </div>


                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>


                <?php else: ?>


                    <div class="empty">

                        No school year records found.

                    </div>


                <?php endif; ?>


            </div>


        </div>


    </section>


</main>


<!-- =========================================================
     ADD SCHOOL YEAR MODAL
========================================================= -->

<div
    id="addModal"
    style="
        display:none;
        position:fixed;
        inset:0;
        background:rgba(15,23,42,0.45);
        z-index:2000;
        align-items:center;
        justify-content:center;
        padding:20px;
    "
>


    <div
        style="
            background:white;
            width:100%;
            max-width:500px;
            border-radius:14px;
            box-shadow:0 20px 50px rgba(0,0,0,0.18);
            overflow:hidden;
        "
    >


        <div
            style="
                padding:20px 22px;
                border-bottom:1px solid #e5e7eb;
                display:flex;
                align-items:center;
                justify-content:space-between;
            "
        >

            <div>

                <div
                    style="
                        font-size:17px;
                        font-weight:700;
                        color:#12355B;
                    "
                >
                    Add School Year
                </div>

                <div
                    style="
                        font-size:11px;
                        color:#6b7280;
                        margin-top:3px;
                    "
                >
                    Create a new academic school year.
                </div>

            </div>


            <button
                type="button"
                id="closeAddModal"
                style="
                    border:none;
                    background:none;
                    font-size:22px;
                    color:#6b7280;
                    cursor:pointer;
                "
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="/SMS/admin/school_year/save_school_year.php"
            style="padding:22px;"
        >


            <div style="margin-bottom:16px;">

                <label
                    style="
                        display:block;
                        font-size:12px;
                        font-weight:600;
                        color:#374151;
                        margin-bottom:7px;
                    "
                >
                    School Year
                </label>


                <input
                    type="text"
                    name="school_year"
                    placeholder="Example: 2028-2029"
                    required
                    style="
                        width:100%;
                        height:42px;
                        border:1px solid #d9dee7;
                        border-radius:7px;
                        padding:0 11px;
                        font-size:13px;
                        outline:none;
                    "
                >

            </div>


            <div
                style="
                    display:grid;
                    grid-template-columns:1fr 1fr;
                    gap:12px;
                "
            >

                <div>

                    <label
                        style="
                            display:block;
                            font-size:12px;
                            font-weight:600;
                            color:#374151;
                            margin-bottom:7px;
                        "
                    >
                        Start Date
                    </label>


                    <input
                        type="date"
                        name="start_date"
                        required
                        style="
                            width:100%;
                            height:42px;
                            border:1px solid #d9dee7;
                            border-radius:7px;
                            padding:0 10px;
                            font-size:13px;
                        "
                    >

                </div>


                <div>

                    <label
                        style="
                            display:block;
                            font-size:12px;
                            font-weight:600;
                            color:#374151;
                            margin-bottom:7px;
                        "
                    >
                        End Date
                    </label>


                    <input
                        type="date"
                        name="end_date"
                        required
                        style="
                            width:100%;
                            height:42px;
                            border:1px solid #d9dee7;
                            border-radius:7px;
                            padding:0 10px;
                            font-size:13px;
                        "
                    >

                </div>

            </div>


            <div
                style="
                    display:flex;
                    justify-content:flex-end;
                    gap:9px;
                    margin-top:22px;
                "
            >

                <button
                    type="button"
                    id="cancelAddModal"
                    style="
                        height:40px;
                        padding:0 15px;
                        border:1px solid #d9dee7;
                        background:white;
                        color:#4b5563;
                        border-radius:7px;
                        cursor:pointer;
                        font-size:12px;
                        font-weight:600;
                    "
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    style="
                        height:40px;
                        padding:0 16px;
                        border:none;
                        background:#12355B;
                        color:white;
                        border-radius:7px;
                        cursor:pointer;
                        font-size:12px;
                        font-weight:600;
                    "
                >
                    Save School Year
                </button>

            </div>


        </form>

    </div>

</div>


<script>


/*
|--------------------------------------------------------------------------
| ADD SCHOOL YEAR MODAL
|--------------------------------------------------------------------------
*/

const addModal =
    document.getElementById("addModal");

const openAddModal =
    document.getElementById("openAddModal");

const closeAddModal =
    document.getElementById("closeAddModal");

const cancelAddModal =
    document.getElementById("cancelAddModal");


openAddModal.addEventListener(
    "click",
    function () {

        addModal.style.display = "flex";

    }
);


closeAddModal.addEventListener(
    "click",
    function () {

        addModal.style.display = "none";

    }
);


cancelAddModal.addEventListener(
    "click",
    function () {

        addModal.style.display = "none";

    }
);


addModal.addEventListener(
    "click",
    function (event) {

        if (event.target === addModal) {

            addModal.style.display = "none";

        }

    }
);


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