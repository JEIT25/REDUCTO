<?php
/**
 * Unified Requests (Superadmin)
 * View all system requests (blocks, unblocks, and all approvals).
 */
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');

$pageTitle = 'Manage Requests';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - LittleLands</title>
    <link rel="stylesheet" href="../../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'superadmin_requests'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">System Requests</h1>
                <p class="page-subtitle">Review and process account status changes and administrative approvals.</p>

                <div class="filters">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="searchInput" class="input-field" placeholder="Search by name, username, or reason..." style="padding-left:48px;" onkeyup="handleSearch(event)">
                    </div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div style="width: 160px;">
                            <input type="date" id="startDate" class="input-field" onchange="applyFilters()">
                        </div>
                        <div style="width: 160px;">
                            <input type="date" id="endDate" class="input-field" onchange="applyFilters()">
                        </div>
                        <div style="width: 180px;">
                            <select id="filterStatus" class="input-field" onchange="applyFilters()">
                                <option value="">All History</option>
                                <option value="pending">Pending Only</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <button class="submitBtn" onclick="resetFilters()" style="background: #f1f5f9; color: #475569;">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </div>

                <div id="requestsTableContainer" class="Area-container">
                    <div style="padding: 3rem; text-align: center; color: #94a3b8;">
                        <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem;">Optimizing workspace...</p>
                    </div>
                </div>

                <div class="pagination-container">
                    <span id="requestCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Requests</span>
                    <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

    <script src="../../js/pagination_util.js"></script>
    <script>
        const api = '../../php/database';
        let currentPage = 1;
        let currentSearch = '';
        let currentStatus = '';
        let currentStartDate = '';
        let currentEndDate = '';
        let limit = 5;

        function loadRequests(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page: currentPage,
                limit: limit,
                search: currentSearch,
                status: currentStatus,
                startDate: currentStartDate,
                endDate: currentEndDate
            });

            fetch(api + '/superadmin_unified_requests.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('requestsTableContainer').innerHTML = `<div style="padding: 2rem; text-align: center; color: #ef4444;"><i class="fa-solid fa-triangle-exclamation"></i> ${data.error || 'Failed to load requests'}</div>`;
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr>' +
                        '<th>Requester</th>' +
                        '<th>Target User</th>' +
                        '<th>Type</th>' +
                        '<th>Reason</th>' +
                        '<th>Date</th>' +
                        '<th>Status</th>' +
                        '<th>Action</th>' +
                        '</tr></thead><tbody>';

                    if (data.requests.length === 0) {
                        html += '<tr><td colspan="7" style="text-align:center; padding:4rem; color: #94a3b8;"><i class="fa-regular fa-folder-open fa-3x" style="display:block; margin-bottom:1rem; opacity:0.5;"></i> No matching requests found.</td></tr>';
                    } else {
                        data.requests.forEach(r => {
                            let statusClass = r.status === 'approved' ? 'status-ok' : (r.status === 'rejected' ? 'status-trash' : 'status-pending');
                            
                            const typeLabel = r.request_type.toUpperCase();
                            const typeStyle = r.request_type === 'registration' ? 'background: #eff6ff; color: #2563eb;' :
                                r.request_type === 'unblock' ? 'background: #ecfdf5; color: #059669;' :
                                    'background: #fef2f2; color: #dc2626;';

                            html += `<tr>
                                <td><div class="table-primary-text">${escapeHtml(r.requester_first + ' ' + r.requester_last)}</div></td>
                                <td>
                                    <div class="table-primary-text">${escapeHtml(r.target_first + ' ' + r.target_last)}</div>
                                    <div class="table-secondary-text">@${escapeHtml(r.target_username)}</div>
                                </td>
                                <td><span class="status-badge" style="${typeStyle}">${typeLabel}</span></td>
                                <td style="max-width: 250px;"><div class="table-secondary-text">${escapeHtml(r.reason)}</div></td>
                                <td><div class="table-secondary-text">${new Date(r.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div></td>
                                <td><span class="status-badge ${statusClass}">${r.status.toUpperCase()}</span></td>
                                <td>
                                    ${r.status === 'pending' ? `
                                        <div style="display:flex; gap:0.5rem;">
                                            <button class="submitBtn" style="padding:0.5rem; background:#dcfce7; color:#15803d;" title="Approve" onclick="handleRequest(${r.request_id}, '${r.source_table}', 'approve')"><i class="fa-solid fa-check"></i></button>
                                            <button class="submitBtn" style="padding:0.5rem; background:#fee2e2; color:#b91c1c;" title="Reject" onclick="handleRequest(${r.request_id}, '${r.source_table}', 'reject')"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
                                    ` : '<span class="table-secondary-text" style="font-weight:700;">PROCESSED</span>'}
                                </td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('requestsTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                })
                .catch(err => {
                    console.error('Fetch error:', err);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('requestCountBadge');
            const total = p.total_requests || 0;
            badge.textContent = `${total} Request${total !== 1 ? 's' : ''}`;
            if (total > 0) { badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
            else { badge.style.background = '#fee2e2'; badge.style.color = '#dc2626'; }
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadRequests(n), l => { limit = l; loadRequests(1); });
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentSearch = e.target.value; loadRequests(1); }, 300);
        }

        function applyFilters() {
            currentStatus = document.getElementById('filterStatus').value;
            currentStartDate = document.getElementById('startDate').value;
            currentEndDate = document.getElementById('endDate').value;
            loadRequests(1);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            currentSearch = ''; currentStatus = ''; currentStartDate = ''; currentEndDate = '';
            loadRequests(1);
        }

        function handleRequest(id, Area, action) {
            const verb = action === 'approve' ? 'APPROVE' : 'REJECT';
            if (!confirm(`Are you sure you want to ${verb} this request? This action cannot be undone.`)) return;
            const fd = new FormData(); fd.append('request_id', id); fd.append('source_table', Area); fd.append('action', action);
            document.getElementById('requestsTableContainer').style.opacity = '0.5';
            fetch(api + '/unified_request_action.php', { method: 'POST', body: fd })
                .then(r => r.json()).then(d => {
                    if (d.success) loadRequests(currentPage);
                    else { alert(d.error); document.getElementById('requestsTableContainer').style.opacity = '1'; }
                })
                .catch(err => { alert('Network error.'); document.getElementById('requestsTableContainer').style.opacity = '1'; });
        }

        function escapeHtml(s) { if (!s) return ''; const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        loadRequests();
    </script>
</body>
</html>