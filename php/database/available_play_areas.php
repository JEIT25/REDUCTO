<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$playgroundId = intval($_GET['playground_id'] ?? 0);
$date = trim($_GET['date'] ?? '');
$time = trim($_GET['time'] ?? '');
$partySize = intval($_GET['group_size'] ?? 1);

if (!$playgroundId) {
    echo json_encode(['success' => false, 'error' => 'playground ID is required.']);
    exit;
}

// Get all available Areas for this playground that fit the party size
$sql = "SELECT rt.* FROM play_areas rt
        WHERE rt.playground_id = ? AND rt.is_available = 1 AND rt.capacity >= ?";
$params = [$playgroundId, $partySize];
$types = 'ii';

// If date and time provided, exclude Areas with existing bookings at that time
if ($date && $time) {
    $resTime = $time . ':00';
    $sql = "SELECT rt.* FROM play_areas rt
            WHERE rt.playground_id = ? AND rt.is_available = 1 AND rt.capacity >= ?
            AND rt.id NOT IN (
                SELECT area_id FROM bookings
                WHERE playground_id = ? AND booking_date = ?
                AND ABS(TIMESTAMPDIFF(MINUTE, booking_time, ?)) < 120
                AND status IN ('pending','confirmed')
                AND area_id IS NOT NULL
            )";
    $params = [$playgroundId, $partySize, $playgroundId, $date, $resTime];
    $types = 'iiiss';
}

$sql .= " ORDER BY rt.capacity ASC, rt.area_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$Areas = [];
while ($row = $result->fetch_assoc()) {
    $Areas[] = $row;
}

echo json_encode(['success' => true, 'Areas' => $Areas]);
$conn->close();




