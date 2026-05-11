<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
$status = trim($_POST['status'] ?? '');
$allowed = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
if ($order_id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("UPDATE package_orders SET status = ? WHERE id = ?");
$stmt->bind_param('si', $status, $order_id);
$stmt->execute();
$stmt->close();
$conn->close();
echo json_encode(['success' => true]);

