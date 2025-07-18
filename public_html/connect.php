<?php
/*
$servername = "localhost";
$username = "u780763580_Demour123"; // default XAMPP username
$password = "Demour123$";     // default XAMPP password is empty
$dbname = "u780763580_Demour123";
*/

$servername = "localhost";
$username = "root"; // default XAMPP username
$password = "";     // default XAMPP password is empty
$dbname = "demurepourtou"; // replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>