<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}
$stmt = $conn->prepare("DELETE FROM payment_methods WHERE id = ? AND user_id = ?");
$stmt->bind_param('is', $id, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();
echo json_encode(['success' => true]);
