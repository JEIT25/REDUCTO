<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole(['admin', 'superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $playground_id = isset($_GET['playground_id']) ? (int) $_GET['playground_id'] : 0;
    if ($playground_id > 0) {
        $stmt = $conn->prepare("SELECT id, playground_id, name, description, price, is_available FROM play_packages WHERE playground_id = ? ORDER BY name");
        $stmt->bind_param('i', $playground_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query("SELECT id, playground_id, name, description, price, is_available FROM play_packages ORDER BY playground_id, name");
    }
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $row['price'] = (float) $row['price'];
        $list[] = $row;
    }
    if (isset($stmt)) $stmt->close();
    echo json_encode(['success' => true, 'Packages' => $list]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $playground_id = (int) ($_POST['playground_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $is_available = isset($_POST['is_available']) ? (int) $_POST['is_available'] : 1;
        if ($name === '' || $playground_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Name and playground required']);
            exit;
        }
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE play_packages SET playground_id = ?, name = ?, description = ?, price = ?, is_available = ? WHERE id = ?");
            $stmt->bind_param('issdii', $playground_id, $name, $description, $price, $is_available, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO play_packages (playground_id, name, description, price, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('issdi', $playground_id, $name, $description, $price, $is_available);
        }
        $stmt->execute();
        $newId = $id > 0 ? $id : $conn->insert_id;
        $stmt->close();
        echo json_encode(['success' => true, 'id' => $newId]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) { echo json_encode(['success' => false]); exit; }
        $stmt = $conn->prepare("DELETE FROM play_packages WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
}
$conn->close();


