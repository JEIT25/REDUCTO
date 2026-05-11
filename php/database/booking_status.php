<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
requireRole('admin', 'superadmin');
require_once 'db_connect.php';

header('Content-Type: application/json');

$bookingId = intval($_POST['booking_id'] ?? 0);
$newStatus = trim($_POST['status'] ?? '');
$validStatuses = ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'];

if (!$bookingId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'error' => 'Invalid booking ID or status.']);
    exit;
}

$stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->bind_param("si", $newStatus, $bookingId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'booking status updated to ' . $newStatus . '.']);
}
else {
    echo json_encode(['success' => false, 'error' => 'booking not found or status unchanged.']);
}
$conn->close();

