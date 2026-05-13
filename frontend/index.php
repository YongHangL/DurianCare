<?php
session_start();
$isLoggedIn = isset($_SESSION['username']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Durian Leaf Disease Detection</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      background: radial-gradient(circle at top left, #f8fafc 0%, #ecfdf5 100%);
      color: #0f172a;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .page-shell { max-width: 1180px; margin: 0 auto; padding: 0 20px; }

    /* Animations */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-up { animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards; opacity: 0; }
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }

    .hero {
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 40px;
      align-items: center;
      padding: 60px 0 90px;
    }
    .hero-text h1 {
      font-size: 3.5rem;
      line-height: 1.15;
      margin-bottom: 18px;
      color: #064e3b;
      font-weight: 800;
      letter-spacing: -1px;
    }
    .hero-text p {
      font-size: 1.1rem;
      line-height: 1.8;
      color: #334155;
      margin-bottom: 28px;
      max-width: 620px;
    }
    .hero-badges { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
    .badge {
      background: rgba(255,255,255,0.8);
      border: 1px solid #a7f3d0;
      color: #059669;
      padding: 8px 16px;
      border-radius: 999px;
      font-size: 0.85rem;
      font-weight: 600;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.05);
      transition: all 0.3s ease;
    }
    .badge:hover { transform: translateY(-2px); background: #fff; box-shadow: 0 6px 16px rgba(16, 185, 129, 0.1); }
    .hero-actions { display: flex; flex-wrap: wrap; gap: 16px; }

    .btn {
      text-decoration: none;
      padding: 14px 28px;
      border-radius: 16px;
      font-weight: 700;
      display: inline-block;
      transition: all 0.3s ease;
      text-align: center;
      border: none;
      cursor: pointer;
    }
    .btn-primary {
      background: linear-gradient(135deg, #10B981, #059669);
      color: #fff;
      box-shadow: 0 10px 20px rgba(16, 185, 129, 0.25);
    }
    .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 14px 28px rgba(16, 185, 129, 0.35); }
    .btn-secondary { background: #fff; color: #064e3b; border: 1px solid #cbd5e1; }
    .btn-secondary:hover { background: #f8fafc; transform: translateY(-3px); border-color: #94a3b8; }

    .upload-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(10px);
      border-radius: 32px;
      padding: 40px;
      box-shadow: 0 24px 50px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.5);
    }
    .upload-card h2 { font-size: 1.8rem; margin-bottom: 12px; color: #064e3b; font-weight: 700; }
    .upload-card p { color: #475569; margin-bottom: 24px; line-height: 1.6; }

    .upload-guideline {
      background: #f8fafc;
      border-radius: 20px;
      padding: 20px;
      margin-bottom: 24px;
      border: 1px solid #e2e8f0;
    }
    .upload-guideline h3 { color: #0f172a; font-size: 1rem; margin-bottom: 12px; font-weight: 700;}

    /* UPDATED: Guideline Layout to feature a Portrait style image box */
    .guideline-layout { display: grid; grid-template-columns: auto 1fr; gap: 20px; align-items: center; }
    .guide-sample {
      width: 90px;
      height: 120px; /* Tall portrait shape fits leaves better */
      border-radius: 12px;
      overflow: hidden;
      border: 2px solid #cbd5e1;
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
      background: #fff;
    }
    .guide-sample img {
      width: 100%;
      height: 100%;
      object-fit: cover; /* Perfectly fills the portrait box */
      display: block;
    }

    .guide-list { color: #475569; line-height: 1.6; font-size: 0.9rem; padding-left: 20px; }
    .guide-list li { margin-bottom: 6px; }

    .upload-box {
      border: 2px dashed #94a3b8;
      border-radius: 24px;
      padding: 40px 20px;
      text-align: center;
      background: #f1f5f9;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
    }
    .upload-box:hover, .upload-box.dragover {
      background: #ecfdf5;
      border-color: #10B981;
      transform: scale(1.02);
    }
    .upload-icon { font-size: 3rem; margin-bottom: 12px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); transition: transform 0.3s ease; }
    .upload-box:hover .upload-icon { transform: translateY(-5px); }
    .upload-box h3 { margin-bottom: 8px; color: #0f172a; font-weight: 600;}
    .upload-box span { display: block; font-size: 0.85rem; color: #64748b; margin-bottom: 20px; }
    input[type="file"] { display: none; }

    .preview { margin-top: 24px; display: none; text-align: center; animation: fadeInUp 0.4s ease; }
    .preview img { max-width: 100%; max-height: 260px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .preview p { margin-top: 12px; color: #064e3b; font-weight: 600; word-break: break-all; }

    .actions { display: flex; gap: 14px; margin-top: 24px; }
    .actions .btn { flex: 1; }

    .info-section { padding: 40px 0 80px; }
    .section-title { text-align: center; margin-bottom: 40px; }
    .section-title h2 { font-size: 2.2rem; color: #064e3b; margin-bottom: 12px; font-weight: 800;}
    .section-title p { color: #475569; max-width: 600px; margin: 0 auto; line-height: 1.7; }

    .cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
    .card {
      background: #fff;
      border-radius: 24px;
      padding: 30px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 10px 30px rgba(0,0,0,0.03);
      transition: all 0.3s ease;
      text-align: center;
    }
    .card:hover { transform: translateY(-8px); box-shadow: 0 20px 40px rgba(16, 185, 129, 0.1); border-color: #a7f3d0; }
    .card-icon { font-size: 2.5rem; margin-bottom: 16px; }
    .card h3 { margin-bottom: 12px; color: #0f172a; font-weight: 700; }
    .card p { color: #475569; line-height: 1.6; font-size: 0.95rem; }

    .footer { text-align: center; padding: 20px 0 40px; color: #64748b; font-size: 0.9rem; font-weight: 500;}

    @media (max-width: 960px) {
      .hero { grid-template-columns: 1fr; padding-top: 40px; }
      .hero-text h1 { font-size: 2.8rem; }
      .cards { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
      .hero-text h1 { font-size: 2.2rem; }
      .upload-card { padding: 24px; border-radius: 24px; }
      .actions, .hero-actions { flex-direction: column; }

      /* Mobile fix for guideline layout */
      .guideline-layout { grid-template-columns: 1fr; text-align: center; gap: 14px;}
      .guide-sample { width: 100px; height: 130px; margin: 0 auto; }
      .guide-list { text-align: left; }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-shell">
  <section class="hero" id="detection">
    <div class="hero-text animate-up">
      <h1>Smarter Care for Your Durian Trees</h1>
      <p>Upload a durian leaf image to identify possible diseases instantly. Get AI-driven predictions, confidence scores, and actionable treatment recommendations.</p>
      <div class="hero-badges">
        <div class="badge">Algal Leaf Spot</div>
        <div class="badge">Leaf Blight</div>
        <div class="badge">Phomopsis</div>
        <div class="badge">Healthy Leaf</div>
      </div>
      <div class="hero-actions">
        <a href="#detection" class="btn btn-primary">Start Detection</a>
        <a href="#about" class="btn btn-secondary">Learn More</a>
      </div>
    </div>

    <div class="upload-card animate-up delay-1">
      <h2>Scan Leaf</h2>
      <p>Select a clear image for AI analysis.</p>

      <div class="upload-guideline">
        <h3>📸 Photo Guidelines</h3>
        <div class="guideline-layout">
          <div class="guide-sample">
            <img src="image/sample.jpg" alt="Sample Example" onerror="this.src='https://via.placeholder.com/100?text=Sample'">
          </div>
          <ul class="guide-list">
            <li>Capture one main leaf clearly.</li>
            <li>Ensure the diseased area is visible.</li>
            <li>Use bright natural lighting.</li>
            <li>Supported: JPG, JPEG, PNG.</li>
          </ul>
        </div>
      </div>

      <form action="<?php echo $isLoggedIn ? 'upload.php' : 'loginPage.php'; ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="upload-box" id="dropZone" onclick="document.getElementById('imageUpload').click()">
          <div class="upload-icon">☁️</div>
          <h3>Click or Drag Image Here</h3>
          <span>Maximum file size: 5MB</span>

          <?php if ($isLoggedIn): ?>
            <input type="file" id="imageUpload" name="leafImage" accept="image/*" required />
          <?php else: ?>
            <a href="loginPage.php" class="btn btn-primary" style="margin-top:10px;" onclick="event.stopPropagation()">Login to Upload</a>
          <?php endif; ?>

          <div class="preview" id="previewBox">
            <img id="previewImage" src="" alt="Preview" />
            <p id="fileName"></p>
          </div>
        </div>

        <div class="actions">
          <?php if ($isLoggedIn): ?>
            <button type="submit" class="btn btn-primary">Analyze Image</button>
          <?php endif; ?>
          <button type="button" class="btn btn-secondary" id="resetBtn">Clear</button>
        </div>
      </form>
    </div>
  </section>

  <section class="info-section animate-up delay-2" id="about">
    <div class="section-title">
      <h2>How It Works</h2>
      <p>Three simple steps to secure the health of your durian plantation.</p>
    </div>
    <div class="cards">
      <div class="card">
        <div class="card-icon">📤</div>
        <h3>1. Upload</h3>
        <p>Snap a photo with your mobile or select an image from your desktop.</p>
      </div>
      <div class="card">
        <div class="card-icon">🧠</div>
        <h3>2. AI Analysis</h3>
        <p>Our advanced model scans the leaf to detect patterns of common diseases.</p>
      </div>
      <div class="card">
        <div class="card-icon">💊</div>
        <h3>3. Get Results</h3>
        <p>Receive an instant diagnosis with a confidence score and tailored treatment plan.</p>
      </div>
    </div>
  </section>

  <div class="footer">
    © Final Year Project — DurianCare AI System
  </div>
</div>

<script>
  const imageUpload = document.getElementById('imageUpload');
  const previewBox = document.getElementById('previewBox');
  const previewImage = document.getElementById('previewImage');
  const fileName = document.getElementById('fileName');
  const resetButton = document.getElementById('resetBtn');
  const dropZone = document.getElementById('dropZone');

  if (imageUpload) {
    imageUpload.addEventListener('change', function (e) {
      handleFiles(this.files);
    });
  }

  if(dropZone && imageUpload) {
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        imageUpload.files = e.dataTransfer.files;
        handleFiles(e.dataTransfer.files);
    });
  }

  function handleFiles(files) {
    const file = files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        previewImage.src = e.target.result;
        previewBox.style.display = 'block';
        fileName.textContent = file.name;
        dropZone.querySelector('.upload-icon').style.display = 'none';
        dropZone.querySelector('h3').style.display = 'none';
        dropZone.querySelector('span').style.display = 'none';
      };
      reader.readAsDataURL(file);
    }
  }

  if (resetButton) {
    resetButton.addEventListener('click', function (e) {
      e.stopPropagation();
      if (imageUpload) imageUpload.value = '';
      previewImage.src = '';
      fileName.textContent = '';
      previewBox.style.display = 'none';
      dropZone.querySelector('.upload-icon').style.display = 'block';
      dropZone.querySelector('h3').style.display = 'block';
      dropZone.querySelector('span').style.display = 'block';
    });
  }
</script>
</body>
</html>