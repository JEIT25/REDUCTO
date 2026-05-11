<?php
/**
 * Superadmin Dashboard (Index)
 * Central overview for system-wide statistics and activity.
 */
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');

// Fetch Stats
require_once __DIR__ . '/../database/db_connect.php';

$stats = [
    'playgrounds' => 0,
    'Areas' => 0,
    'bookings_total' => 0,
    'bookings_pending' => 0,
    'bookings_confirmed' => 0,
    'consumers' => 0,
];

// Optimized stat fetching
$res = $conn->query("SELECT COUNT(*) as n FROM playgrounds");
if ($row = $res->fetch_assoc()) $stats['playgrounds'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM play_areas");
if ($row = $res->fetch_assoc()) $stats['Areas'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM bookings");
if ($row = $res->fetch_assoc()) $stats['bookings_total'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM bookings WHERE status = 'pending'");
if ($row = $res->fetch_assoc()) $stats['bookings_pending'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM bookings WHERE status = 'confirmed'");
if ($row = $res->fetch_assoc()) $stats['bookings_confirmed'] = $row['n'];

$res = $conn->query("SELECT COUNT(*) as n FROM users WHERE role = 'basic-user'");
if ($row = $res->fetch_assoc()) $stats['consumers'] = $row['n'];

// Fetch Latest Pending Requests (Limit 5)
$requests = $conn->query("
    SELECT a.id, a.requested_by, a.target_id, a.reason, a.action_type as request_type, a.created_at, u.username as target_username, u.email as target_email
    FROM approvals a
    JOIN users u ON a.target_id = u.id
    WHERE a.status = 'pending'
    ORDER BY a.created_at DESC LIMIT 5
");

// Fetch Latest Logs (Limit 5)
$logs = $conn->query("SELECT l.*, u.username
                      FROM login_logs l
                      JOIN users u ON l.user_id = u.id
                      ORDER BY l.log_time DESC
                      LIMIT 5");

$pageTitle = 'Superadmin Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LittleLands</title>
    <link rel="stylesheet" href="../../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'superadmin_dashboard'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Dashboard Overview</h1>
                <p class="page-subtitle">Manage LittleLands system statistics and monitor recent activity logs.</p>

                <section class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-teal"><i class="fa-solid fa-tent"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['playgrounds']; ?></h3>
                            <p>Playgrounds</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-gold"><i class="fa-solid fa-chair"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['Areas']; ?></h3>
                            <p>Play Areas</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-blue"><i class="fa-solid fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['bookings_total']; ?></h3>
                            <p>Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-amber"><i class="fa-solid fa-clock"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['bookings_pending']; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-emerald"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['bookings_confirmed']; ?></h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-purple"><i class="fa-solid fa-users"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $stats['consumers']; ?></h3>
                            <p>Consumers</p>
                        </div>
                    </div>
                </section>

                <div class="dashboard-widgets">
                    <!-- Recent Requests Widget -->
                    <div class="widget-card">
                        <div class="widget-header">
                            <h2 class="section-title"><i class="fa-solid fa-clipboard-check"></i> Pending Requests</h2>
                            <a href="requests.php" class="view-all-link">View All</a>
                        </div>
                        <?php if ($requests && $requests->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Target User</th>
                                        <th>Type</th>
                                        <th style="text-align:right;">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $requests->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="table-primary-text">@<?php echo htmlspecialchars($r['target_username']); ?></span>
                                                <span class="table-secondary-text"><?php echo htmlspecialchars($r['target_email']); ?></span>
                                            </td>
                                            <td><span class="status-badge status-pending"><?php echo strtoupper($r['request_type']); ?></span></td>
                                            <td style="text-align:right;"><span class="table-secondary-text" style="font-weight:700;"><?php echo date('M j, Y', strtotime($r['created_at'])); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-widget">
                                <i class="fa-solid fa-clipboard-check"></i>
                                <p>No pending requests.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Recent Activity Widget -->
                    <div class="widget-card">
                        <div class="widget-header">
                            <h2 class="section-title"><i class="fa-solid fa-list-check"></i> Recent Activity</h2>
                            <a href="logs.php" class="view-all-link">View All</a>
                        </div>
                        <?php if ($logs && $logs->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Username</th>
                                        <th style="text-align:right;">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($l = $logs->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="status-badge <?php echo $l['action'] === 'login' ? 'status-ok' : 'status-trash'; ?>">
                                                    <?php echo strtoupper($l['action']); ?>
                                                </span>
                                            </td>
                                            <td><span class="table-primary-text">@<?php echo htmlspecialchars($l['username']); ?></span></td>
                                            <td style="text-align:right;"><span class="table-secondary-text" style="font-weight:700;"><?php echo date('M j, H:i', strtotime($l['log_time'])); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-widget">
                                <i class="fa-solid fa-list"></i>
                                <p>No activity logs found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
</body>
</html>