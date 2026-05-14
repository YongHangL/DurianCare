<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginPage.php");
    exit();
}

$resultData = [
    "status" => "error",
    "isSuccess" => false,
    "resultMessage" => "",
    "disease" => "",
    "confidence" => "",
    "imageToShow" => "",
    "check_id" => "",
    "probabilities" => []
];

if (!isset($_FILES['leafImage']) || $_FILES['leafImage']['error'] !== UPLOAD_ERR_OK) {
    $resultData["resultMessage"] = "No file uploaded or upload error occurred.";
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

$userID = $_SESSION['user_id'];

$originalFileName = $_FILES['leafImage']['name'];
$tmpName = $_FILES['leafImage']['tmp_name'];
$fileSize = $_FILES['leafImage']['size'];

$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

$fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
$allowedTypes = ["jpg", "jpeg", "png"];

if (!in_array($fileExtension, $allowedTypes)) {
    $resultData["resultMessage"] = "Only JPG, JPEG, and PNG files are allowed.";
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

// Limit file size to 5MB
if ($fileSize > 5 * 1024 * 1024) {
    $resultData["resultMessage"] = "File size is too large. Maximum allowed size is 5MB.";
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

// Extra image validation
$imageInfo = @getimagesize($tmpName);
if ($imageInfo === false) {
    $resultData["resultMessage"] = "Invalid image file.";
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

// Generate safe unique filename
$newName = time() . "_" . bin2hex(random_bytes(4)) . "." . $fileExtension;

$serverUploadPath = $uploadDir . $newName;
$dbImagePath = "uploads/" . $newName;

if (!move_uploaded_file($tmpName, $serverUploadPath)) {
    $resultData["resultMessage"] = "Failed to upload image file. Check uploads folder path or permission.";
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

// Save upload record into database
$sqlUpload = "INSERT INTO uploaded_images (UserID, image_name, image_path)
              VALUES (?, ?, ?)";

$stmtUpload = $conn->prepare($sqlUpload);

if (!$stmtUpload) {
    $resultData["resultMessage"] = "Database prepare failed: " . $conn->error;
    $_SESSION['result_data'] = $resultData;
    header("Location: result.php");
    exit();
}

$stmtUpload->bind_param("iss", $userID, $originalFileName, $dbImagePath);

if ($stmtUpload->execute()) {
    $checkID = $conn->insert_id;

    $resultData["status"] = "pending";
    $resultData["isSuccess"] = false;
    $resultData["resultMessage"] = "Image uploaded successfully. Prediction is running.";
    $resultData["imageToShow"] = $dbImagePath;
    $resultData["check_id"] = $checkID;
} else {
    $resultData["resultMessage"] = "Database insert failed: " . $stmtUpload->error;
}

$stmtUpload->close();

$_SESSION['result_data'] = $resultData;
header("Location: result.php");
exit();
?>
