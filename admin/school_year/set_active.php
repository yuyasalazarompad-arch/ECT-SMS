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
| GET SCHOOL YEAR ID
|--------------------------------------------------------------------------
*/

$school_year_id = filter_input(
    INPUT_POST,
    "school_year_id",
    FILTER_VALIDATE_INT
);

if (!$school_year_id || $school_year_id < 1) {

    die("Invalid school year.");
}


/*
|--------------------------------------------------------------------------
| START TRANSACTION
|--------------------------------------------------------------------------
|
| Both status changes must succeed together.
|
*/

$conn->begin_transaction();

try {


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

        throw new Exception(
            "School year not found."
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE CURRENT ACTIVE SCHOOL YEAR
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE school_years
        SET status = 'Archived'
        WHERE status = 'Active'
    ");

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Unable to archive the current active school year."
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SET SELECTED SCHOOL YEAR AS ACTIVE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare("
        UPDATE school_years
        SET status = 'Active'
        WHERE school_year_id = ?
    ");

    $stmt->bind_param(
        "i",
        $school_year_id
    );

    if (!$stmt->execute()) {

        $stmt->close();

        throw new Exception(
            "Unable to activate the selected school year."
        );
    }

    $stmt->close();


    /*
    |--------------------------------------------------------------------------
    | SAVE CHANGES
    |--------------------------------------------------------------------------
    */

    $conn->commit();


    /*
    |--------------------------------------------------------------------------
    | RETURN TO SCHOOL YEAR PAGE
    |--------------------------------------------------------------------------
    */

    header(
        "Location: /SMS/admin/school_year/school_year.php"
    );

    exit;


} catch (Exception $e) {

    $conn->rollback();

    http_response_code(500);

    die(
        "Unable to set school year as active. Please try again."
    );
}

?>