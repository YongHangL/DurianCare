<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - DurianCare AI</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    background:
      radial-gradient(circle at top left, #f8fff8 0%, #eef8f0 35%, #e7f7eb 100%);
    min-height: 100vh;
    color: #1f2d1f;
}

.page-shell {
    max-width: 1180px;
    margin: 0 auto;
    padding: 0 20px;
}

.login-wrapper {
    min-height: calc(100vh - 86px);
    display: grid;
    place-items: center;
    padding: 36px 0 60px;
}

.login-card {
    background: white;
    padding: 34px;
    border-radius: 24px;
    box-shadow: 0 22px 46px rgba(16, 70, 35, 0.14);
    width: 100%;
    max-width: 430px;
    border: 1px solid #e2f0e6;
}

.login-top {
    margin-bottom: 24px;
    text-align: center;
}

.login-badge {
    width: 64px;
    height: 64px;
    margin: 0 auto 14px;
    border-radius: 18px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #2f9b53, #207d44);
    color: white;
    font-size: 1.6rem;
    box-shadow: 0 14px 28px rgba(32, 125, 68, 0.2);
}

.login-top h2 {
    color: #1c6b39;
    margin-bottom: 8px;
}

.login-top p {
    color: #5c7662;
    font-size: 0.96rem;
    line-height: 1.6;
}

.input-group {
    margin-bottom: 18px;
}

.input-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 700;
    color: #33543b;
}

.input-group input {
    width: 100%;
    padding: 13px 14px;
    border-radius: 12px;
    border: 1px solid #cfe3d5;
    outline: none;
    font-size: 0.98rem;
    transition: 0.2s ease;
}

.input-group input:focus {
    border-color: #56a36f;
    box-shadow: 0 0 0 4px rgba(86, 163, 111, 0.12);
}

.btn-login {
    width: 100%;
    padding: 13px;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #3ba55d, #2d8c4d);
    color: white;
    font-weight: bold;
    cursor: pointer;
    transition: 0.25s ease;
    font-size: 1rem;
    margin-top: 4px;
}

.btn-login:hover {
    transform: translateY(-1px);
    background: linear-gradient(135deg, #2d8c4d, #246d3d);
}

.message {
    margin-top: 16px;
    text-align: center;
    font-size: 14px;
    min-height: 22px;
}

.error {
    color: #c0392b;
    font-weight: 700;
}

.success {
    color: #1f8a45;
    font-weight: 700;
}

.helper-link {
    margin-top: 20px;
    text-align: center;
}

.helper-link a {
    color: #1f8a45;
    text-decoration: none;
    font-weight: 700;
}

.helper-link a:hover {
    text-decoration: underline;
}

@media (max-width: 560px) {
    .page-shell {
        padding: 0 14px;
    }

    .login-card {
        padding: 24px 18px;
        border-radius: 20px;
    }

    .login-wrapper {
        padding: 24px 0 40px;
    }
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
                <h2>Login</h2>
                <p>Sign in to upload leaf images and view your previous prediction history.</p>
            </div>

            <form id="loginForm">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" id="username" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" id="password" required>
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>

            <div id="message" class="message"></div>

            <div class="helper-link">
                <a href="index.php">Back to Home</a>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const username = document.getElementById("username").value;
    const password = document.getElementById("password").value;
    const messageBox = document.getElementById("message");

    fetch("login.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "username=" + encodeURIComponent(username) + "&password=" + encodeURIComponent(password)
    })
    .then(response => response.text())
    .then(data => {
        if (data.trim() === "success") {
            messageBox.innerHTML = "<span class='success'>Login successful! Redirecting...</span>";
            setTimeout(() => {
                window.location.href = "index.php";
            }, 1000);
        } else {
            messageBox.innerHTML = "<span class='error'>Invalid username or password</span>";
        }
    })
    .catch(error => {
        messageBox.innerHTML = "<span class='error'>Error occurred</span>";
    });
});
</script>

</body>
</html>