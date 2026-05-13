<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('superadmin');
$pageTitle = 'User Management';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - LittleLands</title>
    <link rel="stylesheet" href="../../css/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .password-container { position: relative; display: flex; align-items: center; }
        .pw-toggle {
            position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: #94a3b8; cursor: pointer;
            padding: 0.25rem; display: flex; align-items: center; justify-content: center;
            transition: color 0.2s; z-index: 5;
        }
        .pw-toggle:hover { color: var(--primary-color); }
        .password-container input { padding-right: 2.5rem !important; }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php $currentPage = 'superadmin_users';
        include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage identities, roles, and access across the platform.</p>

                <div class="filters">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="searchInput" class="input-field" placeholder="Search accounts..." style="padding-left:48px;" onkeyup="handleSearch(event)">
                    </div>
                    <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                        <select id="filterRole" class="input-field" style="width: auto;" onchange="applyFilters()">
                            <option value="">All Roles</option>
                            <option value="basic-user">Basic User</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Superadmin</option>
                        </select>
                        <select id="filterStatus" class="input-field" style="width: auto;" onchange="applyFilters()">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="blocked">Blocked</option>
                        </select>
                        <button class="submitBtn" onclick="openUserModal()" style="white-space: nowrap;">
                            <i class="fa-solid fa-plus" style="margin-right: 8px;"></i> Add User
                        </button>
                    </div>
                </div>

                <div id="usersTableContainer" class="Area-container">
                    <div style="padding: 2rem; text-align: center; color: var(--text-muted);">Loading users...</div>
                </div>

                <div class="pagination-container" style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                    <span id="userCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Users</span>
                    <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal2">
        <div class="modal2-content" style="max-width: 420px; text-align: center; padding: 2.5rem;">
            <div style="width: 64px; height: 64px; background: #fef2f2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 1.75rem;">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h2 style="margin: 0 0 0.5rem; font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700; color: var(--text-heading);">Confirm Delete</h2>
            <p style="color: var(--text-muted); line-height: 1.5; margin-bottom: 1.5rem;">
                Are you sure you want to permanently delete <span id="deleteUserName" style="font-weight: 800; color: var(--text-heading);"></span>?
                <br><small style="display: block; margin-top: 0.5rem;">This action cannot be undone.</small>
            </p>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <button onclick="closeDeleteModal()" class="btn-secondary" style="justify-content: center;">Cancel</button>
                <button id="confirmDeleteBtn" class="btn-primary" style="background: var(--error-color); justify-content: center;">Delete User</button>
            </div>
        </div>
    </div>

    <!-- Response Modal -->
    <div id="responseModal" class="modal2" style="z-index: 2100;">
        <div class="modal2-content" style="max-width: 400px; text-align: center; padding: 2.5rem;">
            <div id="responseIcon" style="font-size: 3.5rem; margin-bottom: 1.5rem;"></div>
            <h2 id="responseTitle" style="margin: 0 0 0.5rem; font-family: 'Outfit', sans-serif; font-size: 1.5rem; font-weight: 700;">Success</h2>
            <p id="responseMessage" style="color: var(--text-muted); line-height: 1.5; margin-bottom: 2rem;"></p>
            <button onclick="closeResponseModal()" class="btn-primary" style="width: 100%; justify-content: center;">Got it</button>
        </div>
    </div>

    <!-- User Modal (Add/Edit) -->
    <div id="userModal" class="modal2">
        <div class="modal2-content" style="max-width: 800px; width: 95%;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 id="modalTitle" style="margin:0; font-size:1.5rem; font-weight:700; color:var(--text-heading);">Add User</h2>
                <button onclick="closeUserModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>

            <!-- Stepper -->
            <div class="modal-stepper">
                <div class="step-item active" id="stepIndicator0">
                    <div class="step-circle">1</div>
                    <span class="step-label">Personal</span>
                </div>
                <div class="step-item" id="stepIndicator1">
                    <div class="step-circle">2</div>
                    <span class="step-label">Address</span>
                </div>
                <div class="step-item" id="stepIndicator2">
                    <div class="step-circle">3</div>
                    <span class="step-label">Account</span>
                </div>
            </div>

            <form id="userForm" novalidate class="modal-scrollable">
                <input type="hidden" name="id" id="userId">

                <div class="form-step" id="step0">
                    <div class="form-section-title">Personal Information</div>
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Select User Role <span class="required">*</span></label>
                            <select name="role" id="role" class="input-field">
                                <option value="basic-user">Basic User</option>
                                <option value="admin">Admin</option>
                                <option value="superadmin">Superadmin</option>
                            </select>
                            <span class="hint">Administrative roles (Admin/Superadmin) skip security question setup.</span>
                        </div>
                            <div class="form-group">
                                <label>Identity Number <span class="hint">(xxxx-xxxx)</span></label>
                                <input type="text" name="custom_id" id="customId" placeholder="0000-0000"
                                    class="input-field">
                                <span class="validation-message" id="customIdError"></span>
                            </div>
                            <div class="form-group">
                                <label>First Name<span class="required">*</span></label>
                                <input type="text" name="firstName" id="firstName" class="input-field">
                                <span class="validation-message" id="firstNameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Last Name<span class="required">*</span></label>
                                <input type="text" name="lastName" id="lastName" class="input-field">
                                <span class="validation-message" id="lastNameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Middle Initial</label>
                                <input type="text" name="middleInitial" id="middleInitial" class="input-field">
                                <span class="validation-message" id="middleInitialError"></span>
                            </div>
                            <div class="form-group">
                                <label>Suffix / Extension</label>
                                <input type="text" name="extension" id="extension" class="input-field">
                                <span class="validation-message" id="extensionError"></span>
                            </div>
                            <div class="form-group">
                                <label>Sex<span class="required">*</span></label>
                                <select name="sex" id="sex" class="input-field">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <span class="validation-message" id="sexError"></span>
                            </div>
                            <div class="form-group">
                                <label>Birthdate<span class="required">*</span></label>
                                <input type="date" name="birthdate" id="birthdate" class="input-field">
                                <span class="validation-message" id="birthdateError"></span>
                            </div>
                            <div class="form-group">
                                <label>Calculated Age</label>
                                <input type="number" name="age" id="age" readonly class="input-field"
                                    style="background:#f3f4f6; color:#6b7280; font-weight:600;">
                            </div>
                    </div>
                </div>

                <!-- STEP 2: Address -->
                <div class="form-step" id="step1" style="display:none;">
                    <div class="form-section-title">Address Details</div>
                    <div class="form-grid">
                            <div class="form-group">
                                <label>Purok / Zone</label>
                                <input type="text" name="purok" id="purok" class="input-field">
                                <span class="validation-message" id="purokError"></span>
                            </div>
                            <div class="form-group">
                                <label>Barangay</label>
                                <input type="text" name="barangay" id="barangay" class="input-field">
                                <span class="validation-message" id="barangayError"></span>
                            </div>
                            <div class="form-group">
                                <label>City / Municipality</label>
                                <input type="text" name="city" id="city" class="input-field">
                                <span class="validation-message" id="cityError"></span>
                            </div>
                            <div class="form-group">
                                <label>Province</label>
                                <input type="text" name="province" id="province" class="input-field">
                                <span class="validation-message" id="provinceError"></span>
                            </div>
                            <div class="form-group">
                                <label>Zip Code</label>
                                <input type="text" name="zipCode" id="zipCode" class="input-field">
                                <span class="validation-message" id="zipCodeError"></span>
                            </div>
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" id="country" value="Philippines" class="input-field">
                                <span class="validation-message" id="countryError"></span>
                            </div>
                        </div>
                </div>

                <!-- STEP 3: Credentials -->
                <div class="form-step" id="step2" style="display:none;">
                    <div class="form-section-title">Account Credentials</div>
                    <div class="form-grid">
                            <div class="form-group">
                                <label>Username<span class="required">*</span></label>
                                <input type="text" name="username" id="username" class="input-field">
                                <span class="validation-message" id="usernameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Email Address<span class="required">*</span></label>
                                <input type="email" name="email" id="email" class="input-field">
                                <span class="validation-message" id="emailError"></span>
                            </div>
                            <div class="form-group">
                                <label>Account Password <span id="pwHint" class="hint"></span></label>
                                <div class="password-container">
                                    <input type="password" name="password" id="password" autocomplete="new-password" class="input-field">
                                    <button type="button" class="pw-toggle" data-target="password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span class="validation-message" id="passwordError"></span>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password</label>
                                <div class="password-container">
                                    <input type="password" name="repassword" id="repassword" autocomplete="new-password" class="input-field">
                                    <button type="button" class="pw-toggle" data-target="repassword">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                                <span class="validation-message" id="repasswordError"></span>
                            </div>
                        </div>
                </div>



                <div class="form-navigation" style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #f1f5f9;">
                    <div class="nav-left">
                        <button type="button" id="prevBtn" onclick="prevStep()" class="btn-secondary" style="display:none; min-width: 120px;">
                            <i class="fa-solid fa-arrow-left"></i> Previous
                        </button>
                    </div>
                    <div class="nav-right" style="display:flex; gap:12px;">
                        <button type="button" id="nextBtn" onclick="nextStep()" class="btn-primary" style="min-width: 120px; justify-content: center;">
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="submit" id="submitBtn" class="btn-primary" style="display:none; background:#10b981; min-width: 160px; justify-content: center;">
                            Complete & Save <i class="fa-solid fa-circle-check"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Privileges Modal -->
    <div id="privModal" class="modal2">
        <div class="modal2-content" style="max-width:550px; width:95%; max-height: 90vh; display: flex; flex-direction: column;">
            <h2 style="margin:0 0 0.5rem; font-size:1.5rem; color:#1f2937;">Account Privileges</h2>
            <p id="privUserName" style="font-weight: 600; margin-bottom: 1.5rem; color:#64748b; font-size:0.9rem;"></p>
            <div id="privContent" class="modal-scrollable" style="width: 100%; flex: 1;"></div>
            <button onclick="document.getElementById('privModal').style.display='none'" class="btn-primary"
                style="margin-top: 1.5rem; width:100%; justify-content:center;">Close</button>
        </div>
    </div>

    <script src="../../js/admin_user_validation.js?v=<?php echo time(); ?>"></script>
    <script src="../../js/pagination_util.js"></script>
    <script>
        function toggleAnswerVisibility(icon) {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        const api = '../../php/database';
        const currentUserId = '<?php echo $_SESSION['user']['id']; ?>';

        let currentPage = 1;
        let limit = 5;
        let currentSearch = '';
        let currentRole = '';
        let currentStatus = '';
        let usersMap = {};
        let editingUserId = '';

        function loadUsers(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({ page, limit, search: currentSearch, role: currentRole, status: currentStatus });
            fetch(api + '/superadmin_users_list.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Status</th><th>Actions</th><th>Privileges</th></tr></thead><tbody>';
                    usersMap = {};
                    data.users.forEach(u => { usersMap[u.id] = u; });
                    if (data.users.length === 0) {
                        html += '<tr><td colspan="6" style="text-align:center; padding:2rem; color: var(--text-muted);">No users found matching your criteria.</td></tr>';
                    } else {
                        data.users.forEach(u => {
                            const isBlocked = u.is_blocked == 1;
                            const isSelf = u.id === currentUserId;
                            const rowClass = isBlocked ? 'blocked-row' : '';
                            const roleLower = (u.role || 'basic-user').toLowerCase();
                            let roleClass = roleLower === 'admin' ? 'status-ok' : (roleLower === 'superadmin' ? 'status-trash' : 'status-pending');
                            html += `<tr class="${rowClass}">
                                <td style="font-weight: 500;">${escapeHtml(u.firstName + ' ' + u.lastName)}</td>
                                <td class="text-muted">@${escapeHtml(u.username)}</td>
                                <td><span class="status-badge ${roleClass}">${escapeHtml(u.role || 'basic-user')}</span></td>
                                <td>${isBlocked ? '<span class="status-badge status-trash">Blocked</span>' : '<span class="status-badge status-ok">Active</span>'}</td>
                                <td>
                                    <div style="display: flex; gap: 4px;">
                                        <button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;" onclick="editUser('${u.id}')">Edit</button>
                                        ${!isSelf ? (isBlocked ?
                                    `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #10b981; border-color: #10b981;" onclick="blockUser('${u.id}', 'unblock')">Unblock</button>` :
                                    `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #ef4444; border-color: #ef4444;" onclick="blockUser('${u.id}', 'block')">Block</button>`) : ''}
                                        ${!isSelf ? `<button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: #ef4444; border-color: #ef4444;" onclick="openDeleteModal('${u.id}', '${escapeHtml(u.firstName + ' ' + u.lastName)}')">Delete</button>` : ''}
                                    </div>
                                </td>
                                <td><button class="btn-secondary" style="padding: 4px 10px; font-size: 0.75rem;" onclick="viewPrivileges('${u.role}', '${escapeHtml(u.username)}')">View</button></td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('usersTableContainer').innerHTML = html;
                    updatePagination(data.pagination);
                });
        }

        function updatePagination(p) {
            const controls = document.getElementById('paginationControls');
            const badge = document.getElementById('userCountBadge');
            badge.textContent = `${p.total_users} User${p.total_users !== 1 ? 's' : ''}`;
            if (p.total_users > 0) { badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
            else { badge.style.background = '#fee2e2'; badge.style.color = '#dc2626'; }
            window.renderPagination(controls, currentPage, p.total_pages || 1, limit, n => loadUsers(n), l => { limit = l; loadUsers(1); });
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => { currentSearch = e.target.value; loadUsers(1); }, 300);
        }

        function applyFilters() {
            currentRole = document.getElementById('filterRole').value;
            currentStatus = document.getElementById('filterStatus').value;
            loadUsers(1);
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterRole').value = '';
            document.getElementById('filterStatus').value = '';
            currentSearch = ''; currentRole = ''; currentStatus = '';
            loadUsers(1);
        }

        let userToDelete = null;
        function openDeleteModal(id, name) {
            userToDelete = id;
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; userToDelete = null; }

        function showResponse(success, message) {
            const title = document.getElementById('responseTitle');
            const msg = document.getElementById('responseMessage');
            const icon = document.getElementById('responseIcon');
            title.textContent = success ? 'Success' : 'Error';
            title.style.color = success ? '#10b981' : '#ef4444';
            msg.textContent = message;
            icon.innerHTML = success ? '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i>' : '<i class="fa-solid fa-circle-xmark" style="color:#ef4444;"></i>';
            document.getElementById('responseModal').style.display = 'flex';
        }

        function closeResponseModal() { document.getElementById('responseModal').style.display = 'none'; }

        document.getElementById('confirmDeleteBtn').onclick = function () {
            if (!userToDelete) return;
            const fd = new FormData(); fd.append('user_id', userToDelete);
            fetch(api + '/superadmin_user_delete.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    closeDeleteModal();
                    if (d.success) { showResponse(true, 'User deleted successfully.'); loadUsers(currentPage); }
                    else showResponse(false, d.error || 'Delete failed.');
                });
        };

        function blockUser(userId, action) {
            if (!userId) return;
            if (!confirm(`Are you sure you want to ${action} this user?`)) return;
            fetch(api + '/superadmin_user_block.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'user_id=' + encodeURIComponent(userId) + '&action=' + encodeURIComponent(action)
            }).then(r => r.json()).then(d => {
                if (d.success) { showResponse(true, `User ${action}ed.`); loadUsers(currentPage); }
                else showResponse(false, d.error || 'Action failed.');
            });
        }

        let currentStep = 0;
        const totalSteps = 3;

        function showStep(n) {
            document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
            const activeStep = document.getElementById('step' + n);
            if (activeStep) activeStep.style.display = 'block';

            document.querySelectorAll('.step-item').forEach((item, index) => {
                item.classList.remove('active', 'completed');
                if (index === n) {
                    item.classList.add('active');
                    item.querySelector('.step-circle').innerHTML = index + 1;
                } else if (index < n) {
                    item.classList.add('completed');
                    item.querySelector('.step-circle').innerHTML = '<i class="fa-solid fa-check"></i>';
                } else {
                    item.querySelector('.step-circle').innerHTML = index + 1;
                }
            });

            document.getElementById('prevBtn').style.display = (n === 0) ? 'none' : 'inline-block';
            
            if (n >= totalSteps - 1) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'inline-block';
            } else {
                document.getElementById('nextBtn').style.display = 'inline-block';
                document.getElementById('submitBtn').style.display = 'none';
            }
        }

        async function nextStep() {
            const isEdit = !!editingUserId;
            AdminUserValidation.clearAllErrors();
            let isValid = false;

            if (currentStep === 0) isValid = await AdminUserValidation.validatePersonalInfo();
            else if (currentStep === 1) isValid = AdminUserValidation.validateAddress();
            else if (currentStep === 2) isValid = await AdminUserValidation.validateCredentials(isEdit);

            if (isValid) { currentStep++; showStep(currentStep); }
        }

        function prevStep() { if (currentStep > 0) { currentStep--; showStep(currentStep); } }

        function openUserModal() {
            editingUserId = '';
            AdminUserValidation.setEditMode(false);
            document.getElementById('modalTitle').textContent = 'Add User';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('pwHint').textContent = '(Required for new user)';
            document.getElementById('role').value = 'basic-user';
            document.getElementById('role').dispatchEvent(new Event('change'));
            AdminUserValidation.clearAllErrors();
            currentStep = 0; showStep(0);
            document.getElementById('userModal').style.display = 'flex';
        }

        document.getElementById('role').addEventListener('change', () => { showStep(currentStep); });

        function closeUserModal() { document.getElementById('userModal').style.display = 'none'; }

        function editUser(userId) {
            const u = usersMap[userId];
            if (!u) return;

            editingUserId = u.id;
            AdminUserValidation.setEditMode(true);
            document.getElementById('modalTitle').textContent = 'Edit User';
            
            // Core Identity
            document.getElementById('userId').value = u.id;
            document.getElementById('customId').value = u.id || '';
            
            // Personal Information
            document.getElementById('firstName').value = u.firstName || '';
            document.getElementById('lastName').value = u.lastName || '';
            document.getElementById('middleInitial').value = u.middleInitial || '';
            document.getElementById('extension').value = u.extension || '';
            document.getElementById('sex').value = (u.sex || '').toLowerCase();
            
            // Date Restoration
            if (u.birthdate) {
                // Ensure we get YYYY-MM-DD even if DB returns datetime or weird format
                let rawDate = String(u.birthdate).split(' ')[0].split('T')[0];
                if (rawDate && rawDate !== '0000-00-00') {
                    // Try to normalize to YYYY-MM-DD
                    const parts = rawDate.split(/[-/]/);
                    if (parts.length === 3) {
                        if (parts[0].length === 4) document.getElementById('birthdate').value = parts.join('-');
                        else if (parts[2].length === 4) document.getElementById('birthdate').value = `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
                    } else {
                        document.getElementById('birthdate').value = rawDate;
                    }
                } else {
                    document.getElementById('birthdate').value = '';
                }
            } else {
                document.getElementById('birthdate').value = '';
            }
            document.getElementById('age').value = u.age || '';

            // Address Information
            document.getElementById('purok').value = u.purok || '';
            document.getElementById('barangay').value = u.barangay || '';
            document.getElementById('city').value = u.city || '';
            document.getElementById('province').value = u.province || '';
            document.getElementById('zipCode').value = u.zipCode || '';
            document.getElementById('country').value = u.country || '';

            // Account Credentials
            document.getElementById('username').value = u.username || '';
            document.getElementById('email').value = u.email || '';
            document.getElementById('password').value = '';
            document.getElementById('pwHint').textContent = '(Leave blank to keep current)';

            // Role Handling
            const roleEl = document.getElementById('role');
            roleEl.value = (u.role || 'basic-user').toLowerCase();
            
            // Security Questions
            if (u.role === 'basic-user') {
                if (document.getElementById('sq1')) document.getElementById('sq1').value = u.secure_question || '';
                if (document.getElementById('sq2')) document.getElementById('sq2').value = u.secure_question2 || '';
                if (document.getElementById('sq3')) document.getElementById('sq3').value = u.secure_question3 || '';
            }

            // Sync all change events and clear errors
            setTimeout(() => {
                const event = new Event('change');
                ['role', 'sex', 'birthdate', 'username', 'email'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.dispatchEvent(event);
                });
                AdminUserValidation.clearAllErrors();
            }, 50);

            currentStep = 0;
            showStep(0);
            document.getElementById('userModal').style.display = 'flex';
        }

        document.getElementById('userForm').onsubmit = async function (e) {
            e.preventDefault();
            const isEdit = !!editingUserId;
            const role = document.getElementById('role').value;

            try {
                // SEQUENTIAL VALIDATION: Check all steps before allowed to save
                if (!(await AdminUserValidation.validatePersonalInfo())) { showStep(0); return; }
                if (!AdminUserValidation.validateAddress()) { showStep(1); return; }
                if (!(await AdminUserValidation.validateCredentials(isEdit))) { showStep(2); return; }

                if (!isEdit && role === 'superadmin') {
                    if (!confirm("CRITICAL: Creating a new Superadmin will BLOCK your current account and log you out for security reasons. The new account will become the primary Superadmin. Do you wish to proceed?")) {
                        return;
                    }
                }

                const fd = new FormData(this);
                fd.append('id', editingUserId);

                const response = await fetch(api + '/superadmin_user_save.php', { method: 'POST', body: fd });
                const text = await response.text();
                let d;
                try {
                    d = JSON.parse(text);
                } catch (e) {
                    console.error('Server returned non-JSON:', text);
                    showResponse(false, 'Server Error: ' + text.substring(0, 100));
                    return;
                }

                if (d.success) {
                    closeUserModal();
                    if (d.superadmin_swap) {
                        alert("Account swapped successfully. You will now be logged out. Please log in with the new Superadmin credentials.");
                        window.location.href = '../auth/logout.php';
                        return;
                    }
                    showResponse(true, 'User data has been saved successfully.');
                    loadUsers(currentPage);
                } else {
                    showResponse(false, d.error || 'Failed to save user data.');
                }
            } catch (err) {
                console.error('Submission Error:', err);
                showResponse(false, 'A network error occurred while saving.');
            }
        };

        const privilegesConfig = {
            'superadmin': [
                { desc: 'Access My Profile', has: true },
                { desc: 'Change Password', has: true },
                { desc: 'Manage All Users (Admins & Consumers)', has: true },
                { desc: 'Approve / Reject Registration Requests', has: true },
                { desc: 'Block / Unblock Any User', has: true },
                { desc: 'Manage All playgrounds & Packages', has: true },
                { desc: 'View & Manage All Bookings', has: true },
                { desc: 'View Full Login Audit Logs', has: true },
                { desc: 'Assign / Change User Roles', has: true },
                { desc: 'Full System Administration', has: true },
            ],
            'admin': [
                { desc: 'Access My Profile', has: true },
                { desc: 'Change Password', has: true },
                { desc: 'Manage Basic User Accounts', has: true },
                { desc: 'Approve / Reject Registration Requests', has: true },
                { desc: 'Block / Unblock Consumers', has: true },
                { desc: 'Manage playgrounds & Play Packages', has: true },
                { desc: 'View & Manage Bookings', has: true },
                { desc: 'View Login History (Own)', has: true },
                { desc: 'Manage All Users (Admins)', has: false },
                { desc: 'Assign / Change User Roles', has: false },
                { desc: 'Full System Administration', has: false },
            ],
            'basic-user': [
                { desc: 'Access My Profile', has: true },
                { desc: 'Change Password', has: true },
                { desc: 'Browse playgrounds & Book', has: true },
                { desc: 'Manage Bookings', has: true },
                { desc: 'View Personal Booking History', has: true },
                { desc: 'Save Favourite playgrounds', has: true },
                { desc: 'Manage Payment Methods', has: true },
                { desc: 'Manage Basic User Accounts', has: false },
                { desc: 'Approve / Reject Registration Requests', has: false },
                { desc: 'Block / Unblock Users', has: false },
                { desc: 'Manage playgrounds & Play Packages', has: false },
                { desc: 'Full System Administration', has: false },
            ],
        };

        function viewPrivileges(role, name) {
            document.getElementById('privUserName').textContent = name + ' (' + role + ')';
            const privs = privilegesConfig[role] || privilegesConfig['basic-user'];
            const listHtml = privs.map(p => `
                <div style="display:flex; align-items:center; gap:12px; padding:12px 16px; border-radius:12px;
                     background:${p.has ? '#f0fdf4' : '#f8fafc'};
                     border:1px solid ${p.has ? '#bbf7d0' : '#e2e8f0'}; margin-bottom:8px; transition: all 0.2s;">
                    <i class="fa-solid ${p.has ? 'fa-circle-check' : 'fa-circle-xmark'}"
                       style="color:${p.has ? '#16a34a' : '#94a3b8'}; font-size:18px;"></i>
                    <span style="font-size:0.9rem; font-weight:600; color:${p.has ? '#166534' : '#64748b'};
                          text-decoration:${p.has ? 'none' : 'line-through'}; opacity:${p.has ? '1' : '0.6'};">${p.desc}</span>
                </div>`).join('');
            document.getElementById('privContent').innerHTML = listHtml;
            document.getElementById('privModal').style.display = 'flex';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        document.addEventListener('DOMContentLoaded', () => {
            if (typeof AdminUserValidation !== 'undefined') {
                AdminUserValidation.init(document.getElementById('userForm'), { apiBase: api, roleSelector: '#role', securitySection: '#securitySection' });
            }
            loadUsers(1);

            // Password Toggle logic
            document.addEventListener('click', function(e) {
                const toggle = e.target.closest('.pw-toggle');
                if (toggle) {
                    const input = document.getElementById(toggle.dataset.target);
                    const icon = toggle.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                    }
                }
            });
        });
    </script>
</body>

</html>