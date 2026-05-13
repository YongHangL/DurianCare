<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION['user_id']);
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root {
    --primary: #10B981;
    --primary-dark: #059669;
    --bg-color: #f0fdf4;
    --text-main: #064e3b;
    --text-muted: #475569;
    --card-bg: rgba(255, 255, 255, 0.95);
  }

  body { font-family: 'Poppins', sans-serif; }

  .site-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(16, 185, 129, 0.1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
  }

  .header-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 20px;
  }

  .navbar {
    min-height: 76px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    position: relative; /* Essential for mobile dropdown positioning */
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 14px;
    text-decoration: none;
    transition: transform 0.2s ease;
  }

  .brand:hover { transform: scale(1.02); }

  .brand-badge {
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: #fff;
    font-size: 1.2rem;
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
  }

  .brand-text { display: flex; flex-direction: column; line-height: 1.2; }
  .brand-title { font-size: 1.15rem; font-weight: 800; color: var(--text-main); }
  .brand-subtitle { font-size: 0.75rem; color: var(--text-muted); font-weight: 600; letter-spacing: 0.03em; text-transform: uppercase;}

  .nav-links {
    display: flex;
    align-items: center;
    gap: 8px;
    list-style: none;
    margin: 0;
    padding: 0;
  }

  .nav-links li {
    display: inline-block;
  }

  .nav-links a {
    display: inline-block;
    text-decoration: none;
    color: var(--text-main);
    font-weight: 600;
    font-size: 0.95rem;
    padding: 10px 18px;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
  }

  .nav-links a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 3px;
    background: var(--primary);
    transition: all 0.3s ease;
    border-radius: 3px 3px 0 0;
    transform: translateX(-50%);
  }

  .nav-links a:hover::after, .nav-links a.active::after { width: 40%; }
  .nav-links a:hover { background: #ecfdf5; color: var(--primary-dark); transform: translateY(-2px); }
  .nav-links a.active { color: var(--primary-dark); background: #ecfdf5;}

  .menu-toggle {
    display: none;
    border: none;
    background: #ecfdf5;
    color: var(--primary-dark);
    width: 46px;
    height: 46px;
    border-radius: 14px;
    font-size: 1.4rem;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .menu-toggle:active { transform: scale(0.95); }

  /* --- NEW MOBILE MENU REDESIGN --- */
  @media (max-width: 860px) {
    .navbar { flex-wrap: wrap; padding: 14px 0; }
    .menu-toggle { display: inline-grid; place-items: center; }

    .nav-links {
      position: absolute;
      top: 100%;
      left: 0;
      right: 0;
      width: 100%;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(16, 185, 129, 0.15);
      display: none;
      flex-direction: column;
      gap: 4px;
      padding: 16px;
      margin-top: 10px;
      animation: slideDown 0.3s ease forwards;
    }

    .nav-links.show { display: flex; }

    .nav-links li {
      width: 100%;
      display: block;
    }

    .nav-links a {
      width: 100%;
      display: block; /* Fixes the overlapping issue */
      background: transparent;
      border: none;
      text-align: left; /* Modern mobile alignment */
      padding: 14px 20px;
      font-size: 1.05rem;
      border-radius: 14px;
    }

    .nav-links a:hover, .nav-links a.active {
      transform: none; /* Prevent jumping on mobile */
      background: #ecfdf5;
      color: var(--primary-dark);
    }

    .nav-links a::after { display: none; /* Hide desktop underline */ }
  }

  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>

<header class="site-header">
  <div class="header-shell">
    <nav class="navbar">
      <a href="index.php" class="brand">
        <div class="brand-badge">🌿</div>
        <div class="brand-text">
          <span class="brand-title">DurianCare AI</span>
          <span class="brand-subtitle">Disease Detection</span>
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
          <li><a href="registerPage.php" class="<?php echo ($currentPage === 'registerPage.php') ? 'active' : ''; ?>">Register</a></li>
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
    menuToggle.addEventListener("click", () => {
      navMenu.classList.toggle("show");
      menuToggle.textContent = navMenu.classList.contains("show") ? "✕" : "☰";
    });
  }
</script>