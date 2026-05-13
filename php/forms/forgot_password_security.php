<?php
/**
 * Forgot Password - Step 4: Security Questions
 * Verify user identity via security questions
 */
session_start();
require_once __DIR__ . '/../includes/path_helper.php';
$basePath = getBasePath(__FILE__);
require_once '../database/db_connect.php';

// Check if OTP was verified
if (!isset($_SESSION['reset_user_id']) || !isset($_SESSION['otp_verified'])) {
    header('Location: forgot_password.php');
    exit;
}

$user_id = $_SESSION['reset_user_id'];
$error = '';

// Fetch user info for display
$stmt = $conn->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->bind_param('s', $user_id);
$stmt->execute();
$uInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();

$username = $uInfo['username'];
$email = $uInfo['email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $q1 = $_POST['q1'] ?? '';
    $ans1 = trim($_POST['ans1']);
    $q2 = $_POST['q2'] ?? '';
    $ans2 = trim($_POST['ans2']);
    $q3 = $_POST['q3'] ?? '';
    $ans3 = trim($_POST['ans3']);

    if (empty($q1) || empty($ans1) || empty($q2) || empty($ans2) || empty($q3) || empty($ans3)) {
        $error = "Please select all questions and provide answers.";
    }
    else {
        // Fetch saved questions and hashed answers
        $stmt = $conn->prepare("SELECT secure_question, secure_answer, secure_question2, secure_answer2, secure_question3, secure_answer3 FROM users WHERE id = ?");
        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $saved = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verify all questions AND answers
        $qMatch = ($q1 === $saved['secure_question'] && $q2 === $saved['secure_question2'] && $q3 === $saved['secure_question3']);
        $aMatch = (password_verify($ans1, $saved['secure_answer']) &&
                  password_verify($ans2, $saved['secure_answer2']) &&
                  password_verify($ans3, $saved['secure_answer3']));

        if ($qMatch && $aMatch) {
            $_SESSION['security_verified'] = true;
            header('Location: forgot_password_reset.php');
            exit;
        }
        else {
            $error = "Security validation failed. Please check your questions and answers.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <title>Security Questions - FoodGrab</title>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-left" id="navbarLeft">
            <img src="../../images/logo4.png" alt="FoodGrab logo" class="logo">
            <span class="navbar-text">
                FoodGrab
                <span class="navbar-subtext">Online Food Delivery</span>
            </span>
        </div>
        <div class="navbar-right">
            <a href="./login.php" class="nav-link">Back to Login</a>
        </div>
    </nav>

    <main>
        <div class="form-container">
            <h2>Security Check</h2>
            <div style="background: #f1f5f9; padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; text-align: center; border: 1px solid #e2e8f0; font-family: monospace; font-weight: 800; color: #1e293b;">
                ID NUMBER: <?php echo htmlspecialchars($user_id); ?>
            </div>
            <p>Select your security questions and provide the answers.</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="forgot-form">
                <div class="form-group">
                    <label>Security Question 1</label>
                    <select name="q1" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 10px;">
                        <option value="">-- Choose a Question --</option>
                        <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                        <option value="What is the name of your pet?">What is the name of your pet?</option>
                        <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                        <option value="What was your first car?">What was your first car?</option>
                        <option value="In what city were you born?">In what city were you born?</option>
                    </select>
                    <div class="password-container">
                        <input type="password" name="ans1" id="ans1" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans1" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Security Question 2</label>
                    <select name="q2" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 10px;">
                        <option value="">-- Choose a Question --</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                        <option value="What is your favorite food?">What is your favorite food?</option>
                        <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                        <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                    </select>
                    <div class="password-container">
                        <input type="password" name="ans2" id="ans2" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans2" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Security Question 3</label>
                    <select name="q3" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 10px;">
                        <option value="">-- Choose a Question --</option>
                        <option value="What is your father's middle name?">What is your father's middle name?</option>
                        <option value="What street did you grow up on?">What street did you grow up on?</option>
                        <option value="What is your favorite movie?">What is your favorite movie?</option>
                        <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                        <option value="What year did you graduate high school?">What year did you graduate high school?</option>
                    </select>
                    <div class="password-container">
                        <input type="password" name="ans3" id="ans3" required placeholder="Your answer">
                        <button type="button" class="password-toggle" data-target="ans3" aria-label="Toggle visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Verify Answers</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>
    <script>
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        });
    </script>
</body>
</html>
