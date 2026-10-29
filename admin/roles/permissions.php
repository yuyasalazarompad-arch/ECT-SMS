<?php

require_once "../../includes/auth_check.php";

include "../../config/database.php";

/*
|--------------------------------------------------------------------------
| ADMINISTRATOR AUTHORIZATION
|--------------------------------------------------------------------------
*/

$currentUserId = $_SESSION["user_id"] ?? 0;

$adminCheck = $conn->prepare(
    "SELECT r.role_name
     FROM users u
     INNER JOIN roles r
        ON u.role_id = r.role_id
     WHERE u.user_id = ?
     LIMIT 1"
);

$adminCheck->bind_param(
    "i",
    $currentUserId
);

$adminCheck->execute();

$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows !== 1) {

    $adminCheck->close();

    http_response_code(403);

    die("Access denied.");
}

$currentRole = $adminResult->fetch_assoc();

$adminCheck->close();


if ($currentRole["role_name"] !== "Administrator") {

    http_response_code(403);

    die(
        "Access denied. Only an Administrator can manage role permissions."
    );
}


/*
|--------------------------------------------------------------------------
| GET ROLE ID
|--------------------------------------------------------------------------
*/

$role_id = intval(
    $_GET["id"] ?? 0
);


if ($role_id <= 0) {

    header(
        "Location: /SMS/admin/roles/roles.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ROLE INFORMATION
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare(
    "SELECT
        role_id,
        role_name,
        description,
        status
     FROM roles
     WHERE role_id = ?
     LIMIT 1"
);

if (!$stmt) {

    die("Unable to prepare role lookup.");
}


$stmt->bind_param(
    "i",
    $role_id
);

$stmt->execute();

$result = $stmt->get_result();

$role = $result->fetch_assoc();

$stmt->close();


if (!$role) {

    header(
        "Location: /SMS/admin/roles/roles.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET ALL PERMISSIONS
|--------------------------------------------------------------------------
*/

$permissions_result = $conn->query(
    "SELECT
        permission_id,
        permission_name,
        description
     FROM permissions
     ORDER BY permission_id ASC"
);


if (!$permissions_result) {

    die("Unable to load system permissions.");
}


/*
|--------------------------------------------------------------------------
| GET PERMISSIONS ALREADY ASSIGNED TO THIS ROLE
|--------------------------------------------------------------------------
*/

$assigned_permissions = [];

$stmt = $conn->prepare(
    "SELECT permission_id
     FROM role_permissions
     WHERE role_id = ?"
);

if (!$stmt) {

    die(
        "Unable to prepare assigned permission lookup."
    );
}


$stmt->bind_param(
    "i",
    $role_id
);

$stmt->execute();

$assigned_result = $stmt->get_result();


while (
    $row = $assigned_result->fetch_assoc()
) {

    $assigned_permissions[] =
        (int) $row["permission_id"];
}


$stmt->close();


/*
|--------------------------------------------------------------------------
| COUNT ASSIGNED PERMISSIONS
|--------------------------------------------------------------------------
*/

$assigned_count =
    count($assigned_permissions);


/*
|--------------------------------------------------------------------------
| GROUP PERMISSIONS
|--------------------------------------------------------------------------
*/

$permission_groups = [

    "User Management" => [

        "View Users",
        "Manage Users"

    ],

    "Role Management" => [

        "View Roles",
        "Manage Roles"

    ],

    "Student Management" => [

        "View Students",
        "Manage Students"

    ],

    "Enrollment" => [

        "View Enrollment",
        "Manage Enrollment"

    ],

    "Grades" => [

        "View Grades",
        "Manage Grades"

    ],

    "Attendance" => [

        "View Attendance",
        "Manage Attendance"

    ],

    "Finance" => [

        "View Finance",
        "Manage Finance"

    ],

    "Learning Management System" => [

        "Access LMS"

    ],

    "School Administration" => [

        "Manage School Settings",
        "Manage School Year"

    ],

    "Reports" => [

        "View Reports",
        "Generate Reports"

    ]

];


/*
|--------------------------------------------------------------------------
| STORE ALL PERMISSIONS IN AN ARRAY
|--------------------------------------------------------------------------
*/

$all_permissions = [];

while (
    $permission =
    $permissions_result->fetch_assoc()
) {

    $all_permissions[
        $permission["permission_name"]
    ] = $permission;
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
        Permissions - SMS
    </title>


    <style>

        * {
            box-sizing: border-box;
        }


        body {

            margin: 0;

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #F4F7FB;

            color: #243447;
        }


        /* SIDEBAR */

        .sidebar {

            position: fixed;

            left: 0;
            top: 0;
            bottom: 0;

            width: 245px;

            background: #12355B;

            color: white;

            padding: 24px 16px;

            transition: width 0.25s ease;

            overflow-y: auto;
        }


        .sidebar.collapsed {

            width: 78px;
        }


        .school-brand {

            text-align: center;

            margin-bottom: 28px;
        }


        .school-logo {

            width: 68px;
            height: 68px;

            margin: 0 auto 10px;

            background: white;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

            color: #12355B;

            font-weight: 700;

            font-size: 18px;
        }


        .school-brand h2 {

            margin: 0;

            font-size: 17px;

            white-space: nowrap;
        }


        .school-brand p {

            margin: 6px 0 0;

            font-size: 11px;

            color: #D8E6F2;

            white-space: nowrap;
        }


        .sidebar.collapsed
        .school-brand h2,

        .sidebar.collapsed
        .school-brand p {

            display: none;
        }


        .sidebar a {

            display: flex;

            align-items: center;

            gap: 10px;

            padding: 12px 13px;

            margin-bottom: 6px;

            color: #EAF3FA;

            text-decoration: none;

            border-radius: 8px;

            font-size: 14px;

            transition: 0.2s ease;

            white-space: nowrap;
        }


        .sidebar a:hover {

            background: #1E5A96;
        }


        .sidebar a.active {

            background: #1E5A96;

            color: white;

            font-weight: 600;
        }


        .sidebar.collapsed a {

            justify-content: center;

            padding-left: 8px;

            padding-right: 8px;

            font-size: 0;
        }


        .sidebar.collapsed a::before {

            content: "•";

            font-size: 20px;

            color: white;
        }


        .sidebar-bottom {

            margin-top: 28px;

            padding-top: 18px;

            border-top: 1px solid
                rgba(255,255,255,0.15);
        }


        .logout-link {

            color: #FFDADA !important;
        }


        /* COLLAPSE BUTTON */

        .collapse-button {

            width: 100%;

            border: 0;

            background: transparent;

            color: white;

            cursor: pointer;

            text-align: center;

            font-size: 18px;

            padding: 8px;

            margin-bottom: 12px;
        }


        /* MAIN */

        .main-content {

            margin-left: 245px;

            min-height: 100vh;

            padding: 30px;

            transition: margin-left 0.25s ease;
        }


        .main-content.expanded {

            margin-left: 78px;
        }


        /* TOPBAR */

        .topbar {

            margin-bottom: 25px;
        }


        .topbar h1 {

            margin: 0;

            color: #12355B;

            font-size: 25px;
        }


        .topbar p {

            margin: 7px 0 0;

            color: #718096;

            font-size: 13px;
        }


        /* CARD */

        .card {

            background: white;

            border: 1px solid #E1E8F0;

            border-radius: 12px;

            padding: 28px;

            margin-bottom: 20px;

            box-shadow:
                0 4px 14px
                rgba(18,53,91,0.05);
        }


        /* ROLE INFORMATION */

        .role-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            flex-wrap: wrap;
        }


        .role-label {

            margin: 0 0 6px;

            color: #6B7785;

            font-size: 13px;
        }


        .role-name {

            margin: 0;

            color: #12355B;

            font-size: 24px;
        }


        .role-badges {

            display: flex;

            align-items: center;

            gap: 10px;

            flex-wrap: wrap;
        }


        .badge {

            display: inline-block;

            padding: 7px 14px;

            border-radius: 20px;

            font-size: 13px;

            font-weight: bold;
        }


        .badge-active {

            background: #E8F5EE;

            color: #2E8B57;
        }


        .badge-inactive {

            background: #FBEAEA;

            color: #C94C4C;
        }


        .badge-permissions {

            background: #EAF3FA;

            color: #12355B;
        }


        .description-section {

            margin-top: 20px;

            padding-top: 18px;

            border-top: 1px solid #EEF2F5;
        }


        .description-label {

            margin: 0 0 6px;

            color: #6B7785;

            font-size: 13px;
        }


        .description {

            margin: 0;

            color: #243447;

            font-size: 14px;

            line-height: 1.6;
        }


        /* PERMISSION HEADER */

        .permission-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            flex-wrap: wrap;

            margin-bottom: 22px;
        }


        .permission-header h2 {

            margin: 0;

            color: #12355B;

            font-size: 20px;
        }


        .permission-header p {

            color: #6B7785;

            margin: 6px 0 0;

            font-size: 13px;
        }


        .permission-actions {

            display: flex;

            gap: 8px;

            flex-wrap: wrap;
        }


        /* PERMISSION GRID */

        .permission-grid {

            display: grid;

            grid-template-columns:
                repeat(2, minmax(0, 1fr));

            gap: 20px;
        }


        .permission-group {

            border: 1px solid #DCE5ED;

            border-radius: 10px;

            padding: 20px;

            background: #FFFFFF;
        }


        .group-title {

            margin: 0;

            color: #12355B;

            font-size: 16px;
        }


        .group-title-wrapper {

            margin-bottom: 12px;

            padding-bottom: 10px;

            border-bottom: 1px solid #EEF2F5;
        }


        .permission-item {

            display: flex;

            align-items: flex-start;

            gap: 12px;

            padding: 12px 0;

            cursor: pointer;
        }


        .permission-item input {

            width: 18px;

            height: 18px;

            margin-top: 2px;

            cursor: pointer;
        }


        .permission-name {

            color: #12355B;

            font-size: 14px;
        }


        .permission-description {

            color: #6B7785;

            font-size: 12px;

            line-height: 1.5;
        }


        /* BUTTONS */

        .btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            padding: 10px 17px;

            border-radius: 7px;

            border: none;

            text-decoration: none;

            cursor: pointer;

            font-size: 12px;

            font-weight: 600;
        }


        .btn-primary {

            background: #1E5A96;

            color: white;
        }


        .btn-primary:hover {

            background: #174A7D;
        }


        .btn-secondary {

            background: #E9EEF4;

            color: #34495E;
        }


        .btn-secondary:hover {

            background: #DDE5ED;
        }


        .form-actions {

            display: flex;

            gap: 10px;

            margin-top: 25px;

            flex-wrap: wrap;
        }


        /* RESPONSIVE */

        @media (max-width: 850px) {

            .permission-grid {

                grid-template-columns: 1fr;
            }

        }


        @media (max-width: 800px) {

            .sidebar {

                width: 210px;
            }

            .main-content {

                margin-left: 210px;

                padding: 20px;
            }

            .main-content.expanded {

                margin-left: 78px;
            }
        }


        @media (max-width: 650px) {

            .sidebar {

                width: 78px;
            }

            .sidebar .school-brand h2,

            .sidebar .school-brand p {

                display: none;
            }

            .sidebar a {

                justify-content: center;

                font-size: 0;
            }

            .sidebar a::before {

                content: "•";

                font-size: 20px;
            }

            .main-content {

                margin-left: 78px;

                padding: 18px;
            }

            .card {

                padding: 20px;
            }

            .role-name {

                font-size: 21px;
            }
        }

    </style>

</head>


<body>


    <!-- SIDEBAR -->

    <div
        class="sidebar"
        id="sidebar"
    >

        <button
            type="button"
            class="collapse-button"
            id="collapseButton"
            aria-label="Collapse sidebar"
        >
            ☰
        </button>


        <div class="school-brand">

            <div class="school-logo">
                FBC
            </div>

            <h2>
                Fullbright College
            </h2>

            <p>
                School Management System
            </p>

        </div>


        <a href="/SMS/admin/dashboard.php">
            Dashboard
        </a>


        <a
            href="/SMS/admin/roles/roles.php"
            class="active"
        >
            Roles & Permissions
        </a>


        <a href="/SMS/admin/users/users.php">
            Users
        </a>


        <a href="/SMS/admin/school_settings/school_settings.php">
            School Settings
        </a>


        <a href="/SMS/admin/school_year/school_year.php">
            School Year
        </a>


        <div class="sidebar-bottom">

            <a
                href="/SMS/auth/logout.php"
                class="logout-link"
            >
                Logout
            </a>

        </div>

    </div>


    <!-- MAIN CONTENT -->

    <div
        class="main-content"
        id="mainContent"
    >


        <!-- TOPBAR -->

        <div class="topbar">

            <h1>
                Permissions
            </h1>

            <p>
                Manage the system permissions assigned to this role.
            </p>

        </div>


        <!-- ROLE INFORMATION -->

        <div class="card">

            <div class="role-header">


                <div>

                    <p class="role-label">
                        Role
                    </p>

                    <h2 class="role-name">

                        <?php

                        echo htmlspecialchars(
                            $role["role_name"],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>

                    </h2>

                </div>


                <div class="role-badges">


                    <?php if (
                        $role["status"] === "Active"
                    ): ?>

                        <span
                            class="badge badge-active"
                        >
                            Active
                        </span>

                    <?php else: ?>

                        <span
                            class="badge badge-inactive"
                        >
                            Inactive
                        </span>

                    <?php endif; ?>


                    <span
                        class="badge badge-permissions"
                    >

                        <?php
                        echo $assigned_count;
                        ?>

                        Permissions

                    </span>

                </div>

            </div>


            <div class="description-section">

                <p class="description-label">
                    Description
                </p>

                <p class="description">

                    <?php

                    $description =
                        trim(
                            $role["description"] ?? ""
                        );

                    echo $description !== ""
                        ? htmlspecialchars(
                            $description,
                            ENT_QUOTES,
                            "UTF-8"
                        )
                        : "No description provided.";

                    ?>

                </p>

            </div>

        </div>


        <!-- PERMISSIONS -->

        <div class="card">


            <div class="permission-header">

                <div>

                    <h2>
                        Available Permissions
                    </h2>

                    <p>
                        Select the permissions that this role should have.
                    </p>

                </div>


                <div class="permission-actions">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        id="selectAllButton"
                    >
                        Select All
                    </button>


                    <button
                        type="button"
                        class="btn btn-secondary"
                        id="clearAllButton"
                    >
                        Clear All
                    </button>

                </div>

            </div>


            <form
                method="POST"
                action="/SMS/admin/roles/save_permissions.php"
            >


                <input
                    type="hidden"
                    name="role_id"
                    value="<?php
                        echo (int) $role["role_id"];
                    ?>"
                >


                <div class="permission-grid">


                    <?php foreach (
                        $permission_groups
                        as $group_name =>
                        $group_permissions
                    ): ?>


                        <div class="permission-group">


                            <div class="group-title-wrapper">

                                <h3 class="group-title">

                                    <?php

                                    echo htmlspecialchars(
                                        $group_name,
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                    ?>

                                </h3>

                            </div>


                            <?php foreach (
                                $group_permissions
                                as $permission_name
                            ): ?>


                                <?php

                                if (
                                    !isset(
                                        $all_permissions[
                                            $permission_name
                                        ]
                                    )
                                ) {
                                    continue;
                                }


                                $permission =
                                    $all_permissions[
                                        $permission_name
                                    ];

                                $permissionId =
                                    (int)
                                    $permission[
                                        "permission_id"
                                    ];

                                $isChecked =
                                    in_array(
                                        $permissionId,
                                        $assigned_permissions,
                                        true
                                    );

                                ?>


                                <label
                                    class="permission-item"
                                >


                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="<?php
                                            echo $permissionId;
                                        ?>"
                                        <?php
                                        echo $isChecked
                                            ? "checked"
                                            : "";
                                        ?>
                                    >


                                    <span>

                                        <strong
                                            class="permission-name"
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $permission[
                                                    "permission_name"
                                                ],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            );

                                            ?>

                                        </strong>


                                        <br>


                                        <small
                                            class="
                                            permission-description
                                            "
                                        >

                                            <?php

                                            $permissionDescription =
                                                trim(
                                                    $permission[
                                                        "description"
                                                    ] ?? ""
                                                );

                                            echo $permissionDescription !== ""
                                                ? htmlspecialchars(
                                                    $permissionDescription,
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                )
                                                : "No description provided.";

                                            ?>

                                        </small>

                                    </span>

                                </label>


                            <?php endforeach; ?>


                        </div>


                    <?php endforeach; ?>


                </div>


                <!-- BUTTONS -->

                <div class="form-actions">


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Permissions
                    </button>


                    <a
                        href="/SMS/admin/roles/roles.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                </div>


            </form>

        </div>

    </div>


    <script>

        const sidebar =
            document.getElementById("sidebar");

        const mainContent =
            document.getElementById("mainContent");

        const collapseButton =
            document.getElementById("collapseButton");


        /*
        |--------------------------------------------------------------------------
        | SIDEBAR STATE
        |--------------------------------------------------------------------------
        */

        function applySidebarState() {

            const collapsed =
                localStorage.getItem(
                    "smsSidebarCollapsed"
                ) === "true";


            if (collapsed) {

                sidebar.classList.add(
                    "collapsed"
                );

                mainContent.classList.add(
                    "expanded"
                );

            } else {

                sidebar.classList.remove(
                    "collapsed"
                );

                mainContent.classList.remove(
                    "expanded"
                );
            }
        }


        collapseButton.addEventListener(
            "click",
            function () {

                const collapsed =
                    sidebar.classList.contains(
                        "collapsed"
                    );


                localStorage.setItem(
                    "smsSidebarCollapsed",
                    collapsed
                        ? "false"
                        : "true"
                );


                applySidebarState();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | SELECT ALL
        |--------------------------------------------------------------------------
        */

        document
            .getElementById("selectAllButton")
            .addEventListener(
                "click",
                function () {

                    const checkboxes =
                        document.querySelectorAll(
                            'input[name="permissions[]"]'
                        );


                    checkboxes.forEach(
                        function (checkbox) {

                            checkbox.checked = true;

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | CLEAR ALL
        |--------------------------------------------------------------------------
        */

        document
            .getElementById("clearAllButton")
            .addEventListener(
                "click",
                function () {

                    const checkboxes =
                        document.querySelectorAll(
                            'input[name="permissions[]"]'
                        );


                    checkboxes.forEach(
                        function (checkbox) {

                            checkbox.checked = false;

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | APPLY SIDEBAR
        |--------------------------------------------------------------------------
        */

        applySidebarState();

    </script>


</body>

</html>