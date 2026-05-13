<?php
session_start();
include "db.php";
if (!isset($_SESSION['user_id'])) { header("Location: loginPage.php"); exit(); }
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM user WHERE UserID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$userName = $user['UserName'] ?? 'User';
$userEmail = $user['email'] ?? 'No email';

$history_sql = "SELECT u.check_id, u.image_name, u.image_path, u.uploaded_at,
                    p.algal_prob, p.blight_prob, p.healthy_prob, p.phomopsis_prob,
                    p.final_prediction AS prediction, p.final_confidence AS confidence, t.recommendation
                FROM uploaded_images u LEFT JOIN prediction p ON u.check_id = p.check_id AND u.UserID = p.UserID
                LEFT JOIN treatment_recom t ON p.final_prediction = t.disease_name
                WHERE u.UserID = ? ORDER BY u.uploaded_at DESC";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$historyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalUploads = count($historyRows);
$latestPrediction = $totalUploads > 0 ? (!empty($historyRows[0]['prediction']) ? $historyRows[0]['prediction'] : "Pending") : "No record";
$avgConfidence = 0; $countConfidence = 0; $sumConfidence = 0; $lowConfidenceCount = 0;
foreach ($historyRows as $row) {
    if ($row['confidence'] !== null && $row['confidence'] !== '') {
        $val = (float)$row['confidence']; $sumConfidence += $val; $countConfidence++;
        if ($val < 70) $lowConfidenceCount++;
    }
}
if ($countConfidence > 0) $avgConfidence = $sumConfidence / $countConfidence;
function safeText($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - DurianCare AI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: radial-gradient(circle at top left, #f8fafc 0%, #ecfdf5 100%); color: #0f172a; min-height: 100vh; }
.page-shell { max-width: 1180px; margin: 0 auto; padding: 0 20px 60px; }

.dashboard-hero { padding: 40px 0 24px; animation: fadeIn 0.6s ease;}
@keyframes fadeIn { from { opacity:0; transform: translateY(10px);} to {opacity:1; transform:translateY(0);}}
.dashboard-hero h1 { font-size: 2.4rem; color: #064e3b; margin-bottom: 10px; font-weight: 800;}
.dashboard-hero p { color: #475569; font-size: 1.05rem; }

.user-panel {
    background: #fff; border-radius: 24px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    margin-bottom: 24px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;
}
.user-info h2 { color: #064e3b; font-size: 1.8rem; margin-bottom: 6px; font-weight: 700;}
.user-info p { color: #64748b; font-weight: 500;}
.quick-btn {
    text-decoration: none; border-radius: 14px; padding: 12px 24px; font-weight: 600;
    transition: all 0.3s ease; display: inline-block;
}
.quick-btn.primary { background: #10B981; color: #fff; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2); }
.quick-btn.primary:hover { background: #059669; transform: translateY(-2px); }
.quick-btn.secondary { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
.quick-btn.secondary:hover { background: #e2e8f0; color: #0f172a; }

.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
.stat-card {
    background: #fff; border-radius: 20px; padding: 24px; border: 1px solid #e2e8f0;
    box-shadow: 0 10px 20px rgba(0,0,0,0.02); transition: all 0.3s ease; position: relative; overflow: hidden;
}
.stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(16, 185, 129, 0.1); border-color: #a7f3d0;}
.stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background: #10B981; border-radius: 20px 0 0 20px;}
.stat-card.warning::before { background: #f59e0b; }
.stat-card span { display: block; color: #64748b; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
.stat-card strong { color: #064e3b; font-size: 2rem; font-weight: 800;}
.stat-card.warning strong { color: #b45309; }

.history-header { margin-bottom: 24px; }
.history-header h2 { color: #064e3b; font-size: 1.8rem; margin-bottom: 8px; font-weight: 700;}
.history-header p { color: #475569; }
.empty-state { background: #fff; border: 2px dashed #cbd5e1; color: #64748b; border-radius: 20px; padding: 40px; text-align: center; font-weight: 600; font-size: 1.1rem;}

.history-list { display: flex; flex-direction: column; gap: 20px; }
.history-item {
    display: grid; grid-template-columns: 240px 1fr; gap: 24px; background: #fff;
    border: 1px solid #e2e8f0; border-radius: 24px; padding: 24px; transition: all 0.3s ease;
    box-shadow: 0 10px 20px rgba(0,0,0,0.02);
}
.history-item:hover { box-shadow: 0 15px 35px rgba(0,0,0,0.06); transform: translateY(-2px);}
.history-item.low-confidence-item { background: #fffbeb; border-color: #fde68a; }
.history-item.pending-item { background: #f8fafc; border-color: #cbd5e1; }

.image-box img { width: 100%; height: 200px; object-fit: cover; border-radius: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.history-content h3 { color: #0f172a; font-size: 1.3rem; margin-bottom: 14px; font-weight: 700; word-break: break-word;}
.meta-row { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 16px; }
.meta-badge {
    padding: 6px 12px; border-radius: 10px; background: #f1f5f9; color: #475569;
    font-size: 0.85rem; font-weight: 600; border: 1px solid #e2e8f0;
}
.quality-badge { padding: 6px 12px; border-radius: 10px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px;}
.quality-badge.reliable { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.quality-badge.low-confidence { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.quality-badge.pending { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }

.probability-box, .recommendation {
    background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 16px; margin-top: 12px; color: #334155; font-size: 0.95rem; line-height: 1.6;
}
.low-confidence-item .probability-box, .low-confidence-item .recommendation { background: #fff; border-color: #fde68a;}
.probability-box strong, .recommendation strong { color: #0f172a; display: block; margin-bottom: 8px;}
.prob-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
.prob-row:last-child { border-bottom: none; }
.prob-value { font-weight: 700; color: #10B981; }
.low-confidence-item .prob-value { color: #d97706; }

.confidence-meter { width: 100%; height: 12px; background: #e2e8f0; border-radius: 999px; overflow: hidden; margin: 12px 0; }
.confidence-fill { height: 100%; background: linear-gradient(90deg, #10B981, #34d399); border-radius: 999px; }
.confidence-fill.low { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.warning-note, .pending-note { padding: 12px 16px; border-radius: 12px; margin-top: 12px; font-weight: 600; font-size: 0.9rem;}
.warning-note { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.pending-note { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }

@media (max-width: 1050px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) {
    .user-panel { flex-direction: column; align-items: flex-start;}
    .history-item { grid-template-columns: 1fr; }
    .image-box img { height: 240px; }
}
@media (max-width: 560px) {
    .stats-grid { grid-template-columns: 1fr; }
    .quick-btn { width: 100%; text-align: center; }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="page-shell">
    <section class="dashboard-hero">
        <h1>Dashboard Overview</h1>
        <p>Track your recent detections and monitor your durian plantation's health.</p>
    </section>
    <section class="user-panel">
        <div class="user-info">
            <h2>Hello, <?php echo safeText($userName); ?> 👋</h2>
            <p><?php echo safeText($userEmail); ?></p>
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <a href="index.php#detection" class="quick-btn primary">Scan New Leaf</a>
            <a href="logout.php" class="quick-btn secondary">Logout</a>
        </div>
    </section>
    <section class="stats-grid">
        <div class="stat-card"><span>Total Scans</span><strong><?php echo $totalUploads; ?></strong></div>
        <div class="stat-card"><span>Latest Status</span><strong style="font-size: 1.4rem; line-height: 1.5;"><?php echo safeText($latestPrediction); ?></strong></div>
        <div class="stat-card"><span>Avg Confidence</span><strong><?php echo $avgConfidence > 0 ? number_format($avgConfidence, 1) . '%' : '--'; ?></strong></div>
        <div class="stat-card warning"><span>Low Confidence</span><strong><?php echo $lowConfidenceCount; ?></strong></div>
    </section>
    <section class="history-section">
        <div class="history-header"><h2>Detection History</h2><p>Review past analyses and recommendations.</p></div>
        <?php if (empty($historyRows)): ?>
            <div class="empty-state">No scans yet. Start by uploading a leaf image.</div>
        <?php else: ?>
            <div class="history-list">
                <?php foreach ($historyRows as $row):
                    $prediction = $row['prediction'] ?? ''; $confidence = $row['confidence'] ?? null;
                    $hasPrediction = !empty($prediction) && $confidence !== null && $confidence !== '';
                    $confVal = $hasPrediction ? (float)$confidence : null; $isLow = $hasPrediction && $confVal < 70;
                    if (!$hasPrediction) { $c="pending-item"; $qc="pending"; $qt="Pending"; $qi="⏳"; }
                    elseif ($isLow) { $c="low-confidence-item"; $qc="low-confidence"; $qt="Low Confidence"; $qi="⚠️"; }
                    else { $c=""; $qc="reliable"; $qt="Reliable"; $qi="✅"; }
                    $probs = array_filter(["Algal"=>$row['algal_prob']??null, "Blight"=>$row['blight_prob']??null, "Healthy"=>$row['healthy_prob']??null, "Phomopsis"=>$row['phomopsis_prob']??null], fn($v)=>$v!==null&&$v!=='');
                    if(!empty($probs)) arsort($probs);
                ?>
                <div class="history-item <?php echo $c; ?>">
                    <div class="image-box"><img src="<?php echo safeText($row['image_path']); ?>" alt="Leaf"></div>
                    <div class="history-content">
                        <h3><?php echo safeText($row['image_name']); ?></h3>
                        <div class="meta-row">
                            <span class="quality-badge <?php echo $qc; ?>"><?php echo $qi." ".$qt; ?></span>
                            <span class="meta-badge">Result: <b><?php echo !empty($prediction) ? safeText($prediction) : 'Processing'; ?></b></span>
                            <span class="meta-badge">Score: <b><?php echo $hasPrediction ? number_format($confVal,1).'%' : '--'; ?></b></span>
                            <span class="meta-badge">Date: <?php echo date("M d, Y", strtotime($row['uploaded_at'])); ?></span>
                        </div>
                        <?php if ($hasPrediction): ?>
                            <div class="confidence-meter"><div class="confidence-fill <?php echo $isLow?'low':''; ?>" style="width: <?php echo min(100, max(0, $confVal)); ?>%;"></div></div>
                        <?php endif; ?>
                        <?php if ($isLow): ?><div class="warning-note">Score below 70%. Recommendation may be inaccurate.</div>
                        <?php elseif (!$hasPrediction): ?><div class="pending-note">Analysis in progress. Check back soon.</div><?php endif; ?>

                        <div class="probability-box"><strong>Probability Breakdown:</strong>
                            <?php if(!empty($probs)): foreach($probs as $n=>$p): ?>
                                <div class="prob-row"><span><?php echo safeText($n); ?></span><span class="prob-value"><?php echo number_format((float)$p,1); ?>%</span></div>
                            <?php endforeach; else: ?> No data. <?php endif; ?>
                        </div>
                        <div class="recommendation"><strong>Suggested Action:</strong>
                            <?php if(!$hasPrediction) echo "Pending analysis."; else echo !empty($row['recommendation']) ? nl2br(safeText($row['recommendation'])) : 'None available.'; ?>
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