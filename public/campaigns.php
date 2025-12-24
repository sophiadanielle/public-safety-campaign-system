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
        overflow: hidden;
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
    .form-field textarea,
    .form-field select {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
    }
    .form-field input:focus,
    .form-field textarea:focus,
    .form-field select:focus {
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
        overflow: hidden;
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
    
    /* Combobox Styles (Select-like with autocomplete) */
    .combobox-wrapper {
        position: relative;
        width: 100%;
    }
    .combobox-input {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 40px 12px 16px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
    }
    .combobox-input:focus {
        outline: none;
        border-color: #4c8a89;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
    }
    .combobox-arrow {
        position: absolute;
        right: 16px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
        font-size: 12px;
    }
    .combobox-options {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        width: 100%;
        background: #fff;
        border: 2px solid #4c8a89;
        border-top: none;
        border-radius: 0 0 12px 12px;
        max-height: 160px; /* default dropdown height for all comboboxes */
        overflow-y: auto;
        overscroll-behavior: contain;
        box-sizing: border-box;
        z-index: 1000;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
        display: none;
        margin-top: -2px;
    }
    .combobox-options.active {
        display: block;
    }
    .combobox-option {
        padding: 12px 16px;
        cursor: pointer;
        border-bottom: 1px solid #f1f5f9;
        transition: all 0.2s;
        color: #1e293b;
        font-size: 14px;
    }
    .combobox-option:hover,
    .combobox-option.selected {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        color: #0f172a;
        font-weight: 600;
    }
    .combobox-option:last-child {
        border-bottom: none;
    }
    .combobox-input.active {
        border-bottom-left-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-color: #4c8a89;
    }
    .combobox-input[readonly] {
        cursor: pointer;
        background: #fafbfc;
    }
    /* For multi-select comboboxes */
    .combobox-multi .combobox-input {
        padding-right: 16px;
    }
    .combobox-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin-top: 4px;
        width: 100%;
    }
    .combobox-tag {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #0c4a6e;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        flex: 0 0 auto;
        max-width: calc(25% - 3px); /* Allow 4 items per row */
        min-width: calc(25% - 3px);
    }
    .combobox-tag-remove {
        cursor: pointer;
        font-weight: bold;
    }

    /* Show 4 items per row for Assigned Staff tags */
    .combobox-assigned .combobox-tags {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .combobox-assigned .combobox-tag {
        flex: 0 0 calc(25% - 3px); /* 4 items per row: (100% - 12px) / 4 */
        max-width: calc(25% - 3px);
        min-width: 0; /* Allow shrinking if text is too long */
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Show 2 items per row for Materials tags */
    .combobox-materials .combobox-tags {
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
    }
    
    .combobox-materials .combobox-tag {
        flex: 0 0 calc(50% - 2px); /* 2 items per row: (100% - 4px) / 2 */
        max-width: calc(50% - 2px);
        min-width: 0; /* Allow shrinking if text is too long */
        box-sizing: border-box;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Keep dropdown height reasonable */
    .combobox-assigned .combobox-options,
    .combobox-materials .combobox-options {
        max-height: 80px; /* Show only 2 items to prevent overlap with buttons */
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
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <h2 class="section-title analytics-accent" style="margin: 0;">Plan New Campaign</h2>
            <button type="button" class="btn btn-secondary" onclick="showCampaignHowItWorks()" style="padding: 8px 16px; font-size: 13px;">
                💡 How It Works
            </button>
        </div>
        
        <form id="planningForm">
            <div class="form-grid">
                <div class="form-field">
                    <label for="title">Campaign Title *</label>
                    <div class="combobox-wrapper">
                        <input type="text" class="combobox-input" id="title" placeholder="Select or type campaign title..." required autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="title_options"></div>
                    </div>
                </div>
                <div class="form-field">
                    <label for="category">Category *</label>
                    <div class="combobox-wrapper">
                        <input
                            type="text"
                            class="combobox-input"
                            id="category"
                            placeholder="Select or type category..."
                            autocomplete="off"
                            required
                        >
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="category_options"></div>
                    </div>
                </div>
                <div class="form-field">
                    <label for="geographic_scope">Geographic Scope / Barangay</label>
                    <div class="combobox-wrapper">
                        <input type="text" class="combobox-input" id="geographic_scope" placeholder="Select or type barangay..." autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="geographic_scope_options"></div>
                    </div>
                </div>
                <div class="form-field">
                    <label for="status">Status</label>
                    <div class="combobox-wrapper">
                        <input
                            type="text"
                            class="combobox-input"
                            id="status"
                            placeholder="Select or type status..."
                            autocomplete="off"
                        >
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="status_options"></div>
                    </div>
                </div>
                <div class="form-field">
                    <label for="start_date">Start Date</label>
                    <input id="start_date" type="date">
                </div>
                <div class="form-field">
                    <label for="end_date">End Date</label>
                    <input id="end_date" type="date">
                </div>
                <div class="form-field">
                    <label for="draft_schedule_datetime">Draft Schedule (Date & Time)</label>
                    <input id="draft_schedule_datetime" type="datetime-local" disabled title="Schedule will be set after requesting AI recommendation">
                    <small style="display: block; margin-top: 4px; color: #64748b; font-size: 12px;">Schedule must be set via AI recommendation flow (Steps 3-9)</small>
                </div>
                <div class="form-field">
                    <label for="location">Location</label>
                    <div class="combobox-wrapper">
                        <input type="text" class="combobox-input" id="location" placeholder="Select or type location..." autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="location_options"></div>
                    </div>
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
                    <div class="combobox-wrapper combobox-multi">
                        <input type="text" class="combobox-input" id="barangay_zones" placeholder="Select or type barangays..." autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="barangay_zones_options"></div>
                        <div class="combobox-tags" id="barangay_zones_tags"></div>
                    </div>
                </div>
                <div class="form-field full-width">
                    <label for="objectives">Objectives</label>
                    <textarea id="objectives" rows="3" placeholder="Primary objectives and goals for this campaign..."></textarea>
                </div>
                <div class="form-field full-width">
                    <label for="description">Description</label>
                    <textarea id="description" rows="3" placeholder="Detailed description of the campaign..."></textarea>
                </div>
                <div class="form-field full-width">
                    <label for="assigned_staff">Assigned Staff</label>
                    <div class="combobox-wrapper combobox-multi combobox-assigned">
                        <input type="text" class="combobox-input" id="assigned_staff" placeholder="Select or type staff names..." autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="assigned_staff_options"></div>
                        <div class="combobox-tags" id="assigned_staff_tags"></div>
                    </div>
                    <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Select multiple staff members</small>
                </div>
                <div class="form-field full-width">
                    <label for="materials_json">Materials</label>
                    <div class="combobox-wrapper combobox-multi combobox-materials">
                        <input type="text" class="combobox-input" id="materials_json" placeholder="Select or type materials..." autocomplete="off">
                        <span class="combobox-arrow">▼</span>
                        <div class="combobox-options" id="materials_json_options"></div>
                        <div class="combobox-tags" id="materials_json_tags"></div>
                    </div>
                    <small style="color: #64748b; font-size: 12px; margin-top: 4px; display: block;">Select multiple materials from Content Repository</small>
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
            <h2 class="section-title analytics-accent">AI-Powered Deployment Optimization</h2>
        </div>
        <div class="automl-panel">
            <h3>Google AutoML Predictions</h3>
            <p style="margin: 0 0 20px; opacity: 0.95;">Get AI-suggested optimal dates and times for campaign deployment based on real-time historical data, audience engagement patterns, and performance analytics.</p>
            <div class="form-grid" style="grid-template-columns: 1fr 1fr auto; gap: 16px;">
                <div class="form-field">
                    <label for="automl_campaign_id" style="color: white; opacity: 0.95;">Campaign ID</label>
                    <select id="automl_campaign_id" style="background: rgba(255,255,255,0.95); border: 2px solid rgba(255,255,255,0.3); color: #0f172a;">
                        <option value="">Select Campaign</option>
                    </select>
                </div>
                <div class="form-field">
                    <label for="automl_audience_segment" style="color: white; opacity: 0.95;">Audience Segment ID (Optional)</label>
                    <input id="automl_audience_segment" type="number" placeholder="e.g., 1" style="background: rgba(255,255,255,0.95); border: 2px solid rgba(255,255,255,0.3); color: #0f172a;">
                </div>
                <div class="form-field" style="justify-content: flex-end; align-items: flex-end;">
                    <label style="color: white; opacity: 0; height: 0;">Action</label>
                    <button type="button" class="btn btn-primary" onclick="getAutoMLPrediction()" style="background: white; color: #667eea; border: none; font-weight: 700; padding: 12px 24px;">🔮 Get Prediction</button>
                </div>
            </div>
            <div id="automlResult" class="prediction-result" style="display:none;">
                <div class="prediction-item">
                    <strong>📅 Suggested Date & Time:</strong>
                    <span id="pred_datetime">-</span>
                </div>
                <div class="prediction-item">
                    <strong>📊 Confidence Score:</strong>
                    <span id="pred_confidence">-</span>
                </div>
                <div class="prediction-item">
                    <strong>🔍 Model Source:</strong>
                    <span id="pred_source">-</span>
                </div>
                <div class="prediction-item">
                    <strong>💡 Recommendation:</strong>
                    <span id="pred_recommendation" style="font-size: 13px;">Based on historical performance data</span>
                </div>
                <div style="margin-top: 16px; display: flex; gap: 12px;">
                    <button type="button" class="btn btn-primary" onclick="acceptAIRecommendation()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">✓ Accept AI Recommendation</button>
                    <button type="button" class="btn btn-secondary" onclick="checkConflicts()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">🔍 Check Conflicts</button>
                    <button type="button" class="btn btn-secondary" onclick="overrideSchedule()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">✏️ Override Schedule</button>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline & Calendar Tabs -->
    <section class="card" id="timeline-section">
        <div class="tabs">
            <button class="tab active" onclick="switchTab('gantt')">📊 Project Timeline</button>
            <button class="tab" onclick="switchTab('calendar')">📅 Scheduling Calendar</button>
        </div>
        
        <div id="gantt-tab" class="tab-content active">
            <div class="section-header">
                <h3 class="section-title analytics-accent">Gantt Chart</h3>
                <button class="btn btn-secondary" onclick="refreshGantt()">🔄 Refresh</button>
            </div>
            <div id="gantt-container"></div>
        </div>
        
        <div id="calendar-tab" class="tab-content">
            <div class="section-header">
                <h3 class="section-title analytics-accent">Calendar View</h3>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-secondary" onclick="calendarView('dayGridMonth')">📆 Month</button>
                    <button class="btn btn-secondary" onclick="calendarView('timeGridWeek')">📅 Week</button>
                </div>
            </div>
            <div id="calendar"></div>
        </div>
    </section>

    <!-- Resource Allocation -->
    <section class="card" id="resources-section">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Resource Allocation Microservices</h2>
            <button class="btn btn-secondary" onclick="loadResources()">🔄 Refresh</button>
        </div>
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
        <div class="section-header">
            <h2 class="section-title analytics-accent">All Campaigns</h2>
            <button class="btn btn-secondary" onclick="loadCampaigns()">🔄 Refresh</button>
        </div>
        <div class="form-field" style="max-width: 300px; margin-bottom: 16px;">
            <label for="active_campaign">Active Campaign</label>
            <select id="active_campaign" onchange="onCampaignChange()"></select>
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
            <button class="btn btn-secondary" onclick="loadSegments()">🔄 Refresh</button>
        </div>
        <div class="form-grid">
            <div class="form-field">
                <label for="segment_ids">Segment IDs (comma-separated)</label>
                <input id="segment_ids" type="text" placeholder="1,2,5">
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="saveSegments()">Save Segments</button>
        </div>
        <div id="segmentStatus" class="status-text" style="display:none;"></div>
        <table class="data-table" style="margin-top:16px;">
            <thead><tr><th>ID</th><th>Name</th><th>Criteria</th></tr></thead>
            <tbody id="segmentTable"><tr><td colspan="3" style="text-align:center; padding:16px;">No segments loaded.</td></tr></tbody>
        </table>
    </section>

    <!-- Linked Content -->
    <section class="card" id="content-section">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Linked Content</h2>
            <button class="btn btn-secondary" onclick="loadCampaignContent()">🔄 Refresh</button>
        </div>
        <div class="form-field" style="max-width: 300px; margin-bottom: 16px;">
            <label for="content_campaign_id">Campaign ID</label>
            <input id="content_campaign_id" type="number" placeholder="Enter campaign ID" onchange="loadCampaignContent()">
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Content Type</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody id="contentTable">
                <tr><td colspan="4" style="text-align:center; padding:24px;">Enter a Campaign ID to view linked content</td></tr>
            </tbody>
        </table>
    </section>

        </div> <!-- /.campaign-main -->
    </div> <!-- /.campaign-layout -->
        </div> <!-- /.campaign-page -->
    </main> <!-- /.main-content-wrapper -->

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
        if (!token || token.trim() === '') {
            console.warn('getToken() - No token found in localStorage');
            console.warn('getToken() - localStorage keys:', Object.keys(localStorage));
            return '';
        }
        return token.trim();
    } catch (e) {
        console.error('Error reading localStorage:', e);
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

// Reusable Combobox Component (Select-like with autocomplete or local samples)
function initCombobox(inputId, optionsId, apiEndpoint, options = {}) {
    const input = document.getElementById(inputId);
    const optionsDiv = document.getElementById(optionsId);
    const wrapper = input?.closest('.combobox-wrapper');
    const tagsDiv = wrapper?.querySelector('.combobox-tags');
    
    if (!input || !optionsDiv) {
        console.warn('Combobox: Element not found', inputId, optionsId);
        return;
    }
    
    // Mark as initialized
    input.dataset.comboboxInit = 'true';
    console.log('Combobox initialized for:', inputId);

    const isMultiSelect = options.multiSelect || false;
    const staticOptions = Array.isArray(options.staticOptions) ? options.staticOptions : null;
    let selectedIndex = -1;
    let suggestions = [];
    let selectedValues = isMultiSelect ? [] : null;
    let debounceTimer = null;
    const minChars = options.minChars || 0; // Allow empty query to show all options
    const delay = options.delay || 300;

    // Show dropdown on focus/click
    input.addEventListener('focus', function() {
        // For static option lists, always show the full list on focus
        if (staticOptions && staticOptions.length) {
            fetchSuggestions('');
            return;
        }

        const query = this.value.trim();
        if (query.length >= minChars) {
            fetchSuggestions(query);
        } else {
            // Show all options if empty or short
            fetchSuggestions('');
        }
    });

    // Handle input
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timer
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        // If query is too short, show all options
        if (query.length < minChars) {
            fetchSuggestions('');
            return;
        }

        // Debounce API calls / filtering
        debounceTimer = setTimeout(() => {
            fetchSuggestions(query);
        }, delay);
    });

    // Handle keyboard navigation
    input.addEventListener('keydown', function(e) {
        if (!optionsDiv.classList.contains('active')) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                // For static option lists, open with full list when first activated
                if (staticOptions && staticOptions.length) {
                    fetchSuggestions('');
                } else {
                    fetchSuggestions(this.value.trim());
                }
            }
            return;
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
            updateSelected();
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelected();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                selectOption(suggestions[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            hideOptions();
        }
    });

    // Handle blur (hide options after a delay to allow clicks)
    input.addEventListener('blur', function() {
        setTimeout(() => {
            hideOptions();
        }, 200);
    });

    // Handle click on arrow to toggle dropdown
    const arrow = wrapper?.querySelector('.combobox-arrow');
    if (arrow) {
        arrow.addEventListener('click', function(e) {
            e.preventDefault();
            input.focus();
            // For static lists, always show full options when arrow is clicked
            if (staticOptions && staticOptions.length) {
                fetchSuggestions('');
                return;
            }

            const query = input.value.trim();
            if (query.length >= minChars) {
                fetchSuggestions(query);
            } else {
                fetchSuggestions('');
            }
        });
    }

    // Fetch suggestions from API or static options
    async function fetchSuggestions(query) {
        // If static options are provided, always show the full list and skip API calls
        if (staticOptions && staticOptions.length) {
            suggestions = staticOptions.slice(); // full list, no filtering
            displayOptions();
            return;
        }

        try {
            // Build API URL using apiBase (which includes /index.php)
            const url = apiBase + apiEndpoint + (query ? '?q=' + encodeURIComponent(query) : '?q=');
            const res = await fetch(url, {
                headers: { 
                    'Authorization': 'Bearer ' + getToken(),
                    'Content-Type': 'application/json'
                }
            });
            
            if (!res.ok) {
                console.error('Combobox API error:', res.status, res.statusText);
                hideOptions();
                return;
            }
            
            const data = await res.json();
            suggestions = data.data || [];
            displayOptions();
        } catch (err) {
            console.error('Combobox error:', err);
            hideOptions();
        }
    }

    // Display options
    function displayOptions() {
        if (suggestions.length === 0) {
            optionsDiv.innerHTML = '<div class="combobox-option" style="color: #94a3b8; font-style: italic;">No suggestions found</div>';
        } else {
            optionsDiv.innerHTML = '';
        }
        
        selectedIndex = -1;
        input.classList.add('active');

        suggestions.forEach((suggestion, index) => {
            const div = document.createElement('div');
            div.className = 'combobox-option';
            div.textContent = suggestion;
            div.addEventListener('click', () => selectOption(suggestion));
            optionsDiv.appendChild(div);
        });

        optionsDiv.classList.add('active');
    }

    // Update selected option highlight
    function updateSelected() {
        const items = optionsDiv.querySelectorAll('.combobox-option');
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('selected');
            }
        });
    }

    // Select an option
    function selectOption(value) {
        if (isMultiSelect) {
            // Multi-select: add to selected values and show as tags
            if (!selectedValues.includes(value)) {
                selectedValues.push(value);
                updateTags();
            }
            input.value = '';
        } else {
            // Single select: set value
            input.value = value;
            selectedValues = value;
        }
        
        hideOptions();
        input.focus();
        
        // Trigger input/change event for any handlers
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Update tags display for multi-select
    function updateTags() {
        if (!tagsDiv || !isMultiSelect) return;
        
        tagsDiv.innerHTML = '';
        selectedValues.forEach(value => {
            const tag = document.createElement('div');
            tag.className = 'combobox-tag';
            tag.innerHTML = `
                <span>${value}</span>
                <span class="combobox-tag-remove" data-value="${value}">×</span>
            `;
            tag.querySelector('.combobox-tag-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                removeTag(value);
            });
            tagsDiv.appendChild(tag);
        });
    }

    // Remove a tag
    function removeTag(value) {
        selectedValues = selectedValues.filter(v => v !== value);
        updateTags();
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    // Hide options
    function hideOptions() {
        optionsDiv.classList.remove('active');
        input.classList.remove('active');
        selectedIndex = -1;
    }

    // Expose selectedValues for form submission
    input.getSelectedValues = () => isMultiSelect ? selectedValues : input.value;
}

// Initialize all combobox fields when DOM is ready
(function() {
    function initAllComboboxes() {
        // Campaign Title
        if (document.getElementById('title')) {
            initCombobox('title', 'title_options', '/api/v1/autocomplete/campaign-titles', {
                staticOptions: SAMPLE_CAMPAIGN_TITLES,
            });
        }
        
        // Category (single-select combobox)
        if (document.getElementById('category')) {
            initCombobox('category', 'category_options', '/api/v1/autocomplete/campaign-titles', {
                staticOptions: ['fire', 'flood', 'earthquake', 'health', 'road safety', 'general'],
            });
        }
        
        // Barangay / Geographic Scope (single select)
        if (document.getElementById('geographic_scope')) {
            initCombobox('geographic_scope', 'geographic_scope_options', '/api/v1/autocomplete/barangays', {
                staticOptions: SAMPLE_BARANGAYS,
            });
        }
        
        // Barangay Target Zones (multi-select with real Quezon City sub-areas)
        if (document.getElementById('barangay_zones')) {
            initCombobox('barangay_zones', 'barangay_zones_options', '/api/v1/autocomplete/barangays', {
                multiSelect: true,
                staticOptions: SAMPLE_BARANGAY_ZONES,
            });
        }
        
        // Location
        if (document.getElementById('location')) {
            initCombobox('location', 'location_options', '/api/v1/autocomplete/locations', {
                staticOptions: SAMPLE_LOCATIONS,
            });
        }
        
        // Status (single-select combobox)
        if (document.getElementById('status')) {
            initCombobox('status', 'status_options', '/api/v1/autocomplete/campaign-titles', {
                staticOptions: ['draft','pending','approved','ongoing','scheduled','completed','archived'],
            });
        }
        
        // Assigned Staff
        if (document.getElementById('assigned_staff')) {
            initCombobox('assigned_staff', 'assigned_staff_options', '/api/v1/autocomplete/staff', {
                multiSelect: true,
                staticOptions: SAMPLE_STAFF,
            });
        }
        
        // Materials
        if (document.getElementById('materials_json')) {
            initCombobox('materials_json', 'materials_json_options', '/api/v1/autocomplete/materials', {
                multiSelect: true,
                staticOptions: SAMPLE_MATERIALS,
            });
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAllComboboxes);
    } else {
        // DOM already loaded, initialize immediately
        setTimeout(initAllComboboxes, 100);
    }
})();

// Also initialize on window load as a fallback
window.addEventListener('load', function() {
    // Double-check combobox is initialized
    if (document.getElementById('title') && !document.getElementById('title').dataset.comboboxInit) {
        console.log('Re-initializing comboboxes on window load...');
        // Re-run initialization
        if (typeof initCombobox === 'function') {
            initCombobox('title', 'title_options', '/api/v1/autocomplete/campaign-titles');
            initCombobox('geographic_scope', 'geographic_scope_options', '/api/v1/autocomplete/barangays');
            initCombobox('barangay_zones', 'barangay_zones_options', '/api/v1/autocomplete/barangays', { multiSelect: true });
            initCombobox('location', 'location_options', '/api/v1/autocomplete/locations');
            initCombobox('assigned_staff', 'assigned_staff_options', '/api/v1/autocomplete/staff', { multiSelect: true });
            initCombobox('materials_json', 'materials_json_options', '/api/v1/autocomplete/materials', { multiSelect: true });
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
        console.log('Campaign creation - Making API call with token (length:', token ? token.length : 0 + ')');
        console.log('Campaign creation - API URL:', apiBase + '/api/v1/campaigns');
        
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token.trim()
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
    
    // Clear form fields
    document.getElementById('planningForm').reset();
    document.getElementById('createStatus').style.display = 'none';
}

// AutoML
let currentPrediction = null;
let currentCampaignId = null;

async function getAutoMLPrediction() {
    const cid = parseInt(document.getElementById('automl_campaign_id').value);
    if (!cid) {
        alert('Please select a campaign');
        return;
    }
    
    currentCampaignId = cid;
    const resultDiv = document.getElementById('automlResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div style="text-align:center; padding:20px;">Loading prediction from real-time data...</div>';
    
    try {
        const audienceSegmentId = document.getElementById('automl_audience_segment').value;
        const features = {};
        if (audienceSegmentId) {
            features.audience_segment_id = parseInt(audienceSegmentId);
        }
        
        const res = await fetch(apiBase + `/api/v1/campaigns/${cid}/ai-recommendation`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify({ features })
        });
        
        console.log('getAutoMLPrediction() - Response status:', res.status);
        
        if (!res.ok) {
            const errorText = await res.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { error: errorText || `HTTP ${res.status}` };
            }
            console.error('getAutoMLPrediction() - API error:', res.status, errorData);
            resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5;">
                <strong>Error:</strong>
                <span>${errorData.error || `Failed to get prediction (${res.status})`}</span>
            </div>`;
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
        
        let recommendation = 'Optimal deployment time based on real-time historical performance data';
        if (pred.confidence_score && pred.confidence_score > 0.8) {
            recommendation = 'High confidence recommendation - Strong historical match with similar campaigns';
        } else if (pred.confidence_score && pred.confidence_score > 0.6) {
            recommendation = 'Moderate confidence - Good historical indicators from similar campaigns';
        } else if (pred.confidence_score) {
            recommendation = 'Lower confidence - Limited historical data, consider additional factors';
        }
        
        resultDiv.innerHTML = `
            <div class="prediction-item">
                <strong>📅 Suggested Date & Time:</strong>
                <span>${suggestedDateTime}</span>
            </div>
            <div class="prediction-item">
                <strong>📊 Confidence Score:</strong>
                <span>${confidence}</span>
            </div>
            <div class="prediction-item">
                <strong>🔍 Model Source:</strong>
                <span>${modelSource === 'google_automl' ? 'Google AutoML' : modelSource === 'heuristic_with_history' ? 'Heuristic (with historical data)' : 'Heuristic (fallback)'}</span>
            </div>
            <div class="prediction-item">
                <strong>💡 Recommendation:</strong>
                <span style="font-size: 13px;">${recommendation}</span>
            </div>
            <div style="margin-top: 16px; display: flex; gap: 12px;">
                <button type="button" class="btn btn-primary" onclick="acceptAIRecommendation()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">✓ Accept AI Recommendation</button>
                <button type="button" class="btn btn-secondary" onclick="checkConflicts()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">🔍 Check Conflicts</button>
                <button type="button" class="btn btn-secondary" onclick="overrideSchedule()" style="background: rgba(255,255,255,0.2); color: white; border: 2px solid rgba(255,255,255,0.5);">✏️ Override Schedule</button>
            </div>
        `;
    } catch (err) {
        resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5;">
            <strong>Error:</strong>
            <span>Failed to get prediction: ${err.message}</span>
        </div>`;
    }
}

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
        
        if (!res.ok) {
            const errorText = await res.text();
            console.error('loadCampaigns() - API error:', res.status, errorText);
            tbody.innerHTML = `<tr><td colspan="12" style="text-align:center; padding:24px; color: #dc2626;">Failed to load campaigns: ${res.status} ${errorText}</td></tr>`;
            return;
        }
        
        const data = await res.json();
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
        select.innerHTML = '';
        automlSelect.innerHTML = '<option value="">Select Campaign</option>';
        
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
            
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.id} - ${c.title || ''}`;
            select.appendChild(opt);
            
            const automlOpt = document.createElement('option');
            automlOpt.value = c.id;
            automlOpt.textContent = `${c.id} - ${c.title || ''}`;
            automlSelect.appendChild(automlOpt);
        });
        
        if (!activeCampaignId && allCampaigns.length) {
            activeCampaignId = allCampaigns[0].id;
            select.value = activeCampaignId;
        }
        
        refreshGantt();
        loadResources();
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:24px; color:#dc2626;">Failed to load campaigns.</td></tr>';
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
    await loadCampaigns();
    loadResources();
    
    // Populate campaign dropdown for AI recommendations
    const automlSelect = document.getElementById('automl_campaign_id');
    if (allCampaigns.length > 0) {
        allCampaigns.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.id} - ${c.title || 'Untitled'}`;
            automlSelect.appendChild(opt);
        });
    }
    
    setTimeout(() => {
        if (document.getElementById('gantt-tab').classList.contains('active')) {
            refreshGantt();
        }
    }, 500);
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
                        <li><strong>AI Processing</strong> - System sends request to AI scheduling microservice</li>
                        <li><strong>Google AutoML Analysis</strong> - Microservice calls Google AutoML for prediction</li>
                        <li><strong>AI Recommendation</strong> - Google AutoML returns optimal date/time prediction</li>
                        <li><strong>Display Recommendation</strong> - AI suggestion shown with confidence score</li>
                        <li><strong>Review & Confirm</strong> - User reviews the AI recommendation</li>
                        <li><strong>Accept Schedule</strong> - User confirms and accepts the recommended schedule</li>
                        <li><strong>Campaign Saved</strong> - Campaign is saved with final schedule and appears in All Campaigns table</li>
                    </ol>
                </div>
                
                <div style="margin-bottom: 24px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">📋 Main Sections Explained:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Plan New Campaign</strong> - Create campaigns with all details (title, category, dates, location, budget, staff, materials)</li>
                        <li><strong>AI-Powered Deployment Optimization</strong> - Get AI recommendations for optimal posting times using Google AutoML</li>
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
                    <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🔗 Connection to Content Module:</strong>
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
                            <li>Click "🔮 Get Prediction" button</li>
                            <li>Wait for AI analysis (Google AutoML or heuristic fallback)</li>
                            <li>Review the recommended date/time and confidence score</li>
                            <li>Click "✓ Accept AI Recommendation" to confirm</li>
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
                        <li>Draft Schedule field is disabled - schedule must be set via AI recommendation flow</li>
                        <li>AI recommendation is required before campaign can be finalized</li>
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

initializeCampaigns();
</script>
</body>
</html>
