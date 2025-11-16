<?php
include 'Database.php';
include 'Bus.php';

header('Content-Type: application/json');

$db = new Database();
$bus = new Bus($db->getConnection());

$buses = $bus->getAllActiveBuses();

echo json_encode($buses);
?>