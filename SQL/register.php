<?php
include 'db_connect.php'; // Assume you have saved the connection code in 'db_connect.php'

$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];

// Hash the password using bcrypt algorithm
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if username exists
$sql = "SELECT username FROM user WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    echo "Username already exists";
    $stmt->close();
    $conn->close();
    exit;
}

// Insert new user
$sql = "INSERT INTO user (username, password, email) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $username, $hashedPassword, $email);
if ($stmt->execute()) {
    echo "User registered successfully";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>