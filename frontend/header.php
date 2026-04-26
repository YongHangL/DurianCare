<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
  .site-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid #e3eee6;
  }

  .header-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .navbar {
    min-height: 74px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
  }

  .brand-badge {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #2e8b57, #1f6f43);
    color: #fff;
    font-size: 1.1rem;
    box-shadow: 0 10px 24px rgba(31, 111, 67, 0.22);
  }

  .brand-text {
    display: flex;
    flex-direction: column;
    line-height: 1.1;
  }

  .brand-title {
    font-size: 1.08rem;
    font-weight: 800;
    color: #174b2f;
  }

  .brand-subtitle {
    font-size: 0.76rem;
    color: #6b8371;
    font-weight: 600;
    letter-spacing: 0.02em;
  }

  .nav-links {
    display: flex;
    align-items: center;
    gap: 10px;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .nav-links a {
    text-decoration: none;
    color: #33543b;
    font-weight: 700;
    padding: 10px 14px;
    border-radius: 12px;
    transition: 0.22s ease;
    display: inline-block;
  }

  .nav-links a:hover,
  .nav-links a.active {
    background: #edf7ef;
    color: #1f8a45;
  }

  .menu-toggle {
    display: none;
    border: none;
    background: #edf7ef;
    color: #1f6f43;
    width: 46px;
    height: 46px;
    border-radius: 12px;
    font-size: 1.2rem;
    cursor: pointer;
    font-weight: bold;
  }

  @media (max-width: 860px) {
    .navbar {
      flex-wrap: wrap;
      padding: 14px 0;
    }

    .menu-toggle {
      display: inline-grid;
      place-items: center;
    }

    .nav-links {
      width: 100%;
      display: none;
      flex-direction: column;
      align-items: stretch;
      gap: 8px;
      padding: 8px 0 4px;
    }

    .nav-links.show {
      display: flex;
    }

    .nav-links li {
      width: 100%;
    }

    .nav-links a {
      width: 100%;
      background: #f8fcf9;
      border: 1px solid #e2eee6;
    }
  }
</style>

<header class="site-header">
  <div class="header-shell">
    <nav class="navbar">
      <a href="index.php" class="brand">
        <div class="brand-badge">🌿</div>
        <div class="brand-text">
          <span class="brand-title">DurianCare AI</span>
          <span class="brand-subtitle">Leaf Disease Detection</span>
        </div>
      </a>

      <button class="menu-toggle" id="menuToggle">☰</button>

      <ul class="nav-links" id="navMenu">
        <?php if ($isLoggedIn): ?>
          <li><a href="index.php" class="<?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Home</a></li>
          <li><a href="dashboard.php" class="<?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
          <li><a href="index.php#detection">Detection</a></li>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="index.php" class="<?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Home</a></li>
          <li><a href="index.php#about">About</a></li>
          <li><a href="index.php#detection">Detection</a></li>
          <li><a href="loginPage.php" class="<?php echo ($currentPage === 'loginPage.php') ? 'active' : ''; ?>">Login</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </div>
</header>

<script>
  const menuToggle = document.getElementById("menuToggle");
  const navMenu = document.getElementById("navMenu");

  if (menuToggle && navMenu) {
    menuToggle.addEventListener("click", function () {
      navMenu.classList.toggle("show");
    });
  }
</script>