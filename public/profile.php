<?php
$pageTitle = 'User Profile';
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        // Auth guard
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
</head>
<body class="module-profile" data-module="profile">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <main class="main-content-wrapper">
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
    
    .profile-page {
        max-width: 900px;
        margin: 0 auto;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
    }
    
    .profile-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        gap: 24px;
        margin-bottom: 32px;
        padding-bottom: 24px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        border: 4px solid #4c8a89;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-info h2 {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
    }
    
    .profile-info .user-email {
        font-size: 16px;
        color: #64748b;
        margin: 0 0 12px 0;
    }
    
    .profile-info .user-role {
        display: inline-block;
        padding: 6px 12px;
        background: #e0f2fe;
        color: #1e40af;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
    }
    
    .form-section {
        margin-bottom: 32px;
    }
    
    .form-section h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
    }
    
    .form-field {
        display: flex;
        flex-direction: column;
    }
    
    .form-field label {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-field input,
    .form-field select,
    .form-field textarea {
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .form-field input:focus,
    .form-field select:focus,
    .form-field textarea:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
    }
    
    .form-field.full-width {
        grid-column: 1 / -1;
    }
    
    .status-message {
        padding: 12px 16px;
        border-radius: 8px;
        margin-top: 16px;
        font-size: 14px;
        display: none;
    }
    
    .status-message.success {
        background: #dcfce7;
        color: #166534;
        border: 2px solid #86efac;
        display: block;
    }
    
    .status-message.error {
        background: #fee2e2;
        color: #dc2626;
        border: 2px solid #fca5a5;
        display: block;
    }
</style>

<main class="profile-page">
    <div class="page-header">
        <h1><i class="fas fa-user-circle" style="color: #0d9488; margin-right: 12px;"></i>User Profile</h1>
        <p>Manage your account information and preferences</p>
    </div>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar" id="profileAvatarContainer">
                <img id="profileAvatar" src="https://ui-avatars.com/api/?name=User&background=0d9488&color=fff&size=128" alt="User">
            </div>
            <div class="profile-info">
                <h2 id="profileName">Loading...</h2>
                <p class="user-email" id="profileEmail">Loading...</p>
                <span class="user-role" id="profileRole">Loading...</span>
            </div>
        </div>

        <form id="profileForm">
            <div class="form-section">
                <h3>Personal Information</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="fullname">Full Name</label>
                        <input type="text" id="fullname" name="fullname" required>
                    </div>
                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Profile Picture</h3>
                <div class="avatar-upload-section">
                    <div class="current-avatar">
                        <img id="avatarPreview" src="https://ui-avatars.com/api/?name=User&background=0d9488&color=fff&size=128" alt="Avatar">
                    </div>
                    <div class="avatar-upload-controls">
                        <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        <button type="button" class="btn btn-secondary" onclick="document.getElementById('avatarInput').click()">
                            <i class="fas fa-camera"></i> Change Photo
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="removeAvatar()" style="color: #dc2626;">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Account Information</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="userType">User Type</label>
                        <input type="text" id="userType" name="user_type" readonly>
                    </div>
                    <div class="form-field">
                        <label for="memberSince">Member Since</label>
                        <input type="text" id="memberSince" name="memberSince" readonly>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Change Password</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="currentPassword">Current Password</label>
                        <input type="password" id="currentPassword" name="current_password" placeholder="Enter current password">
                    </div>
                    <div class="form-field">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="new_password" placeholder="Enter new password">
                    </div>
                </div>
                <p style="font-size: 13px; color: #64748b; margin-top: 8px;">Leave blank if you don't want to change your password.</p>
            </div>

            <div class="status-message" id="statusMessage"></div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">
                    <i class="fas fa-undo"></i> Reset
                </button>
            </div>
        </form>
    </div>
</main>

<style>
.avatar-upload-section {
    display: flex;
    align-items: center;
    gap: 24px;
}

.current-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid #0d9488;
}

.current-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-upload-controls {
    display: flex;
    gap: 12px;
}

.avatar-initials {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: white;
    font-weight: 700;
    font-size: 36px;
}
</style>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const basePath = '<?php echo $basePath; ?>';
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $basePath; ?>/api/user_management.php';
let currentUser = null;
let currentAvatarBase64 = null;

// Get initials from name
function getInitials(name) {
    if (!name) return 'U';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
}

// Load user profile
async function loadProfile() {
    try {
        const res = await fetch(apiBase + '?action=me', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (!res.ok) {
            if (res.status === 401) {
                window.location.href = basePath + '/login.php';
                return;
            }
            throw new Error('Failed to load profile');
        }
        
        const data = await res.json();
        currentUser = data.user || data;
        
        // Update display
        const displayName = currentUser.fullname || 'User';
        document.getElementById('profileName').textContent = displayName;
        document.getElementById('profileEmail').textContent = currentUser.email || '';
        document.getElementById('profileRole').textContent = currentUser.user_type || 'User';
        
        // Update avatar
        const avatarEl = document.getElementById('profileAvatar');
        const previewEl = document.getElementById('avatarPreview');
        
        if (currentUser.avatar_url) {
            avatarEl.src = currentUser.avatar_url;
            previewEl.src = currentUser.avatar_url;
            currentAvatarBase64 = currentUser.avatar_url;
        } else {
            const initials = getInitials(displayName);
            avatarEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=0d9488&color=fff&size=128`;
            previewEl.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=0d9488&color=fff&size=128`;
        }
        
        // Populate form
        document.getElementById('fullname').value = currentUser.fullname || '';
        document.getElementById('email').value = currentUser.email || '';
        document.getElementById('userType').value = currentUser.user_type || 'User';
        document.getElementById('memberSince').value = currentUser.date_created 
            ? new Date(currentUser.date_created).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) 
            : 'N/A';
        
    } catch (err) {
        console.error('Error loading profile:', err);
        showStatus('Error loading profile: ' + err.message, 'error');
    }
}

// Handle avatar upload
document.getElementById('avatarInput').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
            document.getElementById('profileAvatar').src = e.target.result;
            currentAvatarBase64 = e.target.result;
        };
        reader.readAsDataURL(this.files[0]);
    }
});

function removeAvatar() {
    const displayName = currentUser?.fullname || 'User';
    const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=0d9488&color=fff&size=128`;
    document.getElementById('avatarPreview').src = defaultAvatar;
    document.getElementById('profileAvatar').src = defaultAvatar;
    currentAvatarBase64 = null;
}

// Save profile
document.getElementById('profileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const statusEl = document.getElementById('statusMessage');
    statusEl.className = 'status-message';
    statusEl.textContent = 'Saving...';
    statusEl.classList.add('success');
    
    try {
        const formData = {
            id: currentUser.id,
            fullname: document.getElementById('fullname').value.trim(),
            email: document.getElementById('email').value.trim(),
            avatar_url: currentAvatarBase64
        };
        
        // Add password if provided
        const newPassword = document.getElementById('newPassword').value;
        if (newPassword) {
            formData.password = newPassword;
        }
        
        const res = await fetch(apiBase, {
            method: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });
        
        const data = await res.json();
        
        if (!res.ok) {
            throw new Error(data.error || 'Failed to update profile');
        }
        
        // Clear password fields
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        
        // Update localStorage with new user data
        const storedUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
        storedUser.fullname = formData.fullname;
        storedUser.email = formData.email;
        storedUser.avatar_url = formData.avatar_url;
        localStorage.setItem('currentUser', JSON.stringify(storedUser));
        
        showStatus('Profile updated successfully!', 'success');
        loadProfile();
        
    } catch (err) {
        showStatus('Error: ' + err.message, 'error');
    }
});

function resetForm() {
    if (currentUser) {
        document.getElementById('fullname').value = currentUser.fullname || '';
        document.getElementById('email').value = currentUser.email || '';
        document.getElementById('currentPassword').value = '';
        document.getElementById('newPassword').value = '';
        
        if (currentUser.avatar_url) {
            document.getElementById('avatarPreview').src = currentUser.avatar_url;
            document.getElementById('profileAvatar').src = currentUser.avatar_url;
            currentAvatarBase64 = currentUser.avatar_url;
        } else {
            const displayName = currentUser.fullname || 'User';
            const defaultAvatar = `https://ui-avatars.com/api/?name=${encodeURIComponent(displayName)}&background=0d9488&color=fff&size=128`;
            document.getElementById('avatarPreview').src = defaultAvatar;
            document.getElementById('profileAvatar').src = defaultAvatar;
            currentAvatarBase64 = null;
        }
    }
    document.getElementById('statusMessage').className = 'status-message';
}

function showStatus(message, type) {
    const statusEl = document.getElementById('statusMessage');
    statusEl.textContent = message;
    statusEl.className = 'status-message ' + type;
    
    if (type === 'success') {
        setTimeout(() => {
            statusEl.className = 'status-message';
        }, 5000);
    }
}

// Initialize
loadProfile();
</script>
    </main>
</body>
</html>





