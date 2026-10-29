<?php
/*
|--------------------------------------------------------------------------
| SMS - SHARED ADMIN SIDEBAR
|--------------------------------------------------------------------------
| Shared sidebar for Dashboard, Users, Roles, School Settings and School Year.
| The page must load auth_check.php before including this file.
|--------------------------------------------------------------------------
*/

$currentSidebarPage = basename($_SERVER["PHP_SELF"] ?? "");

$sidebarUserName = trim((string) (
    $currentUserName ?? ($_SESSION["full_name"] ?? "Administrator")
));

$sidebarRoleName = trim((string) (
    $currentRoleName ?? ($_SESSION["role_name"] ?? "Administrator")
));

if ($sidebarUserName === "") {
    $sidebarUserName = "Administrator";
}

if ($sidebarRoleName === "") {
    $sidebarRoleName = "Administrator";
}

/* User initials */
$sidebarInitials = "U";
$sidebarNameParts = preg_split('/\s+/', $sidebarUserName);

if (!empty($sidebarNameParts[0])) {
    $sidebarInitials = strtoupper(substr($sidebarNameParts[0], 0, 1));

    if (count($sidebarNameParts) > 1) {
        $lastNamePart = $sidebarNameParts[count($sidebarNameParts) - 1];

        if ($lastNamePart !== "") {
            $sidebarInitials .= strtoupper(substr($lastNamePart, 0, 1));
        }
    }
}

/* Profile picture */
$sidebarProfilePicture = "";

if (isset($currentUserId, $conn)) {
    $profileStmt = $conn->prepare("
        SELECT profile_picture
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    if ($profileStmt) {
        $profileStmt->bind_param("i", $currentUserId);
        $profileStmt->execute();

        $profileResult = $profileStmt->get_result();

        if ($profileResult && $profileResult->num_rows === 1) {
            $profileRow = $profileResult->fetch_assoc();
            $sidebarProfilePicture = $profileRow["profile_picture"] ?? "";
        }

        $profileStmt->close();
    }
}

$sidebarProfilePicturePath = "";

if ($sidebarProfilePicture !== "") {
    $sidebarProfilePicturePath =
        "/SMS/assets/uploads/users/" .
        rawurlencode(basename($sidebarProfilePicture));
}

/* Default school information */
$sidebarSchoolName = "FULBRIGHT COLLEGE";
$sidebarSchoolCode = "FBC";
$sidebarSchoolTagline = "Full of bright";
$sidebarSchoolLogo = "";

/* Load saved school information */
if (isset($conn)) {
    $schoolSidebarStmt = $conn->query("
        SELECT
            school_name,
            school_code,
            school_tagline,
            logo_filename
        FROM school_settings
        ORDER BY setting_id ASC
        LIMIT 1
    ");

    if ($schoolSidebarStmt && $schoolSidebarStmt->num_rows > 0) {
        $schoolSidebar = $schoolSidebarStmt->fetch_assoc();

        if (!empty($schoolSidebar["school_name"])) {
            $sidebarSchoolName = $schoolSidebar["school_name"];
        }

        if (!empty($schoolSidebar["school_code"])) {
            $sidebarSchoolCode = $schoolSidebar["school_code"];
        }

        if (!empty($schoolSidebar["school_tagline"])) {
            $sidebarSchoolTagline = $schoolSidebar["school_tagline"];
        }

        if (!empty($schoolSidebar["logo_filename"])) {
            $sidebarSchoolLogo = $schoolSidebar["logo_filename"];
        }
    }
}

$sidebarSchoolLogoPath = "";

if ($sidebarSchoolLogo !== "") {
    $sidebarSchoolLogoPath =
        "/SMS/assets/uploads/school/" .
        rawurlencode(basename($sidebarSchoolLogo));
}

/* Escape displayed values */
$displaySidebarSchoolName = htmlspecialchars(
    $sidebarSchoolName,
    ENT_QUOTES,
    "UTF-8"
);

$displaySidebarSchoolCode = htmlspecialchars(
    $sidebarSchoolCode,
    ENT_QUOTES,
    "UTF-8"
);

$displaySidebarSchoolTagline = htmlspecialchars(
    $sidebarSchoolTagline,
    ENT_QUOTES,
    "UTF-8"
);

$displaySidebarUserName = htmlspecialchars(
    $sidebarUserName,
    ENT_QUOTES,
    "UTF-8"
);

$displaySidebarRoleName = htmlspecialchars(
    $sidebarRoleName,
    ENT_QUOTES,
    "UTF-8"
);

$displaySidebarInitials = htmlspecialchars(
    $sidebarInitials,
    ENT_QUOTES,
    "UTF-8"
);

/* Determine the active navigation item */
if (!function_exists('sidebarActive')) {
    function sidebarActive($page)
    {
        $currentPage = basename($_SERVER['PHP_SELF'] ?? '');

        // Keep Roles & Permissions highlighted on its subpages.
        if (
            $page === 'roles.php' &&
            in_array($currentPage, [
                'roles.php',
                'add_role.php',
                'edit_role.php'
            ], true)
        ) {
            return 'active';
        }

        return $currentPage === $page ? 'active' : '';
    }
}
?>

<style>
:root {
    --sidebar-width: 260px;
    --sidebar-small: 78px;
    --navy: #0b2850;
    --navy-dark: #071d3b;
    --blue: #1769d1;
    --danger: #d64545;
}

/* Sidebar */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    background: linear-gradient(180deg, #0b315e 0%, #09284d 100%);
    color: #ffffff;
    z-index: 1000;
    display: flex;
    flex-direction: column;
    box-shadow: 5px 0 20px rgba(0, 0, 0, 0.08);
    transition: width 0.25s ease;
    overflow: hidden;
    box-sizing: border-box;
    font-family: inherit;
}

.sidebar *,
.sidebar *::before,
.sidebar *::after {
    box-sizing: border-box;
}

/* School branding */
.sidebar .school-brand {
    min-height: 105px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.sidebar .school-logo {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #ffffff;
    color: var(--navy);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 13px;
    flex-shrink: 0;
    overflow: hidden;
}

.sidebar .school-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    padding: 5px;
}

.sidebar .school-logo-text {
    text-align: center;
    line-height: 1.1;
}

.sidebar .school-name {
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar .school-name strong {
    display: block;
    font-size: 18px;
    line-height: 1.3;
    font-weight: 700;
    margin-bottom: 4px;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar .school-name span {
    display: block;
    font-size: 11px;
    line-height: 1.4;
    color: rgba(255, 255, 255, 0.62);
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Navigation */
.sidebar .sidebar-nav {
    flex: 1;
    min-height: 0;
    padding: 18px 12px;
    overflow-y: auto;
    overflow-x: hidden;
}

.sidebar .sidebar-nav::-webkit-scrollbar {
    width: 5px;
}

.sidebar .sidebar-nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 10px;
}

/* Section headings */
.sidebar .section-title {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.3px;
    color: rgba(255, 255, 255, 0.48);
    padding: 8px 12px;
    margin-bottom: 5px;
    white-space: nowrap;
}

.sidebar .section-title.admin {
    margin-top: 20px;
}

/* Navigation items */
.sidebar .menu-item {
    height: 48px;
    width: 100%;
    margin-bottom: 5px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 0 13px;
    color: rgba(255, 255, 255, 0.78);
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
    text-decoration: none;
    border: none;
    background: transparent;
    box-shadow: none;
    cursor: pointer;
    transition: background 0.2s ease, color 0.2s ease;
}

.sidebar .menu-item:hover {
    background: rgba(255, 255, 255, 0.09);
    color: #ffffff;
}

.sidebar .menu-item.active {
    background: var(--blue);
    color: #ffffff;
    box-shadow: 0 5px 14px rgba(23, 105, 209, 0.25);
    border: none;
}

/* Icons */
.sidebar .menu-icon {
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

/* Menu labels */
.sidebar .menu-name {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Coming soon labels */
.sidebar .soon {
    font-size: 8px;
    padding: 3px 6px;
    border-radius: 5px;
    background: rgba(255, 255, 255, 0.08);
    color: rgba(255, 255, 255, 0.55);
}

/* Bottom section */
.sidebar .sidebar-bottom {
    flex-shrink: 0;
    padding: 12px;
    background: rgba(0, 0, 0, 0.08);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Collapse button */
.sidebar .collapse-button {
    width: 100%;
    height: 44px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.09);
    color: #ffffff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-family: inherit;
    font-size: 12px;
    font-weight: 700;
    transition: background 0.2s ease;
}

.sidebar .collapse-button:hover {
    background: var(--blue);
}

.sidebar .collapse-icon {
    font-size: 14px;
}

/* User profile */
.sidebar .admin-profile {
    margin-top: 9px;
    min-width: 0;
    height: 54px;
    padding: 7px;
    display: flex;
    align-items: center;
    gap: 9px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.06);
}

.sidebar .admin-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffffff;
    color: var(--navy);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
    overflow: hidden;
}

.sidebar .admin-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sidebar .admin-info {
    min-width: 0;
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
}

.sidebar .admin-info strong {
    display: block;
    font-size: 11px;
    line-height: 1.3;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar .admin-info span {
    display: block;
    margin-top: 3px;
    font-size: 9px;
    color: rgba(255, 255, 255, 0.65);
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Logout */
.sidebar .logout-link {
    margin-top: 8px;
    color: rgba(255, 255, 255, 0.82);
    background: rgba(214, 69, 69, 0.10);
}

.sidebar .logout-link:hover {
    background: var(--danger);
    color: #ffffff;
}

/* Content spacing.
   Use one of these classes on the page's main content container. */
body .main {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    transition: margin-left 0.25s ease;
}

body .main-content {
    margin-left: var(--sidebar-width);
    transition: margin-left 0.25s ease;
}

/* Collapsed sidebar */
body.collapsed .sidebar {
    width: var(--sidebar-small);
}

body.collapsed .main,
body.collapsed .main-content {
    margin-left: var(--sidebar-small);
}

body.collapsed .sidebar .school-brand {
    justify-content: center;
    padding-left: 10px;
    padding-right: 10px;
}

body.collapsed .sidebar .school-name,
body.collapsed .sidebar .section-title,
body.collapsed .sidebar .menu-name,
body.collapsed .sidebar .soon,
body.collapsed .sidebar .admin-info {
    display: none;
}

body.collapsed .sidebar .menu-item {
    justify-content: center;
    padding-left: 0;
    padding-right: 0;
}

body.collapsed .sidebar .sidebar-bottom {
    padding: 10px;
}

body.collapsed .sidebar .collapse-button {
    width: 46px;
    margin-left: auto;
    margin-right: auto;
    padding: 0;
}

body.collapsed .sidebar .collapse-text {
    display: none;
}

body.collapsed .sidebar .admin-profile {
    justify-content: center;
    padding: 7px;
}

/* Match the Dashboard's compact behavior on smaller screens */
@media (max-width: 900px) {
    .sidebar {
        width: var(--sidebar-small);
    }

    body .main,
    body .main-content {
        margin-left: var(--sidebar-small);
    }

    .sidebar .school-brand {
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }

    .sidebar .school-name,
    .sidebar .section-title,
    .sidebar .menu-name,
    .sidebar .soon,
    .sidebar .admin-info {
        display: none;
    }

    .sidebar .menu-item {
        justify-content: center;
        padding-left: 0;
        padding-right: 0;
    }

    .sidebar .collapse-text {
        display: none;
    }

    .sidebar .collapse-button {
        width: 46px;
        margin-left: auto;
        margin-right: auto;
        padding: 0;
    }
}
</style>

<aside class="sidebar">
    <div class="school-brand">
        <div class="school-logo">
            <?php if ($sidebarSchoolLogoPath !== ""): ?>
                <img
                    src="<?= htmlspecialchars($sidebarSchoolLogoPath, ENT_QUOTES, "UTF-8") ?>"
                    alt="School Logo"
                >
            <?php else: ?>
                <div class="school-logo-text">
                    <?= $displaySidebarSchoolCode ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="school-name">
            <strong><?= $displaySidebarSchoolName ?></strong>
            <span><?= $displaySidebarSchoolTagline ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="section-title">MAIN</div>

        <a
            href="/SMS/admin/dashboard.php"
            class="menu-item <?= sidebarActive("dashboard.php") ?>"
        >
            <span class="menu-icon">⌂</span>
            <span class="menu-name">Dashboard</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">♟</span>
            <span class="menu-name">Students</span>
            <span class="soon">Soon</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">▣</span>
            <span class="menu-name">Enrollment</span>
            <span class="soon">Soon</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">♙</span>
            <span class="menu-name">Faculty</span>
            <span class="soon">Soon</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">◷</span>
            <span class="menu-name">Attendance</span>
            <span class="soon">Soon</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">▤</span>
            <span class="menu-name">Grades</span>
            <span class="soon">Soon</span>
        </a>

        <a href="#" class="menu-item" onclick="return false;" aria-disabled="true">
            <span class="menu-icon">▥</span>
            <span class="menu-name">Reports</span>
            <span class="soon">Soon</span>
        </a>

        <div class="section-title admin">ADMINISTRATION</div>

        <a
            href="/SMS/admin/users/users.php"
            class="menu-item <?= sidebarActive("users.php") ?>"
        >
            <span class="menu-icon">♙</span>
            <span class="menu-name">Users</span>
        </a>

        <a
            href="/SMS/admin/roles/roles.php"
            class="menu-item <?= sidebarActive("roles.php") ?>"
        >
            <span class="menu-icon">⚿</span>
            <span class="menu-name">Roles &amp; Permissions</span>
        </a>

        <a
            href="/SMS/admin/school_settings/school_settings.php"
            class="menu-item <?= sidebarActive("school_settings.php") ?>"
        >
            <span class="menu-icon">⚙</span>
            <span class="menu-name">School Settings</span>
        </a>

        <a
            href="/SMS/admin/school_year/school_year.php"
            class="menu-item <?= sidebarActive("school_year.php") ?>"
        >
            <span class="menu-icon">▣</span>
            <span class="menu-name">School Year</span>
        </a>
    </nav>

    <div class="sidebar-bottom">
        <button
            type="button"
            class="collapse-button"
            id="collapseButton"
            aria-label="Collapse or expand sidebar"
        >
            <span class="collapse-icon" id="collapseIcon">◀</span>
            <span class="collapse-text" id="collapseText">Collapse</span>
        </button>

        <div class="admin-profile">
            <div class="admin-avatar">
                <?php if ($sidebarProfilePicturePath !== ""): ?>
                    <img
                        src="<?= htmlspecialchars($sidebarProfilePicturePath, ENT_QUOTES, "UTF-8") ?>"
                        alt="Profile Picture"
                    >
                <?php else: ?>
                    <?= $displaySidebarInitials ?>
                <?php endif; ?>
            </div>

            <div class="admin-info">
                <strong><?= $displaySidebarUserName ?></strong>
                <span><?= $displaySidebarRoleName ?></span>
            </div>
        </div>

        <a
            href="/SMS/auth/logout.php"
            class="menu-item logout-link"
        >
            <span class="menu-icon">↪</span>
            <span class="menu-name">Logout</span>
        </a>
    </div>
</aside>

<script>
(function () {
    const sidebar = document.querySelector(".sidebar");
    const button = document.getElementById("collapseButton");
    const icon = document.getElementById("collapseIcon");
    const text = document.getElementById("collapseText");

    if (!sidebar || !button || !icon || !text) {
        return;
    }

    function updateSidebarButton() {
        const collapsed = document.body.classList.contains("collapsed");

        icon.textContent = collapsed ? "▶" : "◀";
        text.textContent = collapsed ? "Expand" : "Collapse";

        button.setAttribute(
            "aria-label",
            collapsed ? "Expand sidebar" : "Collapse sidebar"
        );
    }

    try {
        if (localStorage.getItem("smsSidebarCollapsed") === "true") {
            document.body.classList.add("collapsed");
        }
    } catch (error) {
        // Continue working if browser storage is unavailable.
    }

    updateSidebarButton();

    button.addEventListener("click", function () {
        document.body.classList.toggle("collapsed");

        try {
            localStorage.setItem(
                "smsSidebarCollapsed",
                document.body.classList.contains("collapsed") ? "true" : "false"
            );
        } catch (error) {
            // Sidebar still works if browser storage is unavailable.
        }

        updateSidebarButton();
    });
})();
</script>