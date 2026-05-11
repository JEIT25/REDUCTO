<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

$admin_id = $_SESSION['user']['id'];
$role = $_SESSION['user']['role'];

// Get pagination & filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$startDate = trim($_GET['startDate'] ?? '');
$endDate = trim($_GET['endDate'] ?? '');

try {
    // 1. Role-based visibility logic for 'approvals' table
    if ($role === 'superadmin') {
        $where = "1=1";
        $params = [];
        $types = "";
    } else {
        // Admins can see their own block/unblock requests OR consumer registrations
        $where = "(a.requested_by = ? OR a.action_type = 'register_basic-user')";
        $params = [$admin_id];
        $types = "s";
    }

    if ($status !== '') {
        $where .= " AND a.status = ?";
        $params[] = $status;
        $types .= 's';
    }

    if ($startDate !== '') {
        $where .= " AND DATE(a.created_at) >= ?";
        $params[] = $startDate;
        $types .= 's';
    }

    if ($endDate !== '') {
        $where .= " AND DATE(a.created_at) <= ?";
        $params[] = $endDate;
        $types .= 's';
    }

    if ($search !== '') {
        $searchToken = "%$search%";
        $where .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR a.reason LIKE ?)";
        array_push($params, $searchToken, $searchToken, $searchToken, $searchToken, $searchToken);
        $types .= 'sssss';
    }

    // 2. Get total count
    $countSql = "SELECT COUNT(*) as total FROM approvals a JOIN users u ON a.target_id = u.id WHERE $where";
    $cStmt = $conn->prepare($countSql);
    if ($cStmt) {
        if (strlen($types) > 0) {
            $cStmt->bind_param($types, ...$params);
        }
        $cStmt->execute();
        $total = (int)($cStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $cStmt->close();
    } else {
        $total = 0;
    }

    // 3. Fetch Paginated Data
    $sql = "
        SELECT
            a.id as request_id,
            a.action_type as type,
            a.reason,
            a.status,
            a.created_at,
            a.review_notes,
            u.firstName as target_first,
            u.lastName as target_last,
            u.username as target_username,
            u.id as target_id,
            'approvals' as source_table
        FROM approvals a
        JOIN users u ON a.target_id = u.id
        WHERE $where
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ";

    $dataParams = array_merge($params, [$limit, $offset]);
    $dataTypes = $types . 'ii';

    $stmt = $conn->prepare($sql);
    $requests = [];
    if ($stmt) {
        $stmt->bind_param($dataTypes, ...$dataParams);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if ($row['type'] === 'register_basic-user') {
                $row['request_type'] = 'registration';
            } else {
                $row['request_type'] = $row['type'];
            }
            $requests[] = $row;
        }
        $stmt->close();
    } else {
        throw new Exception($conn->error);
    }

    echo json_encode([
        'success' => true,
        'requests' => $requests,
        'pagination' => [
            'current_page' => $page,
            'limit' => $limit,
            'total_requests' => $total,
            'total_pages' => ceil($total / max(1, $limit))
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
