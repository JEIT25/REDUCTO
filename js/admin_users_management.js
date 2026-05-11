/**
 * Admin Users Management - list users, request deletion (AMORA-style)
 */
(function() {
    const API_BASE = (typeof window !== 'undefined' && window.API_BASE) ? window.API_BASE : '';
    const container = document.getElementById('users-container');
    const searchInput = document.getElementById('searchInput');
    const usersCount = document.getElementById('usersCount');
    const paginationEl = document.getElementById('pagination');
    let currentPage = 1;
    let searchTimeout = null;
    let requestDeleteTarget = null; // { action_type, target_type, target_id, name }

    function escapeHtml(s) {
        if (s == null) return '';
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function loadUsers(page) {
        if (!container) return;
        container.innerHTML = '<div class="loading-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading users...</p></div>';
        let url = API_BASE + 'admin_users_list.php?page=' + page + '&per_page=10';
        if (searchInput && searchInput.value.trim()) url += '&search=' + encodeURIComponent(searchInput.value.trim());

        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    container.innerHTML = '<div class="empty-state"><p>' + escapeHtml(data.error || 'Failed to load') + '</p></div>';
                    if (usersCount) usersCount.textContent = '0 users';
                    return;
                }
                const users = data.users || [];
                if (usersCount) usersCount.textContent = data.pagination.total + ' user(s)';
                if (users.length === 0) {
                    container.innerHTML = '<div class="empty-state"><p>No users found.</p></div>';
                    paginationEl.innerHTML = '';
                    return;
                }
                let html = '';
                users.forEach(u => {
                    const name = escapeHtml((u.firstName || '') + ' ' + (u.lastName || ''));
                    const roleClass = (u.role || 'basic-user').toLowerCase().replace(' ', '-');
                    const canRequestDelete = (u.role === 'basic-user' || u.role === 'admin') && u.id;
                    html += '<div class="user-card" data-id="' + escapeHtml(u.id) + '">' +
                        '<div class="user-card-name">' + name + '</div>' +
                        '<span class="user-card-role ' + roleClass + '">' + escapeHtml(u.role || '') + '</span>' +
                        '<div class="user-card-info">' +
                        '<div class="user-card-info-item"><i class="fa-solid fa-user"></i> ' + escapeHtml(u.username || '') + '</div>' +
                        '<div class="user-card-info-item"><i class="fa-solid fa-envelope"></i> ' + escapeHtml(u.email || '') + '</div>' +
                        '</div>' +
                        '<div class="user-card-actions">' +
                        (canRequestDelete ? '<button type="button" class="btn-sm btn-request-delete" data-id="' + escapeHtml(u.id) + '" data-name="' + escapeHtml(name) + '"><i class="fa-solid fa-paper-plane"></i> Request delete</button>' : '') +
                        '</div></div>';
                });
                container.innerHTML = html;
                renderPagination(data.pagination);
                container.querySelectorAll('.btn-request-delete').forEach(btn => {
                    btn.addEventListener('click', function() {
                        openRequestDeleteModal('delete_user', 'user', this.dataset.id, this.dataset.name || 'User');
                    });
                });
            })
            .catch(() => {
                container.innerHTML = '<div class="empty-state"><p>Network error.</p></div>';
                if (usersCount) usersCount.textContent = 'Error';
            });
    }

    function renderPagination(p) {
        if (!paginationEl || !p) { paginationEl.innerHTML = ''; return; }
        const totalPages = p.total_pages || 1;
        let html = '';
        if (currentPage > 1) html += '<button type="button" class="pagination-prev" data-page="' + (currentPage - 1) + '">Prev</button>';
        html += '<span class="pagination-info">Page ' + currentPage + ' of ' + totalPages + '</span>';
        if (currentPage < totalPages) html += '<button type="button" class="pagination-next" data-page="' + (currentPage + 1) + '">Next</button>';
        paginationEl.innerHTML = html;
        paginationEl.querySelectorAll('button').forEach(b => {
            b.addEventListener('click', function() {
                currentPage = parseInt(this.dataset.page, 10);
                loadUsers(currentPage);
            });
        });
    }

    function openRequestDeleteModal(actionType, targetType, targetId, name) {
        requestDeleteTarget = { action_type: actionType, target_type: targetType, target_id: targetId, name: name };
        const title = document.getElementById('requestDeleteTitle');
        const msg = document.getElementById('requestDeleteMessage');
        if (title) title.textContent = 'Request deletion: ' + name;
        if (msg) msg.textContent = 'Provide a reason for requesting deletion. A superadmin will review it.';
        const reason = document.getElementById('requestDeleteReason');
        if (reason) { reason.value = ''; reason.required = true; }
        const modal = document.getElementById('requestDeleteModal');
        if (modal) modal.classList.add('show');
        const countEl = document.getElementById('requestDeleteCharCount');
        if (countEl) countEl.textContent = '0 / 500';
        if (reason) reason.addEventListener('input', function() {
            if (countEl) countEl.textContent = this.value.length + ' / 500';
        });
    }

    function closeRequestDeleteModal() {
        requestDeleteTarget = null;
        document.getElementById('requestDeleteModal').classList.remove('show');
    }

    function submitRequest() {
        if (!requestDeleteTarget) return;
        const reasonEl = document.getElementById('requestDeleteReason');
        const reason = reasonEl && reasonEl.value ? reasonEl.value.trim() : '';
        if (!reason) {
            if (reasonEl) reasonEl.focus();
            return;
        }
        const fd = new FormData();
        fd.append('action_type', requestDeleteTarget.action_type);
        fd.append('target_type', requestDeleteTarget.target_type);
        fd.append('target_id', requestDeleteTarget.target_id);
        fd.append('reason', reason);
        fetch(API_BASE + 'approval_request.php', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                closeRequestDeleteModal();
                showNotification(data.success ? 'Success' : 'Error', data.message || data.error || (data.success ? 'Request submitted.' : 'Failed'));
                if (data.success) loadUsers(currentPage);
            })
            .catch(() => {
                showNotification('Error', 'Network error.');
            });
    }

    function showNotification(title, message) {
        const modal = document.getElementById('notificationModal');
        const titleEl = document.getElementById('notificationTitle');
        const msgEl = document.getElementById('notificationMessage');
        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        if (modal) {
            modal.classList.add('show');
            modal.classList.toggle('error', title === 'Error');
            modal.classList.toggle('success', title === 'Success');
        }
        const close = function() {
            modal.classList.remove('show');
            if (modal) modal.removeEventListener('click', close);
            if (document.getElementById('notificationFooterBtn')) document.getElementById('notificationFooterBtn').removeEventListener('click', close);
        };
        document.getElementById('notificationFooterBtn').addEventListener('click', close);
        modal.addEventListener('click', function(e) { if (e.target === modal) close(); });
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                currentPage = 1;
                loadUsers(1);
            }, 400);
        });
    }
    document.getElementById('requestDeleteCancel') && document.getElementById('requestDeleteCancel').addEventListener('click', closeRequestDeleteModal);
    document.getElementById('requestDeleteSubmit') && document.getElementById('requestDeleteSubmit').addEventListener('click', submitRequest);

    loadUsers(1);
})();
