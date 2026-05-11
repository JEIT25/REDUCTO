<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$user_id = trim($_POST['user_id'] ?? '');
$role = trim($_POST['role'] ?? '');
$allowed = ['basic-user', 'admin', 'superadmin'];
if ($user_id === '' || !in_array($role, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param('ss', $role, $user_id);
$stmt->execute();
$stmt->close();
$conn->close();
echo json_encode(['success' => true]);
