<?php
// Include the database connection file
include 'db_connect.php';

// Get JSON POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    die('Invalid JSON data received.');
}

// Prepare the query for the 'game' table
$stmt = $mysqli->prepare("INSERT INTO game (date_played, game_duration, session_number, game_in_session, user_id, source) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sdiids', $startTime, $gameDuration, $sessionNumber, $gamesInSession, $userId, $source);

// Extract game data from JSON
$startTime = $data['date_played'];
$gameDuration = $data['game_duration'];
$sessionNumber = $data['session_number'];
$gamesInSession = $data['game_in_session'];
$userId = $data['user_id'];
$source = $data['source'];

// Execute the query and get the inserted game_id
if ($stmt->execute()) {
    $gameId = $mysqli->insert_id;
} else {
    die('Error inserting game data: ' . $stmt->error);
}
$stmt->close();


// Prepare the query for the 'gamestate' table to use POINT data type for player and ghosts
$stmt = $mysqli->prepare("
    INSERT INTO gamestate (
        game_id, 
        pacman_pos, 
        ghost1_pos, 
        ghost2_pos, 
        ghost3_pos, 
        ghost4_pos, 
        ghost1_state, 
        ghost2_state, 
        ghost3_state, 
        ghost4_state,
        pacman_attack, 
        score, 
        lives, 
        time_elapsed, 
        pellets, 
        powerPellets
    ) VALUES (?, ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('isssssiiiisiiifii', 
    $gameId, 
    $playerPosition, 
    $ghost1Position, 
    $ghost2Position, 
    $ghost3Position, 
    $ghost4Position, 
    $ghost1State, 
    $ghost2State, 
    $ghost3State, 
    $ghost4State,
    $pacmanAttack, 
    $score, 
    $lives, 
    $timeElapsed, 
    $pelletsRemaining, 
    $powerPelletsRemaining);

// Iterate through each game data point
foreach ($data['dataPoints'] as $point) {
    $playerPosX = $point['playerPosition']['x'];
    $playerPosY = $point['playerPosition']['y'];
    $playerPosition = "POINT($playerPosX $playerPosY)";

    // Handling multiple ghosts
    $ghostPositions = [];
    $ghostStates = [];
    foreach ($point['ghostsPositions'] as $index => $gpos) {
        $ghostPositions[] = "POINT(" . $gpos['x'] . " " . $gpos['y'] . ")";
        $ghostStates[] = $point['ghostStates'][$index];
    }

    // Pad the ghostPositions array to ensure there are always four values
    while (count($ghostPositions) < 4) {
        $ghostPositions[] = "POINT(0 0)"; // Use default or null position
        $ghostStates[] = 0; // Default state
    }

    $ghost1Position = $ghostPositions[0];
    $ghost2Position = $ghostPositions[1];
    $ghost3Position = $ghostPositions[2];
    $ghost4Position = $ghostPositions[3];

    $ghost1State = $ghostStates[0];
    $ghost2State = $ghostStates[1];
    $ghost3State = $ghostStates[2];
    $ghost4State = $ghostStates[3];

    $pacmanAttack = $point['pacmanAttack'] ? 1 : 0;
    $score = $point['score'];
    $lives = $point['livesRemaining'];
    $timeElapsed = $point['timeElapsed'];
    $pelletsRemaining = $point['pelletsRemaining'];
    $powerPelletsRemaining = $point['powerPelletsRemaining'];

    // Execute the insert statement for each game state
    if (!$stmt->execute()) {
        die('Error inserting game state data: ' . $stmt->error);
    }
}
$stmt->close();

