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
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/module-sidebar.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css" rel="stylesheet">
    <script>
        // Force light theme
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="has-module-sidebar" data-module="campaigns">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <?php
    // Include module sidebar for campaigns
    $moduleName = 'campaigns';
    include __DIR__ . '/../sidebar/includes/module-sidebar.php';
    ?>
    
    <!-- Main Content Wrapper - accounts for sidebar (280px), module sidebar (260px), and header (70px) -->
    <main class="main-content-wrapper">
        <div class="campaign-page">
<style>
    /* Main content wrapper - accounts for fixed sidebar, module sidebar, and header */
    .main-content-wrapper {
        margin-left: 540px; /* 280px main sidebar + 260px module sidebar */
        margin-top: 70px;
        min-height: calc(100vh - 70px);
        transition: margin-left 0.3s ease;
    }
    
    /* When module sidebar is hidden (on other pages) */
    body:not(.has-module-sidebar) .main-content-wrapper {
        margin-left: 280px;
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
            margin-left: 280px !important; /* Only main sidebar on tablet */
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
    
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 20px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
    }
    .data-table thead {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    }
    .data-table th {
        padding: 16px;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #e2e8f0;
    }
    .data-table td {
        padding: 16px;
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
    }
    .combobox-tag-remove {
        cursor: pointer;
        font-weight: bold;
    }

    /* Show only ~2 items at a time for Assigned Staff and Materials; others use default height */
    .combobox-assigned .combobox-options,
    .combobox-materials .combobox-options {
        max-height: 80px;
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
    }
</style>

<main class="campaign-page">
    <header>
        <h1 class="page-title">Campaign Planning & Management</h1>
        <p class="page-subtitle">Plan, schedule, and track campaigns with timeline visualization, calendar views, and AI-powered optimization.</p>
    </header>

    <div class="campaign-layout">
        <!-- Module sidebar is now handled by module-sidebar.php component -->
        <div class="campaign-main">

    <!-- Planning Form -->
    <section class="card" id="planning-section">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Plan New Campaign</h2>
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
                    <input id="draft_schedule_datetime" type="datetime-local">
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
                </tr>
            </thead>
            <tbody id="campaignTable">
                <tr><td colspan="11" style="text-align:center; padding:24px;">Loading...</td></tr>
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

        </div> <!-- /.campaign-main -->
    </div> <!-- /.campaign-layout -->
        </div> <!-- /.campaign-page -->
    </main> <!-- /.main-content-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
// Get base path for API calls
<?php
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';

const token = localStorage.getItem('jwtToken') || '';
let calendar, gantt;
let activeCampaignId = null;
let allCampaigns = [];

// Require authentication for campaign module. If no token, send user to login.
if (!token) {
    window.location.href = basePath + '/public/index.php';
}

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

const SAMPLE_BARANGAYS = [
    'Barangay 1',
    'Barangay 2',
    'Barangay 3',
    'Barangay 4',
    'Barangay 5',
    'Barangay Commonwealth',
    'Barangay Batasan Hills',
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
                    'Authorization': 'Bearer ' + token,
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
        
        // Barangay Target Zones
        if (document.getElementById('barangay_zones')) {
            initCombobox('barangay_zones', 'barangay_zones_options', '/api/v1/autocomplete/barangays', {
                multiSelect: true,
                staticOptions: SAMPLE_BARANGAYS,
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
        
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (!res.ok) {
            createStatusEl.textContent = data.error || 'Failed to create campaign.';
            createStatusEl.className = 'status-text error';
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
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ features })
        });
        const data = await res.json();
        
        if (data.error) {
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
                'Authorization': 'Bearer ' + token
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
                'Authorization': 'Bearer ' + token
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
                'Authorization': 'Bearer ' + token
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
                    headers: { 'Authorization': 'Bearer ' + token }
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
            headers: { 'Authorization': 'Bearer ' + token }
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
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        allCampaigns = data.data || [];
        
        if (!allCampaigns.length) {
            tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding:24px;">No campaigns yet.</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding:24px; color:#dc2626;">Failed to load campaigns.</td></tr>';
    }
}

function onCampaignChange() {
    activeCampaignId = parseInt(document.getElementById('active_campaign').value);
    loadSegments();
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
            headers: { 'Authorization': 'Bearer ' + token }
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
                'Authorization': 'Bearer ' + token
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

initializeCampaigns();
</script>
</body>
</html>
