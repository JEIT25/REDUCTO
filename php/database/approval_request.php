<?php
/**
 * Create an approval request (admin only).
 * POST: action_type (delete_user|delete_playground|delete_play_package), target_type, target_id, reason
 */
header('Content-Type: application/json');
ob_start();
require_once __DIR__ . '/db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
if (($user['role'] ?? '') !== 'admin') {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only admin can submit requests']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$actionType = trim($_POST['action_type'] ?? '');
$targetType = trim($_POST['target_type'] ?? '');
$targetId = trim($_POST['target_id'] ?? '');
$reason = trim($_POST['reason'] ?? '');

$allowedActions = ['delete_user', 'delete_playground', 'delete_play_package'];
$allowedTargets = ['user', 'playground', 'play_package'];
if (!in_array($actionType, $allowedActions) || !in_array($targetType, $allowedTargets) || $targetId === '') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid action_type, target_type, or target_id']);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO approvals (requested_by, action_type, target_type, target_id, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param('sssss', $user['id'], $actionType, $targetType, $targetId, $reason);
    $stmt->execute();
    $id = (int) $conn->insert_id;
    $stmt->close();
    $conn->close();
    ob_clean();
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Request submitted for review']);
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();

