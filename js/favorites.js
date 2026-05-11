(function () {
    const api = (window.BASE_URL || '') + '/php/database';
    const list = document.getElementById('favoritesList');
    const searchInput = document.getElementById('favoritesSearch');
    if (!list) return;

    let favorites = [];

    function escapeHtml(s) {
        if (!s) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function matchesSearch(f, q) {
        if (!q) return true;
        const lower = q.toLowerCase();
        return (f.name && f.name.toLowerCase().includes(lower)) ||
            (f.playground_name && f.playground_name.toLowerCase().includes(lower));
    }

    function renderCards(items) {
        if (!items.length) {
            list.innerHTML = '<div class="empty-state favorites-empty"><p class="muted">No favorites match your search.</p><p>Try a different search or <a href="book_package.php">browse playgrounds</a> to add more.</p></div>';
            return;
        }
        list.innerHTML = items.map(f => {
            const avail = f.is_available == 1;
            const orderUrl = 'book_package.php' + (f.playground_id ? '?playground_id=' + encodeURIComponent(f.playground_id) : '');
            return '<div class="Packages-item favorite-card ' + (!avail ? 'unavailable' : '') + '" data-Packages-id="' + escapeHtml(String(f.package_id)) + '">' +
                '<button type="button" class="btn-fav btn-fav-card favorited" data-Packages-id="' + escapeHtml(String(f.package_id)) + '" title="Remove from favorites" aria-label="Remove from favorites"><i class="fa-solid fa-heart" aria-hidden="true"></i></button>' +
                '<h4>' + escapeHtml(f.name) + '</h4>' +
                '<p class="muted small">' + escapeHtml(f.playground_name) + '</p>' +
                '<span class="price">₱' + parseFloat(f.price).toFixed(2) + '</span>' +
                '<div class="Packages-item-actions">' +
                '<a href="' + orderUrl + '" class="btn-order-again">Order again</a>' +
                '</div>' +
                '</div>';
        }).join('');

        list.querySelectorAll('.btn-fav-card').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const PackagesId = btn.dataset.PackagesId;
                const fd = new FormData();
                fd.append('package_id', PackagesId);
                fd.append('action', 'remove');
                fetch(api + '/favorite_toggle.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            favorites = favorites.filter(f => String(f.package_id) !== PackagesId);
                            if (!favorites.length) {
                                list.innerHTML = '<div class="empty-state"><p class="muted">No favorites yet.</p><p><a href="book_package.php">Browse playgrounds</a> and tap the heart on items to save them here.</p></div>';
                            } else {
                                renderCards(favorites.filter(f => matchesSearch(f, (searchInput && searchInput.value) ? searchInput.value.trim() : '')));
                            }
                        }
                    });
            });
        });
    }

    function doSearch() {
        const q = (searchInput && searchInput.value) ? searchInput.value.trim() : '';
        const filtered = q ? favorites.filter(f => matchesSearch(f, q)) : favorites;
        renderCards(filtered);
    }

    fetch(api + '/favorites_list.php', { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.favorites) {
                list.innerHTML = '<div class="empty-state"><p class="muted">Could not load favorites.</p></div>';
                return;
            }
            favorites = data.favorites;
            if (!favorites.length) {
                list.innerHTML = '<div class="empty-state"><p class="muted">No favorites yet.</p><p><a href="book_package.php">Browse playgrounds</a> and tap the heart on items to save them here.</p></div>';
                return;
            }
            doSearch();
        })
        .catch(() => { list.innerHTML = '<div class="empty-state"><p class="muted">Could not load favorites. Try again later.</p></div>'; });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (!favorites.length) return;
            doSearch();
        });
    }
})();




