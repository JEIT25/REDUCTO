<?php
/**
 * Returns list of active playgrounds with booking-relevant details (JSON).
 * No auth required for browsing.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');

$where = ['r.is_active = 1'];
$params = [];
$types = '';

if ($search) {
    $where[] = '(r.name LIKE ? OR r.playground_type LIKE ? OR r.address LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s]);
    $types .= 'sss';
}
if ($type) {
    $where[] = 'r.playground_type = ?';
    $params[] = $type;
    $types .= 's';
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$sql = "SELECT r.*,
        (SELECT COUNT(*) FROM play_areas rt WHERE rt.playground_id = r.id AND rt.is_available = 1) as available_tables
        FROM playgrounds r
        $whereClause
        ORDER BY r.rating DESC, r.name ASC";

$stmt = $conn->prepare($sql);
if ($types)
    $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$list = [];
while ($row = $result->fetch_assoc()) {
    $list[] = $row;
}

// Get distinct Playground Types for filter
$typeResult = $conn->query("SELECT DISTINCT playground_type FROM playgrounds WHERE is_active = 1 ORDER BY playground_type");
$typeList = [];
while ($c = $typeResult->fetch_assoc())
    $typeList[] = $c['playground_type'];

echo json_encode(['success' => true, 'playgrounds' => $list, 'types' => $typeList]);
$conn->close();





