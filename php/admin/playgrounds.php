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
    <title>Manage Playgrounds - LittleLands</title>
    <link rel="stylesheet" href="<?php echo $base; ?>/css/serve_asset.php?file=design-system.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/css/serve_asset.php?file=dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'admin_playgrounds';
        include __DIR__ . '/../includes/layout/sidebar.php'; ?>
        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Manage Playgrounds</h1>
                <p class="page-subtitle">Add, edit, or deactivate playgrounds within the LittleLands system.</p>

                <div style="margin-bottom: 2rem; display: flex; justify-content: flex-start;">
                    <button type="button" id="addplaygroundBtn" class="submitBtn"
                        style="display: flex; align-items: center; gap: 0.75rem; font-size: 0.95rem; padding: 0.75rem 1.5rem; border-radius: 12px;">
                        <i class="fa-solid fa-plus"></i> Add Playground
                    </button>
                </div>

                <div id="playgroundsList" class="Area-container"></div>

                <!-- Edit/Add Modal -->
                <div id="playgroundModal" class="modal-overlay"
                    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:1500; align-items:center; justify-content:center;">
                    <div class="modal-content"
                        style="background:var(--bg-card); padding:2rem; border-radius:24px; width:90%; max-width:600px; box-shadow:var(--shadow-lg); max-height:90vh; overflow-y:auto; border: 1px solid var(--border);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                            <h2 id="modalTitle" style="margin:0; font-family:'Outfit',sans-serif; font-weight:700;">Add Playground</h2>
                            <button type="button" id="closeModal"
                                style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--text-muted);">&times;</button>
                        </div>
                        <form id="playgroundForm">
                            <input type="hidden" name="id" id="rest_id" value="">

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_name" class="input-label">Playground Name</label>
                                    <input type="text" name="name" id="rest_name" class="input-field" required
                                        placeholder="e.g. KidZania" style="border-radius: 10px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_Category" class="input-label">Playground Type</label>
                                    <input type="text" name="playground_type" id="rest_Category" class="input-field"
                                        placeholder="e.g. Indoor, Adventure" style="border-radius: 10px;">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="rest_desc" class="input-label">Description</label>
                                <textarea name="description" id="rest_desc" class="input-field" rows="3"
                                    placeholder="Brief description..." style="border-radius: 10px;"></textarea>
                            </div>
                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="rest_address" class="input-label">Address</label>
                                <input type="text" name="address" id="rest_address" class="input-field"
                                    placeholder="Full address" style="border-radius: 10px;">
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.25rem;">
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_capacity" class="input-label">Capacity</label>
                                    <input type="number" name="capacity" id="rest_capacity" class="input-field" value="50" style="border-radius: 10px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_price" class="input-label">Price Range</label>
                                    <select name="price_range" id="rest_price" class="input-field" style="border-radius: 10px;">
                                        <option value="$">$ (Cheap)</option>
                                        <option value="$$" selected>$$ (Moderate)</option>
                                        <option value="$$$">$$$ (Expensive)</option>
                                        <option value="$$$$">$$$$ (Luxury)</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label class="input-label">Active?</label>
                                    <div style="display:flex; align-items:center; height:48px;">
                                        <input type="checkbox" name="is_active" id="rest_active" value="1" checked
                                            style="width:24px; height:24px; cursor:pointer; accent-color: var(--primary);">
                                    </div>
                                </div>
                            </div>

                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1.25rem;">
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_open" class="input-label">Opening Time</label>
                                    <input type="time" name="opening_time" id="rest_open" class="input-field" value="09:00" style="border-radius: 10px;">
                                </div>
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="rest_close" class="input-label">Closing Time</label>
                                    <input type="time" name="closing_time" id="rest_close" class="input-field"
                                        value="22:00" style="border-radius: 10px;">
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1rem;">
                                <label for="rest_image" class="input-label">Image URL (Optional)</label>
                                <input type="url" name="image_path" id="rest_image" class="input-field"
                                    placeholder="https://example.com/image.jpg" style="border-radius: 10px;">
                            </div>

                            <div style="display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem;">
                                <button type="button" id="cancelModal" class="btn-secondary"
                                    style="padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700;">Cancel</button>
                                <button type="submit" class="submitBtn"
                                    style="padding: 0.75rem 1.5rem; border-radius: 12px; font-weight: 700;">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Message Modal -->
                <div id="messageModal"
                    style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:2000; align-items:center; justify-content:center;">
                    <div
                        style="background:white; padding:2rem; border-radius:24px; width:90%; max-width:380px; text-align:center; box-shadow:var(--shadow-lg);">
                        <div id="msgIconContainer"
                            style="width:60px; height:60px; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 1.5rem;">
                            <i id="msgIcon" class="fa-solid" style="font-size:1.75rem;"></i>
                        </div>
                        <h3 id="msgTitle" style="margin-bottom:0.5rem; font-family:'Outfit',sans-serif; font-weight:700;"></h3>
                        <p id="msgBody" style="color:var(--text-muted); margin-bottom:2rem; font-size: 0.95rem;"></p>
                        <button onclick="document.getElementById('messageModal').style.display='none'" class="submitBtn"
                            style="width:100%; padding: 0.85rem; border-radius: 12px; font-weight: 700;">Okay</button>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>
    <script>
        const api = '<?php echo $base; ?>' + '/php/database';
        let currentPage = 1;
        let totalPages = 1;

        function load(page = 1) {
            currentPage = page;
            fetch(api + '/admin_playgrounds.php?page=' + page, { credentials: 'same-origin' })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    totalPages = data.pagination?.total_pages || 1;

                    if (!data.playgrounds.length) {
                        document.getElementById('playgroundsList').innerHTML = `
                            <div class="empty-state" style="padding: 5rem 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 2px dashed var(--border); border-radius: 24px; background: #fafafa; gap: 1.5rem; text-align: center;">
                                <i class="fa-solid fa-tent" style="font-size: 3.5rem; color: #cbd5e0;"></i>
                                <p style="color: var(--text-muted); margin: 0; font-size: 1.1rem; font-weight: 700; font-family: 'Outfit', sans-serif;">No playgrounds found.</p>
                            </div>`;
                        return;
                    }

                    let html = '<table class="data-table" style="width:100%;"><thead><tr> <th>Image</th> <th>Details</th> <th>Info</th> <th>Status</th> <th style="text-align:right;">Actions</th> </tr></thead><tbody>';

                    data.playgrounds.forEach(r => {
                        const icon = r.image_path
                            ? `<img src="${escapeHtml(r.image_path)}" class="table-thumb">`
                            : `<div class="table-thumb"><i class="fa-solid fa-tent"></i></div>`;

                        const isActive = r.is_active == 1;
                        const statusBadge = isActive
                            ? `<span class="status-badge no-dot status-ok">Active</span>`
                            : `<span class="status-badge no-dot status-trash">Inactive</span>`;

                        html += `<tr>
                            <td>${icon}</td>
                            <td>
                                <div class="table-primary-text">${escapeHtml(r.name)}</div>
                                <div class="table-secondary-text">${escapeHtml(r.description || 'No description')}</div>
                            </td>
                            <td>
                                <div style="font-size:0.85rem; color:var(--text-muted); margin-bottom:0.15rem;">${escapeHtml(r.playground_type || 'General')} • ${escapeHtml(r.price_range || '$$')}</div>
                                <div style="font-size:0.8rem; color:var(--text-muted);"><i class="fa-regular fa-clock" style="width:14px;"></i> ${escapeHtml(r.opening_time)} - ${escapeHtml(r.closing_time)}</div>
                            </td>
                            <td>${statusBadge}</td>
                            <td style="text-align:right;">
                                <a href="javascript:void(0)" style="color:var(--primary-color); font-weight:600; font-size:0.85rem; text-decoration:none; cursor:pointer;" onclick='openEditModal(${JSON.stringify(r)})'>Edit</a>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';

                    if (totalPages > 1) {
                        html += `<div class="pagination-controls">
                            <span class="pagination-info">Page ${currentPage} of ${totalPages}</span>
                            <div style="display:flex; gap:0.5rem;">
                                <button class="btn-secondary btn-sm" onclick="load(currentPage-1)" ${currentPage <= 1 ? 'disabled' : ''}>Previous</button>
                                <button class="btn-secondary btn-sm" onclick="load(currentPage+1)" ${currentPage >= totalPages ? 'disabled' : ''}>Next</button>
                            </div>
                        </div>`;
                    }

                    document.getElementById('playgroundsList').innerHTML = html;
                });
        }

        function toggleRest(id, status) {
            // ... (keeping toggle logic if needed, though not exposed in UI currently)
        }

        function openEditModal(r) {
            document.getElementById('modalTitle').textContent = 'Edit playground';
            document.getElementById('rest_id').value = r.id;
            document.getElementById('rest_name').value = r.name || '';
            document.getElementById('rest_desc').value = r.description || '';
            document.getElementById('rest_address').value = r.address || '';
            document.getElementById('rest_image').value = r.image_path || '';
            document.getElementById('rest_Category').value = r.playground_type || '';
            document.getElementById('rest_capacity').value = r.capacity || 50;
            document.getElementById('rest_open').value = r.opening_time || '09:00';
            document.getElementById('rest_close').value = r.closing_time || '22:00';
            document.getElementById('rest_price').value = r.price_range || '$$';
            document.getElementById('rest_active').checked = r.is_active == 1;
            document.getElementById('playgroundModal').style.display = 'flex';
        }

        document.getElementById('addplaygroundBtn').onclick = () => {
            document.getElementById('modalTitle').textContent = 'Add playground';
            document.getElementById('playgroundForm').reset();
            document.getElementById('rest_id').value = '';
            document.getElementById('rest_active').checked = true;
            document.getElementById('playgroundModal').style.display = 'flex';
        };

        const closeEls = [document.getElementById('closeModal'), document.getElementById('cancelModal')];
        closeEls.forEach(el => el.onclick = () => document.getElementById('playgroundModal').style.display = 'none');

        document.getElementById('playgroundForm').onsubmit = (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'save');
            // Checkbox handling: if unchecked, it's not in FormData, so let's handle it manually or rely on PHP checking isset
            // But PHP 'save' likely expects 'is_active'.
            if (!document.getElementById('rest_active').checked) {
                fd.append('is_active', 0);
            }

            fetch(api + '/admin_playgrounds.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('playgroundModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Success', 'playground saved successfully.');
                        load(currentPage);
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Could not save playground.');
                    }
                })
                .catch(() => {
                    document.getElementById('playgroundModal').style.display = 'none';
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

        load();
    </script>
</body>

</html>