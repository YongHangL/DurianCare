<?php
session_start();
include "db.php";

// Allow either user_id or username session, to avoid login session mismatch
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: loginPage.php");
    exit();
}

if (!isset($_SESSION['result_data'])) {
    header("Location: index.php");
    exit();
}

$data = $_SESSION['result_data'];

$isSuccess = $data['isSuccess'] ?? false;
$resultMessage = $data['resultMessage'] ?? "";
$disease = $data['disease'] ?? "";
$confidence = $data['confidence'] ?? 0;
$imageToShow = $data['imageToShow'] ?? "";
$probabilities = $data['probabilities'] ?? [];

// Sort probability from highest to lowest
if (!empty($probabilities)) {
    arsort($probabilities);
}

$recommendation = "No recommendation found.";

// Get treatment recommendation based on final prediction
if ($isSuccess && !empty($disease)) {
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Prediction Result - DurianCare AI</title>

  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
    }

    body {
      background:
        radial-gradient(circle at top left, #f8fff8 0%, #eef8f0 35%, #e7f7eb 100%);
      color: #1f2d1f;
      min-height: 100vh;
    }

    .page-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 0 20px;
    }

    .result-section {
      padding: 34px 0 70px;
      display: flex;
      justify-content: center;
    }

    .result-card {
      width: 100%;
      max-width: 980px;
      background: #ffffff;
      border-radius: 28px;
      padding: 32px;
      box-shadow: 0 18px 40px rgba(16, 70, 35, 0.12);
      border: 1px solid #e2f0e6;
    }

    .result-card h1 {
      font-size: 2rem;
      color: #174b2f;
      margin-bottom: 10px;
    }

    .subtext {
      color: #58725f;
      margin-bottom: 24px;
      line-height: 1.7;
    }

    .status-box {
      padding: 16px 18px;
      border-radius: 16px;
      margin-bottom: 24px;
      font-weight: 700;
    }

    .status-success {
      background: #edf9f0;
      color: #1c6b39;
      border: 1px solid #cfe8d6;
    }

    .status-error {
      background: #fff3f3;
      color: #b33a3a;
      border: 1px solid #efcccc;
    }

    .result-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 26px;
      align-items: start;
      margin-top: 20px;
    }

    .image-box,
    .info-box,
    .recommendation-box {
      background: #f8fff9;
      border: 1px solid #e0efe4;
      border-radius: 20px;
      padding: 22px;
    }

    .image-box h3,
    .info-box h3,
    .recommendation-box h3 {
      color: #174b2f;
      margin-bottom: 16px;
      font-size: 1.18rem;
    }

    .image-box img {
      width: 100%;
      max-height: 360px;
      object-fit: contain;
      border-radius: 16px;
      border: 1px solid #dcebdd;
      background: #fff;
    }

    .info-item {
      margin-bottom: 18px;
    }

    .info-label {
      display: block;
      font-size: 0.92rem;
      color: #6a856f;
      margin-bottom: 6px;
      font-weight: 700;
    }

    .info-value {
      font-size: 1.08rem;
      color: #1b5531;
      font-weight: 800;
      line-height: 1.6;
      word-break: break-word;
    }

    .probability-list {
      background: #ffffff;
      border: 1px solid #dcebdd;
      border-radius: 14px;
      padding: 14px 16px;
      line-height: 1.8;
    }

    .final-result {
      background: #edf9f0;
      border: 1px solid #cfe8d6;
      color: #174b2f;
      border-radius: 14px;
      padding: 12px 14px;
      display: inline-block;
      font-size: 1.15rem;
    }

    .recommendation-box {
      margin-top: 26px;
    }

    .recommendation-text {
      font-size: 1rem;
      color: #35553d;
      line-height: 1.8;
      background: #ffffff;
      border: 1px solid #dcebdd;
      border-radius: 14px;
      padding: 16px;
      white-space: pre-line;
    }

    .confidence-bar-wrap {
      margin-top: 10px;
      width: 100%;
      background: #e7f4ea;
      border-radius: 999px;
      height: 16px;
      overflow: hidden;
      border: 1px solid #d6e8da;
    }

    .confidence-bar {
      height: 100%;
      background: linear-gradient(90deg, #2d8c4d, #1f8a45);
      border-radius: 999px;
    }

    .actions {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-top: 28px;
    }

    .btn {
      display: inline-block;
      text-decoration: none;
      border: none;
      border-radius: 12px;
      padding: 13px 22px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
    }

    .btn-primary {
      background: #1f8a45;
      color: #fff;
    }

    .btn-primary:hover {
      background: #166b34;
    }

    .btn-secondary {
      background: #edf6ef;
      color: #23583a;
      border: 1px solid #cfe3d3;
    }

    .btn-secondary:hover {
      background: #e1f1e5;
    }

    @media (max-width: 900px) {
      .result-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 560px) {
      .page-shell {
        padding: 0 14px;
      }

      .result-card {
        padding: 22px 18px;
        border-radius: 22px;
      }

      .actions {
        flex-direction: column;
      }

      .btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="page-shell">
  <section class="result-section">
    <div class="result-card">

      <h1>Prediction Result</h1>
      <p class="subtext">
        View the uploaded image, prediction probabilities, final prediction, confidence score, and treatment recommendation below.
      </p>

      <?php if ($isSuccess): ?>

        <div class="status-box status-success">
          Image uploaded and analyzed successfully.
        </div>

        <div class="result-grid">

          <div class="image-box">
            <h3>Uploaded Image</h3>
            <img src="<?php echo htmlspecialchars($imageToShow); ?>" alt="Uploaded Leaf Image">
          </div>

          <div class="info-box">
            <h3>Analysis Result</h3>

            <div class="info-item">
              <span class="info-label">Prediction Probabilities</span>

              <div class="info-value probability-list">
                <?php if (!empty($probabilities)): ?>
                  <?php foreach ($probabilities as $className => $prob): ?>
                    <?php echo htmlspecialchars($className); ?>:
                    <?php echo number_format((float)$prob, 2); ?>%<br>
                  <?php endforeach; ?>
                <?php else: ?>
                  No probability data found.
                <?php endif; ?>
              </div>
            </div>

            <div class="info-item">
              <span class="info-label">Final Prediction</span>
              <span class="info-value final-result">
                <?php echo htmlspecialchars($disease); ?> | <?php echo number_format((float)$confidence, 2); ?>%
              </span>
            </div>

            <div class="info-item">
              <span class="info-label">Confidence</span>
              <span class="info-value">
                <?php echo number_format((float)$confidence, 2); ?>%
              </span>

              <div class="confidence-bar-wrap">
                <div class="confidence-bar"
                     style="width: <?php echo min(100, max(0, (float)$confidence)); ?>%;">
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="recommendation-box">
          <h3>Treatment Recommendation</h3>
          <div class="recommendation-text">
            <?php echo htmlspecialchars($recommendation); ?>
          </div>
        </div>

      <?php else: ?>

        <div class="status-box status-error">
          <?php echo htmlspecialchars($resultMessage); ?>
        </div>

      <?php endif; ?>

      <div class="actions">
        <a href="index.php" class="btn btn-primary">Back to Upload</a>
        <a href="dashboard.php" class="btn btn-secondary">View Dashboard</a>
      </div>

    </div>
  </section>
</div>

</body>
</html>