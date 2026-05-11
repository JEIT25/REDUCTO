<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

$sql = "SELECT a.id, a.reason, a.status, a.created_at, a.action_type as request_type,
               u1.firstName as r_first, u1.lastName as r_last, u1.role as r_role,
               u2.firstName as t_first, u2.lastName as t_last, u2.username as t_username, u2.id as t_id
        FROM approvals a
        JOIN users u1 ON a.requested_by = u1.id
        JOIN users u2 ON a.target_id = u2.id
        ORDER BY a.created_at DESC";

$result = $conn->query($sql);
$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $list[] = $row;
    }
}
$conn->close();

echo json_encode(['success' => true, 'requests' => $list]);
?>
