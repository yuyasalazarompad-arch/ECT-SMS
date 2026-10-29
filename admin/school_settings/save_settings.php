<?php

/*
 * Protect this handler independently of the settings page.
 * auth_check.php verifies that the user is logged in and active,
 * and loads the database connection.
 */
require_once __DIR__ . "/../../includes/auth_check.php";

/* ==============================
   ADMINISTRATOR AUTHORIZATION
============================== */

$currentUserId = (int) ($_SESSION["user_id"] ?? 0);

$adminCheck = $conn->prepare(
    "SELECT r.role_name
     FROM users u
     INNER JOIN roles r ON u.role_id = r.role_id
     WHERE u.user_id = ?
     LIMIT 1"
);

if (!$adminCheck) {
    error_log("School settings authorization query could not be prepared: " . $conn->error);
    http_response_code(500);
    exit("Unable to verify access at this time.");
}

$adminCheck->bind_param("i", $currentUserId);
$adminCheck->execute();
$adminResult = $adminCheck->get_result();
$currentRole = $adminResult->fetch_assoc();
$adminCheck->close();

if (!$currentRole || $currentRole["role_name"] !== "Administrator") {
    http_response_code(403);
    exit("Access denied. Only an Administrator can change school settings.");
}

/* ==============================
   ONLY ALLOW POST
============================== */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: school_settings.php");
    exit;

}


/* ==============================
   GET FORM VALUES
============================== */

$setting_id = intval($_POST["setting_id"] ?? 0);

$school_name = trim($_POST["school_name"] ?? "");
$school_code = trim($_POST["school_code"] ?? "");
$school_tagline = trim($_POST["school_tagline"] ?? "");
$address = trim($_POST["address"] ?? "");
$contact_number = trim($_POST["contact_number"] ?? "");
$email = trim($_POST["email"] ?? "");
$website = trim($_POST["website"] ?? "");
$facebook_url = trim($_POST["facebook_url"] ?? "");
$instagram_url = trim($_POST["instagram_url"] ?? "");
$other_social_url = trim($_POST["other_social_url"] ?? "");
$description = trim($_POST["description"] ?? "");


/* ==============================
   BASIC VALIDATION
============================== */

if ($setting_id <= 0) {

    die("
        <h2>School Settings Error</h2>
        <p>Invalid settings ID.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


if ($school_name === "") {

    die("
        <h2>School Settings Error</h2>
        <p>School name is required.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


/* ==============================
   EMAIL VALIDATION
============================== */

if (
    $email !== "" &&
    !filter_var($email, FILTER_VALIDATE_EMAIL)
) {

    die("
        <h2>School Settings Error</h2>
        <p>Please enter a valid email address.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


/* ==============================
   URL VALIDATION
============================== */

$urlFields = [
    "Website" => $website,
    "Facebook Page" => $facebook_url,
    "Instagram" => $instagram_url,
    "Other Social Page" => $other_social_url
];


foreach ($urlFields as $field => $url) {

    if (
        $url !== "" &&
        !filter_var($url, FILTER_VALIDATE_URL)
    ) {

        die("
            <h2>School Settings Error</h2>
            <p>$field must be a valid URL.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }

}


/* ==============================
   GET CURRENT SCHOOL FILES
============================== */

$stmtCurrent = $conn->prepare(
    "SELECT logo_filename, banner_filename
     FROM school_settings
     WHERE setting_id = ?
     LIMIT 1"
);


if (!$stmtCurrent) {

    die("
        <h2>Database Error</h2>
        <p>Unable to prepare the settings query.</p>
        <p>Please try again later.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


$stmtCurrent->bind_param(
    "i",
    $setting_id
);


$stmtCurrent->execute();

$currentResult = $stmtCurrent->get_result();


if ($currentResult->num_rows === 0) {

    $stmtCurrent->close();

    die("
        <h2>School Settings Error</h2>
        <p>The school settings record could not be found.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


$currentSettings = $currentResult->fetch_assoc();

$stmtCurrent->close();


$logoFilename =
    $currentSettings["logo_filename"] ?? "";


$bannerFilename =
    $currentSettings["banner_filename"] ?? "";


/* ==============================
   UPLOAD DIRECTORY
============================== */

$uploadDirectory =
    "../../assets/uploads/school/";


if (!is_dir($uploadDirectory)) {

    if (!mkdir($uploadDirectory, 0755, true)) {

        die("
            <h2>Upload Error</h2>
            <p>Unable to create the school upload folder.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }

}


/* ==============================
   FUNCTION FOR IMAGE UPLOAD
============================== */

function uploadSchoolImage(
    $file,
    $type,
    $maximumSize,
    $uploadDirectory
) {

    if (
        !isset($file) ||
        $file["error"] === UPLOAD_ERR_NO_FILE
    ) {

        return "";

    }


    if ($file["error"] !== UPLOAD_ERR_OK) {

        die("
            <h2>Upload Error</h2>
            <p>There was a problem uploading the $type.</p>
            <p>Error code: " . intval($file["error"]) . "</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }


    if ($file["size"] > $maximumSize) {

        die("
            <h2>Upload Error</h2>
            <p>The $type is too large.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }


    $imageInfo = @getimagesize(
        $file["tmp_name"]
    );


    if ($imageInfo === false) {

        die("
            <h2>Upload Error</h2>
            <p>The uploaded $type is not a valid image.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }


    $mimeType =
        $imageInfo["mime"] ?? "";


    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];


    if (!isset($allowedTypes[$mimeType])) {

        die("
            <h2>Upload Error</h2>
            <p>The $type must be JPG, PNG, or WEBP.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }


    $extension =
        $allowedTypes[$mimeType];


    $filename =
        $type .
        "_" .
        date("Ymd_His") .
        "_" .
        uniqid() .
        "." .
        $extension;


    $destination =
        $uploadDirectory .
        $filename;


    if (
        !move_uploaded_file(
            $file["tmp_name"],
            $destination
        )
    ) {

        die("
            <h2>Upload Error</h2>
            <p>PHP could not save the uploaded $type.</p>
            <p><a href='school_settings.php'>Back to School Settings</a></p>
        ");

    }


    return $filename;

}


/* ==============================
   UPLOAD LOGO
============================== */

$newLogo = uploadSchoolImage(
    $_FILES["school_logo"] ?? null,
    "school_logo",
    2 * 1024 * 1024,
    $uploadDirectory
);


if ($newLogo !== "") {

    $logoFilename = $newLogo;

}


/* ==============================
   UPLOAD BANNER
============================== */

$newBanner = uploadSchoolImage(
    $_FILES["school_banner"] ?? null,
    "school_banner",
    4 * 1024 * 1024,
    $uploadDirectory
);


if ($newBanner !== "") {

    $bannerFilename = $newBanner;

}


/* ==============================
   UPDATE DATABASE
============================== */

$stmt = $conn->prepare(
    "UPDATE school_settings
     SET school_name = ?,
         school_code = ?,
         school_tagline = ?,
         address = ?,
         contact_number = ?,
         email = ?,
         website = ?,
         facebook_url = ?,
         instagram_url = ?,
         other_social_url = ?,
         logo_filename = ?,
         banner_filename = ?,
         description = ?
     WHERE setting_id = ?"
);


if (!$stmt) {

    die("
        <h2>Database Error</h2>
        <p>Unable to prepare the update query.</p>
        <p>Please try again later.</p>
        <p><a href='school_settings.php'>Back to School Settings</a></p>
    ");

}


$stmt->bind_param(
    "sssssssssssssi",
    $school_name,
    $school_code,
    $school_tagline,
    $address,
    $contact_number,
    $email,
    $website,
    $facebook_url,
    $instagram_url,
    $other_social_url,
    $logoFilename,
    $bannerFilename,
    $description,
    $setting_id
);


/* ==============================
   EXECUTE
============================== */

if (!$stmt->execute()) {

    error_log("School settings update failed: " . $stmt->error);

    $stmt->close();

    die("
        <h2>Database Update Failed</h2>

        <p>The School Settings could not be saved.</p>

        <p>
            <strong>Error:</strong>
            " . htmlspecialchars($errorMessage) . "
        </p>

        <p>
            <a href='school_settings.php'>
                Back to School Settings
            </a>
        </p>
    ");

}


$stmt->close();


/* ==============================
   SUCCESS
============================== */

header(
    "Location: school_settings.php?saved=1"
);

exit;

?>