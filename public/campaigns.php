<?php
$pageTitle = 'Campaign Planning';
// Custom header setup for sidebar + admin-header layout
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <?php
    require_once __DIR__ . '/../header/includes/path_helper.php';
    ?>
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
                    window.location.replace(basePath + '/index.php');
                } catch (e) {
                    console.error('Auth guard error:', e);
                    // If error accessing localStorage and we just logged in, retry
                    if (justLoggedIn && retryCount < maxRetries) {
                        setTimeout(function() {
                            checkAuth(retryCount + 1);
                        }, 300);
                    } else {
                        window.location.replace(basePath + '/index.php');
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css" rel="stylesheet">
    <script>
        // Force light theme
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        
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
    /* Main content wrapper - accounts for fixed sidebar and header */
    .main-content-wrapper {
        margin-left: 280px; /* Main sidebar only */
        margin-top: 70px;
        min-height: calc(100vh - 70px);
        transition: margin-left 0.3s ease;
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
        max-width: 1600px;
        margin: 0 auto;
        padding: 24px;
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
    }
    .campaign-layout {
        display: block; /* Changed from flex since sidebar is now fixed */
    }
    .campaign-main {
        width: 100%;
    }
    .page-title {
        margin: 0 0 12px;
        font-size: 36px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.5px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .page-title::before {
        content: '';
        width: 4px;
        height: 36px;
        background: linear-gradient(135deg, #4c8a89 0%, #667eea 100%);
        border-radius: 2px;
    }
    .page-subtitle {
        margin: 0 0 40px;
        color: #64748b;
        font-size: 16px;
        line-height: 1.6;
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
    .badge.draft { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
    .badge.pending { background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%); color: #78350f; }
    .badge.approved { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; }
    .badge.ongoing { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }
    .badge.completed { background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); color: #3730a3; }
    .badge.scheduled { background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%); color: #1d4ed8; }
    .badge.active { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }
    .badge.archived { background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); color: #6b7280; }
    
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
    
    #gantt-container {
        margin-top: 24px;
        overflow-x: auto;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        min-height: 400px;
    }
    .gantt-container svg {
        font-family: var(--font-family);
    }
    .gantt-container .bar {
        rx: 6;
        ry: 6;
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
        min-width: 1450px; /* Increased minimum width to ensure Actions column is fully visible */
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 12px;
        overflow: visible; /* Changed from hidden to visible to prevent clipping */
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }
    .data-table thead {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    .data-table th {
        padding: 16px 12px;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    
    /* Responsive column widths */
    .data-table th:nth-child(1), /* ID */
    .data-table td:nth-child(1) {
        width: 60px;
        min-width: 60px;
        max-width: 80px;
    }
    
    .data-table th:nth-child(2), /* Title */
    .data-table td:nth-child(2) {
        width: 200px;
        min-width: 150px;
        max-width: 300px;
        white-space: normal;
        word-wrap: break-word;
    }
    
    .data-table th:nth-child(3), /* Category */
    .data-table td:nth-child(3) {
        width: 120px;
        min-width: 100px;
        max-width: 150px;
    }
    
    .data-table th:nth-child(4), /* Status */
    .data-table td:nth-child(4) {
        width: 100px;
        min-width: 90px;
        max-width: 120px;
    }
    
    .data-table th:nth-child(5), /* Start */
    .data-table th:nth-child(6), /* End */
    .data-table td:nth-child(5),
    .data-table td:nth-child(6) {
        width: 110px;
        min-width: 100px;
        max-width: 130px;
        white-space: nowrap;
    }
    
    .data-table th:nth-child(7), /* Draft Schedule */
    .data-table th:nth-child(8), /* AI Recommended */
    .data-table th:nth-child(9), /* Final Schedule */
    .data-table td:nth-child(7),
    .data-table td:nth-child(8),
    .data-table td:nth-child(9) {
        width: 140px;
        min-width: 120px;
        max-width: 180px;
        white-space: nowrap;
        font-size: 12px;
    }
    
    .data-table th:nth-child(10), /* Location */
    .data-table td:nth-child(10) {
        width: 150px;
        min-width: 120px;
        max-width: 200px;
        white-space: normal;
        word-wrap: break-word;
    }
    
    .data-table th:nth-child(11), /* Budget */
    .data-table td:nth-child(11) {
        width: 100px;
        min-width: 90px;
        max-width: 120px;
        text-align: right;
    }
    
    .data-table th:nth-child(12), /* Actions */
    .data-table td:nth-child(12) {
        width: 200px;
        min-width: 180px;
        max-width: 220px;
        white-space: nowrap;
        position: sticky;
        right: 0;
        background: #fff;
        z-index: 10;
        padding-right: 20px;
        box-shadow: -2px 0 8px rgba(0, 0, 0, 0.05);
    }
    
    .data-table thead th:nth-child(12) {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        z-index: 11;
        box-shadow: -2px 0 8px rgba(0, 0, 0, 0.05);
    }
    
    .data-table tbody tr:hover td:nth-child(12) {
        background: #f8fafc;
    }
    
    .data-table tbody tr:last-child td:nth-child(12) {
        border-bottom: none;
    }
    
    .data-table td {
        padding: 16px 12px;
        text-align: left;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .data-table tbody tr {
        transition: all 0.2s;
    }
    .data-table tbody tr:hover {
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
</style>

<main class="campaign-page">
    <header>
        <h1 class="page-title">Campaign Planning & Management</h1>
        <p class="page-subtitle">Plan, schedule, and track campaigns with timeline visualization, calendar views, and AI-powered optimization.</p>
    </header>

    <div class="campaign-layout">
        <!-- Campaign features are now in the main sidebar as nested submenu -->
        <div class="campaign-main">

    <!-- Planning Form -->
    <section class="card" id="planning-section">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
            <h2 class="section-title analytics-accent" style="margin: 0;">Plan New Campaign</h2>
            <button type="button" class="btn btn-secondary" onclick="showCampaignHowItWorks()" style="padding: 8px 16px; font-size: 13px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-info-circle"></i>
                How It Works
            </button>
        </div>
        
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
                    <select class="standard-select" id="title" required>
                        <option value="">Select campaign title...</option>
                    </select>
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
                <div class="form-field">
                    <label for="status">Status</label>
                    <select class="standard-select" id="status">
                        <option value="">Select status...</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" type="date">
                </div>
                <div class="form-field">
                    <label for="end_date">End Date</label>
                    <input id="end_date" type="date">
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
                    <label for="budget">Budget (PHP)</label>
                    <input id="budget" type="number" step="0.01" placeholder="50000.00">
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
                <div class="form-field full-width" style="margin-bottom: 24px;">
                    <label for="materials_json" style="display: flex; align-items: center; gap: 6px; margin-bottom: 10px; font-weight: 600;">
                        <i class="fas fa-file-alt" style="color: #667eea;"></i>
                        Materials
                    </label>
                    <div class="multi-select-container materials-select">
                        <div class="multi-select-tags" id="materials_json_tags"></div>
                        <select class="multi-select-dropdown" id="materials_json" name="materials[]" multiple size="3">
                        </select>
                    </div>
                    <small style="color: #94a3b8; font-size: 12px; margin-top: 8px; display: block; line-height: 1.5;">
                        <i class="fas fa-info-circle" style="margin-right: 4px;"></i>
                        Select multiple materials from the <strong>Content Repository</strong>. Only approved content can be attached.
                    </small>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Create Campaign</button>
                <button type="button" class="btn btn-secondary" onclick="clearForm()">Clear</button>
            </div>
            <div id="createStatus" class="status-text" style="display:none;"></div>
        </form>
    </section>

    <!-- AutoML Panel -->
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
                        Our AI analyzes historical campaign data, audience engagement patterns from the <strong>Surveys module</strong>, and event conflicts from the <strong>Events module</strong> to recommend the optimal deployment schedule for maximum reach and effectiveness.
                    </p>
                </div>
            </div>
            
            <!-- How It Works Card -->
            <div style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); border-radius: 8px; padding: 16px; margin-bottom: 20px; backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px;">
                    <div style="font-size: 24px; line-height: 1;">💡</div>
                    <div style="flex: 1;">
                        <strong style="display: block; margin-bottom: 8px; font-size: 15px;">How the AI Works:</strong>
                        <p style="margin: 0 0 12px 0; font-size: 13px; line-height: 1.6; opacity: 0.95;">
                            The system uses Google AutoML (if configured) or intelligent heuristic predictions based on historical campaign data. Both methods analyze:
                        </p>
                        <ul style="margin: 0; padding-left: 20px; font-size: 13px; line-height: 1.8; opacity: 0.95;">
                            <li>Similar campaigns and their performance metrics</li>
                            <li>Historical engagement patterns from the <strong>Surveys module</strong></li>
                            <li>Optimal timing based on audience segments from the <strong>Segments module</strong></li>
                            <li>Event conflicts from the <strong>Events module</strong> to avoid scheduling overlaps</li>
                        </ul>
                    </div>
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
                            💡 Campaigns are pulled from the <strong>All Campaigns</strong> section above. Conflict checking will compare with the <strong>Events module</strong>.
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
        </div>
    </section>

    <!-- Timeline & Calendar Tabs -->
    <section class="card" id="timeline-section">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('gantt')">
                <i class="fas fa-chart-gantt"></i> Project Timeline
            </button>
            <button class="tab" onclick="switchTab('calendar')">
                <i class="fas fa-calendar-alt"></i> Scheduling Calendar
            </button>
        </div>
        
        <div id="gantt-tab" class="tab-content active">
            <div class="section-header" style="margin-bottom: 16px;">
                <h3 class="section-title analytics-accent">Gantt Chart</h3>
                <button class="btn btn-secondary" onclick="refreshGantt()" style="display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <p style="margin: 0 0 16px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                Visual timeline of all campaigns. Schedule conflicts are checked against the <strong>Events module</strong> to prevent overlapping activities.
            </p>
            <div id="gantt-container"></div>
        </div>
        
        <div id="calendar-tab" class="tab-content">
            <div class="section-header" style="margin-bottom: 16px;">
                <h3 class="section-title analytics-accent">Calendar View</h3>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-secondary" onclick="calendarView('dayGridMonth')" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-calendar"></i> Month
                    </button>
                    <button class="btn btn-secondary" onclick="calendarView('timeGridWeek')" style="display: flex; align-items: center; gap: 6px;">
                        <i class="fas fa-calendar-week"></i> Week
                    </button>
                </div>
            </div>
            <p style="margin: 0 0 16px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
                Calendar view of campaign schedules. Events from the <strong>Events module</strong> are integrated to show potential conflicts.
            </p>
            <div id="calendar"></div>
        </div>
    </section>

    <!-- Resource Allocation -->
    <section class="card" id="resources-section">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2 class="section-title analytics-accent">Resource Allocation</h2>
            <button class="btn btn-secondary" onclick="loadResources()" style="display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
            Overview of allocated resources across all campaigns. Budget and staff assignments are tracked and integrated with campaign planning.
        </p>
        <div class="resource-grid" id="resourceGrid">
            <div class="resource-card">
                <h4>💰 Total Budget</h4>
                <div class="resource-value" id="totalBudget">₱0.00</div>
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="budgetBreakdown">All campaigns</div>
            </div>
            <div class="resource-card">
                <h4>👥 Total Staff</h4>
                <div class="resource-value" id="totalStaff">0</div>
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="staffBreakdown">Assigned personnel</div>
            </div>
            <div class="resource-card">
                <h4>🚀 Active Campaigns</h4>
                <div class="resource-value" id="activeCampaigns">0</div>
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;" id="campaignBreakdown">Ongoing/Approved</div>
            </div>
            <div class="resource-card">
                <h4>📦 Materials Allocated</h4>
                <div class="resource-value" id="materialsUsed" style="font-size: 18px; line-height: 1.4;">-</div>
                <div style="margin-top: 8px; font-size: 12px; color: #64748b;">Inventory summary</div>
            </div>
        </div>
    </section>

    <!-- Campaigns List -->
    <section class="card" id="list-section">
        <div class="section-header" style="margin-bottom: 20px;">
            <h2 class="section-title analytics-accent">All Campaigns</h2>
            <button class="btn btn-secondary" onclick="loadCampaigns()" style="display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <p style="margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6;">
            Complete list of all campaigns. AI recommendations shown in the <strong>"AI Recommended"</strong> column are generated using engagement data from the <strong>Surveys module</strong> and historical performance metrics.
        </p>
        <div class="form-field" style="max-width: 300px; margin-bottom: 20px;">
            <label for="active_campaign" style="display: flex; align-items: center; gap: 6px; font-weight: 600;">
                <i class="fas fa-filter" style="color: #667eea;"></i>
                Active Campaign
            </label>
            <select id="active_campaign" onchange="onCampaignChange()" style="padding: 10px 12px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; transition: border-color 0.2s;" onfocus="this.style.borderColor='#667eea';" onblur="this.style.borderColor='#e2e8f0';"></select>
        </div>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Draft Schedule</th>
                        <th>AI Recommended</th>
                        <th>Final Schedule</th>
                        <th>Location</th>
                        <th>Budget</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="campaignTable">
                    <tr><td colspan="12" style="text-align:center; padding:24px;">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

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

    <!-- Target Segments -->
    <section class="card" id="segments-section">
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
                <div style="margin-top: 8px; padding: 12px; background: #f1f5f9; border-radius: 6px; font-size: 12px; color: #475569; line-height: 1.6;">
                    <strong style="display: block; margin-bottom: 4px; color: #0f172a;">💡 How to use:</strong>
                    <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                        <li>Enter segment IDs separated by commas (e.g., <code style="background: white; padding: 2px 6px; border-radius: 3px;">1, 2, 5</code>)</li>
                        <li>To find segment IDs, go to the <strong>Segments module</strong> and check the ID column</li>
                        <li>You can assign multiple segments to target different audience groups</li>
                        <li>Segments use data from the <strong>Segments module</strong> which may include attendance records, incident reports, and demographic data</li>
                    </ul>
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

    <!-- Linked Content -->
    <section class="card" id="content-section">
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

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
// Get base path for API calls (path_helper already included in head)
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';
console.log('BASE PATH:', basePath);

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


let calendar, gantt;
let activeCampaignId = null;
let allCampaigns = [];

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
        // Populate options for dynamic selects
        if (document.getElementById('title')) {
            populateStandardSelect('title', SAMPLE_CAMPAIGN_TITLES);
        }
        
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
            window.location.href = basePath + '/index.php';
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
        const endDateValue = document.getElementById('end_date').value;
        // NOTE: draft_schedule_datetime is NOT set during initial creation per sequence diagram
        // Schedule should ONLY be set after user requests AI recommendation and confirms it (Step 9)
        const budgetInput = document.getElementById('budget').value.trim();
        const staffCountInput = document.getElementById('staff_count').value.trim();

        const payload = {
            title: titleValue,
            description: descriptionValue || null,
            category: categoryValue,
            geographic_scope: geographicScopeValue,
            status: statusValue,
            start_date: startDateValue || null,
            end_date: endDateValue || null,
            // draft_schedule_datetime: REMOVED - Schedule must be set via AI recommendation flow (Steps 3-9)
            objectives: objectivesValue || null,
            location: locationValue,
            assigned_staff: assignedStaff.length > 0 ? assignedStaff : null,
            barangay_target_zones: barangayZones.length > 0 ? barangayZones : null,
            budget: budgetInput ? parseFloat(budgetInput) : null,
            staff_count: staffCountInput ? parseInt(staffCountInput) : null,
            materials_json: Object.keys(materialsJson).length > 0 ? materialsJson : null,
        };
        
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
                        window.location.href = basePath + '/index.php';
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
                        window.location.href = basePath + '/index.php';
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
        clearForm();
        loadCampaigns();
        refreshGantt();
        if (calendar) calendar.refetchEvents();
    } catch (err) {
        createStatusEl.textContent = 'Network error. Please try again.';
        createStatusEl.className = 'status-text error';
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
    
    // Clear form fields
    document.getElementById('planningForm').reset();
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
        
        let recommendation = 'Optimal deployment time based on real-time historical performance data';
        if (pred.confidence_score && pred.confidence_score > 0.8) {
            recommendation = 'High confidence recommendation - Strong historical match with similar campaigns';
        } else if (pred.confidence_score && pred.confidence_score > 0.6) {
            recommendation = 'Moderate confidence - Good historical indicators from similar campaigns';
        } else if (pred.confidence_score) {
            recommendation = 'Lower confidence - Limited historical data, consider additional factors';
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
        
        resultDiv.innerHTML = `
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
                // Smooth scroll to the field
                setTimeout(() => {
                    finalScheduleField.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }, 100);
            }
        }
        
        alert('AI recommendation accepted! Final schedule has been set.');
        loadCampaigns();
        if (calendar) calendar.refetchEvents();
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
        alert('Please select a campaign first');
        return;
    }
    
    const manualDateTime = prompt('Enter manual schedule date & time (YYYY-MM-DD HH:MM:SS):');
    if (!manualDateTime) return;
    
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
            alert('Error: ' + data.error);
            return;
        }
        
        alert('Manual schedule override successful!');
        loadCampaigns();
        if (calendar) calendar.refetchEvents();
    } catch (err) {
        alert('Failed to override schedule: ' + err.message);
    }
}

// Tabs
function switchTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    event.target.classList.add('active');
    if (tab === 'gantt') {
        document.getElementById('gantt-tab').classList.add('active');
        setTimeout(refreshGantt, 100);
    } else {
        document.getElementById('calendar-tab').classList.add('active');
        if (!calendar) initCalendar();
    }
}

// Gantt Chart
function refreshGantt() {
    const container = document.getElementById('gantt-container');
    container.innerHTML = '';
    
    if (!allCampaigns.length) {
        container.innerHTML = '<div style="text-align:center; padding:60px; color:#64748b;"><p style="font-size:16px; margin-bottom:8px;">📊 No campaigns to display</p><p style="font-size:14px;">Create a campaign first to see the timeline</p></div>';
        return;
    }
    
    const tasks = allCampaigns
        .filter(c => c.start_date && c.end_date)
        .map(c => {
            let progress = 0;
            if (c.status === 'completed') progress = 100;
            else if (c.status === 'ongoing') progress = 50;
            else if (c.status === 'approved') progress = 25;
            else if (c.status === 'pending') progress = 10;
            
            return {
                id: String(c.id),
                name: (c.title || 'Untitled') + ' [' + (c.status || 'draft').toUpperCase() + ']',
                start: c.start_date,
                end: c.end_date,
                progress: progress,
                custom_class: 'status-' + (c.status || 'draft'),
            };
        });
    
    if (!tasks.length) {
        container.innerHTML = '<div style="text-align:center; padding:60px; color:#64748b;"><p style="font-size:16px; margin-bottom:8px;">📅 No campaigns with dates</p><p style="font-size:14px;">Add start and end dates to campaigns to view them on the timeline</p></div>';
        return;
    }
    
    try {
        gantt = new Gantt('#gantt-container', tasks, {
            view_mode: 'Month',
            language: 'en',
            header_height: 50,
            column_width: 30,
            step: 24,
            bar_height: 30,
            bar_corner_radius: 6,
            arrow_curve: 5,
            padding: 18,
            date_format: 'YYYY-MM-DD',
            on_click: function (task) {
                const campaign = allCampaigns.find(c => String(c.id) === task.id);
                if (campaign) {
                    alert(`Campaign: ${campaign.title}\nStatus: ${campaign.status}\nStart: ${campaign.start_date}\nEnd: ${campaign.end_date}`);
                }
            },
            on_date_change: function(task, start, end) {
                console.log('Date changed:', task, start, end);
            },
            on_progress_change: function(task, progress) {
                console.log('Progress changed:', task, progress);
            },
            on_view_change: function(mode) {
                console.log('View changed:', mode);
            }
        });
    } catch (err) {
        console.error('Gantt chart error:', err);
        container.innerHTML = '<div style="text-align:center; padding:40px; color:#dc2626;"><p>Error loading Gantt chart. Please refresh the page.</p></div>';
    }
}

// Calendar
function initCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
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
                const res = await fetch(apiBase + `/api/v1/campaigns/calendar?start=${start}&end=${end}`, {
                    headers: { 'Authorization': 'Bearer ' + getToken() }
                });
                const data = await res.json();
                const campaigns = data.data || [];
                
                const events = [];
                
                // Add campaign date ranges
                campaigns.forEach(c => {
                    if (c.start_date) {
                        events.push({
                            id: 'campaign-' + c.id,
                            title: c.title + ' (Campaign)',
                            start: c.start_date,
                            end: c.end_date ? new Date(new Date(c.end_date).getTime() + 86400000) : new Date(new Date(c.start_date).getTime() + 86400000),
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
                        });
                    }
                    
                    // Add draft schedule
                    if (c.draft_schedule_datetime) {
                        events.push({
                            id: 'draft-' + c.id,
                            title: c.title + ' (Draft)',
                            start: c.draft_schedule_datetime,
                            backgroundColor: '#fbbf24',
                            borderColor: '#f59e0b',
                            textColor: '#000',
                            extendedProps: {
                                type: 'draft_schedule',
                                campaign_id: c.id
                            }
                        });
                    }
                    
                    // Add AI recommended schedule
                    if (c.ai_recommended_datetime) {
                        events.push({
                            id: 'ai-' + c.id,
                            title: c.title + ' (AI Recommended)',
                            start: c.ai_recommended_datetime,
                            backgroundColor: '#667eea',
                            borderColor: '#764ba2',
                            textColor: '#fff',
                            extendedProps: {
                                type: 'ai_recommended',
                                campaign_id: c.id
                            }
                        });
                    }
                    
                    // Add final approved schedule
                    if (c.final_schedule_datetime) {
                        events.push({
                            id: 'final-' + c.id,
                            title: c.title + ' (Final)',
                            start: c.final_schedule_datetime,
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            textColor: '#fff',
                            extendedProps: {
                                type: 'final_schedule',
                                campaign_id: c.id
                            }
                        });
                    }
                });
                
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
    calendar.render();
}

function calendarView(view) {
    if (calendar) {
        calendar.changeView(view);
        // Update active button state
        document.querySelectorAll('.btn-secondary').forEach(btn => {
            if (btn.textContent.toLowerCase().includes(view === 'dayGridMonth' ? 'month' : 'week')) {
                btn.style.background = 'linear-gradient(135deg, #4c8a89 0%, #667eea 100%)';
                btn.style.color = 'white';
            } else {
                btn.style.background = '#fff';
                btn.style.color = '#475569';
            }
        });
    }
}

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
        
        document.getElementById('totalBudget').textContent = '₱' + totalBudget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('totalStaff').textContent = totalStaff;
        document.getElementById('activeCampaigns').textContent = activeCount;
        
        // Update breakdowns
        const totalCampaigns = campaigns.length;
        document.getElementById('budgetBreakdown').textContent = `${totalCampaigns} campaign${totalCampaigns !== 1 ? 's' : ''}`;
        document.getElementById('staffBreakdown').textContent = `${totalCampaigns} campaign${totalCampaigns !== 1 ? 's' : ''} assigned`;
        document.getElementById('campaignBreakdown').textContent = `${totalCampaigns - activeCount} inactive`;
        
        const materialsEl = document.getElementById('materialsUsed');
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
    } catch (err) {
        console.error('Failed to load resources:', err);
    }
}

// Campaigns
    async function loadCampaigns() {
    const tbody = document.getElementById('campaignTable');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">Loading...</td></tr>';
    
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
        
        if (!allCampaigns.length) {
            tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:24px;">No campaigns yet.</td></tr>';
            return;
        }
        
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
                <td>${c.title || ''}</td>
                <td>${c.category || '-'}</td>
                <td><span class="badge ${c.status || 'draft'}">${(c.status || 'draft').charAt(0).toUpperCase() + (c.status || 'draft').slice(1)}</span></td>
                <td>${c.start_date || '-'}</td>
                <td>${c.end_date || '-'}</td>
                <td>${formatDateTime(c.draft_schedule_datetime)}</td>
                <td>${formatDateTime(c.ai_recommended_datetime)}</td>
                <td>${formatDateTime(c.final_schedule_datetime)}</td>
                <td>${c.location || '-'}</td>
                <td>${c.budget ? '₱' + parseFloat(c.budget).toLocaleString('en-US', {minimumFractionDigits: 2}) : '-'}</td>
                <td>
                    <button class="btn btn-secondary" onclick="editCampaign(${c.id})" style="padding: 4px 8px; font-size: 12px; margin-right: 4px;">✏️ Edit</button>
                    ${c.status !== 'archived' ? `<button class="btn btn-secondary" onclick="archiveCampaign(${c.id})" style="padding: 4px 8px; font-size: 12px;">📦 Archive</button>` : '<span style="color: #9ca3af; font-size: 12px;">Archived</span>'}
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
            select.value = activeCampaignId;
        }
        
        refreshGantt();
        loadResources();
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
            document.getElementById('status').value = c.status || 'draft';
            if (typeof document.getElementById('status').setSelectedValues === 'function') {
                document.getElementById('status').setSelectedValues(c.status);
            }
        }
        if (document.getElementById('start_date')) {
            document.getElementById('start_date').value = c.start_date || '';
        }
        if (document.getElementById('end_date')) {
            document.getElementById('end_date').value = c.end_date || '';
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
            if (Array.isArray(zones) && document.getElementById('barangay_zones')) {
                if (typeof document.getElementById('barangay_zones').setSelectedValues === 'function') {
                    document.getElementById('barangay_zones').setSelectedValues(zones);
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
        
        // Scroll to form
        document.getElementById('planning-section').scrollIntoView({ behavior: 'smooth' });
        
    } catch (err) {
        alert('Failed to load campaign: ' + err.message);
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
        
        const payload = {
            title: title,
            description: document.getElementById('description').value.trim(),
            category: category,
            geographic_scope: geographicScope,
            status: status,
            start_date: document.getElementById('start_date').value || null,
            end_date: document.getElementById('end_date').value || null,
            draft_schedule_datetime: document.getElementById('draft_schedule_datetime').value || null,
            objectives: document.getElementById('objectives').value.trim() || null,
            location: location,
            assigned_staff: assignedStaff,
            barangay_target_zones: barangayZones,
            budget: parseFloat(document.getElementById('budget').value) || null,
            staff_count: parseInt(document.getElementById('staff_count').value) || null,
            materials_json: materialsJson,
        };
        
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
                    window.location.href = basePath + '/index.php';
                }, 2000);
            } else {
                createStatusEl.textContent = data.error || 'Failed to update campaign.';
                createStatusEl.className = 'status-text error';
            }
            return;
        }
        
        createStatusEl.textContent = 'Campaign updated successfully!';
        createStatusEl.className = 'status-text success';
        clearForm();
        loadCampaigns();
        refreshGantt();
        if (calendar) calendar.refetchEvents();
        
    } catch (err) {
        createStatusEl.textContent = 'Error: ' + err.message;
        createStatusEl.className = 'status-text error';
    }
}

// Archive Campaign
async function archiveCampaign(campaignId) {
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
            alert('Error: ' + (data.error || 'Failed to archive campaign'));
            return;
        }
        
        alert('Campaign archived successfully!');
        loadCampaigns();
        refreshGantt();
        if (calendar) calendar.refetchEvents();
        
    } catch (err) {
        alert('Failed to archive campaign: ' + err.message);
    }
}

// Segments
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
        
        setTimeout(() => {
            if (document.getElementById('gantt-tab') && document.getElementById('gantt-tab').classList.contains('active')) {
                refreshGantt();
            }
        }, 500);
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
                        <li><strong>Project Timeline (Gantt Chart)</strong> - Visual timeline of all campaigns and their schedules</li>
                        <li><strong>Scheduling Calendar</strong> - Calendar view to see campaign schedules by month/week</li>
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
    content.innerHTML = tips + '<button onclick="this.closest(\'div[style*=\\\'position: fixed\\\']\').remove()" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>';
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
        
        // Make functions globally accessible after DOM loads
        window.handleGetPredictionClick = handleGetPredictionClick;
        window.getAutoMLPrediction = getAutoMLPrediction;
        window.refreshAutoMLCampaigns = refreshAutoMLCampaigns;
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
    
    initializeCampaigns();
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
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main> <!-- /.main-content-wrapper -->
