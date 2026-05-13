<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$pageTitle = 'Basic User Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basic User Management - LittleLands</title>
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

        /* Wizard specific polish */
        .form-step { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .form-section-title {
            font-size: 1.1rem; font-weight: 700; color: var(--primary-color);
            margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f1f5f9;
            display: flex; align-items: center; gap: 0.75rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/layout/navbar.php'; ?>
    <div class="dashboard-container">
        <?php $currentPage = 'admin_consumers'; include __DIR__ . '/../includes/layout/sidebar.php'; ?>

        <main class="dashboard-main">
            <div class="main-content-wrapper">
                <h1 class="page-title">Basic User Management</h1>
                <p class="page-subtitle">Manage and monitor all registered basic users in the system.</p>

                <div class="filters">
                    <div style="flex: 1; min-width: 300px; position: relative;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#94a3b8;"></i>
                        <input type="text" id="searchInput" class="input-field" placeholder="Search basic users..." style="padding-left:48px;" onkeyup="handleSearch(event)">
                    </div>
                    <div style="width: 200px;">
                        <select id="filterStatus" class="input-field" onchange="loadUsers(1)">
                            <option value="">All Consumers</option>
                            <option value="active">Active Only</option>
                            <option value="blocked">Blocked Accounts</option>
                        </select>
                    </div>
                    <button class="submitBtn" onclick="openUserModal()" style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa-solid fa-plus"></i> Add Consumer
                    </button>
                </div>

                <div id="usersTableContainer" class="Area-container">
                    <div style="padding: 3rem; text-align: center; color: var(--text-muted);">
                        <i class="fa-solid fa-circle-notch fa-spin fa-2x"></i>
                        <p style="margin-top: 1rem;">Loading consumers...</p>
                    </div>
                </div>

                <div class="pagination-container">
                    <span id="userCountBadge" style="background:#dbeafe; color:#1e40af; padding:0.35rem 0.8rem; border-radius:20px; font-weight:700; font-size:0.85rem;">0 Consumers</span>
                    <div id="paginationControls" style="display: flex; align-items: center; justify-content: flex-end;"></div>
                </div>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../includes/layout/footer.php'; ?>

    <!-- Basic User Modal (Wizard Add/Edit) -->
    <div id="consumerModal" class="modal2">
        <div class="modal2-content" style="max-width: 800px; width: 95%;">
             <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
                <h2 id="modalTitle" style="margin:0; font-size:1.5rem; font-weight:700; color:var(--text-heading);">Add Basic User</h2>
                <button onclick="closeConsumerModal()" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:#94a3b8;">&times;</button>
            </div>

            <form id="consumerForm" novalidate>
                <input type="hidden" name="id" id="consumerId">
                <input type="hidden" name="role" id="role" value="basic-user">

                <!-- STEP 1: Personal Info -->
                <div class="form-step" id="step0">
                    <div class="form-section-title"><i class="fa-solid fa-user-gear"></i> Personal Information</div>
                    <div class="form-grid">
                            <div class="form-group">
                                <label>Identity Number <span class="hint">(xxxx-xxxx)</span></label>
                                <input type="text" name="custom_id" id="customId" placeholder="0000-0000" class="input-field">
                                <span class="validation-message" id="customIdError"></span>
                            </div>
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="firstName" id="firstName" class="input-field">
                                <span class="validation-message" id="firstNameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
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
                                <label>Sex <span class="required">*</span></label>
                                <select name="sex" id="sex" class="input-field">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                                <span class="validation-message" id="sexError"></span>
                            </div>
                            <div class="form-group">
                                <label>Birthdate <span class="required">*</span></label>
                                <input type="date" name="birthdate" id="birthdate" class="input-field">
                                <span class="validation-message" id="birthdateError"></span>
                            </div>
                            <div class="form-group">
                                <label>Calculated Age</label>
                                <input type="number" name="age" id="age" readonly class="input-field" style="background:#f3f4f6; color:#6b7280; font-weight:600;">
                            </div>
                        </div>
                </div>

                <!-- STEP 2: Address -->
                <div class="form-step" id="step1" style="display:none;">
                    <div class="form-section-title"><i class="fa-solid fa-map-location-dot"></i> Address Details</div>
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
                    <div class="form-section-title"><i class="fa-solid fa-key"></i> Account Credentials</div>
                    <div class="form-grid">
                            <div class="form-group">
                                <label>Username <span class="required">*</span></label>
                                <input type="text" name="username" id="username" class="input-field">
                                <span class="validation-message" id="usernameError"></span>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
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



                <div class="form-navigation" style="display:flex; justify-content:space-between; margin-top:2rem; border-top:1px solid #f1f5f9; padding-top:1.5rem;">
                    <button type="button" id="prevBtn" onclick="prevStep()" class="btn-secondary" style="display:none;">
                        <i class="fa-solid fa-chevron-left" style="margin-right:8px;"></i> Previous
                    </button>
                    <div style="margin-left:auto; display:flex; gap:1rem;">
                        <button type="button" id="nextBtn" onclick="nextStep()" class="btn-primary">
                            Next Step <i class="fa-solid fa-chevron-right" style="margin-left:8px;"></i>
                        </button>
                        <button type="submit" id="submitBtn" class="btn-primary" style="display:none;">
                            <i class="fa-solid fa-floppy-disk" style="margin-right:8px;"></i> Save User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Block Request Modal -->
    <div id="blockModal" class="modal2">
        <div class="modal2-content" style="max-width: 450px;">
            <div style="text-align: center; margin-bottom: 1.5rem;">
                <div id="blockIconContainer" style="width: 60px; height: 60px; background: #fee2e2; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                    <i id="blockIcon" class="fa-solid fa-triangle-exclamation" style="font-size: 1.75rem; color: #dc2626;"></i>
                </div>
                <h2 id="blockTitle" style="font-size: 1.5rem; color: #1f2937; margin-bottom: 0.5rem;">Request to Block Consumer</h2>
                <p id="blockSubtitle" style="color: #6b7280; font-size: 0.95rem;">This action will restrict the consumer's access. A super admin must approve this request.</p>
            </div>

            <form id="blockForm" style="width: 100%; text-align: left;">
                <input type="hidden" name="target_id" id="targetId">
                <input type="hidden" name="request_type" id="requestTypeInput" value="block">
                <div class="form-group">
                    <label style="font-weight: 600; color: #374151; margin-bottom: 0.5rem; display: block;">Reason <span style="color:red;">*</span></label>
                    <textarea name="reason" id="blockReason" rows="4" required placeholder="Please provide a valid reason..." style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem; font-family: inherit; resize: vertical;"></textarea>
                </div>
                <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" onclick="document.getElementById('blockModal').style.display='none'" class="btn-secondary" style="flex: 1; justify-content: center;">Cancel</button>
                    <button type="submit" id="submitRequestBtn" class="btn-primary" style="background: #dc2626; flex: 1; justify-content: center;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success/Error Modal -->
    <div id="messageModal" class="modal2" style="z-index: 2000;">
        <div class="modal2-content" style="max-width: 400px; text-align: center; padding: 2rem;">
            <div id="msgIconContainer" style="width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                <i id="msgIcon" class="fa-solid" style="font-size: 1.75rem;"></i>
            </div>
            <h2 id="msgTitle" style="font-size: 1.5rem; margin-bottom: 0.5rem;"></h2>
            <p id="msgBody" style="color: #6b7280; margin-bottom: 1.5rem;"></p>
            <button onclick="closeMessageModal()" class="btn-primary" style="width: 100%; justify-content: center;">Okay</button>
        </div>
    </div>

    <!-- Privileges Modal -->
    <div id="privModal" class="modal2">
        <div class="modal2-content">
            <h2 style="margin-bottom:0.5rem;">User Privileges</h2>
            <p id="privUserName" style="font-weight: bold; margin-bottom: 1.5rem; color:#64748b;"></p>
            <div id="privContent" style="width: 100%;"></div>
            <button onclick="document.getElementById('privModal').style.display='none'" class="btn-primary" style="margin-top: 1.5rem; width:100%; justify-content:center;">Close</button>
        </div>
    </div>

    <script src="../../js/admin_user_validation.js?v=<?php echo time(); ?>"></script>
    <script src="../../js/pagination_util.js?v=<?php echo time(); ?>"></script>
    <script>
        function toggleAnswerVisibility(icon) {
            const input = icon.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
    <script>
        const api = '../../php/database';

        // State
        let currentPage = 1;
        let limit = 5;
        let currentSearch = '';
        let currentStatus = '';
        let editingUserId = '';
        let usersMap = {};
        let currentStep = 0;
        const totalSteps = 3;

        function loadUsers(page = 1) {
            currentPage = page;
            currentStatus = document.getElementById('filterStatus').value;

            const params = new URLSearchParams({
                page: currentPage,
                limit: limit,
                search: currentSearch,
                status: currentStatus
            });

            fetch(api + '/admin_consumers_list.php?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;

                    usersMap = {};
                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Status</th><th>Actions</th><th>Privileges</th></tr></thead><tbody>';

                    if (data.consumers.length === 0) {
                        html += '<tr><td colspan="6" style="text-align:center; padding:2rem; color: var(--text-muted);">No consumers found matching your criteria.</td></tr>';
                    } else {
                        data.consumers.forEach(u => {
                            usersMap[u.id] = u;
                            const isBlocked = u.is_blocked == 1;
                            html += `<tr class="${isBlocked ? 'blocked-row' : ''}">
                                <td>${escapeHtml(u.firstName + ' ' + u.lastName)}</td>
                                <td>${escapeHtml(u.username)}</td>
                                <td>${escapeHtml(u.email)}</td>
                                <td>${isBlocked ? '<span class="status-badge no-dot status-trash">Blocked</span>' : '<span class="status-badge no-dot status-ok">Active</span>'}</td>
                                <td>
                                    <button class="btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="editUser('${u.id}')">
                                        <i class="fa-solid fa-pen-to-square" style="margin-right:0.25rem;"></i> Edit
                                    </button>
                                    ${!isBlocked
                                        ? `<button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; color: var(--error-color); border-color: var(--error-color);" onclick="openBlockModal('${u.id}', 'block')"><i class="fa-solid fa-ban" style="margin-right:0.25rem;"></i>Request to Block</button>`
                                        : `<button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; color: #16a34a; border-color: #16a34a;" onclick="openBlockModal('${u.id}', 'unblock')"><i class="fa-solid fa-unlock" style="margin-right:0.25rem;"></i>Request to Unblock</button>`}
                                </td>
                                <td>
                                    <button class="btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" onclick="viewPrivileges('basic-user', '${escapeHtml(u.username)}')">
                                        <i class="fa-solid fa-eye" style="margin-right:0.25rem;"></i> View
                                    </button>
                                </td>
                            </tr>`;
                        });
                    }
                    html += '</tbody></table>';
                    document.getElementById('usersTableContainer').innerHTML = html;

                    const badge = document.getElementById('userCountBadge');
                    const total = data.pagination.total_requests || 0;
                    badge.textContent = `${total} Consumer${total !== 1 ? 's' : ''}`;

                    if (total > 0) {
                        badge.style.background = '#dbeafe';
                        badge.style.color = '#1e40af';
                    } else {
                        badge.style.background = '#fee2e2';
                        badge.style.color = '#dc2626';
                    }

                    window.renderPagination(
                        document.getElementById('paginationControls'),
                        currentPage,
                        data.pagination.total_pages || 1,
                        limit,
                        function(newPage) { loadUsers(newPage); },
                        function(newLimit) { limit = newLimit; loadUsers(1); }
                    );
                });
        }

        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            currentSearch = '';
            currentStatus = '';
            loadUsers(1);
        }

        let searchTimeout;
        function handleSearch(e) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                currentSearch = e.target.value;
                loadUsers(1);
            }, 300);
        }

        const privilegesConfig = {
            'basic-user': [
                { desc: 'Access My Profile',                          has: true  },
                { desc: 'Change Password',                             has: true  },
                { desc: 'Browse playgrounds',                          has: true  },
                { desc: 'Book playground spots',                     has: true  },
                { desc: 'View Personal booking History',           has: true  },
                { desc: 'Manage Basic User Accounts',                    has: false },
                { desc: 'Approve / Reject Registration Requests',      has: false },
                { desc: 'Block / Unblock Users',                       has: false },
                { desc: 'Manage playgrounds & Areas',                  has: false },
                { desc: 'Full System Administration',                  has: false },
            ],
        };

        function viewPrivileges(role, name) {
            document.getElementById('privUserName').textContent = name + ' (' + role + ')';
            const privs = privilegesConfig[role] || privilegesConfig['basic-user'];

            const listHtml = privs.map(p => `
                <div style="display:flex; align-items:center; gap:12px; padding:10px 12px; border-radius:8px;
                     background:${p.has ? '#f0fdf4' : '#f8fafc'};
                     border:1px solid ${p.has ? '#bbf7d0' : '#e2e8f0'}; margin-bottom:4px; text-align:left;">
                    <i class="fa-solid ${p.has ? 'fa-circle-check' : 'fa-circle-xmark'}"
                       style="color:${p.has ? '#16a34a' : '#94a3b8'}; font-size:17px; flex-shrink:0;"></i>
                    <span style="font-size:0.875rem; font-weight:500;
                          color:${p.has ? '#166534' : '#64748b'};
                          text-decoration:${p.has ? 'none' : 'line-through'};">${p.desc}</span>
                </div>`
            ).join('');

            const contentDiv = document.getElementById('privContent');
            contentDiv.innerHTML = `<div style="display:inline-block; text-align:left; width:100%;">${listHtml}</div>`;
            contentDiv.style.display = 'block';
            document.getElementById('privModal').style.display = 'flex';
        }

        // Wizard Navigation
        function showStep(n) {
            document.querySelectorAll('.form-step').forEach(el => el.style.display = 'none');
            document.getElementById('step' + n).style.display = 'block';

            document.getElementById('prevBtn').style.display = (n === 0) ? 'none' : 'block';
            if (n === totalSteps - 1) {
                document.getElementById('nextBtn').style.display = 'none';
                document.getElementById('submitBtn').style.display = 'block';
            } else {
                document.getElementById('nextBtn').style.display = 'block';
                document.getElementById('submitBtn').style.display = 'none';
            }
        }

        async function nextStep() {
            const isEdit = !!editingUserId;
            AdminUserValidation.clearAllErrors();
            let isValid = true;

            if (currentStep === 0) isValid = await AdminUserValidation.validatePersonalInfo();
            else if (currentStep === 1) isValid = AdminUserValidation.validateAddress();
            else if (currentStep === 2) isValid = await AdminUserValidation.validateCredentials(isEdit);

            if (isValid) {
                currentStep++;
                showStep(currentStep);
            }
        }

        function prevStep() {
            if (currentStep > 0) {
                currentStep--;
                showStep(currentStep);
            }
        }

        function openUserModal() {
            editingUserId = '';
            AdminUserValidation.setEditMode(false);
            document.getElementById('modalTitle').textContent = 'Add Consumer';
            document.getElementById('consumerForm').reset();
            document.getElementById('consumerId').value = '';
            document.getElementById('pwHint').textContent = '(Required)';
            AdminUserValidation.clearAllErrors();
            currentStep = 0;
            showStep(0);
            document.getElementById('consumerModal').style.display = 'flex';
        }

        function editUser(userId) {
            const u = usersMap[userId];
            if (!u) return;
            editingUserId = u.id;
            AdminUserValidation.setEditMode(true);
            document.getElementById('modalTitle').textContent = 'Edit Consumer';
            document.getElementById('consumerId').value = u.id;
            document.getElementById('customId').value = u.id;

            // Populate fields
            const fields = ['firstName', 'lastName', 'middleInitial', 'extension', 'sex', 'birthdate', 'purok', 'barangay', 'city', 'province', 'zipCode', 'country', 'username', 'email'];
            fields.forEach(f => {
                const el = document.getElementById(f);
                if (el) el.value = u[f] || '';
            });

            if (u.birthdate) {
                const bd = new Date(u.birthdate), today = new Date();
                let age = today.getFullYear() - bd.getFullYear();
                if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) age--;
                document.getElementById('age').value = age;
            }

            document.getElementById('password').value = '';
            document.getElementById('pwHint').textContent = '(Leave blank to keep current)';
            AdminUserValidation.clearAllErrors();
            currentStep = 0;
            showStep(0);
            document.getElementById('consumerModal').style.display = 'flex';
        }

        function closeConsumerModal() {
            document.getElementById('consumerModal').style.display = 'none';
        }

        document.getElementById('consumerForm').onsubmit = async function(e) {
            e.preventDefault();
            const isEdit = !!editingUserId;
            if (!(await AdminUserValidation.validateAll(isEdit))) { showStep(0); return; }

            const fd = new FormData(this);
            fetch(api + '/admin_consumer_save.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        closeConsumerModal();
                        showMessageModal('success', 'Success', 'Consumer saved successfully.');
                        loadUsers(currentPage);
                    } else {
                        showMessageModal('error', 'Error', d.error || 'Failed to save consumer.');
                    }
                });
        };

        function openBlockModal(id, type = 'block') {
            document.getElementById('targetId').value = id;
            document.getElementById('requestTypeInput').value = type;
            document.getElementById('blockReason').value = '';

            const isBlock = type === 'block';
            document.getElementById('blockTitle').textContent = isBlock ? 'Request to Block Consumer' : 'Request to Unblock Consumer';
            document.getElementById('blockSubtitle').textContent = isBlock
                ? "This action will restrict the consumer's access. A super admin must approve this request."
                : "This action will restore the consumer's access. A super admin must approve this request.";

            const submitBtn = document.getElementById('submitRequestBtn');
            submitBtn.textContent = isBlock ? 'Submit Block Request' : 'Submit Unblock Request';
            submitBtn.style.background = isBlock ? '#dc2626' : '#16a34a';

            const iconContainer = document.getElementById('blockIconContainer');
            iconContainer.style.background = isBlock ? '#fee2e2' : '#dcfce7';
            document.getElementById('blockIcon').className = `fa-solid ${isBlock ? 'fa-triangle-exclamation' : 'fa-unlock'}`;
            document.getElementById('blockIcon').style.color = isBlock ? '#dc2626' : '#16a34a';

            document.getElementById('blockModal').style.display = 'flex';
        }

        document.getElementById('blockForm').onsubmit = function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const type = fd.get('request_type');
            const endpoint = type === 'block' ? '/admin_request_block.php' : '/admin_request_unblock.php';

            fetch(api + endpoint, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    document.getElementById('blockModal').style.display = 'none';
                    if (d.success) {
                        showMessageModal('success', 'Submitted', `Your ${type} request has been sent for approval.`);
                    } else {
                        showMessageModal('error', 'Failed', d.error || 'An error occurred.');
                    }
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
            } else {
                iconContainer.style.background = '#fee2e2';
                icon.className = 'fa-solid fa-xmark';
                icon.style.color = '#dc2626';
            }
            modal.style.display = 'flex';
        }

        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            AdminUserValidation.init(document.getElementById('consumerForm'), {
                apiBase: api,
                roleSelector: '#role',
                securitySection: '#securitySection'
            });
            loadUsers();

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


