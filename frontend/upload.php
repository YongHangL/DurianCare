<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginPage.php");
    exit();
}

$resultData = [
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

    $resultData["imageToShow"] = $dbImagePath;

    $fileType = strtolower(pathinfo($serverUploadPath, PATHINFO_EXTENSION));
    $allowedTypes = ["jpg", "jpeg", "png"];

    if (!in_array($fileType, $allowedTypes)) {
        $resultData["resultMessage"] = "Only JPG, JPEG, PNG files are allowed.";
    } else {

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (move_uploaded_file($tmpName, $serverUploadPath)) {

            // Resize image to 300x300
            $imageInfo = getimagesize($serverUploadPath);

            if ($imageInfo !== false) {
                list($width, $height) = $imageInfo;

                $newWidth = 300;
                $newHeight = 300;

                $src = null;

                if ($fileType === "jpg" || $fileType === "jpeg") {
                    $src = imagecreatefromjpeg($serverUploadPath);
                } elseif ($fileType === "png") {
                    $src = imagecreatefrompng($serverUploadPath);
                }

                if ($src) {
                    $dst = imagecreatetruecolor($newWidth, $newHeight);

                    if ($fileType === "png") {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
                        imagefilledrectangle($dst, 0, 0, $newWidth, $newHeight, $transparent);
                    }

                    imagecopyresampled(
                        $dst,
                        $src,
                        0,
                        0,
                        0,
                        0,
                        $newWidth,
                        $newHeight,
                        $width,
                        $height
                    );

                    if ($fileType === "jpg" || $fileType === "jpeg") {
                        imagejpeg($dst, $serverUploadPath, 90);
                    } elseif ($fileType === "png") {
                        imagepng($dst, $serverUploadPath);
                    }

                    imagedestroy($src);
                    imagedestroy($dst);
                }
            }

            // Save uploaded image information first
            $sqlUpload = "INSERT INTO uploaded_images (UserID, image_name, image_path)
                          VALUES (?, ?, ?)";

            $stmtUpload = $conn->prepare($sqlUpload);
            $stmtUpload->bind_param("iss", $userID, $fileName, $dbImagePath);

            if ($stmtUpload->execute()) {

                $checkID = $conn->insert_id;

                $python = "C:\\For UTEM\\year4sem1\\FYP\\project\\.venv\\Scripts\\python.exe";
                $script = "C:\\For UTEM\\year4sem1\\FYP\\project\\model\\predict.py";
                $imageFullPath = realpath($serverUploadPath);

                $command = "\"$python\" \"$script\" \"$imageFullPath\" 2>&1";
                $output = shell_exec($command);
                $output = trim($output);

                // Debug file for checking Python error
                file_put_contents("prediction_debug.txt", $output);

                // Find SUCCESS line
                $lines = preg_split('/\r\n|\r|\n/', $output);
                $successLine = "";

                foreach ($lines as $line) {
                    if (strpos($line, "SUCCESS|") === 0) {
                        $successLine = $line;
                        break;
                    }
                }

                if (!empty($successLine)) {

                    $jsonText = substr($successLine, strlen("SUCCESS|"));
                    $predictionData = json_decode($jsonText, true);

                    if ($predictionData && isset($predictionData['probabilities'])) {

                        $probabilities = $predictionData['probabilities'];

                        $algalProb = isset($probabilities['Algal']) ? (float)$probabilities['Algal'] : 0;
                        $blightProb = isset($probabilities['Blight']) ? (float)$probabilities['Blight'] : 0;
                        $healthyProb = isset($probabilities['Healthy']) ? (float)$probabilities['Healthy'] : 0;
                        $phomopsisProb = isset($probabilities['Phomopsis']) ? (float)$probabilities['Phomopsis'] : 0;

                        $disease = $predictionData['final_prediction'];
                        $confidence = (float)$predictionData['final_confidence'];

                        // Save prediction into prediction table
                        $sqlPrediction = "INSERT INTO prediction
                            (check_id, UserID, algal_prob, blight_prob, healthy_prob, phomopsis_prob, final_prediction, final_confidence)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmtPrediction = $conn->prepare($sqlPrediction);
                        $stmtPrediction->bind_param(
                            "iiddddsd",
                            $checkID,
                            $userID,
                            $algalProb,
                            $blightProb,
                            $healthyProb,
                            $phomopsisProb,
                            $disease,
                            $confidence
                        );

                        if ($stmtPrediction->execute()) {
                            $resultData["isSuccess"] = true;
                            $resultData["disease"] = $disease;
                            $resultData["confidence"] = $confidence;
                            $resultData["imageToShow"] = $dbImagePath;
                            $resultData["check_id"] = $checkID;
                            $resultData["probabilities"] = $probabilities;
                        } else {
                            $resultData["resultMessage"] = "Failed to save prediction: " . $stmtPrediction->error;
                        }

                    } else {
                        $resultData["resultMessage"] = "Prediction JSON format invalid. Check prediction_debug.txt.";
                    }

                } else {
                    $resultData["resultMessage"] = "Prediction failed. Check prediction_debug.txt.";
                }

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