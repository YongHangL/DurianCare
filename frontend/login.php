<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo "invalid_request";
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    echo "empty";
    exit();
}

$sql = "SELECT * FROM user WHERE UserName = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $storedPassword = $row['UserPassword'];

    $isHashedPassword = password_get_info($storedPassword)['algo'] !== 0;

    if (
        ($isHashedPassword && password_verify($password, $storedPassword)) ||
        (!$isHashedPassword && hash_equals($storedPassword, $password))
    ) {
        $_SESSION['user_id'] = $row['UserID'];
        $_SESSION['username'] = $row['UserName'];

        // If old account still uses plain password, convert it to hashed password
        if (!$isHashedPassword) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);

            $updateSql = "UPDATE user SET UserPassword = ? WHERE UserID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("si", $newHash, $row['UserID']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        echo "success";
    } else {
        echo "error";
    }

} else {
    echo "error";
}

$stmt->close();
$conn->close();
?>