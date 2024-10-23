<?php
include 'db_connect.php';
include 'utils.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// SQL to count games
$sql_games = "SELECT total_games_played AS total_games FROM game WHERE user_id = ? ORDER BY game_id DESC LIMIT 1";
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

// Fetch survey status from REDCap API
// Prepare form to fetch consent and survey links from REDcap
$data = array(
    'token' => $API_TOKEN,
    'content' => 'record',
    'action' => 'export',
    'format' => 'json',
    'type' => 'flat',
    'records' => array($user_id),
    'fields' => array('consent_complete', 'survey_complete'),
    'rawOrLabel' => 'raw',
    'rawOrLabelHeaders' => 'raw',
    'exportCheckboxLabel' => 'false',
    'exportSurveyFields' => 'false',
    'exportDataAccessGroups' => 'false',
    'returnFormat' => 'json'
);

// Initialize cURL sessiond
$ch = initializeCurl_Post('https://redcap.helsinki.fi/redcap/api/', $data);
$output = curl_exec($ch);

// Decode the output from REDCap API
$survey_status = json_decode($output, true);

// Extract the consent and survey completion status from the decoded output
$consent_done = $survey_status[0]['consent_complete'] ?? 0;
$survey_done = $survey_status[0]['survey_complete'] ?? 0;

// Update the redcapdata table in MySQL
$sql_update_redcap = "UPDATE redcapdata SET consent_done = ?, survey_done = ? WHERE record_id = ?";
$stmt_update_redcap = $conn->prepare($sql_update_redcap);
$stmt_update_redcap->bind_param("iii", $consent_done, $survey_done, $user_id);
$stmt_update_redcap->execute();

// Fetch survey links from REDCap
$consent_link = '';
$survey_link = '';

// Prepare form to fetch consent and survey links from REDcap
$data_link_consent = array(
    'token' => $API_TOKEN,
    'content' => 'surveyLink',
    'format' => 'json',
    'instrument' => 'consent',
    'event' => '',
    'record' => $user_id,
    'returnFormat' => 'json'
);

$data_link_survey = array(
    'token' => $API_TOKEN,
    'content' => 'surveyLink',
    'format' => 'json',
    'instrument' => 'survey',
    'event' => '',
    'record' => $user_id,
    'returnFormat' => 'json'
);

// Execute the consent link request
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_link_consent, '', '&'));
$consent_link = curl_exec($ch);

// Execute the survey link request
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_link_survey, '', '&'));
$survey_link = curl_exec($ch);

// Close the cURL connection
curl_close($ch);



// Output data in JSON format
$response = array(
    'total_games' => (int)$games['total_games'], // Ensure the count is returned as integer
    'last_session' => (int)$sessions['last_session'],
    'consent_done' => $consent_done,
    'survey_done' => $survey_done,
    'consent_link' => $consent_link,
    'survey_link' => $survey_link
);
echo json_encode($response, JSON_UNESCAPED_SLASHES);

// Close connection
$conn->close();
?>