<?php
/**
 * Admin Account Creation Request
 * Allows superadmin to create admin/superadmin accounts
 */
session_start();
require_once '../database/db_connect.php';
require_once '../includes/auth.php';

// Only superadmin can access this page
requireRole('superadmin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_username = trim($_POST['username']);
    $target_email = trim($_POST['email']);
    $target_firstName = trim($_POST['firstName']);
    $target_lastName = trim($_POST['lastName']);
    $target_role = $_POST['role'];
    $reason = trim($_POST['reason']);
    
    // Validation
    if (empty($target_username) || empty($target_email) || empty($target_firstName) || empty($target_lastName)) {
        $error = 'All required fields must be filled';
    } elseif (!filter_var($target_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (!in_array($target_role, ['admin', 'superadmin'])) {
        $error = 'Invalid role selected';
    } else {
        // Check if username already exists
        $stmt = $conn->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->bind_param('s', $target_username);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            $stmt->close();
            
            // Check if email already exists
            $stmt = $conn->prepare("SELECT 1 FROM users WHERE email = ?");
            $stmt->bind_param('s', $target_email);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                $error = 'Email already exists';
            } else {
                $stmt->close();
                
                // Create admin creation request
                $requested_by = $_SESSION['user']['id'];
                
                $stmt = $conn->prepare("INSERT INTO admin_creation_requests 
                                       (requested_by, target_username, target_email, target_role, 
                                        target_firstName, target_lastName, reason) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssssss', $requested_by, $target_username, $target_email, 
                                 $target_role, $target_firstName, $target_lastName, $reason);
                
                if ($stmt->execute()) {
                    $success = 'Admin account creation request submitted successfully!';
                } else {
                    $error = 'Failed to submit request. Please try again.';
                }
                $stmt->close();
            }
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
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=admin.css">
    <title>Create Admin Account - FoodGrab</title>
</head>

<body>
    <nav class="navbar">
        <div class="navbar-left" id="navbarLeft">
            <img src="../../images/logo4.png" alt="FoodGrab logo" class="logo">
            <span class="navbar-text">
                FoodGrab
                <span class="navbar-subtext">Admin Panel</span>
            </span>
        </div>
        <div class="navbar-right">
            <span class="welcome-text">Welcome, <?php echo htmlspecialchars($_SESSION['user']['firstName']); ?></span>
            <a href="../auth/dashboard.php" class="nav-link">Dashboard</a>
            <a href="../auth/logout.php" class="nav-link">Logout</a>
        </div>
    </nav>

    <main class="admin-main">
        <div class="admin-container">
            <h1>Create Admin Account</h1>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="admin-form">
                <form method="POST">
                    <fieldset>
                        <legend>Account Information</legend>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name *</label>
                                <input type="text" id="firstName" name="firstName" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="lastName">Last Name *</label>
                                <input type="text" id="lastName" name="lastName" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" id="username" name="username" required 
                                       placeholder="Choose a unique username">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">Account Role *</label>
                                <select id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="admin">Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    
                    <fieldset>
                        <legend>Additional Information</legend>
                        
                        <div class="form-group">
                            <label for="reason">Reason for Creation *</label>
                            <textarea id="reason" name="reason" rows="4" required 
                                      placeholder="Explain why this admin account is needed..."></textarea>
                        </div>
                    </fieldset>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Submit Request</button>
                        <a href="admin_requests.php" class="btn-secondary">View Requests</a>
                    </div>
                </form>
            </div>
            
            <div class="info-box">
                <h3>Important Notes:</h3>
                <ul>
                    <li>The new admin will receive an email with temporary password</li>
                    <li>Admin accounts will have access to playground and order management</li>
                    <li>Super admin accounts will have full system access</li>
                    <li>All admin account creations are logged for security</li>
                    <li>The new admin must change their password on first login</li>
                </ul>
            </div>
        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>
</body>
</html>

