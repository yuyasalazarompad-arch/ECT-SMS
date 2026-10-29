<?php
require_once "../../includes/auth_check.php";

$userId = $_SESSION["user_id"] ?? null;
$currentUserName = trim((string) ($_SESSION["full_name"] ?? "Administrator"));
$currentUserRole = (string) ($_SESSION["role_name"] ?? "Administrator");
$nameParts = preg_split('/\\s+/', $currentUserName, -1, PREG_SPLIT_NO_EMPTY);
$userInitials = "";
foreach ($nameParts as $part) {
    $userInitials .= strtoupper(substr($part, 0, 1));
    if (strlen($userInitials) >= 2) {
        break;
    }
}
if ($userInitials === "") {
    $userInitials = "AD";
}

/* Get users */
$sql = "
    SELECT
        users.user_id,
        users.full_name,
        users.username,
        users.email,
        users.status,
        users.created_at,
        roles.role_name
    FROM users
    INNER JOIN roles ON users.role_id = roles.role_id
    ORDER BY users.user_id DESC
";
$result = $conn->query($sql);

/* Count users */
$total_users = 0;
$active_users = 0;
$inactive_users = 0;

$count_result = $conn->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'Active'), 0) AS active,
        COALESCE(SUM(status = 'Inactive'), 0) AS inactive
    FROM users
");

if ($count_result) {
    $count_row = $count_result->fetch_assoc();
    $total_users = (int) ($count_row["total"] ?? 0);
    $active_users = (int) ($count_row["active"] ?? 0);
    $inactive_users = (int) ($count_row["inactive"] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | SMS</title>

    <!-- Shared CSS -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
    
    <style>

        :root {
            --navy: #12355B;
            --navy-dark: #0A2544;
            --blue: #1E5A96;
            --blue-bright: #1769D1;
            --blue-light: #EAF3FA;
            --red: #E53945;
            --white: #FFFFFF;
            --background: #F2F6FB;
            --text: #172033;
            --muted: #718096;
            --border: #DDE7F2;
            --success: #13865B;
            --success-bg: #E8F7EF;
            --danger: #C9343D;
            --danger-bg: #FFF0F0;
            --sidebar-width: 260px;
            --sidebar-small: 78px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            min-height: 100%;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--background);
            color: var(--text);
        }

        a { text-decoration: none; color: inherit; }
        button, input, select { font-family: inherit; }

        /* Sidebar base styles are retained for compatibility with the shared sidebar. */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #0B315E 0%, #09284D 100%);
            color: white; z-index: 1000;
            display: flex; flex-direction: column;
            box-shadow: 5px 0 20px rgba(0,0,0,.08);
            transition: width .25s ease; overflow: hidden;
        }
        .school-brand {
            height: 105px; padding: 20px; display: flex; align-items: center;
            gap: 12px; flex-shrink: 0;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .school-logo {
            width: 52px; height: 52px; border-radius: 14px; background: white;
            color: var(--navy); display: flex; align-items: center;
            justify-content: center; font-size: 15px; font-weight: 800; flex-shrink: 0;
        }
        .school-name { min-width: 0; white-space: nowrap; }
        .school-name strong { display: block; font-size: 21px; margin-bottom: 4px; }
        .school-name span { font-size: 12px; color: rgba(255,255,255,.62); }
        .sidebar-nav { flex: 1; min-height: 0; padding: 18px 12px; overflow-y: auto; overflow-x: hidden; }
        .sidebar-nav::-webkit-scrollbar { width: 5px; }
        .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 10px; }
        .section-title {
            font-size: 11px; font-weight: 800; letter-spacing: 1.3px;
            color: rgba(255,255,255,.38); padding: 8px 12px; margin-bottom: 5px;
        }
        .section-title.admin { margin-top: 20px; }
        .menu-item {
            height: 49px; width: 100%; margin-bottom: 5px; border-radius: 11px;
            display: flex; align-items: center; gap: 12px; padding: 0 13px;
            color: rgba(255,255,255,.72); font-size: 14px; font-weight: 600;
            white-space: nowrap; transition: background .2s ease, color .2s ease;
        }
        .menu-item:hover { background: rgba(255,255,255,.09); color: white; }
        .menu-item.active {
            background: var(--blue-bright); color: white;
            box-shadow: 0 5px 14px rgba(23,105,209,.25);
        }
        .menu-icon {
            width: 26px; height: 26px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .menu-name { flex: 1; overflow: hidden; text-overflow: ellipsis; }
        .soon {
            font-size: 9px; padding: 3px 6px; border-radius: 5px;
            background: rgba(255,255,255,.08); color: rgba(255,255,255,.45);
        }
        .sidebar-bottom {
            flex-shrink: 0; padding: 12px; background: rgba(0,0,0,.08);
            border-top: 1px solid rgba(255,255,255,.08);
        }
        .collapse-button {
            width: 100%; height: 44px; border: 1px solid rgba(255,255,255,.15);
            border-radius: 10px; background: rgba(255,255,255,.09); color: white;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            gap: 8px; font-size: 13px; font-weight: 700; transition: background .2s ease;
        }
        .collapse-button:hover { background: var(--blue-bright); }
        .collapse-icon { font-size: 15px; }
        .logout-button {
            width: 100%; height: 44px; margin-top: 10px;
            border: 1px solid rgba(255,255,255,.15); border-radius: 10px;
            background: rgba(255,255,255,.06); color: white; text-decoration: none;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            font-size: 13px; font-weight: 700;
            transition: background .2s ease, border-color .2s ease;
        }
        .logout-button:hover { background: rgba(220,70,70,.85); border-color: rgba(255,255,255,.2); }
        .logout-icon { font-size: 16px; }
        .logout-text { display: inline; }
        .admin-profile {
            margin-top: 9px; height: 54px; padding: 7px; display: flex;
            align-items: center; gap: 9px; border-radius: 10px;
            background: rgba(255,255,255,.06);
        }
        .admin-avatar {
            width: 38px; height: 38px; border-radius: 50%; background: white;
            color: var(--navy); display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; flex-shrink: 0;
        }
        .admin-info { min-width: 0; white-space: nowrap; }
        .admin-info strong { display: block; font-size: 12px; }
        .admin-info span { display: block; margin-top: 3px; font-size: 10px; color: rgba(255,255,255,.48); }

        .main { margin-left: var(--sidebar-width); min-height: 100vh; transition: margin-left .25s ease; }
        .topbar {
            min-height: 82px; background: white; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; padding: 15px 30px;
        }
        .page-title h1 { font-size: 27px; color: var(--navy); margin-bottom: 5px; }
        .page-title p { font-size: 13px; color: var(--muted); }
        .top-actions { display: flex; align-items: center; gap: 13px; }
        .notification {
            width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--border);
            background: white; cursor: pointer; position: relative; font-size: 18px;
        }
        .notification-dot { position: absolute; right: 7px; top: 7px; width: 6px; height: 6px; border-radius: 50%; background: var(--red); }
        .top-avatar {
            width: 41px; height: 41px; border-radius: 50%; background: var(--navy);
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800;
        }

        .page { padding: 28px; }
        .page-header {
            display: flex; align-items: center; justify-content: space-between;
            gap: 20px; margin-bottom: 23px;
        }
        .page-header-left h2 {
            font-size: 25px; color: var(--navy); margin-bottom: 8px;
            display: table; position: relative;
        }
        .page-header-left h2::after {
            content: ""; display: block; width: 100%; height: 3px;
            margin-top: 8px; background: var(--red); border-radius: 3px;
        }
        .page-header-left p { color: var(--muted); font-size: 13px; }
        .add-button {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 44px; padding: 0 19px; border-radius: 10px;
            background: var(--blue-bright); color: white; font-size: 13px; font-weight: 700;
            box-shadow: 0 5px 14px rgba(23,105,209,.2);
            transition: background .2s ease, transform .2s ease;
        }
        .add-button:hover { background: #0E5BBD; transform: translateY(-1px); }

        .stats { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 17px; margin-bottom: 23px; }
        .stat {
            background: white; border: 1px solid var(--border); border-radius: 14px;
            padding: 20px; min-height: 125px; box-shadow: 0 3px 12px rgba(18,53,91,.025);
        }
        .stat-top { display: flex; align-items: flex-start; justify-content: space-between; }
        .stat-label { font-size: 11px; font-weight: 800; letter-spacing: .8px; color: var(--muted); }
        .stat-number { margin-top: 7px; font-size: 30px; font-weight: 800; color: var(--navy); }
        .stat-icon {
            width: 43px; height: 43px; border-radius: 10px; background: var(--blue-light);
            color: var(--blue-bright); display: flex; align-items: center; justify-content: center; font-size: 19px;
        }
        .stat:nth-child(2) .stat-icon { background: var(--success-bg); color: var(--success); }
        .stat:nth-child(3) .stat-icon { background: var(--danger-bg); color: var(--danger); }

        .users-card {
            background: white; border: 1px solid var(--border); border-radius: 16px;
            overflow: hidden; box-shadow: 0 4px 16px rgba(7,26,61,.035);
        }
        .card-toolbar {
            min-height: 88px; padding: 19px 22px; display: flex; align-items: center;
            justify-content: space-between; gap: 15px; border-bottom: 1px solid var(--border);
        }
        .toolbar-title h3 { font-size: 20px; color: var(--navy); }
        .toolbar-title p { margin-top: 5px; color: var(--muted); font-size: 12px; }
        .search-box { position: relative; width: 280px; }
        .search-box input {
            width: 100%; height: 43px; border: 1px solid var(--border); border-radius: 9px;
            padding: 0 13px 0 40px; outline: none; font-size: 12px; background: #FBFCFE;
            transition: border-color .2s ease, box-shadow .2s ease;
        }
        .search-box input:focus { border-color: var(--blue-bright); box-shadow: 0 0 0 3px rgba(23,105,209,.08); }
        .search-icon { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); color: var(--muted); font-size: 17px; pointer-events: none; }
        .table-container { width: 100%; overflow-x: auto; }
        .users-table { width: 100%; border-collapse: collapse; min-width: 850px; }
        .users-table th {
            padding: 15px 22px; text-align: left; font-size: 11px; letter-spacing: .7px;
            color: var(--muted); font-weight: 800; background: #F7FAFE; border-bottom: 1px solid var(--border);
        }
        .users-table td { padding: 17px 22px; font-size: 13px; border-bottom: 1px solid #EDF1F6; vertical-align: middle; }
        .users-table tbody tr { transition: background .15s ease; }
        .users-table tbody tr:hover { background: #FBFDFF; }
        .users-table tbody tr:last-child td { border-bottom: none; }
        .user-cell { display: flex; align-items: center; gap: 11px; }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: var(--blue-light);
            color: var(--blue-bright); display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 800; flex-shrink: 0;
        }
        .user-details strong { display: block; color: var(--navy); font-size: 13px; }
        .user-details span { display: block; margin-top: 4px; color: var(--muted); font-size: 11px; }
        .role-badge {
            display: inline-flex; align-items: center; padding: 6px 10px; border-radius: 20px;
            background: var(--blue-light); color: var(--blue-bright); font-size: 11px; font-weight: 700;
        }
        .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .status-badge.active { background: var(--success-bg); color: var(--success); }
        .status-badge.inactive { background: var(--danger-bg); color: var(--danger); }
        .status-dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
        .actions { display: flex; align-items: center; gap: 7px; }
        .action-button {
            min-height: 34px; padding: 0 10px; border-radius: 7px; display: inline-flex;
            align-items: center; justify-content: center; font-size: 11px; font-weight: 700;
            border: 1px solid var(--border); background: white; cursor: pointer;
            transition: background .2s ease, border-color .2s ease;
        }
        .action-button.edit { color: var(--blue-bright); }
        .action-button.edit:hover { background: var(--blue-light); border-color: #BDD3EF; }
        .action-button.activate { color: var(--success); }
        .action-button.activate:hover { background: var(--success-bg); border-color: #B9E6CE; }
        .action-button.deactivate { color: var(--danger); }
        .action-button.deactivate:hover { background: var(--danger-bg); border-color: #EFC0C0; }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty-icon {
            width: 56px; height: 56px; margin: 0 auto 14px; border-radius: 14px;
            background: var(--blue-light); color: var(--blue-bright); display: flex;
            align-items: center; justify-content: center; font-size: 23px;
        }
        .empty-state h3 { color: var(--navy); font-size: 16px; margin-bottom: 6px; }
        .empty-state p { font-size: 12px; }
        .no-results { display: none; text-align: center; padding: 40px; color: var(--muted); font-size: 12px; }

        body.collapsed .sidebar { width: var(--sidebar-small); }
        body.collapsed .main { margin-left: var(--sidebar-small); }
        body.collapsed .school-brand { justify-content: center; padding-left: 10px; padding-right: 10px; }
        body.collapsed .school-name, body.collapsed .section-title { display: none; }
        body.collapsed .menu-item { justify-content: center; padding-left: 0; padding-right: 0; }
        body.collapsed .menu-name, body.collapsed .soon { display: none; }
        body.collapsed .collapse-button { width: 46px; margin-left: auto; margin-right: auto; padding: 0; }
        body.collapsed .collapse-text { display: none; }
        body.collapsed .admin-profile { justify-content: center; padding: 7px; }
        body.collapsed .admin-info { display: none; }

        @media (max-width: 1000px) {
            .page { padding: 22px; }
            .stats { gap: 12px; }
            .users-table th, .users-table td { padding-left: 16px; padding-right: 16px; }
        }
        @media (max-width: 800px) {
            .sidebar { width: var(--sidebar-small); }
            .main { margin-left: var(--sidebar-small); }
            .school-name, .section-title, .menu-name, .soon, .collapse-text, .admin-info { display: none; }
            .school-brand { justify-content: center; padding-left: 10px; padding-right: 10px; }
            .menu-item { justify-content: center; padding-left: 0; padding-right: 0; }
            .collapse-button { width: 46px; margin-left: auto; margin-right: auto; }
            .admin-profile { justify-content: center; }
            .page-header { align-items: flex-start; }
            .card-toolbar { align-items: flex-start; flex-direction: column; }
            .search-box { width: 100%; }
        }
        @media (max-width: 600px) {
            .topbar { padding: 14px 17px; }
            .page-title h1 { font-size: 23px; }
            .page { padding: 17px; }
            .stats { grid-template-columns: 1fr; }
            .page-header { flex-direction: column; }
            .add-button { width: 100%; }
            .toolbar-title h3 { font-size: 18px; }
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

<main class="main">
    <header class="topbar">
        <div class="page-title">
            <h1>Users</h1>
            <p>Manage system users and their access.</p>
        </div>
        <div class="top-actions">
            <button type="button" class="notification" aria-label="Notifications">
                🔔<span class="notification-dot"></span>
            </button>
            <div class="top-avatar" aria-label="Administrator"><?php echo htmlspecialchars($userInitials, ENT_QUOTES, "UTF-8"); ?></div>
        </div>
    </header>

    <section class="page">
        <div class="page-header">
            <div class="page-header-left">
                <h2>User Management</h2>
                <p>View, add, edit, and manage system user accounts.</p>
            </div>
            <a href="/SMS/admin/users/add_user.php" class="add-button">
                <span aria-hidden="true">+</span> Add User
            </a>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">TOTAL USERS</div>
                        <div class="stat-number"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">♙</div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">ACTIVE USERS</div>
                        <div class="stat-number"><?php echo $active_users; ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">✓</div>
                </div>
            </div>
            <div class="stat">
                <div class="stat-top">
                    <div>
                        <div class="stat-label">INACTIVE USERS</div>
                        <div class="stat-number"><?php echo $inactive_users; ?></div>
                    </div>
                    <div class="stat-icon" aria-hidden="true">Ⅱ</div>
                </div>
            </div>
        </div>

        <div class="users-card">
            <div class="card-toolbar">
                <div class="toolbar-title">
                    <h3>System Users</h3>
                    <p>All registered user accounts</p>
                </div>
                <div class="search-box">
                    <span class="search-icon" aria-hidden="true">⌕</span>
                    <input type="text" id="userSearch" placeholder="Search users..." autocomplete="off" aria-label="Search users">
                </div>
            </div>

            <div class="table-container">
                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="users-table" id="usersTable">
                        <thead>
                            <tr>
                                <th scope="col">USER</th>
                                <th scope="col">USERNAME</th>
                                <th scope="col">EMAIL</th>
                                <th scope="col">ROLE</th>
                                <th scope="col">STATUS</th>
                                <th scope="col">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($user = $result->fetch_assoc()): ?>
                            <?php
                                $name = trim((string) ($user["full_name"] ?? ""));
                                $name_parts = preg_split('/\s+/', $name) ?: [];
                                $initials = "";
                                if (!empty($name_parts[0])) {
                                    $initials .= strtoupper(substr($name_parts[0], 0, 1));
                                }
                                if (count($name_parts) > 1) {
                                    $last = end($name_parts);
                                    $initials .= strtoupper(substr((string) $last, 0, 1));
                                }
                                if ($initials === "") {
                                    $initials = "U";
                                }
                                $user_id = (int) $user["user_id"];
                                $status = (string) ($user["status"] ?? "Inactive");
                            ?>
                            <tr class="user-row">
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, "UTF-8"); ?></div>
                                        <div class="user-details">
                                            <strong><?php echo htmlspecialchars((string) $user["full_name"], ENT_QUOTES, "UTF-8"); ?></strong>
                                            <span>User ID: <?php echo $user_id; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars((string) $user["username"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><?php echo htmlspecialchars((string) $user["email"], ENT_QUOTES, "UTF-8"); ?></td>
                                <td><span class="role-badge"><?php echo htmlspecialchars((string) $user["role_name"], ENT_QUOTES, "UTF-8"); ?></span></td>
                                <td>
                                    <?php if ($status === "Active"): ?>
                                        <span class="status-badge active"><span class="status-dot"></span>Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive"><span class="status-dot"></span>Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="/SMS/admin/users/edit_user.php?id=<?php echo $user_id; ?>" class="action-button edit">Edit</a>
                                        <form action="/SMS/admin/users/toggle_user_status.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                            <?php if ($status === "Active"): ?>
                                                <button type="submit" class="action-button deactivate">Deactivate</button>
                                            <?php else: ?>
                                                <button type="submit" class="action-button activate">Activate</button>
                                            <?php endif; ?>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="no-results" id="noResults">No users found matching your search.</div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon" aria-hidden="true">♙</div>
                        <h3>No Users Found</h3>
                        <p>There are currently no user accounts in the system.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    const searchInput = document.getElementById("userSearch");
    const rows = document.querySelectorAll(".user-row");
    const noResults = document.getElementById("noResults");

    if (!searchInput) return;

    searchInput.addEventListener("input", function () {
        const searchValue = this.value.toLowerCase().trim();
        let visibleRows = 0;

        rows.forEach(function (row) {
            const rowText = row.textContent.toLowerCase();
            const matches = rowText.includes(searchValue);
            row.style.display = matches ? "" : "none";
            if (matches) visibleRows++;
        });

        if (noResults) {
            noResults.style.display = (visibleRows === 0 && searchValue !== "") ? "block" : "none";
        }
    });
})();
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
