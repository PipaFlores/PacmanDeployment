<?php
include 'db_connect.php'; // Assume you have saved the connection code in 'db_connect.php'
include 'utils.php'; // REDCAP API functions.

$username = $_POST['username'];
$password = $_POST['password'];
$email = $_POST['email'];

$response = ['success' => false, 'message' => '', 'user_id' => 0];

// Hash the password using bcrypt algorithm
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Check for empty fields
    if (empty($username) || empty($password) || empty($email)) {
        throw new Exception("Please fill in all fields");
    }

    // Check if username exists
    $sql = "SELECT username FROM user WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception("Username already exists");
    }

    // Insert new user into SQL database
    $sql = "INSERT INTO user (username, password, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $hashedPassword, $email);
    if (!$stmt->execute()) {
        throw new Exception("Error: " . $stmt->error);
    }

    $user_id = $conn->insert_id;
    $ip_address = getClientIP();

    // Insert IP address into SQL database
    $sql = "INSERT INTO userip (user_id, ip_address, timestamp) VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $ip_address);
    if (!$stmt->execute()) {
        throw new Exception("Error recording IP address: " . $stmt->error);
    }

    // Initialize redcapdata row in SQL database
    $sql = "INSERT INTO redcapdata (record_id, consent_done, survey_done, sam_submissions, flow_submissions) VALUES (?, 2, 0, 0, 0)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error initializing redcapdata: " . $stmt->error);
    }

    CreateREDCapRecord($user_id, $API_TOKEN);
    
    // Commit transaction
    $conn->commit();
    $response['success'] = true;
    $response['user_id'] = $user_id;
    $response['message'] = "User registered successfully, IP address recorded, redcapdata row initialized, and REDCap record created";
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$stmt->close();
$conn->close();
?>
