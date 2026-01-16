<?php
/**
 * Reusable Admin Header Component - Improved Design
 * Include this file in your pages: <?php include 'sidebar/admin-header.php'; ?>
 * 
 * Features:
 * - Responsive menu toggle
 * - Notification and message icons with badges (outlined style)
 * - User profile with avatar and info
 * - Dark mode support
 * - Clean, modern design
 */
?>

<?php
require_once __DIR__ . '/../../header/includes/path_helper.php';
?>
<link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/notification-modal.css'); ?>">
<link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/message-modal.css'); ?>">
<link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/message-content-modal.css'); ?>">

<!-- Admin Header Component -->
<header class="admin-header">
    <div class="admin-header-left">
        <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <div class="search-container">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" placeholder="Search...">
        </div>
    </div>
    
    <div class="admin-header-right">
        <div class="header-actions">
            <div class="notification-item">
                <button class="notification-btn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                </button>
            </div>
            
            <div class="notification-item">
                <button class="notification-btn" aria-label="Messages">
                    <i class="fas fa-envelope"></i>
                    <span class="notification-badge" id="messageBadge" style="display: none;">0</span>
                </button>
            </div>
        </div>
        
        <div class="header-divider"></div>
        
        <div class="user-profile" id="userProfileBtn">
            <div class="user-info">
                <div class="user-name" id="headerUserName">Loading...</div>
                <div class="user-role" id="headerUserRole">Loading...</div>
            </div>
            <div class="user-avatar">
                <img id="headerUserAvatar" src="https://ui-avatars.com/api/?name=User&background=4c8a89&color=fff&size=128" alt="User" class="avatar-img">
            </div>
            <i class="fas fa-chevron-down dropdown-icon"></i>
        </div>
    </div>
</header>

<!-- User Profile Dropdown -->
<div class="user-profile-dropdown" id="userProfileDropdown">
    <div class="dropdown-header">
        <div class="dropdown-user-info">
            <div class="dropdown-user-avatar">
                <img id="dropdownUserAvatar" src="https://ui-avatars.com/api/?name=User&background=4c8a89&color=fff&size=128" alt="User">
            </div>
            <div class="dropdown-user-details">
                <div class="dropdown-user-name" id="dropdownUserName">Loading...</div>
                <div class="dropdown-user-email" id="dropdownUserEmail">Loading...</div>
            </div>
        </div>
    </div>
    
    <div class="dropdown-body">
        <a href="<?php echo $publicPath; ?>/profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="<?php echo $publicPath; ?>/settings.php" class="dropdown-item">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </div>
    
    <div class="dropdown-footer">
        <a href="#" class="dropdown-item logout-item" id="logoutBtn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<!-- Notification Modal -->
<div class="notification-modal" id="notificationModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Notifications</h3>
            <button class="modal-close" onclick="closeModal('notificationModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="notificationBody">
            <div style="text-align: center; padding: 24px; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 8px;"></i>
                <div>Loading notifications...</div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="view-all-link" onclick="markAllNotificationsRead()" style="background: none; border: none; cursor: pointer; padding: 0.5rem 1rem;">Mark All Read</button>
            <a href="#" class="view-all-link" onclick="loadAllNotifications()">View All Notifications</a>
        </div>
    </div>
</div>

<!-- Message Modal -->
<div class="notification-modal" id="messageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Messages</h3>
            <button class="modal-close" onclick="closeModal('messageModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="messageBody">
            <div style="text-align: center; padding: 24px; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="font-size: 24px; margin-bottom: 8px;"></i>
                <div>Loading messages...</div>
            </div>
        </div>
        <div class="modal-footer">
            <a href="#" class="view-all-link" onclick="loadAllMessages()">View All Messages</a>
        </div>
    </div>
</div>

<!-- Message Content Modal -->
<div class="message-content-modal" id="messageContentModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="message-header-info">
                <img id="messageUserAvatar" src="" alt="" class="message-user-avatar">
                <div class="message-user-info">
                    <h3 id="messageUserName"></h3>
                    <span id="messageUserStatus"></span>
                </div>
            </div>
            <button class="modal-close" onclick="closeModal('messageContentModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body message-chat-body">
            <div id="messageContent"></div>
        </div>
        <div class="modal-footer message-reply-footer">
            <div class="message-reply-box">
                <input type="text" id="messageReplyInput" placeholder="Type a message..." class="message-input">
                <button class="send-message-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Admin Header functionality
document.addEventListener('DOMContentLoaded', function() {
    // Load current user information
    async function loadCurrentUser() {
        try {
            // Clear old cached user data to ensure we always fetch fresh data
            try {
                localStorage.removeItem('currentUser');
            } catch (e) {
                console.warn('Could not clear cached user data:', e);
            }
            
            const token = localStorage.getItem('jwtToken') || '';
            console.log('loadCurrentUser - Token:', token ? 'EXISTS (length: ' + token.length + ')' : 'MISSING');
            if (!token || token.trim() === '') {
                // Token check already handled by auth guard - just return
                console.warn('loadCurrentUser - No token available');
                return;
            }
            
            const apiBase = '<?php echo $apiPath; ?>';
            const apiUrl = apiBase + '/api/v1/users/me';
            console.log('loadCurrentUser - Calling API:', apiUrl);
            console.log('loadCurrentUser - Token being sent:', token ? 'EXISTS (length: ' + token.length + ')' : 'MISSING');
            console.log('loadCurrentUser - Token first 20 chars:', token ? token.substring(0, 20) + '...' : 'N/A');
            console.log('loadCurrentUser - Authorization header:', 'Bearer ' + (token ? token.substring(0, 20) + '...' : 'MISSING'));
            
            // Ensure token is not empty before making request
            if (!token || token.trim() === '') {
                console.error('loadCurrentUser() - Token is empty, cannot make API call');
                console.error('loadCurrentUser() - localStorage keys:', Object.keys(localStorage));
                return;
            }
            
            const res = await fetch(apiUrl, {
                headers: { 
                    'Authorization': 'Bearer ' + token.trim(),
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('loadCurrentUser - Response status:', res.status);
            console.log('loadCurrentUser - Response URL:', res.url);
            
            if (!res.ok) {
                if (res.status === 401) {
                    // Token expired or invalid - clear cached data and show default
                    console.warn('401 Unauthorized - Clearing cached user data');
                    try {
                        localStorage.removeItem('currentUser');
                    } catch (e) {
                        console.error('Error clearing cached user data:', e);
                    }
                    
                    // Try to get response body for more info
                    try {
                        const errorData = await res.json();
                        console.error('Error response:', errorData);
                    } catch (e) {
                        console.error('Could not parse error response');
                    }
                    
                    // Show default user
                    updateUserDisplay({ name: 'User', email: '', role_id: null });
                    return;
                }
                throw new Error('Failed to load user info');
            }
            
            const data = await res.json();
            console.log('=== TASK 2 PROOF: API Response data ===', JSON.stringify(data, null, 2));
            const user = data.user || data.data || data;
            console.log('=== TASK 2 PROOF: Extracted user object ===', JSON.stringify(user, null, 2));
            
            // TASK 3: PROVE WHERE "User" IS COMING FROM
            console.log('=== TASK 3 PROOF: user.name value ===', user.name);
            console.log('=== TASK 3 PROOF: user.name type ===', typeof user.name);
            console.log('=== TASK 3 PROOF: user.name truthy check ===', !!user.name);
            console.log('=== TASK 3 PROOF: user.name || "User" result ===', user.name || 'User');
            
            // Validate that we have valid user data with email
            if (!user || !user.email) {
                console.error('Invalid user data received from API:', user);
                console.error('Full API response:', data);
                throw new Error('Invalid user data received');
            }
            
            console.log('User email from API:', user.email);
            console.log('User ID from API:', user.id);
            
            // Store fresh user data in localStorage for fallback
            try {
                localStorage.setItem('currentUser', JSON.stringify(user));
                console.log('Stored fresh user data for:', user.email);
            } catch (e) {
                console.warn('Could not store user data in localStorage:', e);
            }
            
            updateUserDisplay(user);
        } catch (err) {
            console.error('Failed to load user info:', err);
            // Don't use cached data on error - show default to force fresh fetch on retry
            updateUserDisplay({ name: 'User', email: '', role_id: null });
        }
    }
    
    // Helper function to update user display
    function updateUserDisplay(user) {
        if (!user) {
            user = { name: 'User', email: '', role_id: null };
        }
        
        // Store userId for message functions
        if (user.id) {
            localStorage.setItem('userId', user.id.toString());
        }
        
        // Update header user info
        const userNameEl = document.getElementById('headerUserName');
        const userRoleEl = document.getElementById('headerUserRole');
        const userAvatarEl = document.getElementById('headerUserAvatar');
        
        // TASK 3: PROVE WHERE "User" IS COMING FROM
        console.log('=== TASK 3 PROOF: updateUserDisplay called with user.name ===', user.name);
        console.log('=== TASK 3 PROOF: user.name || "User" will result in ===', user.name || 'User');
        if (userNameEl) {
            const finalName = user.name || 'User';
            console.log('=== TASK 3 PROOF: Setting headerUserName.textContent to ===', finalName);
            userNameEl.textContent = finalName;
        }
        if (userRoleEl) {
            // Map role_id to role name
            const roleNames = {
                1: 'Administrator',
                2: 'Barangay Admin',
                3: 'Campaign Creator',
                4: 'Staff'
            };
            userRoleEl.textContent = roleNames[user.role_id] || user.role || 'User';
        }
        if (userAvatarEl) {
            const encodedName = encodeURIComponent(user.name || 'User');
            userAvatarEl.src = `https://ui-avatars.com/api/?name=${encodedName}&background=4c8a89&color=fff&size=128`;
            userAvatarEl.alt = user.name || 'User';
        }
        
        // Update dropdown user info
        const dropdownNameEl = document.getElementById('dropdownUserName');
        const dropdownEmailEl = document.getElementById('dropdownUserEmail');
        const dropdownAvatarEl = document.getElementById('dropdownUserAvatar');
        
        console.log('Updating dropdown - Name:', user.name, 'Email:', user.email);
        if (dropdownNameEl) dropdownNameEl.textContent = user.name || 'User';
        if (dropdownEmailEl) {
            dropdownEmailEl.textContent = user.email || '';
            console.log('Dropdown email element updated to:', user.email);
        }
        if (dropdownAvatarEl) {
            const encodedName = encodeURIComponent(user.name || 'User');
            dropdownAvatarEl.src = `https://ui-avatars.com/api/?name=${encodedName}&background=4c8a89&color=fff&size=128`;
            dropdownAvatarEl.alt = user.name || 'User';
        }
    }
    
    // Load user info on page load
    loadCurrentUser();
    
    // Load notification count on page load
    loadNotificationCount();
    
    // Refresh notification count every 30 seconds
    setInterval(loadNotificationCount, 30000);
    
    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            try {
                localStorage.removeItem('jwtToken');
                localStorage.removeItem('currentUser');
            } catch (e) {
                console.error('Error clearing localStorage:', e);
            }
            const basePath = '<?php echo $basePath; ?>';
            window.location.href = basePath + '/index.php';
        });
    }
    const menuToggle = document.getElementById('menuToggle');
    
    // Toggle sidebar from header menu button
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            // Use the global sidebarToggle function exposed by sidebar.php
            if (typeof window.sidebarToggle === 'function') {
                window.sidebarToggle();
            } else {
                console.warn('Sidebar toggle function not found. Make sure sidebar.php is included before admin-header.php');
            }
        });
    }
    
    // Search functionality
    const searchInput = document.querySelector('.search-input');
    const searchBtn = document.querySelector('.search-btn');
    
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            if (searchInput) {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    console.log('Searching for:', searchTerm);
                }
            }
        });
    }
    
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = searchInput.value.trim();
                if (searchTerm) {
                    console.log('Searching for:', searchTerm);
                }
            }
        });
    }
    
    // Notification button interactions
    const notificationBtns = document.querySelectorAll('.admin-header .notification-btn');
    notificationBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const ariaLabel = this.getAttribute('aria-label');
            
            if (ariaLabel === 'Notifications') {
                const modal = document.getElementById('notificationModal');
                const messageModal = document.getElementById('messageModal');
                const messageContentModal = document.getElementById('messageContentModal');
                const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
                
                // Remove active class from message button
                if (messageBtn) messageBtn.classList.remove('active');
                
                // Close other modals first
                if (messageModal) messageModal.classList.remove('show');
                if (messageContentModal) messageContentModal.classList.remove('show');
                
                // Toggle notification modal and active state
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    modal.classList.add('show');
                    this.classList.add('active');
                    document.body.style.overflow = '';
                    // Load notifications when opening
                    loadNotifications();
                }
            } else if (ariaLabel === 'Messages') {
                const modal = document.getElementById('messageModal');
                const notificationModal = document.getElementById('notificationModal');
                const messageContentModal = document.getElementById('messageContentModal');
                const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
                
                // Remove active class from notification button
                if (notificationBtn) notificationBtn.classList.remove('active');
                
                // Close other modals first
                if (notificationModal) notificationModal.classList.remove('show');
                if (messageContentModal) messageContentModal.classList.remove('show');
                
                // Toggle message modal and active state
                if (modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                } else {
                    modal.classList.add('show');
                    this.classList.add('active');
                    document.body.style.overflow = '';
                    // Load messages when opening
                    loadMessages();
                }
            }
        });
    });
    
    // Load message count on page load
    loadMessageCount();
    
    // Refresh message count every 30 seconds
    setInterval(loadMessageCount, 30000);
    
    // User profile dropdown functionality
    const userProfileBtn = document.getElementById('userProfileBtn');
    const userProfileDropdown = document.getElementById('userProfileDropdown');
    
    if (userProfileBtn && userProfileDropdown) {
        userProfileBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all modals first (except message content modal)
            const notificationModal = document.getElementById('notificationModal');
            const messageModal = document.getElementById('messageModal');
            const messageContentModal = document.getElementById('messageContentModal');
            
            if (notificationModal) notificationModal.classList.remove('show');
            if (messageModal) messageModal.classList.remove('show');
            // Don't close messageContentModal - let it stay open like Facebook chat
            
            // Remove active states from notification buttons
            const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
            const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
            if (notificationBtn) notificationBtn.classList.remove('active');
            if (messageBtn) messageBtn.classList.remove('active');
            
            // Toggle user profile dropdown and active state
            const isOpen = userProfileDropdown.classList.contains('show');
            userProfileDropdown.classList.toggle('show');
            userProfileBtn.classList.toggle('active', !isOpen);
        });
    }
    
    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        const notificationModal = document.getElementById('notificationModal');
        const messageModal = document.getElementById('messageModal');
        const messageContentModal = document.getElementById('messageContentModal');
        const userProfileDropdown = document.getElementById('userProfileDropdown');
        const notificationBtn = document.querySelector('.notification-btn[aria-label="Notifications"]');
        const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
        
        // Close notification modal when clicking outside
        if (notificationModal && notificationModal.classList.contains('show')) {
            if (!notificationModal.contains(e.target) && !e.target.closest('.notification-btn[aria-label="Notifications"]')) {
                notificationModal.classList.remove('show');
                if (notificationBtn) notificationBtn.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Close message modal when clicking outside
        if (messageModal && messageModal.classList.contains('show')) {
            if (!messageModal.contains(e.target) && !e.target.closest('.notification-btn[aria-label="Messages"]')) {
                messageModal.classList.remove('show');
                if (messageBtn) messageBtn.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        // Close user profile dropdown when clicking outside
        if (userProfileDropdown && userProfileDropdown.classList.contains('show')) {
            if (!userProfileDropdown.contains(e.target) && !e.target.closest('#userProfileBtn')) {
                userProfileDropdown.classList.remove('show');
                userProfileBtn.classList.remove('active');
            }
        }
        
        // Message content modal stays open when clicking outside (don't close it)
    });
    
    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
    
    // Modal functions
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            // Don't hide body scroll for message content modal (Facebook style)
            if (modalId !== 'messageContentModal') {
                document.body.style.overflow = 'hidden';
            }
        }
    }
    
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    function closeAllModals() {
        const modals = document.querySelectorAll('.notification-modal, .message-content-modal');
        modals.forEach(modal => {
            modal.classList.remove('show');
        });
        document.body.style.overflow = '';
    }
    
    // Message item interactions
    const messageItems = document.querySelectorAll('.message-item');
    messageItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const userName = this.querySelector('.message-title').textContent;
            const userAvatar = this.querySelector('.message-avatar img').src;
            const messageText = this.querySelector('.message-text').textContent;
            const messageTime = this.querySelector('.message-time').textContent;
            
            // Remove active state from message button when opening chat
            const messageBtn = document.querySelector('.notification-btn[aria-label="Messages"]');
            if (messageBtn) messageBtn.classList.remove('active');
            
            // Close message dropdown modal
            const messageModal = document.getElementById('messageModal');
            if (messageModal) messageModal.classList.remove('show');
            
            // Open message content modal
            openMessageContent(userName, userAvatar, messageText, messageTime);
            
            // Remove unread status
            const statusDot = this.querySelector('.message-status.unread');
            if (statusDot) {
                statusDot.classList.remove('unread');
            }
        });
    });
    
    // Message content functions
    function openMessageContent(userName, userAvatar, lastMessage, messageTime) {
        const modal = document.getElementById('messageContentModal');
        const nameElement = document.getElementById('messageUserName');
        const avatarElement = document.getElementById('messageUserAvatar');
        const contentElement = document.getElementById('messageContent');
        const statusElement = document.getElementById('messageUserStatus');
        
        // Set user info
        nameElement.textContent = userName;
        avatarElement.src = userAvatar;
        avatarElement.alt = userName;
        statusElement.textContent = 'Active now';
        
        // Create conversation HTML
        contentElement.innerHTML = `
            <div class="chat-message received">
                <div class="message-bubble">${lastMessage}</div>
                <div class="message-time">${messageTime}</div>
            </div>
            <div class="chat-message sent">
                <div class="message-bubble">Thanks for reaching out! I'll get back to you soon.</div>
                <div class="message-time">Just now</div>
            </div>
        `;
        
        // Close message modal and open content modal
        closeModal('messageModal');
        modal.classList.add('show');
        // Don't hide body scroll for Facebook-style chat
        document.body.style.overflow = '';
    }
    
    // Send message functionality
    const sendBtn = document.querySelector('.send-message-btn');
    const messageInput = document.getElementById('messageReplyInput');
    
    if (sendBtn && messageInput) {
        sendBtn.addEventListener('click', sendMessage);
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            const contentElement = document.getElementById('messageContent');
            const newMessage = document.createElement('div');
            newMessage.className = 'chat-message sent';
            newMessage.innerHTML = `
                <div class="message-bubble">${message}</div>
                <div class="message-time">Just now</div>
            `;
            contentElement.appendChild(newMessage);
            messageInput.value = '';
            
            // Scroll to bottom
            contentElement.scrollTop = contentElement.scrollHeight;
        }
    }
    
    // Make functions globally accessible
    window.openModal = openModal;
    window.closeModal = closeModal;
    window.closeAllModals = closeAllModals;
    
    // User profile interaction
    const userProfile = document.querySelector('.admin-header .user-profile');
    if (userProfile) {
        userProfile.addEventListener('click', function() {
            console.log('User profile clicked');
        });
    }
    
    // Notification functions
    async function loadNotifications() {
        const notificationBody = document.getElementById('notificationBody');
        if (!notificationBody) return;
        
        try {
            const token = localStorage.getItem('jwtToken') || '';
            if (!token) {
                console.warn('No JWT token found, skipping notification load');
                return;
            }
            
            const apiBase = '<?php echo $apiPath; ?>';
            
            const res = await fetch(apiBase + '/api/v1/notifications?limit=10', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (!res.ok) {
                if (res.status === 401) {
                    console.warn('Unauthorized access to notifications');
                    return;
                }
                
                // Try to get error message from response
                let errorMsg = 'Failed to load notifications';
                try {
                    const errorData = await res.json();
                    if (errorData.error) {
                        errorMsg = errorData.error;
                    }
                } catch (e) {
                    // Response might not be JSON
                }
                
                console.error('Notification API error:', res.status, errorMsg);
                throw new Error(errorMsg);
            }
            
            const data = await res.json();
            
            // Check if response has error field (backend returned error but 200 status)
            if (data.error) {
                console.error('Notification API returned error:', data.error);
                throw new Error(data.error);
            }
            
            const notifications = data.data || [];
            
            // Show empty state if no notifications
            if (notifications.length === 0) {
                notificationBody.innerHTML = `
                    <div style="text-align: center; padding: 24px; color: #64748b;">
                        <i class="fas fa-bell-slash" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div style="font-weight: 500; margin-bottom: 4px;">No notifications yet</div>
                        <div style="font-size: 12px; opacity: 0.7;">You'll see alerts and updates here</div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            notifications.forEach(notif => {
                const iconClass = getNotificationIcon(notif.type, notif.icon);
                const iconBgClass = notif.type || 'system';
                const timeAgo = formatTimeAgo(notif.created_at);
                const linkAttr = notif.link_url ? `onclick="window.location.href='${notif.link_url}'"` : '';
                const unreadClass = notif.is_read ? '' : 'unread-notification';
                
                html += `
                    <div class="notification-item ${unreadClass}" ${linkAttr} data-id="${notif.id}">
                        <div class="notification-icon ${iconBgClass}">
                            <i class="${iconClass}"></i>
                        </div>
                        <div class="notification-details">
                            <div class="notification-title">${escapeHtml(notif.title)}</div>
                            <div class="notification-text">${escapeHtml(notif.message)}</div>
                            <div class="notification-time">${timeAgo}</div>
                        </div>
                    </div>
                `;
            });
            
            notificationBody.innerHTML = html;
            
            // Add click handlers to mark as read
            notificationBody.querySelectorAll('.notification-item').forEach(item => {
                item.addEventListener('click', function() {
                    const notifId = this.getAttribute('data-id');
                    if (notifId && !this.classList.contains('read')) {
                        markNotificationRead(notifId);
                        this.classList.remove('unread-notification');
                        this.classList.add('read');
                    }
                });
            });
            
        } catch (err) {
            console.error('Error loading notifications:', err);
            console.error('Error details:', {
                message: err.message,
                stack: err.stack
            });
            notificationBody.innerHTML = `
                <div style="text-align: center; padding: 24px; color: #dc2626;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px;"></i>
                    <div style="font-weight: 500; margin-bottom: 4px;">Unable to load notifications</div>
                    <div style="font-size: 12px; opacity: 0.8;">Please try again or contact support if the problem persists</div>
                </div>
            `;
        }
    }
    
    async function loadNotificationCount() {
        try {
            const token = localStorage.getItem('jwtToken') || '';
            if (!token) return;
            
            const apiBase = '<?php echo $apiPath; ?>';
            const res = await fetch(apiBase + '/api/v1/notifications?limit=1', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (!res.ok) return;
            
            const data = await res.json();
            const unreadCount = data.unread_count || 0;
            
            // Update badge
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (err) {
            console.error('Error loading notification count:', err);
        }
    }
    
    async function markNotificationRead(notifId) {
        try {
            const token = localStorage.getItem('jwtToken') || '';
            const apiBase = '<?php echo $apiPath; ?>';
            
            await fetch(apiBase + '/api/v1/notifications/' + notifId + '/read', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            // Update count
            loadNotificationCount();
        } catch (err) {
            console.error('Error marking notification as read:', err);
        }
    }
    
    async function markAllNotificationsRead() {
        try {
            const token = localStorage.getItem('jwtToken') || '';
            const apiBase = '<?php echo $apiPath; ?>';
            
            await fetch(apiBase + '/api/v1/notifications/read-all', {
                method: 'PUT',
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            // Reload notifications
            loadNotifications();
            loadNotificationCount();
        } catch (err) {
            console.error('Error marking all as read:', err);
        }
    }
    
    function getNotificationIcon(type, customIcon) {
        if (customIcon) return customIcon;
        
        const icons = {
            'campaign': 'fas fa-bullhorn',
            'event': 'fas fa-calendar',
            'content': 'fas fa-file-alt',
            'system': 'fas fa-info-circle',
            'alert': 'fas fa-exclamation-triangle',
            'reminder': 'fas fa-clock',
        };
        
        return icons[type] || 'fas fa-bell';
    }
    
    function formatTimeAgo(dateString) {
        if (!dateString) return 'Just now';
        
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return diffMins + ' minute' + (diffMins > 1 ? 's' : '') + ' ago';
        if (diffHours < 24) return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
        if (diffDays < 7) return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
        
        return date.toLocaleDateString();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function loadAllNotifications() {
        // Could navigate to a full notifications page
        console.log('View all notifications');
    }
    
    // Message functions
    async function loadMessages() {
        const messageBody = document.getElementById('messageBody');
        if (!messageBody) return;
        
        try {
            const token = localStorage.getItem('jwtToken') || '';
            if (!token) {
                console.warn('No JWT token found, skipping message load');
                return;
            }
            
            const apiBase = '<?php echo $apiPath; ?>';
            
            const res = await fetch(apiBase + '/api/v1/messages/conversations?limit=10', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (!res.ok) {
                if (res.status === 401) {
                    console.warn('Unauthorized access to messages');
                    return;
                }
                
                // Try to get error message from response
                let errorMsg = 'Failed to load messages';
                try {
                    const errorData = await res.json();
                    if (errorData.error) {
                        errorMsg = errorData.error;
                    }
                } catch (e) {
                    // Response might not be JSON
                }
                
                console.error('Message API error:', res.status, errorMsg);
                throw new Error(errorMsg);
            }
            
            const data = await res.json();
            
            // Check if response has error field (backend returned error but 200 status)
            if (data.error) {
                console.error('Message API returned error:', data.error);
                throw new Error(data.error);
            }
            
            const conversations = data.data || [];
            
            // Show empty state if no conversations
            if (conversations.length === 0) {
                messageBody.innerHTML = `
                    <div style="text-align: center; padding: 24px; color: #64748b;">
                        <i class="fas fa-envelope-open" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div style="font-weight: 500; margin-bottom: 4px;">No messages yet</div>
                        <div style="font-size: 12px; opacity: 0.7;">Start a conversation with a team member</div>
                    </div>
                `;
                return;
            }
            
            let html = '';
            conversations.forEach(conv => {
                const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(conv.other_user_name || 'User')}&background=4c8a89&color=fff&size=64`;
                const timeAgo = formatTimeAgo(conv.last_message_at);
                const unreadClass = conv.unread_count > 0 ? 'unread-message' : '';
                const unreadDot = conv.unread_count > 0 ? '<div class="message-status unread"></div>' : '<div class="message-status"></div>';
                const lastMessage = conv.last_message ? escapeHtml(conv.last_message.substring(0, 50)) : 'No messages';
                const otherUserName = escapeHtml(conv.other_user_name || 'Unknown User');
                
                html += `
                    <div class="message-item ${unreadClass}" onclick="openConversation(${conv.conversation_id}, ${conv.other_user_id}, '${otherUserName}')" data-conv-id="${conv.conversation_id}">
                        <div class="message-avatar">
                            <img src="${avatarUrl}" alt="${otherUserName}">
                        </div>
                        <div class="message-details">
                            <div class="message-title">${otherUserName}</div>
                            <div class="message-text">${lastMessage}</div>
                            <div class="message-time">${timeAgo}</div>
                        </div>
                        ${unreadDot}
                    </div>
                `;
            });
            
            messageBody.innerHTML = html;
            
        } catch (err) {
            console.error('Error loading messages:', err);
            console.error('Error details:', {
                message: err.message,
                stack: err.stack
            });
            messageBody.innerHTML = `
                <div style="text-align: center; padding: 24px; color: #dc2626;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px;"></i>
                    <div style="font-weight: 500; margin-bottom: 4px;">Unable to load messages</div>
                    <div style="font-size: 12px; opacity: 0.8;">Please try again or contact support if the problem persists</div>
                </div>
            `;
        }
    }
    
    async function loadMessageCount() {
        try {
            const token = localStorage.getItem('jwtToken') || '';
            if (!token) return;
            
            const apiBase = '<?php echo $apiPath; ?>';
            const res = await fetch(apiBase + '/api/v1/messages/conversations?limit=1', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (!res.ok) return;
            
            const data = await res.json();
            const unreadCount = data.unread_count || 0;
            
            // Update badge
            const badge = document.getElementById('messageBadge');
            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount.toString();
                    badge.style.display = 'block';
                } else {
                    badge.style.display = 'none';
                }
            }
        } catch (err) {
            console.error('Error loading message count:', err);
        }
    }
    
    function openConversation(conversationId, userId, userName) {
        // Open message content modal with conversation
        const messageContentModal = document.getElementById('messageContentModal');
        const messageUserName = document.getElementById('messageUserName');
        const messageUserAvatar = document.getElementById('messageUserAvatar');
        
        if (messageContentModal && messageUserName && messageUserAvatar) {
            messageUserName.textContent = userName;
            messageUserAvatar.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=4c8a89&color=fff&size=64`;
            messageUserAvatar.alt = userName;
            messageContentModal.setAttribute('data-conversation-id', conversationId);
            messageContentModal.setAttribute('data-user-id', userId);
            messageContentModal.classList.add('show');
            
            // Load conversation messages
            loadConversationMessages(conversationId);
        }
    }
    
    async function loadConversationMessages(conversationId) {
        const messageContent = document.getElementById('messageContent');
        if (!messageContent) return;
        
        try {
            const token = localStorage.getItem('jwtToken') || '';
            const apiBase = '<?php echo $apiPath; ?>';
            
            const res = await fetch(apiBase + '/api/v1/messages/conversations/' + conversationId, {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            
            if (!res.ok) {
                throw new Error('Failed to load conversation');
            }
            
            const data = await res.json();
            const messages = data.data || [];
            
            let html = '';
            messages.forEach(msg => {
                const isSent = msg.sender_id === parseInt(localStorage.getItem('userId') || '0');
                const alignClass = isSent ? 'message-sent' : 'message-received';
                const timeAgo = formatTimeAgo(msg.created_at);
                
                html += `
                    <div class="message-bubble ${alignClass}">
                        <div class="message-bubble-text">${escapeHtml(msg.message_text)}</div>
                        <div class="message-bubble-time">${timeAgo}</div>
                    </div>
                `;
            });
            
            messageContent.innerHTML = html;
            messageContent.scrollTop = messageContent.scrollHeight;
            
            // Reload message list to update unread status
            loadMessages();
            loadMessageCount();
        } catch (err) {
            console.error('Error loading conversation:', err);
            messageContent.innerHTML = '<div style="padding: 16px; color: #dc2626;">Error loading conversation</div>';
        }
    }
    
    function loadAllMessages() {
        // Could navigate to a full messages page
        console.log('View all messages');
    }
    
    // Make functions globally accessible
    window.loadNotifications = loadNotifications;
    window.markAllNotificationsRead = markAllNotificationsRead;
    window.loadMessages = loadMessages;
    window.loadMessageCount = loadMessageCount;
    window.openConversation = openConversation;
});
</script>
