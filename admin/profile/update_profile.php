<?php

session_start();

include "../../config/database.php";

/*
|--------------------------------------------------------------------------
| Get logged-in user
|--------------------------------------------------------------------------
*/

$userId = $_SESSION["user_id"] ?? 1;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: profile.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Get submitted information
|--------------------------------------------------------------------------
*/

$fullName = trim($_POST["full_name"] ?? "");
$email = trim($_POST["email"] ?? "");
$contactNumber = trim($_POST["contact_number"] ?? "");


/*
|--------------------------------------------------------------------------
| Basic validation
|--------------------------------------------------------------------------
*/

if ($fullName === "") {
    die("Full name is required.");
}

if ($email === "") {
    die("Email address is required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Please enter a valid email address.");
}


/*
|--------------------------------------------------------------------------
| Check if email is already used by another user
|--------------------------------------------------------------------------
*/

$checkEmail = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE email = ?
    AND user_id != ?
    LIMIT 1
");

$checkEmail->bind_param(
    "si",
    $email,
    $userId
);

$checkEmail->execute();

$emailResult = $checkEmail->get_result();

if ($emailResult->num_rows > 0) {
    die("This email address is already being used by another account.");
}


/*
|--------------------------------------------------------------------------
| Get current profile picture
|--------------------------------------------------------------------------
*/

$getCurrent = $conn->prepare("
    SELECT profile_picture
    FROM users
    WHERE user_id = ?
    LIMIT 1
");

$getCurrent->bind_param("i", $userId);
$getCurrent->execute();

$currentResult = $getCurrent->get_result();
$currentUser = $currentResult->fetch_assoc();

$currentProfilePicture = $currentUser["profile_picture"] ?? "";


/*
|--------------------------------------------------------------------------
| Profile picture upload
|--------------------------------------------------------------------------
*/

$newProfilePicture = $currentProfilePicture;

if (
    isset($_FILES["profile_picture"]) &&
    $_FILES["profile_picture"]["error"] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES["profile_picture"]["error"] !== UPLOAD_ERR_OK) {
        die("There was a problem uploading your profile picture.");
    }


    /*
    | Maximum file size: 2 MB
    */

    $maxFileSize = 2 * 1024 * 1024;

    if ($_FILES["profile_picture"]["size"] > $maxFileSize) {
        die("Profile picture must not exceed 2 MB.");
    }


    /*
    | Allowed file types
    */

    $allowedTypes = [
        "image/jpeg" => "jpg",
        "image/png"  => "png",
        "image/webp" => "webp"
    ];


    /*
    | Detect actual MIME type
    */

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);

    $mimeType = finfo_file(
        $fileInfo,
        $_FILES["profile_picture"]["tmp_name"]
    );

    finfo_close($fileInfo);


    if (!isset($allowedTypes[$mimeType])) {
        die("Only JPG, PNG, and WEBP images are allowed.");
    }


    /*
    | Create upload folder if it does not exist
    */

    $uploadDirectory = "../../assets/uploads/users/";

    if (!is_dir($uploadDirectory)) {

        if (!mkdir($uploadDirectory, 0755, true)) {
            die("Unable to create the profile picture upload folder.");
        }

    }


    /*
    | Generate a unique filename
    */

    $extension = $allowedTypes[$mimeType];

    $newFileName =
        "user_" .
        $userId .
        "_" .
        time() .
        "." .
        $extension;


    $destination =
        $uploadDirectory .
        $newFileName;


    /*
    | Move uploaded image
    */

    if (!move_uploaded_file(
        $_FILES["profile_picture"]["tmp_name"],
        $destination
    )) {

        die("Unable to save the uploaded profile picture.");

    }


    /*
    | Delete previous profile picture
    | only if it belongs to this user's upload
    */

    if (!empty($currentProfilePicture)) {

        $oldFile =
            $uploadDirectory .
            basename($currentProfilePicture);

        if (
            file_exists($oldFile) &&
            is_file($oldFile)
        ) {

            unlink($oldFile);

        }

    }


    $newProfilePicture = $newFileName;
}


/*
|--------------------------------------------------------------------------
| Update database
|--------------------------------------------------------------------------
*/

$update = $conn->prepare("
    UPDATE users
    SET
        full_name = ?,
        email = ?,
        contact_number = ?,
        profile_picture = ?
    WHERE user_id = ?
");

$update->bind_param(
    "ssssi",
    $fullName,
    $email,
    $contactNumber,
    $newProfilePicture,
    $userId
);


if (!$update->execute()) {

    die(
        "Unable to update your profile. " .
        htmlspecialchars($conn->error)
    );

}


/*
|--------------------------------------------------------------------------
| Update session information
|--------------------------------------------------------------------------
*/

$_SESSION["full_name"] = $fullName;


/*
|--------------------------------------------------------------------------
| Return to profile
|--------------------------------------------------------------------------
*/

header("Location: profile.php?updated=1");
exit;

?>