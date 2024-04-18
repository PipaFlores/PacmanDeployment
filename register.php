<?php
$username = $_POST['username'];
$password = $_POST['password']; 
$email = $_POST['email'];

// Hash the password using bcrypt algorithm
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$file = 'Data/credentials.csv'; // Or .txt, depending on your preference

// Attempt to read the existing credentials
if (($handle = fopen($file, "a+")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        if ($data[0] == $username) {
            echo "Username already exists";
            fclose($handle);
            exit;
        }
    }
    // Username doesn't exist, write the new user with hashed password
    fputcsv($handle, array($username, $hashedPassword, $email));
    echo "User registered successfully";
    fclose($handle);
} else {
    echo "Error opening the file.";
}
?>