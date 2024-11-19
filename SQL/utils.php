<?php

/**
 * Returns the client's IP address.
 *
 * @return string The client's IP address.
 */
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

/**
 * Initializes a cURL session with common settings for making POST requests.
 *
 * @param string $url The URL to which the request is sent.
 * @param array $postData An associative array of data to be sent via POST.
 * @return resource The initialized cURL session handle.
 */
function initializeCurl_Post($url, $postData) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData, '', '&'));
    return $ch;
}

/**
 * Creates a new record in the REDCap database with the given user ID.
 * ds
 * @param int $user_id The user ID to be used as the record ID in REDCap.
 * @param string $API_TOKEN The API token for the REDCap project.
 * @throws Exception If an error occurs during the creation of the REDCap record.
 *
 * 
 * @see https://redcap.helsinki.fi/redcap/api/help/?content=default
 */
function createREDCapRecord($user_id, $API_TOKEN) {
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

    $ch = initializeCurl_Post('https://redcap.helsinki.fi/redcap/api/', $data);
    $output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($output, true);
    if ($response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $output);
    }
}

/**
 * Exports survey data to REDCap for a given user ID. The survey data includes valence, arousal, and dominance ratings.
 * 
 * @param int $user_id The user ID to be used as the record ID in REDCap.
 * @param string $API_TOKEN The API token for the REDCap project.
 * @param int $total_games The total number of games played by the user at the moment of submission.
 * @param int $val The value for the 'valence' field in the REDCap survey.
 * @param int $ar The value for the 'arousal' field in the REDCap survey.
 * @param int $dom The value for the 'dominance' field in the REDCap survey.
 * @throws Exception If an error occurs during the export of survey data to REDCap.
 * @return array The response from REDCap.
 *
 * 
 * @see https://redcap.helsinki.fi/redcap/api/help/?content=default
 */
function exportSAMdata($user_id, $API_TOKEN, $total_games ,$val, $ar, $dom) {
    $date = date('Y-m-d H:i:s');
    $data = array(
        'token' => $API_TOKEN,
        'content' => 'record',
        'action' => 'import',
        'format' => 'csv',
        'type' => 'flat',
        'overwriteBehavior' => 'normal',
        'forceAutoNumber' => 'false',
        'data' => "record_id,redcap_repeat_instrument,redcap_repeat_instance,date,total_games,val,ar,dom,sam_complete\n$user_id,sam,new,$date,$total_games,$val,$ar,$dom,2",
        'returnContent' => 'count',
        'returnFormat' => 'json'
    );
    $ch = initializeCurl_Post('https://redcap.helsinki.fi/redcap/api/', $data);
    $output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($output, true);
    if ($response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $output);
    }
    return $response;
}

/**
 * Exports flow data to REDCap for a given user ID.
 * 
 * @param int $user_id The user ID to be used as the record ID in REDCap.
 * @param string $API_TOKEN The API token for the REDCap project.
 * @param int $total_games The total number of games played by the user at the moment of submission.
 * @param int $fss_1 The value for the 'fss_n' field in the REDCap survey.
 * @throws Exception If an error occurs during the export of survey data to REDCap.
 * @return array The response from REDCap.
 */
function exportFlowdata($user_id, $API_TOKEN, $total_games ,$fss_1, $fss_2, $fss_3, $fss_4, $fss_5, $fss_6, $fss_7, $fss_8) {
    $date = date('Y-m-d H:i:s');
    $data = array(
        'token' => $API_TOKEN,
        'content' => 'record',
        'action' => 'import',
        'format' => 'csv',
        'type' => 'flat',
        'overwriteBehavior' => 'normal',
        'forceAutoNumber' => 'false',
        'data' => "record_id,redcap_repeat_instrument,redcap_repeat_instance,date_flow,total_games_flow,fss_1,fss_2,fss_3,fss_4,fss_5,fss_6,fss_7,fss_8,flow_complete\n$user_id,flow,new,$date,$total_games,$fss_1,$fss_2,$fss_3,$fss_4,$fss_5,$fss_6,$fss_7,$fss_8,2",
        'returnContent' => 'count',
        'returnFormat' => 'json'
    );
    $ch = initializeCurl_Post('https://redcap.helsinki.fi/redcap/api/', $data);
    $output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($output, true);
    if ($response['count'] !== 1) {
        throw new Exception("Error creating REDCap record: " . $output);
    }
    return $response;
}

/**
 * Validates the input data for game data.
 * 
 * @param array $data The game data to be validated.
 * @return array An array of error messages if any validation errors are found, or an empty array if no errors are found.
 */
function validategamedataInput($data) {
    $errors = [];
    
    // Numeric validations
    if (!is_numeric($data['game_duration']) || $data['game_duration'] < 0) {
        $errors[] = "Invalid game duration";
    }
    if (!is_int($data['session_number']) || $data['session_number'] < 0) {
        $errors[] = "Invalid session number";
    }
    if (!is_int($data['game_in_session']) || $data['game_in_session'] < 0) {
        $errors[] = "Invalid game in session";
    }
    if (!is_int($data['total_games']) || $data['total_games'] < 0) {
        $errors[] = "Invalid total games";
    }
    if (!is_int($data['level']) || $data['level'] < 0) {
        $errors[] = "Invalid level";
    }
    
    // User ID validation
    if (!is_numeric($data['user_id'])) {
        $errors[] = "Invalid user ID format";
    }
    
    // Date validation
    if (!strtotime($data['date_played'])) {
        $errors[] = "Invalid date format";
    }
    
    // Win validation
    if (!is_bool($data['win']) && !in_array($data['win'], [0, 1])) {
        $errors[] = "Invalid win value";
    }
    
    return $errors;
}

// Function to validate and sanitize input
function validateInput($post, $field, $type = 'string') {
    if (!isset($post[$field])) {
        throw new Exception("Missing required field: $field");
    }
    
    $value = $post[$field];
    
    switch($type) {
        case 'int':
            if (!is_numeric($value)) {
                throw new Exception("Invalid value for $field: must be numeric");
            }
            return (int)$value;
            
        case 'float':
            if (!is_numeric($value)) {
                throw new Exception("Invalid value for $field: must be numeric");
            }
            return (float)$value;
            
        default:
            return htmlspecialchars(trim($value));
    }
}

?>