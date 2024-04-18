<?php

$filePath = "gameData.json"; // Path to your JSON file
$jsonString = file_get_contents('php://input'); // Get JSON as a string from UnityWebRequest

// Decode the incoming JSON data
$newData = json_decode($jsonString, true); // True to get as associative array

// Check if the file exists and is not empty
if (file_exists($filePath) && filesize($filePath) > 0) {
    // Read the existing data
    $existingDataJson = file_get_contents($filePath);
    $existingData = json_decode($existingDataJson, true);

    // Check if the root is an array (to handle multiple entries)
    if (is_array($existingData)) {
        // Append the new data
        $existingData[] = $newData;
    } else {
        // Handle the case where the existing data is not an array
        $existingData = array($existingData, $newData);
    }

    // Encode the updated data back to JSON
    $newJsonString = json_encode($existingData, JSON_PRETTY_PRINT);
} else {
    // If the file doesn't exist or is empty, start a new array with the new data
    $newJsonString = json_encode(array($newData), JSON_PRETTY_PRINT);
}

// Save the updated data back to the file
file_put_contents($filePath, $newJsonString);

echo "Data appended successfully.";

?>