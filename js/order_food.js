/**
 * Order Food page: load playgrounds, load Packages, add to cart (persisted to DB), checkout.
 * Uses BASE_URL and relative paths for API calls.
 */
(function () {
    const base = window.BASE_URL || '';
    const api = base + '/php/database';

    let playgrounds = [];
    let Packages = [];
    let currentplaygroundId = null;
    let currentplaygroundName = '';
    let paymentMethods = [];

    const $ = (id) => document.getElementById(id);
    const playgroundsView = $('playgroundsView');
    const PackagesView = $('PackagesView');
    const checkoutView = $('checkoutView');
    const checkoutTotal = $('checkoutTotal');
    const paymentMethodsList = $('paymentMethodsList');

    function showView(name) {
        playgroundsView.style.display = name === 'playgrounds' ? 'block' : 'none';
        PackagesView.style.display = name === 'Packages' ? 'block' : 'none';
        checkoutView.style.display = name === 'checkout' ? 'block' : 'none';
    }

    /* ── Toast notification ── */
    function showToast(message, type) {
        // Remove any existing toast
        const existing = document.querySelector('.toast-notification');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `
            position: fixed; bottom: 2rem; right: 2rem; z-index: 2000;
            padding: 0.75rem 1.25rem; border-radius: 10px; font-size: 0.9rem;
            font-weight: 600; box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            display: flex; align-items: center; gap: 0.5rem;
            animation: slideInRight 0.3s ease-out;
            ${type === 'success'
                ? 'background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0;'
                : 'background: #fee2e2; color: #dc2626; border: 1px solid #fecaca;'}
        `;
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        toast.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;
        document.body.appendChild(toast);

        // Add animation keyframes if not present
        if (!document.getElementById('toastAnimStyle')) {
            const style = document.createElement('style');
            style.id = 'toastAnimStyle';
            style.textContent = '@keyframes slideInRight { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
            document.head.appendChild(style);
        }

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease-out';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    /* ── Add to cart (persisted to DB) ── */
    async function addToCart(item, qty) {
        const num = parseInt(qty, 10) || 1;
        const fd = new FormData();
        fd.append('package_id', item.id);
        fd.append('quantity', num);

        try {
            const res = await fetch(api + '/cart_add.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                showToast(`${escapeHtml(item.name)} added to cart!`, 'success');
                if (typeof window.updateNavCartBadge === 'function') window.updateNavCartBadge();
            } else {
                showToast(data.error || 'Failed to add item', 'error');
            }
        } catch (e) {
            showToast('Network error. Please try again.', 'error');
        }
    }

    async function loadplaygrounds() {
        try {
            const res = await fetch(api + '/playgrounds_list.php');
            const data = await res.json();
            if (data.success && data.playgrounds) {
                playgrounds = data.playgrounds;
                const list = $('playgroundsList');
                if (data.playgrounds.length === 0) {
                    list.innerHTML = `<div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light, #e5e7eb); border-radius: 12px;">
                        <i class="fa-solid fa-tent" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem;"></i>
                        <p style="color: var(--text-muted); margin: 0;">No playgrounds available at the moment.</p>
                    </div>`;
                    return;
                }
                list.innerHTML = data.playgrounds.map(r => {
                    return `<div class="playground-card" data-id="${r.id}" data-name="${escapeHtml(r.name)}">
                        <h3>${escapeHtml(r.name)}</h3>
                        <p>${escapeHtml(r.description || r.address || '')}</p>
                    </div>`;
                }).join('');
                list.querySelectorAll('.playground-card').forEach(el => {
                    el.addEventListener('click', () => {
                        currentplaygroundId = parseInt(el.dataset.id, 10);
                        currentplaygroundName = el.dataset.name;
                        loadPackages(currentplaygroundId);
                        showView('Packages');
                    });
                });
            }
        } catch (e) {
            console.error('Failed to load playgrounds:', e);
        }
    }

    async function loadPackages(playgroundId) {
        try {
            const res = await fetch(api + '/play_packages_list.php?playground_id=' + playgroundId);
            const data = await res.json();
            if (data.success && data.Packages) {
                Packages = data.Packages;
                $('PackagesplaygroundName').textContent = currentplaygroundName;
                const list = $('PackagesList');

                const availableItems = data.Packages.filter(m => m.is_available == 1);
                if (availableItems.length === 0) {
                    list.innerHTML = `<div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light, #e5e7eb); border-radius: 12px;">
                        <i class="fa-solid fa-shapes" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 0.5rem;"></i>
                        <p style="color: var(--text-muted); margin: 0;">No Play Packages available from this playground.</p>
                    </div>`;
                    return;
                }

                list.innerHTML = data.Packages.map(m => {
                    const avail = m.is_available == 1;
                    return `<div class="Packages-item ${!avail ? 'unavailable' : ''}" data-id="${m.id}">
                        <h4>${escapeHtml(m.name)}</h4>
                        ${m.description ? `<p class="muted small">${escapeHtml(m.description)}</p>` : ''}
                        <span class="price">₱${parseFloat(m.price).toFixed(2)}</span>
                        ${avail ? `<div class="Packages-item-actions">
                            <input type="number" min="1" value="1">
                            <button type="button" class="btn-add" data-Packages-id="${m.id}">Add</button>
                            <button type="button" class="btn-fav" data-Packages-id="${m.id}" title="Favorite">♥</button>
                        </div>` : `<div class="Packages-item-actions"><span class="muted small">Unavailable</span></div>`}
                    </div>`;
                }).join('');

                list.querySelectorAll('.btn-add').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const mid = parseInt(btn.dataset.PackagesId, 10);
                        const item = Packages.find(m => m.id == mid);
                        if (!item) return;
                        const input = btn.closest('.Packages-item').querySelector('input[type="number"]');
                        // Disable button temporarily to prevent double clicks
                        btn.disabled = true;
                        btn.textContent = '...';
                        addToCart({ id: item.id, name: item.name, price: item.price }, input.value)
                            .then(() => {
                                btn.disabled = false;
                                btn.textContent = 'Add';
                                input.value = 1;
                            });
                    });
                });

                list.querySelectorAll('.btn-fav').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        toggleFavorite(btn.dataset.PackagesId, btn);
                    });
                });

                // Mark already-favorited items
                (async function setFavoritedHearts() {
                    try {
                        const r = await fetch(api + '/favorites_list.php');
                        const d = await r.json();
                        if (d.favorites) {
                            const ids = d.favorites.map(f => String(f.package_id));
                            list.querySelectorAll('.btn-fav').forEach(b => {
                                b.classList.toggle('favorited', ids.includes(b.dataset.PackagesId));
                            });
                        }
                    } catch (_) { }
                })();
            }
        } catch (e) {
            console.error('Failed to load Packages:', e);
        }
    }

    async function toggleFavorite(PackagesItemId, btn) {
        try {
            const fd = new FormData();
            fd.append('package_id', PackagesItemId);
            fd.append('action', 'toggle');
            const res = await fetch(api + '/favorite_toggle.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) btn.classList.toggle('favorited', data.favorited);
        } catch (_) { }
    }

    async function loadPaymentMethods() {
        try {
            const res = await fetch(api + '/payment_methods_list.php');
            const data = await res.json();
            paymentMethods = (data.success && data.payment_methods) ? data.payment_methods : [];
            if (!paymentMethodsList) return;
            paymentMethodsList.innerHTML = '';
            paymentMethods.forEach(pm => {
                const label = document.createElement('label');
                label.innerHTML = `<input type="radio" name="payment_method_id" value="${pm.id}"> ${escapeHtml(pm.label)}`;
                paymentMethodsList.appendChild(label);
            });
            const cash = document.createElement('label');
            cash.innerHTML = '<input type="radio" name="payment_method_id" value="" checked> Cash on Delivery';
            paymentMethodsList.appendChild(cash);
        } catch (_) { }
    }

    /* ── Checkout (reads cart from DB) ── */
    async function loadCheckoutCart() {
        try {
            const res = await fetch(api + '/cart_get.php');
            const data = await res.json();
            if (!data.success || data.items.length === 0) {
                showToast('Your cart is empty. Add items first.', 'error');
                showView('playgrounds');
                return;
            }
            if (checkoutTotal) checkoutTotal.textContent = data.total.toFixed(2);
            showView('checkout');
        } catch (e) {
            showToast('Failed to load cart. Try again.', 'error');
        }
    }

    $('backToplaygrounds').addEventListener('click', () => { showView('playgrounds'); });
    $('backToPackages').addEventListener('click', () => { showView('Packages'); });
    $('checkoutForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const delivery_address = $('delivery_address').value.trim();
        const notes = $('notes').value.trim();
        const payment_method_id = document.querySelector('input[name="payment_method_id"]:checked');
        const pmId = payment_method_id && payment_method_id.value ? payment_method_id.value : null;

        // Get items from DB cart
        let cartItems = [];
        let rid = null;
        try {
            const cartRes = await fetch(api + '/cart_get.php');
            const cartData = await cartRes.json();
            if (!cartData.success || cartData.items.length === 0) {
                showToast('Your cart is empty!', 'error');
                return;
            }
            cartItems = cartData.items.map(ci => ({
                package_id: ci.package_id,
                quantity: ci.quantity,
                unit_price: parseFloat(ci.price)
            }));
            rid = cartData.items[0].playground_id;
        } catch (_) {
            showToast('Failed to read cart. Try again.', 'error');
            return;
        }

        const payload = {
            playground_id: rid,
            delivery_address,
            notes,
            payment_method_id: pmId ? parseInt(pmId, 10) : null,
            items: cartItems
        };

        const submitBtn = $('checkoutForm').querySelector('.submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Placing order...';
        try {
            const res = await fetch(api + '/place_order.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (data.success) {
                showView('playgrounds');
                showToast('Order #' + data.order_id + ' placed successfully!', 'success');
                submitBtn.textContent = 'Place Order';
                submitBtn.disabled = false;
                return;
            }
            showToast(data.error || 'Failed to place order', 'error');
        } catch (err) {
            showToast('Network error. Try again.', 'error');
        }
        submitBtn.textContent = 'Place Order';
        submitBtn.disabled = false;
    });

    function escapeHtml(s) {
        if (!s) return '';
        const div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    loadplaygrounds();
    loadPaymentMethods();
    showView('playgrounds');
})();




