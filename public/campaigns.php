<?php
$pageTitle = 'Campaign Planning';
// Custom header setup for sidebar + admin-header layout
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        // Auth guard - MUST be first script executed
        (function () {
            const basePath = '<?php echo $basePath; ?>';
            
            // Check if we just logged in (URL parameter) - this bypasses Tracking Prevention blocking
            const urlParams = new URLSearchParams(window.location.search);
            const justLoggedIn = urlParams.has('logged_in') || urlParams.has('signed_up');
            console.log('Auth guard - Just logged in:', justLoggedIn);
            
            function checkAuth(retryCount) {
                retryCount = retryCount || 0;
                const maxRetries = justLoggedIn ? 20 : 5; // More retries if just logged in
                
                try {
                    const token = localStorage.getItem('jwtToken');
                    console.log('Auth guard - Attempt ' + (retryCount + 1) + ' - Token:', token ? 'EXISTS (length: ' + token.length + ')' : 'MISSING');
                    
                    if (token && token.trim() !== '') {
                        console.log('Token found - access granted');
                        console.log('Token value (first 50 chars):', token.substring(0, 50) + '...');
                        // Remove URL parameter if present
                        if (justLoggedIn) {
                            const cleanUrl = window.location.pathname;
                            window.history.replaceState({}, '', cleanUrl);
                            console.log('URL parameter removed, clean URL:', cleanUrl);
                        }
                        return;
                    }
                    
                    // Token not found - retry if we haven't exceeded max retries
                    if (retryCount < maxRetries) {
                        const delay = justLoggedIn ? 300 : 100; // Longer delay if just logged in
                        console.log('Token not found, retrying in ' + delay + 'ms... (attempt ' + (retryCount + 1) + '/' + maxRetries + ')');
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, delay);
                        return;
                    }
                    
                    // Max retries exceeded - redirect to login
                    console.error('No token found after ' + maxRetries + ' attempts - redirecting to login');
                    window.location.replace(basePath + '/login.php');
                } catch (e) {
                    console.error('Auth guard error:', e);
                    // If error accessing localStorage and we just logged in, retry
                    if (justLoggedIn && retryCount < maxRetries) {
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, 300);
                    } else {
                        window.location.replace(basePath + '/login.php');
                    }
                }
            }
            
            // Start checking
            checkAuth(0);
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="<?php echo htmlspecialchars($publicPath . '/js/custom-modals.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicPath . '/js/modal-replacer.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($basePath . '/public/js/viewer-restrictions.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($basePath . '/public/js/viewer-restrictions.js'); ?>"></script>
    <script>
        // Force light theme
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        
        // RBAC FIX: Set role cookie IMMEDIATELY in <head> BEFORE sidebar renders
        // This ensures PHP can read the cookie when sidebar is included
        (function() {
            try {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        const roleId = payload.role_id || payload.rid;
                        if (roleId && typeof roleId === 'number') {
                            const expires = new Date();
                            expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000));
                            document.cookie = 'user_role_id=' + roleId + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
                            console.log('RBAC: Set user_role_id cookie in <head> =', roleId);
                        }
                    }
                }
            } catch (e) {
                console.error('RBAC: Failed to set role cookie in <head>:', e);
            }
        })();
        
        // IMMEDIATE DEBUG: Check localStorage access right away
        console.log('=== IMMEDIATE CHECK (before any scripts) ===');
        console.log('localStorage available:', typeof(Storage) !== 'undefined');
        try {
            const immediateToken = localStorage.getItem('jwtToken');
            console.log('Token check (immediate):', immediateToken ? 'FOUND (length: ' + immediateToken.length + ')' : 'NOT FOUND');
            console.log('All localStorage keys (immediate):', Object.keys(localStorage));
        } catch (e) {
            console.error('Error accessing localStorage (immediate):', e);
        }
    </script>
</head>
<body class="module-campaign" data-module="campaigns">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <!-- Main Content Wrapper - accounts for sidebar (280px) and header (70px) -->
    <main class="main-content-wrapper">
        <div class="campaign-page">
<style>
                html, body {
            margin: 13px;
            padding: 0;
        }

    /* Main content wrapper - accounts for fixed sidebar and header */
    .main-content-wrapper {
        margin-left: 280px; /* Main sidebar only */
        margin-top: 60px;
        padding-top: 0;
        min-height: calc(100vh - 60px);
        transition: margin-left 0.3s ease;
        overflow-x: hidden;
    }
    
    /* Make sidebar visible by default on desktop */
    @media (min-width: 769px) {
        .sidebar {
            transform: translateX(0) !important;
        }
    }
    
    /* Responsive: hide sidebar on mobile, adjust main content */
    @media (max-width: 1024px) {
        .main-content-wrapper {
            margin-left: 280px !important;
        }
    }
    
    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0 !important;
        }
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.sidebar-open {
            transform: translateX(0);
        }
    }

    .campaign-page {
        width: 100%;
        margin: 0;
        padding: 4px 24px 24px 24px;
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
        box-sizing: border-box;
        overflow-x: hidden;
    }
    .campaign-layout {
        display: block; /* Changed from flex since sidebar is now fixed */
    }
    .campaign-main {
        width: 100%;
    }
    .page-title {
        margin: 0 0 6px;
        font-size: 28px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        line-height: 1.2;
    }
    .page-subtitle {
        margin: 0 0 24px;
        color: #64748b;
        font-size: 14px;
        line-height: 1.5;
    }
    /* Ensure list section card allows horizontal scrolling */
    #list-section.card {
        overflow-x: visible;
        overflow-y: visible;
    }
    
    /* Ensure table wrapper inside list section is not clipped */
    #list-section .table-wrapper {
        margin-left: -28px;
        margin-right: -28px;
        padding-left: 28px;
        padding-right: 28px;
        width: calc(100% + 56px);
    }
    
    .card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        padding: 28px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.08);
        margin-bottom: 28px;
        transition: all 0.3s ease;
        position: relative;
        overflow: visible; /* Changed from hidden to allow dropdowns to show */
        /* When navigating via in-page anchors, keep the card title visible below the sticky header */
        scroll-margin-top: 90px;
    }
    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4c8a89 0%, #667eea 50%, #764ba2 100%);
    }
    .card:hover {
        box-shadow: 0 8px 30px rgba(15, 23, 42, 0.12);
        transform: translateY(-2px);
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
        position: relative;
    }
    .section-header::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: linear-gradient(90deg, #4c8a89 0%, #667eea 100%);
    }
    .section-title {
        margin: 0;
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-title::before {
        content: '';
        width: 3px;
        height: 24px;
        background: #4c8a89;
        border-radius: 2px;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .badge.draft { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #4b5563; }
    .badge.pending { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
    .badge.approved { background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%); color: #1e40af; }
    .badge.ongoing { background: linear-gradient(135deg, #dcfce7 0%, #86efac 100%); color: #166534; }
    .badge.completed { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); color: #ffffff; }
    .badge.scheduled { background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%); color: #ffffff; }
    .badge.active { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: #ffffff; }
    .badge.archived { background: linear-gradient(135deg, #78716c 0%, #57534e 100%); color: #ffffff; }
    .badge.cancelled { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: #ffffff; }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .form-field {
        display: flex;
        flex-direction: column;
    }
    .form-field label {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 10px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .form-field label::before {
        content: '▸';
        color: #4c8a89;
        font-size: 12px;
    }
    .form-field input,
    .form-field textarea {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
    }
    .form-field input:focus,
    .form-field textarea:focus {
        outline: none;
        border-color: #4c8a89;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
    }
    .form-field.full-width {
        grid-column: 1 / -1;
    }
    
    .tabs {
        display: flex;
        gap: 4px;
        margin-bottom: 24px;
        border-bottom: 2px solid #f1f5f9;
        background: #f8fafc;
        padding: 4px;
        border-radius: 12px;
    }
    .tab {
        padding: 12px 24px;
        background: transparent;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        color: #64748b;
        transition: all 0.3s;
        position: relative;
        font-size: 14px;
    }
    .tab:hover {
        color: #4c8a89;
        background: rgba(76, 138, 137, 0.1);
    }
    .tab.active {
        color: #fff;
        background: linear-gradient(135deg, #4c8a89 0%, #667eea 100%);
        box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
    }
    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .tab-content.active {
        display: block;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    #calendar {
        margin-top: 24px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    .fc {
        background: #fff;
        border-radius: 12px;
        padding: 16px;
    }
    .fc-toolbar-title {
        font-weight: 700;
        color: #0f172a;
    }
    .fc-button {
        background: #4c8a89 !important;
        border-color: #4c8a89 !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
    }
    .fc-button:hover {
        background: #3d6f6e !important;
        border-color: #3d6f6e !important;
    }
    .fc-button-active {
        background: #667eea !important;
        border-color: #667eea !important;
    }
    
    .automl-panel {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 28px;
        border-radius: 16px;
        margin-top: 20px;
        box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: visible;
    }
    .automl-panel::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        animation: pulse 4s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 0.5; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    .automl-panel h3 {
        margin: 0 0 12px;
        font-size: 20px;
        font-weight: 700;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .automl-panel h3::before {
        content: '🤖';
        font-size: 24px;
    }
    .automl-panel p {
        position: relative;
        z-index: 1;
        opacity: 0.95;
        line-height: 1.6;
    }
    .prediction-result {
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: 12px;
        margin-top: 16px;
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        border: 1px solid rgba(255,255,255,0.3);
        position: relative;
        z-index: 1;
    }
    .prediction-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 12px 0;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    .prediction-item:last-child {
        border-bottom: none;
    }
    .prediction-item strong {
        font-weight: 600;
        font-size: 14px;
    }
    .prediction-item span {
        font-weight: 700;
        font-size: 15px;
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 6px;
    }

    .crime-ai-card {
        background: rgba(255,255,255,0.95);
        border-radius: 8px;
        padding: 22px;
        margin-top: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .crime-ai-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }
    .crime-ai-status {
        color: #64748b;
        font-size: 13px;
        line-height: 1.5;
    }
    .priority-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .priority-high {
        background: #fee2e2;
        color: #991b1b;
    }
    .priority-medium {
        background: #fef3c7;
        color: #92400e;
    }
    .priority-low {
        background: #dcfce7;
        color: #166534;
    }
    .recommendation-modal-tab {
        border: 0;
        background: #f1f5f9;
        color: #475569;
        padding: 10px 14px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
    }
    .recommendation-modal-tab.active {
        background: #667eea;
        color: #ffffff;
    }
    .recommendation-chart-wrap {
        min-height: 280px;
        max-height: 360px;
        position: relative;
    }
    .crime-ai-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-top: 14px;
    }
    .crime-ai-page-controls {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .crime-ai-page-btn {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        min-width: 34px;
        height: 34px;
        padding: 0 10px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13px;
    }
    .crime-ai-page-btn.active {
        background: #667eea;
        border-color: #667eea;
        color: #ffffff;
    }
    .crime-ai-page-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    @media (max-width: 768px) {
        .crime-ai-card {
            padding: 16px;
        }
        .recommendation-chart-wrap {
            min-height: 240px;
        }
    }
    
    .resource-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .resource-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        padding: 24px;
        border-radius: 16px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    .resource-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #4c8a89 0%, #667eea 100%);
    }
    .resource-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        border-color: #4c8a89;
    }
    .resource-card h4 {
        margin: 0 0 12px;
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .resource-card h4::before {
        content: '📊';
        font-size: 16px;
    }
    .resource-value {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        background: linear-gradient(135deg, #4c8a89 0%, #667eea 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    .status-text {
        font-size: 14px;
        margin-top: 12px;
        padding: 12px 16px;
        border-radius: 10px;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .status-text::before {
        content: '✓';
        font-weight: 700;
    }
    .status-text.success { 
        background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); 
        color: #166534; 
        border: 1px solid #86efac;
    }
    .status-text.error { 
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); 
        color: #991b1b; 
        border: 1px solid #fca5a5;
    }
    .status-text.error::before {
        content: '✗';
    }
    
    /* Table wrapper for horizontal scrolling */
    .table-wrapper {
        width: 100%;
        overflow-x: auto;
        overflow-y: visible;
        margin-top: 20px;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
        position: relative;
        padding-right: 0; /* Ensure no padding cuts off content */
    }
    
    /* Ensure table wrapper doesn't clip content */
    .table-wrapper::after {
        content: '';
        display: block;
        width: 1px;
        height: 1px;
        clear: both;
    }
    
    /* Add padding to the right of the wrapper to ensure Actions column is fully visible */
    .table-wrapper::before {
        content: '';
        display: block;
        width: 0;
        height: 0;
    }
    
    .table-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    
    .table-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
        font-size: 14px;
        table-layout: auto;
    }
    .data-table thead {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    .data-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #0f172a;
        font-size: 13px;
        white-space: nowrap;
    }
    .data-table th,
    .data-table td {
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .data-table tbody tr:hover td:nth-child(12) {
        background: #f8fafc;
    }
    
    .data-table tbody tr:last-child td:nth-child(12) {
        border-bottom: none;
    }
    
        background: #f8fafc;
        transform: scale(1.01);
    }
    .data-table tbody tr:last-child td {
        border-bottom: none;
    }
    
    .btn-group {
        display: flex;
        gap: 12px;
        margin-top: 20px;
        flex-wrap: wrap;
    }
    .btn {
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .btn-primary {
        background: linear-gradient(135deg, #4c8a89 0%, #667eea 100%);
        color: white;
    }
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
    }
    .btn-secondary {
        background: #fff;
        color: #475569;
        border: 2px solid #e2e8f0;
    }
    .btn-secondary:hover {
        border-color: #4c8a89;
        color: #4c8a89;
        transform: translateY(-2px);
    }
    
    .analytics-accent {
        position: relative;
        padding-left: 16px;
    }
    .analytics-accent::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, #4c8a89 0%, #667eea 100%);
        border-radius: 2px;
    }
    
    /* ============================================
       NATIVE SELECT DROPDOWNS - Standard Fields Only
       Scoped to .standard-select class only
       ============================================ */
    select.standard-select {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        background-size: 12px;
        padding-right: 40px;
    }
    
    select.standard-select:focus {
        outline: none;
        border-color: #4c8a89;
        background-color: #fff;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234c8a89' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
    }
    
    select.standard-select:hover {
        border-color: #cbd5e1;
    }
    /* ============================================
       ENHANCED MULTI-SELECT - Assigned Staff & Materials Only
       Scoped to .multi-select-container class only
       ============================================ */
    .multi-select-container {
        position: relative;
        width: 100%;
    }
    
    /* Native select dropdown for multi-select */
    .multi-select-container .multi-select-dropdown {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        overflow-y: auto;
        overflow-x: hidden;
        position: relative;
        box-sizing: border-box;
        height: auto;
        min-height: 0;
    }
    
    .multi-select-container .multi-select-dropdown:focus {
        outline: none;
        border-color: #4c8a89;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
    }
    
    .multi-select-container .multi-select-dropdown:hover {
        border-color: #cbd5e1;
    }
    
    .multi-select-container .multi-select-dropdown option {
        padding: 10px 12px;
        cursor: pointer;
        line-height: 1.5;
    }
    
    .multi-select-container .multi-select-dropdown option:checked {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        color: #0f172a;
        font-weight: 600;
    }
    
    /* Tags container for multi-select (shows selected items) */
    .multi-select-container .multi-select-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
        width: 100%;
        min-height: 0;
    }
    
    /* Assigned Staff specific tags (4 per row) */
    .multi-select-container.assigned-staff-select .multi-select-tag {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #0c4a6e;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 calc(25% - 4.5px);
        max-width: calc(25% - 4.5px);
        min-width: 120px;
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        border: 1px solid #bae6fd;
        transition: all 0.2s;
    }
    
    /* Materials specific tags (2 per row) */
    .multi-select-container.materials-select .multi-select-tag {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #0c4a6e;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex: 0 0 calc(50% - 3px);
        max-width: calc(50% - 3px);
        min-width: 200px;
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        border: 1px solid #bae6fd;
        transition: all 0.2s;
    }
    
    .multi-select-container .multi-select-tag:hover {
        background: linear-gradient(135deg, #bae6fd 0%, #93c5fd 100%);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .multi-select-container .multi-select-tag-remove {
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
        line-height: 1;
        color: #1e40af;
        padding: 0 2px;
        border-radius: 3px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
    }
    
    .multi-select-container .multi-select-tag-remove:hover {
        background: rgba(30, 64, 175, 0.1);
        color: #1e3a8a;
    }
    
    /* Custom scrollbar for multi-select dropdown */
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar {
        width: 8px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    
    
    /* For full-width fields in form-grid, ensure dropdown doesn't overflow */
    .form-field.full-width .combobox-wrapper {
        position: relative;
        z-index: 1;
    }
    
    .form-field.full-width .combobox-options {
        max-width: 100%;
        box-sizing: border-box;
        /* Ensure dropdown is contained within the form-field */
        left: 0;
        right: 0;
    }
    
    /* Ensure campaign-main container doesn't clip dropdowns */
    .campaign-main {
        overflow: visible;
        position: relative;
    }
    
    /* Ensure campaign-layout doesn't clip dropdowns */
    .campaign-layout {
        overflow: visible;
        position: relative;
    }
    
    /* Ensure campaign-page doesn't clip dropdowns */
    .campaign-page {
        overflow: visible;
        position: relative;
    }

    
    /* Responsive table adjustments */
    @media (max-width: 1400px) {
        .data-table {
            min-width: 1200px; /* Ensure Actions column is visible */
        }
        
        .data-table th,
        .data-table td {
            padding: 12px 8px;
            font-size: 12px;
        }
        
        .data-table th:nth-child(7),
        .data-table th:nth-child(8),
        .data-table th:nth-child(9),
        .data-table td:nth-child(7),
        .data-table td:nth-child(8),
        .data-table td:nth-child(9) {
            font-size: 11px;
        }
    }
    
    @media (max-width: 768px) {
        .campaign-page {
            padding: 100px 16px 0;
        }
        .campaign-layout {
            display: block;
        }
        .form-grid {
            grid-template-columns: 1fr;
        }
        .resource-grid {
            grid-template-columns: 1fr;
        }
        .tabs {
            flex-wrap: wrap;
        }
        
        /* Actions column on mobile - ensure buttons wrap */
        .data-table th:nth-child(12),
        .data-table td:nth-child(12) {
            min-width: 160px;
            width: 180px;
            max-width: 280px; /* Allow wider on mobile for wrapped buttons */
            padding: 12px;
            overflow: visible;
        }
        
        /* Actions column button container on mobile */
        .data-table td:nth-child(12) {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: flex-start;
        }
        .tab {
            flex: 1;
            min-width: 120px;
        }
        
        /* Mobile: Ensure table wrapper is scrollable */
        .table-wrapper {
            margin-left: -16px;
            margin-right: -16px;
            padding: 0 16px;
        }
        
        .data-table {
            min-width: 1200px; /* Increased for mobile to ensure Actions column is visible */
        }
        
        .data-table th:nth-child(12),
        .data-table td:nth-child(12) {
            min-width: 160px;
            width: 180px;
            padding-right: 12px;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px 6px;
            font-size: 11px;
        }
    }
    
    /* Toast Notification Styles */
    .toast-container {
        position: fixed;
        top: 90px;
        right: 20px;
        z-index: 10000;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .toast {
        background: #10b981;
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 300px;
        max-width: 500px;
        animation: slideIn 0.3s ease-out;
        font-size: 14px;
        font-weight: 500;
    }
    
    .toast.error {
        background: #ef4444;
    }
    
    .toast.warning {
        background: #f59e0b;
    }
    
    .toast.info {
        background: #3b82f6;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .toast.hiding {
        animation: slideOut 0.3s ease-in forwards;
    }
</style>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<main class="campaign-page">
    <header>
        <h1 class="page-title">Campaign Planning & Management</h1>
        <p class="page-subtitle">Plan, schedule, and track campaigns with timeline visualization, calendar views, and AI-powered optimization.</p>
    </header>

    <div class="campaign-layout">
        <!-- Campaign features are now in the main sidebar as nested submenu -->
        <div class="campaign-main">

    <?php
    // RBAC: $isViewer is already set by block_viewer_access.php (included at top)
    // If Viewer tries to access planning form directly, redirect to list section
    if ($isViewer && isset($_GET['section']) && $_GET['section'] === 'planning-section') {
        header('Location: ' . $publicPath . '/campaigns.php#list-section');
        exit;
    }
    
    // RBAC: For Viewer, ensure we're on list section (read-only view)
    if ($isViewer && !isset($_GET['section'])) {
        // Auto-scroll to list section on page load
        echo '<script>window.location.hash = "list-section";</script>';
    }
    
    // Viewers can view approved campaigns in the list section (read-only)
    // Forms are hidden via PHP conditionals below
    ?>

    <!-- Plan New Campaign Modal -->
    <?php if (!$isViewer): ?>
    <div id="planCampaignModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; overflow-y: auto; padding: 20px;">
        <div class="modal-content" style="background: white; max-width: 900px; margin: 20px auto; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); position: relative;">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 16px 16px 0 0;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-bullhorn" style="color: #4c8a89;"></i>
                    Plan New Campaign
                </h2>
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary" onclick="showCampaignHowItWorks()" style="padding: 8px 14px; font-size: 12px;">
                        <i class="fas fa-info-circle"></i> How It Works
                    </button>
                    <button type="button" onclick="closePlanCampaignModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 4px 8px; line-height: 1;" title="Close">
                        &times;
                    </button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
                <!-- Integration Context -->
                <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 6px; padding: 14px; margin-bottom: 24px; font-size: 12px; color: #0c4a6e; line-height: 1.6;">
                    <div style="display: flex; align-items: flex-start; gap: 10px;">
                        <i class="fas fa-link" style="color: #0ea5e9; font-size: 16px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: 4px;">System Integration:</strong>
                            <p style="margin: 0;">This form integrates with multiple modules: <strong>Segments module</strong> for audience targeting, <strong>Content module</strong> for material selection, <strong>Events module</strong> for conflict checking, and <strong>Surveys module</strong> for engagement data used in AI recommendations.</p>
                        </div>
                    </div>
                </div>
                
                <form id="planningForm">
            <div class="form-grid">
                <div class="form-field">
                    <label for="title">Campaign Title *</label>
                    <input type="text" class="standard-input" id="title" placeholder="Enter campaign title..." required style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px;">
                </div>
                <div class="form-field">
                    <label for="category">Category *</label>
                    <select class="standard-select" id="category" required>
                        <option value="">Select category...</option>
                        <option value="fire">Fire</option>
                        <option value="flood">Flood</option>
                        <option value="earthquake">Earthquake</option>
                        <option value="health">Health</option>
                        <option value="road safety">Road Safety</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="geographic_scope">Geographic Scope / Barangay</label>
                    <select class="standard-select" id="geographic_scope">
                        <option value="">Select barangay...</option>
                    </select>
                </div>
                <!-- Status field is hidden - role-based workflow enforced in backend -->
                <!-- Staff: Creates as Draft (automatic) -->
                <!-- Secretary: Can forward Draft → Pending via button in campaign details -->
                <!-- Kagawad: Can review but cannot change status -->
                <!-- Captain: Can approve Pending → Approved via button in campaign details -->
                <input type="hidden" id="status" value="draft">
                
                <div class="form-field">
                    <label for="start_datetime">Start Date & Time *</label>
                    <input id="start_datetime" type="datetime-local">
                    <input id="start_date" type="hidden">
                    <input id="start_time" type="hidden">
                </div>
                <div class="form-field">
                    <label for="end_datetime">End Date & Time *</label>
                    <input id="end_datetime" type="datetime-local">
                    <input id="end_date" type="hidden">
                    <input id="end_time" type="hidden">
                </div>
                <div class="form-field" id="final_schedule_field" style="display: none;">
                    <label for="final_schedule_display" style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                        <i class="fas fa-calendar-check" style="color: #10b981;"></i>
                        Final Schedule (Generated via AI Optimization)
                    </label>
                    <div id="final_schedule_display" style="background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px; padding: 14px 16px; color: #065f46; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock" style="color: #10b981;"></i>
                        <span id="final_schedule_value">-</span>
                    </div>
                    <small style="display: block; margin-top: 6px; color: #64748b; font-size: 12px; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        This schedule was generated through the AI-Powered Deployment Optimization workflow.
                    </small>
                </div>
                <div class="form-field">
                    <label for="location">Location</label>
                    <select class="standard-select" id="location">
                        <option value="">Select location...</option>
                        <option value="Barangay Hall">Barangay Hall</option>
                        <option value="Covered Court">Covered Court</option>
                        <option value="Barangay Gymnasium">Barangay Gymnasium</option>
                        <option value="Elementary School Grounds">Elementary School Grounds</option>
                        <option value="High School Auditorium">High School Auditorium</option>
                        <option value="Multi-purpose Hall">Multi-purpose Hall</option>
                        <option value="Community Center">Community Center</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="budget" style="display: flex; align-items: center; gap: 8px;">
                        Budget (PHP)
                        <button type="button" id="toggleBudgetBtn" onclick="toggleBudgetVisibility()" style="background: #e2e8f0; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 12px;" title="Show/Hide Budget">
                            <i class="fas fa-eye-slash" id="budgetEyeIcon"></i>
                        </button>
                    </label>
                    <input id="budget" type="number" step="0.01" placeholder="50000.00" style="display: none;">
                    <div id="budgetHiddenPlaceholder" style="padding: 12px 16px; background: #f1f5f9; border: 2px solid #e2e8f0; border-radius: 12px; color: #64748b; font-size: 14px;">••••••</div>
                </div>
                <div class="form-field">
                    <label for="staff_count">Staff Count</label>
                    <input id="staff_count" type="number" placeholder="5">
                </div>
                <div class="form-field">
                    <label for="barangay_zones">Barangay Target Zones</label>
                    <select class="standard-select" id="barangay_zones">
                        <option value="">Select barangay zone...</option>
                    </select>
                </div>
                <div class="form-field full-width">
                    <label for="objectives">Objectives</label>
                    <textarea id="objectives" rows="3" placeholder="Primary objectives and goals for this campaign..."></textarea>
                </div>
                <div class="form-field full-width">
                    <label for="description">Description</label>
                    <textarea id="description" rows="3" placeholder="Detailed description of the campaign..."></textarea>
                </div>
                <div class="form-field full-width" style="margin-bottom: 24px;">
                    <label for="assigned_staff" style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px; font-weight: 600;">
                        <i class="fas fa-users" style="color: #667eea;"></i>
                        Assigned Staff
                    </label>
                    <div class="multi-select-container assigned-staff-select">
                        <div class="multi-select-tags" id="assigned_staff_tags"></div>
                        <select class="multi-select-dropdown" id="assigned_staff" name="assigned_staff[]" multiple size="3">
                        </select>
                    </div>
                    <small style="color: #94a3b8; font-size: 12px; margin-top: 8px; display: block; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        Select multiple staff members. Selected items will appear as tags above.
                    </small>
                </div>
            </div>
            
            <div class="btn-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <button type="submit" class="btn btn-primary">Create Campaign</button>
                <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
                <button type="button" class="btn btn-secondary" onclick="closePlanCampaignModal()">Cancel</button>
            </div>
            <div id="createStatus" class="status-text" style="display:none;"></div>
        </form>
            </div><!-- End Modal Body -->
        </div><!-- End Modal Content -->
    </div><!-- End Modal Overlay -->
    <?php endif; // End RBAC: Hide planning modal for Viewer ?>

    <!-- Add Staff Modal -->
    <?php if (!$isViewer): ?>
    <div id="staffModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10001; overflow-y: auto; padding: 20px;">
        <div class="modal-content" style="background: white; max-width: 550px; margin: 20px auto; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); position: relative;">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 16px 16px 0 0;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-user-plus" style="color: #4c8a89;"></i>
                    Add Staff
                </h2>
                <button type="button" onclick="closeAddStaffModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 4px 8px; line-height: 1;" title="Close">
                    &times;
                </button>
            </div>
            
            <!-- Modal Body -->
            <div style="padding: 24px;">
                <!-- Staff Name -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Staff Name *</label>
                    <input type="text" id="staffName" placeholder="e.g., Juan Dela Cruz" style="width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                </div>
                
                <!-- Position Mode Toggle -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #0f172a;">
                        <i class="fas fa-list" style="margin-right: 6px;"></i> Position Mode
                    </label>
                    <div style="display: flex; gap: 12px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 2px solid #4c8a89; border-radius: 8px; background: #f0fdfa;">
                            <input type="radio" name="staffPositionMode" value="existing" checked onchange="toggleStaffPositionMode('existing')">
                            <span style="font-weight: 500;">Select Existing</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 8px 16px; border: 2px solid #e2e8f0; border-radius: 8px;">
                            <input type="radio" name="staffPositionMode" value="new" onchange="toggleStaffPositionMode('new')">
                            <span style="font-weight: 500;">Add New</span>
                        </label>
                    </div>
                </div>
                
                <!-- Existing Position Dropdown -->
                <div id="existingStaffPositionGroup" style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Position / Role *</label>
                    <select id="staffPositionSelect" required style="width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select Position...</option>
                    </select>
                </div>
                
                <!-- New Position Input -->
                <div id="newStaffPositionGroup" style="margin-bottom: 20px; display: none;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Staff Position *</label>
                    <input type="text" id="newStaffPosition" placeholder="e.g., Barangay Captain" style="width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #64748b;">Enter a new position/role for this staff member.</p>
                </div>
                
                <!-- Quantity -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Quantity *</label>
                    <input type="number" id="staffQty" min="1" value="1" style="width: 100%; max-width: 120px; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #64748b;">Number of staff with this position/role.</p>
                </div>
                
                <div id="staffStatus" style="margin-bottom: 16px; display: none;"></div>
            </div>
            
            <!-- Modal Footer -->
            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeAddStaffModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="saveNewStaff()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Add Staff
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Add Budget Line Items Modal -->
    <?php if (!$isViewer): ?>
    <div id="budgetModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 10000; overflow-y: auto; padding: 20px;">
        <div class="modal-content" style="background: white; max-width: 950px; margin: 20px auto; border-radius: 16px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); position: relative;">
            <!-- Modal Header -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius: 16px 16px 0 0;">
                <h2 style="margin: 0; font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-coins" style="color: #4c8a89;"></i>
                    Add Budget Line Items
                </h2>
                <button type="button" onclick="closeBudgetModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 4px 8px; line-height: 1;" title="Close">
                    &times;
                </button>
            </div>
            
            <!-- Modal Body -->
            <div style="padding: 24px; max-height: calc(100vh - 200px); overflow-y: auto;">
                <!-- Campaign Selection -->
                <div style="margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block;">Campaign *</label>
                    <select id="budget_campaign_id" required style="width: 100%; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select Campaign...</option>
                    </select>
                </div>
                
                <!-- Line Items Table -->
                <div style="overflow-x: auto; margin-bottom: 20px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #e2e8f0;">
                                <th style="padding: 10px; text-align: left; font-weight: 600;">Item Name *</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600; width: 120px;">Type *</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600; width: 80px;">Qty *</th>
                                <th style="padding: 10px; text-align: left; font-weight: 600; width: 120px;">Unit Cost (₱) *</th>
                                <th style="padding: 10px; text-align: center; font-weight: 600; width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="budgetItemsContainer">
                            <tr class="budget-item-row" data-row="0">
                                <td style="padding: 8px;"><input type="text" class="budget-item-name" placeholder="e.g., Tarpaulin" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                                <td style="padding: 8px;">
                                    <select class="budget-item-type" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                                        <option value="consumable">Consumable</option>
                                        <option value="material">Material</option>
                                    </select>
                                </td>
                                <td style="padding: 8px;"><input type="number" class="budget-item-qty" min="1" value="1" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                                <td style="padding: 8px;"><input type="number" class="budget-item-cost" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
                                <td style="padding: 8px; text-align: center;">
                                    <button type="button" onclick="removeBudgetRow(this)" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; background: #ef4444; color: white; border: none;" title="Remove row">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <button type="button" onclick="addBudgetRow()" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; margin-bottom: 20px;">
                    <i class="fas fa-plus"></i> Add Another Item
                </button>
                
                <!-- Funding Source (Overall) -->
                <div style="background: #f0f9ff; border-left: 4px solid #0ea5e9; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                    <label style="font-weight: 600; margin-bottom: 8px; display: block; color: #0c4a6e;">
                        <i class="fas fa-wallet" style="margin-right: 6px;"></i> Funding Source (for all items) *
                    </label>
                    <select id="budget_funding_source" required style="width: 100%; max-width: 300px; padding: 12px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="government_allocated">Government Allocated</option>
                        <option value="reimbursable">Reimbursable</option>
                    </select>
                    <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">This funding source will be applied to all line items above.</p>
                </div>
                
                <div id="budgetStatus" style="margin-bottom: 16px; display: none;"></div>
            </div>
            
            <!-- Modal Footer -->
            <div style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="clearBudgetRows()" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-eraser"></i> Clear All
                </button>
                <button type="button" onclick="closeBudgetModal()" class="btn btn-secondary">Cancel</button>
                <button type="button" onclick="saveAllBudgetItems()" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-save"></i> Save All Items
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- AutoML Panel -->
    <?php if (!$isViewer): // RBAC: Hide AutoML section for Viewer (management tool) ?>
    <section class="card" id="automl-section">
        <div class="section-header">
            <h2 class="section-title analytics-accent">🤖 AI-Powered Deployment Optimization</h2>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 12px; color: #64748b; font-weight: 500; background: #f1f5f9; padding: 4px 10px; border-radius: 12px;">Core Innovation</span>
                <button type="button" id="automlRefreshBtn" onclick="if(typeof refreshAutoMLCampaigns==='function'){refreshAutoMLCampaigns();}else if(typeof window.refreshAutoMLCampaigns==='function'){window.refreshAutoMLCampaigns();}else{console.error('refreshAutoMLCampaigns not found'); alert('Refresh function not loaded');}" style="background: #667eea; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.2s; font-weight: 500;" title="Refresh campaign list" onmouseover="this.style.background='#5568d3'" onmouseout="this.style.background='#667eea'">
                    <i class="fas fa-sync-alt"></i>
                    <span>Refresh</span>
                </button>
            </div>
        </div>
        
        <!-- Core Innovation Highlight Card -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; margin-bottom: 24px; color: white; box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 12px 0; font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-brain"></i>
                        AI-Powered Scheduling Intelligence
                    </h3>
                    <p style="margin: 0; opacity: 0.95; line-height: 1.6; font-size: 14px;">
                        The scheduler analyzes historical campaign data, attendance trends, survey feedback, and event conflicts to recommend optimal deployment schedules. 
                        <button type="button" onclick="showAIHowItWorksModal()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 6px 12px; border-radius: 4px; font-size: 12px; cursor: pointer; margin-left: 8px; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            View How the AI Works
                        </button>
                    </p>
                </div>
            </div>
            
            <!-- Input Form Card -->
            <div style="background: rgba(255,255,255,0.95); border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
                    <div class="form-field" style="flex: 1; min-width: 250px; position: relative; overflow: visible;">
                        <label for="automl_campaign_id" style="color: #0f172a; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-bullhorn" style="margin-right: 6px; color: #667eea;"></i>
                            Select Campaign *
                        </label>
                        <select id="automl_campaign_id" style="background: white; border: 2px solid #e2e8f0; color: #0f172a; width: 100%; padding: 10px 12px; padding-right: 32px; border-radius: 6px; font-size: 14px; cursor: pointer; appearance: auto; -webkit-appearance: menulist; -moz-appearance: menulist; height: 42px; box-sizing: border-box; position: relative; z-index: 1000; overflow: visible; transition: border-color 0.2s;" onfocus="this.style.borderColor='#667eea'; checkDropdownStatus(); console.log('Dropdown focused, options count:', this.options.length);" onblur="this.style.borderColor='#e2e8f0';" onchange="updateDropdownStatus(); validateAutoMLForm(); console.log('Dropdown changed to:', this.value);" onclick="console.log('Dropdown clicked, options count:', this.options.length); if(this.options.length <= 1) { console.warn('Dropdown has no options! Attempting to populate...'); populateAutoMLDropdown(); }" onmousedown="console.log('Dropdown mousedown, options:', Array.from(this.options).map(o => o.value + ':' + o.textContent));">
                            <option value="">-- Select a campaign --</option>
                        </select>
                        <p id="automl_dropdown_status" style="color: #64748b; font-size: 12px; margin: 6px 0 0 0; min-height: 16px;">Loading campaigns...</p>
                        <p style="color: #94a3b8; font-size: 11px; margin: 4px 0 0 0; line-height: 1.4;">
                            💡 Campaigns are pulled from the <strong>All Campaigns</strong> section below. Conflict checking will compare with the <strong>Events module</strong>.
                        </p>
                    </div>
                    <div class="form-field" style="flex: 1; min-width: 200px;">
                        <label for="automl_audience_segment" style="color: #0f172a; display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px;">
                            <i class="fas fa-users" style="margin-right: 6px; color: #667eea;"></i>
                            Target Segment (Optional)
                        </label>
                        <input id="automl_audience_segment" type="number" placeholder="Enter segment ID" style="background: white; border: 2px solid #e2e8f0; color: #0f172a; width: 100%; padding: 10px 12px; border-radius: 6px; font-size: 14px; height: 42px; box-sizing: border-box; transition: border-color 0.2s;" onfocus="this.style.borderColor='#667eea';" onblur="this.style.borderColor='#e2e8f0';" onchange="validateAutoMLForm();" oninput="validateAutoMLForm();">
                        <p style="color: #94a3b8; font-size: 11px; margin: 4px 0 0 0; line-height: 1.4;">
                            💡 Segments are pulled from the <strong>Segments module</strong>. Leave empty for general audience analysis.
                        </p>
                    </div>
                    <div style="display: flex; flex-direction: column; justify-content: flex-end; min-width: 160px;">
                        <button type="button" id="getPredictionBtn" class="btn btn-primary" onclick="if(typeof handleGetPredictionClick==='function'){handleGetPredictionClick(event);}else if(typeof window.handleGetPredictionClick==='function'){window.handleGetPredictionClick(event);}else{console.error('handleGetPredictionClick not found'); alert('Prediction function not loaded. Please refresh the page.');}" style="background: white; color: #667eea; border: 2px solid white; font-weight: 700; padding: 12px 24px; height: 42px; box-sizing: border-box; display: flex; align-items: center; justify-content: center; white-space: nowrap; transition: all 0.2s; cursor: pointer; border-radius: 6px; font-size: 14px;" onmouseover="this.style.background='#f8fafc'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.1)'" onmouseout="this.style.background='white'; this.style.transform='translateY(0)'; this.style.boxShadow='none'">
                            <i class="fas fa-magic" style="margin-right: 8px;"></i>
                            Get AI Prediction
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Empty State (when no campaign selected) -->
            <div id="automlEmptyState" style="background: rgba(255,255,255,0.95); border-radius: 8px; padding: 40px 24px; text-align: center; color: #64748b; display: block;">
                <div style="font-size: 48px; margin-bottom: 16px; opacity: 0.6;">
                    <i class="fas fa-robot"></i>
                </div>
                <h4 style="margin: 0 0 8px 0; color: #0f172a; font-size: 18px; font-weight: 600;">Ready for AI Analysis</h4>
                <p style="margin: 0; font-size: 14px; line-height: 1.6; max-width: 500px; margin-left: auto; margin-right: auto;">
                    Select a campaign above and click <strong>"Get AI Prediction"</strong> to receive an AI-powered recommendation for the optimal deployment schedule.
                </p>
            </div>
            
            <!-- AI Recommendation Result Card -->
            <div id="automlResult" class="prediction-result" style="display:none; background: rgba(255,255,255,0.95); border-radius: 8px; padding: 24px; margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0;">
                    <div style="font-size: 32px;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    </div>
                    <div style="flex: 1;">
                        <h4 style="margin: 0 0 4px 0; color: #0f172a; font-size: 18px; font-weight: 700;">AI Recommendation Generated</h4>
                        <p style="margin: 0; color: #64748b; font-size: 13px;">Review the suggested schedule below and choose an action.</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
                    <div style="background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px; padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-calendar-alt" style="color: #10b981; font-size: 18px;"></i>
                            <strong style="color: #065f46; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Suggested Date & Time</strong>
                        </div>
                        <div id="pred_datetime" style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">-</div>
                    </div>
                    
                    <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-chart-line" style="color: #3b82f6; font-size: 18px;"></i>
                            <strong style="color: #1e40af; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Confidence Score</strong>
                        </div>
                        <div id="pred_confidence" style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">-</div>
                        <p style="margin: 8px 0 0 0; color: #64748b; font-size: 11px; line-height: 1.4;">
                            Higher scores indicate stronger confidence in the recommendation based on historical data analysis.
                        </p>
                    </div>
                    
                    <div style="background: #faf5ff; border: 2px solid #a855f7; border-radius: 8px; padding: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <i class="fas fa-cog" style="color: #a855f7; font-size: 18px;"></i>
                            <strong style="color: #6b21a8; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Model Source</strong>
                        </div>
                        <div id="pred_source" style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">-</div>
                    </div>
                </div>
                
                <div style="background: #f8fafc; border-left: 4px solid #667eea; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-lightbulb" style="color: #667eea; font-size: 20px; margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px;">AI Recommendation:</strong>
                            <div id="pred_recommendation" style="color: #475569; font-size: 13px; line-height: 1.6;">Based on historical performance data</div>
                        </div>
                    </div>
                </div>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary" onclick="acceptAIRecommendation()" style="background: #10b981; color: white; border: none; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                        <i class="fas fa-check"></i>
                        Accept AI Recommendation
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="checkConflicts()" style="background: white; color: #667eea; border: 2px solid #667eea; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                        <i class="fas fa-search"></i>
                        Check Conflicts
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="overrideSchedule()" style="background: white; color: #64748b; border: 2px solid #e2e8f0; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">
                        <i class="fas fa-edit"></i>
                        Override Schedule
                    </button>
                </div>
            </div>

            <div class="crime-ai-card" id="aiRecommendationsCard">
                <div class="crime-ai-toolbar">
                    <div>
                        <h3 style="margin: 0 0 6px 0; color: #0f172a; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-robot" style="color: #667eea;"></i>
                            Smart AI Campaign Recommendations
                        </h3>
                        <p id="aiRecommendationsStatus" class="crime-ai-status" style="margin: 0;">
                            Loading unified analytics from crime and disaster reports...
                        </p>
                    </div>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <div style="position: relative;">
                            <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px;"></i>
                            <input type="text" id="aiRecSearchInput" placeholder="Search recommendations..." 
                                style="padding: 8px 12px 8px 32px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 13px; width: 200px; background: white; outline: none;"
                                onkeyup="filterAiRecommendations()" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="loadAiRecommendations(true)" style="display: inline-flex; align-items: center; gap: 6px; border: 2px solid #667eea; color: #667eea; background: white; font-weight: 700;">
                            <i class="fas fa-sync-alt"></i>
                            Refresh
                        </button>
                        <button type="button" id="aiRecBackfillBtn" class="btn btn-primary" onclick="backfillAiRecommendationPlans()" style="display: inline-flex; align-items: center; gap: 6px; background: #667eea; color: white; border: 2px solid #667eea; font-weight: 700;">
                            <i class="fas fa-magic"></i>
                            Generate Missing Plans
                        </button>
                    </div>
                </div>
                <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 8px;">
                    <table class="data-table" style="width: 100%; border-collapse: collapse; min-width: 860px;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; width: 50px;">#</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0;">Campaign Title</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; width: 90px;">Category</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0;">Main Trend</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; white-space: nowrap; width: 110px;">Reports</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; white-space: nowrap; width: 130px;">Priority</th>
                                <th style="padding: 12px 14px; text-align: left; font-weight: 700; color: #0f172a; border-bottom: 2px solid #e2e8f0; width: 80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="aiRecommendationsTable">
                            <tr>
                                <td colspan="7" style="padding: 28px; text-align: center; color: #64748b;">
                                    <i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>
                                    Loading unified recommendations...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div id="aiRecommendationsPagination" class="crime-ai-pagination" style="display: none;">
                    <div id="aiRecommendationsPaginationSummary" style="color: #64748b; font-size: 13px; font-weight: 600;"></div>
                    <div id="aiRecommendationsPaginationControls" class="crime-ai-page-controls"></div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; // End RBAC: Hide AutoML section for Viewer ?>

    <!-- Campaigns List - Moved to top -->
    <section class="card" id="list-section">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2 class="section-title analytics-accent">All Campaigns</h2>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                <?php if (!$isViewer): ?>
                <button class="btn btn-primary" onclick="openPlanCampaignModal()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Plan New Campaign
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary active" onclick="switchCampaignView('list')" id="campaignListViewBtn" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-list"></i> List
                </button>
                <button class="btn btn-secondary" onclick="switchCampaignView('calendar')" id="campaignCalendarViewBtn" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-calendar-alt"></i> Calendar
                </button>
                <button class="btn btn-secondary" onclick="switchCampaignView('resources')" id="campaignResourcesViewBtn" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-chart-pie"></i> Resources
                </button>
                <button class="btn btn-secondary" onclick="showArchivedCampaigns()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
                <button class="btn btn-secondary" onclick="loadCampaignsWithFilters()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <!-- List View Content -->
        <div id="campaignListView" style="display: block;">
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
            Complete list of all campaigns. AI recommendations shown in the <strong>"AI Recommended"</strong> column are generated using engagement data from the <strong>Surveys module</strong> and historical performance metrics.
        </p>
        
        <!-- Search and Filter Bar -->
        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
            <div style="flex: 1; min-width: 250px;">
                <div style="position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" id="campaignSearchInput" placeholder="Search campaigns by title..." 
                        style="width: 100%; padding: 12px 16px 12px 42px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; transition: all 0.2s; background: white;"
                        onkeyup="filterCampaigns()" onfocus="this.style.borderColor='#4c8a89'; this.style.boxShadow='0 0 0 4px rgba(76, 138, 137, 0.1)';" 
                        onblur="this.style.borderColor='#e2e8f0'; this.style.boxShadow='none';">
                </div>
            </div>
            <div style="min-width: 160px;">
                <select id="campaignCategoryFilter" onchange="filterCampaigns()" 
                    style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">All Categories</option>
                    <option value="Fire">Fire</option>
                    <option value="Flood">Flood</option>
                    <option value="Earthquake">Earthquake</option>
                    <option value="Typhoon">Typhoon</option>
                    <option value="Health">Health</option>
                    <option value="Emergency">Emergency</option>
                </select>
            </div>
            <div style="min-width: 160px;">
                <select id="campaignStatusFilter" onchange="filterCampaigns()" 
                    style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="active">Active</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <button onclick="clearCampaignFilters()" class="btn btn-secondary" style="padding: 12px 20px;">
                <i class="fas fa-times"></i> Clear
            </button>
        </div>
        
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 250px; max-width: 250px;">Title</th>
                        <th style="width: 110px; max-width: 110px;">Category</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 180px; max-width: 180px;">Location</th>
                        <th style="width: 240px; min-width: 240px; max-width: 240px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="campaignTable">
                    <tr><td colspan="6" style="text-align:center; padding:24px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div id="campaignPagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 12px;">
            <button id="prevPageBtn" onclick="changeCampaignPage(-1)" class="btn btn-secondary" style="padding: 10px 16px;" disabled>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <span id="pageInfo" style="padding: 10px 20px; background: white; border-radius: 8px; border: 1px solid #e2e8f0; font-weight: 600; color: #475569;">Page 1 of 1</span>
            <button id="nextPageBtn" onclick="changeCampaignPage(1)" class="btn btn-secondary" style="padding: 10px 16px;" disabled>
                Next <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        </div><!-- End List View -->
        
        <!-- Calendar View Content -->
        <div id="campaignCalendarView" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <p style="margin: 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                    Calendar view of campaign schedules. Click on any campaign event to view details. Events from the <a href="events.php" style="color: #4c8a89; font-weight: 600;">Events module</a> are integrated to show potential conflicts.
                </p>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <select id="calendarStatusFilterInline" onchange="renderInlineCalendar()" style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px; background: white;">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="active">Active</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button class="btn btn-secondary" onclick="renderInlineCalendar()" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
            </div>
            <!-- Status Legend -->
            <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                <span style="font-weight: 600; color: #475569; font-size: 13px; display: flex; align-items: center; gap: 6px;"><i class="fas fa-info-circle"></i> Status Legend:</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);"></span> Draft</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);"></span> Pending</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #dbeafe 0%, #93c5fd 100%);"></span> Approved</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);"></span> Scheduled</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);"></span> Active/Ongoing</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);"></span> Completed</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #78716c 0%, #57534e 100%);"></span> Archived</span>
                <span style="display: flex; align-items: center; gap: 6px; font-size: 12px;"><span style="width: 14px; height: 14px; border-radius: 4px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);"></span> Cancelled</span>
            </div>
            <div id="calendarInline" style="min-height: 500px;"></div>
        </div><!-- End Calendar View -->
        
        <!-- Resources View Content -->
        <div id="campaignResourcesView" style="display: none;">
            <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                Overview of allocated resources across all campaigns. Budget and staff assignments are tracked and integrated with campaign planning.
            </p>
            <div class="resource-grid" id="resourceGridInline" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 20px; border-radius: 12px; border: 1px solid #bbf7d0;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #166534; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-coins" style="color: #16a34a;"></i> TOTAL BUDGET
                        <button onclick="toggleBudgetVisibilityInline()" style="background: none; border: none; cursor: pointer; color: #64748b; padding: 2px;"><i class="fas fa-eye" id="budgetToggleIconInline"></i></button>
                    </h4>
                    <div id="totalBudgetInline" style="display: none; font-size: 28px; font-weight: 700; color: #166534;">₱0.00</div>
                    <div id="totalBudgetHiddenInline" style="font-size: 28px; font-weight: 700; color: #64748b;">••••••</div>
                    <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="budgetBreakdownInline">All campaigns</div>
                </div>
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 20px; border-radius: 12px; border: 1px solid #bfdbfe;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #1e40af;"><i class="fas fa-users" style="color: #3b82f6;"></i> TOTAL STAFF</h4>
                    <div id="totalStaffInline" style="font-size: 28px; font-weight: 700; color: #1e40af;">0</div>
                    <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="staffBreakdownInline">0 campaigns assigned</div>
                </div>
                <div style="background: linear-gradient(135deg, #fdf4ff 0%, #fae8ff 100%); padding: 20px; border-radius: 12px; border: 1px solid #f5d0fe;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #86198f;"><i class="fas fa-rocket" style="color: #d946ef;"></i> ACTIVE CAMPAIGNS</h4>
                    <div id="activeCampaignsInline" style="font-size: 28px; font-weight: 700; color: #86198f;">0</div>
                    <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="activeCampaignsBreakdownInline">0 inactive</div>
                </div>
                <div style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); padding: 20px; border-radius: 12px; border: 1px solid #99f6e4;">
                    <h4 style="margin: 0 0 8px 0; font-size: 14px; color: #115e59;"><i class="fas fa-box-open" style="color: #14b8a6;"></i> MATERIALS ALLOCATED</h4>
                    <div id="materialsUsedInline" style="font-size: 16px; font-weight: 600; color: #115e59; line-height: 1.4;">Loading...</div>
                    <div style="margin-top: 8px; font-size: 12px; color: #64748b;">Inventory summary</div>
                </div>
            </div>
        </div><!-- End Resources View -->
    </section>

    <!-- Financial & Budgeting Section -->
    <?php if (!$isViewer): ?>
    <section class="card" id="budgeting-section">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2 class="section-title analytics-accent">💰 Financial & Budgeting</h2>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="openBudgetModal()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Add Budget Line Items
                </button>
                <button class="btn btn-secondary" onclick="loadBudgetData()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
            Manage budget line items for campaigns. Track consumables and materials with funding source allocation. Budget data is connected to <strong>Resource Allocation</strong> for Materials Allocated.
        </p>
        
        <!-- Budget Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 12px; padding: 20px; text-align: center;">
                <div style="font-size: 12px; color: #166534; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    Total Budget
                    <button onclick="toggleBudgetCardVisibility('total')" style="background: none; border: none; cursor: pointer; color: #166534; padding: 2px;" title="Show/Hide amount"><i class="fas fa-eye-slash" id="budgetTotalIcon"></i></button>
                </div>
                <div id="budgetTotalDisplay" style="font-size: 24px; font-weight: 700; color: #166534; display: none;">₱0.00</div>
                <div id="budgetTotalHidden" style="font-size: 24px; font-weight: 700; color: #166534;">••••••</div>
            </div>
            <div style="background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); border-radius: 12px; padding: 20px; text-align: center;">
                <div style="font-size: 12px; color: #1e40af; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    Government Allocated
                    <button onclick="toggleBudgetCardVisibility('government')" style="background: none; border: none; cursor: pointer; color: #1e40af; padding: 2px;" title="Show/Hide amount"><i class="fas fa-eye-slash" id="budgetGovIcon"></i></button>
                </div>
                <div id="budgetGovDisplay" style="font-size: 24px; font-weight: 700; color: #1e40af; display: none;">₱0.00</div>
                <div id="budgetGovHidden" style="font-size: 24px; font-weight: 700; color: #1e40af;">••••••</div>
            </div>
            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; padding: 20px; text-align: center;">
                <div style="font-size: 12px; color: #92400e; font-weight: 600; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    Reimbursable
                    <button onclick="toggleBudgetCardVisibility('reimbursable')" style="background: none; border: none; cursor: pointer; color: #92400e; padding: 2px;" title="Show/Hide amount"><i class="fas fa-eye-slash" id="budgetReimbIcon"></i></button>
                </div>
                <div id="budgetReimbDisplay" style="font-size: 24px; font-weight: 700; color: #92400e; display: none;">₱0.00</div>
                <div id="budgetReimbHidden" style="font-size: 24px; font-weight: 700; color: #92400e;">••••••</div>
            </div>
            <div style="background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); border-radius: 12px; padding: 20px; text-align: center;">
                <div style="font-size: 12px; color: #7c3aed; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">Line Items</div>
                <div id="budgetItemsDisplay" style="font-size: 24px; font-weight: 700; color: #7c3aed;">0</div>
            </div>
        </div>
        
        <!-- View Archived Budgets Button -->
        <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
            <button type="button" class="btn btn-secondary" onclick="toggleArchivedBudgets()" id="viewArchivedBudgetsBtn" style="padding: 8px 16px; font-size: 13px;">
                <i class="fas fa-archive"></i> View Archived
            </button>
        </div>
        
        <!-- Budget Table - Grouped by Campaign -->
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Items</th>
                        <th>Total Budget</th>
                        <th>Funding</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="budgetTable">
                    <tr><td colspan="6" style="text-align:center; padding:24px; color: #64748b;">No budget items yet. Add items using the form above.</td></tr>
                </tbody>
            </table>
        </div>
        
    </section>
    <?php endif; ?>

    <!-- Staff Section -->
    <?php if (!$isViewer): ?>
    <section class="card" id="staff-section">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2 class="section-title analytics-accent"><i class="fas fa-users" style="margin-right: 8px;"></i> Staff</h2>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button class="btn btn-primary" onclick="openAddStaffModal()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> Add Staff
                </button>
                <button class="btn btn-secondary" onclick="loadStaffTable()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
            Manage staff positions and personnel for campaign deployment. Add new positions or select from existing roles and set the quantity needed.
        </p>
        
        <!-- Staff Table -->
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Name</th>
                        <th>Position / Role</th>
                        <th style="width: 80px;">Qty</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="staffTable">
                    <tr><td colspan="5" style="text-align:center; padding:24px; color: #64748b;">Loading staff data...</td></tr>
                </tbody>
            </table>
        </div>
        <div id="staffPagination" class="pagination-controls" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 16px;"></div>
    </section>
    <?php endif; ?>

    <!-- Archived Budgets Modal - Outside all sections for proper overlay -->
    <div id="archivedBudgetsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.7); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 12px; max-width: 900px; width: 90%; max-height: 80vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="fas fa-archive"></i> Archived Budget Items</h3>
                <button onclick="closeArchivedBudgetsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
            </div>
            <div id="archivedBudgetsList"></div>
        </div>
    </div>

    <!-- Campaign Calendar and Resource Allocation sections removed - now integrated into All Campaigns switch view -->

    <!-- Schedule Management -->
    <section class="card" id="schedule-management-section" style="display:none;">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Schedule Management</h2>
            <button class="btn btn-secondary" onclick="loadSchedules()">🔄 Refresh</button>
        </div>
        <div class="form-field" style="max-width: 300px; margin-bottom: 16px;">
            <label for="schedule_campaign_id">Campaign ID</label>
            <input id="schedule_campaign_id" type="number" placeholder="Enter campaign ID" onchange="loadSchedules()">
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Scheduled At</th>
                    <th>Channel</th>
                    <th>Status</th>
                    <th>Last Posting Attempt</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="scheduleTable">
                <tr><td colspan="7" style="text-align:center; padding:24px;">Enter a Campaign ID to view schedules</td></tr>
            </tbody>
        </table>
    </section>

    <!-- Target Segments section removed -->
    <section class="card" id="segments-section" style="display: none;">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Target Segments</h2>
            <button class="btn btn-secondary" onclick="loadSegments()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        
        <!-- Section Description -->
        <div style="background: #f8fafc; border-left: 4px solid #667eea; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas fa-info-circle" style="color: #667eea; font-size: 20px; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px;">What this does:</strong>
                    <p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.6;">
                        Assign audience segments to your campaign. Segments are pulled from the <strong>Segments module</strong> and define groups of residents (e.g., senior citizens, students, high-risk areas) for targeted campaign delivery.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="form-grid" style="margin-bottom: 20px;">
            <div class="form-field">
                <label for="segment_ids" style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; font-weight: 600;">
                    <i class="fas fa-users" style="color: #667eea;"></i>
                    Segment IDs *
                </label>
                <input id="segment_ids" type="text" placeholder="Enter segment IDs separated by commas (e.g., 1, 2, 5)" style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; transition: border-color 0.2s;" onfocus="this.style.borderColor='#667eea';" onblur="this.style.borderColor='#e2e8f0';">
                <div style="margin-top: 8px;">
                    <button type="button" onclick="toggleSegmentHelp()" style="display: flex; align-items: center; gap: 6px; padding: 8px 12px; background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; color: #475569; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
                        <i class="fas fa-chevron-down" id="segmentHelpIcon" style="transition: transform 0.2s;"></i>
                        <span>💡 How to use</span>
                    </button>
                    <div id="segmentHelpContainer" style="display: none; margin-top: 8px; padding: 12px; background: #f1f5f9; border-radius: 6px; font-size: 12px; color: #475569; line-height: 1.6;">
                        <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                            <li>Enter segment IDs separated by commas (e.g., <code style="background: white; padding: 2px 6px; border-radius: 3px;">1, 2, 5</code>)</li>
                            <li>To find segment IDs, go to the <strong>Segments module</strong> and check the ID column</li>
                            <li>You can assign multiple segments to target different audience groups</li>
                            <li>Segments use data from the <strong>Segments module</strong> which may include attendance records, incident reports, and demographic data</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="btn-group" style="margin-bottom: 16px;">
            <button class="btn btn-primary" onclick="saveSegments()" style="display: flex; align-items: center; gap: 8px; padding: 12px 24px; font-weight: 600;">
                <i class="fas fa-save"></i>
                Save Segments
            </button>
        </div>
        
        <div id="segmentStatus" class="status-text" style="display:none; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px;"></div>
        
        <div style="margin-top: 24px;">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-list" style="color: #667eea;"></i>
                Assigned Segments
            </h3>
            <table class="data-table">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0;">ID</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0;">Segment Name</th>
                        <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0;">Segmentation Criteria</th>
                    </tr>
                </thead>
                <tbody id="segmentTable">
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 40px 24px; color: #64748b;">
                            <div style="font-size: 32px; margin-bottom: 12px; opacity: 0.5;">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div style="font-weight: 600; color: #0f172a; margin-bottom: 6px; font-size: 15px;">No segments assigned yet</div>
                            <div style="font-size: 13px; line-height: 1.6; max-width: 400px; margin: 0 auto;">
                                Enter segment IDs above and click <strong>"Save Segments"</strong> to assign audience segments to this campaign. Segments are pulled from the <strong>Segments module</strong>.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Linked Content section removed -->
    <section class="card" id="content-section" style="display: none;">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Linked Content</h2>
            <button class="btn btn-secondary" onclick="loadCampaignContent()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        
        <!-- Section Description -->
        <div style="background: #f8fafc; border-left: 4px solid #667eea; border-radius: 6px; padding: 16px; margin-bottom: 24px;">
            <div style="display: flex; align-items: flex-start; gap: 12px;">
                <i class="fas fa-info-circle" style="color: #667eea; font-size: 20px; margin-top: 2px;"></i>
                <div style="flex: 1;">
                    <strong style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px;">What this shows:</strong>
                    <p style="margin: 0; color: #475569; font-size: 13px; line-height: 1.6;">
                        View all content materials (posters, videos, guidelines, infographics) that are linked to a specific campaign. Content is pulled from the <strong>Content Repository</strong> and must be approved before it can be attached to campaigns.
                    </p>
                </div>
            </div>
        </div>
        
        <div class="form-field" style="max-width: 400px; margin-bottom: 24px;">
            <label for="content_campaign_id" style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px; font-weight: 600;">
                <i class="fas fa-bullhorn" style="color: #667eea;"></i>
                Campaign ID *
            </label>
            <input id="content_campaign_id" type="number" placeholder="Enter campaign ID to view linked content" style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; transition: border-color 0.2s;" onfocus="this.style.borderColor='#667eea';" onblur="this.style.borderColor='#e2e8f0';" onchange="loadCampaignContent()">
            <p style="margin: 8px 0 0 0; color: #94a3b8; font-size: 12px; line-height: 1.5;">
                💡 Find campaign IDs in the <strong>All Campaigns</strong> table above. Content linked to campaigns is managed through the <strong>Content module</strong>.
            </p>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0; white-space: nowrap;">ID</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0;">Content Title</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0; white-space: nowrap;">Content Type</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #0f172a; border-bottom: 2px solid #e2e8f0; white-space: nowrap;">Created At</th>
                    </tr>
                </thead>
                <tbody id="contentTable">
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 48px 24px; color: #64748b;">
                            <div style="font-size: 40px; margin-bottom: 16px; opacity: 0.5;">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div style="font-weight: 600; color: #0f172a; margin-bottom: 8px; font-size: 16px;">No content linked yet</div>
                            <div style="font-size: 13px; line-height: 1.6; max-width: 450px; margin: 0 auto;">
                                Enter a campaign ID above to view all content materials linked to that campaign. To link content to a campaign, use the <strong>Content module</strong> to attach approved materials.
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

        </div> <!-- /.campaign-main -->
    </div> <!-- /.campaign-layout -->
        </div> <!-- /.campaign-page -->

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js" onload="console.log('FullCalendar script loaded successfully'); if (typeof FullCalendar === 'undefined') { console.warn('FullCalendar global not found after load'); } else { console.log('FullCalendar global found:', typeof FullCalendar); }" onerror="console.error('FullCalendar script failed to load - check network/CDN');"></script>
<script>
// Get base path for API calls (path_helper already included in head)
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';
const aiRecommendationsUrl = basePath + '/public/api/ai_recommendations.php';
console.log('BASE PATH:', basePath);

let aiRecommendations = [];
let aiRecommendationsFiltered = [];
let aiRecommendationsCurrentPage = 1;
let aiRecommendationsChart = null;

const aiRecommendationsPageSize = 10;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getPriorityBadge(level, score) {
    const s = Number(score || 0);
    if (level === 'Critical' || s >= 70) {
        return { className: 'priority-critical', label: 'Critical', color: '#dc2626' };
    }
    if (level === 'High' || s >= 40) {
        return { className: 'priority-high', label: 'High', color: '#ea580c' };
    }
    if (level === 'Medium' || s >= 20) {
        return { className: 'priority-medium', label: 'Medium', color: '#ca8a04' };
    }
    return { className: 'priority-low', label: 'Low', color: '#16a34a' };
}

function getCategoryBadge(source) {
    if (source === 'disaster') {
        return { className: 'badge-disaster', label: 'Disaster', icon: 'fa-exclamation-triangle', color: '#dc2626' };
    }
    return { className: 'badge-crime', label: 'Crime', icon: 'fa-shield-alt', color: '#667eea' };
}

function setAiStatus(message, isError = false) {
    const status = document.getElementById('aiRecommendationsStatus');
    if (!status) return;
    status.textContent = message;
    status.style.color = isError ? '#b91c1c' : '#64748b';
}

function renderAiRecommendationsLoading() {
    const tbody = document.getElementById('aiRecommendationsTable');
    if (!tbody) return;
    updateAiRecommendationsPagination(0);
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="padding: 28px; text-align: center; color: #64748b;">
                <i class="fas fa-spinner fa-spin" style="margin-right: 8px;"></i>
                Loading unified recommendations...
            </td>
        </tr>
    `;
}

function renderAiRecommendationsEmpty(message) {
    const tbody = document.getElementById('aiRecommendationsTable');
    if (!tbody) return;
    updateAiRecommendationsPagination(0);
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="padding: 28px; text-align: center; color: #64748b;">
                <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                ${escapeHtml(message)}
            </td>
        </tr>
    `;
}

function filterAiRecommendations() {
    const query = (document.getElementById('aiRecSearchInput')?.value || '').toLowerCase().trim();
    if (!query) {
        aiRecommendationsFiltered = [...aiRecommendations];
    } else {
        aiRecommendationsFiltered = aiRecommendations.filter(r =>
            String(r.campaign_title || r.recommended_campaign_title || '').toLowerCase().includes(query) ||
            String(r.main_trend || r.incident_title || '').toLowerCase().includes(query) ||
            String(r.category || r.source || '').toLowerCase().includes(query)
        );
    }
    aiRecommendationsCurrentPage = 1;
    renderAiRecommendationsTable();
}

function updateAiRecommendationsPagination(totalItems) {
    const pagination = document.getElementById('aiRecommendationsPagination');
    const summary = document.getElementById('aiRecommendationsPaginationSummary');
    const controls = document.getElementById('aiRecommendationsPaginationControls');
    if (!pagination || !summary || !controls) return;

    controls.innerHTML = '';

    if (totalItems <= aiRecommendationsPageSize) {
        pagination.style.display = 'none';
        return;
    }

    const totalPages = Math.ceil(totalItems / aiRecommendationsPageSize);
    aiRecommendationsCurrentPage = Math.min(Math.max(aiRecommendationsCurrentPage, 1), totalPages);
    const startItem = (aiRecommendationsCurrentPage - 1) * aiRecommendationsPageSize + 1;
    const endItem = Math.min(startItem + aiRecommendationsPageSize - 1, totalItems);

    pagination.style.display = 'flex';
    summary.textContent = `Showing ${startItem}-${endItem} of ${totalItems.toLocaleString()} recommendations`;

    const createButton = (label, page, options = {}) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'crime-ai-page-btn';
        button.textContent = label;
        button.disabled = Boolean(options.disabled);
        if (options.active) button.classList.add('active');
        button.addEventListener('click', () => {
            if (!button.disabled) {
                setAiRecommendationsPage(page);
            }
        });
        controls.appendChild(button);
    };

    createButton('Prev', aiRecommendationsCurrentPage - 1, { disabled: aiRecommendationsCurrentPage === 1 });

    const visiblePages = [];
    const firstPage = Math.max(1, aiRecommendationsCurrentPage - 2);
    const lastPage = Math.min(totalPages, aiRecommendationsCurrentPage + 2);
    for (let page = firstPage; page <= lastPage; page++) {
        visiblePages.push(page);
    }
    if (!visiblePages.includes(1)) {
        visiblePages.unshift(1);
    }
    if (!visiblePages.includes(totalPages)) {
        visiblePages.push(totalPages);
    }

    let previousPage = 0;
    visiblePages.forEach(page => {
        if (previousPage && page - previousPage > 1) {
            const dots = document.createElement('span');
            dots.textContent = '...';
            dots.style.cssText = 'color: #94a3b8; padding: 0 4px; font-weight: 700;';
            controls.appendChild(dots);
        }
        createButton(String(page), page, { active: page === aiRecommendationsCurrentPage });
        previousPage = page;
    });

    createButton('Next', aiRecommendationsCurrentPage + 1, { disabled: aiRecommendationsCurrentPage === totalPages });
}

function setAiRecommendationsPage(page) {
    const totalPages = Math.max(1, Math.ceil(aiRecommendationsFiltered.length / aiRecommendationsPageSize));
    aiRecommendationsCurrentPage = Math.min(Math.max(page, 1), totalPages);
    renderAiRecommendationsTable();
}

function renderAiRecommendationsTable() {
    const tbody = document.getElementById('aiRecommendationsTable');
    if (!tbody) return;
    tbody.innerHTML = '';

    const items = aiRecommendationsFiltered;
    if (!items.length) {
        const msg = aiRecommendations.length ? 'No recommendations match your search.' : 'No incident reports are available for recommendations.';
        updateAiRecommendationsPagination(0);
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="padding: 28px; text-align: center; color: #64748b;">
                    <i class="fas fa-info-circle" style="margin-right: 8px;"></i>
                    ${msg}
                </td>
            </tr>
        `;
        return;
    }

    const totalPages = Math.max(1, Math.ceil(items.length / aiRecommendationsPageSize));
    aiRecommendationsCurrentPage = Math.min(Math.max(aiRecommendationsCurrentPage, 1), totalPages);
    const startIndex = (aiRecommendationsCurrentPage - 1) * aiRecommendationsPageSize;
    const pageItems = items.slice(startIndex, startIndex + aiRecommendationsPageSize);

    pageItems.forEach((rec, pageIndex) => {
        const globalIndex = startIndex + pageIndex;
        const row = document.createElement('tr');
        row.style.borderBottom = '1px solid #e2e8f0';

        const rankCell = document.createElement('td');
        rankCell.style.cssText = 'padding: 12px 14px; color: #64748b; font-weight: 700; font-size: 14px; vertical-align: middle;';
        rankCell.textContent = String(globalIndex + 1);

        const titleCell = document.createElement('td');
        titleCell.style.cssText = 'padding: 12px 14px; color: #0f172a; font-weight: 700; line-height: 1.4;';
        titleCell.textContent = rec.campaign_title || rec.recommended_campaign_title || 'N/A';

        const catCell = document.createElement('td');
        catCell.style.cssText = 'padding: 12px 14px; vertical-align: middle;';
        const catBadge = getCategoryBadge(rec.category || rec.source || 'crime');
        const catSpan = document.createElement('span');
        catSpan.style.cssText = `display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: ${catBadge.color}15; color: ${catBadge.color}; border: 1px solid ${catBadge.color}30; text-transform: uppercase; letter-spacing: 0.3px;`;
        catSpan.innerHTML = `<i class="fas ${catBadge.icon}"></i> ${catBadge.label}`;
        catCell.appendChild(catSpan);

        const incCell = document.createElement('td');
        incCell.style.cssText = 'padding: 12px 14px; color: #475569; font-size: 13px;';
        incCell.textContent = rec.main_trend || rec.incident_title || 'N/A';

        const countCell = document.createElement('td');
        countCell.style.cssText = 'padding: 12px 14px; color: #0f172a; font-weight: 700; white-space: nowrap; font-size: 14px;';
        countCell.textContent = (rec.report_count || 0).toLocaleString();

        const priorityCell = document.createElement('td');
        priorityCell.style.cssText = 'padding: 12px 14px;';
        const prio = getPriorityBadge(rec.priority_level, rec.priority_score);
        const badge = document.createElement('span');
        badge.style.cssText = `display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; background: ${prio.color}15; color: ${prio.color}; border: 1px solid ${prio.color}30;`;
        badge.textContent = `${prio.label} (${Number(rec.priority_score || 0).toFixed(0)})`;
        priorityCell.appendChild(badge);

        const actionCell = document.createElement('td');
        actionCell.style.cssText = 'padding: 12px 14px;';
        const viewButton = document.createElement('button');
        viewButton.type = 'button';
        viewButton.className = 'btn btn-primary';
        viewButton.style.cssText = 'display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 12px;';
        viewButton.innerHTML = '<i class="fas fa-eye"></i><span>View</span>';
        viewButton.addEventListener('click', () => openAiRecommendationModal(globalIndex, items));
        actionCell.appendChild(viewButton);

        row.append(rankCell, titleCell, catCell, incCell, countCell, priorityCell, actionCell);
        tbody.appendChild(row);
    });

    updateAiRecommendationsPagination(items.length);
}

async function loadAiRecommendations(forceRefresh = false) {
    if (!forceRefresh && aiRecommendations.length) {
        aiRecommendationsFiltered = [...aiRecommendations];
        renderAiRecommendationsTable();
        return;
    }

    renderAiRecommendationsLoading();
    setAiStatus('Loading compiled analytics from crime and disaster reports...');
    aiRecommendationsCurrentPage = 1;

    try {
        const url = aiRecommendationsUrl + (forceRefresh ? '?refresh=1' : '');
        const response = await fetch(url, {
            headers: { 'Accept': 'application/json' },
            cache: 'no-store'
        });
        if (!response.ok) throw new Error(`AI recommendations API returned HTTP ${response.status}`);
        const payload = await response.json();
        if (payload && payload.success === false) {
            throw new Error(payload.error || 'AI recommendations endpoint failed');
        }

        aiRecommendations = Array.isArray(payload?.recommendations) ? payload.recommendations : [];
        aiRecommendationsFiltered = [...aiRecommendations];
        aiRecommendationsCurrentPage = 1;
        renderAiRecommendationsTable();

        if (!aiRecommendations.length) {
            setAiStatus('No incident reports available for recommendations.');
            return;
        }

        const genLabel = payload.generated_by === 'gemini' ? 'Gemini AI titles' : payload.generated_by === 'cache' ? 'cached' : 'rule-based titles';
        const testInfo = payload.test_records_filtered > 0 ? ` (${payload.test_records_filtered} test/mock records excluded)` : '';
        setAiStatus(`${payload.valid_records || 0} valid reports grouped into ${payload.total_clusters || 0} campaign clusters; using ${genLabel}.${testInfo}`);
    } catch (err) {
        console.error('Failed to load AI recommendations:', err);
        setAiStatus(err.message || 'Unable to load recommendations.', true);
        const tbody = document.getElementById('aiRecommendationsTable');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="padding: 28px; text-align: center; color: #b91c1c;">
                        <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                        Unable to load recommendations. Please try again.
                    </td>
                </tr>
            `;
        }
    }
}

window.loadAiRecommendations = loadAiRecommendations;

const aiRecommendationDetailsUrl = basePath + '/public/api/ai_recommendation_details.php';
const aiRecommendationGenerateUrl = basePath + '/public/api/ai_recommendation_detail_generate.php';
const aiRecommendationAcceptUrl = basePath + '/public/api/ai_recommendation_accept.php';

async function openAiRecommendationModal(index, items) {
    const rec = items[index];
    if (!rec) return;

    closeAiRecommendationModal();

    const prio = getPriorityBadge(rec.priority_level, rec.priority_score);
    const catBadge = getCategoryBadge(rec.category || rec.source || 'crime');

    const modal = document.createElement('div');
    modal.id = 'aiRecommendationModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);';

    modal.innerHTML = `
        <div style="background: white; border-radius: 14px; max-width: 1120px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #4c8a89 100%); color: white; padding: 16px 24px; display: flex; justify-content: space-between; gap: 16px; align-items: flex-start;">
                <div style="flex: 1;">
                    <div style="font-size: 11px; opacity: 0.9; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                        <i class="fas ${catBadge.icon}"></i> ${catBadge.label} Campaign Recommendation
                    </div>
                    <h2 style="margin: 4px 0; font-size: 18px; font-weight: 800; line-height: 1.3;">${escapeHtml(rec.campaign_title || rec.recommended_campaign_title)}</h2>
                    <div style="font-size: 13px; opacity: 0.85; margin-top: 4px;">${escapeHtml(rec.main_trend || rec.incident_title || '')}</div>
                </div>
                <button type="button" onclick="closeAiRecommendationModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">&times;</button>
            </div>

            <div style="display: flex; align-items: stretch; border-bottom: 1px solid #e2e8f0; background: #f8fafc; padding: 0 18px; gap: 6px; overflow-x: auto; overflow-y: hidden; min-height: 48px; height: 48px; flex: 0 0 auto;" id="aiRecModalTabs">
                <button class="ai-rec-tab active" data-tab="overview" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 700; border: none; background: rgba(102,126,234,0.08); cursor: pointer; color: #667eea; border-bottom: 3px solid #667eea; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Overall Summary</button>
                <button class="ai-rec-tab" data-tab="reports" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Reports</button>
                <button class="ai-rec-tab" data-tab="locations" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Locations & Audience</button>
                <button class="ai-rec-tab" data-tab="budget" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Budget Breakdown</button>
                <button class="ai-rec-tab" data-tab="staff" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Participants & Partners</button>
                <button class="ai-rec-tab" data-tab="events" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Events & Seminars</button>
                <button class="ai-rec-tab" data-tab="schedule" style="height: 48px; min-height: 48px; box-sizing: border-box; padding: 0 14px; margin: 0; font-size: 12px; line-height: 1; font-weight: 600; border: none; background: transparent; cursor: pointer; color: #64748b; border-bottom: 3px solid transparent; white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; appearance: none; border-radius: 0;">Date Sprint</button>
            </div>

            <div id="aiRecModalBody" style="padding: 20px 24px; overflow-y: auto; min-height: 300px;">
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <i class="fas fa-spinner fa-pulse" style="font-size: 24px; margin-bottom: 12px;"></i>
                    <div style="font-size: 14px;">Loading detailed recommendation data...</div>
                </div>
            </div>

            <div style="padding: 12px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; gap: 12px; align-items: center;">
                <span id="aiRecPlanStatus" style="font-size: 11px; color: #64748b;">Generated by ${escapeHtml(rec.generated_by || 'rule-based')}</span>
                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button id="aiRecAcceptBtn" onclick="acceptAiRecommendation()" style="padding: 8px 14px; background: #16a34a; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-check-circle"></i>
                    Accept Recommended
                </button>
                <button id="aiRecGeneratePlanBtn" onclick="generateAiRecommendationPlan()" style="padding: 8px 14px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-magic"></i>
                    Generate Campaign Plan
                </button>
                <button onclick="closeAiRecommendationModal()" style="padding: 8px 20px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px;">Close</button>
                </div>
            </div>
        </div>
    `;

    modal.addEventListener('click', event => {
        if (event.target === modal) closeAiRecommendationModal();
    });

    document.body.appendChild(modal);

    const tabBar = document.getElementById('aiRecModalTabs');
    tabBar.addEventListener('click', function (e) {
        const btn = e.target.closest('.ai-rec-tab');
        if (!btn) return;
        document.querySelectorAll('.ai-rec-tab').forEach(b => {
            b.style.color = '#64748b';
            b.style.borderBottom = '3px solid transparent';
            b.style.fontWeight = '600';
            b.style.background = 'transparent';
        });
        btn.style.color = '#667eea';
        btn.style.borderBottom = '3px solid #667eea';
        btn.style.fontWeight = '700';
        btn.style.background = 'rgba(102,126,234,0.08)';
        const tab = btn.getAttribute('data-tab');
        window.__aiRecActiveTab = tab;
        renderAiRecTab(tab, rec);
    });

    const detailsUrl = aiRecommendationDetailsUrl + '?id=' + (rec.id || 0);
    window.__aiRecActive = { rec, index, items };
    window.__aiRecActiveTab = 'overview';
    try {
        const resp = await fetch(detailsUrl, { headers: { 'Accept': 'application/json' } });
        if (resp.ok) {
            const payload = await resp.json();
            window.__aiRecDetail = payload?.data || null;
        } else {
            const message = await readApiError(resp);
            window.__aiRecDetailError = message || ('Details request failed with HTTP ' + resp.status);
        }
    } catch (e) {
        console.warn('Could not load detailed recommendation data:', e);
        window.__aiRecDetailError = e.message || 'Unable to load details';
    }
    updateAiRecPlanAction(rec);
    renderAiRecTab('overview', rec);
}

function renderAiRecTab(tab, rec) {
    const body = document.getElementById('aiRecModalBody');
    if (!body) return;
    const data = window.__aiRecDetail || {};
    const summary = data?.summary || {};
    const mergedRec = { ...rec, ...summary };

    if (tab === 'overview') {
        renderAiRecOverview(body, mergedRec, data);
    } else if (tab === 'locations') {
        renderAiRecLocationsTab(body, mergedRec, data);
    } else if (tab === 'budget') {
        renderAiRecBudgetTab(body, data);
    } else if (tab === 'staff') {
        renderAiRecStaffTab(body, data);
    } else if (tab === 'events') {
        renderAiRecEventsTab(body, data);
    } else if (tab === 'partners') {
        renderAiRecPartnersTab(body, data);
    } else if (tab === 'schedule') {
        renderAiRecScheduleTab(body, data);
    } else if (tab === 'reports') {
        renderAiRecReportsTab(body, data, mergedRec);
    }
}

function renderAiRecOverview(body, rec, data) {
    const reportList = Array.isArray(rec.cluster_report_ids) ? rec.cluster_report_ids : [];
    const locations = Array.isArray(rec.affected_locations) ? rec.affected_locations : [];
    const actions = Array.isArray(rec.ai_recommended_actions) ? rec.ai_recommended_actions : [];
    const breakdown = rec.scoring_breakdown || {};
    const prio = getPriorityBadge(rec.priority_level, rec.priority_score);

    const reportsHtml = reportList.length > 0 ? reportList.map(r =>
        `<tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(r.id || '')}</td>
            <td style="padding: 6px 10px; font-size: 12px; color: #475569;">${escapeHtml(r.title || '')}</td>
        </tr>`
    ).join('') : '<tr><td colspan="2" style="padding: 10px; text-align: center; color: #94a3b8;">No report details available</td></tr>';

    const locationsHtml = locations.length > 0
        ? locations.map(l => `<span style="display: inline-block; background: #f1f5f9; padding: 3px 8px; border-radius: 12px; font-size: 11px; color: #475569; margin: 2px 4px 2px 0;">${escapeHtml(l)}</span>`).join('')
        : '<span style="color: #94a3b8; font-size: 13px;">No location data</span>';

    const actionsHtml = actions.length > 0
        ? actions.map((a, i) => `<div style="display: flex; gap: 8px; padding: 4px 0;"><span style="color: #667eea; font-weight: 700;">${i + 1}.</span><span style="color: #0f172a; font-size: 13px;">${escapeHtml(a)}</span></div>`).join('')
        : '<div style="color: #94a3b8; font-size: 13px;">No specific actions generated</div>';

    const earliestDate = rec.earliest_date ? new Date(rec.earliest_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';
    const latestDate = rec.latest_date ? new Date(rec.latest_date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : 'N/A';

    const summary = data?.summary || {};
    const planStatus = summary.planning_status || 'not_generated';
    const apprStatus = summary.approval_status || 'recommended';
    const budgetValidation = summary.budget_validation_status || 'unchecked';

    const statusBadge = planStatus === 'completed' ? '<span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Completed</span>'
        : planStatus === 'completed_with_warnings' ? '<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Completed with Warnings</span>'
        : '<span style="background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Not Generated</span>';

    const budgetStatusBadge = budgetValidation === 'verified' ? '<span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Verified</span>'
        : budgetValidation === 'warning' ? '<span style="background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Warning</span>'
        : '<span style="background: #f1f5f9; color: #64748b; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">Unchecked</span>';

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">Reports</div>
                <div style="font-size: 24px; color: #0f172a; font-weight: 800; line-height: 1.2;">${(rec.report_count || 0).toLocaleString()}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">Priority</div>
                <div style="font-size: 18px; font-weight: 800; color: ${prio.color}; line-height: 1.2;">${prio.label}</div>
                <div style="font-size: 11px; color: ${prio.color}; font-weight: 600;">${Number(rec.priority_score || 0).toFixed(0)}/100</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;">Planning Status</div>
                <div style="font-size: 13px; line-height: 1.2; margin-top: 4px;">${statusBadge}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 4px;">Budget: ${budgetStatusBadge}</div>
            </div>
        </div>

        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #0369a1; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 6px;">
                <i class="fas fa-robot"></i> AI Campaign Strategy
            </div>
            <div style="font-size: 14px; color: #0c4a6e; font-weight: 600; line-height: 1.4; margin-bottom: 6px;">${escapeHtml(rec.campaign_title || rec.recommended_campaign_title)}</div>
            ${rec.description ? `<div style="font-size: 13px; color: #334155; line-height: 1.5;">${escapeHtml(rec.description)}</div>` : ''}
        </div>

        ${rec.ai_reasoning ? `
        <div style="background: #fefce8; border: 1px solid #fde68a; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #92400e; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 6px;">
                <i class="fas fa-brain"></i> AI Reasoning
            </div>
            <div style="font-size: 13px; color: #713f12; line-height: 1.6;">${escapeHtml(rec.ai_reasoning)}</div>
        </div>` : ''}

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #15803d; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px;">
                <i class="fas fa-cogs"></i> Priority Score Breakdown
            </div>
            <div style="font-size: 13px; line-height: 1.7;">
                <div style="display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #dcfce7;">
                    <span style="color: #166534;">Severity Score (${breakdown.severity_weight !== undefined ? (breakdown.severity_weight * 100) + '%' : '40%'} weight)</span>
                    <span style="font-weight: 700; color: #166534;">${Number(rec.severity_score || 0).toFixed(0)}/100</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #dcfce7;">
                    <span style="color: #166534;">Frequency Score (${breakdown.frequency_weight !== undefined ? (breakdown.frequency_weight * 100) + '%' : '25%'} weight)</span>
                    <span style="font-weight: 700; color: #166534;">${Number(rec.frequency_score || 0).toFixed(0)}/100</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #dcfce7;">
                    <span style="color: #166534;">Recency Score (${breakdown.recency_weight !== undefined ? (breakdown.recency_weight * 100) + '%' : '20%'} weight)</span>
                    <span style="font-weight: 700; color: #166534;">${Number(rec.recency_score || 0).toFixed(0)}/100</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #dcfce7;">
                    <span style="color: #166534;">Geographic Impact (${breakdown.geographic_weight !== undefined ? (breakdown.geographic_weight * 100) + '%' : '15%'} weight)</span>
                    <span style="font-weight: 700; color: #166534;">${Number(rec.geographic_score || 0).toFixed(0)}/100</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 3px 0; font-size: 15px;">
                    <span style="color: #166534; font-weight: 700;">Final Priority Score</span>
                    <span style="font-weight: 800; color: ${prio.color};">${Number(rec.priority_score || 0).toFixed(0)}/100 (${prio.label})</span>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 6px;">
                    <i class="fas fa-map-marker-alt"></i> Affected Locations
                </div>
                <div>${locationsHtml}</div>
            </div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 6px;">
                    <i class="fas fa-users"></i> Target Audience
                </div>
                <div style="font-size: 13px; color: #0f172a; font-weight: 500;">${escapeHtml(rec.ai_target_audience || 'General public and community stakeholders')}</div>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px;">
                <i class="fas fa-clipboard-check"></i> Recommended Actions
            </div>
            ${actionsHtml}
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px;">
                <i class="fas fa-list"></i> Supporting Reports (${reportList.length})
            </div>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Report ID</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; border-bottom: 1px solid #e2e8f0;">Title</th>
                        </tr>
                    </thead>
                    <tbody>${reportsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderAiRecLocationsTab(body, rec, data) {
    const locations = Array.isArray(rec.affected_locations) ? rec.affected_locations : [];
    const audience = rec.ai_target_audience || 'General public and community stakeholders';
    const actions = Array.isArray(rec.ai_recommended_actions) ? rec.ai_recommended_actions : [];
    const reports = data?.reports || { all: [] };
    const reportRows = Array.isArray(reports.all) ? reports.all : [];

    const locationRows = locations.length > 0 ? locations.map((location, index) => {
        const relatedReports = reportRows.filter(r => (r.location || r.barangay_or_area || '').toLowerCase().includes(String(location).toLowerCase())).length;
        return `<tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 8px 10px; font-size: 12px; color: #0f172a; font-weight: 600;">${escapeHtml(location)}</td>
            <td style="padding: 8px 10px; font-size: 12px; color: #475569;">${Math.max(1, Math.ceil(actions.length / Math.max(1, locations.length)))}</td>
            <td style="padding: 8px 10px; font-size: 12px; color: #475569;">${relatedReports || 'Not separately tagged'}</td>
            <td style="padding: 8px 10px; font-size: 12px; color: #64748b;">Affected location ${index + 1} for campaign deployment</td>
        </tr>`;
    }).join('') : '<tr><td colspan="4" style="padding: 20px; text-align: center; color: #94a3b8;">No location data available.</td></tr>';

    const actionRows = actions.length > 0 ? actions.map((action, index) => `
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 8px 10px; font-size: 12px; color: #64748b;">${index + 1}</td>
            <td style="padding: 8px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(action)}</td>
            <td style="padding: 8px 10px; font-size: 12px; color: #475569;">${escapeHtml(locations[index % Math.max(1, locations.length)] || 'Primary affected location')}</td>
        </tr>
    `).join('') : '<tr><td colspan="3" style="padding: 20px; text-align: center; color: #94a3b8;">No actions generated yet.</td></tr>';

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Locations</div>
                <div style="font-size: 22px; font-weight: 800; color: #0f172a;">${locations.length}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Actions</div>
                <div style="font-size: 22px; font-weight: 800; color: #667eea;">${actions.length}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Duration</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a;">${escapeHtml(rec.recommended_duration || 30)} days</div>
            </div>
        </div>
        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #0369a1; font-weight: 700; text-transform: uppercase; margin-bottom: 6px;">Target Audience</div>
            <div style="font-size: 14px; color: #0c4a6e; font-weight: 600;">${escapeHtml(audience)}</div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Affected Locations</div>
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; min-width: 640px;">
                    <thead><tr style="background: #f1f5f9;">
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Location</th>
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Planned Activities</th>
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Tagged Reports</th>
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Planning Note</th>
                    </tr></thead>
                    <tbody>${locationRows}</tbody>
                </table>
            </div>
        </div>
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">Action Location Mapping</div>
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; min-width: 640px;">
                    <thead><tr style="background: #f8fafc;">
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">#</th>
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Recommended Action</th>
                        <th style="padding: 8px 10px; text-align: left; font-size: 11px; color: #475569;">Primary Deployment Location</th>
                    </tr></thead>
                    <tbody>${actionRows}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderAiRecBudgetTabLegacy(body, data) {
    const budget = data?.budget || { items: [], total_estimated: '0', count: 0 };
    const summary = data?.summary || {};
    const budgetSummary = data?.budget_summary || { by_funding_source: [] };

    const items = budget.items || [];
    const totalEst = Number(budget.total_estimated || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const estBudget = summary.estimated_budget ? Number(summary.estimated_budget).toLocaleString(undefined, { minimumFractionDigits: 2 }) : 'N/A';
    const recBudget = summary.final_recommended_budget ? Number(summary.final_recommended_budget).toLocaleString(undefined, { minimumFractionDigits: 2 }) : 'N/A';
    const appBudget = summary.approved_budget ? Number(summary.approved_budget).toLocaleString(undefined, { minimumFractionDigits: 2 }) : 'Pending';
    const apprStatus = summary.approval_status || 'recommended';
    const valStatus = summary.budget_validation_status || 'unchecked';

    const itemsHtml = items.length > 0 ? items.map(i => `
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(i.item_name)}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(i.item_type || '')}</td>
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a; text-align: right;">${Number(i.quantity || 0).toLocaleString()}</td>
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a; text-align: right;">₱${Number(i.unit_cost || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
            <td style="padding: 6px 10px; font-size: 12px; font-weight: 700; color: #0f172a; text-align: right;">₱${Number(i.subtotal || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(i.funding_source || '')}</td>
        </tr>
    `).join('') : '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #94a3b8;">No budget items generated yet. Use the Generate action to create budget recommendations.</td></tr>';

    const fundingHtml = budgetSummary.by_funding_source && budgetSummary.by_funding_source.length > 0
        ? budgetSummary.by_funding_source.map(f => `
            <div style="display: flex; justify-content: space-between; padding: 4px 0; border-bottom: 1px solid #e2e8f0; font-size: 12px;">
                <span style="color: #475569;">${escapeHtml(f.funding_source)}</span>
                <span style="font-weight: 700; color: #0f172a;">₱${Number(f.total || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })} (${f.item_count} items)</span>
            </div>
        `).join('') : '';

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Estimated</div>
                <div style="font-size: 16px; font-weight: 800; color: #0f172a;">₱${estBudget}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Recommended</div>
                <div style="font-size: 16px; font-weight: 800; color: #667eea;">₱${recBudget}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Approved</div>
                <div style="font-size: 16px; font-weight: 800; color: #16a34a;">₱${appBudget}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Status</div>
                <div style="font-size: 12px; font-weight: 700; color: ${apprStatus === 'approved' ? '#16a34a' : apprStatus === 'rejected' ? '#dc2626' : '#ca8a04'}; margin-top: 4px;">${apprStatus}</div>
                <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Validation: ${valStatus}</div>
            </div>
        </div>

        ${fundingHtml ? `
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; margin-bottom: 6px;">Funding Source Breakdown</div>
            ${fundingHtml}
        </div>` : ''}

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
                Budget Line Items (${items.length})
            </div>
            <div style="max-height: 350px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Item</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Type</th>
                            <th style="padding: 6px 10px; text-align: right; font-size: 11px; font-weight: 700; color: #475569;">Qty</th>
                            <th style="padding: 6px 10px; text-align: right; font-size: 11px; font-weight: 700; color: #475569;">Unit Cost</th>
                            <th style="padding: 6px 10px; text-align: right; font-size: 11px; font-weight: 700; color: #475569;">Subtotal</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Source</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderAiRecStaffTabLegacy(body, data) {
    const staff = data?.staff || { participants: [], total: 0, confirmed: 0, unmatched: 0, conflicts: 0 };
    const participants = staff.participants || [];

    const itemsHtml = participants.length > 0 ? participants.map(p => {
        const matchStyle = p.match_method === 'unmatched' ? 'color: #ea580c;' : p.match_method === 'name_match' ? 'color: #ca8a04;' : 'color: #16a34a;';
        const availHtml = p.availability_status === 'Not Recorded'
            ? '<span style="color: #94a3b8; font-size: 11px;">Not Recorded</span>'
            : `<span style="font-size: 11px;">${escapeHtml(p.availability_status)}</span>`;
        const conflictHtml = p.conflict_status === 'possible_conflict'
            ? `<span style="color: #ea580c; font-size: 11px;" title="${escapeHtml(p.conflict_note || '')}">&#9888; Possible Conflict</span>`
            : '<span style="color: #94a3b8; font-size: 11px;">None</span>';
        const confirmHtml = p.is_confirmed
            ? '<span style="color: #16a34a; font-size: 11px;">&#10003; Confirmed</span>'
            : '<span style="color: #94a3b8; font-size: 11px;">Pending</span>';

        return `<tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(p.staff_name_snapshot)}</td>
            <td style="padding: 6px 10px; font-size: 12px; color: #475569;">${escapeHtml(p.staff_role_snapshot || '')}</td>
            <td style="padding: 6px 10px; font-size: 11px; ${matchStyle}">${p.match_method}</td>
            <td style="padding: 6px 10px;">${availHtml}</td>
            <td style="padding: 6px 10px;">${conflictHtml}</td>
            <td style="padding: 6px 10px;">${confirmHtml}</td>
        </tr>`;
    }).join('') : '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #94a3b8;">No staff recommendations generated yet.</td></tr>';

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Total</div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a;">${staff.total}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Confirmed</div>
                <div style="font-size: 20px; font-weight: 800; color: #16a34a;">${staff.confirmed}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Unmatched</div>
                <div style="font-size: 20px; font-weight: 800; color: #ea580c;">${staff.unmatched}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Conflicts</div>
                <div style="font-size: 20px; font-weight: 800; color: #ca8a04;">${staff.conflicts}</div>
            </div>
        </div>

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
                Recommended Staff (${participants.length})
            </div>
            <div style="max-height: 350px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Name</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Role</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Match</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Availability</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Conflict</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Status</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderAiRecPartnersTab(body, data) {
    const partners = data?.partners || [];
    const suggestions = data?.partner_suggestions || data?.suggestions || [];

    const partnersHtml = partners.length > 0 ? partners.map(p => `
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(p.partner_name_snapshot)}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(p.organization_type_snapshot || '')}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #475569;">${escapeHtml(p.capability_match_basis || '')}</td>
            <td style="padding: 6px 10px; font-size: 12px; color: #475569;">${escapeHtml(p.recommended_role || '')}</td>
            <td style="padding: 6px 10px;">${p.is_confirmed ? '<span style="color: #16a34a; font-size: 11px;">&#10003;</span>' : '<span style="color: #94a3b8; font-size: 11px;">Pending</span>'}</td>
        </tr>
    `).join('') : '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #94a3b8;">No partner matches generated yet.</td></tr>';

    const suggestionsHtml = suggestions.length > 0 ? suggestions.map(s => `
        <tr style="border-bottom: 1px solid #f1f5f9;">
            <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(s.organization_type || 'General')}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #475569;">${escapeHtml(s.capability_description || '')}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(s.rationale || '')}</td>
            <td style="padding: 6px 10px; font-size: 12px; font-weight: 600; color: ${s.acquisition_priority === 'high' ? '#dc2626' : s.acquisition_priority === 'medium' ? '#ca8a04' : '#16a34a'};">${s.acquisition_priority}</td>
            <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(s.proposal_status || 'proposed')}</td>
        </tr>
    `).join('') : '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #94a3b8;">No new partner suggestions.</td></tr>';

    body.innerHTML = `
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
                <i class="fas fa-handshake"></i> Matched Partners (${partners.length})
            </div>
            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Name</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Type</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Match Basis</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Role</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Status</th>
                        </tr>
                    </thead>
                    <tbody>${partnersHtml}</tbody>
                </table>
            </div>
        </div>

        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #15803d; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
                <i class="fas fa-lightbulb"></i> New Partner Suggestions (${suggestions.length})
            </div>
            <div style="font-size: 12px; color: #475569; margin-bottom: 8px; font-style: italic;">Generic capability proposals — no auto-insert into partners table.</div>
            <div style="max-height: 250px; overflow-y: auto; border: 1px solid #bbf7d0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f0fdf4;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #166534;">Type</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #166534;">Capability</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #166534;">Rationale</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #166534;">Priority</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #166534;">Status</th>
                        </tr>
                    </thead>
                    <tbody>${suggestionsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderAiRecScheduleTab(body, data) {
    const schedule = data?.schedule_phases || data?.schedule || { phases: [], total_phases: 0, total_budget: '0' };
    const phases = schedule.phases || [];

    const phasesHtml = phases.length > 0 ? phases.map((p, i) => {
        const activities = Array.isArray(p.activities) ? p.activities : [];
        const activitiesHtml = activities.length > 0
            ? activities.map(a => `<li style="font-size: 12px; color: #475569; margin-bottom: 2px;">${escapeHtml(a)}</li>`).join('')
            : '<li style="font-size: 12px; color: #94a3b8;">No activities listed</li>';
        const pct = Math.round(((i + 1) / phases.length) * 100);
        return `
        <div style="margin-bottom: 12px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; background: ${i % 2 === 0 ? '#f8fafc' : '#fff'};">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <div>
                    <span style="background: #667eea; color: white; border-radius: 12px; padding: 2px 10px; font-size: 11px; font-weight: 700; margin-right: 8px;">Phase ${p.sprint_number}</span>
                    <span style="font-size: 14px; font-weight: 700; color: #0f172a;">${escapeHtml(p.sprint_title)}</span>
                </div>
                <span style="font-size: 11px; color: #64748b;">
                    ${p.start_date} &rarr; ${p.end_date}
                    <span style="color: #94a3b8;">(${p.duration_days} days)</span>
                </span>
            </div>
            ${p.objectives ? `<div style="font-size: 12px; color: #475569; margin-bottom: 4px;"><strong>Objectives:</strong> ${escapeHtml(p.objectives)}</div>` : ''}
            <div style="margin-top: 4px;">
                <strong style="font-size: 11px; color: #64748b;">Activities:</strong>
                <ul style="margin: 4px 0 0 16px; padding: 0;">${activitiesHtml}</ul>
            </div>
            ${p.outputs ? `<div style="font-size: 11px; color: #64748b; margin-top: 4px;"><strong>Outputs:</strong> ${escapeHtml(p.outputs)}</div>` : ''}
            <div style="margin-top: 6px; background: #e2e8f0; border-radius: 8px; height: 4px; overflow: hidden;">
                <div style="background: linear-gradient(90deg, #667eea, #4c8a89); width: ${pct}%; height: 100%; border-radius: 8px;"></div>
            </div>
            <div style="font-size: 10px; color: #94a3b8; text-align: right; margin-top: 2px;">${p.status === 'confirmed' ? 'Confirmed' : 'Recommended - Pending Confirmation'}</div>
        </div>`;
    }).join('') : '<div style="text-align: center; padding: 40px; color: #94a3b8;">No schedule phases generated yet.</div>';

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Phases</div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a;">${schedule.total_phases}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Total Budget</div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a;">₱${Number(schedule.total_budget || 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Duration</div>
                <div style="font-size: 14px; font-weight: 800; color: #0f172a;">${phases.length > 0 ? phases[0].start_date + ' to ' + phases[phases.length - 1].end_date : 'N/A'}</div>
            </div>
        </div>

        ${phasesHtml}
    `;
}

function renderAiRecReportsTab(body, data, rec = {}) {
    const reports = data?.reports || { all: [], crime_count: 0, disaster_count: 0, total: 0 };
    const allReports = Array.isArray(reports.all) ? reports.all : [];
    const summary = data?.summary || {};
    const warning = summary.planning_error_message || reports.warning || '';

    const rowsHtml = allReports.length > 0 ? allReports.map(r => `
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(r.external_report_id || r.source_local_id || '')}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #64748b; text-transform: capitalize;">${escapeHtml(r.source_type || rec.category || '')}</td>
                <td style="padding: 6px 10px; font-size: 12px; color: #0f172a;">${escapeHtml(r.incident_title)}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(r.category || '')}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #475569;">${escapeHtml(r.severity || '')}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #475569;">${escapeHtml(r.location || '')}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${r.report_date || ''}</td>
                <td style="padding: 6px 10px; font-size: 11px; color: #64748b;">${escapeHtml(r.incident_status || '')}</td>
            </tr>
        `).join('') : `<tr><td colspan="8" style="padding: 22px; text-align: center; color: #94a3b8;">${escapeHtml(warning || 'No stored supporting reports for this recommendation yet. Use Generate Campaign Plan to restore available report snapshots.')}</td></tr>`;

    body.innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px;">
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Total Reports</div>
                <div style="font-size: 20px; font-weight: 800; color: #0f172a;">${reports.total}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Crime</div>
                <div style="font-size: 20px; font-weight: 800; color: #667eea;">${reports.crime_count}</div>
            </div>
            <div style="background: #f8fafc; border-radius: 8px; padding: 12px; border: 1px solid #e2e8f0; text-align: center;">
                <div style="font-size: 10px; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Disaster</div>
                <div style="font-size: 20px; font-weight: 800; color: #dc2626;">${reports.disaster_count}</div>
            </div>
        </div>

        ${warning ? `<div style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; font-size: 12px;">${escapeHtml(warning)}</div>` : ''}

        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px;">
            <div style="font-size: 11px; color: #475569; font-weight: 700; text-transform: uppercase; margin-bottom: 8px;">
                <i class="fas fa-list"></i> Supporting Reports for This Recommendation (${allReports.length})
            </div>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f1f5f9;">
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Report ID</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Source</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Title</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Category</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Severity</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Location</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Date</th>
                            <th style="padding: 6px 10px; text-align: left; font-size: 11px; font-weight: 700; color: #475569;">Status</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function aiRecMoney(value) {
    if (value === null || value === undefined || value === '') return 'N/A';
    return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function aiRecBadge(status) {
    const s = String(status || 'Pending');
    const lower = s.toLowerCase();
    const color = lower.includes('approved') || lower.includes('confirmed') || lower.includes('within') ? '#166534'
        : lower.includes('insufficient') || lower.includes('conflict') || lower.includes('unavailable') ? '#991b1b'
        : lower.includes('warning') || lower.includes('pending') || lower.includes('estimated') ? '#92400e'
        : '#475569';
    const bg = color === '#166534' ? '#dcfce7' : color === '#991b1b' ? '#fee2e2' : color === '#92400e' ? '#fef3c7' : '#f1f5f9';
    return `<span style="display:inline-block;background:${bg};color:${color};border-radius:999px;padding:2px 8px;font-size:11px;font-weight:700;white-space:nowrap;">${escapeHtml(s)}</span>`;
}

function aiRecSummaryCard(label, value, color = '#0f172a') {
    return `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;min-width:0;">
        <div style="font-size:10px;color:#94a3b8;font-weight:800;text-transform:uppercase;line-height:1.2;">${label}</div>
        <div style="font-size:15px;color:${color};font-weight:800;margin-top:4px;overflow-wrap:anywhere;">${value}</div>
    </div>`;
}

function aiRecSection(title, icon, content, open = true) {
    return `<details ${open ? 'open' : ''} style="border:1px solid #e2e8f0;border-radius:8px;margin-bottom:12px;background:#fff;overflow:hidden;">
        <summary style="cursor:pointer;padding:12px 14px;background:#f8fafc;font-size:12px;color:#334155;font-weight:800;text-transform:uppercase;letter-spacing:.2px;">
            <i class="fas ${icon}"></i> ${title}
        </summary>
        <div style="padding:14px;">${content}</div>
    </details>`;
}

function renderAiRecBudgetTab(body, data) {
    const analysis = data?.budget_analysis || {};
    const allocation = analysis.allocation || {};
    const campaign = analysis.campaign_summary || {};
    const lineItems = analysis.line_items || data?.budget?.items || [];
    const categories = analysis.category_breakdown || [];
    const actions = analysis.action_breakdown || [];
    const locations = analysis.location_breakdown || [];
    const staffCosts = analysis.staff_cost_impact || [];
    const partnerImpact = analysis.partner_contribution_impact || { items: [] };
    const contingency = analysis.contingency || {};
    const warnings = analysis.warnings || [];

    const lineRows = lineItems.length ? lineItems.map(item => `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 9px;font-size:11px;color:#64748b;">${item.number || ''}</td>
            <td style="padding:7px 9px;font-size:11px;color:#475569;">${escapeHtml(item.category || '')}</td>
            <td style="padding:7px 9px;font-size:12px;color:#0f172a;font-weight:700;">${escapeHtml(item.item_name || '')}</td>
            <td style="padding:7px 9px;font-size:11px;color:#64748b;min-width:180px;">${escapeHtml(item.description || '')}</td>
            <td style="padding:7px 9px;font-size:11px;color:#475569;min-width:170px;">${escapeHtml(item.related_action || '')}</td>
            <td style="padding:7px 9px;font-size:12px;text-align:right;">${Number(item.quantity || 0).toLocaleString('en-PH')}</td>
            <td style="padding:7px 9px;font-size:11px;">${escapeHtml(item.unit || item.unit_label || '')}</td>
            <td style="padding:7px 9px;font-size:12px;text-align:right;">${aiRecMoney(item.unit_cost)}</td>
            <td style="padding:7px 9px;font-size:12px;text-align:right;">${escapeHtml(item.sessions_or_days || '1')}</td>
            <td style="padding:7px 9px;font-size:12px;font-weight:800;text-align:right;">${aiRecMoney(item.subtotal)}</td>
            <td style="padding:7px 9px;font-size:11px;">${escapeHtml(item.pricing_source || '')}</td>
            <td style="padding:7px 9px;">${aiRecBadge(item.estimate_status || 'Estimated Only')}</td>
            <td style="padding:7px 9px;">${aiRecBadge(item.validation_status || 'estimated')}</td>
            <td style="padding:7px 9px;font-size:11px;color:#64748b;min-width:180px;">${escapeHtml(item.reason || item.calculation_basis || '')}</td>
        </tr>`).join('') : '<tr><td colspan="14" style="padding:20px;text-align:center;color:#94a3b8;">No budget items generated yet. Use Generate Campaign Plan.</td></tr>';

    const categoryRows = categories.length ? categories.map(c => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(c.category)}</td><td style="padding:7px 10px;text-align:right;">${aiRecMoney(c.total)}</td><td style="padding:7px 10px;text-align:right;">${c.percentage_of_total ?? 'N/A'}%</td><td style="padding:7px 10px;text-align:right;">${c.item_count}</td></tr>
    `).join('') : '<tr><td colspan="4" style="padding:16px;text-align:center;color:#94a3b8;">No category breakdown.</td></tr>';

    const actionHtml = actions.length ? actions.map(a => `
        <div style="border:1px solid #e2e8f0;border-radius:8px;padding:10px;margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                <div style="font-size:12px;color:#0f172a;font-weight:700;">${escapeHtml(a.action)}</div>
                <div style="font-size:12px;color:#166534;font-weight:800;white-space:nowrap;">${aiRecMoney(a.action_budget_total)}</div>
            </div>
            <div style="margin-top:6px;">${(a.items || []).map(i => `<span style="display:inline-block;background:#f1f5f9;border-radius:999px;padding:3px 8px;font-size:11px;color:#475569;margin:2px;">${escapeHtml(i.item_name)} - ${aiRecMoney(i.subtotal)}</span>`).join('')}</div>
        </div>`).join('') : '<div style="padding:14px;color:#94a3b8;text-align:center;">No action mapping available.</div>';

    const locationRows = locations.length ? locations.map(l => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(l.location)}</td><td style="padding:7px 10px;text-align:right;">${l.activities}</td><td style="padding:7px 10px;text-align:right;">${l.staff_qty}</td><td style="padding:7px 10px;text-align:right;">${aiRecMoney(l.material_allocation)}</td><td style="padding:7px 10px;text-align:right;">${aiRecMoney(l.transportation_cost)}</td><td style="padding:7px 10px;text-align:right;">${aiRecMoney(l.other_cost)}</td><td style="padding:7px 10px;text-align:right;font-weight:800;">${aiRecMoney(l.total_estimated_cost)}</td><td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(l.basis || 'Estimated Distribution')}</td></tr>
    `).join('') : '<tr><td colspan="8" style="padding:16px;text-align:center;color:#94a3b8;">No location distribution available.</td></tr>';

    const staffRows = staffCosts.length ? staffCosts.map(s => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(s.staff_role)}</td><td style="padding:7px 10px;text-align:right;">${s.required_qty}</td><td style="padding:7px 10px;text-align:right;">${s.existing_matched_qty}</td><td style="padding:7px 10px;text-align:right;">${s.missing_qty}</td><td style="padding:7px 10px;text-align:right;">${s.deployment_days}</td><td style="padding:7px 10px;text-align:right;font-weight:800;">${aiRecMoney(s.estimated_support_cost)}</td><td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(s.cost_type)}</td></tr>
    `).join('') : '<tr><td colspan="7" style="padding:16px;text-align:center;color:#94a3b8;">No staff cost impact yet.</td></tr>';

    const partnerRows = (partnerImpact.items || []).length ? partnerImpact.items.map(p => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(p.partner)}</td><td style="padding:7px 10px;font-size:12px;">${escapeHtml(p.recommended_contribution || '')}</td><td style="padding:7px 10px;font-size:12px;">${escapeHtml(p.contribution_type || '')}</td><td style="padding:7px 10px;font-size:12px;">${escapeHtml(p.estimated_budget_impact || '')}</td><td style="padding:7px 10px;">${aiRecBadge(p.verification_status || 'Requires Confirmation')}</td></tr>
    `).join('') : '<tr><td colspan="5" style="padding:16px;text-align:center;color:#94a3b8;">No partner contribution impact yet.</td></tr>';

    const warningHtml = warnings.length ? warnings.map(w => `
        <div style="background:${w.severity === 'danger' ? '#fee2e2' : w.severity === 'success' ? '#dcfce7' : '#fef3c7'};border:1px solid ${w.severity === 'danger' ? '#fecaca' : w.severity === 'success' ? '#bbf7d0' : '#fde68a'};border-radius:8px;padding:9px 10px;margin-bottom:8px;">
            <div style="font-size:11px;font-weight:800;color:#334155;">${escapeHtml(w.status)}</div><div style="font-size:12px;color:#475569;">${escapeHtml(w.message)}</div>
        </div>`).join('') : '<div style="padding:10px;color:#16a34a;font-size:12px;">No budget warnings detected.</div>';

    body.innerHTML = `
        ${aiRecSection('Government Allocation Summary', 'fa-landmark', `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;">
            ${aiRecSummaryCard('Fiscal Year', allocation.fiscal_year ? escapeHtml(allocation.fiscal_year) : 'Unavailable')}
            ${aiRecSummaryCard('Total Government Allocation', allocation.exists ? aiRecMoney(allocation.total_government_allocation) : 'Budget Data Unavailable')}
            ${aiRecSummaryCard('Verified Official Commitments', allocation.commitment_data_available === false ? 'Unavailable' : aiRecMoney(allocation.verified_official_commitments))}
            ${aiRecSummaryCard('Unresolved Potential Commitments', allocation.unresolved_potential_commitments ?? 'Unavailable')}
            ${aiRecSummaryCard('Actual Spending', escapeHtml(allocation.actual_spending_label || 'Actual Spending Data Unavailable'))}
            ${aiRecSummaryCard('Protected Reserve', aiRecMoney(allocation.protected_reserve))}
            ${aiRecSummaryCard('Safe Available Budget', aiRecMoney(allocation.safe_available_budget), '#166534')}
            ${aiRecSummaryCard('Proposed AI Campaign Budget', aiRecMoney(allocation.proposed_ai_campaign_budget), '#667eea')}
            ${aiRecSummaryCard('Forecast Balance After Campaign', aiRecMoney(allocation.forecast_balance_after_campaign), '#0f766e')}
        </div>`)}
        ${aiRecSection('Campaign Budget Summary', 'fa-wallet', `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;">
            ${aiRecSummaryCard('Estimated Campaign Need', aiRecMoney(campaign.estimated_campaign_need))}
            ${aiRecSummaryCard('System Maximum Allowed', aiRecMoney(campaign.system_maximum_allowed))}
            ${aiRecSummaryCard('Final Recommended Budget', aiRecMoney(campaign.final_recommended_budget), '#667eea')}
            ${aiRecSummaryCard('Approved Budget', campaign.approved_budget ? aiRecMoney(campaign.approved_budget) : 'Pending')}
            ${aiRecSummaryCard('Budget Gap', aiRecMoney(campaign.budget_gap), Number(campaign.budget_gap || 0) > 0 ? '#dc2626' : '#166534')}
            ${aiRecSummaryCard('Budget Utilization', campaign.budget_utilization_percentage === null || campaign.budget_utilization_percentage === undefined ? 'N/A' : campaign.budget_utilization_percentage + '%')}
            ${aiRecSummaryCard('Validation Status', aiRecBadge(campaign.validation_status))}
            ${aiRecSummaryCard('Pricing Confidence', aiRecBadge(campaign.pricing_confidence))}
            ${aiRecSummaryCard('Last Calculated', campaign.last_calculated_date ? escapeHtml(campaign.last_calculated_date) : 'Unavailable')}
        </div>`)}
        ${aiRecSection('Budget Warnings', 'fa-triangle-exclamation', warningHtml)}
        ${aiRecSection('Detailed Line Items', 'fa-list-check', `<div style="max-height:390px;overflow:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:1450px;"><thead><tr style="background:#f1f5f9;">${['#','Budget Category','Item Name','Description','Related Campaign Action','QTY','Unit','Unit Cost','Sessions / Days','Subtotal','Pricing Source','Estimate Status','Validation Status','Reason'].map(h => `<th style="padding:7px 9px;text-align:left;font-size:11px;color:#475569;">${h}</th>`).join('')}</tr></thead><tbody>${lineRows}</tbody></table></div>`)}
        ${aiRecSection('Category Breakdown', 'fa-chart-pie', `<div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:520px;"><thead><tr style="background:#f8fafc;"><th style="padding:7px 10px;text-align:left;font-size:11px;">Category</th><th style="padding:7px 10px;text-align:right;font-size:11px;">Category Total</th><th style="padding:7px 10px;text-align:right;font-size:11px;">% of Total</th><th style="padding:7px 10px;text-align:right;font-size:11px;">Line Items</th></tr></thead><tbody>${categoryRows}</tbody></table></div>`, false)}
        ${aiRecSection('Action-to-Budget Mapping', 'fa-route', actionHtml, false)}
        ${aiRecSection('Location Budget Distribution', 'fa-map-location-dot', `<div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:900px;"><thead><tr style="background:#f8fafc;">${['Location','# Activities','Staff QTY','Material Allocation','Transportation Cost','Other Cost','Total','Basis'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;">${h}</th>`).join('')}</tr></thead><tbody>${locationRows}</tbody></table></div>`, false)}
        ${aiRecSection('Staff Deployment Cost Impact', 'fa-users-gear', `<div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:820px;"><thead><tr style="background:#f8fafc;">${['Staff Role','Required QTY','Existing Matched QTY','Missing QTY','Deployment Days','Estimated Support Cost','Cost Type'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;">${h}</th>`).join('')}</tr></thead><tbody>${staffRows}</tbody></table></div>`, false)}
        ${aiRecSection('Partner Contribution Impact', 'fa-handshake', `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;margin-bottom:10px;">${aiRecSummaryCard('Budget Before Confirmed Partner Contributions', aiRecMoney(partnerImpact.budget_before_confirmed_partner_contributions))}${aiRecSummaryCard('Budget After Confirmed Partner Contributions', aiRecMoney(partnerImpact.budget_after_confirmed_partner_contributions))}</div><div style="font-size:12px;color:#64748b;margin-bottom:8px;">${escapeHtml(partnerImpact.note || 'Unconfirmed partner support is not deducted.')}</div><div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:820px;"><thead><tr style="background:#f8fafc;">${['Partner','Recommended Contribution','Contribution Type','Estimated Budget Impact','Verification Status'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;">${h}</th>`).join('')}</tr></thead><tbody>${partnerRows}</tbody></table></div>`, false)}
        ${aiRecSection('Contingency', 'fa-shield-halved', `<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;margin-bottom:10px;">${aiRecSummaryCard('Contingency Percentage', (contingency.contingency_percentage ?? 0) + '%')}${aiRecSummaryCard('Contingency Amount', aiRecMoney(contingency.contingency_amount))}${aiRecSummaryCard('Reason', escapeHtml(contingency.reason || 'No contingency line item generated yet.'))}</div><div style="font-size:12px;color:#475569;">May cover: ${(contingency.may_cover || []).map(x => `<span style="display:inline-block;background:#f1f5f9;border-radius:999px;padding:3px 8px;margin:2px;">${escapeHtml(x)}</span>`).join('')}</div>`, false)}
    `;
}

function buildAiRecPartnersSection(data) {
    const partners = data?.partners || [];
    const suggestions = data?.partner_suggestions || data?.suggestions || [];
    const partnersHtml = partners.length ? partners.map(p => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(p.partner_name_snapshot || p.current_partner_name || '')}</td><td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(p.organization_type_snapshot || '')}</td><td style="padding:7px 10px;font-size:11px;color:#475569;">${escapeHtml(p.capability_match_basis || '')}</td><td style="padding:7px 10px;font-size:12px;">${escapeHtml(p.recommended_role || '')}</td><td style="padding:7px 10px;">${aiRecBadge(p.is_confirmed ? 'Confirmed' : 'Pending')}</td></tr>
    `).join('') : '<tr><td colspan="5" style="padding:18px;text-align:center;color:#94a3b8;">No partner matches generated yet.</td></tr>';
    const suggestionsHtml = suggestions.length ? suggestions.map(s => `
        <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:7px 10px;font-size:12px;font-weight:700;">${escapeHtml(s.organization_type || 'General')}</td><td style="padding:7px 10px;font-size:11px;">${escapeHtml(s.capability_description || '')}</td><td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(s.rationale || '')}</td><td style="padding:7px 10px;">${aiRecBadge(s.acquisition_priority || 'medium')}</td><td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(s.proposal_status || 'proposed')}</td></tr>
    `).join('') : '<tr><td colspan="5" style="padding:18px;text-align:center;color:#94a3b8;">No new partner suggestions.</td></tr>';
    return `
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-top:16px;">
            <div style="font-size:11px;color:#475569;font-weight:800;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-handshake"></i> Existing Recommended Partners (${partners.length})</div>
            <div style="overflow-x:auto;border:1px solid #e2e8f0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:720px;"><thead><tr style="background:#f1f5f9;">${['Name','Type','Match Basis','Role','Status'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#475569;">${h}</th>`).join('')}</tr></thead><tbody>${partnersHtml}</tbody></table></div>
        </div>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;margin-top:12px;">
            <div style="font-size:11px;color:#15803d;font-weight:800;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-lightbulb"></i> New Partner Capability Recommendations (${suggestions.length})</div>
            <div style="font-size:12px;color:#475569;margin-bottom:8px;font-style:italic;">Capability proposals only. They do not auto-insert partner records.</div>
            <div style="overflow-x:auto;border:1px solid #bbf7d0;border-radius:6px;"><table style="width:100%;border-collapse:collapse;min-width:720px;"><thead><tr style="background:#f0fdf4;">${['Type','Capability','Rationale','Priority','Status'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#166534;">${h}</th>`).join('')}</tr></thead><tbody>${suggestionsHtml}</tbody></table></div>
        </div>`;
}

function renderAiRecStaffTab(body, data) {
    const staff = data?.staffing || data?.staff?.staffing || data?.staff || {};
    const summary = staff.summary || data?.staff?.summary || {};
    const existing = staff.existing_staff || data?.staff?.existing || [];
    const gaps = staff.missing_staff_requirements || data?.staff?.gaps || [];
    const existingRows = existing.length ? existing.map(p => `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">${escapeHtml(p.staff_name || '')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(p.current_role || '')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#0f172a;">${escapeHtml(p.recommended_campaign_role || '')}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;">${p.required_qty || 0}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;">${p.selected_qty || 0}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(p.deployment_location || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;min-width:170px;">${escapeHtml(p.assigned_activity || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(p.match_method || 'Related Role Match')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(p.availability_status || 'Not Recorded')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(p.conflict_status === 'unknown' ? 'Unknown' : p.conflict_status)}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;min-width:180px;">${escapeHtml(p.recommendation_reason || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(p.confirmation_status || 'Pending')}</td>
            <td style="padding:7px 10px;"><button type="button" onclick='openAiStaffingReviewForm(${JSON.stringify({ type: 'existing', role: p.recommended_campaign_role, name: p.staff_name }).replace(/'/g, '&apos;')})' style="border:none;background:#eef2ff;color:#4338ca;border-radius:6px;padding:5px 9px;font-size:11px;font-weight:700;cursor:pointer;">Review</button></td>
        </tr>`).join('') : '<tr><td colspan="13" style="padding:20px;text-align:center;color:#94a3b8;">No existing staff matches generated yet.</td></tr>';
    const gapRows = gaps.length ? gaps.map(g => `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">${escapeHtml(g.required_role || '')}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;">${g.required_qty || 0}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;">${g.existing_matched_qty || 0}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;font-weight:800;color:#dc2626;">${g.missing_qty || 0}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;min-width:170px;">${escapeHtml(g.recommended_campaign_responsibility || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(g.deployment_location || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;min-width:160px;">${escapeHtml(g.required_capability || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;min-width:170px;">${escapeHtml(g.reason_needed || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(g.priority || 'medium')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;">${escapeHtml(g.suggested_source || 'Add new Staff record')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(g.status || 'Pending Review')}</td>
            <td style="padding:7px 10px;"><button type="button" onclick='openAiStaffingReviewForm(${JSON.stringify({ type: 'gap', role: g.required_role, missing: g.missing_qty, source: g.suggested_source }).replace(/'/g, '&apos;')})' style="border:none;background:#fef3c7;color:#92400e;border-radius:6px;padding:5px 9px;font-size:11px;font-weight:700;cursor:pointer;">Create Request</button></td>
        </tr>`).join('') : '<tr><td colspan="12" style="padding:20px;text-align:center;color:#94a3b8;">No missing staff requirements.</td></tr>';

    body.innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(145px,1fr));gap:10px;margin-bottom:16px;">
            ${aiRecSummaryCard('Total Staff Required', summary.total_required ?? 0)}
            ${aiRecSummaryCard('Existing Staff Matched', summary.existing_matched ?? 0, '#166534')}
            ${aiRecSummaryCard('Missing Staff Required', summary.missing ?? 0, Number(summary.missing || 0) > 0 ? '#dc2626' : '#166534')}
            ${aiRecSummaryCard('Confirmed Staff', summary.confirmed ?? 0, '#16a34a')}
            ${aiRecSummaryCard('Roles With Shortages', summary.roles_with_shortages ?? 0, Number(summary.roles_with_shortages || 0) > 0 ? '#ca8a04' : '#166534')}
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:14px;">
            <div style="font-size:11px;color:#475569;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Recommended Staff from Existing Staff (${existing.length})</div>
            <div style="overflow:auto;border:1px solid #e2e8f0;border-radius:6px;max-height:340px;"><table style="width:100%;border-collapse:collapse;min-width:1500px;"><thead><tr style="background:#f1f5f9;">${['Staff Name','Current Role','Recommended Campaign Role','Required QTY','Selected QTY','Deployment Location','Assigned Campaign Activity','Match Method','Availability','Conflict Status','Recommendation Reason','Confirmation Status','Action'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#475569;">${h}</th>`).join('')}</tr></thead><tbody>${existingRows}</tbody></table></div>
        </div>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;">
            <div style="font-size:11px;color:#9a3412;font-weight:800;text-transform:uppercase;margin-bottom:8px;">Recommended Staffing Needs Not Yet Available in Staff (${gaps.length})</div>
            <div style="font-size:12px;color:#9a3412;margin-bottom:8px;">These are staffing gaps, not fictional employees. Actions open a review form and do not create staff automatically.</div>
            <div style="overflow:auto;border:1px solid #fed7aa;border-radius:6px;max-height:340px;"><table style="width:100%;border-collapse:collapse;min-width:1420px;"><thead><tr style="background:#ffedd5;">${['Required Staff Role','Required QTY','Existing Matched QTY','Missing QTY','Recommended Campaign Responsibility','Deployment Location','Required Skills or Capability','Reason Needed','Priority','Suggested Source','Status','Action'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#9a3412;">${h}</th>`).join('')}</tr></thead><tbody>${gapRows}</tbody></table></div>
        </div>
        ${buildAiRecPartnersSection(data)}
    `;
}

function openAiStaffingReviewForm(payload) {
    const existing = document.getElementById('aiStaffingReviewModal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = 'aiStaffingReviewModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:10020;display:flex;align-items:center;justify-content:center;padding:20px;';
    modal.innerHTML = `<div style="background:white;border-radius:10px;max-width:520px;width:100%;box-shadow:0 20px 40px rgba(15,23,42,.25);overflow:hidden;">
        <div style="padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;gap:12px;"><strong style="font-size:14px;color:#0f172a;">Review Staffing ${payload?.type === 'gap' ? 'Request' : 'Assignment'}</strong><button type="button" onclick="document.getElementById('aiStaffingReviewModal')?.remove()" style="border:none;background:transparent;font-size:20px;cursor:pointer;color:#64748b;">&times;</button></div>
        <div style="padding:18px;">
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Role</label>
            <input value="${escapeHtml(payload?.role || '')}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;">
            ${payload?.name ? `<label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Existing Staff</label><input value="${escapeHtml(payload.name)}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;">` : ''}
            ${payload?.missing ? `<label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Missing QTY</label><input value="${escapeHtml(payload.missing)}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;">` : ''}
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Review Notes</label>
            <textarea placeholder="Add notes before creating an official staff record or request..." style="width:100%;min-height:90px;padding:9px;border:1px solid #e2e8f0;border-radius:6px;resize:vertical;"></textarea>
            <div style="font-size:12px;color:#64748b;margin-top:10px;">This form is review-only in the AI recommendation modal. It does not automatically create fake staff records.</div>
        </div>
        <div style="padding:12px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:right;"><button type="button" onclick="document.getElementById('aiStaffingReviewModal')?.remove()" style="border:none;background:#4c8a89;color:white;border-radius:7px;padding:8px 14px;font-weight:700;cursor:pointer;">Done</button></div>
    </div>`;
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    document.body.appendChild(modal);
}

window.openAiStaffingReviewForm = openAiStaffingReviewForm;

function renderAiRecEventsTab(body, data) {
    const events = data?.events || {};
    const summary = events.summary || {};
    const existing = events.existing_events || [];
    const related = events.related_events || [];
    const recommended = events.recommended_events || [];

    const existingRows = existing.length ? existing.map(e => `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">#${e.event_id}</td>
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">${escapeHtml(e.event_title || 'Untitled')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(e.event_type || 'seminar')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.event_date || 'No date')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml([e.start_time, e.end_time].filter(Boolean).join(' - ') || 'No time')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.venue || e.location || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(e.event_status || 'scheduled')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;">${escapeHtml(e.match_basis || '')}</td>
        </tr>
    `).join('') : '<tr><td colspan="8" style="padding:18px;text-align:center;color:#94a3b8;">No existing linked Events & Seminars records found for this campaign yet.</td></tr>';

    const relatedRows = related.length ? related.map(e => `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">#${e.event_id}</td>
            <td style="padding:7px 10px;font-size:12px;font-weight:700;color:#0f172a;">${escapeHtml(e.event_title || 'Untitled')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(e.event_type || 'seminar')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.hazard_focus || e.event_description || '')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.event_date || 'No date')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.venue || e.location || '')}</td>
            <td style="padding:7px 10px;text-align:right;font-size:12px;color:#475569;">${e.match_score || 0}</td>
        </tr>
    `).join('') : '<tr><td colspan="7" style="padding:18px;text-align:center;color:#94a3b8;">No related existing events found from the Events & Seminars table.</td></tr>';

    const recommendedRows = recommended.length ? recommended.map(e => {
        const encoded = encodeURIComponent(JSON.stringify(e));
        return `
        <tr style="border-bottom:1px solid #f1f5f9;">
            <td style="padding:7px 10px;font-size:11px;color:#64748b;">${e.sequence || ''}</td>
            <td style="padding:7px 10px;font-size:12px;font-weight:800;color:#0f172a;min-width:190px;">${escapeHtml(e.event_title || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(e.event_type || 'seminar')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.recommended_date || '')}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml([e.start_time, e.end_time].filter(Boolean).join(' - '))}</td>
            <td style="padding:7px 10px;font-size:12px;color:#475569;">${escapeHtml(e.recommended_venue_or_location || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;min-width:190px;">${escapeHtml(e.target_audience || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#475569;min-width:210px;">${escapeHtml(e.recommended_campaign_action || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;min-width:180px;">${escapeHtml(e.trainer_requirements || '')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;min-width:180px;">${escapeHtml(e.equipment_requirements || '')}</td>
            <td style="padding:7px 10px;">${aiRecBadge(e.priority || 'medium')}</td>
            <td style="padding:7px 10px;font-size:11px;color:#64748b;min-width:220px;">${escapeHtml(e.recommendation_reason || '')}</td>
            <td style="padding:7px 10px;"><button type="button" onclick="openAiEventReviewFormFromEncoded('${encoded}')" style="border:none;background:#eef2ff;color:#4338ca;border-radius:6px;padding:5px 9px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">Review Event</button></td>
        </tr>`;
    }).join('') : '<tr><td colspan="13" style="padding:18px;text-align:center;color:#94a3b8;">No AI event recommendations generated yet.</td></tr>';

    body.innerHTML = `
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(165px,1fr));gap:10px;margin-bottom:16px;">
            ${aiRecSummaryCard('Events Table', summary.table_available ? 'Available' : 'Unavailable', summary.table_available ? '#166534' : '#dc2626')}
            ${aiRecSummaryCard('Linked Existing Events', summary.existing_linked_events ?? 0, '#0f766e')}
            ${aiRecSummaryCard('Related Existing Events', summary.related_existing_events ?? 0, '#667eea')}
            ${aiRecSummaryCard('AI Recommended Events', summary.ai_recommended_events ?? 0, '#ca8a04')}
        </div>
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px;margin-bottom:14px;font-size:12px;color:#0c4a6e;">
            ${escapeHtml(events.note || 'AI recommended events are planning proposals only and are not auto-created.')}
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:14px;">
            <div style="font-size:11px;color:#475569;font-weight:800;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-calendar-check"></i> Existing Events & Seminars Linked to This Campaign (${existing.length})</div>
            <div style="overflow:auto;border:1px solid #e2e8f0;border-radius:6px;max-height:260px;"><table style="width:100%;border-collapse:collapse;min-width:880px;"><thead><tr style="background:#f1f5f9;">${['ID','Event / Seminar','Type','Date','Time','Venue / Location','Status','Match Basis'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#475569;">${h}</th>`).join('')}</tr></thead><tbody>${existingRows}</tbody></table></div>
        </div>
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:14px;margin-bottom:14px;">
            <div style="font-size:11px;color:#475569;font-weight:800;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-magnifying-glass"></i> Related Existing Events from Events & Seminars Table (${related.length})</div>
            <div style="overflow:auto;border:1px solid #e2e8f0;border-radius:6px;max-height:260px;"><table style="width:100%;border-collapse:collapse;min-width:850px;"><thead><tr style="background:#f8fafc;">${['ID','Event / Seminar','Type','Focus / Description','Date','Venue / Location','Match Score'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#475569;">${h}</th>`).join('')}</tr></thead><tbody>${relatedRows}</tbody></table></div>
        </div>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:14px;">
            <div style="font-size:11px;color:#9a3412;font-weight:800;text-transform:uppercase;margin-bottom:8px;"><i class="fas fa-lightbulb"></i> AI Recommended Events & Seminars for This Campaign (${recommended.length})</div>
            <div style="font-size:12px;color:#9a3412;margin-bottom:8px;">These recommendations are based on the campaign actions, affected locations, target audience, priority, duration, and supporting reports. Review first before creating an official Events & Seminars record.</div>
            <div style="overflow:auto;border:1px solid #fed7aa;border-radius:6px;max-height:420px;"><table style="width:100%;border-collapse:collapse;min-width:1650px;"><thead><tr style="background:#ffedd5;">${['#','Recommended Event / Seminar','Type','Suggested Date','Time','Venue / Location','Target Audience','Related Campaign Action','Trainer Requirements','Equipment Requirements','Priority','Reason','Action'].map(h => `<th style="padding:7px 10px;text-align:left;font-size:11px;color:#9a3412;">${h}</th>`).join('')}</tr></thead><tbody>${recommendedRows}</tbody></table></div>
        </div>
    `;
}

function openAiEventReviewFormFromEncoded(encoded) {
    try {
        openAiEventReviewForm(JSON.parse(decodeURIComponent(encoded)));
    } catch (e) {
        showErrorToast('Unable to open event review data.');
    }
}

function openAiEventReviewForm(eventData) {
    const existing = document.getElementById('aiEventReviewModal');
    if (existing) existing.remove();
    const modal = document.createElement('div');
    modal.id = 'aiEventReviewModal';
    modal.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.45);z-index:10025;display:flex;align-items:center;justify-content:center;padding:20px;';
    modal.innerHTML = `<div style="background:white;border-radius:10px;max-width:620px;width:100%;box-shadow:0 20px 40px rgba(15,23,42,.25);overflow:hidden;">
        <div style="padding:14px 18px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;gap:12px;"><strong style="font-size:14px;color:#0f172a;">Review AI Recommended Event</strong><button type="button" onclick="document.getElementById('aiEventReviewModal')?.remove()" style="border:none;background:transparent;font-size:20px;cursor:pointer;color:#64748b;">&times;</button></div>
        <div style="padding:18px;max-height:70vh;overflow:auto;">
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Event / Seminar Title</label>
            <input value="${escapeHtml(eventData?.event_title || '')}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div><label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Type</label><input value="${escapeHtml(eventData?.event_type || '')}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;"></div>
                <div><label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Suggested Date</label><input value="${escapeHtml(eventData?.recommended_date || '')}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;"></div>
            </div>
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Venue / Location</label>
            <input value="${escapeHtml(eventData?.recommended_venue_or_location || '')}" readonly style="width:100%;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;">
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Objective</label>
            <textarea readonly style="width:100%;min-height:70px;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;resize:vertical;">${escapeHtml(eventData?.objective || '')}</textarea>
            <label style="display:block;font-size:11px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px;">Requirements</label>
            <textarea readonly style="width:100%;min-height:90px;padding:9px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:10px;resize:vertical;">Trainer: ${escapeHtml(eventData?.trainer_requirements || '')}\nEquipment: ${escapeHtml(eventData?.equipment_requirements || '')}\nVolunteers: ${escapeHtml(eventData?.volunteer_requirements || '')}</textarea>
            <div style="font-size:12px;color:#64748b;">Review-only modal. To make this official, create it from the Events & Seminars module using these details.</div>
        </div>
        <div style="padding:12px 18px;background:#f8fafc;border-top:1px solid #e2e8f0;text-align:right;"><button type="button" onclick="document.getElementById('aiEventReviewModal')?.remove()" style="border:none;background:#4c8a89;color:white;border-radius:7px;padding:8px 14px;font-weight:700;cursor:pointer;">Done</button></div>
    </div>`;
    modal.addEventListener('click', e => { if (e.target === modal) modal.remove(); });
    document.body.appendChild(modal);
}

window.openAiEventReviewFormFromEncoded = openAiEventReviewFormFromEncoded;
window.openAiEventReviewForm = openAiEventReviewForm;

function updateAiRecPlanAction(rec) {
    const data = window.__aiRecDetail || {};
    const summary = data.summary || {};
    const button = document.getElementById('aiRecGeneratePlanBtn');
    const acceptBtn = document.getElementById('aiRecAcceptBtn');
    const status = document.getElementById('aiRecPlanStatus');
    const planStatus = summary.planning_status || rec.planning_status || 'not_generated';
    const generatedBy = summary.generated_by || rec.generated_by || 'rule-based';
    const hasGeneratedData = ['completed', 'completed_with_warnings'].includes(planStatus);
    const convertedCampaignId = summary.converted_campaign_id || rec.converted_campaign_id || null;

    if (button) {
        button.disabled = false;
        button.style.opacity = '1';
        button.style.cursor = 'pointer';
        button.innerHTML = hasGeneratedData
            ? '<i class="fas fa-sync-alt"></i> Recalculate Campaign Plan'
            : '<i class="fas fa-magic"></i> Generate Campaign Plan';
    }

    if (acceptBtn) {
        if (convertedCampaignId) {
            acceptBtn.disabled = true;
            acceptBtn.style.opacity = '0.55';
            acceptBtn.style.cursor = 'not-allowed';
            acceptBtn.style.background = '#16a34a';
            acceptBtn.innerHTML = '<i class="fas fa-check-double"></i> Accepted (Campaign #' + convertedCampaignId + ')';
        } else {
            acceptBtn.disabled = false;
            acceptBtn.style.opacity = '1';
            acceptBtn.style.cursor = 'pointer';
            acceptBtn.style.background = '#16a34a';
            acceptBtn.innerHTML = '<i class="fas fa-check-circle"></i> Accept Recommended';
        }
    }

    if (status) {
        const label = planStatus.replace(/_/g, ' ');
        const acceptedLabel = convertedCampaignId ? ' | Accepted: Campaign #' + convertedCampaignId : '';
        status.textContent = 'Generated by ' + generatedBy + ' | Planning: ' + label + acceptedLabel;
    }
}

async function readApiError(response) {
    try {
        const payload = await response.json();
        return payload.error || payload.message || JSON.stringify(payload);
    } catch (e) {
        try {
            return await response.text();
        } catch (inner) {
            return response.statusText || 'Request failed';
        }
    }
}

async function reloadAiRecommendationDetails(rec) {
    const detailsUrl = aiRecommendationDetailsUrl + '?id=' + (rec.id || 0) + '&_=' + Date.now();
    const resp = await fetch(detailsUrl, { headers: { 'Accept': 'application/json' } });
    if (!resp.ok) {
        throw new Error(await readApiError(resp));
    }
    const payload = await resp.json();
    window.__aiRecDetail = payload?.data || null;
    delete window.__aiRecDetailError;
    updateAiRecPlanAction(rec);
    renderAiRecTab(window.__aiRecActiveTab || 'overview', rec);
}

async function generateAiRecommendationPlan() {
    const active = window.__aiRecActive || {};
    const rec = active.rec || {};
    const data = window.__aiRecDetail || {};
    const summary = data.summary || {};
    const recommendationId = summary.id || rec.id;
    const button = document.getElementById('aiRecGeneratePlanBtn');
    const status = document.getElementById('aiRecPlanStatus');

    if (!recommendationId) {
        showErrorToast('Planning generation failed: missing recommendation ID.');
        return;
    }

    const token = localStorage.getItem('jwtToken') || '';
    if (!token.trim()) {
        showErrorToast('Planning generation failed: unauthorized request. Please log in again.');
        return;
    }

    const action = ['completed', 'completed_with_warnings', 'processing'].includes(summary.planning_status || rec.planning_status)
        ? 'recalculate'
        : 'generate';

    try {
        if (button) {
            button.disabled = true;
            button.style.opacity = '0.7';
            button.style.cursor = 'wait';
            button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Generating...';
        }
        if (status) {
            status.textContent = 'Generating campaign plan...';
        }

        const resp = await fetch(aiRecommendationGenerateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token.trim()
            },
            body: JSON.stringify({ recommendation_id: recommendationId, action })
        });

        if (!resp.ok) {
            throw new Error(await readApiError(resp));
        }

        const payload = await resp.json();
        if (payload.error || payload.success === false) {
            throw new Error(payload.error || payload.message || 'Planning generation failed.');
        }

        await reloadAiRecommendationDetails(rec);
        showSuccessToast(action === 'recalculate' ? 'Campaign plan recalculated.' : 'Campaign plan generated.');
    } catch (e) {
        const message = e.message || 'Planning generation failed.';
        showErrorToast('Planning generation failed: ' + message);
        if (status) {
            status.textContent = 'Planning generation failed: ' + message;
        }
        updateAiRecPlanAction(rec);
    }
}

async function acceptAiRecommendation() {
    const active = window.__aiRecActive || {};
    const rec = active.rec || {};
    const data = window.__aiRecDetail || {};
    const summary = data.summary || {};
    const recommendationId = summary.id || rec.id;
    const acceptBtn = document.getElementById('aiRecAcceptBtn');
    const status = document.getElementById('aiRecPlanStatus');

    if (!recommendationId) {
        showErrorToast('Acceptance failed: missing recommendation ID.');
        return;
    }

    if (summary.converted_campaign_id || rec.converted_campaign_id) {
        showWarningToast('This recommendation has already been accepted.');
        updateAiRecPlanAction(rec);
        return;
    }

    const token = localStorage.getItem('jwtToken') || '';
    if (!token.trim()) {
        showErrorToast('Acceptance failed: unauthorized request. Please log in again.');
        return;
    }

    const budgetCount = (data.budget && data.budget.items ? data.budget.items.length : 0);
    const participants = Array.isArray(data.staff && data.staff.participants) ? data.staff.participants : [];
    const staffToCreate = participants.filter(p => !p.staff_id && (p.missing_qty || 0) > 0).length;
    const phaseCount = (data.schedule && Array.isArray(data.schedule.phases) ? data.schedule.phases.length : 0);
    const suggestions = Array.isArray(data.suggestions) ? data.suggestions.filter(s => s.proposal_status === 'proposed' && !s.created_partner_id) : [];
    const partnerCount = (Array.isArray(data.partners) ? data.partners.length : 0) + suggestions.length;

    const title = summary.campaign_title || rec.campaign_title || 'this recommendation';
    const ok = confirm(
        'Accept this AI recommendation?\n\n' +
        'Campaign: "' + title + '"\n\n' +
        'This will insert:\n' +
        '- ' + budgetCount + ' budget line item(s) into Financial & Budgeting\n' +
        '- ' + staffToCreate + ' missing staff member(s) into the Staff roster\n' +
        '- ' + phaseCount + ' event(s)/seminar(s) into Events & Seminars\n' +
        '- ' + partnerCount + ' partner(s) into the Partners list\n\n' +
        'The campaign will be created in All Campaigns with Draft status. Continue?'
    );
    if (!ok) return;

    try {
        if (acceptBtn) {
            acceptBtn.disabled = true;
            acceptBtn.style.opacity = '0.7';
            acceptBtn.style.cursor = 'wait';
            acceptBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Accepting...';
        }
        if (status) {
            status.textContent = 'Accepting recommendation and creating campaign...';
        }

        const resp = await fetch(aiRecommendationAcceptUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token.trim()
            },
            body: JSON.stringify({ recommendation_id: recommendationId })
        });

        if (!resp.ok) {
            throw new Error(await readApiError(resp));
        }

        const payload = await resp.json();
        if (payload.error || payload.success === false) {
            throw new Error(payload.error || payload.message || 'Acceptance failed.');
        }

        await reloadAiRecommendationDetails(rec);
        updateAiRecPlanAction(rec);

        showSuccessToast(
            'Accepted! Campaign #' + payload.campaign_id + ' created with ' +
            (payload.budget_items_created || 0) + ' budget items, ' +
            (payload.staff_created || 0) + ' staff, ' +
            (payload.events_created || 0) + ' events, ' +
            (payload.partners_created || 0) + ' partners.'
        );

        try { await loadCampaigns(); } catch (e) { console.warn('Campaign list refresh failed:', e); }
        try { await loadBudgetData(); } catch (e) { console.warn('Budget list refresh failed:', e); }
        try { await loadStaffTable(); } catch (e) { console.warn('Staff list refresh failed:', e); }
    } catch (e) {
        const message = e.message || 'Acceptance failed.';
        showErrorToast('Acceptance failed: ' + message);
        if (status) {
            status.textContent = 'Acceptance failed: ' + message;
        }
        updateAiRecPlanAction(rec);
    }
}

async function backfillAiRecommendationPlans() {
    const button = document.getElementById('aiRecBackfillBtn');
    const token = localStorage.getItem('jwtToken') || '';
    if (!token.trim()) {
        showErrorToast('Planning generation failed: unauthorized request. Please log in again.');
        return;
    }

    try {
        if (button) {
            button.disabled = true;
            button.style.opacity = '0.7';
            button.innerHTML = '<i class="fas fa-spinner fa-pulse"></i> Generating...';
        }

        const resp = await fetch(aiRecommendationGenerateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + token.trim()
            },
            body: JSON.stringify({ action: 'backfill_missing' })
        });

        if (!resp.ok) {
            throw new Error(await readApiError(resp));
        }
        const payload = await resp.json();
        if (payload.error || payload.success === false) {
            throw new Error(payload.error || payload.message || 'Backfill failed.');
        }

        showSuccessToast('Generated missing plans for ' + (payload.processed || 0) + ' recommendations.');
        await loadAiRecommendations(false);
    } catch (e) {
        showErrorToast('Planning generation failed: ' + (e.message || 'Backfill failed.'));
    } finally {
        if (button) {
            button.disabled = false;
            button.style.opacity = '1';
            button.innerHTML = '<i class="fas fa-magic"></i> Generate Missing Plans';
        }
    }
}

window.generateAiRecommendationPlan = generateAiRecommendationPlan;
window.backfillAiRecommendationPlans = backfillAiRecommendationPlans;
window.acceptAiRecommendation = acceptAiRecommendation;

function closeAiRecommendationModal() {
    const modal = document.getElementById('aiRecommendationModal');
    if (modal) modal.remove();
    delete window.__aiRecDetail;
    delete window.__aiRecDetailError;
    delete window.__aiRecActive;
    delete window.__aiRecActiveTab;
}

// Toast Notification Functions
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : type === 'warning' ? '⚠' : 'ℹ';
    toast.innerHTML = `<span style="font-size: 18px;">${icon}</span><span>${message}</span>`;
    
    container.appendChild(toast);
    
    // Auto-remove after 4 seconds
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => {
            container.removeChild(toast);
        }, 300);
    }, 4000);
}

function showSuccessToast(message) {
    showToast(message, 'success');
}

function showErrorToast(message) {
    showToast(message, 'error');
}

function showWarningToast(message) {
    showToast(message, 'warning');
}

function showInfoToast(message) {
    showToast(message, 'info');
}

// Function to get fresh token from localStorage
function getToken() {
    try {
        const token = localStorage.getItem('jwtToken') || '';
        // TASK 4: PROVE TOKEN PRESENCE
        console.log('=== TASK 4 PROOF: getToken() called ===');
        console.log('TASK 4: localStorage.getItem("jwtToken") result:', token ? 'EXISTS (length: ' + token.length + ')' : 'NULL/EMPTY');
        console.log('TASK 4: Token value (first 20 chars):', token ? token.substring(0, 20) + '...' : 'N/A');
        console.log('TASK 4: Token after trim:', token ? token.trim().substring(0, 20) + '...' : 'N/A');
        if (!token || token.trim() === '') {
            console.warn('=== TASK 4 PROOF: No token found ===');
            console.warn('TASK 4: localStorage keys:', Object.keys(localStorage));
            return '';
        }
        const trimmedToken = token.trim();
        console.log('=== TASK 4 PROOF: Token returned (length: ' + trimmedToken.length + ') ===');
        return trimmedToken;
    } catch (e) {
        console.error('=== TASK 4 PROOF: Error reading localStorage ===', e);
        return '';
    }
}


let calendar;
let activeCampaignId = null;
let allCampaigns = [];

// RBAC: Get user role for UI visibility (LGU Governance Workflow)
let currentUserRole = null;
let currentUserRoleId = null;
(function() {
    try {
        const currentUserStr = localStorage.getItem('currentUser');
        if (currentUserStr) {
            const currentUser = JSON.parse(currentUserStr);
            currentUserRole = (currentUser && currentUser.role) ? currentUser.role.toLowerCase() : null;
            currentUserRoleId = (currentUser && currentUser.role_id) ? currentUser.role_id : null;
            
            // If role not in user object, try to decode from JWT
            if (!currentUserRole) {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    try {
                        const parts = token.split('.');
                        if (parts.length === 3) {
                            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                            currentUserRole = payload.role ? payload.role.toLowerCase() : null;
                            currentUserRoleId = payload.role_id || payload.rid || null;
                        }
                    } catch (e) {
                        console.error('RBAC: Failed to decode JWT for role check:', e);
                    }
                }
            }
        } else {
            // Try to get role from JWT directly
            const token = localStorage.getItem('jwtToken');
            if (token) {
                try {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        currentUserRole = payload.role ? payload.role.toLowerCase() : null;
                        currentUserRoleId = payload.role_id || payload.rid || null;
                    }
                } catch (e) {
                    console.error('RBAC: Failed to decode JWT for role check:', e);
                }
            }
        }
        
        // Normalize role name (map legacy roles to LGU governance roles)
        // Make it case-insensitive by converting to lowercase first
        if (currentUserRole) {
            const roleLower = currentUserRole.toLowerCase().trim();
            const roleMappings = {
                'partner': 'viewer',
                'partner representative': 'viewer',
                'partner_representative': 'viewer',
                'viewer': 'viewer',
                'staff': 'staff',
                'secretary': 'secretary',
                'kagawad': 'kagawad',
                'captain': 'captain',
                'admin': 'admin',
                'barangay administrator': 'admin',
                'barangay admin': 'admin',
                'barangay staff': 'staff',
                'system_admin': 'admin',
                'barangay_admin': 'admin',
                'barangayadministrator': 'admin', // Handle no-space variant
            };
            currentUserRole = roleMappings[roleLower] || currentUserRole;
        }
        
        // If we have role_id but no role name, try to map it
        if (!currentUserRole && currentUserRoleId) {
            const roleIdMap = {
                1: 'admin',
                2: 'staff',
                3: 'viewer',
                4: 'viewer',
                5: 'admin',
            };
            currentUserRole = roleIdMap[currentUserRoleId] || null;
            console.log('RBAC: Mapped role_id', currentUserRoleId, 'to role', currentUserRole);
        }
        
        console.log('RBAC: Detected role =', currentUserRole, ', roleId =', currentUserRoleId);
    } catch (e) {
        console.error('RBAC: Error checking user role:', e);
    }
})();

// Function to refresh role detection (call this when needed)
function refreshRoleDetection() {
    try {
        const currentUserStr = localStorage.getItem('currentUser');
        if (currentUserStr) {
            const currentUser = JSON.parse(currentUserStr);
            currentUserRole = currentUser.role ? currentUser.role.toLowerCase() : null;
            currentUserRoleId = currentUser.role_id || null;
            
            // If role not in user object, try to decode from JWT
            if (!currentUserRole) {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    try {
                        const parts = token.split('.');
                        if (parts.length === 3) {
                            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                            currentUserRole = payload.role ? payload.role.toLowerCase() : null;
                            currentUserRoleId = payload.role_id || payload.rid || null;
                        }
                    } catch (e) {
                        console.error('RBAC: Failed to decode JWT for role check:', e);
                    }
                }
            }
        } else {
            // Try to get role from JWT directly
            const token = localStorage.getItem('jwtToken');
            if (token) {
                try {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        currentUserRole = payload.role ? payload.role.toLowerCase() : null;
                        currentUserRoleId = payload.role_id || payload.rid || null;
                    }
                } catch (e) {
                    console.error('RBAC: Failed to decode JWT for role check:', e);
                }
            }
        }
        
        // If we have role_id but no role name, try to map it
        if (!currentUserRole && currentUserRoleId) {
            const roleIdMap = {
                1: 'admin',
                2: 'staff',
                3: 'viewer',
                4: 'viewer',
                5: 'admin',
            };
            currentUserRole = roleIdMap[currentUserRoleId] || null;
        }
        
        // Normalize role name (map legacy roles to LGU governance roles)
        // Make it case-insensitive by converting to lowercase first
        if (currentUserRole) {
            const roleLower = currentUserRole.toLowerCase().trim();
            const roleMappings = {
                'partner': 'viewer',
                'partner representative': 'viewer',
                'partner_representative': 'viewer',
                'viewer': 'viewer',
                'staff': 'staff',
                'secretary': 'secretary',
                'kagawad': 'kagawad',
                'captain': 'captain',
                'admin': 'admin',
                'barangay administrator': 'admin',
                'barangay admin': 'admin',
                'barangay staff': 'staff',
                'system_admin': 'admin',
                'barangay_admin': 'admin',
                'barangayadministrator': 'admin', // Handle no-space variant
            };
            currentUserRole = roleMappings[roleLower] || currentUserRole;
        }
        
        console.log('RBAC: Refreshed role detection - role =', currentUserRole, ', roleId =', currentUserRoleId);
        return currentUserRole;
    } catch (e) {
        console.error('RBAC: Error refreshing role detection:', e);
        return null;
    }
}

// RBAC Helper Functions (LGU Governance Workflow)
function isViewer() {
    if (!currentUserRole) {
        // Fallback: Check localStorage for currentUser
        try {
            const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
            const userRole = (currentUser && currentUser.role) ? currentUser.role.toLowerCase() : '';
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
    const role = currentUserRole.toLowerCase();
    return role === 'viewer' || 
           role === 'partner' || 
           role === 'partner representative' ||
           role === 'partner_representative' ||
           role.includes('partner') ||
           role.includes('viewer');
}

function isStaff() {
    return currentUserRole === 'staff';
}

function isSecretary() {
    return currentUserRole === 'secretary';
}

function isKagawad() {
    return currentUserRole === 'kagawad';
}

function isCaptain() {
    return currentUserRole === 'captain';
}

function isAdmin() {
    // Check multiple admin role variations
    if (!currentUserRole) return false;
    const role = currentUserRole.toLowerCase();
    return role === 'admin' || 
           role === 'system_admin' || 
           role === 'barangay_admin' ||
           role === 'barangay administrator' ||
           role === 'barangay admin' ||
           (currentUserRoleId && (currentUserRoleId === 1 || currentUserRoleId === 5)); // Common admin role IDs
}

// Check if user can create campaigns
function canCreateCampaign() {
    return !isViewer() && (isStaff() || isSecretary() || isKagawad() || isCaptain() || isAdmin());
}

// Check if user can edit campaigns
function canEditCampaign(campaignStatus) {
    if (isViewer()) return false;
    if (isAdmin()) return true;
    
    const status = (campaignStatus || '').toLowerCase();
    
    // Staff: Can only edit drafts they created
    if (isStaff()) {
        return status === 'draft';
    }
    
    // Secretary: Can edit drafts and pending campaigns
    if (isSecretary()) {
        return status === 'draft' || status === 'pending';
    }
    
    // Kagawad: Can view but cannot edit (read-only reviewer)
    if (isKagawad()) {
        return false;
    }
    
    // Captain: Can edit all campaigns
    if (isCaptain()) {
        return true;
    }
    
    return false;
}

// Check if user can approve campaigns (Draft → Pending or Pending → Approved)
function canApproveCampaign(campaignStatus) {
    if (isViewer() || isStaff() || isKagawad()) return false;
    if (isAdmin()) return true;
    
    const status = (campaignStatus || '').toLowerCase();
    
    // Secretary: Can forward Draft → Pending (not final approval)
    if (isSecretary()) {
        return status === 'draft';
    }
    
    // Captain: Can approve Pending → Approved (final authority)
    if (isCaptain()) {
        return status === 'pending';
    }
    
    return false;
}

// Check if user can finalize schedules
function canFinalizeSchedule() {
    return isCaptain() || isAdmin();
}

// Check if user can access AI Scheduler
function canAccessAIScheduler() {
    return !isViewer() && (isStaff() || isSecretary() || isKagawad() || isCaptain() || isAdmin());
}

// RBAC: Hide action buttons/forms for Viewer role (read-only)
document.addEventListener('DOMContentLoaded', function() {
    try {
        const currentUserStr = localStorage.getItem('currentUser');
        if (currentUserStr) {
            const currentUser = JSON.parse(currentUserStr);
            // Try to get role from user object or decode from JWT
            let userRole = (currentUser && currentUser.role) ? currentUser.role.toLowerCase() : null;
            
            // If role not in user object, try to decode from JWT
            if (!userRole) {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    try {
                        const parts = token.split('.');
                        if (parts.length === 3) {
                            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                            userRole = payload.role ? payload.role.toLowerCase() : null;
                        }
                    } catch (e) {
                        console.error('RBAC: Failed to decode JWT for role check:', e);
                    }
                }
            }
            
            // Get roleId from JWT for better detection
            let roleId = null;
            if (!userRole) {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    try {
                        const parts = token.split('.');
                        if (parts.length === 3) {
                            const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                            roleId = payload.role_id || payload.rid;
                            if (!userRole) userRole = payload.role ? payload.role.toLowerCase() : null;
                        }
                    } catch (e) {}
                }
            }
            if (!roleId && currentUser && currentUser.role_id) {
                roleId = currentUser.role_id;
            } else if (!roleId) {
                console.warn('Role ID not found in user object or JWT');
            }
            
            // Check if Viewer/Partner (multiple checks for robustness)
            const isViewer = userRole === 'viewer' || 
                            userRole === 'partner' || 
                            userRole === 'partner representative' ||
                            roleId === 6 || // Adjust if Partner/Viewer has different role_id
                            (userRole && (userRole.includes('partner') || userRole.includes('viewer')));
            
            if (isViewer) {
                console.log('RBAC: Viewer/Partner detected (role:', userRole, 'roleId:', roleId, ') - hiding forms and redirecting to list');
                
                // Hide planning section (create form)
                const planningSection = document.getElementById('planning-section');
                if (planningSection) {
                    planningSection.style.display = 'none';
                    planningSection.remove(); // Remove from DOM entirely
                }
                
                // Hide AutoML section (management tool)
                const automlSection = document.getElementById('automl-section');
                if (automlSection) {
                    automlSection.style.display = 'none';
                    automlSection.remove(); // Remove from DOM entirely
                }
                
                // Aggressively hide ALL create/edit buttons
                document.querySelectorAll('button, a.btn, .btn-primary, .btn-secondary').forEach(btn => {
                    const text = btn.textContent.toLowerCase();
                    if (text.includes('create') || text.includes('add') || text.includes('edit') || 
                        text.includes('delete') || text.includes('approve') || text.includes('schedule') ||
                        text.includes('upload') || text.includes('manage')) {
                        btn.style.display = 'none';
                        btn.remove(); // Remove from DOM entirely
                    }
                });
                
                // Auto-scroll to list section (read-only view)
                setTimeout(() => {
                    const listSection = document.getElementById('list-section');
                    if (listSection) {
                        listSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        window.location.hash = 'list-section';
                    }
                }, 100);
            }
        }
    } catch (e) {
        console.error('RBAC: Error checking user role for UI visibility:', e);
    }
});

// Sample data for quick campaign creation (used as local combobox options)
const SAMPLE_CAMPAIGN_TITLES = [
    'Fire Safety Awareness Week',
    'Earthquake Drill and Preparedness Campaign',
    'Flood Preparedness and Evacuation Planning',
    'Road Safety for Students',
    'Dengue Prevention and Clean-Up Drive',
    'Health & Wellness: Vaccination Drive',
    'Community Disaster Preparedness Orientation',
];

// Real Quezon City Barangays (16 official barangays - Quezon City ONLY)
const SAMPLE_BARANGAYS = [
    'Barangay Batasan Hills',
    'Barangay Commonwealth',
    'Barangay Holy Spirit',
    'Barangay Payatas',
    'Barangay Bagong Silangan',
    'Barangay Tandang Sora',
    'Barangay UP Campus',
    'Barangay Diliman',
    'Barangay Matandang Balara',
    'Barangay Loyola Heights',
    'Barangay Cubao',
    'Barangay Kamuning',
    'Barangay Project 6',
    'Barangay Project 8',
    'Barangay Fairview',
    'Barangay Nagkaisang Nayon',
];

// Real Quezon City Barangay Target Zones (sub-areas for planning and deployment)
const SAMPLE_BARANGAY_ZONES = [
    'Sitio Veterans Village (Batasan Hills)',
    'IBP Road Area (Batasan Hills)',
    'Don Antonio Heights (Commonwealth)',
    'Litex Area (Commonwealth)',
    'North Fairview Subdivision',
    'Fairview Center Mall Area',
    'UP Academic Oval Area',
    'Teachers Village East',
    'Teachers Village West',
    'Araneta City Cubao Area',
    'Kamias–E. Rodriguez Area',
    'Balara Filters Area',
    'Payatas A Proper',
    'Payatas B Proper',
    'Novaliches Proper',
    'Nagkaisang Nayon',
];

const SAMPLE_LOCATIONS = [
    'Barangay Hall',
    'Covered Court',
    'Barangay Gymnasium',
    'Elementary School Grounds',
    'High School Auditorium',
    'Multi-purpose Hall',
    'Community Center',
];

const SAMPLE_STAFF = [
    'Barangay Captain',
    'Barangay Health Worker',
    'Barangay Tanod',
    'SK Chairperson',
    'DRRM Officer',
    'School Principal',
    'NGO Partner Volunteer',
];

const SAMPLE_MATERIALS = [
    'Tarpaulin (3x6 ft)',
    'Leaflets / Flyers',
    'Megaphone',
    'First Aid Kit',
    'Projector and Screen',
    'Sound System',
    'Emergency Go Bag Sample',
];

// Populate native select options (For standard dropdowns only)
function populateStandardSelect(selectId, options) {
    const select = document.getElementById(selectId);
    if (!select || !Array.isArray(options)) return;
    
    // Clear existing options except the first placeholder
    const placeholder = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (placeholder) {
        select.appendChild(placeholder);
    }
    
    // Add options
    options.forEach(option => {
        const optionEl = document.createElement('option');
        optionEl.value = option;
        optionEl.textContent = option;
        select.appendChild(optionEl);
    });
}


// Multi-Select Component (For Assigned Staff & Materials only)
// Uses native <select multiple> with inline behavior
function initMultiSelectEnhanced(selectId, options = {}) {
    const select = document.getElementById(selectId);
    if (!select) {
        console.warn('MultiSelectEnhanced: Element not found', selectId);
        return;
    }
    
    // Get tags container
    let tagsDiv = null;
    if (selectId === 'assigned_staff') {
        tagsDiv = document.getElementById('assigned_staff_tags');
    } else if (selectId === 'materials_json') {
        tagsDiv = document.getElementById('materials_json_tags');
    }
    
    if (!tagsDiv) {
        console.warn('MultiSelectEnhanced: Tags container not found', selectId);
        return;
    }
    
    // Mark as initialized
    select.dataset.multiSelectInit = 'true';
    console.log('MultiSelectEnhanced initialized for:', selectId);

    const staticOptions = Array.isArray(options.staticOptions) ? options.staticOptions : [];
    
    // Populate options
    staticOptions.forEach(option => {
        const optionEl = document.createElement('option');
        optionEl.value = option;
        optionEl.textContent = option;
        select.appendChild(optionEl);
    });
    
    // Update tags when selection changes
    function updateTags() {
        if (!tagsDiv) return;
        
        const selectedOptions = Array.from(select.selectedOptions);
        tagsDiv.innerHTML = '';
        
        selectedOptions.forEach(option => {
            const tag = document.createElement('div');
            tag.className = 'multi-select-tag';
            tag.innerHTML = `
                <span>${option.textContent}</span>
                <span class="multi-select-tag-remove" data-value="${option.value}">×</span>
            `;
            tag.querySelector('.multi-select-tag-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                option.selected = false;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                updateTags();
            });
            tagsDiv.appendChild(tag);
        });
    }
    
    // Handle multiple selection - allow single click to toggle (not replace)
    select.addEventListener('mousedown', function(e) {
        const option = e.target;
        if (option.tagName === 'OPTION') {
            // If Ctrl/Cmd is held, allow default behavior (toggle)
            if (e.ctrlKey || e.metaKey) {
                return; // Let browser handle it
            }
            // For single click, toggle the selection without deselecting others
            e.preventDefault();
            option.selected = !option.selected;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateTags();
        }
    });
    
    // Listen for changes
    select.addEventListener('change', updateTags);
    
    // Initial update
    updateTags();
    
    // Expose getSelectedValues for form submission
    select.getSelectedValues = () => Array.from(select.selectedOptions).map(opt => opt.value);
    
    // Expose setSelectedValues for editing campaigns
    select.setSelectedValues = (values) => {
        if (!Array.isArray(values)) return;
        
        // Clear all selections first
        Array.from(select.options).forEach(opt => {
            opt.selected = false;
        });
        
        // Set selected values
        values.forEach(value => {
            const option = Array.from(select.options).find(opt => opt.value === value);
            if (option) {
                option.selected = true;
            }
        });
        
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateTags();
    };
}

// Initialize all dropdown fields when DOM is ready
(function() {
    function initAllDropdowns() {
        // STANDARD DROPDOWNS - Native <select> elements (no custom JS needed)
        // Note: Campaign Title is now a text input, no dropdown population needed
        
        if (document.getElementById('geographic_scope')) {
            populateStandardSelect('geographic_scope', SAMPLE_BARANGAYS);
        }
        
        if (document.getElementById('barangay_zones')) {
            populateStandardSelect('barangay_zones', SAMPLE_BARANGAY_ZONES);
        }
        
        // ENHANCED MULTI-SELECT - Assigned Staff & Materials only
        if (document.getElementById('assigned_staff')) {
            initMultiSelectEnhanced('assigned_staff', {
                staticOptions: SAMPLE_STAFF,
            });
        }
        
        if (document.getElementById('materials_json')) {
            initMultiSelectEnhanced('materials_json', {
                staticOptions: SAMPLE_MATERIALS,
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllDropdowns);
    } else {
        setTimeout(initAllDropdowns, 100);
    }
})();

// Also initialize on window load as a fallback
window.addEventListener('load', function() {
    // Initialize status options to show only 'draft' for new campaigns
    if (typeof updateStatusOptions === 'function') {
        updateStatusOptions('draft');
    }
    
    if (document.getElementById('assigned_staff') && !document.getElementById('assigned_staff').dataset.multiSelectInit) {
        console.log('Re-initializing multi-select dropdowns on window load...');
        if (typeof initMultiSelectEnhanced === 'function') {
            initMultiSelectEnhanced('assigned_staff', { staticOptions: SAMPLE_STAFF });
            initMultiSelectEnhanced('materials_json', { staticOptions: SAMPLE_MATERIALS });
        }
        if (typeof populateStandardSelect === 'function') {
            populateStandardSelect('title', SAMPLE_CAMPAIGN_TITLES);
            populateStandardSelect('geographic_scope', SAMPLE_BARANGAYS);
            populateStandardSelect('barangay_zones', SAMPLE_BARANGAY_ZONES);
        }
    }
});

// Form handling
document.getElementById('planningForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const createStatusEl = document.getElementById('createStatus');
    createStatusEl.style.display = 'block';
    createStatusEl.className = 'status-text';
    
    // Check if token exists before proceeding
    const currentToken = getToken();
    console.log('Campaign creation - Token check:', currentToken ? 'EXISTS (length: ' + currentToken.length + ')' : 'MISSING');
    if (!currentToken || currentToken.trim() === '') {
        console.error('Campaign creation - No token found');
        createStatusEl.textContent = 'Authorization token missing. Please log in again.';
        createStatusEl.className = 'status-text error';
        // Redirect to login after 2 seconds
        setTimeout(() => {
            window.location.href = basePath + '/login.php';
        }, 2000);
        return;
    }
    
    createStatusEl.textContent = 'Creating...';
    
    try {
        // Get values from comboboxes (supports multi-select)
        // Barangay zones (multi-select combobox)
        const barangayZonesEl = document.getElementById('barangay_zones');
        let barangayZones = [];
        if (barangayZonesEl && typeof barangayZonesEl.getSelectedValues === 'function') {
            barangayZones = barangayZonesEl.getSelectedValues();
        } else if (barangayZonesEl?.value) {
            barangayZones = barangayZonesEl.value.split(',').map(s => s.trim()).filter(Boolean);
        }
        
        // Assigned staff (multi-select combobox)
        const assignedStaffEl = document.getElementById('assigned_staff');
        let assignedStaff = [];
        if (assignedStaffEl && typeof assignedStaffEl.getSelectedValues === 'function') {
            assignedStaff = assignedStaffEl.getSelectedValues();
        } else if (assignedStaffEl?.value) {
            const staffInput = assignedStaffEl.value.trim();
            assignedStaff = staffInput ? staffInput.split(',').map(s => s.trim()).filter(Boolean) : [];
        }
        
        // Materials (multi-select combobox - convert to JSON object)
        const materialsEl = document.getElementById('materials_json');
        let materialsJson = {};
        if (materialsEl && typeof materialsEl.getSelectedValues === 'function') {
            const materialsList = materialsEl.getSelectedValues();
            materialsList.forEach(mat => {
                materialsJson[mat] = 1; // Default quantity
            });
        } else if (materialsEl?.value) {
            const materialsInput = materialsEl.value.trim();
            if (materialsInput) {
                if (materialsInput.startsWith('{')) {
                    try {
                        materialsJson = JSON.parse(materialsInput);
                    } catch (e) {
                        const materialsList = materialsInput.split(',').map(s => s.trim()).filter(Boolean);
                        materialsList.forEach(mat => {
                            materialsJson[mat] = 1;
                        });
                    }
                } else {
                    const materialsList = materialsInput.split(',').map(s => s.trim()).filter(Boolean);
                    materialsList.forEach(mat => {
                        const match = mat.match(/^(.+?)\s*\((\d+)\)$/);
                        if (match) {
                            materialsJson[match[1].trim()] = parseInt(match[2]);
                        } else {
                            materialsJson[mat] = 1;
                        }
                    });
                }
            }
        }
        
        // Get single-select combobox values
        const titleEl = document.getElementById('title');
        const title = (titleEl && typeof titleEl.getSelectedValues === 'function') 
            ? titleEl.getSelectedValues() 
            : titleEl?.value.trim() || '';
        
        const locationEl = document.getElementById('location');
        const location = (locationEl && typeof locationEl.getSelectedValues === 'function') 
            ? locationEl.getSelectedValues() 
            : locationEl?.value.trim() || null;
        
        // Get geographic scope (single-select combobox)
        const geographicScopeEl = document.getElementById('geographic_scope');
        const geographicScope = (geographicScopeEl && typeof geographicScopeEl.getSelectedValues === 'function') 
            ? geographicScopeEl.getSelectedValues() 
            : geographicScopeEl?.value.trim() || null;

        // Category (single-select combobox)
        const categoryEl = document.getElementById('category');
        const category = (categoryEl && typeof categoryEl.getSelectedValues === 'function')
            ? categoryEl.getSelectedValues()
            : (categoryEl?.value.trim() || null);

        // Status (single-select combobox)
        const statusEl = document.getElementById('status');
        const status = (statusEl && typeof statusEl.getSelectedValues === 'function')
            ? statusEl.getSelectedValues()
            : (statusEl?.value.trim() || 'draft');

        // Ensure we're using actual form values, not defaults or arrays
        // Handle single-select comboboxes that might return arrays
        let titleValue = title;
        if (Array.isArray(title)) {
            titleValue = title.length > 0 ? title[0] : '';
        } else if (typeof title === 'string') {
            titleValue = title.trim();
        } else {
            titleValue = '';
        }
        
        let categoryValue = category;
        if (Array.isArray(category)) {
            categoryValue = category.length > 0 ? category[0] : null;
        } else if (typeof category === 'string') {
            categoryValue = category.trim() || null;
        } else {
            categoryValue = null;
        }
        
        let geographicScopeValue = geographicScope;
        if (Array.isArray(geographicScope)) {
            geographicScopeValue = geographicScope.length > 0 ? geographicScope[0] : null;
        } else if (typeof geographicScope === 'string') {
            geographicScopeValue = geographicScope.trim() || null;
        } else {
            geographicScopeValue = null;
        }
        
        let locationValue = location;
        if (Array.isArray(location)) {
            locationValue = location.length > 0 ? location[0] : null;
        } else if (typeof location === 'string') {
            locationValue = location.trim() || null;
        } else {
            locationValue = null;
        }
        
        let statusValue = status;
        if (Array.isArray(status)) {
            statusValue = status.length > 0 ? status[0] : 'draft';
        } else if (typeof status === 'string') {
            statusValue = status.trim() || 'draft';
        } else {
            statusValue = 'draft';
        }

        // Get actual form field values (not defaults)
        const descriptionValue = document.getElementById('description').value.trim();
        const objectivesValue = document.getElementById('objectives').value.trim();
        const startDateValue = document.getElementById('start_date').value;
        const startTimeValue = document.getElementById('start_time').value;
        const endDateValue = document.getElementById('end_date').value;
        const endTimeValue = document.getElementById('end_time').value;
        // NOTE: draft_schedule_datetime is NOT set during initial creation per sequence diagram
        // Schedule should ONLY be set after user requests AI recommendation and confirms it (Step 9)
        const budgetInput = document.getElementById('budget').value.trim();
        const staffCountInput = document.getElementById('staff_count').value.trim();

        // GOVERNANCE WORKFLOW: Staff can ONLY create Draft campaigns
        // Status is enforced in backend, but we always send 'draft' from frontend
        // Secretary and Captain actions are handled via separate API endpoints/buttons
        const finalStatusValue = 'draft'; // Always draft for new campaigns - workflow enforced backend

        const payload = {
            title: titleValue,
            description: descriptionValue || null,
            category: categoryValue,
            geographic_scope: geographicScopeValue,
            status: finalStatusValue, // Always 'draft' - workflow enforced backend
            start_date: startDateValue || null,
            start_time: startTimeValue || null,
            end_date: endDateValue || null,
            end_time: endTimeValue || null,
            // draft_schedule_datetime: REMOVED - Schedule must be set via AI recommendation flow (Steps 3-9)
            objectives: objectivesValue || null,
            location: locationValue,
            assigned_staff: assignedStaff.length > 0 ? assignedStaff : null,
            barangay_target_zones: barangayZones.length > 0 ? barangayZones : null,
            budget: budgetInput ? parseFloat(budgetInput) : null,
            staff_count: staffCountInput ? parseInt(staffCountInput) : null,
            materials_json: Object.keys(materialsJson).length > 0 ? materialsJson : null,
        };
        
        // Log individual field values for debugging
        console.log('=== FORM FIELD VALUES DEBUG ===');
        console.log('start_time element:', document.getElementById('start_time'));
        console.log('start_time value:', startTimeValue);
        console.log('end_time element:', document.getElementById('end_time'));
        console.log('end_time value:', endTimeValue);
        console.log('barangay_zones element:', document.getElementById('barangay_zones'));
        console.log('barangay_zones value:', barangayZones);
        console.log('=== END FIELD VALUES DEBUG ===');
        
        // Log the actual payload to verify real data is being sent
        console.log('Campaign creation - Payload (actual form values):', JSON.stringify(payload, null, 2));
        
        if (!payload.title) {
            createStatusEl.textContent = 'Title is required.';
            createStatusEl.className = 'status-text error';
            return;
        }
        
        const token = getToken();
        // TASK 4: PROVE TOKEN PRESENCE ON REQUEST
        console.log('=== TASK 4 PROOF: Campaign creation request ===');
        console.log('TASK 4: Token variable value:', token ? 'EXISTS (length: ' + token.length + ')' : 'NULL/EMPTY');
        console.log('TASK 4: Token first 30 chars:', token ? token.substring(0, 30) + '...' : 'N/A');
        
        const authHeader = 'Bearer ' + (token ? token.trim() : '');
        console.log('=== TASK 4 PROOF: Authorization header value ===');
        console.log('TASK 4: Authorization header:', authHeader ? authHeader.substring(0, 50) + '...' : 'EMPTY');
        console.log('TASK 4: Authorization header length:', authHeader.length);
        
        console.log('Campaign creation - Making API call with token (length:', token ? token.length : 0 + ')');
        console.log('Campaign creation - API URL:', apiBase + '/api/v1/campaigns');
        
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': authHeader
            },
            body: JSON.stringify(payload)
        });
        
        console.log('Campaign creation - Response status:', res.status);
        console.log('Campaign creation - Response URL:', res.url);
        
        // Check if response is JSON and parse it
        let data = {};
        try {
            const contentType = res.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const responseText = await res.clone().text();
                console.log('Campaign creation - Raw response:', responseText);
                data = JSON.parse(responseText);
                console.log('Campaign creation - Parsed response data:', data);
            } else {
                const text = await res.text();
                console.error('Campaign creation - Non-JSON response:', text);
                data = { error: text || 'Server returned non-JSON response' };
            }
        } catch (parseError) {
            console.error('Campaign creation - Parse error:', parseError);
            // Try to get error message from response
            try {
                const text = await res.text();
                data = { error: text || parseError.message };
            } catch (e) {
                data = { error: parseError.message || 'Unable to parse server response' };
            }
            createStatusEl.textContent = 'Error: ' + data.error;
            createStatusEl.className = 'status-text error';
            return;
        }
        
        if (!res.ok) {
            console.error('Campaign creation - API error:', res.status, data);
            
            // Handle 502 Bad Gateway - verify if campaign was actually created
            if (res.status === 502) {
                console.warn('502 Bad Gateway - Verifying if campaign was created...');
                createStatusEl.textContent = 'Server error. Verifying campaign creation...';
                createStatusEl.className = 'status-text';
                
                // Wait and check if campaign was created by refreshing the list
                setTimeout(async () => {
                    try {
                        // Refresh the campaign list to check
                        await refreshAllCampaignViews();
                        showToast('Server encountered an error. Please check if your campaign appears in the list.', 'warning');
                        clearForm();
                        closePlanCampaignModal();
                    } catch (e) {
                        showToast('Server error. Please refresh the page and check your campaigns.', 'error');
                    }
                }, 1000);
                return;
            }
            
            // Handle 401 Unauthorized specifically
            if (res.status === 401) {
                console.error('Campaign creation - 401 Unauthorized error');
                // Try to use cached user data - don't redirect immediately
                const cachedUser = localStorage.getItem('currentUser');
                if (cachedUser) {
                    console.log('Token may be expired, but user is logged in. Please refresh the page.');
                    createStatusEl.textContent = 'Session expired. Please refresh the page and try again.';
                    createStatusEl.className = 'status-text error';
                } else {
                    createStatusEl.textContent = 'Authorization token missing or expired. Please log in again.';
                    createStatusEl.className = 'status-text error';
                    localStorage.removeItem('jwtToken');
                    setTimeout(() => {
                        window.location.href = basePath + '/login.php';
                    }, 2000);
                }
                return;
            }
            
            // Handle other error cases
            if (data && data.error) {
                const errorMsg = data.error.toLowerCase();
                if (errorMsg.includes('authorization') || errorMsg.includes('token')) {
                    createStatusEl.textContent = 'Authorization token missing or expired. Please log in again.';
                    createStatusEl.className = 'status-text error';
                    localStorage.removeItem('jwtToken');
                    setTimeout(() => {
                        window.location.href = basePath + '/login.php';
                    }, 2000);
                } else {
                    createStatusEl.textContent = data.error || 'Failed to create campaign.';
                    createStatusEl.className = 'status-text error';
                }
            } else {
                createStatusEl.textContent = 'Failed to create campaign. Status: ' + res.status;
                createStatusEl.className = 'status-text error';
            }
            return;
        }
        
        createStatusEl.textContent = 'Campaign created successfully!';
        createStatusEl.className = 'status-text success';
        
        // Show toast notification
        showSuccessToast('Campaign created successfully!');
        
        // Log the created campaign data to verify what was saved
        console.log('Campaign created - Response data:', data);
        if (data.campaign) {
            console.log('Saved campaign values:');
            console.log('- start_time:', data.campaign.start_time);
            console.log('- end_time:', data.campaign.end_time);
            console.log('- barangay_target_zones:', data.campaign.barangay_target_zones);
        }
        
        clearForm();
        // Close the modal after successful creation
        closePlanCampaignModal();
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        createStatusEl.textContent = 'Network error. Please try again.';
        createStatusEl.className = 'status-text error';
    }
});

// Plan New Campaign Modal Functions
function openPlanCampaignModal(isEdit = false) {
    const modal = document.getElementById('planCampaignModal');
    if (modal) {
        // Reset form if not editing (opening for new campaign)
        if (!isEdit) {
            clearForm();
        }
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        // Focus on first input
        setTimeout(() => {
            const firstInput = modal.querySelector('select, input');
            if (firstInput) firstInput.focus();
        }, 100);
    }
}

function closePlanCampaignModal() {
    const modal = document.getElementById('planCampaignModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('planCampaignModal');
    if (modal && e.target === modal) {
        closePlanCampaignModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePlanCampaignModal();
    }
});

function clearForm() {
    // Reset form dataset
    if (document.getElementById('planningForm')) {
        delete document.getElementById('planningForm').dataset.campaignId;
    }
    
    // Reset submit button
    const submitBtn = document.querySelector('#planningForm button[type="submit"]');
    if (submitBtn) {
        submitBtn.textContent = 'Create Campaign';
        submitBtn.onclick = null; // Remove custom handler, use form's default submit
    }
    
    // Hide final schedule field
    const finalScheduleField = document.getElementById('final_schedule_field');
    if (finalScheduleField) {
        finalScheduleField.style.display = 'none';
    }
    const finalScheduleValue = document.getElementById('final_schedule_value');
    if (finalScheduleValue) {
        finalScheduleValue.textContent = '-';
    }
    
    // Reset status options to show only 'draft' for new campaigns
    updateStatusOptions('draft');
    
    // Clear form fields
    document.getElementById('planningForm').reset();
    
    // FIX: Clear Assigned Staff multi-select
    const assignedStaffEl = document.getElementById('assigned_staff');
    if (assignedStaffEl) {
        if (typeof assignedStaffEl.setSelectedValues === 'function') {
            assignedStaffEl.setSelectedValues([]);
        } else {
            // Fallback: clear all selected options
            Array.from(assignedStaffEl.options).forEach(opt => opt.selected = false);
        }
        // Clear tags display
        const assignedStaffTags = document.getElementById('assigned_staff_tags');
        if (assignedStaffTags) {
            assignedStaffTags.innerHTML = '';
        }
    }
    
    // FIX: Clear Materials multi-select
    const materialsEl = document.getElementById('materials_json');
    if (materialsEl) {
        if (typeof materialsEl.setSelectedValues === 'function') {
            materialsEl.setSelectedValues([]);
        } else {
            // Fallback: clear all selected options
            Array.from(materialsEl.options).forEach(opt => opt.selected = false);
        }
        // Clear tags display
        const materialsTags = document.getElementById('materials_json_tags');
        if (materialsTags) {
            materialsTags.innerHTML = '';
        }
    }
    
    // Clear status display
    document.getElementById('createStatus').style.display = 'none';
}

// AutoML
let currentPrediction = null;
let currentCampaignId = null;

// Helper function to check dropdown status
function checkDropdownStatus() {
    const automlSelect = document.getElementById('automl_campaign_id');
    const statusEl = document.getElementById('automl_dropdown_status');
    
    if (!automlSelect) {
        if (statusEl) statusEl.textContent = 'Error: Dropdown element not found';
        return;
    }
    
    const optionCount = automlSelect.options.length - 1; // Exclude default option
    if (optionCount <= 0) {
        if (statusEl) {
            if (allCampaigns && allCampaigns.length > 0) {
                statusEl.textContent = 'Campaigns loaded but dropdown empty. Click Refresh.';
                statusEl.style.color = 'rgba(255,193,7,0.9)';
                // Try to populate immediately
                populateAutoMLDropdown();
            } else {
                statusEl.textContent = 'No campaigns available. Create a campaign first.';
                statusEl.style.color = 'rgba(255,255,255,0.7)';
            }
        }
    } else {
        if (statusEl) {
            statusEl.textContent = `${optionCount} campaign(s) available - Click to select`;
            statusEl.style.color = 'rgba(255,255,255,0.9)';
        }
    }
}

// Refresh AutoML campaigns dropdown
async function refreshAutoMLCampaigns() {
    console.log('=== refreshAutoMLCampaigns() - Function called ===');
    const refreshBtn = document.getElementById('automlRefreshBtn');
    const statusEl = document.getElementById('automl_dropdown_status');
    
    if (refreshBtn) {
        refreshBtn.disabled = true;
        refreshBtn.style.opacity = '0.6';
        refreshBtn.innerHTML = '<span>⏳</span><span>Loading...</span>';
    }
    
    if (statusEl) {
        statusEl.textContent = 'Refreshing campaigns...';
        statusEl.style.color = 'rgba(255,255,255,0.7)';
    }
    
    try {
        await loadCampaigns();
        populateAutoMLDropdown();
        
        if (statusEl) {
            const optionCount = document.getElementById('automl_campaign_id') ? document.getElementById('automl_campaign_id').options.length - 1 : 0;
            if (optionCount > 0) {
                statusEl.textContent = `${optionCount} campaign(s) loaded - Click to select`;
                statusEl.style.color = 'rgba(144, 238, 144, 0.9)';
            } else {
                statusEl.textContent = 'No campaigns available. Create a campaign first.';
                statusEl.style.color = 'rgba(255,255,255,0.7)';
            }
        }
    } catch (err) {
        console.error('refreshAutoMLCampaigns() - Error:', err);
        if (statusEl) {
            statusEl.textContent = 'Error refreshing campaigns. Please try again.';
            statusEl.style.color = 'rgba(255, 100, 100, 0.9)';
        }
    } finally {
        if (refreshBtn) {
            refreshBtn.disabled = false;
            refreshBtn.style.opacity = '1';
            refreshBtn.innerHTML = '<span>🔄</span><span>Refresh</span>';
        }
    }
}

// Handle Get Prediction button click
function handleGetPredictionClick(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('=== handleGetPredictionClick() - Button clicked ===');
    console.log('Event:', event);
    
    const automlSelect = document.getElementById('automl_campaign_id');
    const getPredictionBtn = document.getElementById('getPredictionBtn');
    
    console.log('automlSelect:', automlSelect);
    console.log('getPredictionBtn:', getPredictionBtn);
    
    if (!automlSelect || !getPredictionBtn) {
        console.error('handleGetPredictionClick() - Elements not found');
        alert('Form elements not found. Please refresh the page.');
        return;
    }
    
    const campaignId = parseInt(automlSelect.value);
    console.log('Campaign ID from dropdown:', automlSelect.value, 'Parsed:', campaignId);
    
    if (!campaignId || isNaN(campaignId)) {
        alert('Please select a campaign from the dropdown first.');
        automlSelect.focus();
        return;
    }
    
    console.log('Calling getAutoMLPrediction() with campaign ID:', campaignId);
    console.log('getAutoMLPrediction function exists:', typeof getAutoMLPrediction);
    console.log('window.getAutoMLPrediction function exists:', typeof window.getAutoMLPrediction);
    
    // Get result div and empty state, make result visible and hide empty state
    const resultDiv = document.getElementById('automlResult');
    const emptyState = document.getElementById('automlEmptyState');
    if (resultDiv) {
        resultDiv.style.display = 'block';
        resultDiv.style.visibility = 'visible';
        resultDiv.style.opacity = '1';
        resultDiv.innerHTML = '<div style="text-align:center; padding:20px; color: white; background: rgba(0,0,0,0.3); border-radius: 8px;">⏳ Processing request...</div>';
        console.log('handleGetPredictionClick() - Result div made visible');
        // Hide empty state
        if (emptyState) {
            emptyState.style.display = 'none';
        }
    } else {
        console.error('handleGetPredictionClick() - Result div not found!');
        alert('Result container not found. Please refresh the page.');
        return;
    }
    
    // Call the prediction function
    try {
        if (typeof getAutoMLPrediction === 'function') {
            console.log('Calling getAutoMLPrediction() directly');
            const promise = getAutoMLPrediction();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(err => {
                    console.error('getAutoMLPrediction() promise rejected:', err);
                    if (resultDiv) {
                        resultDiv.innerHTML = `<div style="color: #fee2e2; padding: 16px; background: rgba(254, 226, 226, 0.1); border-radius: 8px;">
                            <strong>❌ Error:</strong> ${err.message || 'Unknown error'}
                        </div>`;
                    }
                });
            }
        } else if (typeof window.getAutoMLPrediction === 'function') {
            console.log('Calling window.getAutoMLPrediction()');
            const promise = window.getAutoMLPrediction();
            if (promise && typeof promise.catch === 'function') {
                promise.catch(err => {
                    console.error('window.getAutoMLPrediction() promise rejected:', err);
                    if (resultDiv) {
                        resultDiv.innerHTML = `<div style="color: #fee2e2; padding: 16px; background: rgba(254, 226, 226, 0.1); border-radius: 8px;">
                            <strong>❌ Error:</strong> ${err.message || 'Unknown error'}
                        </div>`;
                    }
                });
            }
        } else {
            console.error('getAutoMLPrediction is not a function!');
            console.error('Available functions:', {
                getAutoMLPrediction: typeof getAutoMLPrediction,
                window_getAutoMLPrediction: typeof window.getAutoMLPrediction,
                handleGetPredictionClick: typeof handleGetPredictionClick,
                window_handleGetPredictionClick: typeof window.handleGetPredictionClick
            });
            if (resultDiv) {
                resultDiv.innerHTML = '<div style="color: #fee2e2; padding: 16px; background: rgba(254, 226, 226, 0.1); border-radius: 8px;"><strong>❌ Error:</strong> Prediction function not found. Please refresh the page.</div>';
            } else {
                alert('Error: Prediction function not found. Please refresh the page.');
            }
        }
    } catch (err) {
        console.error('Error calling getAutoMLPrediction:', err);
        if (resultDiv) {
            resultDiv.innerHTML = `<div style="color: #fee2e2; padding: 16px; background: rgba(254, 226, 226, 0.1); border-radius: 8px;"><strong>❌ Error:</strong> ${err.message || 'Unknown error'}</div>`;
        } else {
            alert('Error: ' + err.message);
        }
    }
}

// Validate AutoML form before submission
function validateAutoMLForm() {
    const automlSelect = document.getElementById('automl_campaign_id');
    const getPredictionBtn = document.getElementById('getPredictionBtn');
    const emptyState = document.getElementById('automlEmptyState');
    const resultDiv = document.getElementById('automlResult');
    
    if (!automlSelect || !getPredictionBtn) {
        console.warn('validateAutoMLForm() - Elements not found');
        return;
    }
    
    const hasCampaign = automlSelect.value && parseInt(automlSelect.value) > 0;
    console.log('validateAutoMLForm() - Campaign selected:', hasCampaign, 'Value:', automlSelect.value);
    
    if (hasCampaign) {
        getPredictionBtn.disabled = false;
        getPredictionBtn.style.opacity = '1';
        getPredictionBtn.style.cursor = 'pointer';
        getPredictionBtn.title = 'Click to get AI prediction for selected campaign';
        console.log('validateAutoMLForm() - Button enabled');
    } else {
        getPredictionBtn.disabled = true;
        getPredictionBtn.style.opacity = '0.6';
        getPredictionBtn.style.cursor = 'not-allowed';
        getPredictionBtn.title = 'Please select a campaign first';
        console.log('validateAutoMLForm() - Button disabled (no campaign selected)');
        
        // Show empty state and hide result if no campaign selected
        if (emptyState) {
            emptyState.style.display = 'block';
        }
        if (resultDiv) {
            resultDiv.style.display = 'none';
        }
    }
}

// Update status when dropdown value changes
function updateDropdownStatus() {
    const automlSelect = document.getElementById('automl_campaign_id');
    const statusEl = document.getElementById('automl_dropdown_status');
    
    if (automlSelect && automlSelect.value) {
        const selectedOption = automlSelect.options[automlSelect.selectedIndex];
        if (statusEl) {
            statusEl.textContent = `Selected: ${selectedOption.textContent}`;
            statusEl.style.color = 'rgba(144, 238, 144, 0.9)';
        }
    } else if (statusEl) {
        const optionCount = automlSelect ? automlSelect.options.length - 1 : 0;
        if (optionCount > 0) {
            statusEl.textContent = `${optionCount} campaign(s) available - Click to select`;
            statusEl.style.color = 'rgba(255,255,255,0.9)';
        }
    }
    
    validateAutoMLForm();
    
    // Add event listener to Get Prediction button as backup
    const getPredictionBtn = document.getElementById('getPredictionBtn');
    if (getPredictionBtn) {
        // Remove any existing listeners to avoid duplicates
        const newBtn = getPredictionBtn.cloneNode(true);
        getPredictionBtn.parentNode.replaceChild(newBtn, getPredictionBtn);
        
        // Add click event listener
        document.getElementById('getPredictionBtn').addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Get Prediction button clicked via event listener');
            handleGetPredictionClick(e);
        });
        
        console.log('Get Prediction button event listener attached');
    }
}

// Helper function to ensure AutoML dropdown is populated
function populateAutoMLDropdown() {
    const automlSelect = document.getElementById('automl_campaign_id');
    const statusEl = document.getElementById('automl_dropdown_status');
    
    if (!automlSelect) {
        console.error('populateAutoMLDropdown() - automl_campaign_id element not found');
        if (statusEl) statusEl.textContent = 'Error: Dropdown element not found';
        return false;
    }
    
    console.log('populateAutoMLDropdown() - Called. allCampaigns length:', allCampaigns ? allCampaigns.length : 'undefined');
    console.log('populateAutoMLDropdown() - Current dropdown options:', automlSelect.options.length);
    
    // Always repopulate to ensure it's up to date with latest campaign data
    if (allCampaigns && allCampaigns.length > 0) {
        console.log('populateAutoMLDropdown() - Populating with', allCampaigns.length, 'campaigns');
        
        // Clear and repopulate
        automlSelect.innerHTML = '<option value="">Select Campaign</option>';
        
        // Sort campaigns by ID (newest first) for better UX
        const sortedCampaigns = [...allCampaigns].sort((a, b) => (b.id || 0) - (a.id || 0));
        
        sortedCampaigns.forEach(c => {
            if (!c || !c.id) {
                console.warn('populateAutoMLDropdown() - Skipping invalid campaign:', c);
                return;
            }
            
            const opt = document.createElement('option');
            opt.value = c.id.toString();
            opt.textContent = `${c.id} - ${c.title || 'Untitled Campaign'}`;
            opt.setAttribute('data-campaign-id', c.id.toString());
            automlSelect.appendChild(opt);
            
            console.log('populateAutoMLDropdown() - Added option:', opt.value, opt.textContent);
        });
        
        const finalOptionCount = automlSelect.options.length - 1;
        console.log('populateAutoMLDropdown() - Successfully added', finalOptionCount, 'options. Total options now:', automlSelect.options.length);
        
        // Verify options are actually in the DOM
        if (finalOptionCount !== allCampaigns.length) {
            console.warn('populateAutoMLDropdown() - Option count mismatch! Expected:', allCampaigns.length, 'Got:', finalOptionCount);
        }
        
        if (statusEl) {
            statusEl.textContent = `${finalOptionCount} campaign(s) available - Click to select`;
            statusEl.style.color = 'rgba(255,255,255,0.9)';
        }
        
        // Force a re-render by triggering a change event
        automlSelect.dispatchEvent(new Event('change', { bubbles: true }));
        
        return true;
    } else {
        console.warn('populateAutoMLDropdown() - No campaigns available. allCampaigns:', allCampaigns);
        automlSelect.innerHTML = '<option value="">Select Campaign</option>';
        if (statusEl) {
            statusEl.textContent = 'No campaigns available. Create a campaign first.';
            statusEl.style.color = 'rgba(255,255,255,0.7)';
        }
        return false;
    }
}

// Define getAutoMLPrediction function
async function getAutoMLPrediction() {
    console.log('=== getAutoMLPrediction() - Function called ===');
    console.log('apiBase:', apiBase);
    console.log('basePath:', basePath);
    
    const automlSelect = document.getElementById('automl_campaign_id');
    const getPredictionBtn = document.getElementById('getPredictionBtn');
    const resultDiv = document.getElementById('automlResult');
    
    if (!automlSelect) {
        alert('Campaign dropdown not found. Please refresh the page.');
        console.error('getAutoMLPrediction() - automl_campaign_id element not found');
        return;
    }
    
    if (!resultDiv) {
        alert('Result container not found. Please refresh the page.');
        console.error('getAutoMLPrediction() - automlResult element not found');
        return;
    }
    
    console.log('getAutoMLPrediction() - Dropdown value:', automlSelect.value);
    console.log('getAutoMLPrediction() - Dropdown options count:', automlSelect.options.length);
    
    // Ensure dropdown is populated before checking value
    if (automlSelect.options.length <= 1) {
        console.log('getAutoMLPrediction() - Dropdown empty, refreshing...');
        await refreshAutoMLCampaigns();
        // Wait a moment for DOM to update
        await new Promise(resolve => setTimeout(resolve, 300));
    }
    
    const cid = parseInt(automlSelect.value);
    console.log('getAutoMLPrediction() - Parsed campaign ID:', cid);
    
    if (!cid || isNaN(cid)) {
        alert('Please select a campaign from the dropdown first');
        automlSelect.focus();
        console.warn('getAutoMLPrediction() - No valid campaign ID selected');
        // Re-enable button
        if (getPredictionBtn) {
            getPredictionBtn.disabled = false;
            getPredictionBtn.style.opacity = '1';
            getPredictionBtn.innerHTML = '🔮 Get Prediction';
        }
        return;
    }
    
    // Disable button during request
    if (getPredictionBtn) {
        getPredictionBtn.disabled = true;
        getPredictionBtn.style.opacity = '0.6';
        getPredictionBtn.innerHTML = '⏳ Processing...';
        getPredictionBtn.style.cursor = 'wait';
        console.log('getAutoMLPrediction() - Button disabled, starting request...');
    }
    
    currentCampaignId = cid;
    
    // Make sure result div is visible and hide empty state
    const emptyState = document.getElementById('automlEmptyState');
    resultDiv.style.display = 'block';
    resultDiv.style.visibility = 'visible';
    resultDiv.style.opacity = '1';
    resultDiv.style.height = 'auto';
    resultDiv.style.overflow = 'visible';
    
    // Hide empty state
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    console.log('getAutoMLPrediction() - Result div display set to:', resultDiv.style.display);
    console.log('getAutoMLPrediction() - Result div element:', resultDiv);
    console.log('getAutoMLPrediction() - Result div computed style:', window.getComputedStyle(resultDiv).display);
    
    resultDiv.innerHTML = '<div style="text-align:center; padding:20px; color: white; background: rgba(0,0,0,0.3); border-radius: 8px;">⏳ Loading prediction from real-time data...</div>';
    
    // Force a reflow to ensure display change takes effect
    resultDiv.offsetHeight;
    
    try {
        const audienceSegmentId = document.getElementById('automl_audience_segment')?.value;
        const features = {};
        if (audienceSegmentId && audienceSegmentId.trim() !== '') {
            features.audience_segment_id = parseInt(audienceSegmentId);
            console.log('getAutoMLPrediction() - Audience Segment ID:', features.audience_segment_id);
        }
        
        const token = getToken();
        if (!token) {
            throw new Error('Authentication token not found. Please log in again.');
        }
        
        if (!apiBase) {
            throw new Error('API base path not defined. Please refresh the page.');
        }
        
        const apiUrl = apiBase + `/api/v1/campaigns/${cid}/ai-recommendation`;
        console.log('getAutoMLPrediction() - Making API call to:', apiUrl);
        console.log('getAutoMLPrediction() - Request payload:', JSON.stringify({ features }));
        console.log('getAutoMLPrediction() - Token length:', token ? token.length : 0);
        
        const res = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ features })
        });
        
        console.log('getAutoMLPrediction() - Response status:', res.status);
        console.log('getAutoMLPrediction() - Response ok:', res.ok);
        console.log('getAutoMLPrediction() - Response headers:', Object.fromEntries(res.headers.entries()));
        
        if (!res.ok) {
            const errorText = await res.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { error: errorText || `HTTP ${res.status}` };
            }
            console.error('getAutoMLPrediction() - API error:', res.status, errorData);
            resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5; background: rgba(254, 226, 226, 0.1); padding: 16px; border-radius: 8px;">
                <strong>❌ Error:</strong>
                <span style="display: block; margin-top: 8px;">${errorData.error || `Failed to get prediction (HTTP ${res.status})`}</span>
                <p style="margin-top: 12px; font-size: 11px; opacity: 0.7;">Check the browser console (F12) for more details.</p>
            </div>`;
            // Re-enable button on error
            if (getPredictionBtn) {
                getPredictionBtn.disabled = false;
                getPredictionBtn.style.opacity = '1';
                getPredictionBtn.innerHTML = '🔮 Get Prediction';
                getPredictionBtn.style.cursor = 'pointer';
            }
            return;
        }
        
        const data = await res.json();
        console.log('getAutoMLPrediction() - Received data:', data);
        
        if (data.error) {
            console.error('getAutoMLPrediction() - Error in response:', data.error);
            resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5;">
                <strong>Error:</strong>
                <span>${data.error}</span>
            </div>`;
            return;
        }
        
        currentPrediction = data.prediction || {};
        const pred = currentPrediction;
        const suggestedDateTime = pred.suggested_datetime || new Date().toISOString().slice(0, 16).replace('T', ' ');
        const confidence = pred.confidence_score ? (pred.confidence_score * 100).toFixed(1) + '%' : 'N/A';
        const modelSource = pred.model_source || 'unknown';
        const automlConfigured = pred.automl_configured !== undefined ? pred.automl_configured : null;
        const fallbackReason = pred.fallback_reason || null;
        
        // Determine model source display
        let modelSourceDisplay = 'Unknown';
        let modelStatusBadge = '';
        if (modelSource === 'google_automl') {
            modelSourceDisplay = 'Google AutoML';
            modelStatusBadge = '<span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">✓ Active</span>';
        } else if (modelSource === 'heuristic_with_history') {
            modelSourceDisplay = 'Heuristic (with historical data)';
            if (automlConfigured === false) {
                modelStatusBadge = '<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">⚠ Not Configured</span>';
            } else if (fallbackReason) {
                modelStatusBadge = '<span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">⚠ Fallback</span>';
            }
        } else {
            modelSourceDisplay = 'Heuristic (fallback)';
            if (automlConfigured === false) {
                modelStatusBadge = '<span style="background: #f59e0b; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">⚠ Not Configured</span>';
            } else if (fallbackReason) {
                modelStatusBadge = '<span style="background: #ef4444; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">⚠ Fallback</span>';
            }
        }
        
        // Build recommendation message with transparency about data limitations
        let recommendation = 'Optimal deployment time based on real-time historical performance data';
        const dataSources = pred.data_sources_used || {};
        const usedSources = dataSources.used || [];
        const totalRecords = usedSources.reduce((sum, s) => sum + (s.count || 0), 0);
        
        if (pred.confidence_score && pred.confidence_score > 0.8) {
            recommendation = 'High confidence recommendation - Strong historical match with similar campaigns in Nagkaisang Nayon';
        } else if (pred.confidence_score && pred.confidence_score > 0.6) {
            recommendation = 'Moderate confidence - Good historical indicators from similar campaigns in Nagkaisang Nayon';
        } else if (pred.confidence_score) {
            recommendation = 'Lower confidence - Limited historical data in Nagkaisang Nayon dataset, consider additional factors';
        }
        
        // Add transparency message if data is limited
        if (totalRecords < 10 || usedSources.length < 3) {
            recommendation += '. Limited recommendation accuracy due to insufficient historical campaign records in Barangay Nagkaisang Nayon.';
        }
        
        // Add configuration notice if AutoML is not configured
        let configNotice = '';
        if (automlConfigured === false) {
            configNotice = `
            <div class="prediction-item" style="background: rgba(245, 158, 11, 0.1); border-color: #f59e0b; margin-top: 12px;">
                <strong>⚠️ Notice:</strong>
                <span style="font-size: 13px;">Google AutoML is not configured. Using heuristic prediction. To enable Google AutoML, set GOOGLE_AUTOML_ENDPOINT and GOOGLE_AUTOML_API_KEY environment variables.</span>
            </div>
            `;
        } else if (fallbackReason) {
            configNotice = `
            <div class="prediction-item" style="background: rgba(239, 68, 68, 0.1); border-color: #ef4444; margin-top: 12px;">
                <strong>⚠️ Notice:</strong>
                <span style="font-size: 13px;">Google AutoML unavailable: ${fallbackReason}. Using heuristic fallback.</span>
            </div>
            `;
        }
        
        // Ensure result div is still visible
        resultDiv.style.display = 'block';
        resultDiv.style.visibility = 'visible';
        resultDiv.style.opacity = '1';
        
        console.log('getAutoMLPrediction() - Setting result HTML, display:', resultDiv.style.display);
        console.log('getAutoMLPrediction() - Prediction data:', pred);
        
        // Hide empty state
        const emptyState = document.getElementById('automlEmptyState');
        if (emptyState) {
            emptyState.style.display = 'none';
        }
        
        // Get campaign title for display
        const campaignSelect = document.getElementById('automl_campaign_id');
        const campaignTitle = campaignSelect ? campaignSelect.options[campaignSelect.selectedIndex]?.textContent?.replace(/^\d+\s*-\s*/, '') || 'Selected Campaign' : 'Selected Campaign';
        
        resultDiv.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #e2e8f0;">
                <div style="font-size: 32px;">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                </div>
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 4px 0; color: #0f172a; font-size: 18px; font-weight: 700;">AI Recommendation Generated</h4>
                    <p style="margin: 0 0 8px 0; color: #64748b; font-size: 13px;">Review the suggested schedule below and choose an action.</p>
                    <p style="margin: 0; color: #475569; font-size: 12px; font-weight: 600; background: #f1f5f9; padding: 6px 12px; border-radius: 6px; display: inline-block;">
                        <i class="fas fa-info-circle" style="margin-right: 6px;"></i>
                        Prediction generated for campaign: <span style="color: #0f172a;">${campaignTitle}</span>
                    </p>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px;">
                <div style="background: #f0fdf4; border: 2px solid #10b981; border-radius: 8px; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-calendar-alt" style="color: #10b981; font-size: 18px;"></i>
                        <strong style="color: #065f46; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Suggested Date & Time</strong>
                    </div>
                    <div style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">${suggestedDateTime}</div>
                </div>
                
                <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-chart-line" style="color: #3b82f6; font-size: 18px;"></i>
                        <strong style="color: #1e40af; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Confidence Score</strong>
                    </div>
                    <div style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">${confidence}</div>
                    <p style="margin: 8px 0 0 0; color: #64748b; font-size: 11px; line-height: 1.4;">
                        Higher scores indicate stronger confidence in the recommendation based on historical data analysis.
                    </p>
                </div>
                
                <div style="background: #faf5ff; border: 2px solid #a855f7; border-radius: 8px; padding: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <i class="fas fa-cog" style="color: #a855f7; font-size: 18px;"></i>
                        <strong style="color: #6b21a8; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Model Source</strong>
                    </div>
                    <div style="color: #0f172a; font-size: 16px; font-weight: 600; line-height: 1.4;">${modelSourceDisplay}${modelStatusBadge}</div>
                </div>
            </div>
            
            ${configNotice ? `<div style="background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px; padding: 12px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; color: #991b1b;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Notice:</strong>
                </div>
                <p style="margin: 4px 0 0 0; color: #7f1d1d; font-size: 13px;">${configNotice.replace(/<[^>]*>/g, '')}</p>
            </div>` : ''}
            
            <div style="background: #f8fafc; border-left: 4px solid #667eea; border-radius: 6px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <i class="fas fa-lightbulb" style="color: #667eea; font-size: 20px; margin-top: 2px;"></i>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 6px; color: #0f172a; font-size: 14px;">AI Recommendation:</strong>
                        <div style="color: #475569; font-size: 13px; line-height: 1.6;">${recommendation}</div>
                    </div>
                </div>
            </div>
            
            ${getDataFactorsHTML(pred.data_sources_used || [])}
            
            ${getAIRecommendationsHTML(pred.recommendations || {})}
            
            ${getAIDecisionBasisHTML(pred.recommendations || {})}
            
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button type="button" class="btn btn-primary" onclick="acceptAIRecommendation()" style="background: #10b981; color: white; border: none; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'">
                    <i class="fas fa-check"></i>
                    Accept AI Recommendation
                </button>
                <button type="button" class="btn btn-secondary" onclick="checkConflicts()" style="background: white; color: #667eea; border: 2px solid #667eea; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <i class="fas fa-search"></i>
                    Check Conflicts
                </button>
                <button type="button" class="btn btn-secondary" onclick="overrideSchedule()" style="background: white; color: #64748b; border: 2px solid #e2e8f0; font-weight: 600; padding: 12px 24px; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px; font-size: 14px;" onmouseover="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">
                    <i class="fas fa-edit"></i>
                    Override Schedule
                </button>
            </div>
        `;
        
        console.log('getAutoMLPrediction() - Result HTML set, innerHTML length:', resultDiv.innerHTML.length);
        console.log('getAutoMLPrediction() - Result div final display:', window.getComputedStyle(resultDiv).display);
        
        // Scroll to results
        resultDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Re-enable button after successful prediction
        if (getPredictionBtn) {
            getPredictionBtn.disabled = false;
            getPredictionBtn.style.opacity = '1';
            getPredictionBtn.innerHTML = '🔮 Get Prediction';
            getPredictionBtn.style.cursor = 'pointer';
        }
    } catch (err) {
        console.error('getAutoMLPrediction() - Exception caught:', err);
        console.error('getAutoMLPrediction() - Exception:', err);
        console.error('getAutoMLPrediction() - Error stack:', err.stack);
        
        let errorMessage = err.message || 'Unknown error occurred';
        if (err.message.includes('token')) {
            errorMessage = 'Authentication failed. Please refresh the page and log in again.';
        } else if (err.message.includes('fetch')) {
            errorMessage = 'Network error. Please check your connection and try again.';
        }
        
        resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5; background: rgba(254, 226, 226, 0.1); padding: 16px; border-radius: 8px;">
            <strong>❌ Error:</strong>
            <span style="display: block; margin-top: 8px;">${errorMessage}</span>
            <p style="margin-top: 12px; font-size: 11px; opacity: 0.7;">Check the browser console (F12) for more details.</p>
        </div>`;
        
        // Re-enable button on error
        if (getPredictionBtn) {
            getPredictionBtn.disabled = false;
            getPredictionBtn.style.opacity = '1';
            getPredictionBtn.innerHTML = '🔮 Get Prediction';
            getPredictionBtn.style.cursor = 'pointer';
            console.log('getAutoMLPrediction() - Button re-enabled after error');
        }
    }
}

// Make functions globally accessible immediately after definition
window.getAutoMLPrediction = getAutoMLPrediction;
window.handleGetPredictionClick = handleGetPredictionClick;
window.refreshAutoMLCampaigns = refreshAutoMLCampaigns;
console.log('AutoML functions registered globally:', {
    getAutoMLPrediction: typeof window.getAutoMLPrediction,
    handleGetPredictionClick: typeof window.handleGetPredictionClick,
    refreshAutoMLCampaigns: typeof window.refreshAutoMLCampaigns
});

async function acceptAIRecommendation() {
    if (!currentCampaignId || !currentPrediction) {
        alert('Please get a prediction first');
        return;
    }
    
    try {
        // First, set the final schedule
        const res = await fetch(apiBase + `/api/v1/campaigns/${currentCampaignId}/final-schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ use_ai_recommendation: true })
        });
        const data = await res.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        // Populate campaign form with AI recommendations
        const recommendations = currentPrediction.recommendations || {};
        const populatedFields = [];
        const skippedFields = [];
        
        // Populate title
        if (recommendations.title && recommendations.title.value) {
            const titleEl = document.getElementById('title');
            if (titleEl) {
                // Title is now a text input, just set the value directly
                titleEl.value = recommendations.title.value;
                populatedFields.push('Campaign Title');
            }
        } else {
            skippedFields.push({field: 'Campaign Title', reason: 'No factual data available for this field'});
        }
        
        // Populate category
        if (recommendations.category && recommendations.category.value) {
            const categoryEl = document.getElementById('category');
            if (categoryEl) {
                categoryEl.value = recommendations.category.value;
                populatedFields.push('Category');
            }
        } else {
            skippedFields.push({field: 'Category', reason: 'No factual data available for this field'});
        }
        
        // Populate budget
        if (recommendations.budget && recommendations.budget.value) {
            const budgetEl = document.getElementById('budget');
            if (budgetEl) {
                budgetEl.value = recommendations.budget.value;
                populatedFields.push('Budget');
            }
        } else {
            skippedFields.push({field: 'Budget', reason: 'No factual data available for this field'});
        }
        
        // Populate staff count
        if (recommendations.staff_count && recommendations.staff_count.value) {
            const staffCountEl = document.getElementById('staff_count');
            if (staffCountEl) {
                staffCountEl.value = recommendations.staff_count.value;
                populatedFields.push('Staff Count');
            }
        } else {
            skippedFields.push({field: 'Staff Count', reason: 'No factual data available for this field'});
        }
        
        // Populate assigned staff (multi-select)
        if (recommendations.assigned_staff && Array.isArray(recommendations.assigned_staff.value) && recommendations.assigned_staff.value.length > 0) {
            const assignedStaffEl = document.getElementById('assigned_staff');
            if (assignedStaffEl && typeof initMultiSelectEnhanced === 'function') {
                // Clear existing selections
                Array.from(assignedStaffEl.options).forEach(opt => opt.selected = false);
                
                // Select recommended staff
                recommendations.assigned_staff.value.forEach(staffName => {
                    const option = Array.from(assignedStaffEl.options).find(opt => opt.textContent === staffName || opt.value === staffName);
                    if (option) {
                        option.selected = true;
                    } else {
                        // Add as new option and select
                        const newOption = document.createElement('option');
                        newOption.value = staffName;
                        newOption.textContent = staffName;
                        newOption.selected = true;
                        assignedStaffEl.appendChild(newOption);
                    }
                });
                
                // Trigger multi-select update if function exists
                if (typeof assignedStaffEl.updateTags === 'function') {
                    assignedStaffEl.updateTags();
                }
                populatedFields.push('Assigned Staff');
            }
        } else {
            skippedFields.push({field: 'Assigned Staff', reason: 'No factual data available for this field'});
        }
        
        // Populate materials (multi-select)
        if (recommendations.materials && recommendations.materials.value && typeof recommendations.materials.value === 'object' && Object.keys(recommendations.materials.value).length > 0) {
            const materialsEl = document.getElementById('materials_json');
            if (materialsEl && typeof initMultiSelectEnhanced === 'function') {
                // Clear existing selections
                Array.from(materialsEl.options).forEach(opt => opt.selected = false);
                
                // Select recommended materials
                Object.keys(recommendations.materials.value).forEach(materialName => {
                    const option = Array.from(materialsEl.options).find(opt => opt.textContent === materialName || opt.value === materialName);
                    if (option) {
                        option.selected = true;
                    } else {
                        // Add as new option and select
                        const newOption = document.createElement('option');
                        newOption.value = materialName;
                        newOption.textContent = materialName;
                        newOption.selected = true;
                        materialsEl.appendChild(newOption);
                    }
                });
                
                // Trigger multi-select update if function exists
                if (typeof materialsEl.updateTags === 'function') {
                    materialsEl.updateTags();
                }
                populatedFields.push('Materials');
            }
        } else {
            skippedFields.push({field: 'Materials', reason: 'No factual data available for this field'});
        }
        
        // Update final schedule display in form
        const finalScheduleField = document.getElementById('final_schedule_field');
        const finalScheduleValue = document.getElementById('final_schedule_value');
        
        if (currentPrediction && currentPrediction.suggested_datetime) {
            // Format the datetime for display
            const scheduleDate = new Date(currentPrediction.suggested_datetime);
            const formattedDate = scheduleDate.toLocaleString('en-US', {
                dateStyle: 'long',
                timeStyle: 'short'
            });
            
            if (finalScheduleValue) {
                finalScheduleValue.textContent = formattedDate;
            }
            
            if (finalScheduleField) {
                finalScheduleField.style.display = 'block';
            }
        }
        
        // Scroll to planning form
        const planningSection = document.getElementById('planning-section');
        if (planningSection) {
            setTimeout(() => {
                planningSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
        }
        
        // Build summary message with HTML formatting for better readability
        let messageHtml = '<div style="text-align:left;">';
        messageHtml += '<p style="margin:0 0 12px 0; font-weight:600; color:#059669;">✓ AI recommendations accepted!</p>';
        
        if (populatedFields.length > 0) {
            messageHtml += '<div style="margin-bottom:12px;">';
            messageHtml += '<p style="margin:0 0 6px 0; font-weight:600; color:#166534;">Populated fields (' + populatedFields.length + '):</p>';
            messageHtml += '<ul style="margin:0; padding-left:20px; color:#334155;">';
            populatedFields.forEach(field => {
                messageHtml += '<li style="margin:2px 0;">' + field + '</li>';
            });
            messageHtml += '</ul></div>';
        }
        
        if (skippedFields.length > 0) {
            messageHtml += '<div style="margin-bottom:8px;">';
            messageHtml += '<p style="margin:0 0 6px 0; font-weight:600; color:#92400e;">Fields not populated (' + skippedFields.length + '):</p>';
            messageHtml += '<ul style="margin:0; padding-left:20px; color:#64748b; font-size:13px;">';
            skippedFields.forEach(item => {
                messageHtml += '<li style="margin:4px 0;"><strong>' + item.field + ':</strong> ' + item.reason + '</li>';
            });
            messageHtml += '</ul></div>';
        }
        
        if (populatedFields.length === 0 && skippedFields.length === 0) {
            messageHtml += '<p style="margin:0; color:#334155;">Final schedule has been set.</p>';
        }
        messageHtml += '</div>';
        
        // Use customAlert for better formatting (if available) or fallback to alert
        if (typeof customAlert === 'function') {
            await customAlert(messageHtml, 'AI Recommendations Applied');
        } else {
            // Fallback to plain text alert
            let plainMessage = 'AI recommendations accepted!\n\n';
            if (populatedFields.length > 0) {
                plainMessage += 'Populated: ' + populatedFields.join(', ') + '\n';
            }
            if (skippedFields.length > 0) {
                plainMessage += '\nNot populated:\n';
                skippedFields.forEach(item => {
                    plainMessage += '- ' + item.field + '\n';
                });
            }
            alert(plainMessage);
        }
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to accept recommendation: ' + err.message);
    }
}

async function checkConflicts() {
    if (!currentCampaignId || !currentPrediction) {
        alert('Please get a prediction first');
        return;
    }
    
    try {
        const res = await fetch(apiBase + `/api/v1/campaigns/${currentCampaignId}/check-conflicts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ proposed_datetime: currentPrediction.suggested_datetime })
        });
        const data = await res.json();
        
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        let message = `Conflict Check Results:\n\n`;
        message += `Proposed: ${data.proposed_datetime}\n`;
        message += `Has Conflicts: ${data.has_conflicts ? 'YES' : 'NO'}\n\n`;
        
        if (data.has_conflicts) {
            if (data.campaign_conflicts && data.campaign_conflicts.length > 0) {
                message += `Campaign Conflicts:\n`;
                data.campaign_conflicts.forEach(c => {
                    message += `- ${c.title} (${c.final_schedule_datetime})\n`;
                });
            }
            if (data.event_conflicts && data.event_conflicts.length > 0) {
                message += `\nEvent/Seminar Conflicts:\n`;
                data.event_conflicts.forEach(e => {
                    message += `- ${e.name} (${e.event_date} ${e.event_time}) at ${e.venue || 'N/A'}\n`;
                });
            }
        } else {
            message += 'No conflicts found! Safe to schedule.';
        }
        
        alert(message);
    } catch (err) {
        alert('Failed to check conflicts: ' + err.message);
    }
}

async function overrideSchedule() {
    if (!currentCampaignId) {
        if (typeof customAlert === 'function') {
            await customAlert('Please select a campaign first', 'Notice');
        } else {
            alert('Please select a campaign first');
        }
        return;
    }
    
    // Show custom modal for date/time selection
    openOverrideScheduleModal();
}

function openOverrideScheduleModal() {
    // Create modal if it doesn't exist
    let modal = document.getElementById('overrideScheduleModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'overrideScheduleModal';
        modal.innerHTML = `
            <div style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; display:flex; align-items:center; justify-content:center;">
                <div style="background:white; border-radius:12px; width:100%; max-width:450px; box-shadow:0 25px 50px rgba(0,0,0,0.25); overflow:hidden;">
                    <div style="background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); color:white; padding:16px 20px;">
                        <h3 style="margin:0; font-size:18px; font-weight:600;">
                            <i class="fas fa-calendar-alt" style="margin-right:8px;"></i>Override Schedule
                        </h3>
                    </div>
                    <div style="padding:24px;">
                        <p style="margin:0 0 20px 0; color:#64748b; font-size:14px;">
                            Set a manual schedule date and time for this campaign. This will override any AI recommendations.
                        </p>
                        <div style="margin-bottom:16px;">
                            <label style="display:block; margin-bottom:6px; font-weight:600; color:#334155; font-size:14px;">
                                Schedule Date <span style="color:#dc2626;">*</span>
                            </label>
                            <input type="date" id="overrideDate" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
                        </div>
                        <div style="margin-bottom:20px;">
                            <label style="display:block; margin-bottom:6px; font-weight:600; color:#334155; font-size:14px;">
                                Schedule Time <span style="color:#dc2626;">*</span>
                            </label>
                            <input type="time" id="overrideTime" style="width:100%; padding:12px 14px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
                        </div>
                        <div style="display:flex; gap:12px; justify-content:flex-end;">
                            <button type="button" onclick="closeOverrideScheduleModal()" style="padding:10px 20px; border:2px solid #e2e8f0; background:white; color:#64748b; border-radius:6px; font-weight:600; cursor:pointer; font-size:14px;">
                                Cancel
                            </button>
                            <button type="button" onclick="submitOverrideSchedule()" style="padding:10px 20px; border:none; background:#4c8a89; color:white; border-radius:6px; font-weight:600; cursor:pointer; font-size:14px;">
                                <i class="fas fa-check" style="margin-right:6px;"></i>Apply Schedule
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Set default values to current date/time
    const now = new Date();
    const dateStr = now.toISOString().split('T')[0];
    const timeStr = now.toTimeString().slice(0, 5);
    document.getElementById('overrideDate').value = dateStr;
    document.getElementById('overrideTime').value = timeStr;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeOverrideScheduleModal() {
    const modal = document.getElementById('overrideScheduleModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

async function submitOverrideSchedule() {
    const dateVal = document.getElementById('overrideDate').value;
    const timeVal = document.getElementById('overrideTime').value;
    
    if (!dateVal || !timeVal) {
        if (typeof customAlert === 'function') {
            await customAlert('Please select both date and time', 'Notice');
        } else {
            alert('Please select both date and time');
        }
        return;
    }
    
    const manualDateTime = `${dateVal} ${timeVal}:00`;
    
    try {
        const res = await fetch(apiBase + `/api/v1/campaigns/${currentCampaignId}/final-schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ final_schedule_datetime: manualDateTime })
        });
        const data = await res.json();
        
        if (data.error) {
            if (typeof customAlert === 'function') {
                await customAlert('Error: ' + data.error, 'Error');
            } else {
                alert('Error: ' + data.error);
            }
            return;
        }
        
        closeOverrideScheduleModal();
        
        if (typeof customAlert === 'function') {
            await customAlert('<div style="text-align:center;"><i class="fas fa-check-circle" style="font-size:48px; color:#059669; margin-bottom:12px;"></i><p style="margin:0; font-weight:600; color:#059669;">Schedule Override Successful!</p><p style="margin:8px 0 0 0; color:#64748b;">New schedule: ' + manualDateTime + '</p></div>', 'Success');
        } else {
            alert('Manual schedule override successful!');
        }
        
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        if (typeof customAlert === 'function') {
            await customAlert('Failed to override schedule: ' + err.message, 'Error');
        } else {
            alert('Failed to override schedule: ' + err.message);
        }
    }
}

// Calendar initialization (Gantt chart removed - using Calendar view only)

// CENTRALIZED: Refresh all campaign views after data changes
// This ensures Calendar stays synchronized
function refreshAllCampaignViews() {
    console.log('refreshAllCampaignViews() - Refreshing all campaign views...');
    // Reload campaigns from API (updates allCampaigns array)
    loadCampaigns(); // This will call calendar.refetchEvents() internally
}

// Calendar
function initCalendar() {
    console.log('=== initCalendar() called ===');
    const calendarEl = document.getElementById('calendar');
    
    // DIAGNOSTIC: Check if calendar element exists
    if (!calendarEl) {
        console.error('CRITICAL: Calendar container element not found! Looking for #calendar');
        console.error('Available elements with id containing "calendar":', 
            Array.from(document.querySelectorAll('[id*="calendar"]')).map(el => el.id));
        return;
    }
    console.log('✓ Calendar container element found:', calendarEl);
    
    // DIAGNOSTIC: Check if FullCalendar is loaded
    // FullCalendar v6 uses different namespace - check both FullCalendar and window.FullCalendar
    let FullCalendarLib = null;
    if (typeof FullCalendar !== 'undefined') {
        FullCalendarLib = FullCalendar;
        console.log('✓ FullCalendar library found via FullCalendar namespace');
    } else if (typeof window.FullCalendar !== 'undefined') {
        FullCalendarLib = window.FullCalendar;
        console.log('✓ FullCalendar library found via window.FullCalendar');
    } else if (typeof window !== 'undefined' && window.FullCalendar) {
        FullCalendarLib = window.FullCalendar;
        console.log('✓ FullCalendar library found via window object');
    }
    
    if (!FullCalendarLib) {
        console.error('CRITICAL: FullCalendar library not loaded!');
        console.error('Available globals:', Object.keys(window).filter(k => k.toLowerCase().includes('calendar')));
        console.error('Script loading check:', document.querySelectorAll('script[src*="fullcalendar"]').length > 0 ? 'Script tag found' : 'Script tag NOT found');
        
        // Try to wait for script to load if script tag exists but library isn't ready
        const scriptTag = document.querySelector('script[src*="fullcalendar"]');
        if (scriptTag && !scriptTag.onload) {
            console.log('Script tag found but library not loaded yet. Waiting for load...');
            scriptTag.onload = function() {
                console.log('FullCalendar script loaded, retrying initialization...');
                setTimeout(() => {
                    if (typeof FullCalendar !== 'undefined' || typeof window.FullCalendar !== 'undefined') {
                        initCalendar();
                    }
                }, 100);
            };
            return; // Exit and wait for script to load
        }
        
        calendarEl.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;"><p>Error: FullCalendar library failed to load. Please refresh the page.</p><p style="font-size:12px; margin-top:8px;">If the problem persists, check your internet connection or contact support.</p></div>';
        return;
    }
    console.log('✓ FullCalendar library loaded successfully');
    
    // DIAGNOSTIC: Check if calendar already exists
    if (calendar) {
        console.log('Calendar already initialized, skipping...');
        return;
    }
    
    console.log('Initializing FullCalendar with library:', FullCalendarLib);
    console.log('FullCalendarLib.Calendar:', typeof FullCalendarLib.Calendar);
    
    // Verify Calendar constructor exists
    if (!FullCalendarLib.Calendar) {
        console.error('CRITICAL: FullCalendar.Calendar constructor not found!');
        console.error('FullCalendarLib object keys:', Object.keys(FullCalendarLib));
        calendarEl.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;"><p>Error: FullCalendar.Calendar constructor not found.</p><p style="font-size:12px; margin-top:8px;">Library loaded but Calendar class missing. Please refresh the page.</p></div>';
        return;
    }
    
    try {
        calendar = new FullCalendarLib.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        views: {
            dayGridMonth: {
                titleFormat: { year: 'numeric', month: 'long' },
                dayHeaderFormat: { weekday: 'short' },
                dayMaxEvents: 3,
                moreLinkClick: 'popover'
            },
            timeGridWeek: {
                titleFormat: { year: 'numeric', month: 'short', day: 'numeric' },
                slotMinTime: '06:00:00',
                slotMaxTime: '22:00:00',
                slotDuration: '01:00:00',
                allDaySlot: true
            },
            listWeek: {
                titleFormat: { year: 'numeric', month: 'long', day: 'numeric' }
            }
        },
        firstDay: 1, // Start week on Monday
        height: 'auto',
        aspectRatio: 1.8,
        events: async function(fetchInfo, successCallback, failureCallback) {
            try {
                const start = fetchInfo.startStr;
                const end = fetchInfo.endStr;
                console.log('=== Calendar events fetch ===');
                console.log('Date range:', start, 'to', end);
                console.log('Using allCampaigns array (same source as Gantt Chart)');
                console.log('allCampaigns count:', allCampaigns ? allCampaigns.length : 0);
                
                // FIX: Use the same allCampaigns array that Gantt Chart uses
                // This ensures both views show the same campaigns
                const campaigns = allCampaigns || [];
                console.log('Campaigns from allCampaigns:', campaigns.length, campaigns);
                
                // Filter campaigns that fall within or overlap the date range
                const startDate = new Date(start);
                const endDate = new Date(end);
                
                // Get status filter value
                const statusFilter = document.getElementById('calendarStatusFilter')?.value || '';
                
                const filteredCampaigns = campaigns.filter(c => {
                    // Apply status filter if selected
                    if (statusFilter) {
                        const campaignStatus = (c.status || '').toLowerCase();
                        if (campaignStatus !== statusFilter.toLowerCase()) {
                            return false;
                        }
                    }
                    
                    // Include if campaign has any date information
                    if (!c.start_date && !c.end_date && !c.draft_schedule_datetime && !c.ai_recommended_datetime && !c.final_schedule_datetime) {
                        return false;
                    }
                    
                    // Check if campaign dates overlap with calendar view range
                    if (c.start_date) {
                        const campaignStart = new Date(c.start_date);
                        if (campaignStart <= endDate) {
                            if (c.end_date) {
                                const campaignEnd = new Date(c.end_date);
                                if (campaignEnd >= startDate) {
                                    return true; // Campaign overlaps with view range
                                }
                            } else {
                                return true; // Has start date but no end date, include it
                            }
                        }
                    }
                    
                    // Check schedule datetimes
                    const scheduleDates = [
                        c.draft_schedule_datetime,
                        c.ai_recommended_datetime,
                        c.final_schedule_datetime
                    ].filter(Boolean);
                    
                    for (const scheduleDate of scheduleDates) {
                        const schedule = new Date(scheduleDate);
                        if (schedule >= startDate && schedule <= endDate) {
                            return true;
                        }
                    }
                    
                    return false;
                });
                
                console.log('Filtered campaigns for date range:', filteredCampaigns.length);
                
                const events = [];
                
                // Add campaign date ranges
                // FIX: Use filteredCampaigns (from allCampaigns) instead of separate API call
                filteredCampaigns.forEach(c => {
                    console.log('Processing campaign:', c.id, c.title, 'start_date:', c.start_date, 'end_date:', c.end_date);
                    
                    // Add campaign date range if start_date exists
                    if (c.start_date) {
                        const eventObj = {
                            id: 'campaign-' + c.id,
                            title: '#' + c.id + ' ' + (c.title || 'Untitled Campaign'),
                            start: c.start_date,
                            end: c.end_date ? new Date(new Date(c.end_date).getTime() + 86400000).toISOString().split('T')[0] : new Date(new Date(c.start_date).getTime() + 86400000).toISOString().split('T')[0],
                            backgroundColor: getStatusColor(c.status),
                            borderColor: getStatusColor(c.status),
                            textColor: '#fff',
                            allDay: true,
                            extendedProps: {
                                type: 'campaign',
                                status: c.status,
                                location: c.location,
                                budget: c.budget
                            }
                        };
                        console.log('Adding campaign event:', eventObj);
                        events.push(eventObj);
                    }
                    
                    // Add draft schedule
                    if (c.draft_schedule_datetime) {
                        const draftEvent = {
                            id: 'draft-' + c.id,
                            title: (c.title || 'Untitled Campaign') + ' (Draft)',
                            start: c.draft_schedule_datetime,
                            backgroundColor: '#fbbf24',
                            borderColor: '#f59e0b',
                            textColor: '#000',
                            extendedProps: {
                                type: 'draft_schedule',
                                campaign_id: c.id
                            }
                        };
                        console.log('Adding draft schedule event:', draftEvent);
                        events.push(draftEvent);
                    }
                    
                    // Add AI recommended schedule
                    if (c.ai_recommended_datetime) {
                        const aiEvent = {
                            id: 'ai-' + c.id,
                            title: (c.title || 'Untitled Campaign') + ' (AI Recommended)',
                            start: c.ai_recommended_datetime,
                            backgroundColor: '#667eea',
                            borderColor: '#764ba2',
                            textColor: '#fff',
                            extendedProps: {
                                type: 'ai_recommended',
                                campaign_id: c.id
                            }
                        };
                        console.log('Adding AI recommended event:', aiEvent);
                        events.push(aiEvent);
                    }
                    
                    // Add final approved schedule
                    if (c.final_schedule_datetime) {
                        const finalEvent = {
                            id: 'final-' + c.id,
                            title: (c.title || 'Untitled Campaign') + ' (Final)',
                            start: c.final_schedule_datetime,
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            textColor: '#fff',
                            extendedProps: {
                                type: 'final_schedule',
                                campaign_id: c.id
                            }
                        };
                        console.log('Adding final schedule event:', finalEvent);
                        events.push(finalEvent);
                    }
                });
                
                console.log('Total events created:', events.length);
                console.log('Events array:', events);
                successCallback(events);
            } catch (err) {
                failureCallback(err);
            }
        },
        eventClick: function(info) {
            const event = info.event;
            const extended = event.extendedProps;
            let message = `Campaign: ${event.title}\n`;
            message += `Type: ${extended.type || 'campaign'}\n`;
            if (extended.status) message += `Status: ${extended.status}\n`;
            if (extended.location) message += `Location: ${extended.location}\n`;
            if (extended.budget) message += `Budget: ₱${parseFloat(extended.budget).toLocaleString()}\n`;
            message += `Start: ${event.start.toLocaleString()}\n`;
            if (event.end) message += `End: ${event.end.toLocaleString()}`;
            alert(message);
        },
        eventDisplay: 'block',
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: 'short'
        }
        });
        console.log('Calendar configuration created successfully');
    } catch (initError) {
        console.error('CRITICAL: Calendar initialization failed:', initError);
        if (calendarEl) {
            calendarEl.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;"><p>Error initializing calendar: ' + initError.message + '</p><p style="font-size:12px; margin-top:8px;">Please check the browser console for details.</p></div>';
        }
        return;
    }
    
    // DIAGNOSTIC: Log before rendering
    console.log('Calendar instance created, calling render()...');
    try {
        calendar.render();
        console.log('✓ Calendar rendered successfully');
    } catch (renderError) {
        console.error('CRITICAL: Calendar render() failed:', renderError);
        if (calendarEl) {
            calendarEl.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;"><p>Error rendering calendar: ' + renderError.message + '</p></div>';
        }
    }
}

// REMOVED: calendarView() function - no longer needed since custom Month/Week buttons were removed
// FullCalendar's built-in toolbar (headerToolbar) now handles all view switching

function getStatusColor(status) {
    const colors = {
        draft: '#fbbf24',
        pending: '#f59e0b',
        approved: '#3b82f6',
        ongoing: '#10b981',
        completed: '#8b5cf6',
        scheduled: '#06b6d4',
        active: '#10b981',
        archived: '#9ca3af',
    };
    return colors[status] || '#6b7280';
}

// Resources
async function loadResources() {
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const campaigns = data.data || [];
        
        let totalBudget = 0;
        let totalStaff = 0;
        let activeCount = 0;
        let materials = {};
        let budgetByStatus = { draft: 0, pending: 0, approved: 0, ongoing: 0, completed: 0 };
        
        campaigns.forEach(c => {
            if (c.budget) {
                const budget = parseFloat(c.budget);
                totalBudget += budget;
                const status = c.status || 'draft';
                if (budgetByStatus.hasOwnProperty(status)) {
                    budgetByStatus[status] += budget;
                }
            }
            if (c.staff_count) totalStaff += parseInt(c.staff_count);
            if (['ongoing', 'active', 'approved'].includes(c.status)) activeCount++;
            if (c.materials_json) {
                try {
                    const m = typeof c.materials_json === 'string' ? JSON.parse(c.materials_json) : c.materials_json;
                    Object.keys(m).forEach(k => {
                        materials[k] = (materials[k] || 0) + (parseInt(m[k]) || 0);
                    });
                } catch (e) {}
            }
        });
        
        // Safely update elements only if they exist
        const totalBudgetEl = document.getElementById('totalBudget');
        const totalStaffEl = document.getElementById('totalStaff');
        const activeCampaignsEl = document.getElementById('activeCampaigns');
        const budgetBreakdownEl = document.getElementById('budgetBreakdown');
        const staffBreakdownEl = document.getElementById('staffBreakdown');
        const campaignBreakdownEl = document.getElementById('campaignBreakdown');
        const materialsEl = document.getElementById('materialsUsed');
        
        if (totalBudgetEl) totalBudgetEl.textContent = '₱' + totalBudget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        if (totalStaffEl) totalStaffEl.textContent = totalStaff;
        if (activeCampaignsEl) activeCampaignsEl.textContent = activeCount;
        
        // Update breakdowns
        const totalCampaigns = campaigns.length;
        if (budgetBreakdownEl) budgetBreakdownEl.textContent = `${totalCampaigns} campaign${totalCampaigns !== 1 ? 's' : ''}`;
        if (staffBreakdownEl) staffBreakdownEl.textContent = `${totalCampaigns} campaign${totalCampaigns !== 1 ? 's' : ''} assigned`;
        if (campaignBreakdownEl) campaignBreakdownEl.textContent = `${totalCampaigns - activeCount} inactive`;
        
        if (materialsEl) {
            if (Object.keys(materials).length > 0) {
                const materialsList = Object.entries(materials)
                    .map(([k, v]) => `${k}: ${v}`)
                    .join(', ');
                materialsEl.textContent = materialsList.length > 60 ? materialsList.substring(0, 60) + '...' : materialsList;
                materialsEl.title = Object.entries(materials).map(([k, v]) => `${k}: ${v}`).join('\n');
            } else {
                materialsEl.textContent = 'No materials allocated';
                materialsEl.style.fontSize = '14px';
            }
        }
    } catch (err) {
        console.error('Failed to load resources:', err);
    }
}

// Switch Campaign View (List, Calendar, Resources)
let inlineCalendar = null;

function switchCampaignView(view) {
    const listView = document.getElementById('campaignListView');
    const calendarView = document.getElementById('campaignCalendarView');
    const resourcesView = document.getElementById('campaignResourcesView');
    const listBtn = document.getElementById('campaignListViewBtn');
    const calendarBtn = document.getElementById('campaignCalendarViewBtn');
    const resourcesBtn = document.getElementById('campaignResourcesViewBtn');
    
    // Hide all views
    if (listView) listView.style.display = 'none';
    if (calendarView) calendarView.style.display = 'none';
    if (resourcesView) resourcesView.style.display = 'none';
    
    // Remove active class from all buttons
    if (listBtn) listBtn.classList.remove('active');
    if (calendarBtn) calendarBtn.classList.remove('active');
    if (resourcesBtn) resourcesBtn.classList.remove('active');
    
    // Show selected view and activate button
    if (view === 'list') {
        if (listView) listView.style.display = 'block';
        if (listBtn) listBtn.classList.add('active');
        loadCampaigns();
    } else if (view === 'calendar') {
        if (calendarView) calendarView.style.display = 'block';
        if (calendarBtn) calendarBtn.classList.add('active');
        renderInlineCalendar();
    } else if (view === 'resources') {
        if (resourcesView) resourcesView.style.display = 'block';
        if (resourcesBtn) resourcesBtn.classList.add('active');
        loadResourcesInline();
    }
}

// Render inline calendar for switch view
async function renderInlineCalendar() {
    const container = document.getElementById('calendarInline');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align:center; color:#64748b; padding:40px;"><i class="fas fa-spinner fa-spin"></i> Loading calendar...</p>';
    
    try {
        const statusFilter = document.getElementById('calendarStatusFilterInline')?.value || '';
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        let campaigns = data.data || [];
        
        // Apply status filter
        if (statusFilter) {
            campaigns = campaigns.filter(c => c.status === statusFilter);
        }
        
        const statusColors = {
            'draft': '#9ca3af',
            'pending': '#f59e0b',
            'approved': '#3b82f6',
            'scheduled': '#06b6d4',
            'ongoing': '#22c55e',
            'active': '#22c55e',
            'completed': '#a855f7',
            'archived': '#78716c',
            'cancelled': '#ef4444'
        };
        
        const events = campaigns.map(c => {
            let startDate = c.final_schedule_datetime || c.draft_schedule_datetime || c.start_date;
            let endDate = c.end_date || startDate;
            
            return {
                id: c.id,
                title: '#' + c.id + ' ' + (c.title || 'Untitled'),
                start: startDate,
                end: endDate,
                backgroundColor: statusColors[c.status] || '#9ca3af',
                borderColor: statusColors[c.status] || '#9ca3af',
                extendedProps: { campaign: c }
            };
        }).filter(e => e.start);
        
        container.innerHTML = '';
        
        if (inlineCalendar) {
            inlineCalendar.destroy();
        }
        
        inlineCalendar = new FullCalendar.Calendar(container, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            events: events,
            eventClick: function(info) {
                const campaign = info.event.extendedProps.campaign;
                if (campaign) viewCampaign(campaign.id);
            },
            height: 'auto',
            eventDisplay: 'block'
        });
        
        inlineCalendar.render();
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626; padding:40px;">Error loading calendar: ' + err.message + '</p>';
    }
}

// Load resources for inline view
async function loadResourcesInline() {
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const campaigns = data.data || [];
        
        let totalBudget = 0;
        let totalStaff = 0;
        let activeCampaigns = 0;
        let inactiveCampaigns = 0;
        
        campaigns.forEach(c => {
            totalBudget += parseFloat(c.budget || 0);
            totalStaff += parseInt(c.staff_count || 0);
            if (['scheduled', 'ongoing', 'active'].includes(c.status)) {
                activeCampaigns++;
            } else {
                inactiveCampaigns++;
            }
        });
        
        const budgetEl = document.getElementById('totalBudgetInline');
        const staffEl = document.getElementById('totalStaffInline');
        const activeEl = document.getElementById('activeCampaignsInline');
        const breakdownEl = document.getElementById('budgetBreakdownInline');
        const staffBreakdownEl = document.getElementById('staffBreakdownInline');
        const activeBreakdownEl = document.getElementById('activeCampaignsBreakdownInline');
        
        if (budgetEl) budgetEl.textContent = '₱' + totalBudget.toLocaleString('en-PH', {minimumFractionDigits: 2});
        if (staffEl) staffEl.textContent = totalStaff;
        if (activeEl) activeEl.textContent = activeCampaigns;
        if (breakdownEl) breakdownEl.textContent = campaigns.length + ' campaigns';
        if (staffBreakdownEl) staffBreakdownEl.textContent = campaigns.length + ' campaigns assigned';
        if (activeBreakdownEl) activeBreakdownEl.textContent = inactiveCampaigns + ' inactive';
        
        // Load materials from budget
        try {
            const budgetRes = await fetch(apiBase + '/api/v1/budgets', {
                headers: { 'Authorization': 'Bearer ' + getToken() }
            });
            const budgetData = await budgetRes.json();
            if (budgetData.success && budgetData.summary) {
                const materialsEl = document.getElementById('materialsUsedInline');
                if (materialsEl) {
                    const items = budgetData.data || [];
                    const materialNames = items.slice(0, 3).map(i => i.item_name).join(', ');
                    materialsEl.innerHTML = materialNames ? materialNames + (items.length > 3 ? '...' : '') : 'No materials';
                }
            }
        } catch (e) {
            console.error('Failed to load budget for materials:', e);
        }
    } catch (err) {
        console.error('Failed to load resources inline:', err);
    }
}

// Toggle budget visibility for inline view
let budgetVisibleInline = false;
function toggleBudgetVisibilityInline() {
    budgetVisibleInline = !budgetVisibleInline;
    const budgetEl = document.getElementById('totalBudgetInline');
    const hiddenEl = document.getElementById('totalBudgetHiddenInline');
    const iconEl = document.getElementById('budgetToggleIconInline');
    
    if (budgetVisibleInline) {
        if (budgetEl) budgetEl.style.display = 'block';
        if (hiddenEl) hiddenEl.style.display = 'none';
        if (iconEl) iconEl.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        if (budgetEl) budgetEl.style.display = 'none';
        if (hiddenEl) hiddenEl.style.display = 'block';
        if (iconEl) iconEl.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Campaigns
    async function loadCampaigns() {
    // FIX: Refresh role detection before loading campaigns to ensure role is available
    refreshRoleDetection();
    
    const tbody = document.getElementById('campaignTable');
    if (!tbody) {
        console.error('loadCampaigns() - Campaign table not found');
        return;
    }
    tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:24px; color: #64748b;">Loading campaigns...</td></tr>';
    
    // Check token before making API call
    const token = getToken();
    if (!token || token.trim() === '') {
        console.error('loadCampaigns() - No token available, skipping API call');
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color: #dc2626;">Authentication required. Please refresh the page.</td></tr>';
        return;
    }
    
    try {
        console.log('loadCampaigns() - Making API call with token (length:', token.length + ')');
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            }
        });
        
        console.log('loadCampaigns() - Response status:', res.status);
        
        // Read response as text first (can be parsed as JSON or shown as error)
        const responseText = await res.text();
        console.log('loadCampaigns() - Response text length:', responseText.length);
        
        if (!res.ok) {
            console.error('loadCampaigns() - API error:', res.status, responseText);
            let errorMessage = `Failed to load campaigns (HTTP ${res.status})`;
            try {
                const errorData = JSON.parse(responseText);
                if (errorData.error) {
                    errorMessage = errorData.error;
                }
            } catch (e) {
                // If not JSON, use the raw text (truncated if too long)
                if (responseText && responseText.length < 200) {
                    errorMessage += ': ' + responseText;
                }
            }
            tbody.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:24px; color: #dc2626;">
                <strong>Failed to load campaigns.</strong><br>
                <small style="margin-top:8px; display:block; opacity:0.8;">${errorMessage}</small>
            </td></tr>`;
            return;
        }
        
        // Parse JSON from the text we already read
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('loadCampaigns() - JSON parse error:', parseError);
            console.error('loadCampaigns() - Response text:', responseText.substring(0, 500));
            tbody.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:24px; color: #dc2626;">
                <strong>Failed to parse response.</strong><br>
                <small style="margin-top:8px; display:block; opacity:0.8;">Invalid JSON format. Check console for details.</small>
            </td></tr>`;
            return;
        }
        
        console.log('loadCampaigns() - Received data:', data);
        allCampaigns = data.data || [];
        console.log('loadCampaigns() - Campaigns count:', allCampaigns.length);
        
        // Filter out archived campaigns from main list (they show in View Archived only)
        allCampaigns = allCampaigns.filter(c => {
            const status = (c.status || '').toLowerCase();
            return status !== 'archived';
        });
        console.log('loadCampaigns() - Filtered out archived, remaining:', allCampaigns.length);
        
        // RBAC: For Viewer, filter to show only approved/ongoing campaigns (read-only)
        const isViewerCheck = isViewer();
        if (isViewerCheck) {
            // Viewer can only see approved, ongoing, or scheduled campaigns (not drafts)
            allCampaigns = allCampaigns.filter(c => {
                const status = (c.status || '').toLowerCase();
                return status === 'approved' || status === 'ongoing' || status === 'scheduled' || status === 'active';
            });
            console.log('loadCampaigns() - Filtered campaigns for Viewer:', allCampaigns.length, 'approved/ongoing campaigns');
        }
        
        // If no campaigns after filtering, show message
        if (!allCampaigns.length) {
            if (isViewerCheck) {
                tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:24px; color: #64748b;">No approved campaigns available for viewing.</td></tr>';
            } else {
                tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:24px; color: #64748b;">No campaigns yet. Create a campaign to get started.</td></tr>';
            }
            return;
        }
        
        // Ensure campaigns are displayed (for Viewer to see the table structure)
        console.log('loadCampaigns() - Rendering', allCampaigns.length, 'campaigns');
        
        tbody.innerHTML = '';
        const select = document.getElementById('active_campaign');
        const automlSelect = document.getElementById('automl_campaign_id');
        
        // Clear and populate dropdowns
        if (select) {
            select.innerHTML = '';
        }
        if (automlSelect) {
            automlSelect.innerHTML = '<option value="">Select Campaign</option>';
        }
        
        console.log('loadCampaigns() - Populating dropdowns with', allCampaigns.length, 'campaigns');
        
        // Debug: Log role before rendering
        console.log('loadCampaigns() - Rendering campaigns. Current role:', currentUserRole, 'Role ID:', currentUserRoleId);
        console.log('loadCampaigns() - isAdmin():', isAdmin(), 'isViewer():', isViewer());
        
        // Populate AutoML dropdown immediately after campaigns are loaded
        populateAutoMLDropdown();
        
        allCampaigns.forEach(c => {
            const tr = document.createElement('tr');
            const formatDateTime = (dt) => {
                if (!dt) return '-';
                try {
                    const d = new Date(dt);
                    if (isNaN(d.getTime())) return dt; // Return as-is if invalid
                    return d.toLocaleString('en-US', {dateStyle: 'short', timeStyle: 'short'});
                } catch (e) {
                    return dt; // Return as-is if parsing fails
                }
            };
            tr.innerHTML = `
                <td>${c.id}</td>
                <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.title || ''}">${c.title || ''}</td>
                <td style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.category || '-'}">${c.category || '-'}</td>
                <td><span class="badge ${c.status || 'draft'}">${(c.status || 'draft').charAt(0).toUpperCase() + (c.status || 'draft').slice(1)}</span></td>
                <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.location || '-'}">${c.location || '-'}</td>
                <td style="white-space: nowrap;">
                    ${(() => {
                        // Debug: Log role detection
                        console.log('Rendering Actions for campaign', c.id, 'Status:', c.status, 'Current Role:', currentUserRole);
                        console.log('isAdmin():', isAdmin(), 'isViewer():', isViewer(), 'isStaff():', isStaff(), 'isSecretary():', isSecretary(), 'isKagawad():', isKagawad(), 'isCaptain():', isCaptain());
                        return '';
                    })()}
                    
                    ${isViewer() ? '<span style="color: #9ca3af; font-size: 12px; display: block; width: 100%; margin-bottom: 4px;">Read-only</span>' : ''}
                    
                    <!-- Staff Actions -->
                    ${isStaff() && !isViewer() ? `
                        ${c.status === 'draft' ? `
                            ${canEditCampaign(c.status) ? `<button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Edit</button>` : ''}
                            <button class="btn btn-primary" onclick="submitToSecretary(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Submit</button>
                        ` : ''}
                        ${c.status !== 'draft' ? `<span style="color: #9ca3af; font-size: 12px; display: block; width: 100%; margin-top: 4px;">View only</span>` : ''}
                    ` : ''}
                    
                    <!-- Secretary Actions -->
                    ${isSecretary() && !isViewer() ? `
                        ${c.status === 'draft' ? `
                            ${canEditCampaign(c.status) ? `<button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Edit</button>` : ''}
                            <button class="btn btn-primary" onclick="forwardToPending(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Forward</button>
                            <button class="btn btn-warning" onclick="returnForRevision(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;">Return</button>
                        ` : ''}
                        ${c.status === 'pending' ? `
                            ${canEditCampaign(c.status) ? `<button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Edit</button>` : ''}
                            <button class="btn btn-warning" onclick="returnForRevision(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;">Return</button>
                        ` : ''}
                    ` : ''}
                    
                    <!-- Kagawad Actions -->
                    ${isKagawad() && !isViewer() ? `
                        ${c.status === 'pending' ? `
                            <button class="btn btn-success" onclick="recommendApproval(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #10b981; color: white; border: none;">Recommend</button>
                            <button class="btn btn-warning" onclick="returnForRevision(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;">Return</button>
                        ` : ''}
                        ${c.status !== 'pending' ? `<span style="color: #9ca3af; font-size: 12px; display: block; width: 100%; margin-top: 4px;">View only</span>` : ''}
                    ` : ''}
                    
                    <!-- Captain Actions -->
                    ${isCaptain() && !isViewer() ? `
                        ${c.status === 'pending' ? `
                            <button class="btn btn-success" onclick="approveCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #10b981; color: white; border: none;">Approve</button>
                        ` : ''}
                        ${c.status === 'approved' ? `
                            <button class="btn btn-primary" onclick="finalizeSchedule(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Finalize</button>
                            <button class="btn btn-info" onclick="closeCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #3b82f6; color: white; border: none;">Close</button>
                        ` : ''}
                        ${c.status === 'ongoing' ? `
                            <button class="btn btn-info" onclick="closeCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px; background: #3b82f6; color: white; border: none;">Close</button>
                        ` : ''}
                        ${canEditCampaign(c.status) && c.status !== 'completed' && c.status !== 'archived' ? `
                            <button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Edit</button>
                        ` : ''}
                        ${c.status !== 'archived' && c.status !== 'completed' ? `
                            <button class="btn btn-secondary" onclick="archiveCampaign(${c.id})" style="padding: 3px 6px; font-size: 11px; margin: 1px;">Archive</button>
                        ` : c.status === 'archived' ? '<span style="color: #9ca3af; font-size: 12px; display: block; width: 100%; margin-top: 4px;">Archived</span>' : ''}
                    ` : ''}
                    
                    <!-- Admin Actions (Technical only, can override) - Always show if admin -->
                    ${isAdmin() && !isViewer() ? `
                        <div style="display: flex; flex-direction: column; gap: 4px; width: 230px;">
                            <div style="display: flex; gap: 4px; flex-wrap: nowrap;">
                                <button class="btn btn-secondary" onclick="viewCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">View</button>
                                <button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Edit</button>
                                ${c.status !== 'archived' ? `<button class="btn btn-secondary" onclick="archiveCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Archive</button>` : '<span style="color: #9ca3af; font-size: 12px;">Archived</span>'}
                            </div>
                            <div style="display: flex; gap: 4px; flex-wrap: nowrap;">
                                ${c.status === 'pending' ? `<button class="btn btn-success" onclick="approveCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; background: #10b981; color: white; border: none; margin: 0; white-space: nowrap;">Approve</button>` : ''}
                                ${c.status === 'approved' ? `<button class="btn btn-primary" onclick="finalizeSchedule(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Finalize</button>` : ''}
                                ${c.status === 'approved' || c.status === 'ongoing' || c.status === 'scheduled' ? `<button class="btn btn-info" onclick="closeCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; background: #3b82f6; color: white; border: none; margin: 0; white-space: nowrap;">Close</button>` : ''}
                            </div>
                        </div>
                    ` : ''}
                    
                    <!-- Fallback: If no role detected after refresh, show admin actions as default -->
                    ${!currentUserRole && !isViewer() ? `
                        <div style="display: flex; flex-direction: column; gap: 4px; width: 230px;">
                            <span style="color: #f59e0b; font-size: 10px;">Role issue</span>
                            <div style="display: flex; gap: 4px; flex-wrap: nowrap;">
                                <button class="btn btn-secondary" onclick="viewCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">View</button>
                                <button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Edit</button>
                                ${c.status !== 'archived' ? `<button class="btn btn-secondary" onclick="archiveCampaign(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Archive</button>` : '<span style="color: #9ca3af; font-size: 12px;">Archived</span>'}
                            </div>
                            <div style="display: flex; gap: 4px; flex-wrap: nowrap;">
                                ${c.status === 'approved' ? `<button class="btn btn-primary" onclick="finalizeSchedule(${c.id})" style="padding: 5px 10px; font-size: 12px; margin: 0; white-space: nowrap;">Finalize</button>` : ''}
                            </div>
                        </div>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(tr);
            
            // Populate active_campaign dropdown
            if (select) {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.id} - ${c.title || 'Untitled'}`;
                select.appendChild(opt);
            }
            
            // Populate automl_campaign_id dropdown (will be repopulated by populateAutoMLDropdown)
            // We populate here as backup, but populateAutoMLDropdown() will ensure it's correct
            if (automlSelect) {
                // Check if option already exists to avoid duplicates
                const existingOption = Array.from(automlSelect.options).find(opt => opt.value === c.id.toString());
                if (!existingOption) {
                    const automlOpt = document.createElement('option');
                    automlOpt.value = c.id.toString();
                    automlOpt.textContent = `${c.id} - ${c.title || 'Untitled'}`;
                    automlOpt.setAttribute('data-campaign-id', c.id.toString());
                    automlSelect.appendChild(automlOpt);
                }
            }
        });
        
        console.log('loadCampaigns() - Dropdowns populated. automlSelect options:', automlSelect ? automlSelect.options.length : 'N/A');
        
        // Force populate AutoML dropdown immediately after campaigns are loaded
        // This ensures the dropdown always has the latest campaign data from the database (NOT hardcoded)
        // The campaigns come from the /api/v1/campaigns endpoint which queries the campaigns table
        setTimeout(() => {
            console.log('loadCampaigns() - Calling populateAutoMLDropdown()');
            console.log('loadCampaigns() - allCampaigns data (from database):', allCampaigns);
            console.log('loadCampaigns() - Campaign count:', allCampaigns ? allCampaigns.length : 0);
            
            const populated = populateAutoMLDropdown();
            console.log('loadCampaigns() - populateAutoMLDropdown() returned:', populated);
            
            // Verify dropdown has options
            const automlSelectCheck = document.getElementById('automl_campaign_id');
            if (automlSelectCheck) {
                const finalCount = automlSelectCheck.options.length - 1;
                console.log('loadCampaigns() - Final dropdown option count:', finalCount);
                
                // Log all options for debugging
                const allOptions = Array.from(automlSelectCheck.options).map((o, idx) => ({
                    index: idx,
                    value: o.value,
                    text: o.textContent,
                    selected: o.selected
                }));
                console.log('loadCampaigns() - All dropdown options:', allOptions);
                
                // Update status message
                const statusEl = document.getElementById('automl_dropdown_status');
                if (statusEl) {
            if (finalCount > 0) {
                statusEl.textContent = `${finalCount} campaign(s) available - Click dropdown to select`;
                statusEl.style.color = 'rgba(255,255,255,0.9)';
            } else {
                statusEl.textContent = 'No campaigns available. Create a campaign first.';
                statusEl.style.color = 'rgba(255,255,255,0.7)';
            }
        }
        
        // If dropdown is still empty but we have campaigns, try again with more force
        if (finalCount === 0 && allCampaigns.length > 0) {
            console.warn('loadCampaigns() - Dropdown empty but campaigns exist! Retrying with force...');
            setTimeout(() => {
                // Force clear and repopulate
                automlSelectCheck.innerHTML = '<option value="">Select Campaign</option>';
                allCampaigns.forEach(c => {
                    if (c && c.id) {
                        const opt = document.createElement('option');
                        opt.value = c.id.toString();
                        opt.textContent = `${c.id} - ${c.title || 'Untitled Campaign'}`;
                        automlSelectCheck.appendChild(opt);
                    }
                });
                console.log('loadCampaigns() - Force populated, new count:', automlSelectCheck.options.length - 1);
            }, 300);
        }
        
        // Always validate form after populating dropdown
        validateAutoMLForm();
    }
    
    // Ensure button state is correct
    validateAutoMLForm();
        }, 150);
        
        if (!activeCampaignId && allCampaigns.length) {
            activeCampaignId = allCampaigns[0].id;
            if (select) {
                select.value = activeCampaignId;
            }
        }
        
        // Calendar will auto-refresh via refetchEvents() in loadCampaigns()
        // FIX: Refresh calendar events when campaigns are reloaded
        if (calendar) {
            calendar.refetchEvents();
            console.log('loadCampaigns() - Calendar events refetched');
        }
        loadResources();
        
        // Apply pagination after loading
        filterCampaigns();
    } catch (err) {
        console.error('loadCampaigns() - Exception caught:', err);
        console.error('loadCampaigns() - Error message:', err.message);
        console.error('loadCampaigns() - Error stack:', err.stack);
        
        let errorMessage = 'Failed to load campaigns.';
        if (err.message) {
            errorMessage += ' Error: ' + err.message;
        }
        
        // Count actual table columns
        const headerRow = document.querySelector('#campaignTable')?.closest('table')?.querySelector('thead tr');
        const columnCount = headerRow ? headerRow.children.length : 12;
        
        tbody.innerHTML = `<tr><td colspan="${columnCount}" style="text-align:center; padding:24px; color:#dc2626;">
            <strong>Failed to load campaigns.</strong><br>
            <small style="margin-top:8px; display:block; opacity:0.8;">${errorMessage}</small><br>
            <small style="margin-top:4px; display:block; opacity:0.6;">Check browser console (F12) for details.</small>
        </td></tr>`;
    }
}

// Pagination and filtering variables
let filteredCampaigns = [];
let currentPage = 1;
const itemsPerPage = 10;

// Filter campaigns based on search and filters
function filterCampaigns() {
    const searchTerm = (document.getElementById('campaignSearchInput')?.value || '').toLowerCase().trim();
    const categoryFilter = document.getElementById('campaignCategoryFilter')?.value || '';
    const statusFilter = document.getElementById('campaignStatusFilter')?.value || '';
    
    filteredCampaigns = allCampaigns.filter(c => {
        const matchesSearch = !searchTerm || (c.title || '').toLowerCase().includes(searchTerm);
        const matchesCategory = !categoryFilter || (c.category || '').toLowerCase() === categoryFilter.toLowerCase();
        const matchesStatus = !statusFilter || (c.status || '').toLowerCase() === statusFilter.toLowerCase();
        return matchesSearch && matchesCategory && matchesStatus;
    });
    
    currentPage = 1;
    renderPaginatedCampaigns();
}

// Clear all filters
function clearCampaignFilters() {
    const searchInput = document.getElementById('campaignSearchInput');
    const categoryFilter = document.getElementById('campaignCategoryFilter');
    const statusFilter = document.getElementById('campaignStatusFilter');
    
    if (searchInput) searchInput.value = '';
    if (categoryFilter) categoryFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    
    filterCampaigns();
}

// Change page
function changeCampaignPage(delta) {
    const totalPages = Math.ceil(filteredCampaigns.length / itemsPerPage);
    const newPage = currentPage + delta;
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        renderPaginatedCampaigns();
    }
}

// Render paginated campaigns
function renderPaginatedCampaigns() {
    const tbody = document.getElementById('campaignTable');
    if (!tbody) return;
    
    const totalPages = Math.max(1, Math.ceil(filteredCampaigns.length / itemsPerPage));
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageData = filteredCampaigns.slice(startIndex, endIndex);
    
    // Update pagination controls
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    const pageInfo = document.getElementById('pageInfo');
    
    if (prevBtn) prevBtn.disabled = currentPage <= 1;
    if (nextBtn) nextBtn.disabled = currentPage >= totalPages;
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages} (${filteredCampaigns.length} campaigns)`;
    
    if (pageData.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color: #64748b;">No campaigns match your filters.</td></tr>';
        return;
    }
    
    tbody.innerHTML = '';
    pageData.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${c.id}</td>
            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.title || ''}">${c.title || ''}</td>
            <td style="max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.category || '-'}">${c.category || '-'}</td>
            <td><span class="badge ${c.status || 'draft'}">${(c.status || 'draft').charAt(0).toUpperCase() + (c.status || 'draft').slice(1)}</span></td>
            <td style="max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${c.location || '-'}">${c.location || '-'}</td>
            <td style="white-space: nowrap;">
                <button class="btn btn-secondary" onclick="viewCampaign(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px;">View</button>
                ${!isViewer() ? `<button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px;">Edit</button>` : ''}
                ${!isViewer() && c.status === 'pending' ? `<button class="btn btn-success" onclick="approveCampaign(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px; background: #10b981; color: white; border: none;">Approve</button>` : ''}
                ${!isViewer() && c.status === 'approved' ? `<button class="btn btn-primary" onclick="finalizeSchedule(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px;">Finalize</button>` : ''}
                ${!isViewer() && (c.status === 'scheduled' || c.status === 'ongoing') ? `<button class="btn btn-success" onclick="closeCampaign(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px; background: #10b981; color: white; border: none;">Close</button>` : ''}
                ${!isViewer() && c.status !== 'archived' ? `<button class="btn btn-warning" onclick="archiveCampaign(${c.id})" style="padding: 4px 8px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;">Archive</button>` : ''}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Load campaigns with filters (alias for refresh)
function loadCampaignsWithFilters() {
    loadCampaigns();
}

// ============================================
// BUDGET MANAGEMENT FUNCTIONS
// ============================================

let allBudgetItems = [];

// Load budget data
async function loadBudgetData() {
    try {
        const res = await fetch(apiBase + '/api/v1/budgets', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        // Handle non-JSON responses
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            console.error('Budget load API returned non-JSON response:', res.status);
            return;
        }
        
        const data = await res.json();
        console.log('Budget data loaded:', data);
        
        if (data.success) {
            allBudgetItems = data.data || [];
            console.log('Budget items count:', allBudgetItems.length);
            renderBudgetTable();
            updateBudgetSummary(data.summary);
        } else {
            console.error('Budget load failed:', data.error || 'Unknown error');
        }
    } catch (err) {
        console.error('Failed to load budget data:', err);
    }
}

// Populate budget campaign dropdown
function populateBudgetCampaignDropdown() {
    const select = document.getElementById('budget_campaign_id');
    if (!select || !allCampaigns) return;
    
    select.innerHTML = '<option value="">Select Campaign...</option>';
    allCampaigns.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = `${c.id} - ${c.title || 'Untitled'}`;
        select.appendChild(opt);
    });
}

// Budget row counter
let budgetRowCounter = 1;

// Budget Modal Functions
function openBudgetModal() {
    const modal = document.getElementById('budgetModal');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        // Populate campaign dropdown
        populateBudgetCampaignDropdown();
    }
}

function closeBudgetModal() {
    const modal = document.getElementById('budgetModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close budget modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('budgetModal');
    if (modal && e.target === modal) {
        closeBudgetModal();
    }
});

// Populate budget campaign dropdown
function populateBudgetCampaignDropdown() {
    const select = document.getElementById('budget_campaign_id');
    if (!select || !allCampaigns) return;
    
    select.innerHTML = '<option value="">Select Campaign...</option>';
    allCampaigns.forEach(c => {
        const option = document.createElement('option');
        option.value = c.id;
        option.textContent = `${c.title} (ID: ${c.id})`;
        select.appendChild(option);
    });
}

// Add new budget row (without funding source - it's now overall)
function addBudgetRow() {
    const container = document.getElementById('budgetItemsContainer');
    if (!container) return;
    
    const newRow = document.createElement('tr');
    newRow.className = 'budget-item-row';
    newRow.setAttribute('data-row', budgetRowCounter++);
    newRow.innerHTML = `
        <td style="padding: 8px;"><input type="text" class="budget-item-name" placeholder="e.g., Leaflets" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
        <td style="padding: 8px;">
            <select class="budget-item-type" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                <option value="consumable">Consumable</option>
                <option value="material">Material</option>
            </select>
        </td>
        <td style="padding: 8px;"><input type="number" class="budget-item-qty" min="1" value="1" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
        <td style="padding: 8px;"><input type="number" class="budget-item-cost" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
        <td style="padding: 8px; text-align: center;">
            <button type="button" onclick="removeBudgetRow(this)" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; background: #ef4444; color: white; border: none;" title="Remove row">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    container.appendChild(newRow);
}

// Remove budget row
function removeBudgetRow(btn) {
    const row = btn.closest('tr');
    const container = document.getElementById('budgetItemsContainer');
    if (container && container.children.length > 1) {
        row.remove();
    } else {
        showBudgetStatus('At least one row is required', 'error');
    }
}

// Clear all budget rows
function clearBudgetRows() {
    const container = document.getElementById('budgetItemsContainer');
    if (!container) return;
    
    container.innerHTML = `
        <tr class="budget-item-row" data-row="0">
            <td style="padding: 8px;"><input type="text" class="budget-item-name" placeholder="e.g., Tarpaulin" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
            <td style="padding: 8px;">
                <select class="budget-item-type" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;">
                    <option value="consumable">Consumable</option>
                    <option value="material">Material</option>
                </select>
            </td>
            <td style="padding: 8px;"><input type="number" class="budget-item-qty" min="1" value="1" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
            <td style="padding: 8px;"><input type="number" class="budget-item-cost" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 8px; border: 1px solid #e2e8f0; border-radius: 6px;"></td>
            <td style="padding: 8px; text-align: center;">
                <button type="button" onclick="removeBudgetRow(this)" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; background: #ef4444; color: white; border: none;" title="Remove row">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `;
    budgetRowCounter = 1;
    document.getElementById('budget_campaign_id').value = '';
}

// Save all budget items
async function saveAllBudgetItems() {
    const campaignId = document.getElementById('budget_campaign_id')?.value;
    if (!campaignId) {
        showBudgetStatus('Please select a campaign', 'error');
        return;
    }
    
    // Get overall funding source
    const fundingSource = document.getElementById('budget_funding_source')?.value || 'government_allocated';
    
    const rows = document.querySelectorAll('#budgetItemsContainer .budget-item-row');
    const items = [];
    
    rows.forEach(row => {
        const itemName = row.querySelector('.budget-item-name')?.value?.trim();
        const itemType = row.querySelector('.budget-item-type')?.value;
        const quantity = parseInt(row.querySelector('.budget-item-qty')?.value) || 1;
        const unitCost = parseFloat(row.querySelector('.budget-item-cost')?.value) || 0;
        
        if (itemName) {
            items.push({ item_name: itemName, item_type: itemType, quantity, unit_cost: unitCost, funding_source: fundingSource });
        }
    });
    
    if (items.length === 0) {
        showBudgetStatus('Please enter at least one item', 'error');
        return;
    }
    
    showBudgetStatus('Saving ' + items.length + ' item(s)...', 'success');
    
    let savedCount = 0;
    let errorCount = 0;
    
    for (const item of items) {
        try {
            const res = await fetch(apiBase + '/api/v1/budgets', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + getToken()
                },
                body: JSON.stringify({
                    campaign_id: parseInt(campaignId),
                    ...item
                })
            });
            
            // Handle non-JSON responses
            const contentType = res.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                console.error('Budget API returned non-JSON response:', res.status);
                errorCount++;
                continue;
            }
            
            // Check for success - API returns 201 status and success: true
            if (res.ok && data.success) {
                savedCount++;
            } else {
                console.error('Budget save failed:', data.error || 'Unknown error');
                errorCount++;
            }
        } catch (err) {
            console.error('Budget save exception:', err);
            errorCount++;
        }
    }
    
    if (errorCount === 0) {
        showBudgetStatus(`Successfully saved ${savedCount} item(s)!`, 'success');
        clearBudgetRows();
        closeBudgetModal();
        loadBudgetData();
        // Show global toast notification (visible after modal closes)
        showSuccessToast(`Successfully saved ${savedCount} budget item(s)!`);
    } else if (savedCount > 0) {
        showBudgetStatus(`Saved ${savedCount} item(s), ${errorCount} failed`, 'error');
        loadBudgetData();
        showWarningToast(`Saved ${savedCount} item(s), ${errorCount} failed`);
    } else {
        showBudgetStatus(`Failed to save items. Please try again.`, 'error');
        showErrorToast(`Failed to save budget items. Check console for details.`);
    }
}

// Delete budget item
async function deleteBudgetItem(id) {
    if (!confirm('Are you sure you want to delete this budget item?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/budgets/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        const data = await res.json();
        if (data.success) {
            loadBudgetData();
        } else {
            alert(data.error || 'Failed to delete');
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// ========== Staff Functions ==========

let staffRoles = [];

// Load staff table
let currentStaffPage = 1;
const STAFF_PER_PAGE = 10;
let allStaffData = [];

function renderStaffTable() {
    const tbody = document.getElementById('staffTable');
    const pagination = document.getElementById('staffPagination');
    if (!tbody) return;
    
    const start = (currentStaffPage - 1) * STAFF_PER_PAGE;
    const end = Math.min(start + STAFF_PER_PAGE, allStaffData.length);
    const pageData = allStaffData.slice(start, end);
    const totalPages = Math.ceil(allStaffData.length / STAFF_PER_PAGE) || 1;
    
    tbody.innerHTML = '';
    pageData.forEach(s => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="font-weight: 600; color: #6366f1;">${s.id}</td>
            <td>${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.role)}</td>
            <td style="text-align: center; font-weight: 600;">${s.qty || 1}</td>
            <td>
                <button class="btn btn-danger" onclick="deleteStaffMember(${s.id})" style="padding: 4px 10px; font-size: 11px;" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    if (pagination) {
        pagination.innerHTML = '';
        if (allStaffData.length <= STAFF_PER_PAGE) return;
        
        const prev = document.createElement('button');
        prev.className = 'btn btn-secondary';
        prev.innerHTML = '&laquo; Prev';
        prev.style.padding = '6px 12px';
        prev.style.fontSize = '13px';
        prev.disabled = currentStaffPage === 1;
        prev.onclick = () => { if (currentStaffPage > 1) { currentStaffPage--; renderStaffTable(); } };
        pagination.appendChild(prev);
        
        for (let i = 1; i <= totalPages; i++) {
            const btn = document.createElement('button');
            btn.className = 'btn' + (i === currentStaffPage ? ' btn-primary' : ' btn-secondary');
            btn.textContent = i;
            btn.style.padding = '6px 12px';
            btn.style.fontSize = '13px';
            btn.onclick = () => { currentStaffPage = i; renderStaffTable(); };
            pagination.appendChild(btn);
        }
        
        const next = document.createElement('button');
        next.className = 'btn btn-secondary';
        next.innerHTML = 'Next &raquo;';
        next.style.padding = '6px 12px';
        next.style.fontSize = '13px';
        next.disabled = currentStaffPage === totalPages;
        next.onclick = () => { if (currentStaffPage < totalPages) { currentStaffPage++; renderStaffTable(); } };
        pagination.appendChild(next);
    }
}

async function loadStaffTable() {
    const tbody = document.getElementById('staffTable');
    if (!tbody) return;
    
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color: #64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/reference-staff', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        
        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color: #ef4444;">Error: ' + (data.error || 'Failed to load staff') + '</td></tr>';
            return;
        }
        
        allStaffData = data.data || data.staff || [];
        currentStaffPage = 1;
        
        if (allStaffData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color: #64748b;">No staff members yet. Click "Add Staff" to add one.</td></tr>';
            document.getElementById('staffPagination').innerHTML = '';
            return;
        }
        
        renderStaffTable();
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color: #ef4444;">Error: ' + err.message + '</td></tr>';
    }
}

// Load roles for the staff position dropdown
async function loadStaffRoles() {
    try {
        const res = await fetch(apiBase + '/api/v1/reference-staff/roles', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        
        if (data.success) {
            staffRoles = data.data || data.roles || [];
            const select = document.getElementById('staffPositionSelect');
            if (select) {
                select.innerHTML = '<option value="">Select Position...</option>';
                staffRoles.forEach(role => {
                    const opt = document.createElement('option');
                    opt.value = role;
                    opt.textContent = role;
                    select.appendChild(opt);
                });
            }
        }
    } catch (err) {
        console.error('Failed to load staff roles:', err);
    }
}

// Open Add Staff modal
function openAddStaffModal() {
    const modal = document.getElementById('staffModal');
    if (modal) {
        loadStaffRoles();
        document.getElementById('staffName').value = '';
        document.getElementById('staffPositionSelect').value = '';
        document.getElementById('newStaffPosition').value = '';
        document.getElementById('staffQty').value = 1;
        document.getElementById('staffStatus').style.display = 'none';
        toggleStaffPositionMode('existing');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
}

// Close Add Staff modal
function closeAddStaffModal() {
    const modal = document.getElementById('staffModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

// Close staff modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('staffModal');
    if (modal && e.target === modal) {
        closeAddStaffModal();
    }
});

// Toggle between existing position mode and add new mode
function toggleStaffPositionMode(mode) {
    const existingGroup = document.getElementById('existingStaffPositionGroup');
    const newGroup = document.getElementById('newStaffPositionGroup');
    
    if (mode === 'existing') {
        existingGroup.style.display = 'block';
        newGroup.style.display = 'none';
    } else {
        existingGroup.style.display = 'none';
        newGroup.style.display = 'block';
    }
}

// Save new staff member
async function saveNewStaff() {
    const mode = document.querySelector('input[name="staffPositionMode"]:checked')?.value;
    const qty = parseInt(document.getElementById('staffQty')?.value) || 1;
    
    const name = document.getElementById('staffName')?.value?.trim();
    if (!name) {
        showStaffStatus('Please enter a staff name', 'error');
        return;
    }
    
    let role;
    if (mode === 'existing') {
        role = document.getElementById('staffPositionSelect')?.value;
        if (!role) {
            showStaffStatus('Please select a position/role', 'error');
            return;
        }
    } else {
        role = document.getElementById('newStaffPosition')?.value?.trim();
        if (!role) {
            showStaffStatus('Please enter a staff position', 'error');
            return;
        }
    }
    
    showStaffStatus('Saving...', 'success');
    
    try {
        const res = await fetch(apiBase + '/api/v1/reference-staff', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ name, role, qty })
        });
        
        const data = await res.json();
        
        if (data.success) {
            showStaffStatus('Staff added successfully!', 'success');
            closeAddStaffModal();
            loadStaffTable();
            showSuccessToast('Staff member added successfully!');
        } else {
            showStaffStatus(data.error || 'Failed to add staff', 'error');
        }
    } catch (err) {
        showStaffStatus('Error: ' + err.message, 'error');
    }
}

// Delete staff member
async function deleteStaffMember(id) {
    if (!confirm('Are you sure you want to delete this staff member?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/reference-staff/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        const data = await res.json();
        if (data.success) {
            loadStaffTable();
            showSuccessToast('Staff member deleted successfully!');
        } else {
            alert(data.error || 'Failed to delete');
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// Show status message in staff modal
function showStaffStatus(message, type) {
    const status = document.getElementById('staffStatus');
    if (!status) return;
    status.style.display = 'block';
    status.style.padding = '12px';
    status.style.borderRadius = '8px';
    status.style.fontSize = '13px';
    status.style.fontWeight = '500';
    
    if (type === 'error') {
        status.style.background = '#fef2f2';
        status.style.color = '#dc2626';
        status.style.border = '1px solid #fecaca';
    } else {
        status.style.background = '#f0fdf4';
        status.style.color = '#16a34a';
        status.style.border = '1px solid #bbf7d0';
    }
    status.innerHTML = '<i class="fas ' + (type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle') + '" style="margin-right: 6px;"></i> ' + message;
}

// Render budget table - grouped by campaign
function renderBudgetTable() {
    const tbody = document.getElementById('budgetTable');
    if (!tbody) return;
    
    // Filter out archived items for main view
    const activeItems = (allBudgetItems || []).filter(item => !item.is_archived);
    
    if (!activeItems || activeItems.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:24px; color: #64748b;">No budget items yet. Add items using the form above.</td></tr>';
        return;
    }
    
    // Group items by campaign_id
    const groupedByCampaign = {};
    activeItems.forEach(item => {
        const campaignId = item.campaign_id || 0;
        if (!groupedByCampaign[campaignId]) {
            groupedByCampaign[campaignId] = {
                campaign_id: campaignId,
                campaign_title: item.campaign_title || 'Campaign #' + campaignId,
                items: [],
                total_budget: 0,
                government_total: 0,
                reimbursable_total: 0
            };
        }
        const itemTotal = (item.quantity || 0) * (item.unit_cost || 0);
        groupedByCampaign[campaignId].items.push(item);
        groupedByCampaign[campaignId].total_budget += itemTotal;
        if (item.funding_source === 'government_allocated') {
            groupedByCampaign[campaignId].government_total += itemTotal;
        } else {
            groupedByCampaign[campaignId].reimbursable_total += itemTotal;
        }
    });
    
    tbody.innerHTML = '';
    Object.values(groupedByCampaign).forEach(group => {
        const fundingBreakdown = [];
        if (group.government_total > 0) fundingBreakdown.push(`Gov: ₱${group.government_total.toLocaleString('en-PH', {minimumFractionDigits: 2})}`);
        if (group.reimbursable_total > 0) fundingBreakdown.push(`Reimb: ₱${group.reimbursable_total.toLocaleString('en-PH', {minimumFractionDigits: 2})}`);
        
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; font-weight: 600;" title="${group.campaign_title}"><span style="color: #6366f1; font-weight: 700;">#${group.campaign_id}</span> ${group.campaign_title}</td>
            <td><span class="badge draft">${group.items.length} item${group.items.length > 1 ? 's' : ''}</span></td>
            <td style="font-weight: 700; color: #059669;">₱${group.total_budget.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td style="font-size: 12px; color: #64748b;">${fundingBreakdown.join('<br>') || '-'}</td>
            <td><span class="badge approved">Active</span></td>
            <td style="white-space: nowrap;">
                <button class="btn btn-secondary" onclick="viewBudgetDetails(${group.campaign_id})" style="padding: 4px 10px; font-size: 11px; margin: 1px;" title="View budget breakdown">
                    <i class="fas fa-eye"></i> View
                </button>
                <button class="btn btn-secondary" onclick="editBudgetItems(${group.campaign_id})" style="padding: 4px 10px; font-size: 11px; margin: 1px;" title="Edit budget items">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-warning" onclick="archiveBudgetItems(${group.campaign_id})" style="padding: 4px 10px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;" title="Archive budget items">
                    <i class="fas fa-archive"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

// Toggle budget card visibility (hide/show amounts)
function toggleBudgetCardVisibility(type) {
    let displayEl, hiddenEl, iconEl;
    
    if (type === 'total') {
        displayEl = document.getElementById('budgetTotalDisplay');
        hiddenEl = document.getElementById('budgetTotalHidden');
        iconEl = document.getElementById('budgetTotalIcon');
    } else if (type === 'government') {
        displayEl = document.getElementById('budgetGovDisplay');
        hiddenEl = document.getElementById('budgetGovHidden');
        iconEl = document.getElementById('budgetGovIcon');
    } else if (type === 'reimbursable') {
        displayEl = document.getElementById('budgetReimbDisplay');
        hiddenEl = document.getElementById('budgetReimbHidden');
        iconEl = document.getElementById('budgetReimbIcon');
    }
    
    if (!displayEl || !hiddenEl || !iconEl) return;
    
    // Toggle visibility
    if (displayEl.style.display === 'none') {
        // Show the amount
        displayEl.style.display = 'block';
        hiddenEl.style.display = 'none';
        iconEl.className = 'fas fa-eye';
    } else {
        // Hide the amount
        displayEl.style.display = 'none';
        hiddenEl.style.display = 'block';
        iconEl.className = 'fas fa-eye-slash';
    }
}

// View budget details modal
function viewBudgetDetails(campaignId) {
    const items = (allBudgetItems || []).filter(item => item.campaign_id === campaignId && !item.is_archived);
    if (items.length === 0) {
        alert('No budget items found for this campaign.');
        return;
    }
    
    const campaignTitle = items[0].campaign_title || 'Campaign #' + campaignId;
    let totalBudget = 0;
    
    let itemsHtml = items.map(item => {
        const itemTotal = (item.quantity || 0) * (item.unit_cost || 0);
        totalBudget += itemTotal;
        const fundingLabel = item.funding_source === 'government_allocated' ? 'Government' : 'Reimbursable';
        const fundingClass = item.funding_source === 'government_allocated' ? 'approved' : 'pending';
        return `
            <tr>
                <td>${item.item_name || '-'}</td>
                <td><span class="badge ${item.item_type === 'material' ? 'scheduled' : 'draft'}">${item.item_type || 'consumable'}</span></td>
                <td style="text-align: center;">${item.quantity || 0}</td>
                <td style="text-align: right;">₱${parseFloat(item.unit_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td style="text-align: right; font-weight: 600;">₱${itemTotal.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                <td><span class="badge ${fundingClass}">${fundingLabel}</span></td>
            </tr>
        `;
    }).join('');
    
    const modal = document.createElement('div');
    modal.id = 'budgetDetailsModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 800px; width: 90%; max-height: 80vh; overflow: auto; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="fas fa-receipt"></i> Budget Breakdown: ${campaignTitle}</h3>
                <button onclick="document.getElementById('budgetDetailsModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <div style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border-radius: 8px; padding: 16px; margin-bottom: 20px; text-align: center;">
                <div style="font-size: 12px; color: #166534; font-weight: 600; text-transform: uppercase;">Total Budget</div>
                <div style="font-size: 28px; font-weight: 700; color: #166534;">₱${totalBudget.toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
            </div>
            <table class="data-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Cost</th>
                        <th style="text-align: right;">Total</th>
                        <th>Funding</th>
                    </tr>
                </thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            <div style="margin-top: 20px; text-align: right;">
                <button onclick="document.getElementById('budgetDetailsModal').remove()" class="btn btn-secondary" style="padding: 10px 20px;">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Edit budget items for a campaign
function editBudgetItems(campaignId) {
    const items = (allBudgetItems || []).filter(item => item.campaign_id === campaignId && !item.is_archived);
    if (items.length === 0) {
        alert('No budget items found for this campaign.');
        return;
    }
    
    const campaignTitle = items[0].campaign_title || 'Campaign #' + campaignId;
    
    let itemsHtml = items.map(item => {
        const itemTotal = (item.quantity || 0) * (item.unit_cost || 0);
        return `
            <tr data-item-id="${item.id}">
                <td><input type="text" value="${item.item_name || ''}" class="edit-item-name" style="width: 100%; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;"></td>
                <td>
                    <select class="edit-item-type" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <option value="consumable" ${item.item_type === 'consumable' ? 'selected' : ''}>Consumable</option>
                        <option value="material" ${item.item_type === 'material' ? 'selected' : ''}>Material</option>
                        <option value="equipment" ${item.item_type === 'equipment' ? 'selected' : ''}>Equipment</option>
                        <option value="service" ${item.item_type === 'service' ? 'selected' : ''}>Service</option>
                    </select>
                </td>
                <td><input type="number" value="${item.quantity || 1}" class="edit-item-qty" min="1" style="width: 60px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center;"></td>
                <td><input type="number" value="${item.unit_cost || 0}" class="edit-item-cost" min="0" step="0.01" style="width: 100px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: right;"></td>
                <td>
                    <select class="edit-item-funding" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <option value="government_allocated" ${item.funding_source === 'government_allocated' ? 'selected' : ''}>Government</option>
                        <option value="reimbursable" ${item.funding_source === 'reimbursable' ? 'selected' : ''}>Reimbursable</option>
                    </select>
                </td>
                <td>
                    <button onclick="deleteBudgetItem(${item.id})" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; background: #ef4444; color: white; border: none;">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    const modal = document.createElement('div');
    modal.id = 'editBudgetModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 900px; width: 95%; max-height: 85vh; overflow: auto; padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="fas fa-edit"></i> Edit Budget: ${campaignTitle}</h3>
                <button onclick="document.getElementById('editBudgetModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
            </div>
            <table class="data-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Type</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Unit Cost</th>
                        <th>Funding</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody id="editBudgetItemsBody">${itemsHtml}</tbody>
            </table>
            <div style="margin-top: 16px;">
                <button onclick="addNewBudgetItemRow(${campaignId})" class="btn btn-secondary" style="padding: 8px 16px; font-size: 13px;">
                    <i class="fas fa-plus"></i> Add Another Item
                </button>
            </div>
            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                <button onclick="document.getElementById('editBudgetModal').remove()" class="btn btn-secondary" style="padding: 10px 20px;">Cancel</button>
                <button onclick="saveEditedBudgetItems(${campaignId})" class="btn btn-primary" style="padding: 10px 20px; background: #10b981; color: white; border: none;">Save Changes</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Save edited budget items
async function saveEditedBudgetItems(campaignId) {
    const rows = document.querySelectorAll('#editBudgetItemsBody tr');
    let savedCount = 0;
    let errorCount = 0;
    
    for (const row of rows) {
        const itemId = row.dataset.itemId;
        const isNew = row.dataset.isNew === 'true';
        const itemName = row.querySelector('.edit-item-name').value;
        const itemType = row.querySelector('.edit-item-type').value;
        const quantity = parseInt(row.querySelector('.edit-item-qty').value) || 1;
        const unitCost = parseFloat(row.querySelector('.edit-item-cost').value) || 0;
        const fundingSource = row.querySelector('.edit-item-funding').value;
        
        // Skip empty item names
        if (!itemName.trim()) continue;
        
        try {
            let res;
            if (isNew) {
                // Create new item via POST
                res = await fetch(apiBase + '/api/v1/budgets', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify({
                        campaign_id: campaignId,
                        item_name: itemName,
                        item_type: itemType,
                        quantity: quantity,
                        unit_cost: unitCost,
                        funding_source: fundingSource
                    })
                });
            } else {
                // Update existing item via PUT
                res = await fetch(apiBase + '/api/v1/budgets/' + itemId, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + getToken()
                    },
                    body: JSON.stringify({
                        item_name: itemName,
                        item_type: itemType,
                        quantity: quantity,
                        unit_cost: unitCost,
                        funding_source: fundingSource
                    })
                });
            }
            
            if (res.ok) {
                savedCount++;
            } else {
                errorCount++;
            }
        } catch (err) {
            errorCount++;
        }
    }
    
    document.getElementById('editBudgetModal').remove();
    
    if (errorCount === 0) {
        showSuccessToast(`Successfully saved ${savedCount} budget item(s)!`);
    } else {
        showWarningToast(`Saved ${savedCount} item(s), ${errorCount} failed`);
    }
    
    loadBudgetData();
}

// Add new budget item row in edit modal
function addNewBudgetItemRow(campaignId) {
    const tbody = document.getElementById('editBudgetItemsBody');
    if (!tbody) return;
    
    const newRowId = 'new_' + Date.now();
    const tr = document.createElement('tr');
    tr.dataset.itemId = newRowId;
    tr.dataset.isNew = 'true';
    tr.innerHTML = `
        <td><input type="text" value="" class="edit-item-name" placeholder="Item name" style="width: 100%; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;"></td>
        <td>
            <select class="edit-item-type" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                <option value="consumable" selected>Consumable</option>
                <option value="material">Material</option>
                <option value="equipment">Equipment</option>
                <option value="service">Service</option>
            </select>
        </td>
        <td><input type="number" value="1" class="edit-item-qty" min="1" style="width: 60px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: center;"></td>
        <td><input type="number" value="0" class="edit-item-cost" min="0" step="0.01" style="width: 100px; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px; text-align: right;"></td>
        <td>
            <select class="edit-item-funding" style="padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                <option value="government_allocated" selected>Government</option>
                <option value="reimbursable">Reimbursable</option>
            </select>
        </td>
        <td>
            <button onclick="this.closest('tr').remove()" class="btn btn-danger" style="padding: 4px 8px; font-size: 11px; background: #ef4444; color: white; border: none;">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    
    // Focus on the new item name field
    tr.querySelector('.edit-item-name').focus();
}

// Archive budget items for a campaign
async function archiveBudgetItems(campaignId) {
    if (!confirm('Archive all budget items for this campaign? They can be restored from View Archived.')) {
        return;
    }
    
    const items = (allBudgetItems || []).filter(item => item.campaign_id === campaignId && !item.is_archived);
    let archivedCount = 0;
    
    for (const item of items) {
        try {
            const res = await fetch(apiBase + '/api/v1/budgets/' + item.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + getToken()
                },
                body: JSON.stringify({ is_archived: true })
            });
            if (res.ok) archivedCount++;
        } catch (err) {
            console.error('Failed to archive budget item:', err);
        }
    }
    
    showSuccessToast(`Archived ${archivedCount} budget item(s)`);
    loadBudgetData();
}

// Toggle archived budgets modal
function toggleArchivedBudgets() {
    const modal = document.getElementById('archivedBudgetsModal');
    modal.style.display = 'flex';
    document.body.classList.add('archived-modal-open');
    loadArchivedBudgets();
}

function closeArchivedBudgetsModal() {
    document.getElementById('archivedBudgetsModal').style.display = 'none';
    document.body.classList.remove('archived-modal-open');
}

// Load archived budgets
async function loadArchivedBudgets() {
    const container = document.getElementById('archivedBudgetsList');
    container.innerHTML = '<p style="text-align: center; color: #64748b;">Loading...</p>';
    
    const archivedItems = (allBudgetItems || []).filter(item => item.is_archived);
    
    if (archivedItems.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 24px;">No archived budget items.</p>';
        return;
    }
    
    // Group by campaign
    const grouped = {};
    archivedItems.forEach(item => {
        const cid = item.campaign_id || 0;
        if (!grouped[cid]) {
            grouped[cid] = {
                campaign_title: item.campaign_title || 'Campaign #' + cid,
                items: [],
                total: 0
            };
        }
        grouped[cid].items.push(item);
        grouped[cid].total += (item.quantity || 0) * (item.unit_cost || 0);
    });
    
    let html = '';
    Object.entries(grouped).forEach(([campaignId, group]) => {
        html += `
            <div style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px; overflow: hidden;">
                <div style="background: #f8fafc; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${group.campaign_title}</strong>
                        <span style="color: #64748b; font-size: 12px; margin-left: 8px;">${group.items.length} item(s) - ₱${group.total.toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="restoreBudgetItems(${campaignId})" class="btn btn-success" style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white; border: none;">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                        <button onclick="deleteArchivedBudgetItems(${campaignId})" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white; border: none;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Restore archived budget items
async function restoreBudgetItems(campaignId) {
    // Convert campaignId to number for comparison (it comes as string from onclick)
    const cid = parseInt(campaignId);
    const items = (allBudgetItems || []).filter(item => parseInt(item.campaign_id) === cid && item.is_archived);
    
    console.log('Restoring items for campaign:', cid, 'Found items:', items.length);
    
    if (items.length === 0) {
        showWarningToast('No archived items found for this campaign');
        return;
    }
    
    let restoredCount = 0;
    
    for (const item of items) {
        try {
            const res = await fetch(apiBase + '/api/v1/budgets/' + item.id, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + getToken()
                },
                body: JSON.stringify({ is_archived: false })
            });
            if (res.ok) {
                restoredCount++;
                console.log('Restored item:', item.id);
            } else {
                console.error('Failed to restore item:', item.id, await res.text());
            }
        } catch (err) {
            console.error('Failed to restore budget item:', err);
        }
    }
    
    showSuccessToast(`Restored ${restoredCount} budget item(s)`);
    // Reload budget data to refresh allBudgetItems - must await to ensure data is fresh
    await loadBudgetData();
    // Reload archived list after data is refreshed
    await loadArchivedBudgets();
}

// Delete archived budget items permanently
async function deleteArchivedBudgetItems(campaignId) {
    if (!confirm('Permanently delete all archived budget items for this campaign? This cannot be undone.')) {
        return;
    }
    
    // Convert campaignId to number for comparison
    const cid = parseInt(campaignId);
    const items = (allBudgetItems || []).filter(item => parseInt(item.campaign_id) === cid && item.is_archived);
    let deletedCount = 0;
    
    for (const item of items) {
        try {
            const res = await fetch(apiBase + '/api/v1/budgets/' + item.id, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + getToken()
                }
            });
            if (res.ok) deletedCount++;
        } catch (err) {
            console.error('Failed to delete budget item:', err);
        }
    }
    
    showSuccessToast(`Deleted ${deletedCount} budget item(s)`);
    // Reload budget data to refresh allBudgetItems
    await loadBudgetData();
    // Reload archived list
    loadArchivedBudgets();
}

// Update budget summary cards
function updateBudgetSummary(summary) {
    if (!summary) return;
    
    const formatCurrency = (val) => '₱' + parseFloat(val || 0).toLocaleString('en-PH', {minimumFractionDigits: 2});
    
    const totalEl = document.getElementById('budgetTotalDisplay');
    const govEl = document.getElementById('budgetGovDisplay');
    const reimbEl = document.getElementById('budgetReimbDisplay');
    const itemsEl = document.getElementById('budgetItemsDisplay');
    
    if (totalEl) totalEl.textContent = formatCurrency(summary.total_budget);
    if (govEl) govEl.textContent = formatCurrency(summary.government_allocated);
    if (reimbEl) reimbEl.textContent = formatCurrency(summary.reimbursable);
    if (itemsEl) itemsEl.textContent = summary.item_count || 0;
    
    // Update Materials Allocated in Resource Allocation
    const materialsEl = document.getElementById('materialsUsed');
    if (materialsEl) {
        materialsEl.innerHTML = `${summary.item_count || 0} items<br><small style="font-size: 12px; color: #64748b;">₱${parseFloat(summary.total_budget || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</small>`;
    }
}

// Clear budget form
function clearBudgetForm() {
    document.getElementById('budget_campaign_id').value = '';
    document.getElementById('budget_item_name').value = '';
    document.getElementById('budget_item_type').value = 'consumable';
    document.getElementById('budget_quantity').value = '1';
    document.getElementById('budget_unit_cost').value = '';
    document.getElementById('budget_funding_source').value = 'government_allocated';
    document.getElementById('budget_notes').value = '';
}

// Show budget status message
function showBudgetStatus(message, type) {
    const el = document.getElementById('budgetStatus');
    if (!el) return;
    
    el.textContent = message;
    el.style.display = 'block';
    el.style.padding = '10px 16px';
    el.style.borderRadius = '8px';
    el.style.fontWeight = '500';
    
    if (type === 'success') {
        el.style.background = '#dcfce7';
        el.style.color = '#166534';
    } else {
        el.style.background = '#fee2e2';
        el.style.color = '#dc2626';
    }
    
    setTimeout(() => { el.style.display = 'none'; }, 4000);
}

// Initialize budget section when campaigns load
const originalLoadCampaigns = loadCampaigns;
loadCampaigns = async function() {
    await originalLoadCampaigns();
    populateBudgetCampaignDropdown();
    loadBudgetData();
};

function onCampaignChange() {
    activeCampaignId = parseInt(document.getElementById('active_campaign').value);
    loadSegments();
}

// Schedule Management
async function loadSchedules() {
    const campaignId = parseInt(document.getElementById('schedule_campaign_id')?.value || activeCampaignId);
    if (!campaignId) {
        document.getElementById('scheduleTable').innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">Enter a Campaign ID to view schedules</td></tr>';
        return;
    }
    
    const tbody = document.getElementById('scheduleTable');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId + '/schedules', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const schedules = data.data || [];
        
        if (!schedules.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">No schedules found for this campaign.</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        schedules.forEach(s => {
            const tr = document.createElement('tr');
            const formatDateTime = (dt) => {
                if (!dt) return '-';
                try {
                    const d = new Date(dt);
                    if (isNaN(d.getTime())) return dt;
                    return d.toLocaleString('en-US', {dateStyle: 'short', timeStyle: 'short'});
                } catch (e) {
                    return dt;
                }
            };
            
            const statusBadge = {
                'pending': '<span class="badge" style="background:#fbbf24; color:#92400e;">Pending</span>',
                'sent': '<span class="badge" style="background:#10b981; color:#065f46;">Sent</span>',
                'failed': '<span class="badge" style="background:#ef4444; color:#991b1b;">Failed</span>'
            };
            
            tr.innerHTML = `
                <td>${s.id}</td>
                <td>${formatDateTime(s.scheduled_at)}</td>
                <td>${s.channel || '-'}</td>
                <td>${statusBadge[s.status] || s.status}</td>
                <td>${formatDateTime(s.last_posting_attempt)}</td>
                <td>${s.notes || '-'}</td>
                <td>
                    ${s.status === 'failed' ? `<button class="btn btn-secondary" onclick="resendSchedule(${campaignId}, ${s.id})" style="padding: 4px 8px; font-size: 12px;">🔄 Re-send</button>` : ''}
                    ${s.status === 'pending' ? `<button class="btn btn-secondary" onclick="sendSchedule(${campaignId}, ${s.id})" style="padding: 4px 8px; font-size: 12px;">📤 Send</button>` : ''}
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Show schedule management section
        document.getElementById('schedule-management-section').style.display = 'block';
        
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#dc2626;">Failed to load schedules: ' + err.message + '</td></tr>';
    }
}

async function sendSchedule(campaignId, scheduleId) {
    if (!confirm('Send this schedule now?')) return;
    
    const currentToken = getToken();
    try {
        const res = await fetch(apiBase + `/api/v1/campaigns/${campaignId}/schedules/${scheduleId}/send`, {
            method: 'PATCH',
            headers: {
                'Authorization': 'Bearer ' + getToken()
            }
        });
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to send schedule'));
            return;
        }
        alert('Schedule sent successfully!');
        loadSchedules();
    } catch (err) {
        alert('Failed to send schedule: ' + err.message);
    }
}

async function resendSchedule(campaignId, scheduleId) {
    if (!confirm('Re-send this failed schedule?')) return;
    
    const currentToken = getToken();
    try {
        const res = await fetch(apiBase + `/api/v1/campaigns/${campaignId}/schedules/${scheduleId}/resend`, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + getToken()
            }
        });
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to re-send schedule'));
            return;
        }
        alert('Schedule re-sent successfully!');
        loadSchedules();
    } catch (err) {
        alert('Failed to re-send schedule: ' + err.message);
    }
}

// Load Campaign Content
async function loadCampaignContent() {
    const campaignId = parseInt(document.getElementById('content_campaign_id')?.value || activeCampaignId);
    if (!campaignId) {
        document.getElementById('contentTable').innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px;">Enter a Campaign ID to view linked content</td></tr>';
        return;
    }
    
    const tbody = document.getElementById('contentTable');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId + '/content', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const contentItems = data.data || [];
        
        if (!contentItems.length) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px;">No content items linked to this campaign.</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        contentItems.forEach(item => {
            const tr = document.createElement('tr');
            const formatDateTime = (dt) => {
                if (!dt) return '-';
                try {
                    const d = new Date(dt);
                    if (isNaN(d.getTime())) return dt;
                    return d.toLocaleString('en-US', {dateStyle: 'short', timeStyle: 'short'});
                } catch (e) {
                    return dt;
                }
            };
            
            tr.innerHTML = `
                <td>${item.id}</td>
                <td>${item.title || '-'}</td>
                <td><span class="badge" style="background:#e0f2fe; color:#1d4ed8;">${item.content_type || 'text'}</span></td>
                <td>${formatDateTime(item.created_at)}</td>
            `;
            tbody.appendChild(tr);
        });
        
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#dc2626;">Failed to load content: ' + err.message + '</td></tr>';
    }
}

// Update status dropdown options based on current status and valid transitions
// Matches backend validation rules in CampaignController.php
function updateStatusOptions(currentStatus) {
    const statusSelect = document.getElementById('status');
    if (!statusSelect) return;
    
    // Valid transitions matching backend rules
    const validTransitions = {
        'draft': ['draft', 'pending', 'approved'],
        'pending': ['pending', 'approved', 'rejected'],
        'approved': ['approved', 'ongoing', 'scheduled'],
        'ongoing': ['ongoing', 'completed'],
        'scheduled': ['scheduled', 'ongoing'],
        'completed': ['completed', 'archived'],
        'rejected': ['rejected', 'draft'],
        'archived': ['archived']
    };
    
    // Get allowed statuses for current status
    const allowedStatuses = validTransitions[currentStatus] || ['draft'];
    
    // Status labels
    const statusLabels = {
        'draft': 'Draft',
        'pending': 'Pending',
        'approved': 'Approved',
        'ongoing': 'Ongoing',
        'scheduled': 'Scheduled',
        'completed': 'Completed',
        'archived': 'Archived',
        'rejected': 'Rejected'
    };
    
    // Store current value
    const currentValue = statusSelect.value;
    
    // Clear all options except the placeholder
    statusSelect.innerHTML = '<option value="">Select status...</option>';
    
    // Add only allowed statuses
    allowedStatuses.forEach(status => {
        const option = document.createElement('option');
        option.value = status;
        option.textContent = statusLabels[status] || status;
        statusSelect.appendChild(option);
    });
    
    // Restore current value if it's still valid
    if (currentValue && allowedStatuses.includes(currentValue)) {
        statusSelect.value = currentValue;
    } else if (allowedStatuses.length > 0) {
        // Set to first allowed status if current is invalid
        statusSelect.value = allowedStatuses[0];
    }
}

// Edit Campaign
async function editCampaign(campaignId) {
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        const c = data.data;
        
        // Debug: Log the campaign data received from API
        console.log('=== EDIT CAMPAIGN DEBUG ===');
        console.log('Full campaign data:', c);
        console.log('start_time from API:', c.start_time);
        console.log('end_time from API:', c.end_time);
        console.log('barangay_target_zones from API:', c.barangay_target_zones);
        console.log('=== END EDIT CAMPAIGN DEBUG ===');
        
        // Populate form with campaign data
        if (document.getElementById('title')) {
            document.getElementById('title').value = c.title || '';
            // Trigger combobox update if needed
            if (typeof document.getElementById('title').setSelectedValues === 'function') {
                document.getElementById('title').setSelectedValues(c.title);
            }
        }
        if (document.getElementById('description')) {
            document.getElementById('description').value = c.description || '';
        }
        if (document.getElementById('category')) {
            document.getElementById('category').value = c.category || '';
            if (typeof document.getElementById('category').setSelectedValues === 'function') {
                document.getElementById('category').setSelectedValues(c.category);
            }
        }
        if (document.getElementById('geographic_scope')) {
            document.getElementById('geographic_scope').value = c.geographic_scope || '';
            if (typeof document.getElementById('geographic_scope').setSelectedValues === 'function') {
                document.getElementById('geographic_scope').setSelectedValues(c.geographic_scope);
            }
        }
        if (document.getElementById('status')) {
            // Filter status options based on current status and valid transitions
            updateStatusOptions(c.status || 'draft');
            document.getElementById('status').value = c.status || 'draft';
            if (typeof document.getElementById('status').setSelectedValues === 'function') {
                document.getElementById('status').setSelectedValues(c.status);
            }
        }
        if (document.getElementById('start_date')) {
            document.getElementById('start_date').value = c.start_date || '';
        }
        if (document.getElementById('start_time')) {
            document.getElementById('start_time').value = c.start_time || '';
        }
        if (document.getElementById('end_date')) {
            document.getElementById('end_date').value = c.end_date || '';
        }
        if (document.getElementById('end_time')) {
            document.getElementById('end_time').value = c.end_time || '';
        }
        
        // Populate datetime-local fields for start and end
        if (document.getElementById('start_datetime') && c.start_date) {
            const startTime = c.start_time || '00:00:00';
            const startDateTimeValue = c.start_date + 'T' + startTime.substring(0, 5);
            document.getElementById('start_datetime').value = startDateTimeValue;
        }
        if (document.getElementById('end_datetime') && c.end_date) {
            const endTime = c.end_time || '00:00:00';
            const endDateTimeValue = c.end_date + 'T' + endTime.substring(0, 5);
            document.getElementById('end_datetime').value = endDateTimeValue;
        }
        // Handle final schedule display (read-only)
        const finalScheduleField = document.getElementById('final_schedule_field');
        const finalScheduleValue = document.getElementById('final_schedule_value');
        
        if (c.final_schedule_datetime) {
            // Format the datetime for display
            const scheduleDate = new Date(c.final_schedule_datetime);
            const formattedDate = scheduleDate.toLocaleString('en-US', {
                dateStyle: 'long',
                timeStyle: 'short'
            });
            
            if (finalScheduleValue) {
                finalScheduleValue.textContent = formattedDate;
            }
            
            if (finalScheduleField) {
                finalScheduleField.style.display = 'block';
            }
        } else {
            // Hide the field if no final schedule exists
            if (finalScheduleField) {
                finalScheduleField.style.display = 'none';
            }
        }
        
        // Legacy draft_schedule_datetime handling (for backward compatibility, but field is removed)
        if (document.getElementById('draft_schedule_datetime')) {
            // Convert datetime to datetime-local format
            if (c.draft_schedule_datetime) {
                const dt = new Date(c.draft_schedule_datetime);
                const localDateTime = dt.toISOString().slice(0, 16);
                document.getElementById('draft_schedule_datetime').value = localDateTime;
            }
        }
        if (document.getElementById('location')) {
            document.getElementById('location').value = c.location || '';
            if (typeof document.getElementById('location').setSelectedValues === 'function') {
                document.getElementById('location').setSelectedValues(c.location);
            }
        }
        if (document.getElementById('objectives')) {
            document.getElementById('objectives').value = c.objectives || '';
        }
        if (document.getElementById('budget')) {
            document.getElementById('budget').value = c.budget || '';
        }
        if (document.getElementById('staff_count')) {
            document.getElementById('staff_count').value = c.staff_count || '';
        }
        
        // Handle multi-select fields
        if (c.assigned_staff) {
            const staff = typeof c.assigned_staff === 'string' ? JSON.parse(c.assigned_staff) : c.assigned_staff;
            if (Array.isArray(staff) && document.getElementById('assigned_staff')) {
                if (typeof document.getElementById('assigned_staff').setSelectedValues === 'function') {
                    document.getElementById('assigned_staff').setSelectedValues(staff);
                }
            }
        }
        if (c.barangay_target_zones) {
            const zones = typeof c.barangay_target_zones === 'string' ? JSON.parse(c.barangay_target_zones) : c.barangay_target_zones;
            console.log('Barangay zones to set:', zones);
            console.log('Is array?', Array.isArray(zones));
            
            const barangayEl = document.getElementById('barangay_zones');
            console.log('Barangay element:', barangayEl);
            console.log('Has setSelectedValues?', barangayEl && typeof barangayEl.setSelectedValues === 'function');
            
            if (Array.isArray(zones) && barangayEl) {
                if (typeof barangayEl.setSelectedValues === 'function') {
                    console.log('Calling setSelectedValues with:', zones);
                    barangayEl.setSelectedValues(zones);
                    console.log('After setSelectedValues, element value:', barangayEl.value);
                } else {
                    console.warn('barangay_zones element does not have setSelectedValues function');
                    // Fallback: try setting value directly
                    barangayEl.value = zones.join(',');
                }
            }
        }
        if (c.materials_json) {
            const materials = typeof c.materials_json === 'string' ? JSON.parse(c.materials_json) : c.materials_json;
            if (typeof materials === 'object' && document.getElementById('materials_json')) {
                const materialList = Object.keys(materials);
                if (typeof document.getElementById('materials_json').setSelectedValues === 'function') {
                    document.getElementById('materials_json').setSelectedValues(materialList);
                }
            }
        }
        
        // Store campaign ID for update
        document.getElementById('planningForm').dataset.campaignId = campaignId;
        
        // Change form button text
        const submitBtn = document.querySelector('#planningForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Update Campaign';
            submitBtn.onclick = function(e) {
                e.preventDefault();
                updateCampaign(campaignId);
            };
        }
        
        // Open the Plan Campaign modal (since planning section is now a modal)
        openPlanCampaignModal(true); // Pass true to indicate edit mode
        
    } catch (err) {
        alert('Failed to load campaign: ' + err.message);
    }
}

// View Campaign Details
async function viewCampaign(campaignId) {
    try {
        // Remove existing modal if any to prevent duplication
        const existingModal = document.getElementById('viewCampaignModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (data.error) {
            alert('Error: ' + data.error);
            return;
        }
        
        const c = data.data;
        
        // Format dates with optional time
        const formatDate = (dateStr, timeStr = null) => {
            if (!dateStr) return 'Not set';
            try {
                // Combine date and time if time is provided
                let dateTimeStr = dateStr;
                if (timeStr) {
                    // Ensure time is in HH:MM format
                    const timePart = timeStr.substring(0, 5);
                    dateTimeStr = dateStr + 'T' + timePart;
                }
                return new Date(dateTimeStr).toLocaleString('en-US', {dateStyle: 'long', timeStyle: 'short'});
            } catch (e) {
                return dateStr;
            }
        };
        
        // Format JSON fields
        const formatJSON = (jsonStr) => {
            if (!jsonStr) return 'None';
            try {
                const parsed = typeof jsonStr === 'string' ? JSON.parse(jsonStr) : jsonStr;
                if (Array.isArray(parsed)) {
                    return parsed.join(', ') || 'None';
                } else if (typeof parsed === 'object') {
                    return Object.keys(parsed).join(', ') || 'None';
                }
                return String(parsed);
            } catch (e) {
                return String(jsonStr);
            }
        };
        
        // Create modal with campaign details
        const modal = document.createElement('div');
        modal.id = 'viewCampaignModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);';
        
        const statusColors = {
            'draft': { bg: '#f3f4f6', color: '#374151' },
            'pending': { bg: '#fef3c7', color: '#92400e' },
            'approved': { bg: '#d1fae5', color: '#065f46' },
            'ongoing': { bg: '#dbeafe', color: '#1e40af' },
            'scheduled': { bg: '#e0f2fe', color: '#0c4a6e' },
            'completed': { bg: '#dcfce7', color: '#166534' },
            'archived': { bg: '#f3f4f6', color: '#6b7280' }
        };
        const statusStyle = statusColors[c.status] || statusColors['draft'];
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; max-width: 900px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column;">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 24px 32px; position: relative;">
                    <button id="closeViewModalBtn" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <div>
                            <div style="font-size: 14px; opacity: 0.9; font-weight: 500;">Campaign #${c.id}</div>
                            <h2 style="margin: 4px 0 0 0; font-size: 28px; font-weight: 700; line-height: 1.2;">${c.title || 'Untitled Campaign'}</h2>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                        <span style="background: ${statusStyle.bg}; color: ${statusStyle.color}; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">
                            ${(c.status || 'draft').charAt(0).toUpperCase() + (c.status || 'draft').slice(1)}
                        </span>
                        ${c.category ? `<span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;">${c.category}</span>` : ''}
                        ${c.geographic_scope ? `<span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;">${c.geographic_scope}</span>` : ''}
                    </div>
                </div>
                
                <!-- Content -->
                <div style="padding: 32px; overflow-y: auto; flex: 1;">
                    ${c.description ? `
                        <div style="background: #f8fafc; padding: 20px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid #4c8a89;">
                            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Description</div>
                            <p style="margin: 0; color: #475569; line-height: 1.6; font-size: 15px;">${c.description}</p>
                        </div>
                    ` : ''}
                    
                    ${c.objectives ? `
                        <div style="background: #f0fdfa; padding: 20px; border-radius: 12px; margin-bottom: 24px; border-left: 4px solid #14b8a6;">
                            <div style="font-size: 12px; font-weight: 700; color: #115e59; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">Objectives</div>
                            <p style="margin: 0; color: #134e4a; line-height: 1.6; font-size: 15px;">${c.objectives}</p>
                        </div>
                    ` : ''}
                    
                    <!-- Schedule & Timeline -->
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                            Schedule & Timeline
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                            <div style="background: white; border: 2px solid #e2e8f0; padding: 16px; border-radius: 10px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Start Date</div>
                                <div style="font-size: 15px; font-weight: 600; color: #0f172a;">${formatDate(c.start_date, c.start_time)}</div>
                            </div>
                            <div style="background: white; border: 2px solid #e2e8f0; padding: 16px; border-radius: 10px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">End Date</div>
                                <div style="font-size: 15px; font-weight: 600; color: #0f172a;">${formatDate(c.end_date, c.end_time)}</div>
                            </div>
                            ${c.final_schedule_datetime ? `
                                <div style="background: white; border: 2px solid #14b8a6; padding: 16px; border-radius: 10px;">
                                    <div style="font-size: 11px; font-weight: 600; color: #115e59; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Final Schedule</div>
                                    <div style="font-size: 15px; font-weight: 600; color: #115e59;">${formatDate(c.final_schedule_datetime)}</div>
                                </div>
                            ` : ''}
                            ${c.ai_recommended_datetime ? `
                                <div style="background: white; border: 2px solid #4c8a89; padding: 16px; border-radius: 10px;">
                                    <div style="font-size: 11px; font-weight: 600; color: #2d5a59; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">AI Recommended</div>
                                    <div style="font-size: 15px; font-weight: 600; color: #2d5a59;">${formatDate(c.ai_recommended_datetime)}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Resources & Budget -->
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                            Resources & Budget
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            ${c.budget ? `
                                <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #e2e8f0;">
                                    <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Budget</div>
                                    <div style="font-size: 24px; font-weight: 700; color: #0f172a;">₱${parseFloat(c.budget).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                                </div>
                            ` : ''}
                            ${c.staff_count ? `
                                <div style="background: white; border: 2px solid #e2e8f0; padding: 20px; border-radius: 10px;">
                                    <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Staff Count</div>
                                    <div style="font-size: 24px; font-weight: 700; color: #0f172a;">${c.staff_count}</div>
                                </div>
                            ` : ''}
                        </div>
                        ${c.assigned_staff && formatJSON(c.assigned_staff) !== 'None' ? `
                            <div style="margin-top: 16px; background: #f8fafc; padding: 16px; border-radius: 10px; border-left: 4px solid #4c8a89;">
                                <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px;">Assigned Staff</div>
                                <div style="color: #475569; font-size: 14px;">${formatJSON(c.assigned_staff)}</div>
                            </div>
                        ` : ''}
                        ${c.materials_json && formatJSON(c.materials_json) !== 'None' ? `
                            <div style="margin-top: 16px; background: #f8fafc; padding: 16px; border-radius: 10px; border-left: 4px solid #14b8a6;">
                                <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px;">Materials</div>
                                <div style="color: #475569; font-size: 14px;">${formatJSON(c.materials_json)}</div>
                            </div>
                        ` : ''}
                        
                        <!-- Budget Line Items Breakdown -->
                        <div id="campaignBudgetBreakdown" style="margin-top: 16px;"></div>
                    </div>
                    
                    <!-- Location & Zones -->
                    ${c.location || (c.barangay_target_zones && formatJSON(c.barangay_target_zones) !== 'None') ? `
                        <div style="margin-bottom: 24px;">
                            <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                Location & Target Zones
                            </h3>
                            ${c.location ? `
                                <div style="background: white; border: 2px solid #e2e8f0; padding: 16px; border-radius: 10px; margin-bottom: 12px;">
                                    <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Location</div>
                                    <div style="color: #0f172a; font-size: 15px; font-weight: 500;">${c.location}</div>
                                </div>
                            ` : ''}
                            ${c.barangay_target_zones && formatJSON(c.barangay_target_zones) !== 'None' ? `
                                <div style="background: #f0fdfa; padding: 16px; border-radius: 10px; border-left: 4px solid #14b8a6;">
                                    <div style="font-size: 12px; font-weight: 600; color: #115e59; margin-bottom: 8px;">Barangay Target Zones</div>
                                    <div style="color: #134e4a; font-size: 14px;">${formatJSON(c.barangay_target_zones)}</div>
                                </div>
                            ` : ''}
                        </div>
                    ` : ''}
                    
                    <!-- Metadata -->
                    <div style="padding-top: 20px; border-top: 2px solid #f1f5f9;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px;">
                            <div>
                                <span style="color: #64748b; font-weight: 500;">Created:</span>
                                <span style="color: #0f172a; font-weight: 600; margin-left: 4px;">${formatDate(c.created_at)}</span>
                            </div>
                            <div>
                                <span style="color: #64748b; font-weight: 500;">Updated:</span>
                                <span style="color: #0f172a; font-weight: 600; margin-left: 4px;">${formatDate(c.updated_at)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style="background: #f8fafc; padding: 20px 32px; border-top: 2px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                    <button id="closeViewModalFooterBtn" style="padding: 10px 24px; background: white; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569; transition: all 0.2s; font-size: 14px;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Close</button>
                    ${!isViewer() && canEditCampaign(c.status) ? `<button id="editFromViewBtn" data-campaign-id="${c.id}" style="padding: 10px 24px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(76, 138, 137, 0.3); font-size: 14px;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(76, 138, 137, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(76, 138, 137, 0.3)'">Edit Campaign</button>` : ''}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add event listeners for close buttons
        const closeTopBtn = document.getElementById('closeViewModalBtn');
        const closeFooterBtn = document.getElementById('closeViewModalFooterBtn');
        const editBtn = document.getElementById('editFromViewBtn');
        
        if (closeTopBtn) {
            closeTopBtn.addEventListener('click', () => {
                modal.remove();
            });
        }
        
        if (closeFooterBtn) {
            closeFooterBtn.addEventListener('click', () => {
                modal.remove();
            });
        }
        
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                const campaignId = editBtn.getAttribute('data-campaign-id');
                modal.remove();
                editCampaign(parseInt(campaignId));
            });
        }
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // Load budget breakdown for this campaign
        loadCampaignBudgetBreakdown(campaignId);
    } catch (err) {
        alert('Failed to load campaign: ' + err.message);
    }
}

// Load budget breakdown for campaign view modal
async function loadCampaignBudgetBreakdown(campaignId) {
    const container = document.getElementById('campaignBudgetBreakdown');
    if (!container) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/budgets?campaign_id=' + campaignId, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        
        if (!data.success || !data.data || data.data.length === 0) {
            container.innerHTML = '';
            return;
        }
        
        const items = data.data.filter(item => !item.is_archived);
        if (items.length === 0) {
            container.innerHTML = '';
            return;
        }
        
        let totalBudget = 0;
        let itemsHtml = items.map(item => {
            const itemTotal = (item.quantity || 0) * (item.unit_cost || 0);
            totalBudget += itemTotal;
            const fundingLabel = item.funding_source === 'government_allocated' ? 'Gov' : 'Reimb';
            return `
                <tr>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">${item.item_name || '-'}</td>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: center;">${item.quantity || 0}</td>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: right;">₱${parseFloat(item.unit_cost || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱${itemTotal.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;"><span style="background: ${item.funding_source === 'government_allocated' ? '#dbeafe' : '#fef3c7'}; color: ${item.funding_source === 'government_allocated' ? '#1e40af' : '#92400e'}; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">${fundingLabel}</span></td>
                </tr>
            `;
        }).join('');
        
        container.innerHTML = `
            <div style="background: #f0fdf4; padding: 16px; border-radius: 10px; border-left: 4px solid #22c55e;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <div style="font-size: 12px; font-weight: 600; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">Budget Line Items</div>
                    <div style="font-size: 16px; font-weight: 700; color: #166534;">Total: ₱${totalBudget.toLocaleString('en-PH', {minimumFractionDigits: 2})}</div>
                </div>
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Item</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Qty</th>
                            <th style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Unit Cost</th>
                            <th style="padding: 10px 12px; text-align: right; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Funding</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml}</tbody>
                </table>
            </div>
        `;
    } catch (err) {
        console.error('Failed to load budget breakdown:', err);
        container.innerHTML = '';
    }
}

// Delete Campaign
async function deleteCampaign(campaignId) {
    if (!confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + getToken()
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to delete campaign'));
            return;
        }
        
        // Close any open view modal before refreshing
        const viewModal = document.getElementById('viewCampaignModal');
        if (viewModal) {
            viewModal.remove();
        }
        
        alert('Campaign deleted successfully!');
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to delete campaign: ' + err.message);
    }
}

// Update Campaign
async function updateCampaign(campaignId) {
    const createStatusEl = document.getElementById('createStatus');
    createStatusEl.style.display = 'block';
    createStatusEl.className = 'status-text';
    
    const currentToken = getToken();
    if (!currentToken || currentToken.trim() === '') {
        createStatusEl.textContent = 'Authorization token missing. Please log in again.';
        createStatusEl.className = 'status-text error';
        return;
    }
    
    createStatusEl.textContent = 'Updating...';
    
    try {
        // Get form values (same as create)
        const barangayZonesEl = document.getElementById('barangay_zones');
        let barangayZones = [];
        if (barangayZonesEl && typeof barangayZonesEl.getSelectedValues === 'function') {
            barangayZones = barangayZonesEl.getSelectedValues();
        } else if (barangayZonesEl?.value) {
            barangayZones = barangayZonesEl.value.split(',').map(s => s.trim()).filter(Boolean);
        }
        
        const assignedStaffEl = document.getElementById('assigned_staff');
        let assignedStaff = [];
        if (assignedStaffEl && typeof assignedStaffEl.getSelectedValues === 'function') {
            assignedStaff = assignedStaffEl.getSelectedValues();
        } else if (assignedStaffEl?.value) {
            const staffInput = assignedStaffEl.value.trim();
            assignedStaff = staffInput ? staffInput.split(',').map(s => s.trim()).filter(Boolean) : [];
        }
        
        const materialsEl = document.getElementById('materials_json');
        let materialsJson = {};
        if (materialsEl && typeof materialsEl.getSelectedValues === 'function') {
            const materialsList = materialsEl.getSelectedValues();
            materialsList.forEach(mat => {
                materialsJson[mat] = 1;
            });
        }
        
        const titleEl = document.getElementById('title');
        const title = (titleEl && typeof titleEl.getSelectedValues === 'function') 
            ? titleEl.getSelectedValues() 
            : titleEl?.value.trim() || '';
        
        const locationEl = document.getElementById('location');
        const location = (locationEl && typeof locationEl.getSelectedValues === 'function') 
            ? locationEl.getSelectedValues() 
            : locationEl?.value.trim() || null;
        
        const geographicScopeEl = document.getElementById('geographic_scope');
        const geographicScope = (geographicScopeEl && typeof geographicScopeEl.getSelectedValues === 'function') 
            ? geographicScopeEl.getSelectedValues() 
            : geographicScopeEl?.value.trim() || null;
        
        const categoryEl = document.getElementById('category');
        const category = (categoryEl && typeof categoryEl.getSelectedValues === 'function')
            ? categoryEl.getSelectedValues()
            : (categoryEl?.value.trim() || null);
        
        const statusEl = document.getElementById('status');
        const status = (statusEl && typeof statusEl.getSelectedValues === 'function')
            ? statusEl.getSelectedValues()
            : (statusEl?.value.trim() || 'draft');
        
        const descriptionEl = document.getElementById('description');
        const startDateEl = document.getElementById('start_date');
        const endDateEl = document.getElementById('end_date');
        const draftScheduleEl = document.getElementById('draft_schedule_datetime');
        const objectivesEl = document.getElementById('objectives');
        const budgetEl = document.getElementById('budget');
        const staffCountEl = document.getElementById('staff_count');
        
        const startTimeEl = document.getElementById('start_time');
        const endTimeEl = document.getElementById('end_time');
        
        const payload = {
            title: title,
            description: descriptionEl ? descriptionEl.value.trim() : '',
            category: category,
            geographic_scope: geographicScope,
            status: status,
            start_date: startDateEl ? (startDateEl.value || null) : null,
            start_time: startTimeEl ? (startTimeEl.value || null) : null,
            end_date: endDateEl ? (endDateEl.value || null) : null,
            end_time: endTimeEl ? (endTimeEl.value || null) : null,
            draft_schedule_datetime: draftScheduleEl ? (draftScheduleEl.value || null) : null,
            objectives: objectivesEl ? (objectivesEl.value.trim() || null) : null,
            location: location,
            assigned_staff: assignedStaff,
            barangay_target_zones: barangayZones,
            budget: budgetEl ? (parseFloat(budgetEl.value) || null) : null,
            staff_count: staffCountEl ? (parseInt(staffCountEl.value) || null) : null,
            materials_json: materialsJson,
        };
        
        // Log individual field values for debugging
        console.log('=== UPDATE FORM FIELD VALUES DEBUG ===');
        console.log('start_time element:', startTimeEl);
        console.log('start_time value:', startTimeEl ? startTimeEl.value : 'ELEMENT NOT FOUND');
        console.log('end_time element:', endTimeEl);
        console.log('end_time value:', endTimeEl ? endTimeEl.value : 'ELEMENT NOT FOUND');
        console.log('barangay_zones element:', document.getElementById('barangay_zones'));
        console.log('barangay_zones value:', barangayZones);
        console.log('=== END UPDATE FIELD VALUES DEBUG ===');
        console.log('Update payload:', JSON.stringify(payload, null, 2));
        
        if (!payload.title) {
            createStatusEl.textContent = 'Title is required.';
            createStatusEl.className = 'status-text error';
            return;
        }
        
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify(payload)
        });
        
        let data;
        try {
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                const text = await res.text();
                throw new Error(text || 'Server returned non-JSON response');
            }
        } catch (parseError) {
            createStatusEl.textContent = 'Error: Unable to parse server response. ' + parseError.message;
            createStatusEl.className = 'status-text error';
            return;
        }
        
        if (!res.ok) {
            if (data.error && (data.error.toLowerCase().includes('authorization') || data.error.toLowerCase().includes('token'))) {
                createStatusEl.textContent = 'Authorization token missing or expired. Please log in again.';
                createStatusEl.className = 'status-text error';
                localStorage.removeItem('jwtToken');
                setTimeout(() => {
                    window.location.href = basePath + '/login.php';
                }, 2000);
            } else {
                createStatusEl.textContent = data.error || 'Failed to update campaign.';
                createStatusEl.className = 'status-text error';
            }
            return;
        }
        
        createStatusEl.textContent = 'Campaign updated successfully!';
        createStatusEl.className = 'status-text success';
        
        // Show toast notification
        showSuccessToast('Campaign updated successfully!');
        
        // Log the updated campaign data to verify what was saved
        console.log('Campaign updated - Response data:', data);
        if (data.campaign) {
            console.log('Updated campaign values:');
            console.log('- start_time:', data.campaign.start_time);
            console.log('- end_time:', data.campaign.end_time);
            console.log('- barangay_target_zones:', data.campaign.barangay_target_zones);
        }
        
        clearForm();
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
        // Calendar will auto-refresh via refetchEvents() in loadCampaigns()
        if (calendar) calendar.refetchEvents();
        
    } catch (err) {
        createStatusEl.textContent = 'Error: ' + err.message;
        createStatusEl.className = 'status-text error';
    }
}

// Forward Campaign to Pending (Secretary only)
async function forwardToPending(campaignId) {
    if (!isSecretary() && !isAdmin()) {
        alert('Only Secretary can forward campaigns to pending status.');
        return;
    }
    
    if (!confirm('Forward this campaign to Pending status for review?')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'pending' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to forward campaign'));
            return;
        }
        
        alert('Campaign forwarded to Pending status successfully!');
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to forward campaign: ' + err.message);
    }
}

// Approve Campaign (Captain only)
async function approveCampaign(campaignId) {
    if (!isCaptain() && !isAdmin()) {
        alert('Only Captain can approve campaigns.');
        return;
    }
    
    if (!confirm('Approve this campaign? This will change the status to Approved.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'approved' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to approve campaign'));
            return;
        }
        
        alert('Campaign approved successfully!');
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to approve campaign: ' + err.message);
    }
}

// Finalize Schedule (Captain only)
async function finalizeSchedule(campaignId) {
    if (!canFinalizeSchedule()) {
        showToast('Only Captain can finalize schedules.', 'error');
        return;
    }
    
    const campaign = allCampaigns.find(c => c.id === campaignId);
    if (!campaign) {
        showToast('Campaign not found', 'error');
        return;
    }
    
    // Use AI recommended schedule if available, otherwise prompt for manual entry
    let scheduleDateTime = campaign.ai_recommended_datetime || campaign.draft_schedule_datetime;
    
    if (!scheduleDateTime) {
        const manualDateTime = await customPrompt('Enter final schedule date & time (YYYY-MM-DD HH:MM:SS):', '', 'Schedule Date & Time');
        if (!manualDateTime) return;
        scheduleDateTime = manualDateTime;
    }
    
    const confirmed = await customConfirm(`Finalize schedule for ${campaign.title}?\nSchedule: ${scheduleDateTime}`, 'Finalize Schedule');
    if (!confirmed) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + `/api/v1/campaigns/${campaignId}/final-schedule`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ 
                final_schedule_datetime: scheduleDateTime,
                finalize: true  // This flag tells the API to change status to "scheduled"
            })
        });
        
        const data = await res.json();
        if (!res.ok) {
            showToast('Error: ' + (data.error || 'Failed to finalize schedule'), 'error');
            return;
        }
        
        showToast('Schedule finalized successfully! Status changed to Scheduled.', 'success');
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
    } catch (err) {
        showToast('Failed to finalize schedule: ' + err.message, 'error');
    }
}

// Submit to Secretary (Staff only - Draft campaigns)
async function submitToSecretary(campaignId) {
    if (!isStaff() && !isAdmin()) {
        alert('Only Staff can submit campaigns to Secretary.');
        return;
    }
    
    const campaign = allCampaigns.find(c => c.id === campaignId);
    if (!campaign) {
        alert('Campaign not found');
        return;
    }
    
    if (campaign.status !== 'draft') {
        alert('Only Draft campaigns can be submitted to Secretary.');
        return;
    }
    
    if (!confirm(`Submit "${campaign.title}" to Secretary for review?`)) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'pending' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to submit campaign'));
            return;
        }
        
        alert('Campaign submitted to Secretary successfully!');
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to submit campaign: ' + err.message);
    }
}

// Return for Revision (Secretary/Kagawad - can return Draft/Pending to Draft)
async function returnForRevision(campaignId) {
    if ((!isSecretary() && !isKagawad()) && !isAdmin()) {
        alert('Only Secretary or Kagawad can return campaigns for revision.');
        return;
    }
    
    const campaign = allCampaigns.find(c => c.id === campaignId);
    if (!campaign) {
        alert('Campaign not found');
        return;
    }
    
    if (campaign.status !== 'draft' && campaign.status !== 'pending') {
        alert('Only Draft or Pending campaigns can be returned for revision.');
        return;
    }
    
    const reason = prompt('Enter reason for returning this campaign for revision:');
    if (!reason) {
        return;
    }
    
    if (!confirm(`Return "${campaign.title}" for revision?\nReason: ${reason}`)) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'draft' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to return campaign for revision'));
            return;
        }
        
        alert('Campaign returned for revision successfully!');
        refreshAllCampaignViews();
    } catch (err) {
        alert('Failed to return campaign: ' + err.message);
    }
}

// Recommend Approval (Kagawad only - adds recommendation but doesn't approve)
async function recommendApproval(campaignId) {
    if (!isKagawad() && !isAdmin()) {
        alert('Only Kagawad can recommend approval.');
        return;
    }
    
    const campaign = allCampaigns.find(c => c.id === campaignId);
    if (!campaign) {
        alert('Campaign not found');
        return;
    }
    
    if (campaign.status !== 'pending') {
        alert('Only Pending campaigns can be recommended for approval.');
        return;
    }
    
    const recommendation = prompt('Enter your recommendation notes:');
    if (!recommendation) {
        return;
    }
    
    if (!confirm(`Recommend "${campaign.title}" for approval?\nRecommendation: ${recommendation}\n\nNote: This does not approve the campaign. Only Captain can give final approval.`)) {
        return;
    }
    
    // Kagawad can't change status, but we can add a note/comment
    // For now, we'll keep status as 'pending' but log the recommendation
    alert('Recommendation recorded! Campaign remains in Pending status. Captain will review and approve.');
    // Note: In a full implementation, you might want to store recommendations in a separate table
    refreshAllCampaignViews();
}

// Close Campaign (Captain only - Approved/Ongoing → Completed)
async function closeCampaign(campaignId) {
    if (!isCaptain() && !isAdmin()) {
        showToast('Only Captain can close campaigns.', 'error');
        return;
    }
    
    const campaign = allCampaigns.find(c => c.id === campaignId);
    if (!campaign) {
        showToast('Campaign not found', 'error');
        return;
    }
    
    if (campaign.status !== 'approved' && campaign.status !== 'ongoing' && campaign.status !== 'scheduled') {
        showToast('Only Approved, Scheduled, or Ongoing campaigns can be closed.', 'error');
        return;
    }
    
    const confirmed = await customConfirm(`Close "${campaign.title}"? This will mark the campaign as Completed.`, 'Close Campaign');
    if (!confirmed) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'completed' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            showToast('Error: ' + (data.error || 'Failed to close campaign'), 'error');
            return;
        }
        
        showToast('Campaign closed successfully!', 'success');
        refreshAllCampaignViews();
    } catch (err) {
        showToast('Failed to close campaign: ' + err.message, 'error');
    }
}

// Archive Campaign
async function archiveCampaign(campaignId) {
    if (!canFinalizeSchedule()) {
        alert('Only Captain and Admin can archive campaigns.');
        return;
    }
    
    if (!confirm('Are you sure you want to archive this campaign?')) {
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken || currentToken.trim() === '') {
        alert('Authorization token missing. Please log in again.');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ status: 'archived' })
        });
        
        const data = await res.json();
        if (!res.ok) {
            showToast('Error: ' + (data.error || 'Failed to archive campaign'), 'error');
            return;
        }
        
        showToast('Campaign archived successfully!', 'success');
        // FIX: Use centralized refresh to ensure all views update
        refreshAllCampaignViews();
        
    } catch (err) {
        showToast('Failed to archive campaign: ' + err.message, 'error');
    }
}

// Flag to track if archived modal operation is in progress
let archivedModalOperationInProgress = false;

// Show Archived Campaigns Modal
function showArchivedCampaigns() {
    // Prevent duplicate modals
    const existingModal = document.getElementById('archivedCampaignsModal');
    if (existingModal) {
        // Modal already exists, just refresh the list
        loadArchivedCampaignsList();
        return;
    }
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'archivedCampaignsModal';
    modal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.7); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(4px);';
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 900px; width: 90%; max-height: 80vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);">
            <div style="background: linear-gradient(135deg, #115e59 0%, #0f766e 100%); color: white; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 600;"><i class="fas fa-archive"></i> Archived Campaigns</h3>
                <button onclick="closeArchivedCampaignsModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <div style="padding: 20px 24px; color: #64748b; font-size: 14px; border-bottom: 1px solid #e2e8f0;">
                Archived campaigns are hidden from the main list. You can restore them or delete them permanently.
            </div>
            <div id="archivedCampaignsList" style="max-height: 50vh; overflow-y: auto; padding: 0;"></div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Close on backdrop click only
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            closeArchivedCampaignsModal();
        }
    });
    
    loadArchivedCampaignsList();
}

// Load archived campaigns list
async function loadArchivedCampaignsList() {
    const container = document.getElementById('archivedCampaignsList');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align: center; padding: 24px; color: #64748b;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns?status=archived', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const archivedCampaigns = (data.data || []).filter(c => (c.status || '').toLowerCase() === 'archived');
        
        if (archivedCampaigns.length === 0) {
            container.innerHTML = '<p style="text-align: center; padding: 24px; color: #64748b;">No archived campaigns found.</p>';
            return;
        }
        
        let html = `
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0;">ID</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0;">Title</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0;">Category</th>
                        <th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0;">Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        archivedCampaigns.forEach(c => {
            html += `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px 16px; color: #64748b;">${c.id}</td>
                    <td style="padding: 12px 16px; font-weight: 500; color: #0f172a;">${c.title || 'Untitled'}</td>
                    <td style="padding: 12px 16px; color: #64748b;">${c.category || '—'}</td>
                    <td style="padding: 12px 16px;">
                        <button class="btn btn-success" onclick="restoreArchivedCampaign(${c.id})" style="padding: 4px 10px; font-size: 11px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px;">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                        <button class="btn btn-danger" onclick="event.stopPropagation(); deleteArchivedCampaignPermanently(${c.id})" style="padding: 4px 10px; font-size: 11px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; padding: 24px; color: #dc2626;">Error loading archived campaigns: ' + err.message + '</p>';
    }
}

// Close archived campaigns modal
function closeArchivedCampaignsModal() {
    const modal = document.getElementById('archivedCampaignsModal');
    if (modal) {
        modal.remove();
    }
}

// Restore archived campaign
async function restoreArchivedCampaign(campaignId) {
    if (!confirm('Are you sure you want to restore this campaign?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'PUT',
            headers: {
                'Authorization': 'Bearer ' + getToken(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ status: 'draft' })
        });
        
        if (!res.ok) {
            const data = await res.json();
            showToast('Error: ' + (data.error || 'Failed to restore campaign'), 'error');
            return;
        }
        
        showToast('Campaign restored successfully!', 'success');
        await loadArchivedCampaignsList();
        refreshAllCampaignViews();
    } catch (err) {
        showToast('Failed to restore campaign: ' + err.message, 'error');
    }
}

// Delete archived campaign permanently
async function deleteArchivedCampaignPermanently(campaignId) {
    // Use native confirm to avoid modal interference issues
    const confirmed = window._originalConfirm ? 
        window._originalConfirm('Are you sure you want to PERMANENTLY delete this campaign? This action cannot be undone.') :
        confirm('Are you sure you want to PERMANENTLY delete this campaign? This action cannot be undone.');
    
    if (!confirmed) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        if (!res.ok) {
            const data = await res.json();
            showToast('Error: ' + (data.error || 'Failed to delete campaign'), 'error');
            return;
        }
        
        showToast('Campaign deleted permanently!', 'success');
        await loadArchivedCampaignsList();
        setTimeout(() => refreshAllCampaignViews(), 100);
    } catch (err) {
        showToast('Failed to delete campaign: ' + err.message, 'error');
    }
}

// Segments
// Toggle segment help container
function toggleSegmentHelp() {
    const container = document.getElementById('segmentHelpContainer');
    const icon = document.getElementById('segmentHelpIcon');
    if (container && icon) {
        const isVisible = container.style.display !== 'none';
        container.style.display = isVisible ? 'none' : 'block';
        icon.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
    }
}

async function loadSegments() {
    const cid = activeCampaignId || parseInt(document.getElementById('active_campaign').value);
    if (!cid) {
        document.getElementById('segmentTable').innerHTML = '<tr><td colspan="3" style="text-align:center; padding:16px;">Select a campaign first.</td></tr>';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/segments', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        const segments = data.data || [];
        
        const tbody = document.getElementById('segmentTable');
        if (!segments.length) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; padding:16px;">No segments assigned.</td></tr>';
            document.getElementById('segment_ids').value = '';
            return;
        }
        
        tbody.innerHTML = '';
        const ids = [];
        segments.forEach(s => {
            ids.push(s.id);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${s.id}</td>
                <td>${s.name || ''}</td>
                <td><code style="font-size:12px;">${JSON.stringify(s.criteria || {})}</code></td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById('segment_ids').value = ids.join(',');
    } catch (err) {
        console.error('Failed to load segments:', err);
    }
}

async function saveSegments() {
    const cid = activeCampaignId || parseInt(document.getElementById('active_campaign').value);
    if (!cid) {
        alert('Select a campaign first');
        return;
    }
    
    const raw = document.getElementById('segment_ids').value || '';
    const ids = raw.split(',').map(s => parseInt(s.trim(), 10)).filter(n => !isNaN(n));
    
    const statusEl = document.getElementById('segmentStatus');
    statusEl.style.display = 'block';
    statusEl.textContent = 'Saving...';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/segments', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ segment_ids: ids })
        });
        const data = await res.json();
        statusEl.textContent = data.message || 'Segments saved.';
        statusEl.className = 'status-text success';
        loadSegments();
    } catch (err) {
        statusEl.textContent = 'Failed to save segments.';
        statusEl.className = 'status-text error';
    }
}

// Initialize
async function initializeCampaigns() {
    console.log('initializeCampaigns() - Starting initialization');
    try {
        // Wait for campaigns to load
        await loadCampaigns();
        console.log('initializeCampaigns() - Campaigns loaded. Count:', allCampaigns.length);
        
        // Initialize calendar if not already initialized
        if (!calendar) {
            console.log('initializeCampaigns() - Initializing calendar...');
            initCalendar();
        }
        
        loadResources();
        
        // Populate AutoML dropdown immediately after campaigns are loaded
        console.log('initializeCampaigns() - Populating AutoML dropdown with', allCampaigns.length, 'campaigns');
        populateAutoMLDropdown();
        validateAutoMLForm();
        
        // Also set up a delayed check as backup
        setTimeout(() => {
            const automlSelect = document.getElementById('automl_campaign_id');
            if (automlSelect && automlSelect.options.length <= 1 && allCampaigns.length > 0) {
                console.log('initializeCampaigns() - Dropdown empty, populating...');
                populateAutoMLDropdown();
                validateAutoMLForm();
            } else if (automlSelect) {
                console.log('initializeCampaigns() - Dropdown already has', automlSelect.options.length - 1, 'options');
                validateAutoMLForm();
            }
            
            // Ensure Get Prediction button has event listener
            const getPredictionBtn = document.getElementById('getPredictionBtn');
            if (getPredictionBtn) {
                console.log('initializeCampaigns() - Found Get Prediction button');
                // Remove old onclick and add event listener
                getPredictionBtn.onclick = null;
                
                // Remove any existing listeners first
                const newBtn = getPredictionBtn.cloneNode(true);
                getPredictionBtn.parentNode.replaceChild(newBtn, getPredictionBtn);
                
                // Get the new button reference
                const btn = document.getElementById('getPredictionBtn');
                
                // Add click event listener
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('=== Get Prediction button clicked via event listener ===');
                    handleGetPredictionClick(e);
                });
                
                // Also set onclick as backup
                btn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('=== Get Prediction button clicked via onclick handler ===');
                    handleGetPredictionClick(e);
                };
                
                console.log('initializeCampaigns() - Get Prediction button event listeners attached');
            } else {
                console.warn('initializeCampaigns() - Get Prediction button not found!');
            }
            
            // Ensure Refresh button has event listener
            const refreshBtn = document.getElementById('automlRefreshBtn');
            if (refreshBtn) {
                console.log('initializeCampaigns() - Found Refresh button');
                
                // Remove any existing listeners by cloning
                const newRefreshBtn = refreshBtn.cloneNode(true);
                refreshBtn.parentNode.replaceChild(newRefreshBtn, refreshBtn);
                
                // Get the new button reference
                const refreshBtnNew = document.getElementById('automlRefreshBtn');
                
                // Add click event listener
                refreshBtnNew.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('=== Refresh button clicked ===');
                    console.log('refreshAutoMLCampaigns type:', typeof refreshAutoMLCampaigns);
                    console.log('window.refreshAutoMLCampaigns type:', typeof window.refreshAutoMLCampaigns);
                    
                    try {
                        if (typeof refreshAutoMLCampaigns === 'function') {
                            refreshAutoMLCampaigns();
                        } else if (typeof window.refreshAutoMLCampaigns === 'function') {
                            window.refreshAutoMLCampaigns();
                        } else {
                            console.error('refreshAutoMLCampaigns function not found!');
                            alert('Error: Refresh function not loaded. Please refresh the page.');
                        }
                    } catch (err) {
                        console.error('Error in refresh button click handler:', err);
                        alert('Error: ' + err.message);
                    }
                });
                
                console.log('initializeCampaigns() - Refresh button event listener attached');
            } else {
                console.warn('initializeCampaigns() - Refresh button not found!');
            }
        }, 200);
        
        loadStaffTable();
        
        // Calendar will auto-refresh via refetchEvents() in loadCampaigns()
    } catch (err) {
        console.error('initializeCampaigns() - Error:', err);
        console.error('initializeCampaigns() - Stack:', err.stack);
    }
}

// Show Campaign "How It Works" modal
function showCampaignHowItWorks() {
    const tips = `
        <div style="max-width: 700px; padding: 24px;">
            <h3 style="margin: 0 0 20px 0; color: #0f172a; font-size: 22px;">📋 Campaign Module - How It Works</h3>
            <div style="line-height: 1.8; color: #475569; font-size: 14px;">
                
                <div style="margin-bottom: 24px; padding: 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #4c8a89;">
                    <strong style="color: #065f46; display: block; margin-bottom: 12px; font-size: 16px;">🎯 Complete Campaign Workflow (10 Steps):</strong>
                    <ol style="margin: 0; padding-left: 20px; line-height: 2;">
                        <li><strong>Create Campaign</strong> - Fill out campaign details (title, category, dates, location, etc.)</li>
                        <li><strong>Select Audience & Content</strong> - Choose target audience segments and attach materials from Content Repository</li>
                        <li><strong>Request Optimal Posting Time</strong> - Click "Get Prediction" in AI-Powered Deployment Optimization</li>
                        <li><strong>AI Processing</strong> - System provides decision support through AI-powered analysis interface</li>
                        <li><strong>AI Analysis</strong> - System is designed to support Google AutoML integration or uses heuristic-based prediction algorithms</li>
                        <li><strong>AI Recommendation</strong> - System displays optimal date/time recommendation with confidence metrics</li>
                        <li><strong>Display Recommendation</strong> - AI suggestion shown with confidence score and decision support rationale</li>
                        <li><strong>Review & Confirm</strong> - User reviews the AI recommendation and decision support data</li>
                        <li><strong>Accept Schedule</strong> - User confirms and accepts the recommended schedule</li>
                        <li><strong>Campaign Saved</strong> - Campaign is saved with final schedule and appears in All Campaigns table</li>
                    </ol>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">📋 Main Sections Explained:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Plan New Campaign</strong> - Create campaigns with all details (title, category, dates, location, budget, staff, materials)</li>
                        <li><strong>AI-Powered Deployment Optimization</strong> - Provides decision support for optimal posting times through AI-powered analysis (designed to support Google AutoML integration or heuristic-based algorithms)</li>
                        <li><strong>Campaign Calendar</strong> - Calendar view to see campaign schedules by month/week. Click on any campaign event to view details.</li>
                        <li><strong>Resource Allocation</strong> - Overview of total budget, staff, and active campaigns</li>
                        <li><strong>All Campaigns</strong> - Table view of all campaigns with actions (edit, delete, view details)</li>
                        <li><strong>Schedule Management</strong> - Manage and update campaign schedules</li>
                        <li><strong>Target Segments</strong> - Manage audience segments for campaigns</li>
                        <li><strong>Linked Content</strong> - View and manage content materials attached to campaigns</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🔗 Conceptual Integration with Content Module:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Materials Selection</strong> - When creating a campaign, you can select materials from the Content Repository</li>
                        <li><strong>Approved Content Only</strong> - Only approved content items can be attached to campaigns</li>
                        <li><strong>Content Types</strong> - Posters, videos, guidelines, and infographics can be linked to campaigns</li>
                        <li><strong>Linked Content Section</strong> - View all content materials attached to a specific campaign</li>
                    </ul>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🤖 AI Scheduling Flow:</strong>
                    <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                        <p style="margin: 0 0 8px 0;"><strong>Step-by-Step:</strong></p>
                        <ol style="margin: 0; padding-left: 20px; font-size: 13px;">
                            <li>Create campaign first (without schedule)</li>
                            <li>Go to "AI-Powered Deployment Optimization" section</li>
                            <li>Select the campaign ID from dropdown</li>
                            <li>Optionally enter audience segment ID</li>
                            <li>Click "Get AI Prediction" button</li>
                            <li>System processes request and provides decision support recommendation (designed to support Google AutoML integration or heuristic-based analysis)</li>
                            <li>Review the recommended date/time and confidence score</li>
                            <li>Click "Accept AI Recommendation" to confirm</li>
                            <li>Campaign schedule is updated automatically</li>
                            <li>View updated schedule in "All Campaigns" table</li>
                        </ol>
                    </div>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">💡 Pro Tips:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Complete Form First</strong> - Fill out all campaign details before requesting AI recommendation</li>
                        <li><strong>Attach Materials</strong> - Link approved content from Content Repository for better campaign planning</li>
                        <li><strong>Check Conflicts</strong> - Use "Check Conflicts" button to see if schedule conflicts with other campaigns</li>
                        <li><strong>Override if Needed</strong> - You can manually override AI recommendation if needed</li>
                        <li><strong>View Timeline</strong> - Use Gantt Chart and Calendar to visualize all campaign schedules</li>
                        <li><strong>Monitor Resources</strong> - Check Resource Allocation section to see budget and staff usage</li>
                    </ul>
                </div>
                
                <div style="background: #fff7ed; padding: 12px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                    <strong style="color: #92400e;">⚠️ Important Notes:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 13px;">
                        <li>Draft Schedule field is disabled - schedule must be set via AI recommendation workflow</li>
                        <li>AI recommendation workflow is designed to guide schedule selection and provide decision support</li>
                        <li>Only approved content from Content Repository can be attached</li>
                        <li>Campaign status changes based on schedule confirmation</li>
                    </ul>
                </div>
            </div>
        </div>
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.onclick = function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    
    const content = document.createElement('div');
    content.style.cssText = 'background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); max-width: 700px; max-height: 85vh; overflow-y: auto;';
    content.innerHTML = tips + '<button type="button" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>';
    content.onclick = function(e) {
        if (e.target.tagName === 'BUTTON') {
            document.body.removeChild(modal);
        }
    };
    
    modal.appendChild(content);
    document.body.appendChild(modal);
}

// Wait for DOM to be fully ready before initializing
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        console.log('DOMContentLoaded - Initializing campaigns');
        initializeCampaigns();
        loadAiRecommendations();
        
        // Make functions globally accessible after DOM loads
        window.handleGetPredictionClick = handleGetPredictionClick;
        window.getAutoMLPrediction = getAutoMLPrediction;
        window.refreshAutoMLCampaigns = refreshAutoMLCampaigns;
        window.loadAiRecommendations = loadAiRecommendations;
        window.closeAiRecommendationModal = closeAiRecommendationModal;
        console.log('Functions made globally accessible:', {
            handleGetPredictionClick: typeof window.handleGetPredictionClick,
            getAutoMLPrediction: typeof window.getAutoMLPrediction,
            refreshAutoMLCampaigns: typeof window.refreshAutoMLCampaigns
        });
    });
} else {
    // DOM is already ready
    console.log('DOM already ready - Initializing campaigns');
    
    // Make functions globally accessible
    window.handleGetPredictionClick = handleGetPredictionClick;
    window.getAutoMLPrediction = getAutoMLPrediction;
    window.refreshAutoMLCampaigns = refreshAutoMLCampaigns;
    window.loadAiRecommendations = loadAiRecommendations;
    window.closeAiRecommendationModal = closeAiRecommendationModal;
    
    initializeCampaigns();
    loadAiRecommendations();
}

// Also try to populate dropdown when the AutoML section becomes visible
const observer = new MutationObserver((mutations) => {
    const automlSelect = document.getElementById('automl_campaign_id');
    if (automlSelect && automlSelect.options.length <= 1 && allCampaigns.length > 0) {
        console.log('MutationObserver - AutoML dropdown detected, populating...');
        populateAutoMLDropdown();
    }
});

// Observe the automl section for visibility changes
const automlSection = document.getElementById('automl-section');
if (automlSection) {
    observer.observe(automlSection, { childList: true, subtree: true });
}

// Helper function to generate HTML for data factors actually used
function getDataFactorsHTML(dataSources) {
    if (!dataSources) {
        return '';
    }
    
    // Handle new format with used/not_used
    const usedSources = dataSources.used || [];
    const notUsedSources = dataSources.not_used || [];
    
    if (usedSources.length === 0 && notUsedSources.length === 0) {
        return '';
    }
    
    let factorsList = '';
    if (usedSources.length > 0) {
        factorsList = usedSources.map(source => {
            const countText = source.count > 0 ? ` (${source.count} records)` : '';
            return `<li style="color: #065f46; margin-bottom: 6px;"><i class="fas fa-check-circle" style="color: #10b981; margin-right: 8px;"></i><strong>${source.name}</strong>${countText} - <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 11px;">${source.table}</code></li>`;
        }).join('');
    }
    
    if (notUsedSources.length > 0 && usedSources.length === 0) {
        // Only show not used if no sources were used (transparency)
        factorsList += notUsedSources.map(source => {
            return `<li style="color: #92400e; margin-bottom: 6px;"><i class="fas fa-times-circle" style="color: #f59e0b; margin-right: 8px;"></i><strong>${source.name}</strong> - ${source.reason}</li>`;
        }).join('');
    }
    
    return `
        <div style="background: #f0f9ff; border: 2px solid #0ea5e9; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <i class="fas fa-database" style="color: #0ea5e9; font-size: 18px;"></i>
                <strong style="color: #0c4a6e; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Data Factors Actually Used in This Prediction</strong>
            </div>
            <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8;">
                ${factorsList || '<li style="color: #64748b;">No data sources available in Nagkaisang Nayon dataset</li>'}
            </ul>
        </div>
    `;
}

// Show modal explaining how the AI works (dynamically generated from actual data sources)
function showAIHowItWorksModal() {
    const modal = document.createElement('div');
    modal.id = 'aiHowItWorksModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    // Get data sources from current prediction if available
    const dataSources = currentPrediction?.data_sources_used || {};
    const usedSources = dataSources.used || [];
    const notUsedSources = dataSources.not_used || [];
    
    // Build used sources HTML
    let usedSourcesHTML = '';
    if (usedSources.length > 0) {
        usedSourcesHTML = '<div style="background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 6px; padding: 12px; margin-bottom: 16px;"><strong style="color: #065f46; display: block; margin-bottom: 8px;">✓ Data sources actually used:</strong><ul style="margin: 0; padding-left: 20px; color: #0f172a; font-size: 13px; line-height: 1.8;">';
        usedSources.forEach(source => {
            const countText = source.count > 0 ? ` (${source.count} records found)` : '';
            usedSourcesHTML += `<li><strong>${source.name}</strong>${countText} - <code>${source.table}</code></li>`;
        });
        usedSourcesHTML += '</ul></div>';
    }
    
    // Build not used sources HTML
    let notUsedSourcesHTML = '';
    if (notUsedSources.length > 0) {
        notUsedSourcesHTML = '<div style="background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 12px; margin-bottom: 16px;"><strong style="color: #92400e; display: block; margin-bottom: 8px;">✖ Data sources not used:</strong><ul style="margin: 0; padding-left: 20px; color: #78350f; font-size: 13px; line-height: 1.8;">';
        notUsedSources.forEach(source => {
            notUsedSourcesHTML += `<li><strong>${source.name}</strong> - ${source.reason}</li>`;
        });
        notUsedSourcesHTML += '</ul></div>';
    }
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 12px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 20px; font-weight: 700;">How the Scheduling Intelligence Works</h3>
                <button onclick="this.closest('#aiHowItWorksModal').remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <div style="padding: 24px;">
                <p style="margin: 0 0 16px 0; color: #475569; font-size: 14px; line-height: 1.6;">
                    The scheduler analyzes multiple historical and operational data sources from <strong>Barangay Nagkaisang Nayon, Quezon City</strong> to generate realistic schedule recommendations. 
                    <strong>All data is sourced exclusively from this system's database.</strong>
                </p>
                ${usedSourcesHTML}
                ${notUsedSourcesHTML}
                <p style="margin: 16px 0 0 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                    The system uses these datasets to: <strong>recommend optimal dates and times</strong>, <strong>avoid schedule conflicts</strong>, 
                    <strong>maximize expected attendance</strong>, and <strong>improve preparedness impact</strong>.
                </p>
                <p style="margin: 12px 0 0 0; color: #94a3b8; font-size: 12px; font-style: italic;">
                    If advanced ML is not configured, the system uses a rule-based heuristic model derived from these same datasets.
                </p>
            </div>
        </div>
    `;
    
    modal.onclick = function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    };
    
    document.body.appendChild(modal);
}

// Helper function to generate HTML for AI recommendations
function getAIRecommendationsHTML(recommendations) {
    if (!recommendations || Object.keys(recommendations).length === 0) {
        return `
            <div style="background: #f3f4f6; border: 2px solid #9ca3af; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <i class="fas fa-info-circle" style="color: #6b7280; font-size: 18px;"></i>
                    <strong style="color: #374151; font-size: 14px;">AI Recommendations</strong>
                </div>
                <p style="margin: 0; color: #6b7280; font-size: 13px; line-height: 1.6;">
                    Not generated – insufficient factual records in Nagkaisang Nayon dataset. Schedule recommendation is still available.
                </p>
            </div>
        `;
    }
    
    const fieldLabels = {
        'title': 'Campaign Title',
        'category': 'Category',
        'budget': 'Budget',
        'staff_count': 'Staff Count',
        'assigned_staff': 'Assigned Staff',
        'materials': 'Materials'
    };
    
    let html = `
        <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <i class="fas fa-lightbulb" style="color: #f59e0b; font-size: 18px;"></i>
                <strong style="color: #92400e; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">AI Recommendations for Campaign Planning</strong>
            </div>
            <p style="margin: 0 0 12px 0; color: #78350f; font-size: 13px; line-height: 1.6;">
                The AI has generated recommendations for the following fields based on historical data analysis. Click "Accept AI Recommendation" to populate the campaign form.
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
    `;
    
    for (const [field, rec] of Object.entries(recommendations)) {
        if (!rec || !rec.value) continue;
        
        const label = fieldLabels[field] || field;
        let valueDisplay = '';
        
        if (field === 'assigned_staff' && Array.isArray(rec.value)) {
            valueDisplay = rec.value.length > 0 ? rec.value.join(', ') : 'None';
        } else if (field === 'materials' && typeof rec.value === 'object') {
            const materials = Object.entries(rec.value);
            valueDisplay = materials.length > 0 ? materials.map(([mat, qty]) => `${mat} (${qty})`).join(', ') : 'None';
        } else if (field === 'budget') {
            valueDisplay = '₱' + parseFloat(rec.value).toLocaleString('en-US', {minimumFractionDigits: 2});
        } else {
            valueDisplay = String(rec.value);
        }
        
        html += `
            <div style="background: white; border: 1px solid #fcd34d; border-radius: 6px; padding: 10px;">
                <div style="font-size: 11px; color: #92400e; font-weight: 600; margin-bottom: 4px;">${label}</div>
                <div style="font-size: 13px; color: #0f172a; font-weight: 600;">${valueDisplay}</div>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

// Helper function to generate HTML for AI Decision Basis
function getAIDecisionBasisHTML(recommendations) {
    if (!recommendations || Object.keys(recommendations).length === 0) {
        return '';
    }
    
    const fieldLabels = {
        'title': 'Campaign Title',
        'category': 'Category',
        'budget': 'Budget',
        'staff_count': 'Staff Count',
        'assigned_staff': 'Assigned Staff',
        'materials': 'Materials'
    };
    
    let html = `
        <div style="background: #eff6ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <i class="fas fa-brain" style="color: #3b82f6; font-size: 18px;"></i>
                <strong style="color: #1e40af; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">AI Decision Basis</strong>
            </div>
            <p style="margin: 0 0 12px 0; color: #1e3a8a; font-size: 13px; line-height: 1.6;">
                Explanation of how each recommendation was generated from real system data:
            </p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
    `;
    
    for (const [field, rec] of Object.entries(recommendations)) {
        if (!rec || !rec.decision_basis) continue;
        
        const label = fieldLabels[field] || field;
        const confidence = rec.confidence ? (rec.confidence * 100).toFixed(0) + '%' : 'N/A';
        
        html += `
            <div style="background: white; border-left: 4px solid #3b82f6; border-radius: 4px; padding: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <strong style="color: #0f172a; font-size: 13px;">${label}</strong>
                    <span style="background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">${confidence} confidence</span>
                </div>
                <p style="margin: 0; color: #475569; font-size: 12px; line-height: 1.6;">${rec.decision_basis}</p>
            </div>
        `;
    }
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

window.showAIHowItWorksModal = showAIHowItWorksModal;
window.getDataFactorsHTML = getDataFactorsHTML;
window.getAIRecommendationsHTML = getAIRecommendationsHTML;
window.getAIDecisionBasisHTML = getAIDecisionBasisHTML;
</script>
<script src="<?php echo htmlspecialchars($basePath . '/public/campaigns_js_functions.js'); ?>"></script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main> <!-- /.main-content-wrapper -->
