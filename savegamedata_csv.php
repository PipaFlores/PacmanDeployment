<?php

$filePath = "gameData.csv"; // Path to your CSV file

// Define your CSV header
$header = "source;PlayerPositionX;PlayerPositionY;Ghosts1;Ghost2;Ghost3;Ghost4;Score;LivesRemaining;TimeElapsed;IP;DateTime\n";

// Check if the file exists and is empty. If so, write the header.
if (!file_exists($filePath) || filesize($filePath) == 0) {
    file_put_contents($filePath, $header, FILE_APPEND);
}


// Check if data is sent via POST
if (isset($_POST['data'])) {
    $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : 'N/A';
    $dateTime = date('Y-m-d H:i:s'); // Get current date and time
    $csvData = $_POST['data'] . ";" . $ip . ";" . $dateTime . "\n"; // Append IP, date and time, and newline to prepare for the next entry
    // Append the data to the file
    file_put_contents($filePath, $csvData, FILE_APPEND);

    echo "Data appended successfully.";
} else {
    echo "No data received.";
}

?>