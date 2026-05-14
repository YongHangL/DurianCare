<?php
session_start();
include "db.php";

header("Content-Type: application/json");

function jsonResponse($data) {
    echo json_encode($data);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse([
        "status" => "error",
        "message" => "User not logged in."
    ]);
}

$userID = (int)$_SESSION['user_id'];

$checkID = 0;

if (isset($_POST['check_id'])) {
    $checkID = (int)$_POST['check_id'];
} elseif (isset($_SESSION['result_data']['check_id'])) {
    $checkID = (int)$_SESSION['result_data']['check_id'];
}

if ($checkID <= 0) {
    jsonResponse([
        "status" => "error",
        "message" => "Invalid check ID."
    ]);
}

// Check if prediction already exists
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

if (!$stmtExisting) {
    jsonResponse([
        "status" => "error",
        "message" => "Database prepare failed: " . $conn->error
    ]);
}

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

    jsonResponse([
        "status" => "success",
        "message" => "Prediction already completed.",
        "disease" => $row["final_prediction"],
        "confidence" => (float)$row["final_confidence"],
        "probabilities" => $probabilities,
        "recommendation" => $row["recommendation"] ?? "No recommendation found."
    ]);
}

$stmtExisting->close();

// Get uploaded image path
$sqlImage = "SELECT image_path
             FROM uploaded_images
             WHERE check_id = ?
             AND UserID = ?
             LIMIT 1";

$stmtImage = $conn->prepare($sqlImage);

if (!$stmtImage) {
    jsonResponse([
        "status" => "error",
        "message" => "Database prepare failed: " . $conn->error
    ]);
}

$stmtImage->bind_param("ii", $checkID, $userID);
$stmtImage->execute();
$imageResult = $stmtImage->get_result();

if (!$imageResult || $imageResult->num_rows == 0) {
    jsonResponse([
        "status" => "error",
        "message" => "Uploaded image record not found."
    ]);
}

$imageRow = $imageResult->fetch_assoc();
$dbImagePath = $imageRow["image_path"];

$serverImagePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $dbImagePath);
$imageFullPath = realpath($serverImagePath);

if (!$imageFullPath || !file_exists($imageFullPath)) {
    jsonResponse([
        "status" => "error",
        "message" => "Uploaded image file not found."
    ]);
}

$stmtImage->close();

// Check if running on local XAMPP
$isLocalhost = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($isLocalhost) {
    // === LOCAL XAMPP SETTINGS ===
    $python = "C:\\For UTEM\\year4sem1\\FYP\\project\\.venv\\Scripts\\python.exe";
    $scriptPath = "C:\\For UTEM\\year4sem1\\FYP\\project\\model\\version5-changeMethodUseYolo\\predictConvNeXt_V5.py";
} else {
    // === RENDER PRODUCTION SETTINGS ===
    $python = getenv("PYTHON_BIN") ?: "python3";
    $scriptPath = realpath(__DIR__ . "/../model/version5-changeMethodUseYolo/predictConvNeXt_V5.py");
}

if (!$scriptPath || !file_exists($scriptPath)) {
    jsonResponse([
        "status" => "error",
        "message" => "Prediction script not found. Check predictConvNeXt_V5.py path."
    ]);
}

// 1. Get the exact folder where the Python script lives
$modelDir = dirname($scriptPath);
$scriptName = basename($scriptPath);

// 2. Tell PHP to securely change its own directory to the model folder
chdir($modelDir);

// 3. Execute Python directly
$command = escapeshellarg($python) . " " . escapeshellarg($scriptName) . " " . escapeshellarg($imageFullPath) . " 2>&1";
$output = shell_exec($command);
$output = trim((string)$output);

// Save debug output for deployment checking
file_put_contents(__DIR__ . "/prediction_debug.txt", $output);

$lines = preg_split('/\r\n|\r|\n/', $output);
$successLine = "";

foreach ($lines as $line) {
    // Find where "SUCCESS|" is, even if there are hidden characters before it
    $pos = strpos($line, "SUCCESS|");
    if ($pos !== false) {
        // Cut out the hidden characters and keep everything from "SUCCESS|" onwards
        $successLine = substr($line, $pos);
        break;
    }
}

if (empty($successLine)) {
    jsonResponse([
        "status" => "error",
        "message" => "Prediction failed. Check frontend/prediction_debug.txt.",
        "debug" => substr($output, 0, 1000)
    ]);
}

$jsonText = substr($successLine, strlen("SUCCESS|"));
$predictionData = json_decode($jsonText, true);

if (!$predictionData || !isset($predictionData['probabilities'])) {
    jsonResponse([
        "status" => "error",
        "message" => "Prediction JSON format invalid. Check frontend/prediction_debug.txt.",
        "debug" => substr($output, 0, 1000)
    ]);
}

$probabilities = $predictionData['probabilities'];

$algalProb = isset($probabilities['Algal']) ? (float)$probabilities['Algal'] : 0;
$blightProb = isset($probabilities['Blight']) ? (float)$probabilities['Blight'] : 0;
$healthyProb = isset($probabilities['Healthy']) ? (float)$probabilities['Healthy'] : 0;
$phomopsisProb = isset($probabilities['Phomopsis']) ? (float)$probabilities['Phomopsis'] : 0;

$disease = $predictionData['final_prediction'] ?? "";
$confidence = isset($predictionData['final_confidence']) ? (float)$predictionData['final_confidence'] : 0;

if ($disease === "") {
    jsonResponse([
        "status" => "error",
        "message" => "Prediction result missing final_prediction."
    ]);
}

// Save prediction into database
$sqlPrediction = "INSERT INTO prediction
    (check_id, UserID, algal_prob, blight_prob, healthy_prob, phomopsis_prob, final_prediction, final_confidence)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmtPrediction = $conn->prepare($sqlPrediction);

if (!$stmtPrediction) {
    jsonResponse([
        "status" => "error",
        "message" => "Database prepare failed: " . $conn->error
    ]);
}

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
    jsonResponse([
        "status" => "error",
        "message" => "Failed to save prediction: " . $stmtPrediction->error
    ]);
}

$stmtPrediction->close();

// Get treatment recommendation
$recommendation = "No recommendation found.";

$sqlRec = "SELECT recommendation
           FROM treatment_recom
           WHERE disease_name = ?
           LIMIT 1";

$stmtRec = $conn->prepare($sqlRec);

if ($stmtRec) {
    $stmtRec->bind_param("s", $disease);
    $stmtRec->execute();
    $resultRec = $stmtRec->get_result();

    if ($resultRec && $resultRec->num_rows > 0) {
        $rowRec = $resultRec->fetch_assoc();
        $recommendation = $rowRec['recommendation'];
    }

    $stmtRec->close();
}

arsort($probabilities);

$_SESSION['result_data']["status"] = "completed";
$_SESSION['result_data']["isSuccess"] = true;
$_SESSION['result_data']["disease"] = $disease;
$_SESSION['result_data']["confidence"] = $confidence;
$_SESSION['result_data']["probabilities"] = $probabilities;

jsonResponse([
    "status" => "success",
    "message" => "Prediction completed.",
    "disease" => $disease,
    "confidence" => $confidence,
    "probabilities" => $probabilities,
    "recommendation" => $recommendation
]);
?>
