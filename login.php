<?php
$username = $_POST['username'];
$password = $_POST['password']; // The password attempt from the user

$file = 'credentials.csv';

// Flag to track if username is found
$usernameFound = false;

if (($handle = fopen($file, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($data[0] == $username) {
            $usernameFound = true;
            // Verify the hashed password
            if (password_verify($password, $data[1])) {
                echo "Login successful";
            } else {
                echo "Invalid password";
            }
            break;
        }
    }
    fclose($handle);
    
    if (!$usernameFound) {
        echo "Username does not exist";
    }
} else {
    echo "Error opening the file.";
}
?>
