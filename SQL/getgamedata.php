<?php
include 'db_connect.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// SQL to count games
$sql_games = "SELECT COUNT(*) AS total_games FROM game WHERE user_id = ?";
$stmt_games = $conn->prepare($sql_games);
$stmt_games->bind_param("i", $user_id);
$stmt_games->execute();
$result_games = $stmt_games->get_result();
$games = $result_games->fetch_assoc();

// SQL to get the last session number, default to 0 if no sessions
$sql_sessions = "SELECT COALESCE(MAX(session_number), 0) AS last_session FROM game WHERE user_id = ?";
$stmt_sessions = $conn->prepare($sql_sessions);
$stmt_sessions->bind_param("i", $user_id);
$stmt_sessions->execute();
$result_sessions = $stmt_sessions->get_result();
$sessions = $result_sessions->fetch_assoc();

// Output data in JSON format
$response = array(
    'total_games' => (int)$games['total_games'], // Ensure the count is returned as integer
    'last_session' => (int)$sessions['last_session']
);
echo json_encode($response);

// Close connection
$conn->close();
?>