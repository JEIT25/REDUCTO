<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Support JSON input (for Fetch API)
$input = json_decode(file_get_contents('php://input'), true);
$bookingId = intval($input['id'] ?? ($_POST['booking_id'] ?? 0));

if (!$bookingId) {
    echo json_encode(['success' => false, 'error' => 'booking ID is required.']);
    exit;
}

// Consumers can only cancel their own bookings
if ($userRole === 'basic-user') {
    $stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
    $stmt->bind_param("is", $bookingId, $userId);
}
else {
    $stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ?");
    $stmt->bind_param("i", $bookingId);
}
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    echo json_encode(['success' => false, 'error' => 'booking not found.']);
    exit;
}

if (!in_array($booking['status'], ['pending', 'confirmed'])) {
    echo json_encode(['success' => false, 'error' => 'Only pending or confirmed bookings can be cancelled.']);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param("i", $bookingId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'booking cancelled successfully.']);
}
else {
    echo json_encode(['success' => false, 'error' => 'Failed to cancel booking.']);
}
$conn->close();

