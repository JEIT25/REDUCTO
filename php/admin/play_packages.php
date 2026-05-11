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
    <title>Play Packages - Admin</title>
    <link rel="stylesheet" href="../../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_Packages';
include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Play Packages</h1>
                <p class="page-subtitle">Manage Play Packages for your playgrounds.</p>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
                <div class="form-group" style="margin-bottom:0;">
                    <select id="restFilter" class="input-field" style="padding:0.5rem 1rem; min-width:200px; font-size: 0.9rem;">
                        <option value="">All playgrounds</option>
                    </select>
                </div>
                <button type="button" id="addPackagesBtn" class="submitBtn" style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; padding: 0.5rem 1rem;">
                    <i class="fa-solid fa-plus"></i> Add Play Package
                </button>
            </div>

            <div id="PackagesList" class="Area-container"></div>

            <!-- Edit/Add Modal -->
            <div id="PackagesModal" class="modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
                <div class="modal-content" style="background:var(--bg-card); padding:1.5rem; border-radius:var(--radius-lg); width:90%; max-width:450px; box-shadow:var(--shadow-lg);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                        <h2 id="PackagesModalTitle" style="margin:0; font-size:1.25rem;">Add Play Package</h2>
                        <button type="button" id="closePackagesModal" style="background:none; border:none; font-size:1.25rem; cursor:pointer; color:var(--text-muted);">&times;</button>
                    </div>
                    <form id="PackagesForm">
                        <input type="hidden" name="id" id="Packages_id" value="">
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="Packages_playground_id" style="font-weight:500; margin-bottom:0.25rem; display:block; font-size: 0.9rem;">playground</label>
                            <select name="playground_id" id="Packages_playground_id" class="input-field" required style="padding: 0.5rem;"></select>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="Packages_name" style="font-weight:500; margin-bottom:0.25rem; display:block; font-size: 0.9rem;">Item Name</label>
                            <input type="text" name="name" id="Packages_name" class="input-field" required placeholder="e.g. All-Day Play Pass" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="Packages_desc" style="font-weight:500; margin-bottom:0.25rem; display:block; font-size: 0.9rem;">Description</label>
                            <textarea name="description" id="Packages_desc" class="input-field" rows="2" placeholder="Brief description..." style="padding: 0.5rem;"></textarea>
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="Packages_price" style="font-weight:500; margin-bottom:0.25rem; display:block; font-size: 0.9rem;">Price (₱)</label>
                            <input type="number" step="0.01" min="0" name="price" id="Packages_price" class="input-field" required placeholder="0.00" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.75rem;">
                            <label for="Packages_image" style="font-weight:500; margin-bottom:0.25rem; display:block; font-size: 0.9rem;">Image URL (Optional)</label>
                            <input type="url" name="image_path" id="Packages_image" class="input-field" placeholder="https://example.com/image.jpg" style="padding: 0.5rem;">
                        </div>
                        <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin-bottom: 1rem;">
                            <input type="checkbox" name="is_available" id="Packages_available" value="1" checked style="width:auto; transform:scale(1.1);">
                            <label for="Packages_available" style="cursor:pointer; font-size: 0.9rem;">Available</label>
                        </div>
                        <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
                            <button type="button" id="cancelPackagesModal" class="btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Cancel</button>
                            <button type="submit" class="submitBtn" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Message Modal -->
            <div id="messageModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:1100; align-items:center; justify-content:center;">
                <div style="background:white; padding:1.5rem; border-radius:12px; width:90%; max-width:350px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1);">
                    <div id="msgIconContainer" style="width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1rem;">
                        <i id="msgIcon" class="fa-solid" style="font-size:1.5rem;"></i>
                    </div>
                    <h3 id="msgTitle" style="margin-bottom:0.25rem; font-size: 1.1rem;"></h3>
                    <p id="msgBody" style="color:var(--text-muted); margin-bottom:1.5rem; font-size: 0.9rem;"></p>
                    <button onclick="document.getElementById('messageModal').style.display='none'" class="submitBtn" style="width:100%; padding: 0.5rem;">Okay</button>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    <script>
        const api = '<?php echo $base; ?>' + '/php/database';
        let playgrounds = [];
        let currentPage = 1;

        // Load playgrounds for dropdowns
        fetch(api + '/admin_playgrounds.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.playgrounds) {
                    playgrounds = data.playgrounds;
                    const sel = document.getElementById('restFilter');
                    const sel2 = document.getElementById('Packages_playground_id');

                    // Clear existing options except first
                    sel.innerHTML = '<option value="">All playgrounds</option>';
                    sel2.innerHTML = ''; // Start empty

                    playgrounds.forEach(r => {
                        sel.innerHTML += `<option value="${r.id}">${escapeHtml(r.name)}</option>`;
                        sel2.innerHTML += `<option value="${r.id}">${escapeHtml(r.name)}</option>`;
                    });

                    document.getElementById('restFilter').onchange = () => loadPackages(1);
                    loadPackages();
                }
            });

        function loadPackages(page = 1) {
            currentPage = page;
            const rid = document.getElementById('restFilter').value;
            let url = api + '/admin_play_packages.php?page=' + page;
            if (rid) url += '&playground_id=' + rid;

            fetch(url, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    const totalPages = data.pagination?.total_pages || 1;

                    if (!data.Packages.length) {
                        document.getElementById('PackagesList').innerHTML = `
                            <div class="empty-state" style="padding: 2rem; text-align: center; border: 2px dashed var(--border-light); border-radius: var(--radius-lg);">
                                <i class="fa-solid fa-shapes" style="font-size: 2rem; color: var(--border-medium); margin-bottom: 0.5rem;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-size: 0.9rem;">No Play Packages found.</p>
                            </div>`;
                        return;
                    }

                    const getRestName = (id) => (playgrounds.find(r => r.id == id) || {}).name || 'Unknown';

                    let html = '<table class="orders-table" style="width:100%; border-collapse:separate; border-spacing:0 0.25rem;"><thead><tr style="text-align:left; color:var(--text-muted); font-size:0.85rem;"> <th style="padding:0.75rem;">Image</th> <th style="padding:0.75rem;">Details</th> <th style="padding:0.75rem;">Price</th> <th style="padding:0.75rem;">Status</th> <th style="padding:0.75rem; text-align:right;">Actions</th> </tr></thead><tbody>';

                    data.Packages.forEach(m => {
                        const icon = m.image_path
                            ? `<img src="${escapeHtml(m.image_path)}" style="width:40px; height:40px; object-fit:cover; border-radius:6px;">`
                            : `<div style="width:40px; height:40px; background:var(--bg-body); border-radius:6px; display:flex; align-items:center; justify-content:center; color:var(--text-muted); font-size: 0.9rem;"><i class="fa-solid fa-shapes"></i></div>`;

                        const isAvail = m.is_available == 1;
                        const statusBadge = isAvail
                            ? `<span class="status-badge no-dot status-ok" style="font-size: 0.8rem; padding: 0.2rem 0.6rem;">Available</span>`
                            : `<span class="status-badge no-dot status-trash" style="font-size: 0.8rem; padding: 0.2rem 0.6rem;">Unavailable</span>`;

                        const toggleBtnKey = isAvail ? 'Mark Unavailable' : 'Mark Available';

                        html += `<tr style="background:var(--bg-card); box-shadow:var(--shadow-sm); border-radius:6px;">
                            <td style="padding:0.75rem; border-top-left-radius:6px; border-bottom-left-radius:6px;">${icon}</td>
                            <td style="padding:0.75rem;">
                                <div style="font-weight:600; font-size:0.95rem; margin-bottom:0.1rem;">${escapeHtml(m.name)}</div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-solid fa-tent" style="font-size:0.8em; margin-right:4px;"></i> ${escapeHtml(getRestName(m.playground_id))}</div>
                            </td>
                            <td style="padding:0.75rem;"><span style="font-weight:700; color:var(--primary-color);">₱${parseFloat(m.price).toFixed(2)}</span></td>
                            <td style="padding:0.75rem;">${statusBadge}</td>
                            <td style="padding:0.75rem; text-align:right; border-top-right-radius:6px; border-bottom-right-radius:6px;">
                                <button class="btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.8rem;" onclick='openEditModal(${JSON.stringify(m)})'>
                                    Edit
                                </button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';

                     if (totalPages > 1) {
                         html += `<div class="pagination-controls" style="display:flex; justify-content:space-between; align-items:center; margin-top:1rem;">
                            <span class="pagination-info" style="color:var(--text-muted); font-size: 0.85rem;">Page ${currentPage} of ${totalPages}</span>
                            <div style="display:flex; gap:0.5rem;">
                                <button class="btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;" onclick="loadPackages(currentPage-1)" ${currentPage<=1?'disabled':''}>Previous</button>
                                <button class="btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;" onclick="loadPackages(currentPage+1)" ${currentPage>=totalPages?'disabled':''}>Next</button>
                            </div>
                        </div>`;
                    }

                    document.getElementById('PackagesList').innerHTML = html;
                });
        }

        function toggleAvail(id, status) {
            const fd = new FormData();
            fd.append('action', 'toggle_available');
            fd.append('id', id);
            fd.append('status', status);
            fetch(api + '/admin_play_packages.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        showMessageModal('success', 'Status Updated', 'Play Package status has been updated.');
                        loadPackages(currentPage);
                    } else {
                        showMessageModal('error', 'Update Failed', d.error || 'Could not update status.');
                    }
                });
        }

        function openEditModal(m) {
            document.getElementById('PackagesModalTitle').textContent = 'Edit Play Package';
            document.getElementById('Packages_id').value = m.id;
            document.getElementById('Packages_playground_id').value = m.playground_id;
            document.getElementById('Packages_name').value = m.name || '';
            document.getElementById('Packages_desc').value = m.description || '';
            document.getElementById('Packages_price').value = m.price || '';
            document.getElementById('Packages_image').value = m.image_path || '';
            document.getElementById('Packages_available').checked = m.is_available == 1;
            document.getElementById('PackagesModal').style.display = 'flex';
        }

        document.getElementById('addPackagesBtn').onclick = () => {
            document.getElementById('PackagesModalTitle').textContent = 'Add Play Package';
            document.getElementById('PackagesForm').reset();
            document.getElementById('Packages_id').value = '';
            document.getElementById('Packages_available').checked = true;
            document.getElementById('PackagesModal').style.display = 'flex';
        };

        const closeEls = [document.getElementById('closePackagesModal'), document.getElementById('cancelPackagesModal')];
        closeEls.forEach(el => el.onclick = () => document.getElementById('PackagesModal').style.display = 'none');

        document.getElementById('PackagesForm').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'save');
            if (!document.getElementById('Packages_available').checked) {
                fd.append('is_available', 0);
            }

            fetch(api + '/admin_play_packages.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('PackagesModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Success', 'Play Package saved successfully.');
                        loadPackages(currentPage);
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Could not save Play Package.');
                    }
                })
                .catch(() => {
                     document.getElementById('PackagesModal').style.display = 'none';
                     showMessageModal('error', 'Error', 'Network error occurred.');
                });
        };

        function showMessageModal(type, title, message) {
            const modal = document.getElementById('messageModal');
            const iconContainer = document.getElementById('msgIconContainer');
            const icon = document.getElementById('msgIcon');
            const titleEl = document.getElementById('msgTitle');
            const bodyEl = document.getElementById('msgBody');

            titleEl.textContent = title;
            bodyEl.textContent = message;

            if (type === 'success') {
                iconContainer.style.background = '#dcfce7';
                icon.className = 'fa-solid fa-check';
                icon.style.color = '#16a34a';
                titleEl.style.color = '#16a34a';
            } else {
                iconContainer.style.background = '#fee2e2';
                icon.className = 'fa-solid fa-xmark';
                icon.style.color = '#dc2626';
                titleEl.style.color = '#dc2626';
            }

            modal.style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    </script>
</body>
</html>




