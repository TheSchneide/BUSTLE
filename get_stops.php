<?php
include 'Database.php';
include 'BusStop.php';
header('Content-Type: application/json');

$db = new Database();
$stops = new BusStop($db->getConnection());

echo json_encode($stops->getAll());
?>