<?php
/*
$servername = "localhost";
$username = "u780763580_Demour123"; // default XAMPP username
$password = "Demour123$";     // default XAMPP password is empty
$dbname = "u780763580_Demour123";
*/
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->Load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USERNAME']; // default XAMPP username
$password = $_ENV['DB_PASSWORD'];     // default XAMPP password is empty
$dbname = $_ENV['DB_NAME']; // replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>