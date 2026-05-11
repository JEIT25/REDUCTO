<?php
/**
 * Returns Play Packages for a playground (JSON).
 * GET playground_id required.
 */
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

$playground_id = isset($_GET['playground_id']) ? (int) $_GET['playground_id'] : 0;
if ($playground_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid playground']);
    exit;
}

$stmt = $conn->prepare("SELECT id, playground_id, name, description, price, image_path, is_available FROM play_packages WHERE playground_id = ? ORDER BY name");
$stmt->bind_param('i', $playground_id);
$stmt->execute();
$result = $stmt->get_result();
$list = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = (float) $row['price'];
    $list[] = $row;
}
$stmt->close();
$conn->close();
echo json_encode(['success' => true, 'Packages' => $list]);


