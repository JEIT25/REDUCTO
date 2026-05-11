<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = min(50, max(5, (int) ($_GET['per_page'] ?? 10)));
$offset = ($page - 1) * $per_page;

$allowed_statuses = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];
$where = ["o.user_id = ?"];
$params = [$user_id];
$types = 's';

if ($status !== '' && in_array($status, $allowed_statuses, true)) {
    $where[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}
if ($search !== '') {
    if (ctype_digit($search)) {
        $where[] = "(o.id = ? OR r.name LIKE ?)";
        $params[] = (int) $search;
        $params[] = '%' . $conn->real_escape_string($search) . '%';
        $types .= 'is';
    } else {
        $where[] = "r.name LIKE ?";
        $params[] = '%' . $conn->real_escape_string($search) . '%';
        $types .= 's';
    }
}

$where_sql = implode(' AND ', $where);

$count_sql = "SELECT COUNT(*) AS total FROM package_orders o JOIN playgrounds r ON r.id = o.playground_id WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total = (int) $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = $total > 0 ? (int) ceil($total / $per_page) : 0;

$sql = "SELECT o.id, o.playground_id, o.status, o.total_amount, o.delivery_address, o.created_at, r.name AS playground_name
    FROM package_orders o
    JOIN playgrounds r ON r.id = o.playground_id
    WHERE $where_sql
    ORDER BY o.created_at DESC
    LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
$order_ids = [];
while ($row = $result->fetch_assoc()) {
    $row['total_amount'] = (float) $row['total_amount'];
    $orders[] = $row;
    $order_ids[] = (int) $row['id'];
}
$stmt->close();

$item_preview = [];
if (!empty($order_ids)) {
    $ids_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $item_sql = "SELECT oi.order_id, m.name FROM package_order_items oi JOIN play_packages m ON m.id = oi.package_id WHERE oi.order_id IN ($ids_placeholders) ORDER BY oi.order_id, oi.id";
    $item_stmt = $conn->prepare($item_sql);
    $item_stmt->bind_param(str_repeat('i', count($order_ids)), ...$order_ids);
    $item_stmt->execute();
    $item_result = $item_stmt->get_result();
    while ($ir = $item_result->fetch_assoc()) {
        $oid = (int) $ir['order_id'];
        if (!isset($item_preview[$oid])) $item_preview[$oid] = [];
        if (count($item_preview[$oid]) < 2) $item_preview[$oid][] = $ir['name'];
    }
    $item_stmt->close();
}
foreach ($orders as &$o) {
    $o['item_preview'] = $item_preview[$o['id']] ?? [];
}
unset($o);

$conn->close();
echo json_encode([
    'success' => true,
    'orders' => $orders,
    'pagination' => [
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'total_pages' => $total_pages,
    ],
]);


