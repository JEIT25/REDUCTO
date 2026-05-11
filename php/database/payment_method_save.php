<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];

// Only GCash can be saved; COD is always available at checkout.
$type = isset($_POST['type']) ? trim($_POST['type']) : '';
if ($type !== 'gcash') {
    $type = 'gcash';
}
$label = trim($_POST['label'] ?? '');
$details = trim($_POST['details'] ?? '');
$is_default = isset($_POST['is_default']) ? (int) $_POST['is_default'] : 0;

if ($label === '') {
    echo json_encode(['success' => false, 'error' => 'GCash mobile number is required.']);
    exit;
}

if ($is_default) {
    $up = $conn->prepare("UPDATE payment_methods SET is_default = 0 WHERE user_id = ?");
    $up->bind_param('s', $user_id);
    $up->execute();
    $up->close();
}
$stmt = $conn->prepare("INSERT INTO payment_methods (user_id, type, label, details, is_default) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('ssssi', $user_id, $type, $label, $details, $is_default);
$stmt->execute();
$id = $conn->insert_id;
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'id' => $id]);
