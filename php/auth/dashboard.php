<?php
/**
 * LittleLands Dashboard - All roles.
 * Consumer: booking stats, latest playgrounds, latest bookings, quick links.
 * Admin/Superadmin: management links.
 */
require_once __DIR__ . '/../includes/auth_check.php';

if (isset($_POST['logout_action'])) {
    require_once __DIR__ . '/../database/db_connect.php';
    $logStmt = $conn->prepare("INSERT INTO login_logs (user_id, action) VALUES (?, 'logout')");
    $logStmt->bind_param('s', $_SESSION['user']['id']);
    $logStmt->execute();
    $logStmt->close();
    $conn->close();
    session_destroy();
    header('Location: login.php');
    exit;
}

$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Basic User dashboard data
$consumerStats = null;
$latestbookings = [];
$latestplaygrounds = [];

if ($userRole === 'basic-user' && isset($user['id'])) {
    require_once __DIR__ . '/../database/db_connect.php';
    $uid = $user['id'];
    $consumerStats = [
        'total_bookings' => 0,
        'pending_bookings' => 0,
        'confirmed_bookings' => 0,
        'completed_bookings' => 0,
        'upcoming' => null,
    ];

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM bookings WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $consumerStats['total_bookings'] = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM bookings WHERE user_id = ? AND status = 'pending'");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $consumerStats['pending_bookings'] = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM bookings WHERE user_id = ? AND status = 'confirmed'");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $consumerStats['confirmed_bookings'] = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as n FROM bookings WHERE user_id = ? AND status = 'completed'");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $consumerStats['completed_bookings'] = (int) ($stmt->get_result()->fetch_assoc()['n'] ?? 0);
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT r.*, rest.name AS playground_name FROM bookings r JOIN playgrounds rest ON rest.id = r.playground_id WHERE r.user_id = ? AND r.status IN ('pending','confirmed') AND r.booking_date >= CURDATE() ORDER BY r.booking_date ASC, r.booking_time ASC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res)
            $consumerStats['upcoming'] = $res;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT r.*, rest.name AS playground_name FROM bookings r JOIN playgrounds rest ON rest.id = r.playground_id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 5");
    if ($stmt) {
        $stmt->bind_param('s', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $latestbookings[] = $row;
        }
        $stmt->close();
    }

    $rstmt = $conn->query("SELECT * FROM playgrounds WHERE is_active = 1 ORDER BY created_at DESC LIMIT 4");
    if ($rstmt) {
        while ($row = $rstmt->fetch_assoc()) {
            $latestplaygrounds[] = $row;
        }
    }

    $conn->close();
}
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - LittleLands</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>/css/serve_asset.php?file=bookings.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php
    $showSidebarToggle = true;
    include __DIR__ . '/../includes/layout/navbar.php';
    ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Welcome, <?php echo htmlspecialchars($user['firstName']); ?>!</h1>
                <p class="page-subtitle">
                    <?php
                    if ($userRole === 'basic-user')
                        echo 'Browse playgrounds, make bookings, and manage your playground adventures.';
                    elseif ($userRole === 'admin')
                        echo 'Manage playgrounds, Areas, and bookings.';
                    else
                        echo 'Manage users, roles, and system settings.';
                    ?>
                </p>

                <?php if ($userRole === 'basic-user'): ?>
                    <?php if ($consumerStats !== null): ?>
                        <section class="dashboard-stats" aria-label="booking summary">
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon bg-blue" aria-hidden="true"><i class="fa-solid fa-calendar-check"></i></div>
                                    <div class="stat-info">
                                        <h3><?php echo $consumerStats['total_bookings']; ?></h3>
                                        <p>Total bookings</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon bg-amber" aria-hidden="true"><i class="fa-solid fa-clock"></i>
                                    </div>
                                    <div class="stat-info">
                                        <h3><?php echo $consumerStats['pending_bookings']; ?></h3>
                                        <p>Pending</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon bg-emerald" aria-hidden="true"><i
                                            class="fa-solid fa-circle-check"></i></div>
                                    <div class="stat-info">
                                        <h3><?php echo $consumerStats['confirmed_bookings']; ?></h3>
                                        <p>Confirmed</p>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon bg-purple" aria-hidden="true"><i
                                            class="fa-solid fa-flag-checkered"></i></div>
                                    <div class="stat-info">
                                        <h3><?php echo $consumerStats['completed_bookings']; ?></h3>
                                        <p>Completed</p>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($consumerStats['upcoming'])):
                                $up = $consumerStats['upcoming']; ?>
                                <div class="latest-booking-section">
                                    <h2 class="section-title">Upcoming booking</h2>
                                    <a href="<?php echo $baseUrl; ?>/php/forms/my_bookings.php"
                                        class="latest-booking-card card-interactive">
                                        <div class="latest-booking-info">
                                            <span
                                                class="latest-booking-playground"><?php echo htmlspecialchars($up['playground_name']); ?></span>
                                            <span class="latest-booking-meta">
                                                <?php echo date('M d, Y', strtotime($up['booking_date'])); ?> at
                                                <?php echo date('g:i A', strtotime($up['booking_time'])); ?>
                                                &middot; <?php echo $up['group_size']; ?> kids
                                                &middot; <span
                                                    class="status-badge status-<?php echo $up['status']; ?>"><?php echo ucfirst($up['status']); ?></span>
                                            </span>
                                        </div>
                                        <span class="latest-booking-link-text">View details <i
                                                class="fa-solid fa-arrow-right"></i></span>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="latest-booking-section">
                                    <h2 class="section-title">Upcoming booking</h2>
                                    <div class="empty-state">
                                        <i class="fa-solid fa-calendar-plus"
                                            style="font-size:2rem;margin-bottom:0.75rem;color:var(--text-muted);"></i>
                                        <p>No upcoming bookings.</p>
                                        <a href="<?php echo $baseUrl; ?>/php/forms/browse_playgrounds.php" class="btn-primary">Browse
                                            playgrounds</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($latestplaygrounds)): ?>
                        <section class="dashboard-section" aria-label="Latest playgrounds">
                            <div class="dashboard-section-header">
                                <h2 class="section-title">Latest playgrounds</h2>
                                <a href="<?php echo $baseUrl; ?>/php/forms/browse_playgrounds.php" class="section-link">View All <i
                                        class="fa-solid fa-arrow-right"></i></a>
                            </div>
                            <div class="dashboard-playgrounds-grid">
                                <?php foreach ($latestplaygrounds as $rest): ?>
                                    <div class="playground-card card-interactive">
                                        <div class="playground-card-img">
                                            <?php if (!empty($rest['image_path'])): ?>
                                                <img src="<?php echo $baseUrl . '/' . htmlspecialchars($rest['image_path']); ?>"
                                                    alt="<?php echo htmlspecialchars($rest['name']); ?>">
                                            <?php else: ?>
                                                <div class="no-img"><i class="fa-solid fa-shapes"></i></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="playground-card-body">
                                            <div class="playground-card-header">
                                                <h3><?php echo htmlspecialchars($rest['name']); ?></h3>
                                                <span class="playground-rating"><i class="fa-solid fa-star"></i>
                                                    <?php echo number_format((float) $rest['rating'], 1); ?></span>
                                            </div>
                                            <p class="playground-type"><i class="fa-solid fa-tag"></i>
                                                <?php echo htmlspecialchars($rest['playground_type'] ?: 'General'); ?></p>
                                            <p class="playground-address"><i class="fa-solid fa-location-dot"></i>
                                                <?php echo htmlspecialchars($rest['address'] ?: 'Address not available'); ?></p>
                                            <div class="playground-meta">
                                                <span><i class="fa-solid fa-clock"></i>
                                                    <?php echo substr($rest['opening_time'], 0, 5); ?> &ndash;
                                                    <?php echo substr($rest['closing_time'], 0, 5); ?></span>
                                                <span
                                                    class="price-range"><?php echo htmlspecialchars($rest['price_range'] ?: '$$'); ?></span>
                                            </div>
                                            <a href="<?php echo $baseUrl; ?>/php/forms/make_booking.php?playground_id=<?php echo $rest['id']; ?>"
                                                class="btn-primary"
                                                style="width:100%;margin-top:1rem;text-decoration:none;text-align:center;display:block;">
                                                <i class="fa-solid fa-calendar-plus"></i> Book Now
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <?php if (!empty($latestbookings)): ?>
                        <section class="dashboard-section" aria-label="Latest bookings">
                            <div class="dashboard-section-header">
                                <h2 class="section-title">Latest bookings</h2>
                                <a href="<?php echo $baseUrl; ?>/php/forms/my_bookings.php" class="section-link">View All <i
                                        class="fa-solid fa-arrow-right"></i></a>
                            </div>
                            <div class="bookings-list">
                                <?php foreach ($latestbookings as $rv): ?>
                                    <div class="booking-card">
                                        <div class="booking-date-badge">
                                            <span class="date-month"><?php echo date('M', strtotime($rv['booking_date'])); ?></span>
                                            <span class="date-day"><?php echo date('d', strtotime($rv['booking_date'])); ?></span>
                                        </div>
                                        <div class="booking-card-body">
                                            <h3><?php echo htmlspecialchars($rv['playground_name']); ?></h3>
                                            <p><i class="fa-solid fa-clock"></i>
                                                <?php echo date('g:i A', strtotime($rv['booking_time'])); ?></p>
                                            <p><i class="fa-solid fa-users"></i> <?php echo $rv['group_size']; ?> kids</p>
                                        </div>
                                        <div class="booking-card-right">
                                            <span
                                                class="status-badge status-<?php echo $rv['status']; ?>"><?php echo ucfirst($rv['status']); ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>



                <?php elseif ($userRole === 'admin'): ?>
                    <h2 class="section-title quick-links-title">Management</h2>
                    <div class="quick-links-grid">
                        <a href="<?php echo $baseUrl; ?>/php/admin/playgrounds.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-tent"></i></div>
                            <span class="ql-label">Manage playgrounds</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/admin/play_areas.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-shapes"></i></div>
                            <span class="ql-label">Manage Areas</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/admin/bookings.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <span class="ql-label">All bookings</span>
                        </a>
                    </div>

                <?php else: ?>
                    <h2 class="section-title quick-links-title">System Administration</h2>
                    <div class="quick-links-grid">
                        <a href="<?php echo $baseUrl; ?>/php/superadmin/users.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-users"></i></div>
                            <span class="ql-label">Users &amp; Roles</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/superadmin/requests.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-user-shield"></i></div>
                            <span class="ql-label">Block Requests</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/superadmin/logs.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-list"></i></div>
                            <span class="ql-label">Login Logs</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/admin/playgrounds.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-tent"></i></div>
                            <span class="ql-label">playgrounds</span>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/php/admin/bookings.php" class="quick-link card-interactive">
                            <div class="ql-icon"><i class="fa-solid fa-calendar-check"></i></div>
                            <span class="ql-label">All bookings</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

    <script>
        // Sidebar toggle
        const toggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.dashboard-sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (toggle && sidebar) {
            toggle.addEventListener('click', () => {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('active');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            });
        }
    </script>
</body>

</html>