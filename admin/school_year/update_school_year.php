<?php

require_once "../../includes/auth_check.php";
require_once "../../config/database.php";


/*
|--------------------------------------------------------------------------
| ADMINISTRATOR ONLY
|--------------------------------------------------------------------------
*/

if (($currentRoleName ?? "") !== "Administrator") {

    http_response_code(403);

    die("Access denied. Administrator privileges are required.");
}


/*
|--------------------------------------------------------------------------
| POST REQUEST ONLY
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: /SMS/admin/school_year/school_year.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$school_year_id = filter_input(
    INPUT_POST,
    "school_year_id",
    FILTER_VALIDATE_INT
);

$school_year = trim($_POST["school_year"] ?? "");
$start_date = trim($_POST["start_date"] ?? "");
$end_date = trim($_POST["end_date"] ?? "");


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if (
    !$school_year_id ||
    $school_year_id < 1 ||
    $school_year === "" ||
    $start_date === "" ||
    $end_date === ""
) {

    die("Please complete all required fields.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE SCHOOL YEAR FORMAT
|--------------------------------------------------------------------------
|
| Expected format:
| 2026-2027
|
*/

if (!preg_match("/^\d{4}-\d{4}$/", $school_year)) {

    die("Invalid school year format. Please use the format YYYY-YYYY.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE SCHOOL YEAR RANGE
|--------------------------------------------------------------------------
*/

$years = explode("-", $school_year);

$schoolYearStart = (int) $years[0];
$schoolYearEnd = (int) $years[1];

if ($schoolYearEnd !== $schoolYearStart + 1) {

    die("Invalid school year range.");
}


/*
|--------------------------------------------------------------------------
| VALIDATE DATES
|--------------------------------------------------------------------------
*/

$startDateObject = DateTime::createFromFormat(
    "Y-m-d",
    $start_date
);

$endDateObject = DateTime::createFromFormat(
    "Y-m-d",
    $end_date
);

if (
    !$startDateObject ||
    $startDateObject->format("Y-m-d") !== $start_date ||
    !$endDateObject ||
    $endDateObject->format("Y-m-d") !== $end_date
) {

    die("Please provide valid start and end dates.");
}


/*
|--------------------------------------------------------------------------
| CHECK DATE ORDER
|--------------------------------------------------------------------------
*/

if ($end_date <= $start_date) {

    die("End date must be later than the start date.");
}


/*
|--------------------------------------------------------------------------
| CHECK IF SCHOOL YEAR EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT school_year_id
    FROM school_years
    WHERE school_year_id = ?
    LIMIT 1
");

$stmt->bind_param(
    "i",
    $school_year_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    $stmt->close();

    die("School year not found.");
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| CHECK FOR DUPLICATE SCHOOL YEAR
|--------------------------------------------------------------------------
|
| The current record is excluded from the duplicate check.
|
*/

$stmt = $conn->prepare("
    SELECT school_year_id
    FROM school_years
    WHERE school_year = ?
    AND school_year_id != ?
    LIMIT 1
");

$stmt->bind_param(
    "si",
    $school_year,
    $school_year_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    die("This school year already exists.");
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| UPDATE SCHOOL YEAR
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    UPDATE school_years
    SET
        school_year = ?,
        start_date = ?,
        end_date = ?
    WHERE school_year_id = ?
");

$stmt->bind_param(
    "sssi",
    $school_year,
    $start_date,
    $end_date,
    $school_year_id
);


/*
|--------------------------------------------------------------------------
| SAVE CHANGES
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: /SMS/admin/school_year/school_year.php"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| UPDATE FAILED
|--------------------------------------------------------------------------
*/

$stmt->close();

die("Unable to update school year. Please try again.");

?>