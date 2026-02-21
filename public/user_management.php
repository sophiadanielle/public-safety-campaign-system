<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        (function () {
            const basePath = '<?php echo $basePath; ?>';
            const urlParams = new URLSearchParams(window.location.search);
            const justLoggedIn = urlParams.has('logged_in') || urlParams.has('signed_up');
            
            function checkAuth(retryCount) {
                retryCount = retryCount || 0;
                const maxRetries = justLoggedIn ? 20 : 5;
                
                try {
                    const token = localStorage.getItem('jwtToken');
                    if (token && token.trim() !== '') {
                        if (justLoggedIn) {
                            const cleanUrl = window.location.pathname;
                            window.history.replaceState({}, '', cleanUrl);
                        }
                        return;
                    }
                    
                    if (retryCount < maxRetries) {
                        const delay = justLoggedIn ? 300 : 100;
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, delay);
                        return;
                    }
                    
                    window.location.replace(basePath + '/login.php');
                } catch (e) {
                    if (justLoggedIn && retryCount < maxRetries) {
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, 300);
                    } else {
                        window.location.replace(basePath + '/login.php');
                    }
                }
            }
            checkAuth(0);
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
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
    <style>
        html, body {
            margin: 13px;
            padding: 0;
        }

        .main-content-wrapper {
            margin-left: 280px;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            transition: margin-left 0.3s ease;
            padding: 24px;
        }
        
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0 !important;
            }
        }
        
        .user-management-page {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        
        .page-header p {
            color: #64748b;
            margin: 4px 0 0 0;
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
        }
        
        .btn-add-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        
        .btn-add-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.4);
        }
        
        .btn-view-archived {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-view-archived:hover {
            background: #e2e8f0;
            border-color: #cbd5e1;
        }
        
        .btn-view-archived.active {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }
        
        .users-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th,
        .users-table td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .users-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tr:hover {
            background: #f8fafc;
        }
        
        .user-info-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            flex-shrink: 0;
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-avatar-initials {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            font-weight: 700;
            font-size: 16px;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .user-email {
            font-size: 13px;
            color: #64748b;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-super-admin {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }
        
        .badge-admin {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }
        
        .badge-staff {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
        }
        
        .badge-employee {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            color: #7c3aed;
        }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .btn-action {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-edit {
            background: #e0f2fe;
            color: #0369a1;
        }
        
        .btn-edit:hover {
            background: #bae6fd;
        }
        
        .btn-archive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-archive:hover {
            background: #fecaca;
        }
        
        .btn-restore {
            background: #dcfce7;
            color: #166534;
        }
        
        .btn-restore:hover {
            background: #bbf7d0;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: #cbd5e1;
        }
        
        .empty-state h3 {
            margin: 0 0 8px;
            color: #475569;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 24px;
        }
        
        .modal-overlay.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 520px;
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .modal-header h2 i {
            color: #0d9488;
        }
        
        .modal-close {
            width: 36px;
            height: 36px;
            border: none;
            background: #f1f5f9;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        
        .modal-body {
            padding: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        
        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .avatar-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #e2e8f0;
            flex-shrink: 0;
        }
        
        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .avatar-upload-btn {
            flex: 1;
        }
        
        .avatar-upload-btn input[type="file"] {
            display: none;
        }
        
        .avatar-upload-btn label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            background: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            color: #475569;
        }
        
        .avatar-upload-btn label:hover {
            background: #e2e8f0;
            border-color: #0d9488;
            color: #0d9488;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 2px solid #f1f5f9;
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            padding: 12px 24px;
            background: #f1f5f9;
            color: #475569;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn-cancel:hover {
            background: #e2e8f0;
        }
        
        .btn-submit {
            padding: 12px 24px;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-spinner {
            text-align: center;
            padding: 40px;
            color: #64748b;
        }
        
        .loading-spinner i {
            font-size: 32px;
            margin-bottom: 12px;
            color: #0d9488;
        }

        @media (max-width: 768px) {
            .users-table th:nth-child(4),
            .users-table td:nth-child(4) {
                display: none;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .btn-add-user,
            .btn-view-archived {
                flex: 1;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="module-users" data-module="user_management">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <main class="main-content-wrapper">
        <div class="user-management-page">
            <div class="page-header">
                <div>
                    <h1><i class="fas fa-users-cog" style="color: #0d9488; margin-right: 12px;"></i>User Management</h1>
                    <p>Manage system users and their access levels</p>
                </div>
                <div class="header-actions">
                    <button class="btn-view-archived" id="viewArchivedBtn" onclick="toggleArchivedView()">
                        <i class="fas fa-archive"></i>
                        <span id="archivedBtnText">View Archived</span>
                    </button>
                    <button class="btn-add-user" onclick="openAddUserModal()">
                        <i class="fas fa-user-plus"></i>
                        Add User
                    </button>
                </div>
            </div>
            
            <div class="users-card">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>User Type</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="4">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <p>Loading users...</p>
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
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> <span id="modalTitle">Add New User</span></h2>
                <button class="modal-close" onclick="closeUserModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <input type="hidden" id="userId" name="id">
                    
                    <div class="form-group">
                        <label for="userFullname">Full Name</label>
                        <input type="text" id="userFullname" name="fullname" placeholder="Enter full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="userEmail">Email Address</label>
                        <input type="email" id="userEmail" name="email" placeholder="Enter email address" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPassword">Password <span id="passwordHint" style="font-weight: normal; color: #64748b;"></span></label>
                        <input type="password" id="userPassword" name="password" placeholder="Enter password">
                    </div>
                    
                    <div class="form-group">
                        <label for="userType">User Type</label>
                        <select id="userType" name="user_type" required>
                            <option value="">Select user type</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                            <option value="Employee">Employee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Profile Picture</label>
                        <div class="avatar-upload">
                            <div class="avatar-preview">
                                <img id="avatarPreview" src="https://ui-avatars.com/api/?name=User&background=0d9488&color=fff&size=128" alt="Avatar">
                            </div>
                            <div class="avatar-upload-btn">
                                <input type="file" id="avatarInput" accept="image/*" onchange="previewAvatar(this)">
                                <label for="avatarInput">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    Upload Photo
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeUserModal()">Cancel</button>
                <button class="btn-submit" id="submitBtn" onclick="submitUserForm()">
                    <i class="fas fa-save"></i>
                    <span id="submitBtnText">Save User</span>
                </button>
            </div>
        </div>
    </div>

    <script>
    const basePath = '<?php echo $basePath; ?>';
    const apiBase = '<?php echo $basePath; ?>/api/user_management.php';
    let isViewingArchived = false;
    let currentAvatarBase64 = null;

    async function loadUsers() {
        const tbody = document.getElementById('usersTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="4">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading users...</p>
                    </div>
                </td>
            </tr>
        `;
        
        try {
            const token = localStorage.getItem('jwtToken');
            const endpoint = isViewingArchived ? `${apiBase}?action=archived` : `${apiBase}?action=list`;
            
            const res = await fetch(endpoint, {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                throw new Error(data.error || 'Failed to load users');
            }
            
            renderUsers(data.users || []);
        } catch (err) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="fas fa-exclamation-circle"></i>
                            <h3>Error loading users</h3>
                            <p>${err.message}</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    function renderUsers(users) {
        const tbody = document.getElementById('usersTableBody');
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>${isViewingArchived ? 'No archived users' : 'No users found'}</h3>
                            <p>${isViewingArchived ? 'Archived users will appear here' : 'Click "Add User" to create a new user'}</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = users.map(user => {
            const initials = getInitials(user.fullname);
            const badgeClass = getBadgeClass(user.user_type);
            const dateCreated = new Date(user.date_created).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            });
            
            return `
                <tr>
                    <td>
                        <div class="user-info-cell">
                            <div class="user-avatar">
                                ${user.avatar_url 
                                    ? `<img src="${user.avatar_url}" alt="${user.fullname}">`
                                    : `<div class="user-avatar-initials">${initials}</div>`
                                }
                            </div>
                            <div class="user-details">
                                <span class="user-name">${escapeHtml(user.fullname)}</span>
                                <span class="user-email">${escapeHtml(user.email)}</span>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge ${badgeClass}">${escapeHtml(user.user_type)}</span></td>
                    <td>${dateCreated}</td>
                    <td>
                        <div class="action-btns">
                            ${isViewingArchived ? `
                                <button class="btn-action btn-restore" onclick="restoreUser(${user.id})">
                                    <i class="fas fa-undo"></i> Restore
                                </button>
                            ` : `
                                <button class="btn-action btn-edit" onclick="editUser(${user.id})">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-action btn-archive" onclick="archiveUser(${user.id}, '${escapeHtml(user.fullname)}')">
                                    <i class="fas fa-archive"></i> Archive
                                </button>
                            `}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function getInitials(name) {
        if (!name) return 'U';
        const parts = name.trim().split(' ');
        if (parts.length >= 2) {
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        }
        return name.substring(0, 2).toUpperCase();
    }

    function getBadgeClass(userType) {
        const classes = {
            'Super Admin': 'badge-super-admin',
            'Admin': 'badge-admin',
            'Staff': 'badge-staff',
            'Employee': 'badge-employee'
        };
        return classes[userType] || 'badge-staff';
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function toggleArchivedView() {
        isViewingArchived = !isViewingArchived;
        const btn = document.getElementById('viewArchivedBtn');
        const btnText = document.getElementById('archivedBtnText');
        
        if (isViewingArchived) {
            btn.classList.add('active');
            btnText.textContent = 'View Active';
        } else {
            btn.classList.remove('active');
            btnText.textContent = 'View Archived';
        }
        
        loadUsers();
    }

    function openAddUserModal() {
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('submitBtnText').textContent = 'Save User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('passwordHint').textContent = '';
        document.getElementById('userPassword').required = true;
        document.getElementById('avatarPreview').src = 'https://ui-avatars.com/api/?name=User&background=0d9488&color=fff&size=128';
        currentAvatarBase64 = null;
        document.getElementById('userModal').classList.add('show');
    }

    async function editUser(id) {
        try {
            const token = localStorage.getItem('jwtToken');
            const res = await fetch(`${apiBase}?id=${id}`, {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                throw new Error(data.error || 'Failed to load user');
            }
            
            const user = data.user;
            
            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('submitBtnText').textContent = 'Update User';
            document.getElementById('userId').value = user.id;
            document.getElementById('userFullname').value = user.fullname;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPassword').value = '';
            document.getElementById('userPassword').required = false;
            document.getElementById('passwordHint').textContent = '(Leave blank to keep current)';
            document.getElementById('userType').value = user.user_type;
            
            if (user.avatar_url) {
                document.getElementById('avatarPreview').src = user.avatar_url;
                currentAvatarBase64 = user.avatar_url;
            } else {
                const initials = getInitials(user.fullname);
                document.getElementById('avatarPreview').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(user.fullname)}&background=0d9488&color=fff&size=128`;
                currentAvatarBase64 = null;
            }
            
            document.getElementById('userModal').classList.add('show');
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }

    function closeUserModal() {
        document.getElementById('userModal').classList.remove('show');
    }

    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('avatarPreview').src = e.target.result;
                currentAvatarBase64 = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    async function submitUserForm() {
        const submitBtn = document.getElementById('submitBtn');
        const originalText = document.getElementById('submitBtnText').textContent;
        
        submitBtn.disabled = true;
        document.getElementById('submitBtnText').textContent = 'Saving...';
        
        try {
            const token = localStorage.getItem('jwtToken');
            const userId = document.getElementById('userId').value;
            const isEdit = !!userId;
            
            const formData = {
                fullname: document.getElementById('userFullname').value.trim(),
                email: document.getElementById('userEmail').value.trim(),
                user_type: document.getElementById('userType').value,
                avatar_url: currentAvatarBase64
            };
            
            const password = document.getElementById('userPassword').value;
            if (password) {
                formData.password = password;
            }
            
            if (isEdit) {
                formData.id = parseInt(userId);
            }
            
            const res = await fetch(apiBase, {
                method: isEdit ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify(formData)
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                throw new Error(data.error || 'Failed to save user');
            }
            
            closeUserModal();
            loadUsers();
            
        } catch (err) {
            alert('Error: ' + err.message);
        } finally {
            submitBtn.disabled = false;
            document.getElementById('submitBtnText').textContent = originalText;
        }
    }

    async function archiveUser(id, name) {
        if (!confirm(`Are you sure you want to archive "${name}"? They will no longer be able to log in.`)) {
            return;
        }
        
        try {
            const token = localStorage.getItem('jwtToken');
            const res = await fetch(`${apiBase}?id=${id}`, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                throw new Error(data.error || 'Failed to archive user');
            }
            
            loadUsers();
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }

    async function restoreUser(id) {
        try {
            const token = localStorage.getItem('jwtToken');
            const res = await fetch(apiBase, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ id: id, archived: 0 })
            });
            
            const data = await res.json();
            
            if (!res.ok) {
                throw new Error(data.error || 'Failed to restore user');
            }
            
            loadUsers();
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUserModal();
        }
    });

    // Close modal on overlay click
    document.getElementById('userModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeUserModal();
        }
    });

    // Initialize
    loadUsers();
    </script>
</body>
</html>
