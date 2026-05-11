<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

// Must be at least an admin
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'superadmin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = $_SESSION['user'];
$role = $currentUser['role'];

$request_id = trim($_POST['request_id'] ?? '');
$action = trim($_POST['action'] ?? ''); // 'approve' or 'reject'
$reviewNotes = trim($_POST['review_notes'] ?? '');

if (!$request_id || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$conn->begin_transaction();

try {
    $status = ($action === 'approve') ? 'approved' : 'rejected';

    // Fetch the approval request
    $get = $conn->prepare("SELECT * FROM approvals WHERE id = ?");
    $get->bind_param('i', $request_id);
    $get->execute();
    $approval = $get->get_result()->fetch_assoc();
    $get->close();

    if (!$approval) {
        throw new Exception("Approval request not found.");
    }

    $actionType = $approval['action_type'];
    $targetId = $approval['target_id'];

    // Permission check
    // register_basic-user: Admins and Superadmins
    // block/unblock: Only Superadmin
    if ($actionType === 'register_basic-user') {
        // Both can approve consumer registration
    } else if (in_array($actionType, ['block', 'unblock', 'register_admin'])) {
        if ($role !== 'superadmin') {
            throw new Exception("Only superadmin can process " . $actionType . " requests.");
        }
    }

    // Update approval status
    $up = $conn->prepare("UPDATE approvals SET status = ?, reviewed_by = ?, review_notes = ? WHERE id = ?");
    $up->bind_param('sssi', $status, $currentUser['id'], $reviewNotes, $request_id);
    $up->execute();
    $up->close();

    if ($action === 'approve') {
        if ($actionType === 'register_basic-user' || $actionType === 'register_admin') {
            // Approve registration = Unblock the user
            $upd = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $upd->bind_param('s', $targetId);
            $upd->execute();
            $upd->close();
        } else if ($actionType === 'block') {
            $upd = $conn->prepare("UPDATE users SET is_blocked = 1 WHERE id = ?");
            $upd->bind_param('s', $targetId);
            $upd->execute();
            $upd->close();
        } else if ($actionType === 'unblock') {
            $upd = $conn->prepare("UPDATE users SET is_blocked = 0 WHERE id = ?");
            $upd->bind_param('s', $targetId);
            $upd->execute();
            $upd->close();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

}
catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>
