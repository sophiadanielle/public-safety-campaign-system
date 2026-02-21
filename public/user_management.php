<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../header/includes/path_helper.php';
require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        (function () {
            const basePath = '<?php echo $basePath; ?>';
            const token = localStorage.getItem('jwtToken');
            if (!token || token.trim() === '') {
                window.location.replace(basePath + '/login.php');
            }
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #14b8a6;
            --secondary: #0f172a;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error: #dc2626;
            --success: #16a34a;
        }

        .main-content-wrapper {
            margin-left: 280px;
            margin-top: 60px;
            padding: 24px;
            min-height: calc(100vh - 60px);
            background: var(--background);
        }

        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .page-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--secondary);
            margin: 0;
        }

        .page-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin-top: 4px;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
        }

        .btn-secondary {
            background: var(--surface);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: #f1f5f9;
        }

        .btn-danger {
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .btn-danger:hover {
            background: #fee2e2;
        }

        .card {
            background: var(--surface);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            color: var(--text-primary);
            font-size: 14px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 200px;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 10px;
            object-fit: cover;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-width: 0;
        }

        .user-name {
            font-weight: 600;
            color: var(--text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-email {
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-super-admin {
            background: #fae8ff;
            color: #86198f;
        }

        .badge-staff {
            background: #dcfce7;
            color: #166534;
        }

        .badge-employee {
            background: #f3f4f6;
            color: #374151;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-weight: 500;
        }

        .action-btn-edit {
            background: #e0f2fe;
            color: #0369a1;
        }

        .action-btn-edit:hover {
            background: #bae6fd;
        }

        .action-btn-archive {
            background: #fef3c7;
            color: #92400e;
        }

        .action-btn-archive:hover {
            background: #fde68a;
        }

        .action-btn-restore {
            background: #dcfce7;
            color: #166534;
        }

        .action-btn-restore:hover {
            background: #bbf7d0;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal {
            background: var(--surface);
            border-radius: 20px;
            width: 100%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-header img {
            height: 32px;
            margin-right: 12px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f1f5f9;
            color: var(--text-primary);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 2px solid var(--border);
            border-radius: 10px;
            transition: all 0.2s;
            outline: none;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            border: 2px solid var(--border);
            border-radius: 10px;
            background: white;
            cursor: pointer;
            outline: none;
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: 700;
            overflow: hidden;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload-btn {
            flex: 1;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }

        .toggle-archived {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .toggle-archived input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }

        .loading-spinner {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .loading-spinner i {
            font-size: 32px;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .status-message {
            position: fixed;
            top: 80px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            font-size: 14px;
            z-index: 20000;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            min-width: 300px;
            animation: slideInRight 0.3s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .status-message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-message.error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
    </style>
</head>
<body class="module-user-management">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>

    <main class="main-content-wrapper">
        <div class="page-header">
            <div>
                <h1 class="page-title">User Management</h1>
                <p class="page-subtitle">Manage system users, roles, and permissions</p>
            </div>
            <div class="header-actions">
                <label class="toggle-archived">
                    <input type="checkbox" id="showArchivedToggle" onchange="toggleArchivedView()">
                    <span>Show Archived</span>
                </label>
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <i class="fas fa-plus"></i>
                    Add User
                </button>
            </div>
        </div>

        <div id="statusMessage" class="status-message" style="display: none;"></div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title" id="tableTitle">Active Users</h2>
                <button class="btn btn-secondary" onclick="loadUsers()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>User Type</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="4">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add/Edit User Modal -->
    <div class="modal-overlay" id="userModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">
                    <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Logo">
                    <span id="modalTitle">Add New User</span>
                </h3>
                <button type="button" class="modal-close" onclick="event.stopPropagation(); closeModal();">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm" onsubmit="return handleUserSubmit(event)">
                    <input type="hidden" id="userId" value="">
                    
                    <div class="form-group">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-input" id="userName" name="name" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address *</label>
                        <input type="email" class="form-input" id="userEmail" name="email" required placeholder="Enter email address">
                    </div>

                    <div class="form-group" id="passwordGroup">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-input" id="userPassword" name="password" placeholder="Enter password (min 6 characters)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">User Type *</label>
                        <select class="form-select" id="userType" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                            <option value="Employee">Employee</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Picture</label>
                        <div class="avatar-upload">
                            <div class="avatar-preview" id="avatarPreview">
                                <span id="avatarInitials">?</span>
                            </div>
                            <div class="avatar-upload-btn">
                                <div class="file-input-wrapper">
                                    <button type="button" class="btn btn-secondary">
                                        <i class="fas fa-upload"></i>
                                        Choose Image
                                    </button>
                                    <input type="file" id="avatarFile" accept="image/*" onchange="previewAvatar(this)">
                                </div>
                                <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                                    JPG, PNG, GIF up to 5MB
                                </p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="event.stopPropagation(); closeModal();">Cancel</button>
                <button type="submit" class="btn btn-primary" form="userForm" id="submitBtn">
                    <i class="fas fa-save"></i>
                    Save User
                </button>
            </div>
        </div>
    </div>

    <script>
        // Version: 2.2 - Cache bust: <?php echo time(); ?>
        console.log('User Management Script Loaded - Version 2.2');
        const apiBase = '<?php echo $apiPath; ?>';
        const basePath = '<?php echo $basePath; ?>';
        let showingArchived = false;

        function getToken() {
            return localStorage.getItem('jwtToken') || '';
        }

        function showStatus(message, type) {
            const el = document.getElementById('statusMessage');
            el.textContent = message;
            el.className = 'status-message ' + type;
            el.style.display = 'block';
            setTimeout(() => { 
                el.style.display = 'none';
            }, 5000);
        }

        function getInitials(name) {
            if (!name) return '?';
            const parts = name.trim().split(' ');
            if (parts.length >= 2) {
                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            }
            return name.substring(0, 2).toUpperCase();
        }

        function getBadgeClass(userType) {
            const type = (userType || '').toLowerCase().replace(' ', '-');
            return 'badge-' + type;
        }

        function formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        async function loadUsers() {
            const tbody = document.getElementById('usersTableBody');
            tbody.innerHTML = '<tr><td colspan="4"><div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div></td></tr>';

            try {
                const url = apiBase + '/api/v1/users/manage' + (showingArchived ? '?archived=1' : '');
                const res = await fetch(url, {
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });

                if (!res.ok) {
                    throw new Error('Failed to load users');
                }

                const data = await res.json();
                const users = data.data || [];

                if (users.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>${showingArchived ? 'No archived users' : 'No users found'}</h3>
                                    <p>${showingArchived ? 'Archived users will appear here' : 'Click "Add User" to create a new user'}</p>
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = '';
                users.forEach(user => {
                    const tr = document.createElement('tr');
                    const initials = getInitials(user.name);
                    const avatarHtml = user.avatar_url 
                        ? `<img src="${user.avatar_url}" alt="${user.name}">`
                        : initials;

                    tr.innerHTML = `
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar">${avatarHtml}</div>
                                <div class="user-info">
                                    <span class="user-name">${user.name || 'Unknown'}</span>
                                    <span class="user-email">${user.email || ''}</span>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge ${getBadgeClass(user.user_type)}">${user.user_type || 'Employee'}</span></td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <div class="action-btns">
                                ${showingArchived ? `
                                    <button class="action-btn action-btn-restore" onclick="restoreUser(${user.id})">
                                        <i class="fas fa-undo"></i> Restore
                                    </button>
                                ` : `
                                    <button class="action-btn action-btn-edit" onclick="editUser(${user.id})">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="action-btn action-btn-archive" onclick="archiveUser(${user.id})">
                                        <i class="fas fa-archive"></i> Archive
                                    </button>
                                `}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });

            } catch (err) {
                console.error('Error loading users:', err);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h3>Error loading users</h3>
                                <p>${err.message}</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        }

        function toggleArchivedView() {
            showingArchived = document.getElementById('showArchivedToggle').checked;
            document.getElementById('tableTitle').textContent = showingArchived ? 'Archived Users' : 'Active Users';
            loadUsers();
        }

        function openAddUserModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('userPassword').required = true;
            document.getElementById('avatarPreview').innerHTML = '<span id="avatarInitials">?</span>';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Save User';
            document.getElementById('userModal').classList.add('show');
        }

        async function editUser(userId) {
            try {
                const res = await fetch(apiBase + '/api/v1/users/manage/' + userId, {
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });

                if (!res.ok) throw new Error('Failed to load user');

                const data = await res.json();
                const user = data.data;

                document.getElementById('modalTitle').textContent = 'Edit User';
                document.getElementById('userId').value = user.id;
                document.getElementById('userName').value = user.name || '';
                document.getElementById('userEmail').value = user.email || '';
                document.getElementById('userType').value = user.user_type || 'Employee';
                document.getElementById('userPassword').value = '';
                document.getElementById('userPassword').required = false;
                document.getElementById('passwordGroup').querySelector('.form-label').textContent = 'Password (leave blank to keep current)';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update User';

                const initials = getInitials(user.name);
                if (user.avatar_url) {
                    document.getElementById('avatarPreview').innerHTML = `<img src="${user.avatar_url}" alt="${user.name}">`;
                } else {
                    document.getElementById('avatarPreview').innerHTML = `<span id="avatarInitials">${initials}</span>`;
                }

                document.getElementById('userModal').classList.add('show');

            } catch (err) {
                showStatus('Error loading user: ' + err.message, 'error');
            }
        }

        function closeModal() {
            console.log('closeModal called');
            const modal = document.getElementById('userModal');
            if (!modal) {
                console.error('Modal element not found');
                return;
            }
            
            modal.classList.remove('show');
            
            setTimeout(() => {
                const form = document.getElementById('userForm');
                if (form) form.reset();
                
                const userIdField = document.getElementById('userId');
                if (userIdField) userIdField.value = '';
                
                const avatarPreview = document.getElementById('avatarPreview');
                if (avatarPreview) avatarPreview.innerHTML = '<span id="avatarInitials">?</span>';
                
                const passwordGroup = document.getElementById('passwordGroup');
                if (passwordGroup) {
                    const passwordLabel = passwordGroup.querySelector('.form-label');
                    if (passwordLabel) passwordLabel.textContent = 'Password *';
                    passwordGroup.style.display = 'block';
                }
                
                const passwordField = document.getElementById('userPassword');
                if (passwordField) passwordField.required = false;
            }, 300);
        }

        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Update initials preview when name changes
        document.getElementById('userName').addEventListener('input', function() {
            const preview = document.getElementById('avatarPreview');
            if (!preview.querySelector('img')) {
                const initials = getInitials(this.value);
                preview.innerHTML = `<span id="avatarInitials">${initials || '?'}</span>`;
            }
        });

        async function handleUserSubmit(event) {
            event.preventDefault();

            const userId = document.getElementById('userId').value;
            const isEdit = !!userId;

            const formData = {
                name: document.getElementById('userName').value.trim(),
                email: document.getElementById('userEmail').value.trim(),
                user_type: document.getElementById('userType').value
            };

            const password = document.getElementById('userPassword').value;
            if (password) {
                if (password.length < 6) {
                    showStatus('Password must be at least 6 characters', 'error');
                    return false;
                }
                formData.password = password;
            } else if (!isEdit) {
                showStatus('Password is required for new users', 'error');
                return false;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const url = isEdit 
                    ? apiBase + '/api/v1/users/manage/' + userId
                    : apiBase + '/api/v1/users/manage';

                const res = await fetch(url, {
                    method: isEdit ? 'PUT' : 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify(formData)
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Failed to save user');
                }

                // Handle avatar upload if file selected
                const avatarFile = document.getElementById('avatarFile').files[0];
                if (avatarFile && data.data && data.data.id) {
                    const avatarFormData = new FormData();
                    avatarFormData.append('avatar', avatarFile);

                    await fetch(apiBase + '/api/v1/users/manage/' + data.data.id + '/avatar', {
                        method: 'POST',
                        headers: { 'Authorization': 'Bearer ' + getToken() },
                        body: avatarFormData
                    });
                }

                showStatus(isEdit ? 'User updated successfully' : 'User created successfully', 'success');
                
                // Close modal and refresh data
                setTimeout(() => {
                    closeModal();
                    loadUsers();
                }, 300);

            } catch (err) {
                showStatus(err.message, 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> ' + (isEdit ? 'Update User' : 'Save User');
            }

            return false;
        }

        async function archiveUser(userId) {
            if (!confirm('Are you sure you want to archive this user? They will no longer be able to log in.')) {
                return;
            }

            try {
                const res = await fetch(apiBase + '/api/v1/users/manage/' + userId + '/archive', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Failed to archive user');
                }

                showStatus('User archived successfully', 'success');
                loadUsers();

            } catch (err) {
                showStatus(err.message, 'error');
            }
        }

        async function restoreUser(userId) {
            if (!confirm('Are you sure you want to restore this user?')) {
                return;
            }

            try {
                const res = await fetch(apiBase + '/api/v1/users/manage/' + userId + '/restore', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });

                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Failed to restore user');
                }

                showStatus('User restored successfully', 'success');
                loadUsers();

            } catch (err) {
                showStatus(err.message, 'error');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadUsers();
        });

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        // Close modal on overlay click
        document.getElementById('userModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
