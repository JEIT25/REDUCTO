/**
 * Cart list page: read cart from localStorage, group by store, render list. Checkout button links to checkout.php.
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

    function saveCart(cart) {
        try {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
            if (typeof window.updateNavCartBadge === 'function') window.updateNavCartBadge();
        } catch (e) {}
    }

    function removeItemFromCart(cart, PackagesItemId, playgroundId) {
        const mid = parseInt(PackagesItemId, 10);
        const rid = playgroundId !== undefined && playgroundId !== '' ? parseInt(playgroundId, 10) : null;
        const next = cart.filter(function (c) {
            const cMid = parseInt(c.package_id, 10);
            const cRid = c.playground_id !== undefined && c.playground_id !== null ? parseInt(c.playground_id, 10) : null;
            if (cMid !== mid) return true;
            if (rid === null) return false;
            return cRid !== rid;
        });
        return next;
    }

    function setQuantity(cart, PackagesItemId, playgroundId, newQty) {
        const mid = parseInt(PackagesItemId, 10);
        const rid = playgroundId !== undefined && playgroundId !== '' ? parseInt(playgroundId, 10) : null;
        const qty = Math.max(0, parseInt(newQty, 10) || 0);
        if (qty <= 0) return removeItemFromCart(cart, PackagesItemId, playgroundId);
        const next = cart.slice();
        const item = next.find(function (c) {
            const cMid = parseInt(c.package_id, 10);
            const cRid = c.playground_id !== undefined && c.playground_id !== null ? parseInt(c.playground_id, 10) : null;
            if (cMid !== mid) return false;
            return rid === null ? true : cRid === rid;
        });
        if (item) item.quantity = qty;
        return next;
    }

    function updateQty(cart, PackagesItemId, playgroundId, delta) {
        const mid = parseInt(PackagesItemId, 10);
        const rid = playgroundId !== undefined && playgroundId !== '' ? parseInt(playgroundId, 10) : null;
        const item = cart.find(function (c) {
            const cMid = parseInt(c.package_id, 10);
            const cRid = c.playground_id !== undefined && c.playground_id !== null ? parseInt(c.playground_id, 10) : null;
            if (cMid !== mid) return false;
            return rid === null ? true : cRid === rid;
        });
        if (!item) return cart;
        const newQty = Math.max(0, (item.quantity || 1) + delta);
        if (newQty <= 0) return removeItemFromCart(cart, PackagesItemId, playgroundId);
        return setQuantity(cart, PackagesItemId, playgroundId, newQty);
    }

    function render() {
        const cart = getCart();
        const emptyEl = document.getElementById('cartEmpty');
        const contentEl = document.getElementById('cartContent');
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (!emptyEl || !contentEl) return;

        if (cart.length === 0) {
            emptyEl.style.display = 'block';
            contentEl.style.display = 'none';
            if (checkoutBtn) checkoutBtn.style.display = 'none';
            return;
        }
        emptyEl.style.display = 'none';
        contentEl.style.display = 'block';
        if (checkoutBtn) checkoutBtn.style.display = 'inline-block';

        loadplaygroundNames().then(function (nameMap) {
            const groups = groupCartByStore(cart);
            groups.sort(function (a, b) {
                const na = nameMap[a.playground_id] || '';
                const nb = nameMap[b.playground_id] || '';
                return na.localeCompare(nb);
            });
            const container = document.getElementById('cartByStore');
            if (!container) return;
            container.innerHTML = groups.map(function (group) {
                const storeName = nameMap[group.playground_id] || ('Store #' + group.playground_id);
                let storeTotal = 0;
                const rows = group.items.map(function (i) {
                    const subtotal = i.quantity * (parseFloat(i.unit_price) || 0);
                    storeTotal += subtotal;
                    return '<div class="cart-item-row" data-Packages-id="' + i.package_id + '" data-playground-id="' + (i.playground_id || group.playground_id) + '">' +
                        '<span class="cart-item-name">' + escapeHtml(i.name) + '</span>' +
                        '<div class="cart-qty-controls">' +
                        '<button type="button" class="cart-qty-btn cart-qty-minus" aria-label="Decrease">−</button>' +
                        '<input type="number" class="cart-qty-input" min="1" max="99" value="' + (i.quantity || 1) + '" aria-label="Quantity">' +
                        '<button type="button" class="cart-qty-btn cart-qty-plus" aria-label="Increase">+</button>' +
                        '</div>' +
                        '<span class="cart-item-price">₱' + parseFloat(i.unit_price).toFixed(2) + '</span>' +
                        '<span class="cart-item-subtotal">₱' + subtotal.toFixed(2) + '</span>' +
                        '<button type="button" class="cart-item-remove" title="Remove" aria-label="Remove"><i class="fa-solid fa-trash-can"></i></button>' +
                        '</div>';
                }).join('');
                return '<div class="cart-store-group" data-playground-id="' + group.playground_id + '">' +
                    '<div class="cart-store-header"><i class="fa-solid fa-tent"></i> ' + escapeHtml(storeName) + '</div>' +
                    '<div class="cart-store-items">' + rows + '</div>' +
                    '<div class="cart-store-footer">Subtotal: <span class="cart-store-total">₱' + storeTotal.toFixed(2) + '</span></div>' +
                    '</div>';
            }).join('');

            container.querySelectorAll('.cart-item-remove').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const row = btn.closest('.cart-item-row');
                    if (!row) return;
                    const PackagesId = row.getAttribute('data-Packages-id');
                    const restId = row.getAttribute('data-playground-id');
                    if (PackagesId === null || PackagesId === '') return;
                    let next = getCart();
                    next = removeItemFromCart(next, PackagesId, restId);
                    saveCart(next);
                    render();
                });
            });

            container.querySelectorAll('.cart-qty-minus').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const row = btn.closest('.cart-item-row');
                    if (!row) return;
                    const PackagesId = row.getAttribute('data-Packages-id');
                    const restId = row.getAttribute('data-playground-id');
                    if (PackagesId === null || PackagesId === '') return;
                    let next = getCart();
                    next = updateQty(next, PackagesId, restId, -1);
                    saveCart(next);
                    render();
                });
            });

            container.querySelectorAll('.cart-qty-plus').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const row = btn.closest('.cart-item-row');
                    if (!row) return;
                    const PackagesId = row.getAttribute('data-Packages-id');
                    const restId = row.getAttribute('data-playground-id');
                    if (PackagesId === null || PackagesId === '') return;
                    let next = getCart();
                    next = updateQty(next, PackagesId, restId, 1);
                    saveCart(next);
                    render();
                });
            });

            container.querySelectorAll('.cart-qty-input').forEach(function (input) {
                function applyQty() {
                    const row = input.closest('.cart-item-row');
                    if (!row) return;
                    const PackagesId = row.getAttribute('data-Packages-id');
                    const restId = row.getAttribute('data-playground-id');
                    if (PackagesId === null || PackagesId === '') return;
                    let val = parseInt(input.value, 10);
                    if (isNaN(val) || val < 1) val = 1;
                    if (val > 99) val = 99;
                    input.value = val;
                    let next = getCart();
                    next = setQuantity(next, PackagesId, restId, val);
                    saveCart(next);
                    render();
                }
                input.addEventListener('change', applyQty);
                input.addEventListener('blur', applyQty);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', render);
})();



