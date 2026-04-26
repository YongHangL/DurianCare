<?php
session_start();
include "db.php";

$username = $_POST['username'];
$password = $_POST['password'];

$sql = "SELECT * FROM user WHERE UserName = ? AND UserPassword = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['user_id'] = $row['UserID'];
    $_SESSION['username'] = $row['UserName'];
    echo "success";
} else {
    echo "error";
}
?>