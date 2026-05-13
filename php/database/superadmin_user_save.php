<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('superadmin');

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ob_start();
    try {
        $id = $_POST['id'] ?? ''; // Current DB ID (for WHERE clause)
        $customId = trim($_POST['custom_id'] ?? ''); // New ID (for SET clause)
        $firstName = trim($_POST['firstName'] ?? '');
        $lastName = trim($_POST['lastName'] ?? '');
        $middleInitial = trim($_POST['middleInitial'] ?? '');
        $extension = trim($_POST['extension'] ?? '');
        $sex = $_POST['sex'] ?? '';
        $birthdate = trim($_POST['birthdate'] ?? '');
        if ($birthdate && $birthdate !== '0000-00-00') {
            $birthdate = date('Y-m-d', strtotime($birthdate));
        }
        $age = (int)($_POST['age'] ?? 0);
        $purok = trim($_POST['purok'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $zipCode = trim($_POST['zipCode'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'basic-user';

        if (!$firstName || !$lastName || !$username || !$email || !$sex || !$birthdate || $birthdate === '0000-00-00') {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Valid birthdate is required.']);
            exit;
        }

        // Uniqueness check (including customId if it changed)
        $checkSql = "SELECT id FROM users WHERE (username = ? OR email = ? OR id = ?)";
        if ($id) $checkSql .= " AND id != ?";
        
        $checkStmt = $conn->prepare($checkSql);
        if ($id) $checkStmt->bind_param("ssss", $username, $email, $customId, $id);
        else $checkStmt->bind_param("sss", $username, $email, $customId);
        
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        if ($checkRes->num_rows > 0) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Username, Email, or ID already exists.']);
            exit;
        }
        $checkStmt->close();

        if ($id) {
            // UPDATE
            $conn->begin_transaction();
            $conn->query("SET foreign_key_checks = 0");

            // 1. Manual Cascade for ID update (Foreign Key constraints)
            if ($customId !== $id) {
                $cascades = [
                    ['approvals', 'requested_by'],
                    ['approvals', 'reviewed_by'],
                    ['login_logs', 'user_id'],
                    ['bookings', 'user_id'],
                    ['package_orders', 'user_id'],
                    ['booking_cart', 'user_id'],
                    ['user_favorites', 'user_id']
                ];

                foreach ($cascades as $c) {
                    $table = $c[0]; $col = $c[1];
                    // Check if table exists
                    $checkTable = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($checkTable->num_rows > 0) {
                        $uSql = "UPDATE `$table` SET `$col` = ? WHERE `$col` = ?";
                        $uStmt = $conn->prepare($uSql);
                        if ($uStmt) {
                            $uStmt->bind_param("ss", $customId, $id);
                            $uStmt->execute();
                            $uStmt->close();
                        }
                    }
                }
            }

            $sql = "UPDATE users SET id=?, firstName=?, lastName=?, middleInitial=?, extension=?, sex=?, birthdate=?, age=?,
                    purok=?, barangay=?, city=?, province=?, zipCode=?, country=?,
                    username=?, email=?, role=?";
            $types = "sssssssisssssssss";
            $params = [$customId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age, $purok, $barangay, $city, $province, $zipCode, $country, $username, $email, $role];

            if ($password) {
                $sql .= ", password=?";
                $types .= "s";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id=?";
            $types .= "s";
            $params[] = $id;

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $conn->query("SET foreign_key_checks = 1");
                $conn->rollback();
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Update prepare failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                // If user edited themselves, update session
                if ($id === ($_SESSION['user']['id'] ?? '')) {
                    $_SESSION['user']['id'] = $customId;
                    $_SESSION['user']['username'] = $username;
                }
                $conn->query("SET foreign_key_checks = 1");
                $conn->commit();
                ob_clean();
                echo json_encode(['success' => true]);
            } else {
                $conn->query("SET foreign_key_checks = 1");
                $conn->rollback();
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Update failed: ' . $stmt->error]);
            }
        } else {
            // INSERT
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $isBlocked = 0; // Active, skip approval since superadmin is adding
            $status = 'registered';

            // Check if we are creating a new Superadmin
            $creatorSwap = false;
            if ($role === 'superadmin') {
                $creatorSwap = true;
                $creatorId = $_SESSION['user']['id'];
            }

            $sql = "INSERT INTO users (id, firstName, lastName, middleInitial, extension, sex, birthdate, age, purok, barangay, city, province, zipCode, country, username, email, password, role, is_blocked, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Insert prepare failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("sssssssissssssssssis", $customId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age, $purok, $barangay, $city, $province, $zipCode, $country, $username, $email, $hashedPassword, $role, $isBlocked, $status);
            
            if ($stmt->execute()) {
                if ($creatorSwap) {
                    // Block the creator
                    $conn->query("UPDATE users SET is_blocked = 1 WHERE id = '$creatorId'");
                    // We will return a specific flag so frontend can logout
                    ob_clean();
                    echo json_encode(['success' => true, 'superadmin_swap' => true]);
                } else {
                    ob_clean();
                    echo json_encode(['success' => true]);
                }
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
        }
        $stmt->close();
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
}
$conn->close();
?>
