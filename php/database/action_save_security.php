<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/db_connect.php';

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

$sq1 = $_POST['secure_question'] ?? '';
$sa1 = $_POST['secure_answer'] ?? '';
$sq2 = $_POST['secure_question2'] ?? '';
$sa2 = $_POST['secure_answer2'] ?? '';
$sq3 = $_POST['secure_question3'] ?? '';
$sa3 = $_POST['secure_answer3'] ?? '';

if (!$sq1 || !$sa1 || !$sq2 || !$sa2 || !$sq3 || !$sa3) {
    echo json_encode(['success' => false, 'error' => 'All security questions and answers are required.']);
    exit;
}

$ans1 = password_hash($sa1, PASSWORD_DEFAULT);
$ans2 = password_hash($sa2, PASSWORD_DEFAULT);
$ans3 = password_hash($sa3, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET secure_question = ?, secure_answer = ?, secure_question2 = ?, secure_answer2 = ?, secure_question3 = ?, secure_answer3 = ? WHERE id = ?");
$stmt->bind_param('sssssss', $sq1, $ans1, $sq2, $ans2, $sq3, $ans3, $userId);

if ($stmt->execute()) {
    // Update session user info
    $_SESSION['user']['secure_question'] = $sq1;
    
    $base = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/REDUCTO';
    $redirect = '';
    if ($user['role'] === 'admin') $redirect = $base . '/php/admin/index.php';
    elseif ($user['role'] === 'superadmin') $redirect = $base . '/php/superadmin/index.php';
    else $redirect = $base . '/php/auth/dashboard.php';

    echo json_encode(['success' => true, 'redirect' => $redirect]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database update failed.']);
}
$stmt->close();
$conn->close();
?>
