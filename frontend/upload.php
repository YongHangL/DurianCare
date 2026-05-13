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

if (isset($_FILES['leafImage']) && $_FILES['leafImage']['error'] == 0) {

    $userID = $_SESSION['user_id'];

    $fileName = $_FILES['leafImage']['name'];
    $tmpName = $_FILES['leafImage']['tmp_name'];

    $newName = time() . "_" . basename($fileName);

    $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR;
    $serverUploadPath = $uploadDir . $newName;
    $dbImagePath = "uploads/" . $newName;

    $fileType = strtolower(pathinfo($serverUploadPath, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png"];

    if (!in_array($fileType, $allowedTypes)) {
        $resultData["resultMessage"] = "Only JPG, JPEG, PNG files are allowed.";
    } else {

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($tmpName, $serverUploadPath)) {

            // No resize here.
            // Original image is saved.
            // Python predictPyTorch.py will resize image to 300x300 in memory.

            $sqlUpload = "INSERT INTO uploaded_images (UserID, image_name, image_path)
                          VALUES (?, ?, ?)";

            $stmtUpload = $conn->prepare($sqlUpload);
            $stmtUpload->bind_param("iss", $userID, $fileName, $dbImagePath);

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

        } else {
            $resultData["resultMessage"] = "Failed to upload image file. Check uploads folder path or permission.";
        }
    }

} else {
    $resultData["resultMessage"] = "No file uploaded or upload error occurred.";
}

$_SESSION['result_data'] = $resultData;
header("Location: result.php");
exit();
?>