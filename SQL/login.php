<?php
include 'db_connect.php';

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
        $user_id = $result->fetch_assoc()['user_id']; // Assuming user_id is fetched from the 'user' table
        $current_ip = getClientIP();

        // Check if the current IP is already registered
        $sql = "SELECT ip_id FROM userip WHERE user_id = ? AND ip_address = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $current_ip);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // IP not registered, insert new IP
            $sql = "INSERT INTO userip (user_id, ip_address, timestamp) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $current_ip);
            if ($stmt->execute()) {
                echo " New IP address recorded";
            } else {
                echo " Failed to record new IP address: " . $stmt->error;
            }
        } else {
            echo " Known IP address used for login";
        }
    } else {
        echo "Invalid password";
    }
}

$stmt->close();
$conn->close();
?>