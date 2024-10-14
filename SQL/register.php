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

    // Create a new record in REDCap with the same value as the user_id
    $data = array(
        'token' => $API_TOKEN,
        'content' => 'record',
        'action' => 'import',
        'format' => 'csv',
        'type' => 'flat',
        'overwriteBehavior' => 'normal',
        'forceAutoNumber' => 'false',
        'data' => "record_id\n$user_id",
        'dateFormat' => 'DMY',
        'returnContent' => 'count',
        'returnFormat' => 'json'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://redcap.helsinki.fi/redcap/api/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    $output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($output, true);
    if ($response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $output);
    }

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