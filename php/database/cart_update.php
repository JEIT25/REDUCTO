<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');

$item_id = (int)($_POST['item_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 0); // 0 means remove

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item']);
    exit;
}

// Verify item belongs to user's cart
$user_id = $_SESSION['user']['id'];
$stmt = $conn->prepare("SELECT ci.id FROM booking_cart_packages ci JOIN cart c ON ci.cart_id = c.id WHERE ci.id = ? AND c.user_id = ?");
$stmt->bind_param('is', $item_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Item not found']);
    exit;
}
$stmt->close();

if ($quantity > 0) {
    $stmt = $conn->prepare("UPDATE booking_cart_packages SET quantity = ? WHERE id = ?");
    $stmt->bind_param('ii', $quantity, $item_id);
}
else {
    $stmt = $conn->prepare("DELETE FROM booking_cart_packages WHERE id = ?");
    $stmt->bind_param('i', $item_id);
}

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
}
else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
$stmt->close();
$conn->close();
?>

