<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("SELECT id, type, label, details, is_default FROM payment_methods WHERE user_id = ? AND type = 'gcash' ORDER BY is_default DESC, id");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'payment_methods' => $list]);
