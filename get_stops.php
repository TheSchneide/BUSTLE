<?php
include 'Database.php';
header('Content-Type: application/json');

$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT stop_id, stop_name, latitude, longitude FROM bus_stops";
$result = $conn->query($sql);

$stops = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $stops[] = $row;
    }
}

echo json_encode($stops);
?>