<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

// Get pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$endDate = trim($_GET['endDate'] ?? '');

$appWhere = "a.target_type = 'user'";
$appParams = [];
$appTypes = '';

if ($status !== '') {
    $appWhere .= " AND a.status = ?";
    $appParams[] = $status;
    $appTypes .= 's';
}

if ($search !== '') {
    $searchToken = "%$search%";
    $appWhere .= " AND (u1.firstName LIKE ? OR u1.lastName LIKE ? OR u.firstName LIKE ? OR u.lastName LIKE ? OR u.username LIKE ? OR a.reason LIKE ?)";
    array_push($appParams, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
    $appTypes .= 'ssssss';
}

// Date Range Filtering
if ($startDate !== '') {
    $appWhere .= " AND DATE(a.created_at) >= ?";
    $appParams[] = $startDate;
    $appTypes .= 's';
}
if ($endDate !== '') {
    $appWhere .= " AND DATE(a.created_at) <= ?";
    $appParams[] = $endDate;
    $appTypes .= 's';
}

try {
    // Unified query from the 'approvals' table
    $sql = "
        SELECT
            a.id as request_id,
            a.action_type as type,
            a.reason,
            a.status,
            a.created_at,
            a.review_notes,
            u1.firstName as requester_first,
            u1.lastName as requester_last,
            u.firstName as target_first,
            u.lastName as target_last,
            u.username as target_username,
            u.id as target_id,
            'approvals' as source_table
        FROM approvals a
        JOIN users u1 ON a.requested_by = u1.id
        JOIN users u ON a.target_id = u.id
        WHERE $appWhere
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";

    $allParams = array_merge($appParams, [$limit, $offset]);
    $allTypes = $appTypes . 'ii';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (strlen($allTypes) > 0) {
            $stmt->bind_param($allTypes, ...$allParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['type'] === 'register_basic-user') {
                $row['request_type'] = 'registration';
            }
            elseif ($row['type'] === 'register_admin') {
                $row['request_type'] = 'admin_reg';
            }
            elseif ($row['type'] === 'delete_user') {
                $row['request_type'] = 'deletion';
            }
            else {
                $row['request_type'] = $row['type'];
            }
            $requests[] = $row;
        }
        $stmt->close();

        $countSql = "SELECT COUNT(*) as total FROM approvals a JOIN users u1 ON a.requested_by = u1.id JOIN users u ON a.target_id = u.id WHERE $appWhere";
        $countStmt = $conn->prepare($countSql);
        if ($countStmt) {
            if (strlen($appTypes) > 0) {
                $countStmt->bind_param($appTypes, ...$appParams);
            }
            $countStmt->execute();
            $totalResult = $countStmt->get_result()->fetch_assoc();
            $total = (int)$totalResult['total'];
            $countStmt->close();
        }
        else {
            $total = 0;
        }

        echo json_encode([
            'success' => true,
            'requests' => $requests,
            'pagination' => [
                'current_page' => $page,
                'limit' => $limit,
                'total_requests' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    }
    else {
        throw new Exception($conn->error);
    }
}
catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
