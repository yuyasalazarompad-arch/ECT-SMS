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

$adminCheck->bind_param("i", $currentUserId);
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
    exit('Access denied. Only an Administrator can create roles.');
}

/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/
$message = '';
$message_type = '';
$role_name = '';
$description = '';
$status = 'Active';

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role_name = trim($_POST['role_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if ($role_name === '') {
        $message = 'Role name is required.';
        $message_type = 'error';
    } elseif (mb_strlen($role_name) < 2) {
        $message = 'Role name must be at least 2 characters.';
        $message_type = 'error';
    } elseif (!in_array($status, ['Active', 'Inactive'], true)) {
        $message = 'Invalid role status.';
        $message_type = 'error';
    } else {
        $duplicateCheck = $conn->prepare(
            "SELECT role_id
             FROM roles
             WHERE role_name = ?
             LIMIT 1"
        );

        $duplicateCheck->bind_param('s', $role_name);
        $duplicateCheck->execute();

        $duplicateResult = $duplicateCheck->get_result();

        if ($duplicateResult->num_rows > 0) {
            $message = 'This role name already exists.';
            $message_type = 'error';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO roles (role_name, description, status)
                 VALUES (?, ?, ?)"
            );

            if (!$stmt) {
                $message = 'Unable to prepare the role creation.';
                $message_type = 'error';
            } else {
                $stmt->bind_param(
                    'sss',
                    $role_name,
                    $description,
                    $status
                );

                if ($stmt->execute()) {
                    $stmt->close();
                    $duplicateCheck->close();

                    header('Location: /SMS/admin/roles/roles.php');
                    exit;
                }

                $message = 'Unable to add role. Please try again.';
                $message_type = 'error';

                $stmt->close();
            }
        }

        $duplicateCheck->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Role | Fullbright College SMS</title>

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
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 26px;
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
            line-height: 1.5;
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

        .field-hint {
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
        <div>
            <div class="breadcrumb">
                <a href="/SMS/admin/dashboard.php">Dashboard</a>
                &nbsp;/&nbsp;
                <a href="/SMS/admin/roles/roles.php">Roles &amp; Permissions</a>
                &nbsp;/&nbsp;
                Add Role
            </div>

            <h1>Add Role</h1>

            <p>Create a role to organize access and permissions within the School Management System.</p>
        </div>
    </header>

    <section class="form-card">

        <div class="card-heading">
            <div class="heading-icon" aria-hidden="true">+</div>

            <div>
                <h2>Role Information</h2>
                <p>Enter the role details below. You can configure its permissions afterward.</p>
            </div>
        </div>

        <?php if ($message !== ''): ?>
            <div
                class="message <?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8') ?>"
                role="alert"
            >
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="role_name">
                    Role Name <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="role_name"
                    name="role_name"
                    value="<?= htmlspecialchars($role_name, ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="e.g., Guidance Counselor"
                    maxlength="100"
                    minlength="2"
                    autocomplete="off"
                    required
                >

                <div class="field-hint">
                    Use a clear, descriptive name for the role.
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    id="description"
                    name="description"
                    maxlength="255"
                    placeholder="Describe the responsibilities of this role..."
                ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea>

                <div class="field-hint">
                    Briefly explain what this role is responsible for.
                </div>
            </div>

            <div class="form-group">
                <label for="status">
                    Status <span class="required">*</span>
                </label>

                <select id="status" name="status" required>
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

                <div class="field-hint">
                    Active roles can be managed and assigned according to your system's access rules.
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Save Role
                </button>

                <a href="/SMS/admin/roles/roles.php" class="btn btn-secondary">
                    Cancel
                </a>
            </div>

        </form>

        <div class="info-note">
            <span aria-hidden="true">ⓘ</span>
            <div>
                <strong>Next step:</strong>
                After creating the role, open Roles &amp; Permissions to configure the permissions assigned to it.
            </div>
        </div>

    </section>

</main>

</body>
</html>