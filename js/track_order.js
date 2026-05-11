(function () {
    const api = (window.BASE_URL || '') + '/php/database';
    const content = document.getElementById('trackContent');
    const orderId = window.ORDER_ID;

    const STEPS = ['pending', 'confirmed', 'preparing', 'out_for_delivery', 'delivered'];
    const STEP_LABELS = {
        pending: 'Order placed',
        confirmed: 'Confirmed',
        preparing: 'Preparing',
        out_for_delivery: 'Out for delivery',
        delivered: 'Delivered'
    };

    function statusLabel(s) {
        const map = { pending: 'Pending', confirmed: 'Confirmed', preparing: 'Preparing', out_for_delivery: 'Out for delivery', delivered: 'Delivered', cancelled: 'Cancelled' };
        return map[s] || (s || '').replace(/_/g, ' ');
    }

    function escapeHtml(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function renderTrackEntryAndRecent() {
        content.innerHTML = '<div class="track-order-entry"><p class="track-entry-title">Track your order</p><div class="track-order-form"><input type="text" id="trackOrderIdInput" placeholder="Enter Order ID" autocomplete="off"><button type="button" id="trackOrderIdBtn">Track</button></div><p class="track-entry-hint">Enter your order number to see status and delivery progress.</p></div><div class="track-recent-orders"><h3>Recent orders</h3><div id="trackRecentList" class="track-recent-list">Loading…</div></div>';
        const input = document.getElementById('trackOrderIdInput');
        const btn = document.getElementById('trackOrderIdBtn');
        if (btn && input) {
            btn.addEventListener('click', function () {
                const id = (input.value || '').trim();
                if (id) window.location.href = 'track_order.php?order_id=' + encodeURIComponent(id);
            });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') btn.click();
            });
        }
        fetch(api + '/orders_list.php?per_page=10&page=1', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const listEl = document.getElementById('trackRecentList');
                if (!listEl) return;
                const orders = (data.success && data.orders) ? data.orders : [];
                if (orders.length === 0) {
                    listEl.innerHTML = '<p class="muted">No recent orders. <a href="book_package.php">Order food</a> or <a href="order_history.php">view order history</a>.</p>';
                    return;
                }
                listEl.innerHTML = orders.map(o => {
                    const sc = o.status === 'delivered' ? 'status-ok' : o.status === 'cancelled' ? 'status-cancel' : 'status-pending';
                    return '<a href="track_order.php?order_id=' + encodeURIComponent(o.id) + '" class="track-recent-item">' +
                        '<span class="track-recent-id">Order #' + escapeHtml(String(o.id)) + '</span>' +
                        '<span class="track-recent-playground">' + escapeHtml(o.playground_name || '') + '</span>' +
                        '<span class="status-badge ' + sc + '">' + statusLabel(o.status) + '</span>' +
                        '</a>';
                }).join('');
            })
            .catch(() => {
                const listEl = document.getElementById('trackRecentList');
                if (listEl) listEl.innerHTML = '<p class="muted">Could not load recent orders.</p>';
            });
    }

    function getStepIndex(status) {
        if (status === 'cancelled') return -1;
        const i = STEPS.indexOf(status);
        return i >= 0 ? i : 0;
    }

    function renderTimeline(order) {
        const isCancelled = order.status === 'cancelled';
        const currentIndex = getStepIndex(order.status);
        let stepsHtml = '<div class="track-timeline">';
        STEPS.forEach((step, i) => {
            const done = !isCancelled && i <= currentIndex;
            const current = !isCancelled && i === currentIndex;
            stepsHtml += '<div class="track-step' + (done ? ' done' : '') + (current ? ' current' : '') + '">';
            stepsHtml += '<div class="track-step-marker"></div>';
            stepsHtml += '<div class="track-step-label">' + escapeHtml(STEP_LABELS[step] || step) + '</div>';
            if (i < STEPS.length - 1) stepsHtml += '<div class="track-step-connector"></div>';
            stepsHtml += '</div>';
        });
        stepsHtml += '</div>';
        return stepsHtml;
    }

    function renderDetail(order) {
        const statusClass = order.status === 'delivered' ? 'status-ok' : order.status === 'cancelled' ? 'status-cancel' : 'status-pending';
        const timelineHtml = order.status === 'cancelled'
            ? '<div class="track-timeline-wrap"><p class="status-badge status-cancel">Order cancelled</p></div>'
            : '<div class="track-timeline-wrap">' + renderTimeline(order) + '</div>';
        let html = '<div class="track-order-detail">';
        html += '<p class="track-back"><a href="track_order.php">← Track another order</a> &nbsp;|&nbsp; <a href="order_history.php">Order history</a></p>';
        html += timelineHtml;
        html += '<div class="order-detail-card track-summary-card">';
        html += '<h2>Order #' + escapeHtml(String(order.id)) + '</h2>';
        html += '<p><strong>' + escapeHtml(order.playground_name) + '</strong></p>';
        html += '<p>Status: <span class="status-badge ' + statusClass + '">' + statusLabel(order.status) + '</span></p>';
        html += '<p>Delivery: ' + escapeHtml(order.delivery_address) + '</p>';
        html += '<p>Total: ₱' + parseFloat(order.total_amount).toFixed(2) + '</p>';
        html += '<p class="muted small">Ordered: ' + escapeHtml(order.created_at) + '</p>';
        html += '<h3>Items</h3><ul class="order-items-list">';
        (order.items || []).forEach(i => {
            html += '<li>' + escapeHtml(i.item_name) + ' × ' + (i.quantity || 1) + ' — ₱' + parseFloat(i.subtotal || 0).toFixed(2) + '</li>';
        });
        html += '</ul></div></div>';
        content.innerHTML = html;
    }

    if (orderId) {
        fetch(api + '/order_detail.php?order_id=' + orderId, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.order) renderDetail(data.order);
                else content.innerHTML = '<div class="empty-state"><p class="muted">Order not found.</p><p><a href="track_order.php">Track another order</a> or <a href="order_history.php">view order history</a>.</p></div>';
            })
            .catch(() => { content.innerHTML = '<div class="empty-state"><p class="muted">Could not load order. Try again.</p></div>'; });
    } else {
        renderTrackEntryAndRecent();
    }
})();


