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

    // Fetch playgrounds for dropdown
    $playgrounds = [];
    $rRes = $conn->query("SELECT id, name FROM playgrounds ORDER BY name");
    while ($r = $rRes->fetch_assoc())
        $playgrounds[] = $r;

    // Count
    $countRes = $conn->query("SELECT COUNT(*) as total FROM play_areas");
    $total = $countRes->fetch_assoc()['total'];
    $totalPages = ceil($total / $limit);

    // Fetch Areas
    $sql = "SELECT rt.id, rt.area_name, rt.capacity, rt.location_type, rt.is_available, rt.playground_id, r.name as playground_name
            FROM play_areas rt
            JOIN playgrounds r ON rt.playground_id = r.id
            ORDER BY r.name, rt.area_name
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
        'Areas' => $list,
        'playgrounds' => $playgrounds,
        'pagination' => ['current' => $page, 'total_pages' => $totalPages, 'total_records' => $total]
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $playground_id = (int)($_POST['playground_id'] ?? 0);
        $area_name = trim($_POST['area_name'] ?? '');
        $capacity = (int)($_POST['capacity'] ?? 2);
        $location_type = $_POST['location_type'] ?? 'indoor';
        $is_available = isset($_POST['is_available']) ? (int)$_POST['is_available'] : 1;

        if ($playground_id <= 0 || $area_name === '') {
            echo json_encode(['success' => false, 'error' => 'playground and Area Number required']);
            exit;
        }

        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE play_areas SET playground_id=?, area_name=?, capacity=?, location_type=?, is_available=? WHERE id=?");
            $stmt->bind_param('isisii', $playground_id, $area_name, $capacity, $location_type, $is_available, $id);
        }
        else {
            // Check duplicate Area number in same playground
            $check = $conn->prepare("SELECT id FROM play_areas WHERE playground_id = ? AND area_name = ?");
            $check->bind_param('is', $playground_id, $area_name);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                echo json_encode(['success' => false, 'error' => 'Area number already exists for this playground']);
                exit;
            }
            $stmt = $conn->prepare("INSERT INTO play_areas (playground_id, area_name, capacity, location_type, is_available) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('isisi', $playground_id, $area_name, $capacity, $location_type, $is_available);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        }
        else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }
        $stmt->close();
    }
    elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM play_areas WHERE id = ?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute())
                echo json_encode(['success' => true]);
            else
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            $stmt->close();
        }
        else {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        }
    }
}
$conn->close();




