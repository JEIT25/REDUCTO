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
$currentPage = 'payment_methods';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Methods - FoodGrab</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=order_food.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>css/serve_asset.php?file=payment_methods.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php $showSidebarToggle = true; include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
        <?php include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1 class="page-title">Payment Methods</h1>
            <p class="page-subtitle">Pay with GCash or Cash on Delivery at checkout. Add your GCash number below to pay via GCash.</p>
            <div class="payment-methods-section">
                <h2 class="pm-section-title">Your saved payment methods</h2>
                <p class="pm-section-hint">Cash on Delivery is always available at checkout—no need to add it here.</p>
                <div id="paymentMethodsList" class="pm-list pm-cards"></div>
            </div>
            <div class="payment-methods-section add-pm-section">
                <h2 class="pm-section-title">Add GCash</h2>
                <p class="pm-section-hint">Add your GCash mobile number to pay at checkout. You’ll confirm payment in the app before the order is placed.</p>
                <form id="addPaymentForm" class="pm-form">
                    <input type="hidden" name="type" value="gcash">
                    <div class="form-group">
                        <label for="pm_label">Mobile number</label>
                        <input type="text" id="pm_label" name="label" required placeholder="e.g. 09171234567" maxlength="20" inputmode="tel">
                    </div>
                    <div class="form-group">
                        <label for="pm_details">Account name (optional)</label>
                        <input type="text" id="pm_details" name="details" placeholder="Name on GCash account">
                    </div>
                    <div class="form-group form-group-checkbox">
                        <label><input type="checkbox" name="is_default" value="1"> Set as default for checkout</label>
                    </div>
                    <button type="submit" class="submitBtn btn-pm-save">Add GCash</button>
                </form>
            </div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>
        (function(){ var o=document.getElementById('sidebarOverlay'),t=document.getElementById('sidebarToggle'); if(t&&o){ t.addEventListener('click',function(){ document.body.classList.toggle('sidebar-open'); o.classList.toggle('is-open',document.body.classList.contains('sidebar-open')); }); o.addEventListener('click',function(){ document.body.classList.remove('sidebar-open'); o.classList.remove('is-open'); }); } })();
    </script>
    <script>window.BASE_URL = '<?php echo $baseUrl; ?>';</script>
    <script src="<?php echo $basePath; ?>js/serve_asset.php?file=payment_methods.js"></script>
</body>
</html>
