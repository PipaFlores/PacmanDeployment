<?php
include 'db_connect.php';

$username = $_POST['username'];
$password = $_POST['password']; // The password attempt from the user

// Prepare a statement to prevent SQL injection
$stmt = $conn->prepare("SELECT password FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Username does not exist";
} else {
    // Fetch hashed password from the database
    $row = $result->fetch_assoc();
    $hashed_password = $row['password'];

    // Verify the hashed password
    if (password_verify($password, $hashed_password)) {
        echo "Login successful";
    } else {
        echo "Invalid password";
    }
}

$stmt->close();
$conn->close();
?>