<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| ADMINISTRATOR AUTHORIZATION
|--------------------------------------------------------------------------
*/
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$adminCheck = $conn->prepare(
    "SELECT r.role_name
     FROM users u
     INNER JOIN roles r ON u.role_id = r.role_id
     WHERE u.user_id = ?
     LIMIT 1"
);

$adminCheck->bind_param('i', $currentUserId);
$adminCheck->execute();
$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows !== 1) {
    $adminCheck->close();
    http_response_code(403);
    exit('Access denied.');
}

$currentRole = $adminResult->fetch_assoc();
$adminCheck->close();

if ($currentRole['role_name'] !== 'Administrator') {
    http_response_code(403);
    exit('Access denied. Only an Administrator can edit roles.');
}

/*
|--------------------------------------------------------------------------
| GET ROLE ID
|--------------------------------------------------------------------------
*/
$role_id = (int)($_GET['id'] ?? $_POST['role_id'] ?? 0);

if ($role_id <= 0) {
    header('Location: /SMS/admin/roles/roles.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET ROLE INFORMATION
|--------------------------------------------------------------------------
*/
$stmt = $conn->prepare(
    "SELECT role_id, role_name, description, status
     FROM roles
     WHERE role_id = ?
     LIMIT 1"
);

if (!$stmt) {
    exit('Unable to prepare role lookup.');
}

$stmt->bind_param('i', $role_id);
$stmt->execute();

$result = $stmt->get_result();
$role = $result->fetch_assoc();

$stmt->close();

if (!$role) {
    header('Location: /SMS/admin/roles/roles.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| PROTECT BUILT-IN ROLES
|--------------------------------------------------------------------------
| Role IDs 1-4:
| 1 - Administrator
| 2 - Registrar
| 3 - Faculty
| 4 - Student
|
| Their names and statuses cannot be changed.
|--------------------------------------------------------------------------
*/
$isProtectedRole = $role_id <= 4;

/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/
$message = '';
$message_type = '';

$role_name = $role['role_name'];
$description = $role['description'] ?? '';
$status = $role['status'];

/*
|--------------------------------------------------------------------------
| UPDATE ROLE
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedRoleId = (int)($_POST['role_id'] ?? 0);
    $postedRoleName = trim($_POST['role_name'] ?? '');
    $postedDescription = trim($_POST['description'] ?? '');
    $postedStatus = $_POST['status'] ?? '';

    if ($postedRoleId !== $role_id) {
        http_response_code(400);
        exit('Invalid role request.');
    }

    if ($postedRoleName === '') {
        $message = 'Role name is required.';
        $message_type = 'error';
    } elseif (mb_strlen($postedRoleName) < 2) {
        $message = 'Role name must be at least 2 characters.';
        $message_type = 'error';
    } elseif (!in_array($postedStatus, ['Active', 'Inactive'], true)) {
        $message = 'Invalid role status.';
        $message_type = 'error';
    } else {
        /*
        |------------------------------------------------------------------
        | PRESERVE BUILT-IN ROLE NAME AND STATUS
        |------------------------------------------------------------------
        */
        if ($isProtectedRole) {
            $postedRoleName = $role['role_name'];
            $postedStatus = $role['status'];
        }

        /*
        |------------------------------------------------------------------
        | CHECK DUPLICATE ROLE NAME
        |------------------------------------------------------------------
        */
        $duplicateCheck = $conn->prepare(
            "SELECT role_id
             FROM roles
             WHERE role_name = ?
               AND role_id != ?
             LIMIT 1"
        );

        if (!$duplicateCheck) {
            exit('Unable to prepare duplicate role check.');
        }

        $duplicateCheck->bind_param(
            'si',
            $postedRoleName,
            $role_id
        );

        $duplicateCheck->execute();
        $duplicateResult = $duplicateCheck->get_result();

        if ($duplicateResult->num_rows > 0) {
            $message = 'This role name already exists.';
            $message_type = 'error';
            $duplicateCheck->close();
        } else {
            $duplicateCheck->close();

            /*
            |--------------------------------------------------------------
            | UPDATE ROLE
            |--------------------------------------------------------------
            */
            $updateStmt = $conn->prepare(
                "UPDATE roles
                 SET role_name = ?,
                     description = ?,
                     status = ?
                 WHERE role_id = ?"
            );

            if (!$updateStmt) {
                exit('Unable to prepare role update.');
            }

            $updateStmt->bind_param(
                'sssi',
                $postedRoleName,
                $postedDescription,
                $postedStatus,
                $role_id
            );

            if ($updateStmt->execute()) {
                $updateStmt->close();

                header('Location: /SMS/admin/roles/roles.php');
                exit;
            }

            $message = 'Unable to update role. Please try again.';
            $message_type = 'error';

            $updateStmt->close();
        }
    }

    /*
    |----------------------------------------------------------------------
    | PRESERVE FORM VALUES AFTER VALIDATION ERRORS
    |----------------------------------------------------------------------
    */
    $role_name = $postedRoleName;
    $description = $postedDescription;

    // Keep protected role status unchanged.
    $status = $isProtectedRole
        ? $role['status']
        : $postedStatus;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Role | Fullbright College SMS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #F4F7FB;
            color: #243447;
        }

        .main-content {
            min-height: 100vh;
            padding: 32px;
        }

        .page-header {
            margin-bottom: 26px;
        }

        .breadcrumb {
            margin-bottom: 10px;
            color: #64748B;
            font-size: 12px;
        }

        .breadcrumb a {
            color: #1E5A96;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .page-header h1 {
            margin: 0;
            color: #12355B;
            font-size: 27px;
            font-weight: 700;
        }

        .page-header p {
            margin: 8px 0 0;
            color: #64748B;
            font-size: 14px;
            line-height: 1.6;
        }

        .form-card {
            width: 100%;
            max-width: 850px;
            padding: 30px;
            background: #FFFFFF;
            border: 1px solid #E1E8F0;
            border-radius: 14px;
            box-shadow: 0 5px 20px rgba(18, 53, 91, 0.05);
        }

        .card-heading {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding-bottom: 22px;
            margin-bottom: 24px;
            border-bottom: 1px solid #E8EDF3;
        }

        .heading-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            width: 44px;
            height: 44px;
            background: #EAF3FA;
            color: #1E5A96;
            border-radius: 11px;
            font-size: 21px;
            font-weight: 700;
        }

        .card-heading h2 {
            margin: 2px 0 6px;
            color: #12355B;
            font-size: 19px;
        }

        .card-heading p {
            margin: 0;
            color: #718096;
            font-size: 13px;
            line-height: 1.6;
        }

        .protected-notice {
            display: flex;
            align-items: flex-start;
            gap: 11px;
            padding: 15px;
            margin-bottom: 22px;
            background: #FFF5F5;
            border: 1px solid #FECACA;
            border-left: 4px solid #DC2626;
            border-radius: 9px;
            color: #991B1B;
            font-size: 13px;
            line-height: 1.6;
        }

        .protected-notice strong {
            display: block;
            margin-bottom: 3px;
            color: #991B1B;
        }

        .message {
            padding: 13px 15px;
            margin-bottom: 22px;
            border-radius: 8px;
            font-size: 13px;
            line-height: 1.5;
        }

        .message.error {
            background: #FEF2F2;
            color: #B91C1C;
            border: 1px solid #FECACA;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #243447;
            font-size: 13px;
            font-weight: 700;
        }

        .required {
            color: #DC2626;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            display: block;
            width: 100%;
            padding: 12px 13px;
            border: 1px solid #CBD5E1;
            border-radius: 8px;
            background: #FFFFFF;
            color: #243447;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input,
        .form-group select {
            min-height: 44px;
        }

        .form-group textarea {
            min-height: 115px;
            resize: vertical;
            line-height: 1.6;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #1E5A96;
            box-shadow: 0 0 0 3px rgba(30, 90, 150, 0.12);
        }

        .form-group input:disabled,
        .form-group select:disabled {
            background: #F1F5F9;
            color: #64748B;
            cursor: not-allowed;
            opacity: 1;
        }

        .field-hint {
            display: block;
            margin-top: 7px;
            color: #718096;
            font-size: 12px;
            line-height: 1.5;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 22px;
            margin-top: 8px;
            border-top: 1px solid #E8EDF3;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 43px;
            padding: 11px 19px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-primary {
            background: #1E5A96;
            color: #FFFFFF;
        }

        .btn-primary:hover {
            background: #12355B;
        }

        .btn-secondary {
            background: #FFFFFF;
            color: #475569;
            border-color: #CBD5E1;
        }

        .btn-secondary:hover {
            background: #F1F5F9;
        }

        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 14px;
            margin-top: 24px;
            background: #F0F7FF;
            border: 1px solid #D7E8FA;
            border-radius: 9px;
            color: #345675;
            font-size: 12px;
            line-height: 1.6;
        }

        .info-note strong {
            color: #12355B;
        }

        @media (max-width: 900px) {
            .main-content {
                padding: 24px;
            }
        }

        @media (max-width: 600px) {
            .main-content {
                padding: 18px;
            }

            .page-header h1 {
                font-size: 23px;
            }

            .form-card {
                padding: 20px;
            }

            .form-actions {
                align-items: stretch;
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<?php require_once __DIR__ . '/../../includes/admin_sidebar.php'; ?>

<main class="main-content">

    <header class="page-header">
        <div class="breadcrumb">
            <a href="/SMS/admin/dashboard.php">Dashboard</a>
            &nbsp;/&nbsp;
            <a href="/SMS/admin/roles/roles.php">Roles &amp; Permissions</a>
            &nbsp;/&nbsp;
            Edit Role
        </div>

        <h1>Edit Role</h1>

        <p>Review and update the information for an existing system role.</p>
    </header>

    <section class="form-card">

        <div class="card-heading">
            <div class="heading-icon" aria-hidden="true">&#9998;</div>

            <div>
                <h2>Role Information</h2>
                <p>Make the necessary changes, then save your updates.</p>
            </div>
        </div>

        <?php if ($isProtectedRole): ?>
            <div class="protected-notice">
                <span aria-hidden="true">&#9888;</span>

                <div>
                    <strong>Protected System Role</strong>
                    This built-in role's name and status cannot be changed.
                    You may update its description.
                </div>
            </div>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <div
                class="message <?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8') ?>"
                role="alert"
            >
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <input
                type="hidden"
                name="role_id"
                value="<?= (int)$role_id ?>"
            >

            <div class="form-group">
                <label for="role_name">
                    Role Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="role_name"
                    name="role_name"
                    value="<?= htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') ?>"
                    maxlength="100"
                    minlength="2"
                    autocomplete="off"
                    <?= $isProtectedRole ? 'disabled' : '' ?>
                    required
                >

                <?php if ($isProtectedRole): ?>
                    <span class="field-hint">
                        The name of a built-in role is locked.
                    </span>
                <?php else: ?>
                    <span class="field-hint">
                        Choose a clear, descriptive role name.
                    </span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="255"
                    placeholder="Describe the responsibilities of this role..."
                ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>

                <span class="field-hint">
                    Explain the responsibilities or purpose of this role.
                </span>
            </div>

            <div class="form-group">
                <label for="status">
                    Status <span class="required">*</span>
                </label>

                <select
                    id="status"
                    name="status"
                    <?= $isProtectedRole ? 'disabled' : '' ?>
                >
                    <option
                        value="Active"
                        <?= $status === 'Active' ? 'selected' : '' ?>
                    >
                        Active
                    </option>

                    <option
                        value="Inactive"
                        <?= $status === 'Inactive' ? 'selected' : '' ?>
                    >
                        Inactive
                    </option>
                </select>

                <?php if ($isProtectedRole): ?>
                    <span class="field-hint">
                        The status of a built-in role is locked.
                    </span>
                <?php else: ?>
                    <span class="field-hint">
                        Select whether this role should be active or inactive.
                    </span>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Save Changes
                </button>

                <a
                    href="/SMS/admin/roles/roles.php"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>
            </div>

        </form>

        <div class="info-note">
            <span aria-hidden="true">&#9432;</span>

            <div>
                <strong>Permissions:</strong>
                Editing role information does not automatically change
                the permissions assigned to that role.
            </div>
        </div>

    </section>

</main>

</body>
</html>