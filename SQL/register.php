<?php
include 'db_connect.php'; // Assume you have saved the connection code in 'db_connect.php'
include 'utils.php'; // REDCAP API functions.

$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];

// Hash the password using bcrypt algorithm
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Check if username exists
    $sql = "SELECT username FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // check that there are no empty fields
    if (empty($username) || empty($password) || empty($email)) {
        throw new Exception("Please fill in all fields");
    }

    if ($result->num_rows > 0) {
        throw new Exception("Username already exists");
    }

    // Insert new user
    $sql = "INSERT INTO user (username, password, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    if (!$stmt->execute()) {
        throw new Exception("Error: " . $stmt->error);
    }

    $user_id = $conn->insert_id; // Fetch the last inserted id to link with the IP address
    $ip_address = getClientIP(); // Get the IP address of the user

    // Insert IP address into user_ip table
    $sql = "INSERT INTO userip (user_id, ip_address, timestamp) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $ip_address);
    if (!$stmt->execute()) {
        throw new Exception("Error recording IP address: " . $stmt->error);
    }

    // Initialize redcapdata row
    $sql = "INSERT INTO redcapdata (record_id, consent_done, survey_done) VALUES (?, 0, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error initializing redcapdata: " . $stmt->error);
    }

    CreateREDCapRecord($user_id, $API_TOKEN); // Create a REDCap record with the same user_id as record_id
    // Commit transaction
    $conn->commit();
    echo "User registered successfully, IP address recorded, redcapdata row initialized, and REDCap record created";
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    echo $e->getMessage();
}

$stmt->close();
$conn->close();
?>