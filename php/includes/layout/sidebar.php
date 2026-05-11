<?php
/**
 * LittleLands Role-based sidebar — booking System
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($user)) {
    $user = $_SESSION['user'] ?? null;
}
$userRole = $user['role'] ?? 'basic-user';

if (!function_exists('getBaseUrl')) {
    function getBaseUrl()
    {
        return 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/REDUCTO';
    }
}
$baseUrl = getBaseUrl();

function isActive($page, $current)
{
    return $current === $page ? 'active' : '';
}
$currentPage = $currentPage ?? '';

$fullName = trim(
    htmlspecialchars($user['firstName'] ?? 'Guest') . ' ' .
    htmlspecialchars($user['middleInitial'] ?? '') . ' ' .
    htmlspecialchars($user['lastName'] ?? '')
);
$initials = '';
if (!empty($user['firstName']))
    $initials .= strtoupper($user['firstName'][0]);
if (!empty($user['lastName']))
    $initials .= strtoupper($user['lastName'][0]);
if (!$initials)
    $initials = 'G';

$roleBadgeColors = [
    'superadmin' => 'background:#ede9fe; color:#7c3aed;',
    'admin' => 'background:#fef3c7; color:#d97706;',
    'basic-user' => 'background:#f0fdfa; color:#1a5653;',
];
$roleIcon = [
    'superadmin' => 'fa-user-gear', // System Manager
    'admin' => 'fa-user-tie', // Playground Manager
    'basic-user' => 'fa-child-reaching', // Parent/User
];
$badgeStyle = $roleBadgeColors[$userRole] ?? 'background:#f3f4f6; color:#6b7280;';
$icon = $roleIcon[$userRole] ?? 'fa-user';
?>
<aside class="dashboard-sidebar">
    <div class="sidebar-inner">
        <div class="profile-section" style="display:flex; flex-direction:column; align-items:center;">
            <div class="profile-avatar">
                <?php echo $initials; ?>
            </div>
            <p class="profile-name"><?php echo $fullName; ?></p>
            <span class="profile-role" style="<?php echo $badgeStyle; ?>">
                <i class="fa-solid <?php echo $icon; ?>" style="font-size:0.7rem;"></i>
                <?php echo ucfirst($userRole); ?>
            </span>
        </div>
        <nav class="sidebar-nav">
            <?php if ($userRole === 'superadmin'): ?>
                <p class="muted small">Overview</p>
                <a href="<?php echo $baseUrl; ?>/php/superadmin/index.php"
                    class="<?php echo isActive('superadmin_dashboard', $currentPage); ?>">
                    <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
                </a>

                <p class="muted small">Management</p>
                <a href="<?php echo $baseUrl; ?>/php/admin/playgrounds.php"
                    class="<?php echo isActive('admin_playgrounds', $currentPage); ?>">
                    <i class="fa-solid fa-tent"></i> <span>Playgrounds</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/admin/play_areas.php"
                    class="<?php echo isActive('admin_Areas', $currentPage); ?>">
                    <i class="fa-solid fa-shapes"></i> <span>Play Areas</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/admin/bookings.php"
                    class="<?php echo isActive('admin_bookings', $currentPage); ?>">
                    <i class="fa-solid fa-calendar-check"></i> <span>Bookings</span>
                </a>

                <p class="muted small">Users & Roles</p>
                <a href="<?php echo $baseUrl; ?>/php/superadmin/users.php"
                    class="<?php echo isActive('superadmin_users', $currentPage); ?>">
                    <i class="fa-solid fa-users-gear"></i> <span>Manage Accounts</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/superadmin/requests.php"
                    class="<?php echo isActive('superadmin_requests', $currentPage); ?>">
                    <i class="fa-solid fa-clipboard-check"></i> <span>Manage Requests</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/superadmin/logs.php"
                    class="<?php echo isActive('superadmin_logs', $currentPage); ?>">
                    <i class="fa-solid fa-list-check"></i> <span>Logs</span>
                </a>

            <?php elseif ($userRole === 'admin'): ?>
                <p class="muted small">Overview</p>
                <a href="<?php echo $baseUrl; ?>/php/admin/index.php"
                    class="<?php echo isActive('admin_dashboard', $currentPage); ?>">
                    <i class="fa-solid fa-chart-line"></i> <span>Dashboard</span>
                </a>

                <p class="muted small">Playground</p>
                <a href="<?php echo $baseUrl; ?>/php/admin/playgrounds.php"
                    class="<?php echo isActive('admin_playgrounds', $currentPage); ?>">
                    <i class="fa-solid fa-tent"></i> <span>Playgrounds</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/admin/play_areas.php"
                    class="<?php echo isActive('admin_Areas', $currentPage); ?>">
                    <i class="fa-solid fa-shapes"></i> <span>Play Areas</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/admin/bookings.php"
                    class="<?php echo isActive('admin_bookings', $currentPage); ?>">
                    <i class="fa-solid fa-calendar-check"></i> <span>Bookings</span>
                </a>

                <p class="muted small">People</p>
                <a href="<?php echo $baseUrl; ?>/php/admin/consumers.php"
                    class="<?php echo isActive('admin_consumers', $currentPage); ?>">
                    <i class="fa-solid fa-users"></i> <span>Manage Basic Users</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/admin/admin_requests.php"
                    class="<?php echo isActive('admin_requests', $currentPage); ?>">
                    <i class="fa-solid fa-clipboard-check"></i> <span>Manage Requests</span>
                </a>

            <?php else: ?>
                <!-- Basic User -->
                <p class="muted small">Packages</p>
                <a href="<?php echo $baseUrl; ?>/php/auth/dashboard.php"
                    class="<?php echo isActive('consumer_dashboard', $currentPage);
                    echo isActive('dashboard', $currentPage); ?>">
                    <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/forms/browse_playgrounds.php"
                    class="<?php echo isActive('browse_playgrounds', $currentPage); ?>">
                    <i class="fa-solid fa-shapes"></i> <span>Browse Playgrounds</span>
                </a>
                <a href="<?php echo $baseUrl; ?>/php/forms/my_bookings.php"
                    class="<?php echo isActive('my_bookings', $currentPage); ?>">
                    <i class="fa-solid fa-calendar-days"></i> <span>My Bookings</span>
                </a>
            <?php endif; ?>

            <p class="muted small">Account</p>
            <a href="<?php echo $baseUrl; ?>/php/forms/profile.php"
                class="<?php echo isActive('profile', $currentPage); ?>">
                <i class="fa-solid fa-id-card"></i> <span>My Profile</span>
            </a>
            <a href="<?php echo $baseUrl; ?>/php/auth/logout.php" class="danger-link">
                <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
            </a>
        </nav>
    </div>
</aside>