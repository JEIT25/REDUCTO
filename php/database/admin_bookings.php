<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    // Count
    $countRes = $conn->query("SELECT COUNT(*) as total FROM bookings");
    $total = $countRes->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Fetch bookings
    // Joining users and playgrounds to get names
    $sql = "SELECT r.id, r.booking_date, r.booking_time, r.group_size, r.status, r.created_at,
                   u.firstName, u.lastName, u.email,
                   rest.name as playground_name
            FROM bookings r
            JOIN users u ON r.user_id = u.id
            JOIN playgrounds rest ON r.playground_id = rest.id
            ORDER BY r.booking_date DESC, r.booking_time DESC
            LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $list = [];
    while ($row = $result->fetch_assoc())
        $list[] = $row;

    echo json_encode([
        'success' => true,
        'bookings' => $list,
        'pagination' => ['current' => $page, 'total_pages' => $totalPages, 'total_records' => $total]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $allowed = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
        if (!in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']);
            exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $id);
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            }
            else {
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
            $stmt->close();
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    }
}
$conn->close();


