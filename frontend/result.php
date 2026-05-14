<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: loginPage.php");
    exit();
}

if (!isset($_SESSION['result_data'])) {
    header("Location: index.php");
    exit();
}

$data = $_SESSION['result_data'];

$status = $data['status'] ?? "error";
$isSuccess = $data['isSuccess'] ?? false;
$resultMessage = $data['resultMessage'] ?? "";
$disease = $data['disease'] ?? "";
$confidence = $data['confidence'] ?? 0;
$imageToShow = $data['imageToShow'] ?? "";
$checkID = $data['check_id'] ?? "";
$probabilities = $data['probabilities'] ?? [];

if (!empty($probabilities)) {
    arsort($probabilities);
}

$recommendation = "Waiting for AI analysis...";

if ($isSuccess && !empty($disease)) {
    $stmt = $conn->prepare("SELECT recommendation FROM treatment_recom WHERE disease_name = ? LIMIT 1");

    if ($stmt) {
        $stmt->bind_param("s", $disease);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $recommendation = $res->fetch_assoc()['recommendation'];
        }

        $stmt->close();
    }
}

$isPending = ($status === "pending");
$isLowConfidence = ($isSuccess && (float)$confidence < 70);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analysis Result - DurianCare AI</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: radial-gradient(circle at top left, #f8fafc 0%, #ecfdf5 100%); color: #0f172a; min-height: 100vh; }
.page-shell { max-width: 1000px; margin: 0 auto; padding: 40px 20px 80px; }

.result-card {
    background: #fff;
    border-radius: 32px;
    padding: 40px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    animation: fadeIn 0.5s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.98); }
    to { opacity: 1; transform: scale(1); }
}

.header-text h1 { font-size: 2.2rem; color: #064e3b; margin-bottom: 8px; font-weight: 800; }
.header-text p { color: #475569; margin-bottom: 24px; font-size: 1.05rem; }

.status-box {
    padding: 16px 20px;
    border-radius: 16px;
    margin-bottom: 30px;
    font-weight: 600;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
.status-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.status-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
.status-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

.grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; align-items: start; }
.panel { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 24px; padding: 24px; }
.panel h3 { color: #0f172a; margin-bottom: 16px; font-size: 1.2rem; font-weight: 700; }

.image-panel img {
    width: 100%;
    max-height: 380px;
    object-fit: cover;
    border-radius: 16px;
    box-shadow: 0 10px 20px rgba(0,0,0,0.05);
}

.scanner-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 40px 20px;
    text-align: center;
}
.circle-progress {
    --progress: 0deg;
    width: 160px;
    height: 160px;
    border-radius: 50%;
    background: conic-gradient(#10B981 var(--progress), #e2e8f0 0deg);
    display: grid;
    place-items: center;
    position: relative;
    margin-bottom: 20px;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.2);
}
.circle-progress::before {
    content: "";
    position: absolute;
    width: 130px;
    height: 130px;
    border-radius: 50%;
    background: #fff;
}
.circle-inner { position: relative; z-index: 2; }
.circle-percent { font-size: 2rem; font-weight: 800; color: #064e3b; }
.circle-text {
    font-size: 0.9rem;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: pulseText 1.5s infinite;
}
@keyframes pulseText {
    0%,100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.info-item { margin-bottom: 20px; }
.info-label {
    display: block;
    font-size: 0.9rem;
    color: #64748b;
    margin-bottom: 8px;
    font-weight: 600;
    text-transform: uppercase;
}
.info-value { font-size: 1.1rem; color: #0f172a; font-weight: 600; line-height: 1.6; }

.prob-list { background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; }
.prob-list div { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
.prob-list div:last-child { border-bottom: none; }
.prob-list span:last-child { color: #10B981; font-weight: 800; }

.final-tag {
    background: #10B981;
    color: #fff;
    padding: 12px 20px;
    border-radius: 14px;
    display: inline-block;
    font-size: 1.25rem;
    font-weight: 800;
    box-shadow: 0 8px 15px rgba(16, 185, 129, 0.2);
}
.final-tag.low { background: #f59e0b; box-shadow: 0 8px 15px rgba(245, 158, 11, 0.2); }

.conf-bar-wrap {
    margin-top: 12px;
    width: 100%;
    background: #e2e8f0;
    border-radius: 999px;
    height: 14px;
    overflow: hidden;
}
.conf-bar {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #10B981, #34d399);
    border-radius: 999px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
}
.conf-bar.low { background: linear-gradient(90deg, #f59e0b, #fbbf24); }

.recom-panel {
    margin-top: 30px;
    background: #fff;
    border: 2px solid #e2e8f0;
    padding: 24px;
    border-radius: 20px;
}
.recom-panel h3 { color: #064e3b; margin-bottom: 12px; font-size: 1.3rem; }
.recom-text { font-size: 1.05rem; color: #334155; line-height: 1.8; white-space: pre-line; }

.actions { display: flex; gap: 16px; margin-top: 40px; flex-wrap: wrap; }
.btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    flex: 1;
    text-align: center;
    text-decoration: none;
    border-radius: 16px;
    padding: 16px 24px;
    font-weight: 700;
    font-size: 1.05rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.btn-primary { background: #10B981; color: #fff; box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2); }
.btn-primary:hover { transform: translateY(-3px); box-shadow: 0 15px 25px rgba(16, 185, 129, 0.3); }
.btn-secondary { background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; }
.btn-secondary:hover { background: #e2e8f0; transform: translateY(-3px); }

.popup-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(8px);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 9999;
    opacity: 1;
    transition: 0.3s;
}
.popup-overlay.hidden { opacity: 0; pointer-events: none; }
.popup-card {
    background: #fff;
    border-radius: 32px;
    padding: 40px;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 25px 50px rgba(0,0,0,0.25);
    transform: scale(1);
    transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.popup-overlay.hidden .popup-card { transform: scale(0.9); }
.pop-icon { font-size: 3rem; margin-bottom: 12px; text-align: center; }
.popup-card h2 { color: #92400e; text-align: center; margin-bottom: 12px; font-size: 1.6rem; font-weight: 800; }
.popup-card p { color: #475569; text-align: center; line-height: 1.6; margin-bottom: 20px; }

.popup-guide {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 16px;
    align-items: center;
    background: #f8fafc;
    padding: 16px;
    border-radius: 20px;
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
    text-align: left;
}
.guide-sample-popup {
    width: 80px;
    height: 110px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #cbd5e1;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    background: #fff;
    flex-shrink: 0;
}
.guide-sample-popup img { width: 100%; height: 100%; object-fit: cover; display: block; }
.popup-guide-text h4 { color: #064e3b; margin-bottom: 4px; font-size: 0.95rem; font-weight: 700; }
.popup-guide-text p { color: #475569; font-size: 0.85rem; line-height: 1.5; margin-bottom: 0; text-align: left; }

.popup-buttons { display: flex; flex-direction: column; gap: 12px; }
.pop-btn { background: #f59e0b; color: #fff; width: 100%; }
.pop-btn:hover { background: #d97706; transform: translateY(-2px); }
.pop-close { background: transparent; color: #64748b; border: 2px solid #cbd5e1; width: 100%; }
.pop-close:hover { background: #f1f5f9; color: #0f172a; transform: translateY(-2px); }

.hidden-area { display: none !important; }

@media (max-width: 768px) {
    .grid-layout { grid-template-columns: 1fr; }
}

@media (max-width: 560px) {
    .page-shell { padding: 24px 12px 60px; }
    .result-card, .popup-card { padding: 22px; border-radius: 24px; }
    .header-text h1 { font-size: 1.75rem; }
    .header-text p { font-size: 0.95rem; }
    .panel { padding: 18px; }
    .actions { flex-direction: column; }
    .popup-guide { grid-template-columns: 1fr; text-align: center; gap: 14px; }
    .guide-sample-popup { width: 90px; height: 120px; margin: 0 auto; }
    .popup-guide-text h4, .popup-guide-text p { text-align: center; }
}
</style>
</head>

<body>
<?php include 'header.php'; ?>

<div class="popup-overlay hidden" id="lowConfPopup">
  <div class="popup-card">
    <div class="pop-icon">⚠️</div>
    <h2>Unclear Image Detected</h2>
    <p>The AI confidence score is below 70%. The prediction may be inaccurate.</p>

    <div class="popup-guide">
      <div class="guide-sample-popup">
        <img src="image/sample.jpg" alt="Correct Example" onerror="this.src='https://via.placeholder.com/100?text=Sample'">
      </div>
      <div class="popup-guide-text">
        <h4>How to scan properly:</h4>
        <p>Ensure your photo is bright, clear, and focuses tightly on a single durian leaf.</p>
      </div>
    </div>

    <div class="popup-buttons">
      <a href="index.php#detection" class="btn pop-btn">Upload Clearer Image</a>
      <button class="btn pop-close" onclick="document.getElementById('lowConfPopup').classList.add('hidden')">View Results Anyway</button>
    </div>
  </div>
</div>

<div class="page-shell">
  <div class="result-card">
    <div class="header-text">
      <h1>Analysis Report</h1>
      <p>Detailed AI breakdown of your durian leaf's health.</p>
    </div>

    <div id="statusBox" class="status-box <?php echo $isPending ? 'status-warning' : ($isSuccess ? ($isLowConfidence ? 'status-warning' : 'status-success') : 'status-error'); ?>">
      <?php
        if ($isPending) {
            echo "⏳ Analyzing image using neural networks...";
        } elseif ($isSuccess && $isLowConfidence) {
            echo "⚠️ Low confidence result. Treat as uncertain.";
        } elseif ($isSuccess) {
            echo "✅ Analysis complete. High confidence prediction.";
        } else {
            echo "❌ " . htmlspecialchars($resultMessage);
        }
      ?>
    </div>

    <?php if (!empty($imageToShow)): ?>
      <div class="grid-layout">
        <div class="panel image-panel">
          <h3>Scanned Subject</h3>
          <img src="<?php echo htmlspecialchars($imageToShow); ?>" alt="Uploaded Leaf">
        </div>

        <div class="panel">
          <h3>Diagnostics</h3>

          <div id="loadingArea" class="scanner-wrap <?php echo $isPending ? '' : 'hidden-area'; ?>">
            <div class="circle-progress" id="circleProg">
              <div class="circle-inner">
                <div class="circle-percent" id="circlePerc">0%</div>
                <div class="circle-text">Processing</div>
              </div>
            </div>
            <p style="color: #64748b; font-size: 0.95rem;">Extracting features...</p>
          </div>

          <div id="resultArea" class="<?php echo $isSuccess ? '' : 'hidden-area'; ?>">
            <div class="info-item">
              <span class="info-label">Primary Finding</span>
              <div class="final-tag <?php echo $isLowConfidence ? 'low' : ''; ?>" id="finalPred">
                <?php echo htmlspecialchars($disease); ?>
              </div>
            </div>

            <div class="info-item">
              <span class="info-label">Confidence Level</span>
              <div class="info-value" id="confText"><?php echo number_format((float)$confidence, 1); ?>%</div>
              <div class="conf-bar-wrap">
                <div class="conf-bar <?php echo $isLowConfidence ? 'low' : ''; ?>" id="confBar" style="width: <?php echo min(100, max(0, (float)$confidence)); ?>%;"></div>
              </div>
            </div>

            <div class="info-item" style="margin-top: 24px;">
              <span class="info-label">Probability Distribution</span>
              <div class="prob-list" id="probList">
                <?php if (!empty($probabilities)): ?>
                    <?php foreach ($probabilities as $c => $p): ?>
                        <div>
                            <span><?php echo htmlspecialchars($c); ?></span>
                            <span><?php echo number_format((float)$p, 1); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>No data</div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="recom-panel">
        <h3>💊 Action Plan</h3>
        <div class="recom-text" id="recomText"><?php echo nl2br(htmlspecialchars($recommendation)); ?></div>
      </div>
    <?php endif; ?>

    <div class="actions">
      <a href="index.php#detection" class="btn btn-primary">Scan Another Leaf</a>
      <a href="dashboard.php" class="btn btn-secondary">Go to Dashboard</a>
    </div>
  </div>
</div>

<script>
const isPending = <?php echo $isPending ? 'true' : 'false'; ?>;
const checkID = "<?php echo htmlspecialchars((string)$checkID); ?>";
const circleProg = document.getElementById("circleProg");
const circlePerc = document.getElementById("circlePerc");

let simProgress = 0;
let timer;

function escapeHTML(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function updateCircle(p) {
    if (circleProg && circlePerc) {
        circleProg.style.setProperty("--progress", (p * 3.6) + "deg");
        circlePerc.textContent = Math.round(p) + "%";
    }
}

if (isPending) {
    timer = setInterval(() => {
        if (simProgress < 90) {
            simProgress += Math.random() * 8;
            updateCircle(simProgress);
        }
    }, 300);

    const fd = new FormData();
    fd.append("check_id", checkID);

    fetch("run_prediction.php", {
        method: "POST",
        body: fd
    })
    .then(r => r.json())
    .then(d => {
        clearInterval(timer);
        updateCircle(100);

        setTimeout(() => {
            const loadingArea = document.getElementById("loadingArea");
            const resultArea = document.getElementById("resultArea");
            const statusBox = document.getElementById("statusBox");

            if (loadingArea) {
                loadingArea.classList.add("hidden-area");
            }

            if (d.status === "success") {
                if (resultArea) {
                    resultArea.classList.remove("hidden-area");
                }

                document.getElementById("finalPred").textContent = d.disease;
                document.getElementById("confText").textContent = parseFloat(d.confidence).toFixed(1) + "%";

                let cb = document.getElementById("confBar");
                cb.style.width = d.confidence + "%";

                let html = "";
                Object.entries(d.probabilities)
                    .sort((a, b) => b[1] - a[1])
                    .forEach(([k, v]) => {
                        html += `<div><span>${escapeHTML(k)}</span><span>${parseFloat(v).toFixed(1)}%</span></div>`;
                    });

                document.getElementById("probList").innerHTML = html;
                document.getElementById("recomText").innerHTML = escapeHTML(d.recommendation).replace(/\n/g, "<br>");

                if (parseFloat(d.confidence) < 70) {
                    document.getElementById("finalPred").classList.add("low");
                    cb.classList.add("low");

                    statusBox.className = "status-box status-warning";
                    statusBox.textContent = "⚠️ Low confidence result. Treat as uncertain.";

                    document.getElementById("lowConfPopup").classList.remove("hidden");
                } else {
                    statusBox.className = "status-box status-success";
                    statusBox.textContent = "✅ Analysis complete. High confidence prediction.";
                }
            } else {
                statusBox.className = "status-box status-error";
                statusBox.textContent = "❌ " + (d.message || "Prediction failed.");
            }
        }, 600);
    })
    .catch(() => {
        clearInterval(timer);

        const loadingArea = document.getElementById("loadingArea");
        const statusBox = document.getElementById("statusBox");

        if (loadingArea) {
            loadingArea.classList.add("hidden-area");
        }

        statusBox.className = "status-box status-error";
        statusBox.textContent = "❌ Network or server error occurred.";
    });
} else {
    <?php if ($isLowConfidence): ?>
        document.getElementById("lowConfPopup").classList.remove("hidden");
    <?php endif; ?>
}
</script>
</body>
</html>
