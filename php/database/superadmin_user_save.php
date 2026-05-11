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

        // Security questions
        $secure_question = $_POST['secure_question'] ?? '';
        $secure_answer = $_POST['secure_answer'] ?? '';
        $secure_question2 = $_POST['secure_question2'] ?? '';
        $secure_answer2 = $_POST['secure_answer2'] ?? '';
        $secure_question3 = $_POST['secure_question3'] ?? '';
        $secure_answer3 = $_POST['secure_answer3'] ?? '';

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

            if ($role === 'basic-user' && $secure_question && $secure_answer) {
                $sql .= ", secure_question=?, secure_answer=?, secure_question2=?, secure_answer2=?, secure_question3=?, secure_answer3=?";
                $types .= "ssssss";
                $params[] = $secure_question;
                $params[] = password_hash($secure_answer, PASSWORD_DEFAULT);
                $params[] = $secure_question2;
                $params[] = password_hash($secure_answer2, PASSWORD_DEFAULT);
                $params[] = $secure_question3;
                $params[] = password_hash($secure_answer3, PASSWORD_DEFAULT);
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
            $ans1 = password_hash($secure_answer, PASSWORD_DEFAULT);
            $ans2 = password_hash($secure_answer2, PASSWORD_DEFAULT);
            $ans3 = password_hash($secure_answer3, PASSWORD_DEFAULT);

            $isBlocked = 0; // Active, skip approval since superadmin is adding
            $status = 'registered';
            $sql = "INSERT INTO users (id, firstName, lastName, middleInitial, extension, sex, birthdate, age, purok, barangay, city, province, zipCode, country, username, email, password, role, is_blocked, status, secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                ob_clean();
                echo json_encode(['success' => false, 'error' => 'Insert prepare failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("sssssssissssssssssisssssss", $customId, $firstName, $lastName, $middleInitial, $extension, $sex, $birthdate, $age, $purok, $barangay, $city, $province, $zipCode, $country, $username, $email, $hashedPassword, $role, $isBlocked, $status, $secure_question, $ans1, $secure_question2, $ans2, $secure_question3, $ans3);
            
            if ($stmt->execute()) {
                ob_clean();
                echo json_encode(['success' => true]);
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
