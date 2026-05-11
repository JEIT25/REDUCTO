<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
require_once 'db_connect.php';

header('Content-Type: application/json');

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];
$status = trim($_GET['status'] ?? '');
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query - consumers see only their own, admins/superadmins see all
$where = [];
$params = [];
$types = '';

if ($userRole === 'basic-user') {
    $where[] = 'r.user_id = ?';
    $params[] = $userId;
    $types .= 's';
}

if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
    $where[] = 'r.status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($search) {
    $where[] = '(rest.name LIKE ? OR r.id LIKE ?)';
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= 'ss';
}

$whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSql = "SELECT COUNT(*) as total FROM bookings r JOIN playgrounds rest ON r.playground_id = rest.id $whereClause";
$stmt = $conn->prepare($countSql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = $stmt->get_result()->fetch_assoc()['total'];

// Get bookings
$sql = "SELECT r.*, rest.name AS playground_name, rest.playground_type, rest.address AS playground_address,
        rt.area_name, rt.capacity AS Area_capacity, rt.location_type AS Area_location,
        u.firstName, u.lastName
        FROM bookings r
        JOIN playgrounds rest ON r.playground_id = rest.id
        LEFT JOIN play_areas rt ON r.area_id = rt.id
        JOIN users u ON r.user_id = u.id
        $whereClause
        ORDER BY r.booking_date DESC, r.booking_time DESC
        LIMIT ? OFFSET ?";

$paramsFull = array_merge($params, [$limit, $offset]);
$typesFull = $types . 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($typesFull, ...$paramsFull);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

echo json_encode([
    'success' => true,
    'bookings' => $bookings,
    'total' => intval($total),
    'page' => $page,
    'pages' => ceil($total / $limit),
    'total_pages' => ceil($total / $limit)
]);
$conn->close();




