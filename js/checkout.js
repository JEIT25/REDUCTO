/**
 * Checkout page: delivery, payment (COD or GCash), Place Order.
 * COD: place order directly. GCash: show dummy "Pay with GCash" modal, then place order.
 */
(function () {
    const CART_STORAGE_KEY = 'foodgrab_cart';
    const base = window.BASE_URL || '';
    const api = base + '/php/database';

    function getCart() {
        try {
            const raw = localStorage.getItem(CART_STORAGE_KEY);
            if (!raw) return [];
            const arr = JSON.parse(raw);
            return Array.isArray(arr) ? arr : [];
        } catch (e) { return []; }
    }

    function groupCartByStore(cart) {
        const byStore = {};
        cart.forEach(function (item) {
            const rid = item.playground_id || 0;
            if (!byStore[rid]) byStore[rid] = { playground_id: rid, items: [] };
            byStore[rid].items.push(item);
        });
        return Object.values(byStore);
    }

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    async function loadplaygroundNames() {
        try {
            const res = await fetch(api + '/playgrounds_list.php');
            const data = await res.json();
            if (data.success && data.playgrounds) {
                const map = {};
                data.playgrounds.forEach(function (r) { map[r.id] = r.name || 'Store'; });
                return map;
            }
        } catch (e) {}
        return {};
    }

    async function loadPaymentMethods() {
        try {
            const res = await fetch(api + '/payment_methods_list.php');
            const data = await res.json();
            if (data.success && data.payment_methods) return data.payment_methods;
        } catch (e) {}
        return [];
    }

    function renderSummary(groups, nameMap) {
        const container = document.getElementById('checkoutOrderSummary');
        if (!container) return 0;
        groups.sort(function (a, b) {
            const na = nameMap[a.playground_id] || '';
            const nb = nameMap[b.playground_id] || '';
            return na.localeCompare(nb);
        });
        let grandTotal = 0;
        container.innerHTML = groups.map(function (group) {
            const storeName = nameMap[group.playground_id] || ('Store #' + group.playground_id);
            let storeTotal = 0;
            const rows = group.items.map(function (i) {
                const subtotal = i.quantity * (parseFloat(i.unit_price) || 0);
                storeTotal += subtotal;
                return '<div class="cart-item-row">' +
                    '<span class="cart-item-name">' + escapeHtml(i.name) + '</span>' +
                    '<span class="cart-item-qty">' + i.quantity + '</span>' +
                    '<span class="cart-item-price">₱' + parseFloat(i.unit_price).toFixed(2) + '</span>' +
                    '<span class="cart-item-subtotal">₱' + subtotal.toFixed(2) + '</span>' +
                    '</div>';
            }).join('');
            grandTotal += storeTotal;
            return '<div class="cart-store-group">' +
                '<div class="cart-store-header"><i class="fa-solid fa-tent"></i> ' + escapeHtml(storeName) + '</div>' +
                '<div class="cart-store-items">' + rows + '</div>' +
                '<div class="cart-store-footer">Subtotal: <span class="cart-store-total">₱' + storeTotal.toFixed(2) + '</span></div>' +
                '</div>';
        }).join('');
        var totalStr = '₱' + grandTotal.toFixed(2);
        var grandDisplay = document.getElementById('grandTotalDisplay');
        if (grandDisplay) grandDisplay.textContent = totalStr;
        var mobileGrand = document.getElementById('mobileGrandTotal');
        if (mobileGrand) mobileGrand.textContent = totalStr;
        return grandTotal;
    }

    function clearCart() {
        try {
            localStorage.setItem(CART_STORAGE_KEY, '[]');
            if (typeof window.updateNavCartBadge === 'function') window.updateNavCartBadge();
        } catch (e) {}
    }

    function showCheckoutModal(type, title, message, onClose) {
        var modal = document.getElementById('checkoutResultModal');
        var iconEl = document.getElementById('checkoutModalIcon');
        var titleEl = document.getElementById('checkoutModalTitle');
        var msgEl = document.getElementById('checkoutModalMessage');
        var btn = document.getElementById('checkoutModalOk');
        if (!modal || !iconEl || !msgEl || !btn) return;
        iconEl.className = 'checkout-modal-icon ' + type;
        iconEl.innerHTML = type === 'success' ? '<i class="fa-solid fa-circle-check"></i>' : '<i class="fa-solid fa-circle-exclamation"></i>';
        titleEl.textContent = title;
        msgEl.textContent = message;
        btn.className = 'checkout-modal-btn ' + type;
        btn.textContent = 'OK';
        modal.style.display = 'flex';
        function closeModal() {
            modal.style.display = 'none';
            if (typeof onClose === 'function') onClose();
        }
        btn.onclick = closeModal;
        var overlay = modal.querySelector('.checkout-modal-overlay');
        if (overlay) overlay.onclick = closeModal;
    }

    /** Returns { orderIds: [], error: string|null } */
    async function placeOrders(groups, delivery_address, notes, payment_method_id) {
        const orderIds = [];
        let error = null;
        for (let g = 0; g < groups.length; g++) {
            const group = groups[g];
            const items = group.items.map(function (i) {
                return {
                    package_id: i.package_id,
                    quantity: i.quantity,
                    unit_price: parseFloat(i.unit_price)
                };
            });
            const payload = {
                playground_id: group.playground_id,
                delivery_address: delivery_address,
                notes: notes,
                payment_method_id: payment_method_id,
                items: items
            };
            try {
                const res = await fetch(api + '/place_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    credentials: 'same-origin'
                });
                const data = await res.json();
                if (data.success) orderIds.push(data.order_id);
                else { error = data.error || 'Failed to place order'; break; }
            } catch (err) {
                error = 'Network error. Try again.';
                break;
            }
        }
        return { orderIds: orderIds, error: error };
    }

    function getGrandTotal(groups) {
        let t = 0;
        groups.forEach(function (g) {
            g.items.forEach(function (i) {
                t += i.quantity * (parseFloat(i.unit_price) || 0);
            });
        });
        return t;
    }

    document.addEventListener('DOMContentLoaded', async function () {
        const cart = getCart();
        const emptyEl = document.getElementById('checkoutEmpty');
        const contentEl = document.getElementById('checkoutContent');
        if (!emptyEl || !contentEl) return;

        if (cart.length === 0) {
            emptyEl.style.display = 'block';
            contentEl.style.display = 'none';
            return;
        }
        emptyEl.style.display = 'none';
        contentEl.style.display = 'block';

        const nameMap = await loadplaygroundNames();
        const groups = groupCartByStore(cart);
        const grandTotal = renderSummary(groups, nameMap);

        const pmList = document.getElementById('paymentMethodsList');
        const paymentDetailsEl = document.getElementById('checkoutPaymentDetails');
        const methods = await loadPaymentMethods();

        function updatePaymentDetails() {
            if (!paymentDetailsEl) return;
            var checked = document.querySelector('input[name="payment_method_id"]:checked');
            if (!checked) {
                paymentDetailsEl.innerHTML = '';
                paymentDetailsEl.className = 'checkout-payment-details';
                return;
            }
            var val = (checked.value || '').trim();
            if (val === '' || val === 'cod') {
                paymentDetailsEl.className = 'checkout-payment-details checkout-payment-details-cod';
                paymentDetailsEl.innerHTML = '<p class="checkout-payment-details-text">Pay when your order is delivered. No need to pay now.</p>';
                return;
            }
            var label = checked.getAttribute('data-pm-label') || ('GCash ' + val);
            paymentDetailsEl.className = 'checkout-payment-details checkout-payment-details-gcash';
            paymentDetailsEl.innerHTML = '<p class="checkout-payment-details-text">Pay with <strong>GCash (' + escapeHtml(label) + ')</strong>. You will confirm payment in the next step after clicking Place Order.</p>';
        }

        if (pmList) {
            pmList.innerHTML = '';
            var cash = document.createElement('label');
            cash.className = 'checkout-payment-option selected';
            cash.setAttribute('data-payment-type', 'cod');
            cash.innerHTML = '<input type="radio" name="payment_method_id" value="cod" checked> <span>Cash on Delivery</span> <i class="fa-solid fa-money-bill-wave"></i>';
            cash.addEventListener('click', function () { updatePaymentSelection(); });
            pmList.appendChild(cash);
            methods.forEach(function (pm) {
                var label = document.createElement('label');
                label.className = 'checkout-payment-option';
                label.setAttribute('data-payment-type', 'gcash');
                label.innerHTML = '<input type="radio" name="payment_method_id" value="' + pm.id + '" data-pm-label="' + escapeHtml(pm.label || '') + '"> <span>' + escapeHtml(pm.label || 'GCash') + '</span> <i class="fa-solid fa-mobile-screen-button"></i>';
                label.addEventListener('click', function () { updatePaymentSelection(); });
                pmList.appendChild(label);
            });
            function updatePaymentSelection() {
                pmList.querySelectorAll('.checkout-payment-option').forEach(function (el) {
                    el.classList.toggle('selected', el.querySelector('input').checked);
                });
                updatePaymentDetails();
            }
            pmList.querySelectorAll('input[name="payment_method_id"]').forEach(function (radio) {
                radio.addEventListener('change', updatePaymentSelection);
            });
            updatePaymentDetails();
        }

        var gcashModal = document.getElementById('gcashPaymentModal');
        var gcashStepConfirm = document.getElementById('gcashModalStepConfirm');
        var gcashStepSuccess = document.getElementById('gcashModalStepSuccess');
        var gcashModalAmount = document.getElementById('gcashModalAmount');
        var gcashConfirmBtn = document.getElementById('gcashConfirmBtn');
        var gcashSuccessBtn = document.getElementById('gcashSuccessBtn');

        document.getElementById('checkoutForm').addEventListener('submit', async function (e) {
            e.preventDefault();
            var delivery_address = document.getElementById('delivery_address').value.trim();
            var notes = document.getElementById('notes').value.trim();
            var pmEl = document.querySelector('input[name="payment_method_id"]:checked');
            var rawVal = pmEl ? (pmEl.value || '').trim() : '';
            var isCod = rawVal === '' || rawVal === 'cod';
            var payment_method_id = isCod ? null : (parseInt(rawVal, 10) || null);

            if (!delivery_address) {
                showCheckoutModal('error', 'Missing information', 'Please enter delivery address.', null);
                return;
            }

            var placeOrderBtns = document.querySelectorAll('.btn-place-order');
            function setButtonsLoading(loading) {
                placeOrderBtns.forEach(function (b) {
                    b.disabled = loading;
                    b.textContent = loading ? 'Placing order(s)...' : 'Place Order';
                });
            }

            if (isCod) {
                setButtonsLoading(true);
                var result = await placeOrders(groups, delivery_address, notes, null);
                setButtonsLoading(false);
                if (result.error) {
                    showCheckoutModal('error', 'Order failed', result.error, null);
                    return;
                }
                clearCart();
                var successMsg = result.orderIds.length === 1
                    ? 'Order #' + result.orderIds[0] + ' placed successfully. View Order History to track.'
                    : 'Orders #' + result.orderIds.join(', #') + ' placed successfully. View Order History to track.';
                showCheckoutModal('success', 'Order placed', successMsg, function () {
                    window.location.href = base + '/php/forms/order_history.php';
                });
                return;
            }

            if (payment_method_id && gcashModal && gcashStepConfirm && gcashStepSuccess && gcashModalAmount && gcashConfirmBtn && gcashSuccessBtn) {
                gcashModalAmount.textContent = '₱' + grandTotal.toFixed(2);
                gcashStepConfirm.style.display = 'block';
                gcashStepSuccess.style.display = 'none';
                gcashModal.style.display = 'flex';

                function closeGcashModal() {
                    gcashModal.style.display = 'none';
                }

                gcashConfirmBtn.onclick = function () {
                    gcashStepConfirm.style.display = 'none';
                    gcashStepSuccess.style.display = 'block';
                };

                gcashSuccessBtn.onclick = function () {
                    closeGcashModal();
                    setButtonsLoading(true);
                    placeOrders(groups, delivery_address, notes, payment_method_id).then(function (result) {
                        setButtonsLoading(false);
                        if (result.error) {
                            showCheckoutModal('error', 'Order failed', result.error, null);
                            return;
                        }
                        clearCart();
                        var successMsg = result.orderIds.length === 1
                            ? 'Order #' + result.orderIds[0] + ' placed successfully. View Order History to track.'
                            : 'Orders #' + result.orderIds.join(', #') + ' placed successfully. View Order History to track.';
                        showCheckoutModal('success', 'Order placed', successMsg, function () {
                            window.location.href = base + '/php/forms/order_history.php';
                        });
                    });
                };

                if (gcashModal.querySelector('.checkout-modal-overlay')) {
                    gcashModal.querySelector('.checkout-modal-overlay').onclick = closeGcashModal;
                }
                return;
            }

            setButtonsLoading(true);
            var result = await placeOrders(groups, delivery_address, notes, payment_method_id);
            setButtonsLoading(false);
            if (result.error) {
                showCheckoutModal('error', 'Order failed', result.error, null);
                return;
            }
            clearCart();
            var successMsg = result.orderIds.length === 1
                ? 'Order #' + result.orderIds[0] + ' placed successfully. View Order History to track.'
                : 'Orders #' + result.orderIds.join(', #') + ' placed successfully. View Order History to track.';
            showCheckoutModal('success', 'Order placed', successMsg, function () {
                window.location.href = base + '/php/forms/order_history.php';
            });
        });
    });
})();



