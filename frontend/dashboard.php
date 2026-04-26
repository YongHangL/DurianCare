<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginPage.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// =========================
// Get user info
// =========================
$user_sql = "SELECT * FROM user WHERE UserID = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

$userName = $user['UserName'] ?? 'User';
$userEmail = $user['email'] ?? 'No email';

// =========================
// Get upload history + prediction + recommendation
// =========================
$history_sql = "SELECT
                    u.check_id,
                    u.image_name,
                    u.image_path,
                    u.uploaded_at,

                    p.algal_prob,
                    p.blight_prob,
                    p.healthy_prob,
                    p.phomopsis_prob,
                    p.final_prediction AS prediction,
                    p.final_confidence AS confidence,

                    t.recommendation
                FROM uploaded_images u
                LEFT JOIN prediction p
                    ON u.check_id = p.check_id
                    AND u.UserID = p.UserID
                LEFT JOIN treatment_recom t
                    ON p.final_prediction = t.disease_name
                WHERE u.UserID = ?
                ORDER BY u.uploaded_at DESC";

$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$historyRows = [];
while ($row = $result->fetch_assoc()) {
    $historyRows[] = $row;
}

// =========================
// Dashboard statistics
// =========================
$totalUploads = count($historyRows);

$latestPrediction = "No record yet";
if ($totalUploads > 0 && !empty($historyRows[0]['prediction'])) {
    $latestPrediction = $historyRows[0]['prediction'];
}

$avgConfidence = 0;
$countConfidence = 0;
$sumConfidence = 0;

foreach ($historyRows as $row) {
    if ($row['confidence'] !== null && $row['confidence'] !== '') {
        $sumConfidence += (float)$row['confidence'];
        $countConfidence++;
    }
}

if ($countConfidence > 0) {
    $avgConfidence = $sumConfidence / $countConfidence;
}

function safeText($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - DurianCare AI</title>

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
    padding: 0 20px 50px;
}

.dashboard-hero {
    padding: 30px 0 24px;
}

.dashboard-hero h1 {
    font-size: 2.2rem;
    color: #174b2f;
    margin-bottom: 10px;
}

.dashboard-hero p {
    color: #56705d;
    line-height: 1.7;
}

.user-panel {
    background: linear-gradient(135deg, #ffffff, #f4fbf5);
    border: 1px solid #e1eee5;
    border-radius: 26px;
    padding: 26px;
    box-shadow: 0 16px 36px rgba(20, 72, 38, 0.08);
    margin-bottom: 22px;
}

.user-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 20px;
    align-items: center;
}

.user-info h2 {
    color: #174b2f;
    font-size: 1.6rem;
    margin-bottom: 8px;
}

.user-info p {
    color: #5d7563;
}

.quick-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    flex-wrap: wrap;
}

.quick-btn {
    display: inline-block;
    text-decoration: none;
    border-radius: 13px;
    padding: 13px 20px;
    font-weight: 700;
    transition: 0.2s ease;
}

.quick-btn.primary {
    background: #1f8a45;
    color: #ffffff;
}

.quick-btn.primary:hover {
    background: #166b34;
}

.quick-btn.secondary {
    background: #edf6ef;
    color: #23583a;
    border: 1px solid #cfe3d3;
}

.quick-btn.secondary:hover {
    background: #e1f1e5;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 24px;
}

.stat-card {
    background: #ffffff;
    border: 1px solid #e1eee5;
    border-radius: 22px;
    padding: 22px;
    box-shadow: 0 12px 28px rgba(20, 72, 38, 0.07);
}

.stat-card span {
    display: block;
    color: #6a856f;
    font-size: 0.95rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.stat-card strong {
    color: #174b2f;
    font-size: 1.7rem;
}

.history-card {
    background: #ffffff;
    border: 1px solid #e1eee5;
    border-radius: 26px;
    padding: 26px;
    box-shadow: 0 16px 36px rgba(20, 72, 38, 0.08);
}

.history-header {
    margin-bottom: 22px;
}

.history-header h2 {
    color: #174b2f;
    font-size: 1.6rem;
    margin-bottom: 8px;
}

.history-header p {
    color: #5d7563;
    line-height: 1.6;
}

.empty-state {
    background: #f8fff9;
    border: 1px dashed #cfe3d3;
    color: #4f6c56;
    border-radius: 18px;
    padding: 24px;
    text-align: center;
    font-weight: 700;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.history-item {
    display: grid;
    grid-template-columns: 230px 1fr;
    gap: 20px;
    background: #f8fff9;
    border: 1px solid #e0efe4;
    border-radius: 20px;
    padding: 18px;
}

.image-box img {
    width: 100%;
    height: 190px;
    object-fit: contain;
    border-radius: 16px;
    border: 1px solid #dcebdd;
    background: #ffffff;
}

.history-content h3 {
    color: #174b2f;
    font-size: 1.15rem;
    margin-bottom: 12px;
    word-break: break-word;
}

.meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 12px;
}

.meta-badge {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 999px;
    background: #ffffff;
    border: 1px solid #dbeadf;
    color: #31543b;
    font-size: 0.9rem;
    font-weight: 700;
}

.prediction {
    color: #1f8a45;
    font-weight: 800;
}

.probability-box,
.recommendation {
    background: #ffffff;
    border: 1px solid #dcebdd;
    padding: 14px;
    border-radius: 14px;
    margin-top: 10px;
    color: #3d5d44;
    line-height: 1.7;
    white-space: pre-line;
}

.probability-box strong,
.recommendation strong {
    color: #174b2f;
}

.prob-row {
    display: flex;
    justify-content: space-between;
    gap: 16px;
    border-bottom: 1px solid #edf4ef;
    padding: 5px 0;
}

.prob-row:last-child {
    border-bottom: none;
}

.prob-name {
    font-weight: 700;
    color: #31543b;
}

.prob-value {
    font-weight: 800;
    color: #1f8a45;
}

@media (max-width: 920px) {
    .user-grid,
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .quick-actions {
        justify-content: flex-start;
    }

    .history-item {
        grid-template-columns: 1fr;
    }

    .image-box img {
        height: 220px;
    }
}

@media (max-width: 560px) {
    .page-shell {
        padding: 0 14px 36px;
    }

    .dashboard-hero h1 {
        font-size: 1.85rem;
    }

    .user-panel,
    .history-card,
    .stat-card {
        border-radius: 20px;
        padding: 18px;
    }

    .quick-actions {
        flex-direction: column;
    }

    .quick-btn {
        width: 100%;
        text-align: center;
    }

    .image-box img {
        height: 190px;
    }

    .prob-row {
        flex-direction: column;
        gap: 2px;
    }
}
</style>
</head>

<body>

<?php include 'header.php'; ?>

<div class="page-shell">

    <section class="dashboard-hero">
        <h1>User Dashboard</h1>
        <p>View your prediction history, recent results, prediction probabilities, and treatment recommendations in one place.</p>
    </section>

    <section class="user-panel">
        <div class="user-grid">
            <div class="user-info">
                <h2>Welcome, <?php echo safeText($userName); ?></h2>
                <p>Email: <?php echo safeText($userEmail); ?></p>
            </div>

            <div class="quick-actions">
                <a href="index.php#detection" class="quick-btn primary">New Detection</a>
                <a href="logout.php" class="quick-btn secondary">Logout</a>
            </div>
        </div>
    </section>

    <section class="stats-grid">
        <div class="stat-card">
            <span>Total Uploads</span>
            <strong><?php echo $totalUploads; ?></strong>
        </div>

        <div class="stat-card">
            <span>Latest Prediction</span>
            <strong><?php echo safeText($latestPrediction); ?></strong>
        </div>

        <div class="stat-card">
            <span>Average Confidence</span>
            <strong>
                <?php echo $avgConfidence > 0 ? number_format($avgConfidence, 2) . '%' : 'No data'; ?>
            </strong>
        </div>
    </section>

    <section class="history-card">
        <div class="history-header">
            <h2>Your Detection History</h2>
            <p>All uploaded leaf images and their prediction results are shown below.</p>
        </div>

        <?php if (empty($historyRows)): ?>

            <div class="empty-state">
                No detection history found yet.
            </div>

        <?php else: ?>

            <div class="history-list">

                <?php foreach ($historyRows as $row): ?>

                    <?php
                    $prediction = $row['prediction'] ?? '';
                    $confidence = $row['confidence'] ?? null;

                    $probList = [
                        "Algal" => $row['algal_prob'] ?? null,
                        "Blight" => $row['blight_prob'] ?? null,
                        "Healthy" => $row['healthy_prob'] ?? null,
                        "Phomopsis" => $row['phomopsis_prob'] ?? null
                    ];

                    // Remove null probability values
                    $probList = array_filter($probList, function ($value) {
                        return $value !== null && $value !== '';
                    });

                    // Sort probability from highest to lowest
                    if (!empty($probList)) {
                        arsort($probList);
                    }
                    ?>

                    <div class="history-item">

                        <div class="image-box">
                            <img src="<?php echo safeText($row['image_path']); ?>" alt="Uploaded Image">
                        </div>

                        <div class="history-content">

                            <h3><?php echo safeText($row['image_name']); ?></h3>

                            <div class="meta-row">
                                <span class="meta-badge">
                                    Prediction:
                                    <span class="prediction">
                                        <?php echo !empty($prediction) ? safeText($prediction) : 'No result'; ?>
                                    </span>
                                </span>

                                <span class="meta-badge">
                                    Confidence:
                                    <?php echo $confidence !== null && $confidence !== '' ? number_format((float)$confidence, 2) . '%' : 'N/A'; ?>
                                </span>

                                <span class="meta-badge">
                                    Date: <?php echo safeText($row['uploaded_at']); ?>
                                </span>
                            </div>

                            <div class="probability-box">
                                <strong>Prediction Probabilities:</strong><br>

                                <?php if (!empty($probList)): ?>

                                    <?php foreach ($probList as $className => $prob): ?>
                                        <div class="prob-row">
                                            <span class="prob-name"><?php echo safeText($className); ?></span>
                                            <span class="prob-value"><?php echo number_format((float)$prob, 2); ?>%</span>
                                        </div>
                                    <?php endforeach; ?>

                                <?php else: ?>

                                    No probability data found.

                                <?php endif; ?>
                            </div>

                            <div class="recommendation">
                                <strong>Treatment Recommendation:</strong><br>
                                <?php echo !empty($row['recommendation']) ? safeText($row['recommendation']) : 'No recommendation found'; ?>
                            </div>

                        </div>
                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</div>

</body>
</html>