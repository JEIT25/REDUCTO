<?php
ob_start();
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/../includes/auth.php';

requireRole('admin');

try {
    // Get input values (consumer specific)
    $id = trim($_POST['id'] ?? '');
    $customId = trim($_POST['custom_id'] ?? '');
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $middleInitial = trim($_POST['middleInitial'] ?? '');
    $extension = trim($_POST['extension'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    if ($birthdate && $birthdate !== '0000-00-00') {
        $birthdate = date('Y-m-d', strtotime($birthdate));
    }
    $purok = trim($_POST['purok'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $zipCode = trim($_POST['zipCode'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Security Questions (Always required for basic-user)
    $secure_question = $_POST['secure_question'] ?? '';
    $secure_answer = $_POST['secure_answer'] ?? '';
    $secure_question2 = $_POST['secure_question2'] ?? '';
    $secure_answer2 = $_POST['secure_answer2'] ?? '';
    $secure_question3 = $_POST['secure_question3'] ?? '';
    $secure_answer3 = $_POST['secure_answer3'] ?? '';

    if (!$firstName || !$lastName || !$username || !$email || !$sex || !$birthdate || $birthdate === '0000-00-00') {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Missing required fields or invalid birthdate']);
        exit;
    }

    // Age calc
    $age = date_diff(date_create($birthdate), date_create('today'))->y;

    // Check duplicates
    $checkSql = "SELECT id FROM users WHERE (username = ? OR email = ? OR id = ?)";
    if ($id) $checkSql .= " AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    if ($id) $checkStmt->bind_param('ssss', $username, $email, $customId, $id);
    else $checkStmt->bind_param('sss', $username, $email, $customId);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Username, Email, or Identity Number already exists']);
        exit;
    }
    $checkStmt->close();

    $role = 'basic-user'; // Forced

    if ($id) {
        // UPDATE
        $conn->begin_transaction();
        $conn->query("SET foreign_key_checks = 0");

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
                $checkT = $conn->query("SHOW TABLES LIKE '$table'");
                if ($checkT->num_rows > 0) {
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
                username=?, email=?, role='basic-user'";
        $types = "sssssssisssssss";
        $params = [$customId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age, $purok, $barangay, $city, $province, $zipCode, $country, $username, $email];

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
            echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
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
    }
    else {
        // CREATE
        $isBlocked = 0; // Admin adding user -> active
        $status = 'registered';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (id, firstName, lastName, middleInitial, extension, sex, birthdate, age, purok, barangay, city, province, zipCode, country, username, email, password, role, is_blocked, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssissssssssssis", $customId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age, $purok, $barangay, $city, $province, $zipCode, $country, $username, $email, $hashedPassword, $role, $isBlocked, $status);
            if ($stmt->execute()) {
                ob_clean();
                echo json_encode(['success' => true]);
            } else {
                ob_clean();
                echo json_encode(['success' => false, 'error' => $stmt->error]);
            }
        }
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>
?>
