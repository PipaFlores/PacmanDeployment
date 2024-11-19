<?php
include 'db_connect.php';
include 'utils.php';


$response = ['success' => false, 'message' => ''];

try {
    // Check if all required fields exist
    if (!isset($_POST['user_id']) || !isset($_POST['total_games']) || 
    !isset($_POST['fss_1']) || !isset($_POST['fss_2']) || !isset($_POST['fss_3']) || 
    !isset($_POST['fss_4']) || !isset($_POST['fss_5']) || !isset($_POST['fss_6']) || 
    !isset($_POST['fss_7']) || !isset($_POST['fss_8'])) {
        throw new Exception('Missing required parameters');
    }
    // Validate and sanitize the input
    $user_id = validateInput($_POST, 'user_id', 'int');
    $total_games = validateInput($_POST, 'total_games', 'int');
    $fss_1 = validateInput($_POST, 'fss_1', 'int');
    $fss_2 = validateInput($_POST, 'fss_2', 'int');
    $fss_3 = validateInput($_POST, 'fss_3', 'int');
    $fss_4 = validateInput($_POST, 'fss_4', 'int');
    $fss_5 = validateInput($_POST, 'fss_5', 'int');
    $fss_6 = validateInput($_POST, 'fss_6', 'int');
    $fss_7 = validateInput($_POST, 'fss_7', 'int');
    $fss_8 = validateInput($_POST, 'fss_8', 'int');
} catch (Exception $e) {
    $response['message'] = "An error occurred while validating the sent data: " . $e->getMessage();
    echo json_encode($response);
    exit;
}

try {
    // Export the data to REDCap
    $redcap_response = exportFlowdata($user_id, $API_TOKEN, $total_games, $fss_1, $fss_2, $fss_3, $fss_4, $fss_5, $fss_6, $fss_7, $fss_8);
    if ($redcap_response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $redcap_response["error"]);
    }
    // Update the database with the number of surveys completed
    $stmt = $conn->prepare("UPDATE redcapdata SET flow_submissions = flow_submissions + 1 WHERE record_id = ?");
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