<?php
include 'db_connect.php';
include 'utils.php';

$user_id = $_POST['user_id'];
$total_games = $_POST['total_games'];
$val = $_POST['val'];
$ar = $_POST['ar'];
$dom = $_POST['dom'];

$response = ['success' => false, 'message' => ''];

try {
    // Export the data to REDCap
    $redcap_response = exportREDCapsurveydata($user_id, $API_TOKEN, $total_games, $val, $ar, $dom);
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