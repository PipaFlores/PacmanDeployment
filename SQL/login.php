<?php
include 'db_connect.php';
include 'utils.php';


$username = $_POST['username'];
$password = $_POST['password'];
$response = ['success' => false, 'message' => '', 'user_id' => null];

// Prepare a statement to prevent SQL injection
$stmt = $conn->prepare("SELECT user_id, password FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = "Username does not exist";
} else {
    $row = $result->fetch_assoc();
    if ($row === null) {
        $response['message'] = "Error fetching user data.";
    } else {
        $hashed_password = $row['password'];
        $user_id = $row['user_id'];

        if ($user_id === null) {
            $response['message'] = "Error: User ID is null.";
        } else {
            if (password_verify($password, $hashed_password)) {
                $response['success'] = true; # Login successful
                $response['user_id'] = $user_id;
                $current_ip = getClientIP();

                $stmt = $conn->prepare("SELECT ip_id FROM userip WHERE user_id = ? AND ip_address = ?");
                $stmt->bind_param("is", $user_id, $current_ip);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    $stmt = $conn->prepare("INSERT INTO userip (user_id, ip_address, timestamp) VALUES (?, ?, NOW())");
                    $stmt->bind_param("is", $user_id, $current_ip);
                    if (!$stmt->execute()) {
                        $response['message'] .= " Failed to record new IP address: " . $stmt->error;
                    }
                } else {
                    $response['message'] .= " Known IP address used for login";
                }
            } else {
                $response['message'] = "Invalid password";
            }
        }
    }
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>
