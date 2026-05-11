<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

$stmt = $conn->prepare("
    SELECT o.id, o.user_id, o.playground_id, o.status, o.total_amount, o.delivery_address, o.created_at,
           r.name AS playground_name
    FROM package_orders o
    JOIN playgrounds r ON r.id = o.playground_id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $row['total_amount'] = (float) $row['total_amount'];
    $orders[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'orders' => $orders]);


