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
<body class="module-settings" data-module="settings">
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

// Initialize
loadSettings();
</script>
    </main>
</body>
</html>



