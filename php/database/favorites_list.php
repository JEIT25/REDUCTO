<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('basic-user');
$user_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT f.id, f.package_id, m.name, m.description, m.price, m.playground_id, m.is_available, r.name AS playground_name
    FROM user_favorites f
    JOIN play_packages m ON m.id = f.package_id
    JOIN playgrounds r ON r.id = m.playground_id
    WHERE f.user_id = ?
");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$list = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float) $row['price'];
    $list[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'favorites' => $list]);


