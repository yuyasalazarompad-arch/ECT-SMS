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

$school_year = trim($_POST["school_year"] ?? "");
$start_date = trim($_POST["start_date"] ?? "");
$end_date = trim($_POST["end_date"] ?? "");


/*
|--------------------------------------------------------------------------
| BASIC VALIDATION
|--------------------------------------------------------------------------
*/

if ($school_year === "" || $start_date === "" || $end_date === "") {

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
| VALIDATE DATES
|--------------------------------------------------------------------------
*/

$startDateObject = DateTime::createFromFormat("Y-m-d", $start_date);
$endDateObject = DateTime::createFromFormat("Y-m-d", $end_date);

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
| CHECK SCHOOL YEAR RANGE
|--------------------------------------------------------------------------
|
| Example:
| School Year 2026-2027
| Start year: 2026
| End year: 2027
|
*/

$years = explode("-", $school_year);

$schoolYearStart = (int) $years[0];
$schoolYearEnd = (int) $years[1];

if ($schoolYearEnd !== $schoolYearStart + 1) {

    die("Invalid school year range.");
}


/*
|--------------------------------------------------------------------------
| CHECK IF SCHOOL YEAR ALREADY EXISTS
|--------------------------------------------------------------------------
*/

$stmt = $conn->prepare("
    SELECT school_year_id
    FROM school_years
    WHERE school_year = ?
    LIMIT 1
");

$stmt->bind_param(
    "s",
    $school_year
);

$stmt->execute();

$existing = $stmt->get_result();

if ($existing->num_rows > 0) {

    $stmt->close();

    die("This school year already exists.");
}

$stmt->close();


/*
|--------------------------------------------------------------------------
| INSERT SCHOOL YEAR
|--------------------------------------------------------------------------
|
| New school years are always created as Upcoming.
|
*/

$stmt = $conn->prepare("
    INSERT INTO school_years
    (school_year, start_date, end_date, status)
    VALUES (?, ?, ?, 'Upcoming')
");

$stmt->bind_param(
    "sss",
    $school_year,
    $start_date,
    $end_date
);


/*
|--------------------------------------------------------------------------
| SAVE SCHOOL YEAR
|--------------------------------------------------------------------------
*/

if ($stmt->execute()) {

    $stmt->close();

    header("Location: /SMS/admin/school_year/school_year.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| INSERT FAILED
|--------------------------------------------------------------------------
*/

$stmt->close();

die("Unable to save school year. Please try again.");

?>