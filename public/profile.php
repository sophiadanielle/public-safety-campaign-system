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
                    
                    window.location.replace(basePath + '/index.php');
                } catch (e) {
                    if (justLoggedIn && retryCount < maxRetries) {
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, 300);
                    } else {
                        window.location.replace(basePath + '/index.php');
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
        <h1>User Profile</h1>
        <p>Manage your account information and preferences</p>
    </div>

    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <img id="profileAvatar" src="https://ui-avatars.com/api/?name=User&background=4c8a89&color=fff&size=128" alt="User">
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
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-field">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Optional">
                    </div>
                    <div class="form-field">
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" readonly>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Account Information</h3>
                <div class="form-grid">
                    <div class="form-field">
                        <label for="role">Role</label>
                        <input type="text" id="role" name="role" readonly>
                    </div>
                    <div class="form-field">
                        <label for="memberSince">Member Since</label>
                        <input type="text" id="memberSince" name="memberSince" readonly>
                    </div>
                </div>
            </div>

            <div class="status-message" id="statusMessage"></div>

            <div style="display: flex; gap: 12px; margin-top: 24px;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
            </div>
        </form>
    </div>
</main>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const basePath = '<?php echo $basePath; ?>';
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
let currentUser = null;

// Load user profile
async function loadProfile() {
    try {
        const res = await fetch(apiBase + '/api/v1/users/me', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (!res.ok) {
            if (res.status === 401) {
                window.location.href = basePath + '/index.php';
                return;
            }
            throw new Error('Failed to load profile');
        }
        
        const data = await res.json();
        currentUser = data.user || data.data || data;
        
        // Update display
        document.getElementById('profileName').textContent = currentUser.name || 'User';
        document.getElementById('profileEmail').textContent = currentUser.email || '';
        
        // Use role name from API if available, otherwise fallback to hardcoded mapping
        let roleDisplayName = currentUser.role || 'User';
        
        // Fallback mapping for legacy role_ids (if role name not in API response)
        if (!currentUser.role && currentUser.role_id) {
            const roleNames = {
                1: 'Barangay Administrator',
                2: 'Barangay Staff',
                3: 'School Partner',
                4: 'NGO Partner'
            };
            roleDisplayName = roleNames[currentUser.role_id] || 'User';
        }
        
        // Capitalize first letter of each word for display
        roleDisplayName = roleDisplayName.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
        
        document.getElementById('profileRole').textContent = roleDisplayName;
        
        // Update avatar
        const encodedName = encodeURIComponent(currentUser.name || 'User');
        document.getElementById('profileAvatar').src = `https://ui-avatars.com/api/?name=${encodedName}&background=4c8a89&color=fff&size=128`;
        
        // Populate form
        document.getElementById('name').value = currentUser.name || '';
        document.getElementById('email').value = currentUser.email || '';
        document.getElementById('phone').value = currentUser.phone || '';
        document.getElementById('barangay').value = currentUser.barangay_name || 'N/A';
        
        // Use role name from API if available
        let roleFormValue = currentUser.role || 'User';
        if (!currentUser.role && currentUser.role_id) {
            const roleNames = {
                1: 'Barangay Administrator',
                2: 'Barangay Staff',
                3: 'School Partner',
                4: 'NGO Partner'
            };
            roleFormValue = roleNames[currentUser.role_id] || 'User';
        }
        roleFormValue = roleFormValue.split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
        ).join(' ');
        document.getElementById('role').value = roleFormValue;
        
        document.getElementById('memberSince').value = currentUser.created_at ? new Date(currentUser.created_at).toLocaleDateString() : 'N/A';
        
    } catch (err) {
        console.error('Error loading profile:', err);
        showStatus('Error loading profile: ' + err.message, 'error');
    }
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
            name: document.getElementById('name').value.trim(),
            email: document.getElementById('email').value.trim(),
            phone: document.getElementById('phone').value.trim() || null,
        };
        
        const res = await fetch(apiBase + '/api/v1/users/me', {
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
        
        showStatus('Profile updated successfully!', 'success');
        loadProfile(); // Reload to get updated data
        
    } catch (err) {
        showStatus('Error: ' + err.message, 'error');
    }
});

function resetForm() {
    if (currentUser) {
        document.getElementById('name').value = currentUser.name || '';
        document.getElementById('email').value = currentUser.email || '';
        document.getElementById('phone').value = currentUser.phone || '';
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





