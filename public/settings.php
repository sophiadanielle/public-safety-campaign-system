<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        // Auth guard + Viewer role restriction
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
                        
                        // RBAC: Check if Viewer role - redirect to dashboard
                        try {
                            const parts = token.split('.');
                            if (parts.length === 3) {
                                const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                                const roleId = payload.role_id || payload.rid;
                                
                                // Check if role is Viewer/Partner (typically role_id 3, 4, or 6)
                                // Viewer role should not access Settings
                                const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
                                const userRole = (currentUser.role || '').toLowerCase();
                                const isViewerRole = userRole === 'viewer' || 
                                                    userRole === 'partner' || 
                                                    userRole === 'partner representative' ||
                                                    userRole.includes('partner') ||
                                                    userRole.includes('viewer');
                                
                                if (isViewerRole) {
                                    console.warn('Viewer role cannot access Settings page');
                                    window.location.replace(basePath + '/public/dashboard.php');
                                    return;
                                }
                            }
                        } catch (e) {
                            console.error('Error checking role:', e);
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
<body class="module-settings" data-module="settings">
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
    
    .settings-page {
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
    
    .settings-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 32px;
        margin-bottom: 24px;
    }
    
    .settings-section {
        margin-bottom: 32px;
    }
    
    .settings-section:last-child {
        margin-bottom: 0;
    }
    
    .settings-section h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .settings-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .settings-item:last-child {
        border-bottom: none;
    }
    
    .settings-item-label {
        flex: 1;
    }
    
    .settings-item-label strong {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 4px;
    }
    
    .settings-item-label span {
        font-size: 13px;
        color: #64748b;
    }
    
    .settings-item-control {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .toggle-switch {
        position: relative;
        width: 48px;
        height: 24px;
        background: #cbd5e1;
        border-radius: 12px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .toggle-switch.active {
        background: #4c8a89;
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        background: white;
        border-radius: 50%;
        top: 2px;
        left: 2px;
        transition: transform 0.3s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    
    .toggle-switch.active::after {
        transform: translateX(24px);
    }
    
    .form-field {
        display: flex;
        flex-direction: column;
        margin-bottom: 20px;
    }
    
    .form-field label {
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 8px;
        font-size: 14px;
    }
    
    .form-field input,
    .form-field select {
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    
    .form-field input:focus,
    .form-field select:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
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

<main class="settings-page">
    <div class="page-header">
        <h1>Settings</h1>
        <p>Manage your account settings and preferences</p>
    </div>

    <!-- Notification Settings -->
    <div class="settings-card">
        <div class="settings-section">
            <h3>🔔 Notification Preferences</h3>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Email Notifications</strong>
                    <span>Receive email notifications for important updates</span>
                </div>
                <div class="settings-item-control">
                    <div class="toggle-switch active" id="emailNotifications" onclick="toggleSetting(this, 'email_notifications')"></div>
                </div>
            </div>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Campaign Updates</strong>
                    <span>Get notified when campaigns you're involved in are updated</span>
                </div>
                <div class="settings-item-control">
                    <div class="toggle-switch active" id="campaignUpdates" onclick="toggleSetting(this, 'campaign_updates')"></div>
                </div>
            </div>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Event Reminders</strong>
                    <span>Receive reminders for upcoming events and seminars</span>
                </div>
                <div class="settings-item-control">
                    <div class="toggle-switch active" id="eventReminders" onclick="toggleSetting(this, 'event_reminders')"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Settings -->
    <div class="settings-card">
        <div class="settings-section">
            <h3>🎨 Display Preferences</h3>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Theme</strong>
                    <span>Choose your preferred color theme</span>
                </div>
                <div class="settings-item-control">
                    <select id="themeSelect" onchange="changeTheme(this.value)" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="light">Light</option>
                        <option value="dark">Dark</option>
                        <option value="auto">Auto (System)</option>
                    </select>
                </div>
            </div>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Language</strong>
                    <span>Select your preferred language</span>
                </div>
                <div class="settings-item-control">
                    <select id="languageSelect" onchange="changeLanguage(this.value)" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                        <option value="en">English</option>
                        <option value="tl">Tagalog</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Screen Lock Settings -->
    <div class="settings-card">
        <div class="settings-section">
            <h3>🔐 Screen Lock</h3>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Enable Screen Lock</strong>
                    <span>Lock your screen with Ctrl+L to protect your session</span>
                </div>
                <div class="settings-item-control">
                    <div class="toggle-switch" id="screenLockToggle" onclick="toggleSetting(this, 'screen_lock_enabled')"></div>
                </div>
            </div>
            <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-top: 16px;">
                <div style="display: flex; align-items: start; gap: 12px;">
                    <i class="fas fa-info-circle" style="color: #4c8a89; font-size: 18px; margin-top: 2px;"></i>
                    <div>
                        <strong style="display: block; color: #0f172a; margin-bottom: 8px; font-size: 14px;">Keyboard Shortcut</strong>
                        <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                            Press <kbd style="background: white; border: 2px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; font-family: monospace; font-size: 12px; font-weight: 600;">Ctrl</kbd> + <kbd style="background: white; border: 2px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; font-family: monospace; font-size: 12px; font-weight: 600;">L</kbd> to lock your screen instantly.
                            <br>You'll need to enter your password to unlock.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Settings -->
    <div class="settings-card">
        <div class="settings-section">
            <h3>🔒 Security</h3>
            <form id="passwordForm">
                <div class="form-field">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-field">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required minlength="6">
                </div>
                <div class="form-field">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6">
                </div>
                <div class="status-message" id="passwordStatus"></div>
                <button type="submit" class="btn btn-primary" style="margin-top: 8px;">Change Password</button>
            </form>
        </div>
    </div>

    <!-- Account Actions -->
    <div class="settings-card">
        <div class="settings-section">
            <h3>⚙️ Account Actions</h3>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Export Data</strong>
                    <span>Download a copy of your account data</span>
                </div>
                <div class="settings-item-control">
                    <button class="btn btn-secondary" onclick="exportData()">Export</button>
                </div>
            </div>
            <div class="settings-item">
                <div class="settings-item-label">
                    <strong>Delete Account</strong>
                    <span>Permanently delete your account and all data</span>
                </div>
                <div class="settings-item-control">
                    <button class="btn btn-secondary" onclick="deleteAccount()" style="background: #dc2626; color: white; border-color: #dc2626;">Delete</button>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Screen Lock Overlay -->
<div id="screenLockOverlay" class="screen-lock-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; z-index: 99999;">
    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; width: 100%; height: 100%; padding: 20px; box-sizing: border-box;">
        <!-- System Logo -->
        <div style="margin-bottom: 8px;">
            <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="System Logo" class="lock-screen-logo" style="width: 100px; height: 100px;">
        </div>
        
        <!-- User Profile -->
        <div style="background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(10px); border-radius: 20px; padding: 40px; max-width: 400px; width: 100%; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2); text-align: center; color: white;">
            <!-- Profile Picture -->
            <div style="margin-bottom: 24px;">
                <div id="lockScreenAvatar" style="width: 100px; height: 100px; border-radius: 50%; background: white; margin: 0 auto; display: flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; color: #4c8a89; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);"></div>
            </div>
            
            <!-- Full Name -->
            <h2 id="lockScreenName" style="margin: 0 0 8px 0; font-size: 24px; font-weight: 700;"></h2>
            <p style="margin: 0 0 32px 0; opacity: 0.9; font-size: 14px;">Screen is locked</p>
            
            <!-- Password Input -->
            <form id="unlockForm" onsubmit="return unlockScreen(event);">
                <div style="position: relative; margin-bottom: 16px;">
                    <input 
                        type="password" 
                        id="unlockPassword" 
                        placeholder="Enter password to unlock" 
                        style="width: 100%; padding: 16px 48px 16px 16px; border: 2px solid rgba(255, 255, 255, 0.3); border-radius: 12px; font-size: 16px; background: rgba(255, 255, 255, 0.2); color: white; outline: none; box-sizing: border-box;"
                        autocomplete="off"
                    >
                    <i class="fas fa-lock" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); opacity: 0.7;"></i>
                </div>
                <div id="unlockError" style="display: none; background: rgba(220, 38, 38, 0.2); border: 2px solid rgba(220, 38, 38, 0.5); border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 14px;">
                    <i class="fas fa-exclamation-circle"></i> Incorrect password. Try again.
                </div>
                <button type="submit" style="width: 100%; padding: 16px; background: white; color: #4c8a89; border: none; border-radius: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
                    <i class="fas fa-unlock"></i> Unlock
                </button>
            </form>
            
            <!-- Time Display -->
            <div id="lockScreenTime" style="margin-top: 24px; font-size: 48px; font-weight: 300; opacity: 0.9;"></div>
        </div>
    </div>
</div>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

// Load saved settings
function loadSettings() {
    // Load theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.getElementById('themeSelect').value = savedTheme;
    
    // Load notification preferences
    const emailNotif = localStorage.getItem('email_notifications') !== 'false';
    const campaignUpdates = localStorage.getItem('campaign_updates') !== 'false';
    const eventReminders = localStorage.getItem('event_reminders') !== 'false';
    
    if (emailNotif) document.getElementById('emailNotifications').classList.add('active');
    if (campaignUpdates) document.getElementById('campaignUpdates').classList.add('active');
    if (eventReminders) document.getElementById('eventReminders').classList.add('active');
}

// Toggle setting
function toggleSetting(element, settingKey) {
    element.classList.toggle('active');
    const isActive = element.classList.contains('active');
    localStorage.setItem(settingKey, isActive);
    showStatus('Setting saved', 'success');
}

// Change theme
function changeTheme(theme) {
    localStorage.setItem('theme', theme);
    document.documentElement.setAttribute('data-theme', theme);
    showStatus('Theme updated', 'success');
}

// Change language
function changeLanguage(lang) {
    localStorage.setItem('language', lang);
    showStatus('Language preference saved', 'success');
}

// Change password
document.getElementById('passwordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (newPassword !== confirmPassword) {
        showPasswordStatus('New passwords do not match', 'error');
        return;
    }
    
    if (newPassword.length < 6) {
        showPasswordStatus('Password must be at least 6 characters', 'error');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/users/change-password', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const data = await res.json();
        
        if (!res.ok) {
            throw new Error(data.error || 'Failed to change password');
        }
        
        showPasswordStatus('Password changed successfully!', 'success');
        document.getElementById('passwordForm').reset();
        
    } catch (err) {
        showPasswordStatus('Error: ' + err.message, 'error');
    }
});

function showPasswordStatus(message, type) {
    const statusEl = document.getElementById('passwordStatus');
    statusEl.textContent = message;
    statusEl.className = 'status-message ' + type;
    
    if (type === 'success') {
        setTimeout(() => {
            statusEl.className = 'status-message';
        }, 5000);
    }
}

function showStatus(message, type) {
    // Create temporary status message
    const statusEl = document.createElement('div');
    statusEl.className = 'status-message ' + type;
    statusEl.textContent = message;
    statusEl.style.position = 'fixed';
    statusEl.style.top = '90px';
    statusEl.style.right = '24px';
    statusEl.style.zIndex = '10000';
    statusEl.style.minWidth = '200px';
    document.body.appendChild(statusEl);
    
    setTimeout(() => {
        statusEl.remove();
    }, 3000);
}

function exportData() {
    showStatus('Export feature coming soon', 'success');
}

function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        if (confirm('This will permanently delete all your data. Type DELETE to confirm.')) {
            showStatus('Account deletion feature coming soon', 'error');
        }
    }
}

// Update lock screen theme based on current theme
function updateLockScreenTheme() {
    const overlay = document.getElementById('screenLockOverlay');
    const logo = document.querySelector('.lock-screen-logo');
    const theme = localStorage.getItem('theme') || 'light';
    
    if (theme === 'dark') {
        overlay.style.background = 'linear-gradient(135deg, #0d3d3a 0%, #1a5c57 50%, #0f2e2b 100%)';
        if (logo) logo.style.filter = 'brightness(1.2) drop-shadow(0 2px 8px rgba(76, 138, 137, 0.3))';
    } else {
        overlay.style.background = 'linear-gradient(135deg, #2d7a75 0%, #4c8a89 50%, #3a6b68 100%)';
        if (logo) logo.style.filter = 'brightness(1.1) drop-shadow(0 2px 8px rgba(0,0,0,0.2))';
    }
}

// Screen Lock Functionality
function initScreenLock() {
    // Load screen lock preference
    const screenLockEnabled = localStorage.getItem('screen_lock_enabled') === 'true';
    if (screenLockEnabled) {
        document.getElementById('screenLockToggle').classList.add('active');
    }
    
    // Check if screen was locked before refresh
    const wasLocked = sessionStorage.getItem('screen_locked') === 'true';
    if (wasLocked && screenLockEnabled) {
        // Re-activate lock screen after page load
        setTimeout(() => {
            activateScreenLock();
        }, 100);
    }
    
    // Add Ctrl+L keyboard listener
    document.addEventListener('keydown', function(e) {
        // Check if Ctrl+L is pressed
        if (e.ctrlKey && e.key === 'l') {
            e.preventDefault(); // Prevent browser's default behavior
            
            // Check if screen lock is enabled
            const isEnabled = localStorage.getItem('screen_lock_enabled') === 'true';
            if (isEnabled) {
                activateScreenLock();
            }
        }
    });
    
    // Update time on lock screen every second
    setInterval(updateLockScreenTime, 1000);
}

function activateScreenLock() {
    // Get user info from localStorage
    const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
    const fullName = currentUser.full_name || currentUser.name || 'User';
    
    // Set user name
    document.getElementById('lockScreenName').textContent = fullName;
    
    // Set avatar (first letter of name)
    const firstLetter = fullName.charAt(0).toUpperCase();
    document.getElementById('lockScreenAvatar').textContent = firstLetter;
    
    // Update time
    updateLockScreenTime();
    
    // Clear password field and error
    document.getElementById('unlockPassword').value = '';
    document.getElementById('unlockError').style.display = 'none';
    
    // Update theme-aware background
    updateLockScreenTheme();
    
    // Show overlay
    const overlay = document.getElementById('screenLockOverlay');
    overlay.style.display = 'flex';
    
    // Set lock state in sessionStorage for persistence
    sessionStorage.setItem('screen_locked', 'true');
    
    // Focus on password input
    setTimeout(() => {
        document.getElementById('unlockPassword').focus();
    }, 100);
}

function unlockScreen(event) {
    event.preventDefault();
    
    const passwordEl = document.getElementById('unlockPassword');
    const errorEl = document.getElementById('unlockError');
    const password = passwordEl.value.trim();
    
    // Check password (hardcoded as 'password' for now)
    if (password === 'password') {
        // Correct password - unlock
        document.getElementById('screenLockOverlay').style.display = 'none';
        passwordEl.value = '';
        errorEl.style.display = 'none';
        // Clear lock state from sessionStorage
        sessionStorage.removeItem('screen_locked');
    } else {
        // Incorrect password - show error
        errorEl.style.display = 'block';
        passwordEl.value = '';
        passwordEl.focus();
        
        // Shake animation
        const form = document.getElementById('unlockForm');
        form.style.animation = 'shake 0.5s';
        setTimeout(() => {
            form.style.animation = '';
        }, 500);
    }
    
    return false;
}

function updateLockScreenTime() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const timeEl = document.getElementById('lockScreenTime');
    if (timeEl) {
        timeEl.textContent = hours + ':' + minutes;
    }
}

// Initialize
loadSettings();
initScreenLock();
</script>

<style>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
    20%, 40%, 60%, 80% { transform: translateX(10px); }
}

#unlockPassword::placeholder {
    color: rgba(255, 255, 255, 0.7);
}

#unlockPassword:focus {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.25);
}

button[type="submit"]:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

button[type="submit"]:active {
    transform: translateY(0);
}
</style>
    </main>
</body>
</html>









