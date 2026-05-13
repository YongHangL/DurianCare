<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "invalid_request";
    exit();
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';

if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
    echo "empty";
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "invalid_email";
    exit();
}

if ($password !== $confirmPassword) {
    echo "password_mismatch";
    exit();
}

if (strlen($password) < 6) {
    echo "weak_password";
    exit();
}

// Check duplicate username
$sql = "SELECT UserID FROM user WHERE UserName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "username_exists";
    exit();
}

// Check duplicate email
$sql = "SELECT UserID FROM user WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "email_exists";
    exit();
}

// Hash password before saving
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new user
$sql = "INSERT INTO user (UserName, email, UserPassword) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $email, $hashedPassword);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error";
}

$stmt->close();
$conn->close();
?>