<?php
session_start();
include "db.php";

header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in."
    ]);
    exit();
}

$userID = $_SESSION['user_id'];

$checkID = 0;

if (isset($_POST['check_id'])) {
    $checkID = (int)$_POST['check_id'];
} elseif (isset($_SESSION['result_data']['check_id'])) {
    $checkID = (int)$_SESSION['result_data']['check_id'];
}

if ($checkID <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid check ID."
    ]);
    exit();
}

// Check if prediction already exists.
// This prevents duplicate prediction if user refreshes result.php.
$sqlExisting = "SELECT
                    p.algal_prob,
                    p.blight_prob,
                    p.healthy_prob,
                    p.phomopsis_prob,
                    p.final_prediction,
                    p.final_confidence,
                    t.recommendation
                FROM prediction p
                LEFT JOIN treatment_recom t
                    ON p.final_prediction = t.disease_name
                WHERE p.check_id = ?
                AND p.UserID = ?
                LIMIT 1";

$stmtExisting = $conn->prepare($sqlExisting);
$stmtExisting->bind_param("ii", $checkID, $userID);
$stmtExisting->execute();
$existingResult = $stmtExisting->get_result();

if ($existingResult && $existingResult->num_rows > 0) {
    $row = $existingResult->fetch_assoc();

    $probabilities = [
        "Algal" => (float)$row["algal_prob"],
        "Blight" => (float)$row["blight_prob"],
        "Healthy" => (float)$row["healthy_prob"],
        "Phomopsis" => (float)$row["phomopsis_prob"]
    ];

    arsort($probabilities);

    $_SESSION['result_data']["status"] = "completed";
    $_SESSION['result_data']["isSuccess"] = true;
    $_SESSION['result_data']["disease"] = $row["final_prediction"];
    $_SESSION['result_data']["confidence"] = (float)$row["final_confidence"];
    $_SESSION['result_data']["probabilities"] = $probabilities;

    echo json_encode([
        "status" => "success",
        "message" => "Prediction already completed.",
        "disease" => $row["final_prediction"],
        "confidence" => (float)$row["final_confidence"],
        "probabilities" => $probabilities,
        "recommendation" => $row["recommendation"] ?? "No recommendation found."
    ]);
    exit();
}

// Get uploaded image path
$sqlImage = "SELECT image_path
             FROM uploaded_images
             WHERE check_id = ?
             AND UserID = ?
             LIMIT 1";

$stmtImage = $conn->prepare($sqlImage);
$stmtImage->bind_param("ii", $checkID, $userID);
$stmtImage->execute();
$imageResult = $stmtImage->get_result();

if (!$imageResult || $imageResult->num_rows == 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Uploaded image record not found."
    ]);
    exit();
}

$imageRow = $imageResult->fetch_assoc();
$dbImagePath = $imageRow["image_path"];

$serverImagePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $dbImagePath);
$imageFullPath = realpath($serverImagePath);

if (!$imageFullPath || !file_exists($imageFullPath)) {
    echo json_encode([
        "status" => "error",
        "message" => "Uploaded image file not found."
    ]);
    exit();
}

// Python prediction
$python = "C:\\For UTEM\\year4sem1\\FYP\\project\\.venv\\Scripts\\python.exe";
$script = "C:\\For UTEM\\year4sem1\\FYP\\project\\model\\version5-changeMethodUseYolo\\predictConvNeXt_V5.py";

$command = "\"$python\" \"$script\" \"$imageFullPath\" 2>&1";
$output = shell_exec($command);
$output = trim($output);

file_put_contents("prediction_debug.txt", $output);

$lines = preg_split('/\r\n|\r|\n/', $output);
$successLine = "";

foreach ($lines as $line) {
    if (strpos($line, "SUCCESS|") === 0) {
        $successLine = $line;
        break;
    }
}

if (empty($successLine)) {
    echo json_encode([
        "status" => "error",
        "message" => "Prediction failed. Check prediction_debug.txt."
    ]);
    exit();
}

$jsonText = substr($successLine, strlen("SUCCESS|"));
$predictionData = json_decode($jsonText, true);

if (!$predictionData || !isset($predictionData['probabilities'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Prediction JSON format invalid. Check prediction_debug.txt."
    ]);
    exit();
}

$probabilities = $predictionData['probabilities'];

$algalProb = isset($probabilities['Algal']) ? (float)$probabilities['Algal'] : 0;
$blightProb = isset($probabilities['Blight']) ? (float)$probabilities['Blight'] : 0;
$healthyProb = isset($probabilities['Healthy']) ? (float)$probabilities['Healthy'] : 0;
$phomopsisProb = isset($probabilities['Phomopsis']) ? (float)$probabilities['Phomopsis'] : 0;

$disease = $predictionData['final_prediction'];
$confidence = (float)$predictionData['final_confidence'];

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

if (!$stmtPrediction->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to save prediction: " . $stmtPrediction->error
    ]);
    exit();
}

// Get treatment recommendation
$recommendation = "No recommendation found.";

$sqlRec = "SELECT recommendation
           FROM treatment_recom
           WHERE disease_name = ?
           LIMIT 1";

$stmtRec = $conn->prepare($sqlRec);
$stmtRec->bind_param("s", $disease);
$stmtRec->execute();
$resultRec = $stmtRec->get_result();

if ($resultRec && $resultRec->num_rows > 0) {
    $rowRec = $resultRec->fetch_assoc();
    $recommendation = $rowRec['recommendation'];
}

arsort($probabilities);

$_SESSION['result_data']["status"] = "completed";
$_SESSION['result_data']["isSuccess"] = true;
$_SESSION['result_data']["disease"] = $disease;
$_SESSION['result_data']["confidence"] = $confidence;
$_SESSION['result_data']["probabilities"] = $probabilities;

echo json_encode([
    "status" => "success",
    "message" => "Prediction completed.",
    "disease" => $disease,
    "confidence" => $confidence,
    "probabilities" => $probabilities,
    "recommendation" => $recommendation
]);
exit();
?>