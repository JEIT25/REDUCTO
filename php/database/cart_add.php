<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');

$user_id = $_SESSION['user']['id'];
$package_id = (int)($_POST['package_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);

if ($package_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item or quantity']);
    exit;
}

// Get or Create Cart
$stmt = $conn->prepare("SELECT id FROM booking_cart WHERE user_id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $cart_id = $row['id'];
}
else {
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO booking_cart (user_id) VALUES (?)");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $cart_id = $stmt->insert_id;
}
$stmt->close();

// Add or Update Item
$stmt = $conn->prepare("SELECT id, quantity FROM booking_cart_packages WHERE cart_id = ? AND package_id = ?");
$stmt->bind_param('ii', $cart_id, $package_id);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    // Update
    $new_qty = $row['quantity'] + $quantity;
    $stmt->close();
    $stmt = $conn->prepare("UPDATE booking_cart_packages SET quantity = ? WHERE id = ?");
    $stmt->bind_param('ii', $new_qty, $row['id']);
}
else {
    // Insert
    $stmt->close();
    $stmt = $conn->prepare("INSERT INTO booking_cart_packages (cart_id, package_id, quantity) VALUES (?, ?, ?)");
    $stmt->bind_param('iii', $cart_id, $package_id, $quantity);
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


