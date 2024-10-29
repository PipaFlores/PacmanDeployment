<?php
// Include the database connection file
include 'db_connect.php';
include 'utils.php';

// Get JSON POST data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

$response = ["success" => false, "message" => ""];

if (!$data) {
    $response["message"] = "Invalid JSON data received.";
    echo json_encode($response);
    die();
}

// Start transaction
$conn->begin_transaction();

try {
    // Validate game data
    $errors = validategamedataInput($data);
    if (!empty($errors)) {
        throw new Exception('Invalid game data: ' . implode(', ', $errors));
    }

    // Extract game data from JSON
    $startTime = $data['date_played'];
    $gameDuration = $data['game_duration'];
    $sessionNumber = $data['session_number'];
    $gamesInSession = $data['game_in_session'];
    $total_games = $data['total_games'];
    $userId = $data['user_id'];
    $source = $data['source'];
    $win = $data['win'];
    $level = $data['level'];

    // Validate dataPoints array
    if (!isset($data['dataPoints']) || !is_array($data['dataPoints'])) {
        throw new Exception('Missing or invalid dataPoints array');
    }

    // Prepare the query for the 'game' table
    $stmt = $conn->prepare("INSERT INTO game (date_played, game_duration, session_number, game_in_session, user_id, source, win, level, total_games_played) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sdiidsiii', $startTime, $gameDuration, $sessionNumber, $gamesInSession, $userId, $source, $win, $level, $total_games);

    // Execute the query and get the inserted game_id
    if (!$stmt->execute()) {
        throw new Exception('Error inserting game meta-data: ' . $stmt->error);
    }
    $gameId = $conn->insert_id;
    $stmt->close();

    // Prepare the query for the 'gamestate' table to use POINT data type for player and ghosts
    $stmt = $conn->prepare("
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
            powerPellets,
            powerpelletstate_1,
            powerpelletstate_2,
            powerpelletstate_3,
            powerpelletstate_4,
            fruitState_1,
            fruitState_2,
            input_direction,
            movement_direction) 
            VALUES (?, ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ST_PointFromText(?), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"); 
    $stmt->bind_param('isssssiiiiiiidiiiiiiiiss' , 
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
        $powerPelletsRemaining,
        $powerPelletState1,
        $powerPelletState2,
        $powerPelletState3,
        $powerPelletState4,
        $fruitState1,
        $fruitState2,
        $inputDirection,
        $movementDirection);

    // Iterate through each game data point
    foreach ($data['dataPoints'] as $point) {
        $playerPosX = $point['playerPosition']['x'];
        $playerPosY = $point['playerPosition']['y'];
        $playerPosition = "POINT($playerPosX $playerPosY)";
        $inputDirection = $point['inputDirection'];
        $movementDirection = $point['movementDirection'];

        // Handling multiple ghosts
        $ghostPositions = [];
        $ghostStates = [];
        foreach ($point['ghostsPositions'] as $index => $gpos) {
            $ghostPositions[] = "POINT(" . $gpos['x'] . " " . $gpos['y'] . ")";
            $ghostStates[] = $point['ghostStates'][$index];
        }

        // Handling multiple power pellet states
        $powerPelletState1 = $point['powerPelletStates'][0];
        $powerPelletState2 = $point['powerPelletStates'][1];
        $powerPelletState3 = $point['powerPelletStates'][2];
        $powerPelletState4 = $point['powerPelletStates'][3];


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
        $fruitState1 = $point['fruitState_1'];
        $fruitState2 = $point['fruitState_2'];

        // Execute the insert statement for each game state
        if (!$stmt->execute()) {
            throw new Exception('Error inserting game state data: ' . $stmt->error);
        }
    }

    // Commit the transaction
    $conn->commit();
    $response["success"] = true;
    $response["message"] = "Game data saved successfully.";
} catch (Exception $e) {
    $conn->rollback();
    $response["message"] = $e->getMessage();
} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}

// Return the response
echo json_encode($response);


