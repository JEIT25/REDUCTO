<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');
$pageTitle = 'Audit Logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - LittleLands</title>
    <link rel="stylesheet" href="../../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'superadmin_logs'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Audit Logs</h1>
                <p class="page-subtitle">Monitor system access, user activities, and session durations.</p>

                <div class="filters">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="filterSearch" class="input-field" placeholder="Search by name or username..." style="padding-left:48px;" onkeyup="debounceLoadLogs()">
                    </div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <div style="width: 150px;">
                            <select id="filterRole" class="input-field" onchange="loadLogs(1)">
                                <option value="">All Roles</option>
                                <option value="superadmin">Superadmin</option>
                                <option value="admin">Admin</option>
                                <option value="basic-user">Basic User</option>
                            </select>
                        </div>
                        <div style="display:flex; gap:0.5rem; align-items:center; background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:0 12px; height:48px;">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600; white-space:nowrap;">From:</span>
                            <input type="date" id="filterStartDate" class="input-field" style="border:none; padding:0; height:auto; font-size:0.85rem;" onchange="loadLogs(1)">
                            <span style="font-size:0.75rem; color:#64748b; font-weight:600; white-space:nowrap; margin-left:0.5rem;">To:</span>
                            <input type="date" id="filterEndDate" class="input-field" style="border:none; padding:0; height:auto; font-size:0.85rem;" onchange="loadLogs(1)">
                        </div>
                        <button class="submitBtn" onclick="resetFilters()" style="background: #f1f5f9; color: #475569;">
                            <i class="fa-solid fa-rotate-right"></i> Reset
                        </button>
                    </div>
                </div>

                <div id="logsTableContainer" class="Area-container">
                    <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem;">Fetching audit logs...</p>
                    </div>
                </div>

                <div class="pagination-container">
                    <span id="logCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Logs</span>
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
        let limit = 5;
        let debounceTimer;

        function debounceLoadLogs() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadLogs(1), 300);
        }

        function loadLogs(page = 1) {
            currentPage = page;
            const startDate = document.getElementById('filterStartDate').value;
            const endDate = document.getElementById('filterEndDate').value;
            const search = document.getElementById('filterSearch').value;
            const role = document.getElementById('filterRole').value;

            const params = new URLSearchParams({ startDate, endDate, search, role, page, limit });

            fetch(api + '/superadmin_logs_list.php?' + params)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) {
                        document.getElementById('logsTableContainer').innerHTML = '<div style="padding:2rem; text-align:center; color:#ef4444;">Failed to load logs.</div>';
                        return;
                    }

                    let html = '<table class="data-table"><thead><tr><th>User</th><th>Role</th><th>Login Time</th><th>Logout Time</th><th>Duration</th></tr></thead><tbody>';
                    
                    if (data.logs.length === 0) {
                        html += '<tr><td colspan="5" style="text-align:center; padding:4rem; color:#94a3b8;"><i class="fa-regular fa-folder-open fa-3x" style="display:block; margin-bottom:1rem; opacity:0.5;"></i>No logs found matching your criteria.</td></tr>';
                    } else {
                        data.logs.forEach(l => {
                            const loginDate = new Date(l.login_time);
                            const logoutDate = l.logout_time ? new Date(l.logout_time) : null;

                            const fmtLogin = loginDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                            const fmtLogout = logoutDate ? logoutDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '<span style="color:#94a3b8;">ACTIVE</span>';

                            let duration = '-';
                            if (logoutDate) {
                                const diffMs = logoutDate - loginDate;
                                const diffMins = Math.floor(diffMs / 60000);
                                const hrs = Math.floor(diffMins / 60);
                                const mins = diffMins % 60;
                                duration = hrs > 0 ? `${hrs}h ${mins}m` : `${mins}m`;
                            }

                            let roleStyle = l.role === 'superadmin' ? 'background:#fef2f2; color:#dc2626;' : (l.role === 'admin' ? 'background:#eff6ff; color:#2563eb;' : 'background:#ecfdf5; color:#059669;');

                            html += `<tr>
                                <td>
                                    <div style="font-weight:700; color:var(--text-heading);">${escapeHtml(l.firstName + ' ' + l.lastName)}</div>
                                    <div style="font-size:0.75rem; color:var(--text-muted);">@${escapeHtml(l.username)}</div>
                                </td>
                                <td><span class="status-badge" style="${roleStyle}">${l.role.toUpperCase()}</span></td>
                                <td style="font-size:0.85rem; font-weight:600; color:var(--text-muted);">${fmtLogin}</td>
                                <td style="font-size:0.85rem; font-weight:600; color:var(--text-muted);">${fmtLogout}</td>
                                <td style="font-weight:800; color:var(--text-heading);">${duration}</td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('logsTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('logCountBadge');
            const total = p.total_records || 0;
            badge.textContent = `${total} Log${total !== 1 ? 's' : ''}`;
            if (total > 0) { badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
            else { badge.style.background = '#fee2e2'; badge.style.color = '#dc2626'; }
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadLogs(n), l => { limit = l; loadLogs(1); });
        }

        function resetFilters() {
            document.getElementById('filterSearch').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('filterStartDate').value = '';
            document.getElementById('filterEndDate').value = '';
            loadLogs(1);
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
        loadLogs();
    </script>
</body>
</html>