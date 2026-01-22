/**
 * Permission Utility Functions
 * Centralized role checking for frontend permission enforcement
 * 
 * This file provides consistent role checking across all pages
 * to ensure Viewer (Partner Representative) role restrictions are enforced
 */

// Get current user role from JWT token
function getCurrentUserRole() {
    try {
        const token = localStorage.getItem('jwtToken');
        if (!token) return null;
        
        const parts = token.split('.');
        if (parts.length !== 3) return null;
        
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        const roleId = payload.role_id || payload.rid;
        
        // Fetch role name from API if needed, or use cached value
        const cachedRole = localStorage.getItem('currentUserRole');
        if (cachedRole) {
            return cachedRole.toLowerCase();
        }
        
        // Try to get from API
        return null; // Will be set by loadCurrentUser() in admin-header.php
    } catch (e) {
        console.error('Error getting user role:', e);
        return null;
    }
}

// Check if user is Viewer (Partner Representative)
function isViewer() {
    const role = getCurrentUserRole();
    if (!role) {
        // Fallback: check localStorage for currentUser
        try {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            const userRole = (currentUser.role || '').toLowerCase();
            return userRole === 'viewer' || 
                   userRole === 'partner' || 
                   userRole === 'partner representative' ||
                   userRole === 'partner_representative' ||
                   userRole.includes('partner') ||
                   userRole.includes('viewer');
        } catch (e) {
            return false;
        }
    }
    
    return role === 'viewer' || 
           role === 'partner' || 
           role === 'partner representative' ||
           role === 'partner_representative' ||
           role.includes('partner') ||
           role.includes('viewer');
}

// Check if user can create/edit/delete
function canModify() {
    return !isViewer();
}

// Check if user can approve/reject
function canApprove() {
    if (isViewer()) return false;
    const role = getCurrentUserRole();
    return role === 'captain' || role === 'admin' || role === 'kagawad';
}

// Route guard: Redirect Viewer away from admin pages
function enforceViewerRestrictions() {
    if (!isViewer()) return; // Not a viewer, allow access
    
    const currentPath = window.location.pathname;
    const restrictedPages = [
        '/settings.php',
        '/segments.php',
        '/content.php',
        '/partners.php'
    ];
    
    const isRestricted = restrictedPages.some(page => currentPath.includes(page));
    
    if (isRestricted) {
        console.warn('Viewer role cannot access:', currentPath);
        window.location.href = getBasePath() + '/public/dashboard.php';
    }
}

// Get base path
function getBasePath() {
    // Try to get from global variable set by PHP
    if (typeof basePath !== 'undefined') return basePath;
    
    // Fallback: extract from current path
    const path = window.location.pathname;
    const parts = path.split('/');
    const publicIndex = parts.indexOf('public');
    if (publicIndex > -1) {
        return parts.slice(0, publicIndex).join('/') || '';
    }
    return '';
}

// Hide elements for Viewer role
function hideForViewer(selector) {
    if (isViewer()) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            el.style.display = 'none';
        });
    }
}

// Disable elements for Viewer role
function disableForViewer(selector) {
    if (isViewer()) {
        const elements = document.querySelectorAll(selector);
        elements.forEach(el => {
            el.disabled = true;
            el.style.opacity = '0.5';
            el.style.cursor = 'not-allowed';
        });
    }
}

// Make functions globally available
window.isViewer = isViewer;
window.canModify = canModify;
window.canApprove = canApprove;
window.enforceViewerRestrictions = enforceViewerRestrictions;
window.hideForViewer = hideForViewer;
window.disableForViewer = disableForViewer;
window.getCurrentUserRole = getCurrentUserRole;

