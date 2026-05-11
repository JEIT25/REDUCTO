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
$currentPage = 'order_food';
$user = $_SESSION['user'];
$delivery_address = trim(
    ($user['purok'] ?? '') . ', ' .
    ($user['barangay'] ?? '') . ', ' .
    ($user['city'] ?? '') . ', ' .
    ($user['province'] ?? '') . ' ' .
    ($user['zipCode'] ?? '') . ', ' .
    ($user['country'] ?? '')
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=order_food.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php $showSidebarToggle = true; include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Order Food</h1>
            <p class="page-subtitle">Choose a playground to see the Packages and add items to your cart.</p>
            <div id="playgroundsView" class="view">
                <p class="muted" style="margin-bottom: 1.5rem;">Select a playground below to browse its Packages.</p>
                <div id="playgroundsList" class="playgrounds-grid"></div>
            </div>
            <div id="PackagesView" class="view" style="display:none;">
                <button type="button" class="back-btn" id="backToplaygrounds">&larr; Back to playgrounds</button>
                <h2 id="PackagesplaygroundName"></h2>
                <div id="PackagesList" class="Packages-grid"></div>
            </div>
            <div id="checkoutView" class="view" style="display:none;">
                <button type="button" class="back-btn" id="backToPackages">&larr; Back to Packages</button>
                <h2>Checkout</h2>
                <form id="checkoutForm" class="checkout-form">
                    <div class="form-group">
                        <label for="delivery_address">Delivery address</label>
                        <textarea id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($delivery_address); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="notes">Order notes (optional)</label>
                        <input type="text" id="notes" name="notes" placeholder="e.g. No onions">
                    </div>
                    <div class="form-group">
                        <label>Payment method</label>
                        <div id="paymentMethodsList"></div>
                        <p class="muted small">Or pay Cash on Delivery.</p>
                    </div>
                    <div class="cart-summary">
                        <p><strong>Total: ₱<span id="checkoutTotal">0</span></strong></p>
                        <button type="submit" class="submitBtn">Place Order</button>
                    </div>
                </form>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();
    </script>
    <script>
        window.BASE_URL = '<?php echo $baseUrl; ?>';
        window.USER_ID = '<?php echo htmlspecialchars($user['id']); ?>';
    </script>
    <script src="<?php echo $basePath; ?>js/serve_asset.php?file=order_food.js"></script>
</body>
</html>


