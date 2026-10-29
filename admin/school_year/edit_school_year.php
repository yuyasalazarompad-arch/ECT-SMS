<?php

require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| ADMINISTRATOR AUTHORIZATION
|--------------------------------------------------------------------------
*/

if (($currentRoleName ?? '') !== 'Administrator') {
    http_response_code(403);
    exit('Access denied. Administrator privileges are required.');
}

/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('schoolYearEscape')) {
    function schoolYearEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

/*
|--------------------------------------------------------------------------
| GET SCHOOL YEAR ID
|--------------------------------------------------------------------------
*/

$school_year_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$school_year_id || $school_year_id < 1) {
    http_response_code(400);
    exit('Invalid school year.');
}

/*
|--------------------------------------------------------------------------
| FETCH SCHOOL YEAR
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT
        school_year_id,
        school_year,
        start_date,
        end_date,
        status
    FROM school_years
    WHERE school_year_id = ?
    LIMIT 1
");

if (!$stmt) {
    error_log('School year query preparation failed: ' . $conn->error);
    http_response_code(500);
    exit('Unable to load the school year right now.');
}

$stmt->bind_param('i', $school_year_id);
$stmt->execute();

$result = $stmt->get_result();
$school_year = $result->fetch_assoc();

$stmt->close();

if (!$school_year) {
    http_response_code(404);
    exit('School year not found.');
}

/*
|--------------------------------------------------------------------------
| STATUS DISPLAY
|--------------------------------------------------------------------------
*/

$status = $school_year['status'] ?? 'Upcoming';

$statusClasses = [
    'Active'   => 'status-active',
    'Upcoming' => 'status-upcoming',
    'Archived' => 'status-archived',
];

$statusClass = $statusClasses[$status] ?? 'status-upcoming';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Edit School Year | SMS</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #1f2937;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        button,
        input {
            font: inherit;
        }

        .main-content {
            min-width: 0;
            min-height: 100vh;
            padding: 0;
        }

        .topbar {
            min-height: 82px;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
        }

        .topbar-title {
            margin: 0;
            color: #12355B;
            font-size: 22px;
            font-weight: 750;
        }

        .topbar-description {
            margin: 5px 0 0;
            color: #6b7280;
            font-size: 13px;
        }

        .page-content {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
            margin-bottom: 22px;
            color: #6b7280;
            font-size: 12px;
        }

        .breadcrumb a {
            color: #1E5A96;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-current {
            color: #12355B;
            font-weight: 700;
        }

        .page-heading {
            margin-bottom: 23px;
        }

        .page-heading h2 {
            margin: 0;
            color: #12355B;
            font-size: 25px;
            font-weight: 750;
        }

        .page-heading p {
            margin: 7px 0 0;
            color: #6b7280;
            font-size: 13px;
            line-height: 1.6;
        }

        .edit-card {
            overflow: hidden;
            background: #ffffff;
            border: 1px solid #e3e8ef;
            border-radius: 13px;
            box-shadow: 0 4px 16px rgba(18, 53, 91, 0.045);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            padding: 23px 26px;
            border-bottom: 1px solid #e9edf2;
        }

        .card-title {
            margin: 0;
            color: #12355B;
            font-size: 17px;
            font-weight: 750;
        }

        .card-description {
            margin: 6px 0 0;
            color: #6b7280;
            font-size: 12px;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: currentColor;
        }

        .status-active {
            background: #e7f7ed;
            color: #18733c;
        }

        .status-upcoming {
            background: #fff5df;
            color: #956400;
        }

        .status-archived {
            background: #eef1f5;
            color: #59636f;
        }

        .form-body {
            padding: 27px 26px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 23px 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            margin-bottom: 8px;
            color: #374151;
            font-size: 12px;
            font-weight: 700;
        }

        .required {
            color: #DC2626;
        }

        .form-group input {
            width: 100%;
            min-width: 0;
            min-height: 44px;
            padding: 10px 12px;
            border: 1px solid #d6dde7;
            border-radius: 8px;
            background: #ffffff;
            color: #1f2937;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            border-color: #1E5A96;
            box-shadow: 0 0 0 3px rgba(30, 90, 150, 0.11);
        }

        .form-help {
            margin-top: 7px;
            color: #7b8490;
            font-size: 11px;
            line-height: 1.5;
        }

        .info-box {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-top: 25px;
            padding: 16px;
            border: 1px solid #d8e7f4;
            border-radius: 9px;
            background: #EAF3FA;
        }

        .info-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 30px;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #ffffff;
            color: #1E5A96;
            font-size: 16px;
            font-weight: 800;
        }

        .info-title {
            margin: 0 0 5px;
            color: #12355B;
            font-size: 12px;
            font-weight: 750;
        }

        .info-description {
            margin: 0;
            color: #526779;
            font-size: 12px;
            line-height: 1.7;
        }

        .form-error {
            display: none;
            margin-top: 18px;
            padding: 12px 14px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 12px;
            line-height: 1.6;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 10px;
            padding: 19px 26px;
            border-top: 1px solid #e9edf2;
            background: #fafbfd;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 17px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }

        .btn-secondary {
            border-color: #d6dde7;
            background: #ffffff;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .btn-primary {
            background: #12355B;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #1E5A96;
        }

        .btn:focus-visible {
            outline: 3px solid rgba(30, 90, 150, 0.25);
            outline-offset: 2px;
        }

        @media (max-width: 760px) {
            .topbar {
                padding: 18px;
            }

            .topbar-title {
                font-size: 19px;
            }

            .page-content {
                padding: 22px 16px;
            }

            .card-header,
            .form-body {
                padding: 20px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 19px;
            }

            .form-group.full-width {
                grid-column: auto;
            }

            .form-actions {
                padding: 17px 20px;
            }
        }

        @media (max-width: 420px) {
            .form-actions {
                flex-direction: column-reverse;
            }

            .form-actions .btn {
                width: 100%;
            }

            .card-header {
                align-items: flex-start;
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

<?php
/*
 * Use the same sidebar component as the other admin pages.
 */
require_once __DIR__ . '/../../includes/admin_sidebar.php';
?>

<main class="main-content">

    <header class="topbar">
        <div>
            <h1 class="topbar-title">Edit School Year</h1>
            <p class="topbar-description">
                Update academic year information and dates.
            </p>
        </div>
    </header>

    <section class="page-content">

        <nav class="breadcrumb" aria-label="Breadcrumb">
            <a href="/SMS/admin/dashboard.php">Dashboard</a>
            <span aria-hidden="true">›</span>
            <a href="/SMS/admin/school_year/school_year.php">
                School Year
            </a>
            <span aria-hidden="true">›</span>
            <span class="breadcrumb-current">Edit</span>
        </nav>

        <div class="page-heading">
            <h2>School Year Information</h2>
            <p>
                Review and update the academic year name and date range.
            </p>
        </div>

        <section class="edit-card">

            <div class="card-header">
                <div>
                    <h3 class="card-title">Academic Year Details</h3>
                    <p class="card-description">
                        Make sure the dates are correct before saving.
                    </p>
                </div>

                <span class="status-badge <?= schoolYearEscape($statusClass) ?>">
                    <span class="status-dot" aria-hidden="true"></span>
                    <?= schoolYearEscape($status) ?>
                </span>
            </div>

            <form
                id="editSchoolYearForm"
                method="POST"
                action="/SMS/admin/school_year/update_school_year.php"
            >

                <input
                    type="hidden"
                    name="school_year_id"
                    value="<?= (int) $school_year['school_year_id'] ?>"
                >

                <div class="form-body">

                    <div class="form-grid">

                        <div class="form-group full-width">
                            <label for="schoolYear">
                                School Year <span class="required">*</span>
                            </label>

                            <input
                                type="text"
                                id="schoolYear"
                                name="school_year"
                                value="<?= schoolYearEscape($school_year['school_year']) ?>"
                                placeholder="Example: 2028-2029"
                                pattern="[0-9]{4}-[0-9]{4}"
                                title="Enter the school year in YYYY-YYYY format."
                                required
                            >

                            <span class="form-help">
                                Use the format YYYY-YYYY, for example 2028-2029.
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="startDate">
                                Start Date <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                id="startDate"
                                name="start_date"
                                value="<?= schoolYearEscape($school_year['start_date']) ?>"
                                required
                            >

                            <span class="form-help">
                                The first day of the academic year.
                            </span>
                        </div>

                        <div class="form-group">
                            <label for="endDate">
                                End Date <span class="required">*</span>
                            </label>

                            <input
                                type="date"
                                id="endDate"
                                name="end_date"
                                value="<?= schoolYearEscape($school_year['end_date']) ?>"
                                required
                            >

                            <span class="form-help">
                                The final day of the academic year.
                            </span>
                        </div>

                    </div>

                    <div
                        class="form-error"
                        id="dateError"
                        role="alert"
                    ></div>

                    <div class="info-box">
                        <div class="info-icon" aria-hidden="true">i</div>

                        <div>
                            <p class="info-title">
                                Current Status: <?= schoolYearEscape($status) ?>
                            </p>

                            <p class="info-description">
                                Changing the dates here does not automatically
                                activate this school year. To change the active
                                academic year, use the Set Active action on the
                                School Year records page.
                            </p>
                        </div>
                    </div>

                </div>

                <div class="form-actions">
                    <a
                        href="/SMS/admin/school_year/school_year.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Changes
                    </button>
                </div>

            </form>

        </section>

    </section>

</main>

<script>
(function () {
    const form = document.getElementById('editSchoolYearForm');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const dateError = document.getElementById('dateError');

    function validateDates() {
        const start = startDate.value;
        const end = endDate.value;

        if (start && end && end < start) {
            dateError.textContent =
                'The end date must be on or after the start date.';
            dateError.style.display = 'block';
            endDate.setCustomValidity(
                'The end date must be on or after the start date.'
            );
            return false;
        }

        endDate.setCustomValidity('');
        dateError.textContent = '';
        dateError.style.display = 'none';

        return true;
    }

    startDate.addEventListener('change', validateDates);
    endDate.addEventListener('change', validateDates);

    form.addEventListener('submit', function (event) {
        if (!validateDates()) {
            event.preventDefault();
            endDate.reportValidity();
        }
    });

    validateDates();
})();
</script>

</body>
</html>