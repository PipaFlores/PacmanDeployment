<?php
include 'db_connect.php'; // Assume you have saved the connection code in 'db_connect.php'
function getClientIP() {
    // Check for HTTP headers set by proxies
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // The first IP in the list is usually the original client
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } else {
        // Fall back to REMOTE_ADDR
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return trim($ip);
}

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

// check that there are no empty fields
if (empty($username) || empty($password) || empty($email)) {
    echo "Please fill in all fields";
    $stmt->close();
    $conn->close();
    exit;
}

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
    $user_id = $conn->insert_id; // Fetch the last inserted id to link with the IP address
    $ip_address = getClientIP(); // Get the IP address of the user

    // Insert IP address into user_ip table
    $sql = "INSERT INTO userip (user_id, ip_address, timestamp) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $ip_address);
    if ($stmt->execute()) {
        echo " and IP address recorded";
    } else {
        echo " but failed to record IP address: " . $stmt->error;
    }
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>