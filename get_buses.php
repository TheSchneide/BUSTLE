<?php
include 'Database.php';
include 'Bus.php';

header('Content-Type: application/json');

// 1. Create objects
$db = new Database();
$bus = new Bus($db->getConnection());

// 2. Call the method
$buses = $bus->getAllActiveBuses();

// 3. Echo the data
echo json_encode($buses);
?>