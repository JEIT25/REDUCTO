<?php
/**
 * Login Logs Viewer
 * Only accessible by superadmin
 */
session_start();
require_once '../database/db_connect.php';
require_once '../includes/auth.php';

// Only superadmin can access this page
requireRole('superadmin');

// Get filter parameters
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = min(100, max(10, (int) ($_GET['per_page'] ?? 25)));
$offset = ($page - 1) * $per_page;

// Build WHERE clause
$where = [];
$params = [];
$types = '';

if (!empty($status)) {
    $where[] = "ll.login_status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($role)) {
    $where[] = "ll.user_role = ?";
    $params[] = $role;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(ll.username LIKE ? OR ll.user_id LIKE ?)";
    $search_param = '%' . $conn->real_escape_string($search) . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($date_from)) {
    $where[] = "ll.login_time >= ?";
    $params[] = $date_from . ' 00:00:00';
    $types .= 's';
}

if (!empty($date_to)) {
    $where[] = "ll.login_time <= ?";
    $params[] = $date_to . ' 23:59:59';
    $types .= 's';
}

$where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM login_logs ll $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = (int) $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total / $per_page);

// Get login logs
$sql = "SELECT ll.*, u.firstName, u.lastName 
        FROM login_logs ll 
        LEFT JOIN users u ON ll.user_id = u.id 
        $where_sql 
        ORDER BY ll.login_time DESC 
        LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="../../css/serve_asset.php?file=admin.css">
    <title>Login Logs - FoodGrab</title>
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
            <h1>Login Logs</h1>
            
            <!-- Filters -->
            <div class="filter-section">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status">
                                <option value="">All</option>
                                <option value="success" <?php echo $status === 'success' ? 'selected' : ''; ?>>Success</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role">
                                <option value="">All</option>
                                <option value="basic-user" <?php echo $role === 'basic-user' ? 'selected' : ''; ?>>Basic User</option>
                                <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo $role === 'superadmin' ? 'selected' : ''; ?>>Super Admin</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="search">Search:</label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Username or ID">
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="date_from">From:</label>
                            <input type="date" id="date_from" name="date_from" 
                                   value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="date_to">To:</label>
                            <input type="date" id="date_to" name="date_to" 
                                   value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-primary">Filter</button>
                            <a href="login_logs.php" class="btn-secondary">Clear</a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="stats-section">
                <div class="stat-card">
                    <h3>Total Logins</h3>
                    <p class="stat-number"><?php echo number_format($total); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Success Rate</h3>
                    <p class="stat-number">
                        <?php 
                        $success_count = 0;
                        $logs->data_seek(0);
                        while ($log = $logs->fetch_assoc()) {
                            if ($log['login_status'] === 'success') $success_count++;
                        }
                        $logs->data_seek(0);
                        $success_rate = $total > 0 ? round(($success_count / $total) * 100, 1) : 0;
                        echo $success_rate . '%';
                        ?>
                    </p>
                </div>
                <div class="stat-card">
                    <h3>Failed Attempts</h3>
                    <p class="stat-number"><?php echo number_format($total - $success_count); ?></p>
                </div>
            </div>

            <!-- Logs Area -->
            <div class="Area-container">
                <table class="admin-Area">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                            <th>Failure Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($log = $logs->fetch_assoc()): ?>
                            <tr class="<?php echo $log['login_status']; ?>">
                                <td><?php echo date('M j, Y g:i:s A', strtotime($log['login_time'])); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($log['username']); ?>
                                    <?php if ($log['firstName']): ?>
                                        <br><small><?php echo htmlspecialchars($log['firstName'] . ' ' . $log['lastName']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $log['user_role']; ?>">
                                        <?php echo ucfirst($log['user_role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $log['login_status']; ?>">
                                        <?php echo ucfirst($log['login_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></td>
                                <td class="user-agent">
                                    <?php 
                                    $user_agent = $log['user_agent'] ?? '';
                                    if (strlen($user_agent) > 50) {
                                        echo htmlspecialchars(substr($user_agent, 0, 50)) . '...';
                                    } else {
                                        echo htmlspecialchars($user_agent);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['failure_reason'] ?? ''); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $current_url = $_SERVER['REQUEST_URI'];
                    $url_parts = parse_url($current_url);
                    parse_str($url_parts['query'] ?? '', $query_params);
                    unset($query_params['page']);
                    $base_url = $url_parts['path'] . '?' . http_build_query($query_params);
                    ?>
                    
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page - 1); ?>" class="page-btn">Previous</a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                        (<?php echo number_format($total); ?> total records)
                    </span>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url . '&page=' . ($page + 1); ?>" class="page-btn">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="footer-bottom">
            <p>All rights reserved &copy; 2026</p>
        </div>
    </footer>

    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            min-width: 150px;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #666;
            font-size: 14px;
        }
        
        .stat-number {
            margin: 0;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .user-agent {
            max-width: 200px;
            font-size: 12px;
        }
        
        .badge-success { background-color: #10b981; }
        .badge-failed { background-color: #ef4444; }
        .badge-consumer { background-color: #6b7280; }
        .badge-admin { background-color: #3b82f6; }
        .badge-superadmin { background-color: #dc2626; }
        
        tr.success {
            background-color: rgba(16, 185, 129, 0.05);
        }
        
        tr.failed {
            background-color: rgba(239, 68, 68, 0.05);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
        }
        
        .page-btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .page-info {
            color: #666;
        }
    </style>
</body>
</html>

