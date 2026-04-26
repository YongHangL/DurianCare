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
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
    }

    html {
      scroll-behavior: smooth;
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

    .hero {
      display: grid;
      grid-template-columns: 1.08fr 0.92fr;
      gap: 34px;
      align-items: center;
      padding: 42px 0 72px;
    }

    .hero-text h1 {
      font-size: 3rem;
      line-height: 1.12;
      margin-bottom: 16px;
      color: #174b2f;
    }

    .hero-text p {
      font-size: 1.02rem;
      line-height: 1.8;
      color: #4c6653;
      margin-bottom: 24px;
      max-width: 620px;
    }

    .hero-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 26px;
    }

    .badge {
      background: #ffffff;
      border: 1px solid #d7eadc;
      color: #1d5d34;
      padding: 10px 15px;
      border-radius: 999px;
      font-size: 0.92rem;
      font-weight: 700;
      box-shadow: 0 10px 22px rgba(30, 90, 50, 0.06);
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-top: 10px;
    }

    .hero-link {
      text-decoration: none;
      padding: 13px 20px;
      border-radius: 14px;
      font-weight: 700;
      display: inline-block;
      transition: 0.22s ease;
    }

    .hero-link.primary {
      background: linear-gradient(135deg, #2f9b53, #207d44);
      color: #fff;
      box-shadow: 0 14px 28px rgba(32, 125, 68, 0.2);
    }

    .hero-link.primary:hover {
      transform: translateY(-1px);
    }

    .hero-link.secondary {
      background: #f6fbf7;
      color: #24583a;
      border: 1px solid #d8e9dc;
    }

    .upload-card {
      background: #ffffff;
      border-radius: 28px;
      padding: 30px;
      box-shadow: 0 22px 50px rgba(16, 70, 35, 0.12);
      border: 1px solid #e2f0e6;
    }

    .upload-card h2 {
      font-size: 1.5rem;
      margin-bottom: 10px;
      color: #174b2f;
    }

    .upload-card p {
      color: #58725f;
      margin-bottom: 20px;
      line-height: 1.7;
    }

    .upload-box {
      border: 2px dashed #95c8a3;
      border-radius: 22px;
      padding: 36px 20px;
      text-align: center;
      background: #f8fff9;
      transition: 0.25s ease;
    }

    .upload-box:hover {
      background: #f2fcf4;
      border-color: #5ea875;
    }

    .upload-icon {
      font-size: 2.6rem;
      margin-bottom: 12px;
    }

    .upload-box h3 {
      margin-bottom: 8px;
      color: #1b5531;
    }

    .upload-box span {
      display: block;
      font-size: 0.92rem;
      color: #6a856f;
      margin-bottom: 16px;
    }

    input[type="file"] {
      display: none;
    }

    .file-btn {
      display: inline-block;
      background: #1f8a45;
      color: white;
      padding: 12px 22px;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 700;
      text-decoration: none;
      transition: 0.2s ease;
      border: none;
    }

    .file-btn:hover {
      background: #166b34;
    }

    .preview {
      margin-top: 20px;
      display: none;
      text-align: center;
    }

    .preview img {
      max-width: 100%;
      max-height: 240px;
      border-radius: 18px;
      border: 1px solid #dcebdd;
      box-shadow: 0 12px 26px rgba(20, 72, 38, 0.1);
      background: #fff;
    }

    .preview p {
      margin-top: 12px;
      color: #3f5f47;
      font-weight: 700;
      word-break: break-word;
    }

    .actions {
      display: flex;
      gap: 14px;
      margin-top: 22px;
      flex-wrap: wrap;
    }

    .btn-primary,
    .btn-secondary {
      border: none;
      border-radius: 12px;
      padding: 13px 22px;
      font-weight: 700;
      cursor: pointer;
      transition: 0.2s ease;
      text-decoration: none;
      display: inline-block;
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

    .info-section {
      padding-bottom: 76px;
    }

    .section-title {
      text-align: center;
      margin-bottom: 28px;
    }

    .section-title h2 {
      font-size: 2rem;
      color: #174b2f;
      margin-bottom: 10px;
    }

    .section-title p {
      color: #54705b;
      max-width: 700px;
      margin: 0 auto;
      line-height: 1.7;
    }

    .cards {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 22px;
    }

    .card {
      background: white;
      border-radius: 22px;
      padding: 24px;
      border: 1px solid #e4efe7;
      box-shadow: 0 12px 28px rgba(24, 73, 40, 0.08);
    }

    .card h3 {
      margin-bottom: 10px;
      color: #1b5531;
    }

    .card p {
      color: #5a7561;
      line-height: 1.7;
      font-size: 0.96rem;
    }

    .footer {
      text-align: center;
      padding: 14px 0 34px;
      color: #6e8774;
      font-size: 0.92rem;
    }

    @media (max-width: 960px) {
      .hero {
        grid-template-columns: 1fr;
        padding-top: 28px;
      }

      .hero-text h1 {
        font-size: 2.35rem;
      }

      .cards {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 560px) {
      .page-shell {
        padding: 0 14px;
      }

      .hero-text h1 {
        font-size: 1.95rem;
      }

      .upload-card {
        padding: 22px 18px;
        border-radius: 22px;
      }

      .actions,
      .hero-actions {
        flex-direction: column;
      }

      .btn-primary,
      .btn-secondary,
      .hero-link {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-shell">
  <section class="hero" id="detection">
    <div class="hero-text">
      <h1>Durian Leaf Disease Detection System</h1>
      <p>
        Upload a durian leaf image to identify possible disease categories. This system helps users get a quick prediction result together with confidence and treatment recommendation in a clean and easy interface.
      </p>

      <div class="hero-badges">
        <div class="badge">Algal Leaf Spot</div>
        <div class="badge">Leaf Blight</div>
        <div class="badge">Phomopsis</div>
        <div class="badge">Healthy Leaf</div>
      </div>

      <div class="hero-actions">
        <a href="#detection" class="hero-link primary">Start Detection</a>
        <a href="#about" class="hero-link secondary">Learn More</a>
      </div>
    </div>

    <div class="upload-card">
      <h2>Upload Leaf Image</h2>
      <p>Select a clear image of a durian leaf for disease analysis.</p>

      <form action="<?php echo $isLoggedIn ? 'upload.php' : 'loginPage.php'; ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="upload-box">
          <div class="upload-icon">📷</div>
          <h3>Choose an image file</h3>
          <span>Supported: JPG, PNG, JPEG</span>

          <?php if ($isLoggedIn): ?>
            <label for="imageUpload" class="file-btn">Browse Image</label>
            <input type="file" id="imageUpload" name="leafImage" accept="image/*" required />
          <?php else: ?>
            <a href="loginPage.php" class="file-btn">Login to Upload</a>
          <?php endif; ?>

          <div class="preview" id="previewBox">
            <img id="previewImage" src="" alt="Preview" />
            <p id="fileName"></p>
          </div>
        </div>

        <div class="actions">
          <?php if ($isLoggedIn): ?>
            <button type="submit" class="btn-primary">Upload Image</button>
          <?php else: ?>
            <a href="loginPage.php" class="btn-primary">Go to Login</a>
          <?php endif; ?>
          <button type="button" class="btn-secondary" id="resetBtn">Reset</button>
        </div>
      </form>
    </div>
  </section>

  <section class="info-section" id="about">
    <div class="section-title">
      <h2>How It Works</h2>
      <p>
        The system provides a simple workflow for disease detection, from image upload to prediction result and treatment recommendation.
      </p>
    </div>

    <div class="cards">
      <div class="card">
        <h3>1. Upload Image</h3>
        <p>User selects a durian leaf image from mobile phone or desktop device.</p>
      </div>
      <div class="card">
        <h3>2. Disease Analysis</h3>
        <p>The uploaded image is processed by the AI model to classify the leaf condition.</p>
      </div>
      <div class="card">
        <h3>3. Show Result</h3>
        <p>The system displays the prediction, confidence score, and treatment recommendation.</p>
      </div>
    </div>
  </section>

  <div class="footer">
    Final Year Project — Durian Leaf Disease Detection System
  </div>
</div>

<script>
  const imageUpload = document.getElementById('imageUpload');
  const previewBox = document.getElementById('previewBox');
  const previewImage = document.getElementById('previewImage');
  const fileName = document.getElementById('fileName');
  const resetButton = document.getElementById('resetBtn');

  if (imageUpload) {
    imageUpload.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          previewImage.src = e.target.result;
          previewBox.style.display = 'block';
          fileName.textContent = file.name;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  if (resetButton) {
    resetButton.addEventListener('click', function () {
      if (imageUpload) {
        imageUpload.value = '';
      }
      previewImage.src = '';
      fileName.textContent = '';
      previewBox.style.display = 'none';
    });
  }
</script>

</body>
</html>