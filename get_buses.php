<?php
include 'db_connect.php';

$sql = "
    SELECT b.bus_id, b.plate_number, b.route,
           l.latitude, l.longitude, l.timestamp
    FROM buses b
    LEFT JOIN (
        SELECT bus_id, latitude, longitude, timestamp
        FROM bus_locations
        WHERE (bus_id, timestamp) IN (
            SELECT bus_id, MAX(timestamp)
            FROM bus_locations
            GROUP BY bus_id
        )
    ) l ON b.bus_id = l.bus_id
    WHERE b.status = 'active'
";

$result = $conn->query($sql);
$buses = [];

while ($row = $result->fetch_assoc()) {
    $buses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($buses);
?>
