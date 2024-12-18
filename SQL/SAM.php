<?php
include 'db_connect.php';
include 'utils.php';


$response = ['success' => false, 'message' => ''];

try {
    // Check if all required fields exist
    if (!isset($_POST['user_id']) || !isset($_POST['total_games']) || 
    !isset($_POST['val']) || !isset($_POST['ar']) || !isset($_POST['dom'])) {
        throw new Exception('Missing required parameters');
    }
    // Validate and sanitize the input
    $user_id = validateInput($_POST, 'user_id', 'int');
    $total_games = validateInput($_POST, 'total_games', 'int');
    $val = validateInput($_POST, 'val', 'int');
    $ar = validateInput($_POST, 'ar', 'int');
    $dom = validateInput($_POST, 'dom', 'int');
} catch (Exception $e) {
    $response['message'] = "An error occurred while validating the sent data: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

try {
    // Export the data to REDCap
    $redcap_response = exportSAMdata($user_id, $API_TOKEN, $total_games, $val, $ar, $dom);
    if ($redcap_response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $redcap_response["error"]);
    }
    // Update the database with the number of surveys completed
    $stmt = $conn->prepare("UPDATE redcapdata SET sam_submissions = sam_submissions + 1 WHERE record_id = ?");
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Error updating redcapdata in database: " . $stmt->error);
    }
    $response['success'] = true;
    $response['message'] = "Survey data exported to REDCap and database updated successfully";
    $stmt->close();

    echo json_encode($response);
} catch (Exception $e) {
    // Handle the exceptionds
    error_log("Error in exportREDCapsurveydata: " . $e->getMessage());
    $response['message'] = "An error occurred while processing the request: " . $e->getMessage();
    echo json_encode($response);
}
$conn->close();