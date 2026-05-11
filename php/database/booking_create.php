<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('basic-user');
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$playgroundId = intval($_POST['playground_id'] ?? 0);
$AreaId = !empty($_POST['area_id']) ? intval($_POST['area_id']) : null;
$date = trim($_POST['booking_date'] ?? '');
$time = trim($_POST['booking_time'] ?? '');
$group_size = intval($_POST['group_size'] ?? 1);
$specialRequests = trim($_POST['special_requests'] ?? '');

// Validate
if (!$playgroundId || !$date || !$time || $group_size < 1) {
    echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}
if ($group_size > 50) {
    echo json_encode(['success' => false, 'error' => 'Group size cannot exceed 50.']);
    exit;
}

// Check playground exists and is active
$stmt = $conn->prepare("SELECT id, opening_time, closing_time FROM playgrounds WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $playgroundId);
$stmt->execute();
$playground = $stmt->get_result()->fetch_assoc();
if (!$playground) {
    echo json_encode(['success' => false, 'error' => 'Playground not found or is not available.']);
    exit;
}

// Validate date is not in the past
$bookingDate = new DateTime($date);
$today = new DateTime('today');
if ($bookingDate < $today) {
    echo json_encode(['success' => false, 'error' => 'Cannot make a booking in the past.']);
    exit;
}

// Validate time is within operating hours
$resTime = $time . ':00';
if ($resTime < $playground['opening_time'] || $resTime >= $playground['closing_time']) {
    echo json_encode(['success' => false, 'error' => 'Booking time is outside operating hours.']);
    exit;
}

// If Area is specified, validate it exists and can accommodate the group
if ($AreaId) {
    $stmt = $conn->prepare("SELECT id, capacity FROM play_areas WHERE id = ? AND playground_id = ? AND is_available = 1");
    $stmt->bind_param("ii", $AreaId, $playgroundId);
    $stmt->execute();
    $Area = $stmt->get_result()->fetch_assoc();
    if (!$Area) {
        echo json_encode(['success' => false, 'error' => 'Selected Area is not available.']);
        exit;
    }
    if ($Area['capacity'] < $group_size) {
        echo json_encode(['success' => false, 'error' => 'Area capacity (' . $Area['capacity'] . ') is less than group size (' . $group_size . ').']);
        exit;
    }

    // Check if Area is already booked at this date/time (within 2 hour window)
    $stmt = $conn->prepare("SELECT id FROM bookings WHERE area_id = ? AND booking_date = ? AND ABS(TIMESTAMPDIFF(MINUTE, booking_time, ?)) < 120 AND status IN ('pending','confirmed')");
    $stmt->bind_param("iss", $AreaId, $date, $resTime);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'This Area is already booked at the selected time.']);
        exit;
    }
}

// Create booking
$stmt = $conn->prepare("INSERT INTO bookings (user_id, playground_id, area_id, booking_date, booking_time, group_size, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("siissis", $userId, $playgroundId, $AreaId, $date, $resTime, $group_size, $specialRequests);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Booking created successfully!', 'booking_id' => $stmt->insert_id]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Failed to create booking. Please try again.']);
}
$conn->close();



