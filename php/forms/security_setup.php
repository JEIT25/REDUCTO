<?php
session_start();
require_once __DIR__ . '/../database/db_connect.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Check if already set
$check = $conn->prepare("SELECT secure_question FROM users WHERE id = ?");
$check->bind_param('s', $userId);
$check->execute();
$res = $check->get_result()->fetch_assoc();
if ($res && !empty($res['secure_question'])) {
    // Already setup, redirect to dashboard
    $base = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/REDUCTO';
    if ($user['role'] === 'admin') header('Location: ' . $base . '/php/admin/index.php');
    elseif ($user['role'] === 'superadmin') header('Location: ' . $base . '/php/superadmin/index.php');
    else header('Location: ' . $base . '/php/auth/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Setup - LittleLands</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 20px;
        }
        .setup-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 550px;
        }
        .setup-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .setup-header i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .setup-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            color: #1e293b;
            margin: 0;
        }
        .setup-header p {
            color: #64748b;
            margin-top: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        .input-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .password-container {
            position: relative;
        }
        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            cursor: pointer;
            transition: color 0.2s;
        }
        .pw-toggle:hover {
            color: var(--primary-color);
        }
        .btn-primary {
            width: 100%;
            padding: 0.875rem;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s;
            margin-top: 1rem;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: none;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body>
    <div class="setup-card">
        <div class="setup-header">
            <i class="fa-solid fa-shield-halved"></i>
            <h1>Security Setup</h1>
            <p>Please configure your security questions to continue. This is required for account recovery.</p>
        </div>

        <div id="errorAlert" class="alert alert-error"></div>

        <form id="setupForm">
            <div class="form-group">
                <label>Security Question 1</label>
                <select name="secure_question" id="sq1" class="input-field" required>
                    <option value="">-- Select Question --</option>
                    <option value="Who is your bestfriend in elementary?">Who is your bestfriend in elementary?</option>
                    <option value="What is the name of your pet?">What is the name of your pet?</option>
                    <option value="Who is your favorite teacher in highschool?">Who is your favorite teacher in highschool?</option>
                    <option value="What was your first car?">What was your first car?</option>
                    <option value="In what city were you born?">In what city were you born?</option>
                </select>
            </div>
            <div class="form-group">
                <label>Answer 1</label>
                <div class="password-container">
                    <input type="password" name="secure_answer" id="sa1" class="input-field" required>
                    <i class="fa-solid fa-eye pw-toggle" onclick="toggleVisibility('sa1', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Security Question 2</label>
                <select name="secure_question2" id="sq2" class="input-field" required>
                    <option value="">-- Select Question --</option>
                    <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                    <option value="What elementary school did you attend?">What elementary school did you attend?</option>
                    <option value="What is your favorite food?">What is your favorite food?</option>
                    <option value="What was your childhood nickname?">What was your childhood nickname?</option>
                    <option value="What is the name of your best friend?">What is the name of your best friend?</option>
                </select>
            </div>
            <div class="form-group">
                <label>Answer 2</label>
                <div class="password-container">
                    <input type="password" name="secure_answer2" id="sa2" class="input-field" required>
                    <i class="fa-solid fa-eye pw-toggle" onclick="toggleVisibility('sa2', this)"></i>
                </div>
            </div>

            <div class="form-group">
                <label>Security Question 3</label>
                <select name="secure_question3" id="sq3" class="input-field" required>
                    <option value="">-- Select Question --</option>
                    <option value="What is your father's middle name?">What is your father's middle name?</option>
                    <option value="What street did you grow up on?">What street did you grow up on?</option>
                    <option value="What is your favorite movie?">What is your favorite movie?</option>
                    <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                    <option value="What year did you graduate high school?">What year did you graduate high school?</option>
                </select>
            </div>
            <div class="form-group">
                <label>Answer 3</label>
                <div class="password-container">
                    <input type="password" name="secure_answer3" id="sa3" class="input-field" required>
                    <i class="fa-solid fa-eye pw-toggle" onclick="toggleVisibility('sa3', this)"></i>
                </div>
            </div>

            <button type="submit" class="btn-primary">
                Save & Continue <i class="fa-solid fa-circle-check"></i>
            </button>
        </form>
    </div>

    <script>
        function toggleVisibility(id, icon) {
            const el = document.getElementById(id);
            if (el.type === 'password') {
                el.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                el.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('setupForm').onsubmit = function(e) {
            e.preventDefault();
            const alert = document.getElementById('errorAlert');
            alert.style.display = 'none';

            const fd = new FormData(this);
            fetch('../database/action_save_security.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    window.location.href = d.redirect;
                } else {
                    alert.textContent = d.error || 'Failed to save security questions.';
                    alert.style.display = 'block';
                }
            })
            .catch(err => {
                alert.textContent = 'A network error occurred.';
                alert.style.display = 'block';
            });
        };
    </script>
</body>
</html>
