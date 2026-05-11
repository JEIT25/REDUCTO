<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin', 'superadmin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_action'])) {
    session_unset();
    session_destroy();
    header('Location: ' . getBaseUrl() . '/php/forms/login.php');
    exit;
}
$base = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin</title>
    <link rel="stylesheet" href="../../css/design-system.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/order_history.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_orders';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <h1>Manage Orders</h1>
            <div id="ordersArea"></div>
        </main>
        <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    </div>
    <script>
        window.BASE_URL = '<?php echo $base; ?>';
        const api = window.BASE_URL + '/php/database';
        const statuses = ['pending','confirmed','preparing','out_for_delivery','delivered','cancelled'];
        let currentPage = 1;

        function loadOrders(page = 1) {
            currentPage = page;
            fetch(api + '/admin_orders_list.php?page=' + page, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    const totalPages = data.pagination?.total_pages || 1;

                    if (!data.orders.length) {
                        document.getElementById('ordersArea').innerHTML = `
                            <div class="empty-state" style="padding: 3rem; text-align: center; border: 2px dashed var(--border-light); border-radius: var(--radius-lg);">
                                <p style="color: var(--text-muted); margin: 0;">No orders found.</p>
                            </div>`;
                        return;
                    }

                    let html = '<table class="orders-table" style="width:100%; border-collapse:collapse;"><thead>' +
                        '<tr style="border-bottom: 2px solid var(--bg-body); text-align: left; color: var(--text-muted);">' +
                        '<th style="padding: 1rem;">ID</th><th style="padding: 1rem;">playground</th><th style="padding: 1rem;">Customer</th><th style="padding: 1rem;">Total</th><th style="padding: 1rem;">Status</th><th style="padding: 1rem;">Date</th><th style="padding: 1rem;">Update</th></tr></thead><tbody>';

                    data.orders.forEach(o => {
                        const sc = o.status === 'delivered' ? 'status-ok' : o.status === 'cancelled' ? 'status-trash' : 'status-pending';
                        const dateParams = { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
                        const formattedDate = new Date(o.created_at).toLocaleString('en-US', dateParams);

                        html += `<tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 1rem; font-family: monospace; font-weight: 600; color: var(--primary-color);">#${o.id}</td>
                            <td style="padding: 1rem; font-weight: 500;">${escapeHtml(o.playground_name)}</td>
                            <td style="padding: 1rem; color: var(--text-muted);">User #${escapeHtml(o.user_id)}</td>
                            <td style="padding: 1rem; font-weight: 700;">₱${parseFloat(o.total_amount).toFixed(2)}</td>
                            <td style="padding: 1rem;"><span class="status-badge no-dot ${sc}">${o.status}</span></td>
                            <td style="padding: 1rem; color: var(--text-muted); font-size: 0.9rem;">${formattedDate}</td>
                            <td style="padding: 1rem;">
                                <select class="status-select input-field" style="padding: 0.35rem 0.75rem; font-size: 0.9rem; border-radius: 6px; border: 1px solid var(--border-medium); cursor: pointer;" data-order-id="${o.id}">
                                    ${statuses.map(s => '<option value="'+s+'"'+(s===o.status?' selected':'')+'>'+s.charAt(0).toUpperCase() + s.slice(1).replace(/_/g, ' ')+'</option>').join('')}
                                </select>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';

                     html += `<div class="pagination-controls" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                        <span class="pagination-info" style="color: var(--text-muted); font-size: 0.9rem;">Page <strong>${currentPage}</strong> of <strong>${totalPages}</strong></span>
                        <div style="display:flex; gap:0.5rem;">
                            <button class="btn-secondary" onclick="loadOrders(currentPage-1)" ${currentPage<=1?'disabled':''} style="display: flex; align-items: center; gap: 0.5rem;">
                                <i class="fa-solid fa-chevron-left"></i> Previous
                            </button>
                            <button class="btn-secondary" onclick="loadOrders(currentPage+1)" ${currentPage>=totalPages?'disabled':''} style="display: flex; align-items: center; gap: 0.5rem;">
                                Next <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>`;

                    document.getElementById('ordersArea').innerHTML = html;

                    document.querySelectorAll('.status-select').forEach(sel => {
                        sel.addEventListener('change', () => {
                            const fd = new FormData();
                            fd.append('order_id', sel.dataset.orderId);
                            fd.append('status', sel.value);
                            fetch(api + '/admin_order_status.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                                .then(r => r.json())
                                .then(d => {
                                    if (d.success) {
                                        loadOrders(currentPage); // Reload to update status badge correctly
                                    }
                                });
                        });
                    });
                })
                .catch(() => document.getElementById('ordersArea').innerHTML = '<p class="muted">Error loading orders.</p>');
        }

        loadOrders();
        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    </script>
</body>
</html>


