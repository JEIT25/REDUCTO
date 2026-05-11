<?php
require_once __DIR__ . '/../includes/auth_check.php';
requireRole('basic-user');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_action'])) {
    session_unset();
    session_destroy();
    header('Location: ' . getBaseUrl() . '/php/auth/login.php');
    exit;
}
$basePath = getBasePath(__FILE__);
$baseUrl = getBaseUrl();
$currentPage = 'order_history';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=order_food.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=order_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php $showSidebarToggle = true; include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Order History</h1>
            <p class="page-subtitle">Your past orders. Search, filter by status, and open an order to see details or track delivery.</p>
            <div class="order-history-toolbar">
                <div class="order-history-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="text" id="orderSearch" placeholder="Search by Order ID or playground name..." autocomplete="off">
                </div>
                <div class="order-history-filters" role="group" aria-label="Filter by status">
                    <button type="button" class="filter-tab active" data-status="">All</button>
                    <button type="button" class="filter-tab" data-status="pending">Pending</button>
                    <button type="button" class="filter-tab" data-status="confirmed">Confirmed</button>
                    <button type="button" class="filter-tab" data-status="preparing">Preparing</button>
                    <button type="button" class="filter-tab" data-status="out_for_delivery">Out for delivery</button>
                    <button type="button" class="filter-tab" data-status="delivered">Delivered</button>
                    <button type="button" class="filter-tab" data-status="cancelled">Cancelled</button>
                </div>
            </div>
            <div id="ordersList" class="orders-list orders-cards"></div>
            <div id="orderPagination" class="order-pagination" aria-label="Orders pagination"></div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();
    </script>
    <script>window.BASE_URL = '<?php echo $baseUrl; ?>';</script>
    <script src="<?php echo $basePath; ?>js/serve_asset.php?file=order_history.js"></script>
</body>
</html>

