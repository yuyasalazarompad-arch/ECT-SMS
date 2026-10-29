<?php
require_once "../../includes/auth_check.php";
require_once "../../config/database.php";

/* Administrator access */
if (($_SESSION["role_name"] ?? "") !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Administrator access is required.");
}

/* Current administrator */
$userId = (int) ($_SESSION["user_id"] ?? 0);
$loggedInUser = $_SESSION["full_name"] ?? "Administrator";
$loggedInRole = $_SESSION["role_name"] ?? "Administrator";
$loggedInEmail = "";
$profilePicture = "";

$userStmt = $conn->prepare(
    "SELECT full_name, email, profile_picture
     FROM users
     WHERE user_id = ?
     LIMIT 1"
);

if ($userStmt) {
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($user = $userResult->fetch_assoc()) {
        $loggedInUser = $user["full_name"] ?: $loggedInUser;
        $loggedInEmail = $user["email"] ?? "";
        $profilePicture = trim((string) ($user["profile_picture"] ?? ""));
    }

    $userStmt->close();
}

$profilePicturePath = "";
if ($profilePicture !== "") {
    $profilePicturePath = "/SMS/assets/uploads/users/" .
        rawurlencode(basename($profilePicture));
}

/* Administrator initials */
$nameParts = preg_split(
    '/\s+/',
    trim((string) $loggedInUser),
    -1,
    PREG_SPLIT_NO_EMPTY
);

$initials = "";
if (!empty($nameParts)) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
    }
}
$initials = $initials ?: "AD";

/* HTML escaping helper: declare this function only once in this file. */
if (!function_exists("h")) {
    function h($value): string
    {
        return htmlspecialchars((string) ($value ?? ""), ENT_QUOTES, "UTF-8");
    }
}

/* Roles query */
$sql = "
    SELECT
        roles.*,
        (
            SELECT COUNT(*)
            FROM role_permissions
            WHERE role_permissions.role_id = roles.role_id
        ) AS permission_count
    FROM roles
    ORDER BY roles.role_id ASC
";
$result = $conn->query($sql);

/* Summary statistics */
$total_roles = 0;
$active_roles = 0;
$inactive_roles = 0;

$count_result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Active'), 0) AS active,
        COALESCE(SUM(status = 'Inactive'), 0) AS inactive
    FROM roles
");

if ($count_result) {
    $count_row = $count_result->fetch_assoc();
    $total_roles = (int) ($count_row["total"] ?? 0);
    $active_roles = (int) ($count_row["active"] ?? 0);
    $inactive_roles = (int) ($count_row["inactive"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles &amp; Permissions | SMS</title>

    <style>
        :root {
            --navy: #12355B;
            --navy-deep: #0B2745;
            --blue: #1E5A96;
            --blue-soft: #EAF3FA;
            --red: #DC2626;
            --red-soft: #FEF2F2;
            --green: #168653;
            --green-soft: #E8F7EF;
            --white: #FFFFFF;
            --page: #F4F7FB;
            --text: #1D2939;
            --muted: #748196;
            --border: #E2E8F0;
            --sidebar-width: 260px;
            --sidebar-small: 78px;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--page);
            color: var(--text);
        }

        body { overflow-x: hidden; }
        a { color: inherit; text-decoration: none; }
        button, input { font: inherit; }
        button { -webkit-tap-highlight-color: transparent; }

        .main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left .25s ease;
        }

        body.collapsed .main { margin-left: var(--sidebar-small); }

        .topbar {
            min-height: 82px;
            padding: 16px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: relative;
            z-index: 20;
        }

        .page-title { min-width: 0; }
        .page-title h1 {
            margin: 0 0 5px;
            color: var(--navy);
            font-size: 25px;
            letter-spacing: -.4px;
        }
        .page-title p {
            margin: 0;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        .top-control { position: relative; }

        .notification, .account-button {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: white;
            cursor: pointer;
            transition: background .2s ease, border-color .2s ease;
        }
        .notification:hover, .account-button:hover {
            background: var(--blue-soft);
            border-color: #BDD3EF;
        }
        .notification {
            position: relative;
            color: var(--navy);
            font-size: 19px;
            line-height: 1;
        }
        .notification span { pointer-events: none; }
        .notification-dot {
            position: absolute;
            top: 7px;
            right: 7px;
            width: 7px;
            height: 7px;
            border: 1px solid white;
            border-radius: 50%;
            background: var(--red);
        }
        .account-button {
            overflow: hidden;
            padding: 0;
            border-color: transparent;
            background: var(--navy);
            color: white;
            font-size: 11px;
            font-weight: 800;
        }
        .account-button img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .notification:focus-visible, .account-button:focus-visible,
        .dropdown-close:focus-visible {
            outline: 3px solid rgba(30, 90, 150, .35);
            outline-offset: 3px;
        }

        .dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            width: 310px;
            max-width: calc(100vw - 24px);
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 14px 38px rgba(11, 39, 69, .14);
            display: none;
            overflow: hidden;
            z-index: 100;
        }
        .dropdown.open {
            display: block;
            animation: dropdownIn .15s ease;
        }
        @keyframes dropdownIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .dropdown-heading {
            padding: 17px 18px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .dropdown-heading h3 {
            margin: 0;
            color: var(--navy);
            font-size: 15px;
        }
        .dropdown-heading p {
            margin: 5px 0 0;
            color: var(--muted);
            font-size: 11px;
        }
        .dropdown-close {
            width: 30px;
            height: 30px;
            flex-shrink: 0;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            color: var(--muted);
            cursor: pointer;
        }
        .notification-content {
            padding: 24px 18px;
            text-align: center;
        }
        .notification-empty-icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: var(--blue-soft);
            color: var(--blue);
            font-size: 20px;
        }
        .notification-content h4 {
            margin: 0 0 6px;
            color: var(--navy);
            font-size: 13px;
        }
        .notification-content p {
            margin: 0;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.6;
        }
        .account-profile {
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border);
        }
        .account-profile-image {
            width: 45px;
            height: 45px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 50%;
            background: var(--navy);
            color: white;
            font-size: 12px;
            font-weight: 800;
        }
        .account-profile-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .account-profile-info { min-width: 0; }
        .account-profile-info strong {
            display: block;
            overflow: hidden;
            color: var(--navy);
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .account-profile-info span {
            display: block;
            margin-top: 4px;
            overflow-wrap: anywhere;
            color: var(--muted);
            font-size: 11px;
        }
        .account-menu { padding: 8px; }
        .account-menu a {
            min-height: 43px;
            padding: 0 11px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 8px;
            color: var(--text);
            font-size: 12px;
            transition: background .15s ease;
        }
        .account-menu a:hover { background: var(--blue-soft); }
        .account-menu .logout-link { color: var(--red); }
        .account-menu .logout-link:hover { background: var(--red-soft); }
        .menu-icon { width: 25px; text-align: center; font-size: 16px; }

        .page { padding: 30px; }
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 23px;
        }
        .page-header-left { min-width: 0; }
        .page-header-left h2 {
            display: table;
            margin: 0 0 8px;
            color: var(--navy);
            font-size: 25px;
            letter-spacing: -.35px;
        }
        .page-header-left h2::after {
            content: "";
            display: block;
            width: 100%;
            height: 3px;
            margin-top: 8px;
            border-radius: 3px;
            background: var(--red);
        }
        .page-header-left p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }
        .add-button {
            min-height: 44px;
            padding: 0 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-shrink: 0;
            border-radius: 9px;
            background: var(--blue);
            color: white;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 5px 14px rgba(30, 90, 150, .15);
            transition: background .2s ease, transform .2s ease;
        }
        .add-button:hover { background: var(--navy); transform: translateY(-1px); }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 17px;
            margin-bottom: 23px;
        }
        .stat {
            min-height: 125px;
            padding: 20px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }
        .stat-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .stat-label {
            color: var(--muted);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .7px;
        }
        .stat-number {
            margin-top: 9px;
            color: var(--navy);
            font-size: 30px;
            font-weight: 800;
        }
        .stat-icon {
            width: 43px;
            height: 43px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 11px;
            background: var(--blue-soft);
            color: var(--blue);
            font-size: 19px;
        }
        .stat:nth-child(3) .stat-icon { background: var(--red-soft); color: var(--red); }

        .roles-card {
            overflow: hidden;
            background: white;
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 4px 16px rgba(7, 26, 61, .025);
        }
        .card-header {
            min-height: 88px;
            padding: 19px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            border-bottom: 1px solid var(--border);
        }
        .card-title h3 { margin: 0; color: var(--navy); font-size: 19px; }
        .card-title p { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
        .search-box { position: relative; width: 280px; flex-shrink: 0; }
        .search-box input {
            width: 100%;
            height: 43px;
            padding: 0 13px 0 40px;
            outline: none;
            border: 1px solid var(--border);
            border-radius: 9px;
            background: #FBFCFE;
            font-size: 12px;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .search-box input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(30, 90, 150, .09);
        }
        .search-icon {
            position: absolute;
            top: 50%;
            left: 13px;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 17px;
            pointer-events: none;
        }
        .table-container { width: 100%; overflow-x: auto; }
        .roles-table { width: 100%; min-width: 950px; border-collapse: collapse; }
        .roles-table th {
            padding: 15px 22px;
            text-align: left;
            background: #F8FAFD;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .7px;
            white-space: nowrap;
        }
        .roles-table td {
            padding: 17px 22px;
            border-bottom: 1px solid #EDF1F6;
            font-size: 13px;
            vertical-align: middle;
        }
        .roles-table tbody tr { transition: background .15s ease; }
        .roles-table tbody tr:hover { background: #FBFDFF; }
        .roles-table tbody tr:last-child td { border-bottom: none; }
        .role-id {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 9px;
            background: var(--blue-soft);
            color: var(--navy);
            font-size: 12px;
            font-weight: 800;
        }
        .role-name { color: var(--navy); font-size: 13px; font-weight: 800; }
        .role-description {
            max-width: 360px;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }
        .permission-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 20px;
            background: var(--blue-soft);
            color: var(--blue);
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }
        .status-badge.active { background: var(--green-soft); color: var(--green); }
        .status-badge.inactive { background: var(--red-soft); color: var(--red); }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .actions { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .action-button {
            min-height: 35px;
            padding: 0 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: white;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: background .2s ease, border-color .2s ease;
        }
        .action-button.permissions { color: var(--blue); }
        .action-button.permissions:hover { background: var(--blue-soft); border-color: #BDD3EF; }
        .action-button.edit { color: #536273; }
        .action-button.edit:hover { background: #F3F5F8; border-color: #CBD4DF; }
        .action-button.delete { color: var(--red); }
        .action-button.delete:hover { background: var(--red-soft); border-color: #F0BFC3; }
        .protected-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin-left: 6px;
            padding: 5px 8px;
            border-radius: 6px;
            background: #F3F5F8;
            color: var(--muted);
            font-size: 9px;
            font-weight: 700;
            white-space: nowrap;
        }
        .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); }
        .empty-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            background: var(--blue-soft);
            color: var(--blue);
            font-size: 23px;
        }
        .empty-state h3 { margin: 0 0 6px; color: var(--navy); font-size: 17px; }
        .empty-state p { margin: 0; font-size: 12px; }
        .no-results { display: none; padding: 40px; text-align: center; color: var(--muted); font-size: 12px; }

        @media (max-width: 1050px) {
            .page { padding: 24px; }
            .topbar { padding-left: 24px; padding-right: 24px; }
        }
        @media (max-width: 800px) {
            .main { margin-left: var(--sidebar-small); }
            .page { padding: 20px; }
            .page-header, .card-header { align-items: flex-start; flex-direction: column; }
            .search-box { width: 100%; }
        }
        @media (max-width: 600px) {
            .stats { grid-template-columns: 1fr; }
            .page-title h1 { font-size: 20px; }
            .page-title p { max-width: 210px; }
            .page { padding: 16px; }
            .topbar { padding: 15px; gap: 10px; }
            .top-actions { gap: 7px; }
            .notification, .account-button { width: 36px; height: 36px; }
            .dropdown { position: fixed; top: 76px; right: 10px; width: 310px; }
            .page-header-left h2 { font-size: 22px; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: .01ms !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>
<body>

<?php include "../../includes/admin_sidebar.php"; ?>

<main class="main">
    <header class="topbar">
        <div class="page-title">
            <h1>Roles &amp; Permissions</h1>
            <p>Manage system roles and their access permissions.</p>
        </div>

        <div class="top-actions">
            <div class="top-control" id="notificationControl">
                <button
                    type="button"
                    class="notification"
                    id="notificationButton"
                    aria-label="Open notification history"
                    aria-expanded="false"
                    aria-controls="notificationDropdown"
                >
                    <span aria-hidden="true">🔔</span>
                    <span class="notification-dot" id="notificationDot"></span>
                </button>

                <section
                    class="dropdown"
                    id="notificationDropdown"
                    aria-label="Notification history"
                    aria-hidden="true"
                >
                    <div class="dropdown-heading">
                        <div>
                            <h3>Notification History</h3>
                            <p>System notifications</p>
                        </div>
                        <button type="button" class="dropdown-close" data-close-dropdown aria-label="Close notification history">×</button>
                    </div>
                    <div class="notification-content">
                        <div class="notification-empty-icon" aria-hidden="true">🔔</div>
                        <h4>No notifications yet</h4>
                        <p>Your notification history will appear here when notifications are available.</p>
                    </div>
                </section>
            </div>

            <div class="top-control" id="accountControl">
                <button
                    type="button"
                    class="account-button"
                    id="accountButton"
                    aria-label="Open administrator account menu"
                    aria-expanded="false"
                    aria-controls="accountDropdown"
                >
                    <?php if ($profilePicturePath !== ""): ?>
                        <img src="<?= h($profilePicturePath) ?>" alt="Administrator profile">
                    <?php else: ?>
                        <?= h($initials) ?>
                    <?php endif; ?>
                </button>

                <section class="dropdown" id="accountDropdown" aria-label="Administrator account menu" aria-hidden="true">
                    <div class="account-profile">
                        <div class="account-profile-image">
                            <?php if ($profilePicturePath !== ""): ?>
                                <img src="<?= h($profilePicturePath) ?>" alt="Profile picture">
                            <?php else: ?>
                                <?= h($initials) ?>
                            <?php endif; ?>
                        </div>
                        <div class="account-profile-info">
                            <strong><?= h($loggedInUser) ?></strong>
                            <span><?= h($loggedInEmail ?: $loggedInRole) ?></span>
                        </div>
                    </div>

                    <nav class="account-menu">
                        <a href="/SMS/admin/dashboard.php">
                            <span class="menu-icon" aria-hidden="true">⌂</span>
                            Dashboard
                        </a>
                        <a href="/SMS/admin/school_settings/school_settings.php">
                            <span class="menu-icon" aria-hidden="true">⚙</span>
                            School Settings
                        </a>
                        <a href="/SMS/logout.php" class="logout-link" id="logoutLink">
                            <span class="menu-icon" aria-hidden="true">↪</span>
                            Log Out
                        </a>
                    </nav>
                </section>
            </div>
        </div>
    </header>

    <section class="page">
        <div class="page-header">
            <div class="page-header-left">
                <h2>Role Management</h2>
                <p>View and manage the roles available in SMS.</p>
            </div>
            <a href="add_role.php" class="add-button"><span aria-hidden="true">+</span> Add Role</a>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">TOTAL ROLES</div>
                        <div class="stat-number"><?= $total_roles ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">⚿</div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">ACTIVE ROLES</div>
                        <div class="stat-number"><?= $active_roles ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">✓</div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">INACTIVE ROLES</div>
                        <div class="stat-number"><?= $inactive_roles ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">⏸</div>
                </div>
            </div>
        </div>

        <div class="roles-card">
            <div class="card-header">
                <div class="card-title">
                    <h3>System Roles</h3>
                    <p>Manage role access and permissions.</p>
                </div>
                <label class="search-box">
                    <span class="search-icon" aria-hidden="true">⌕</span>
                    <input type="text" id="roleSearch" placeholder="Search roles..." autocomplete="off" aria-label="Search roles">
                </label>
            </div>

            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="roles-table" id="rolesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>ROLE NAME</th>
                                <th>DESCRIPTION</th>
                                <th>PERMISSIONS</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr class="role-row">
                                    <td><span class="role-id"><?= h($row["role_id"]) ?></span></td>
                                    <td>
                                        <span class="role-name"><?= h($row["role_name"]) ?></span>
                                        <?php if ((int) $row["role_id"] <= 4): ?>
                                            <span class="protected-badge">🔒 Protected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="role-description">
                                            <?php
                                            $description = trim((string) ($row["description"] ?? ""));
                                            echo $description === "" ? "No description provided." : h($description);
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="permission-badge">⚿ <?= h($row["permission_count"] ?? 0) ?> permissions</span>
                                    </td>
                                    <td>
                                        <?php if (($row["status"] ?? "") === "Active"): ?>
                                            <span class="status-badge active"><span class="status-dot"></span>Active</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive"><span class="status-dot"></span>Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="permissions.php?id=<?= rawurlencode((string) $row["role_id"]) ?>" class="action-button permissions">Permissions</a>
                                            <a href="edit_role.php?id=<?= rawurlencode((string) $row["role_id"]) ?>" class="action-button edit">Edit</a>
                                            <?php if ((int) $row["role_id"] > 4): ?>
                                                <a
                                                    href="delete_role.php?id=<?= rawurlencode((string) $row["role_id"]) ?>"
                                                    class="action-button delete"
                                                    onclick="return confirm('Are you sure you want to delete this role?');"
                                                >Delete</a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="no-results" id="noResults">No roles found matching your search.</div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon" aria-hidden="true">⚿</div>
                        <h3>No Roles Found</h3>
                        <p>There are currently no roles in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    "use strict";

    /* Search roles */
    const searchInput = document.getElementById("roleSearch");
    const rows = document.querySelectorAll(".role-row");
    const noResults = document.getElementById("noResults");

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const searchValue = this.value.toLowerCase().trim();
            let visibleRows = 0;

            rows.forEach(function (row) {
                const matches = row.textContent.toLowerCase().includes(searchValue);
                row.style.display = matches ? "" : "none";
                if (matches) visibleRows++;
            });

            if (noResults) {
                noResults.style.display = visibleRows === 0 && searchValue !== "" ? "block" : "none";
            }
        });
    }

    /* Dropdowns */
    const notificationButton = document.getElementById("notificationButton");
    const notificationDropdown = document.getElementById("notificationDropdown");
    const accountButton = document.getElementById("accountButton");
    const accountDropdown = document.getElementById("accountDropdown");
    const notificationDot = document.getElementById("notificationDot");

    function closeDropdowns() {
        if (notificationDropdown) {
            notificationDropdown.classList.remove("open");
            notificationDropdown.setAttribute("aria-hidden", "true");
        }
        if (accountDropdown) {
            accountDropdown.classList.remove("open");
            accountDropdown.setAttribute("aria-hidden", "true");
        }
        if (notificationButton) notificationButton.setAttribute("aria-expanded", "false");
        if (accountButton) accountButton.setAttribute("aria-expanded", "false");
    }

    function toggleDropdown(button, dropdown) {
        if (!button || !dropdown) return;

        const shouldOpen = !dropdown.classList.contains("open");
        closeDropdowns();

        if (shouldOpen) {
            dropdown.classList.add("open");
            dropdown.setAttribute("aria-hidden", "false");
            button.setAttribute("aria-expanded", "true");
        }
    }

    if (notificationButton && notificationDropdown) {
        notificationButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            toggleDropdown(notificationButton, notificationDropdown);
            if (notificationDot) notificationDot.style.display = "none";
        });
    }

    if (accountButton && accountDropdown) {
        accountButton.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            toggleDropdown(accountButton, accountDropdown);
        });
    }

    [notificationDropdown, accountDropdown].forEach(function (dropdown) {
        if (dropdown) {
            dropdown.addEventListener("click", function (event) {
                event.stopPropagation();
            });
        }
    });

    document.addEventListener("click", closeDropdowns);

    document.querySelectorAll("[data-close-dropdown]").forEach(function (button) {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            closeDropdowns();
        });
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") closeDropdowns();
    });
})();
</script>

</body>
</html>
