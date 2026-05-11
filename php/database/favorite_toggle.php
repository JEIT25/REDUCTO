<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];
$package_id = isset($_POST['package_id']) ? (int) $_POST['package_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : 'toggle'; // add | remove | toggle

if ($package_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid item']);
    exit;
}

$check = $conn->prepare("SELECT 1 FROM user_favorites WHERE user_id = ? AND package_id = ?");
$check->bind_param('si', $user_id, $package_id);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($action === 'add' || ($action === 'toggle' && !$exists)) {
    $stmt = $conn->prepare("INSERT IGNORE INTO user_favorites (user_id, package_id) VALUES (?, ?)");
    $stmt->bind_param('si', $user_id, $package_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'favorited' => true]);
} elseif ($action === 'remove' || ($action === 'toggle' && $exists)) {
    $stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND package_id = ?");
    $stmt->bind_param('si', $user_id, $package_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'favorited' => false]);
} else {
    echo json_encode(['success' => true, 'favorited' => $exists]);
}
$conn->close();


