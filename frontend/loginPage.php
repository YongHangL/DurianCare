<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - DurianCare AI</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    background: radial-gradient(circle at top left, #f8fafc 0%, #ecfdf5 100%);
    min-height: 100vh;
    color: #0f172a;
}
.page-shell { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.login-wrapper { min-height: calc(100vh - 86px); display: grid; place-items: center; padding: 40px 0; }

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 40px;
    border-radius: 32px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
    width: 100%;
    max-width: 440px;
    border: 1px solid rgba(255,255,255,0.8);
    animation: slideUp 0.5s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.login-top { margin-bottom: 30px; text-align: center; }
.login-badge {
    width: 70px; height: 70px; margin: 0 auto 16px; border-radius: 20px; display: grid; place-items: center;
    background: linear-gradient(135deg, #10B981, #059669); color: white; font-size: 2rem;
    box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
}
.login-top h2 { color: #064e3b; margin-bottom: 8px; font-weight: 800; font-size: 1.8rem;}
.login-top p { color: #64748b; font-size: 0.95rem; line-height: 1.6; }

.input-group { margin-bottom: 20px; }
.input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #334155; font-size: 0.95rem;}
.input-group input {
    width: 100%; padding: 14px 16px; border-radius: 14px; border: 2px solid #e2e8f0;
    outline: none; font-size: 1rem; transition: all 0.3s ease; background: #f8fafc;
}
.input-group input:focus { border-color: #10B981; background: #fff; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }

.btn-login {
    width: 100%; padding: 15px; border-radius: 16px; border: none;
    background: linear-gradient(135deg, #10B981, #059669); color: white; font-weight: 700;
    cursor: pointer; transition: all 0.3s ease; font-size: 1.05rem; margin-top: 10px;
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
}
.btn-login:hover { transform: translateY(-2px); box-shadow: 0 14px 25px rgba(16, 185, 129, 0.3); }

.message { margin-top: 20px; text-align: center; font-size: 0.95rem; min-height: 24px; transition: 0.3s;}
.error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 10px; border: 1px solid #fecaca; display: block;}
.success { color: #059669; background: #ecfdf5; padding: 10px; border-radius: 10px; border: 1px solid #a7f3d0; display: block;}

.helper-link { margin-top: 24px; text-align: center; color: #64748b; font-size: 0.95rem;}
.helper-link a { color: #10B981; text-decoration: none; font-weight: 700; transition: 0.2s;}
.helper-link a:hover { color: #059669; text-decoration: underline; }

@media (max-width: 560px) {
    .login-card { padding: 30px 20px; border-radius: 24px; }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="page-shell">
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-top">
                <div class="login-badge">🔐</div>
                <h2>Welcome Back</h2>
                <p>Sign in to continue analyzing your durian leaves.</p>
            </div>
            <form id="loginForm">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="username" placeholder="Enter username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            <div id="message" class="message"></div>
            <div class="helper-link">
                <p>New here? <a href="registerPage.php">Create an account</a></p>
                <p style="margin-top: 12px;"><a href="index.php" style="color: #64748b;">← Back to Home</a></p>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const btn = document.querySelector('.btn-login');
    btn.textContent = 'Signing in...';

    fetch("login.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "username=" + encodeURIComponent(document.getElementById("username").value) + "&password=" + encodeURIComponent(document.getElementById("password").value)
    })
    .then(r => r.text())
    .then(data => {
        const msg = document.getElementById("message");
        if (data.trim() === "success") {
            msg.innerHTML = "<span class='success'>Success! Redirecting...</span>";
            setTimeout(() => window.location.href = "index.php", 1000);
        } else {
            msg.innerHTML = "<span class='error'>Invalid username or password</span>";
            btn.textContent = 'Sign In';
        }
    }).catch(() => {
        document.getElementById("message").innerHTML = "<span class='error'>Connection error</span>";
        btn.textContent = 'Sign In';
    });
});
</script>
</body>
</html>