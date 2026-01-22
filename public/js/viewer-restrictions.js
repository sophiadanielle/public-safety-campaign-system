/**
 * Viewer Role Restrictions - Aggressive Enforcement
 * This script aggressively hides all create/edit/delete/approve buttons for Viewer role
 * Runs on all pages to ensure Viewer restrictions are enforced
 */

(function() {
    'use strict';
    
    // Helper function to check if user is Viewer
    function checkIfViewer() {
        try {
            // Method 1: Check localStorage currentUser
            const currentUserStr = localStorage.getItem('currentUser');
            if (currentUserStr) {
                const currentUser = JSON.parse(currentUserStr);
                const userRole = (currentUser.role || '').toLowerCase();
                if (userRole === 'viewer' || 
                    userRole === 'partner' || 
                    userRole === 'partner representative' ||
                    userRole === 'partner_representative' ||
                    userRole.includes('partner') ||
                    userRole.includes('viewer')) {
                    return true;
                }
            }
            
            // Method 2: Check JWT token
            const token = localStorage.getItem('jwtToken');
            if (token) {
                try {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        const roleId = payload.role_id || payload.rid;
                        // Role IDs 3, 4, or 6 are typically Partner/Viewer roles
                        if (roleId === 3 || roleId === 4 || roleId === 6) {
                            return true;
                        }
                        const roleName = (payload.role || '').toLowerCase();
                        if (roleName === 'viewer' || roleName === 'partner' || 
                            roleName.includes('partner') || roleName.includes('viewer')) {
                            return true;
                        }
                    }
                } catch (e) {
                    // Ignore JWT decode errors
                }
            }
            
            return false;
        } catch (e) {
            return false;
        }
    }
    
    // Aggressively hide all action buttons for Viewer
    function hideViewerActionButtons() {
        if (!checkIfViewer()) {
            return; // Not a viewer, don't hide anything
        }
        
        console.log('VIEWER RESTRICTIONS: Hiding all action buttons for Viewer role');
        
        // Hide buttons by text content
        document.querySelectorAll('button, a.btn, .btn').forEach(btn => {
            const text = (btn.textContent || btn.innerText || '').toLowerCase().trim();
            const onclick = (btn.getAttribute('onclick') || '').toLowerCase();
            const href = (btn.getAttribute('href') || '').toLowerCase();
            
            // Check if button is an action button
            const isActionButton = 
                text.includes('create') || 
                text.includes('add') || 
                text.includes('edit') || 
                text.includes('delete') || 
                text.includes('approve') || 
                text.includes('reject') || 
                text.includes('forward') || 
                text.includes('schedule') ||
                text.includes('publish') ||
                text.includes('close') ||
                text.includes('archive') ||
                text.includes('generate') ||
                onclick.includes('create') ||
                onclick.includes('edit') ||
                onclick.includes('delete') ||
                onclick.includes('approve') ||
                onclick.includes('generate') ||
                href.includes('create') ||
                href.includes('planning-section') ||
                href.includes('create-event') ||
                href.includes('add-partner');
            
            if (isActionButton) {
                btn.style.display = 'none';
                btn.style.visibility = 'hidden';
                btn.remove();
            }
        });
        
        // Hide specific containers
        const containersToHide = [
            '#dashboard-quick-actions',
            '.quick-actions',
            '#create-survey',
            '#survey-builder',
            '#survey-analytics',
            '#create-event',
            '#planning-section',
            '#automl-section'
        ];
        
        containersToHide.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                el.style.display = 'none';
                el.remove();
            });
        });
        
        // Hide action columns in tables
        document.querySelectorAll('table th, table td').forEach(cell => {
            const text = (cell.textContent || '').toLowerCase();
            if (text === 'actions' || text === 'action') {
                const colIndex = Array.from(cell.parentElement.children).indexOf(cell);
                // Hide this column in all rows
                cell.style.display = 'none';
                document.querySelectorAll(`table tr td:nth-child(${colIndex + 1})`).forEach(td => {
                    td.style.display = 'none';
                });
            }
        });
    }
    
    // Run immediately
    hideViewerActionButtons();
    
    // Run after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideViewerActionButtons);
    } else {
        setTimeout(hideViewerActionButtons, 100);
    }
    
    // Run after a delay to catch dynamically loaded content
    setTimeout(hideViewerActionButtons, 500);
    setTimeout(hideViewerActionButtons, 1000);
    setTimeout(hideViewerActionButtons, 2000);
    
    // Watch for dynamically added content
    const observer = new MutationObserver(function(mutations) {
        hideViewerActionButtons();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Make function globally available
    window.hideViewerActionButtons = hideViewerActionButtons;
    window.checkIfViewer = checkIfViewer;
})();
