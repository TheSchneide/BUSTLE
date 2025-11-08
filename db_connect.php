<?php
$host = "localhost";
$user = "s24100966_bustle";
$pass = "Ciscocisco1";
$dbname = "s24100966_bustle";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
