<?php
/**
 * Admin Dashboard (Index)
 * Central overview for administrative tasks and playground management.
 */
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

require_once __DIR__ . '/../database/db_connect.php';

$stats = [
    'playgrounds' => 0,
    'Areas' => 0,
    'bookings_total' => 0,
    'bookings_pending' => 0,
    'bookings_confirmed' => 0,
    'consumers' => 0,
];

// Fetch Stats
$res = $conn->query("SELECT COUNT(*) as n FROM playgrounds WHERE is_active = 1");
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

// Latest bookings (5)
$latestbookings = $conn->query(
    "SELECT r.id, r.booking_date, r.booking_time, r.status, u.firstName, u.lastName, rest.name AS playground_name
     FROM bookings r
     JOIN users u ON r.user_id = u.id
     JOIN playgrounds rest ON r.playground_id = rest.id
     ORDER BY r.created_at DESC LIMIT 5"
);

// Latest Play Areas (5)
$latestables = $conn->query(
    "SELECT rt.id, rt.area_name, rt.capacity, rt.is_available, rest.name AS playground_name
     FROM play_areas rt
     JOIN playgrounds rest ON rt.playground_id = rest.id
     ORDER BY rt.created_at DESC LIMIT 5"
);

$pageTitle = 'Admin Dashboard';
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
        <?php $currentPage = 'admin_dashboard'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Quick overview of playgrounds, play areas, and recent booking activity.</p>

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
                    <!-- Latest Bookings Widget -->
                    <div class="widget-card">
                        <div class="widget-header">
                            <h2 class="section-title"><i class="fa-solid fa-calendar-check"></i> Latest Bookings</h2>
                            <a href="bookings.php" class="view-all-link">View All</a>
                        </div>
                        <?php if ($latestbookings && $latestbookings->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Guest</th>
                                        <th>Playground</th>
                                        <th style="text-align:right;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($r = $latestbookings->fetch_assoc()):
                                        $sClass = match ($r['status']) {
                                            'confirmed' => 'status-ok',
                                            'completed' => 'status-ok',
                                            'cancelled' => 'status-trash',
                                            default => 'status-pending',
                                        };
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="table-primary-text"><?php echo htmlspecialchars($r['firstName'] . ' ' . $r['lastName']); ?></span>
                                                <span class="table-secondary-text"><?php echo date('M j, g:i A', strtotime($r['booking_date'] . ' ' . $r['booking_time'])); ?></span>
                                            </td>
                                            <td><span class="table-secondary-text" style="font-weight:700;"><?php echo htmlspecialchars($r['playground_name']); ?></span></td>
                                            <td style="text-align:right;"><span class="status-badge <?php echo $sClass; ?>"><?php echo strtoupper($r['status']); ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-widget">
                                <i class="fa-solid fa-calendar-xmark"></i>
                                <p>No recent bookings.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Latest Play Areas Widget -->
                    <div class="widget-card">
                        <div class="widget-header">
                            <h2 class="section-title"><i class="fa-solid fa-chair"></i> Latest Areas</h2>
                            <a href="play_areas.php" class="view-all-link">View All</a>
                        </div>
                        <?php if ($latestables && $latestables->num_rows > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Area Name</th>
                                        <th>Capacity</th>
                                        <th style="text-align:right;">Available</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($t = $latestables->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="table-primary-text"><?php echo htmlspecialchars($t['area_name']); ?></span>
                                                <span class="table-secondary-text"><?php echo htmlspecialchars($t['playground_name']); ?></span>
                                            </td>
                                            <td><span class="table-secondary-text" style="font-weight:800;"><i class="fa-solid fa-users" style="margin-right:6px; font-size:0.7rem;"></i><?php echo $t['capacity']; ?></span></td>
                                            <td style="text-align:right;"><span class="status-badge <?php echo $t['is_available'] ? 'status-ok' : 'status-trash'; ?>"><?php echo $t['is_available'] ? 'YES' : 'NO'; ?></span></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-widget">
                                <i class="fa-solid fa-chair"></i>
                                <p>No play areas found.</p>
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
