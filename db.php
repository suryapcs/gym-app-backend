<?php
$servername = "localhost";
$username   = "root";
$password   = "";  
$dbname     = "gym_app";  

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . mysqli_connect_error()
    ]));
}
?>
