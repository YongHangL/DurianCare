<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - DurianCare AI</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { background: radial-gradient(circle at top left, #f8fafc 0%, #ecfdf5 100%); min-height: 100vh; color: #0f172a; }
.page-shell { max-width: 1180px; margin: 0 auto; padding: 0 20px; }
.register-wrapper { min-height: calc(100vh - 86px); display: grid; place-items: center; padding: 40px 0; }

.register-card {
    background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); padding: 40px;
    border-radius: 32px; box-shadow: 0 20px 40px rgba(0,0,0,0.06); width: 100%;
    max-width: 480px; border: 1px solid rgba(255,255,255,0.8); animation: slideUp 0.5s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.register-top { margin-bottom: 24px; text-align: center; }
.register-badge {
    width: 70px; height: 70px; margin: 0 auto 16px; border-radius: 20px; display: grid; place-items: center;
    background: linear-gradient(135deg, #10B981, #059669); color: white; font-size: 2rem; box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
}
.register-top h2 { color: #064e3b; margin-bottom: 8px; font-weight: 800; font-size: 1.8rem;}
.register-top p { color: #64748b; font-size: 0.95rem; line-height: 1.6; }

.input-row { display: flex; gap: 16px; margin-bottom: 16px;}
.input-row .input-group { flex: 1; margin-bottom: 0;}
.input-group { margin-bottom: 16px; }
.input-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #334155; font-size: 0.9rem;}
.input-group input {
    width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #e2e8f0;
    outline: none; font-size: 0.95rem; transition: all 0.3s ease; background: #f8fafc;
}
.input-group input:focus { border-color: #10B981; background: #fff; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1); }

.btn-register {
    width: 100%; padding: 15px; border-radius: 16px; border: none;
    background: linear-gradient(135deg, #10B981, #059669); color: white; font-weight: 700;
    cursor: pointer; transition: all 0.3s ease; font-size: 1.05rem; margin-top: 10px;
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
}
.btn-register:hover { transform: translateY(-2px); box-shadow: 0 14px 25px rgba(16, 185, 129, 0.3); }

.message { margin-top: 20px; text-align: center; font-size: 0.95rem; min-height: 24px;}
.error { color: #dc2626; background: #fef2f2; padding: 10px; border-radius: 10px; border: 1px solid #fecaca; display: block;}
.success { color: #059669; background: #ecfdf5; padding: 10px; border-radius: 10px; border: 1px solid #a7f3d0; display: block;}

.helper-link { margin-top: 20px; text-align: center; color: #64748b; font-size: 0.95rem;}
.helper-link a { color: #10B981; text-decoration: none; font-weight: 700; transition: 0.2s;}
.helper-link a:hover { color: #059669; text-decoration: underline; }

@media (max-width: 560px) {
    .register-card { padding: 30px 20px; border-radius: 24px; }
    .input-row { flex-direction: column; gap: 16px; }
}
</style>
</head>
<body>
<?php include 'header.php'; ?>
<div class="page-shell">
    <div class="register-wrapper">
        <div class="register-card">
            <div class="register-top">
                <div class="register-badge">🌱</div>
                <h2>Join DurianCare</h2>
                <p>Create an account to track your plant health.</p>
            </div>
            <form id="registerForm">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="username" placeholder="Choose a username" required>
                </div>
                <div class="input-group">
                    <label>Email Address</label>
                    <input type="email" id="email" placeholder="name@example.com" required>
                </div>
                <div class="input-row">
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" id="password" placeholder="••••••••" required>
                    </div>
                    <div class="input-group">
                        <label>Confirm</label>
                        <input type="password" id="confirmPassword" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn-register">Create Account</button>
            </form>
            <div id="message" class="message"></div>
            <div class="helper-link">
                Already have an account? <a href="loginPage.php">Sign In</a>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById("registerForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const btn = document.querySelector('.btn-register');
    const msg = document.getElementById("message");
    const u = document.getElementById("username").value.trim();
    const em = document.getElementById("email").value.trim();
    const p = document.getElementById("password").value;
    const cp = document.getElementById("confirmPassword").value;

    if (p !== cp) return msg.innerHTML = "<span class='error'>Passwords do not match.</span>";
    if (p.length < 6) return msg.innerHTML = "<span class='error'>Password > 5 characters.</span>";

    btn.textContent = 'Creating...';

    fetch("register.php", {
        method: "POST", headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "username="+encodeURIComponent(u)+"&email="+encodeURIComponent(em)+"&password="+encodeURIComponent(p)+"&confirmPassword="+encodeURIComponent(cp)
    }).then(r => r.text()).then(data => {
        data = data.trim();
        if (data === "success") {
            msg.innerHTML = "<span class='success'>Account created! Redirecting...</span>";
            setTimeout(() => window.location.href = "loginPage.php", 1200);
        } else {
            const errMap = {
                "username_exists": "Username taken.", "email_exists": "Email registered.",
                "invalid_email": "Invalid email.", "weak_password": "Password too weak."
            };
            msg.innerHTML = `<span class='error'>${errMap[data] || "Registration failed."}</span>`;
            btn.textContent = 'Create Account';
        }
    }).catch(() => { msg.innerHTML = "<span class='error'>Connection error.</span>"; btn.textContent = 'Create Account'; });
});
</script>
</body>
</html>