<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../header/includes/path_helper.php';
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
            const urlParams = new URLSearchParams(window.location.search);
            const justLoggedIn = urlParams.has('logged_in') || urlParams.has('signed_up') || urlParams.has('google_login');
            
            // Handle Google login token from URL
            if (urlParams.has('google_login') && urlParams.has('token')) {
                const token = urlParams.get('token');
                if (token && token.trim() !== '') {
                    try {
                        localStorage.setItem('jwtToken', token);
                        // Clean URL
                        const cleanUrl = window.location.pathname;
                        window.history.replaceState({}, '', cleanUrl);
                    } catch (e) {
                        console.error('Failed to store Google login token:', e);
                    }
                }
            }
            
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="module-dashboard" data-module="dashboard">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <main class="main-content-wrapper">
<style>
    .main-content-wrapper {
        margin-left: 280px;
        margin-top: 70px;
        min-height: calc(100vh - 70px);
        transition: margin-left 0.3s ease;
    }
    
    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0 !important;
        }
    }
    
    .dashboard-page {
        max-width: 1600px;
        margin: 0 auto;
        padding: 24px;
    }
    
    .dashboard-section {
        scroll-margin-top: 90px; /* Account for fixed header */
    }
    
    .dashboard-grid .dashboard-section {
        margin-bottom: 0; /* Remove margin for sections inside grid */
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
    
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    
    @media (max-width: 992px) {
        .kpi-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 576px) {
        .kpi-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .kpi-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: all 0.2s;
    }
    
    .kpi-card:hover {
        border-color: #4c8a89;
        box-shadow: 0 4px 12px rgba(76, 138, 137, 0.1);
        transform: translateY(-2px);
    }
    
    .kpi-value {
        font-size: 32px;
        font-weight: 700;
        color: #4c8a89;
        margin: 8px 0;
    }
    
    .kpi-label {
        font-size: 14px;
        color: #64748b;
        font-weight: 600;
    }
    
    .kpi-icon {
        font-size: 24px;
        color: #4c8a89;
        margin-bottom: 8px;
    }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .dashboard-grid .dashboard-section {
        margin-bottom: 0; /* Remove margin for sections inside grid */
    }
    
    .dashboard-grid .dashboard-card {
        height: 100%;
    }
    
    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .dashboard-card {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 24px;
    }
    
    .dashboard-card h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .status-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .status-list li {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .status-list li:last-child {
        border-bottom: none;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-draft { background: #f1f5f9; color: #475569; }
    .badge-scheduled { background: #dbeafe; color: #1e40af; }
    .badge-active { background: #dcfce7; color: #166534; }
    .badge-completed { background: #e0e7ff; color: #4338ca; }
    
    .alert-panel {
        background: #fff7ed;
        border: 2px solid #fb923c;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 16px;
    }
    
    .alert-panel.info {
        background: #eff6ff;
        border-color: #3b82f6;
    }
    
    .alert-panel h4 {
        margin: 0 0 12px 0;
        color: #92400e;
        font-size: 16px;
    }
    
    .alert-panel.info h4 {
        color: #1e40af;
    }
    
    .alert-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .alert-list li {
        padding: 8px 0;
        border-bottom: 1px solid rgba(251, 146, 60, 0.2);
    }
    
    .alert-list li:last-child {
        border-bottom: none;
    }
    
    .chart-container {
        position: relative;
        height: 200px;
        margin-top: 16px;
    }
    
    .widget-link {
        color: #4c8a89;
        text-decoration: none;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 12px;
        transition: color 0.2s;
    }
    
    .widget-link:hover {
        color: #2563eb;
    }
    
    .system-overview {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 20px 24px;
        margin-bottom: 24px;
        position: relative;
    }
    
    .system-overview h2 {
        margin: 0 0 8px 0;
        font-size: 22px;
        font-weight: 700;
    }
    
    .system-overview p {
        margin: 0;
        font-size: 14px;
        opacity: 0.95;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .system-overview-toggle {
        position: absolute;
        top: 20px;
        right: 24px;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .system-overview-toggle:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    .system-overview.expanded p {
        display: block;
        -webkit-line-clamp: unset;
    }
    
    .search-bar {
        position: relative;
        margin-bottom: 0;
    }
    
    .top-actions-row {
        display: flex;
        gap: 16px;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .top-actions-row .search-bar {
        flex: 1;
        min-width: 300px;
    }
    
    .quick-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .quick-action-btn {
        padding: 10px 16px;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        color: #0f172a;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    
    .quick-action-btn:hover {
        border-color: #4c8a89;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(76, 138, 137, 0.1);
    }
    
    .quick-action-btn.primary {
        background: #4c8a89;
        color: white;
        border-color: #4c8a89;
    }
    
    .quick-action-btn.primary:hover {
        background: #3d6f6e;
        border-color: #3d6f6e;
    }
    
    .search-bar input {
        width: 100%;
        padding: 12px 16px 12px 44px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
    }
    
    .search-bar .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #64748b;
    }
    
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        margin-top: 4px;
        max-height: 400px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
    }
    
    .search-results.show {
        display: block;
    }
    
    .search-result-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.2s;
    }
    
    .search-result-item:hover {
        background: #f8fafc;
    }
    
    .search-result-item:last-child {
        border-bottom: none;
    }
</style>

<main class="dashboard-page">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>System-wide preparedness planning overview and insights</p>
    </div>

    <!-- Top Actions Row: Search + Quick Actions -->
    <div class="top-actions-row">
        <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="globalSearch" placeholder="Search campaigns, events, content..." autocomplete="off">
            <div id="searchResults" class="search-results"></div>
        </div>
        <div class="quick-actions">
            <a href="<?php echo $publicPath; ?>/campaigns.php#planning-section" class="quick-action-btn primary">
                <i class="fas fa-plus"></i> Create Campaign
            </a>
            <a href="<?php echo $publicPath; ?>/events.php#create-event" class="quick-action-btn">
                <i class="fas fa-calendar-plus"></i> Schedule Event
            </a>
            <a href="<?php echo $publicPath; ?>/partners.php" class="quick-action-btn">
                <i class="fas fa-handshake"></i> Add Partner
            </a>
            <a href="<?php echo $publicPath; ?>/events.php#event-calendar" class="quick-action-btn">
                <i class="fas fa-calendar-alt"></i> View Calendar
            </a>
        </div>
    </div>

    <!-- System Overview Panel -->
    <div class="system-overview" id="systemOverview">
        <h2>Barangay Public Safety Campaign Management System</h2>
        <p>
            This system supports pre-calamity preparedness planning, scheduling, and coordination for Quezon City barangays.
            It enables structured campaign creation, manual and AI-assisted scheduling, conflict avoidance, visibility of schedules,
            and data preparation for impact evaluation. The system focuses on safety seminars, preparedness orientations, fire and
            earthquake drills, clean-up drives, and simulation activities to improve community readiness.
        </p>
        <button class="system-overview-toggle" onclick="toggleSystemOverview()">
            <i class="fas fa-info-circle"></i> About
        </button>
    </div>

    <!-- KPI Summary Cards -->
    <section id="kpi-overview" class="dashboard-section">
    <div class="kpi-grid" id="kpiGrid">
        <div class="kpi-card">
            <div class="kpi-icon">📢</div>
            <div class="kpi-value" id="kpiActiveCampaigns">-</div>
            <div class="kpi-label">Active Campaigns</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">📅</div>
            <div class="kpi-value" id="kpiScheduledCampaigns">-</div>
            <div class="kpi-label">Scheduled Campaigns</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🎯</div>
            <div class="kpi-value" id="kpiUpcomingEvents">-</div>
            <div class="kpi-label">Upcoming Events</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">👥</div>
            <div class="kpi-value" id="kpiDefinedSegments">-</div>
            <div class="kpi-label">Audience Segments</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">🤝</div>
            <div class="kpi-value" id="kpiPartnerOrgs">-</div>
            <div class="kpi-label">Partner Organizations</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon">💬</div>
            <div class="kpi-value" id="kpiFeedbackResponses">-</div>
            <div class="kpi-label">Feedback Responses</div>
        </div>
    </div>
    </section>

    <!-- Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Campaign Planning Snapshot -->
        <section id="campaign-planning" class="dashboard-section">
            <div class="dashboard-card">
            <h3>📊 Campaign Planning Snapshot</h3>
            <div id="campaignStatusChart" class="chart-container">
                <canvas id="campaignStatusChartCanvas"></canvas>
            </div>
            <div id="upcomingCampaigns" style="margin-top: 16px;">
                <strong>Upcoming Campaigns (Next 14 Days):</strong>
                <ul class="status-list" id="upcomingCampaignsList">
                    <li>Loading...</li>
                </ul>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                <strong>AI vs Manual Scheduling:</strong>
                <div style="margin-top: 8px;">
                    <span id="aiScheduledCount">-</span> AI-recommended | 
                    <span id="manualScheduledCount">-</span> Manual
                </div>
            </div>
            <a href="<?php echo $publicPath; ?>/campaigns.php" class="widget-link">
                View All Campaigns <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>

        <!-- Event & Seminar Readiness -->
        <section id="event-readiness" class="dashboard-section">
            <div class="dashboard-card">
            <h3>🎯 Event & Seminar Readiness</h3>
            <div id="eventTypeChart" class="chart-container">
                <canvas id="eventTypeChartCanvas"></canvas>
            </div>
            <div id="upcomingEvents" style="margin-top: 16px;">
                <strong>Upcoming Events:</strong>
                <ul class="status-list" id="upcomingEventsList">
                    <li>Loading...</li>
                </ul>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px;">
                <div><strong>Capacity Readiness:</strong> <span id="capacityInfo">-</span></div>
                <div style="margin-top: 8px;"><strong>Linkage:</strong> <span id="linkageInfo">-</span></div>
            </div>
            <a href="<?php echo $publicPath; ?>/events.php" class="widget-link">
                View All Events <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>

        <!-- Audience Coverage Overview -->
        <section id="audience-coverage" class="dashboard-section">
            <div class="dashboard-card">
            <h3>👥 Audience Coverage Overview</h3>
            <div style="margin-bottom: 16px;">
                <div style="font-size: 24px; font-weight: 700; color: #4c8a89;" id="totalSegmentsCount">-</div>
                <div style="font-size: 14px; color: #64748b;">Total Audience Segments Defined</div>
            </div>
            <div>
                <strong>Most Targeted Segments:</strong>
                <ul class="status-list" id="mostTargetedSegments">
                    <li>Loading...</li>
                </ul>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px;">
                <div><strong>Campaigns with Segments:</strong> <span id="campaignsWithSegments">-</span></div>
                <div style="margin-top: 4px;"><strong>Segments in Use:</strong> <span id="segmentsUsed">-</span></div>
            </div>
            <a href="<?php echo $publicPath; ?>/segments.php" class="widget-link">
                View All Segments <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>

        <!-- Engagement & Impact Preview -->
        <section id="engagement-impact" class="dashboard-section">
            <div class="dashboard-card">
            <h3>📈 Engagement & Impact Preview</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #4c8a89;" id="campaignsWithFeedback">-</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Campaigns with Feedback</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #4c8a89;" id="eventsWithAttendance">-</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Events with Attendance</div>
                </div>
            </div>
            <div style="padding: 12px; background: #f8fafc; border-radius: 8px;">
                <div><strong>Total Attendance:</strong> <span id="totalAttendance">-</span></div>
                <div style="margin-top: 4px;"><strong>Recent Engagement (30 days):</strong> <span id="recentEngagement">-</span> events</div>
            </div>
            <a href="<?php echo $publicPath; ?>/impact.php" class="widget-link">
                View Impact Reports <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>

        <!-- Partner & Collaboration Snapshot -->
        <section id="partners-snapshot" class="dashboard-section">
            <div class="dashboard-card">
            <h3>🤝 Partner & Collaboration Snapshot</h3>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 16px;">
                <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #4c8a89;" id="activePartners">-</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Active Partners</div>
                </div>
                <div style="text-align: center; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 28px; font-weight: 700; color: #4c8a89;" id="upcomingPartneredEvents">-</div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Partnered Events</div>
                </div>
            </div>
            <div style="padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px;">
                <div><strong>Schools:</strong> <span id="schoolsCount">-</span></div>
                <div style="margin-top: 4px;"><strong>NGOs:</strong> <span id="ngosCount">-</span></div>
            </div>
            <a href="<?php echo $publicPath; ?>/partners.php" class="widget-link">
                View All Partners <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>

        <!-- Content Repository Snapshot -->
        <section id="content-snapshot" class="dashboard-section">
            <div class="dashboard-card">
            <h3>📚 Content Repository Snapshot</h3>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #4c8a89;" id="totalContent">-</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Total Content</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #166534;" id="approvedContent">-</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Approved</div>
                </div>
                <div style="text-align: center; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="font-size: 24px; font-weight: 700; color: #dc2626;" id="pendingContent">-</div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Pending</div>
                </div>
            </div>
            <div id="recentContent" style="margin-top: 16px;">
                <strong>Recent Approved Content:</strong>
                <ul class="status-list" id="recentContentList">
                    <li>Loading...</li>
                </ul>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; font-size: 13px;">
                <div><strong>Draft Content:</strong> <span id="draftContent">-</span></div>
                <div style="margin-top: 4px;"><strong>Top Categories:</strong> <span id="topCategories">-</span></div>
            </div>
            <a href="<?php echo $publicPath; ?>/content.php" class="widget-link">
                View Content Library <i class="fas fa-arrow-right"></i>
            </a>
            </div>
        </section>
    </div>

    <!-- Alerts & Reminders Panel -->
    <section id="alerts-reminders" class="dashboard-section">
        <div class="dashboard-card" style="grid-column: 1 / -1;">
            <h3>⚠️ Alerts & Reminders</h3>
            <div id="alertsContainer">
                <p style="text-align:center; color:#64748b; padding:24px;">Loading alerts...</p>
            </div>
        </div>
    </section>
</main>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
const publicPath = '<?php echo $publicPath; ?>';

let campaignStatusChart = null;
let eventTypeChart = null;

// Load dashboard data
async function loadDashboard() {
    try {
        // Get token from localStorage
        const authToken = localStorage.getItem('jwtToken');
        if (!authToken || authToken.trim() === '') {
            throw new Error('Authentication token not found. Please log in again.');
        }
        
        const res = await fetch(apiBase + '/api/v1/dashboard/summary', {
            headers: { 'Authorization': 'Bearer ' + authToken }
        });
        
        if (!res.ok) {
            if (res.status === 401) {
                window.location.href = publicPath + '/index.php';
                return;
            }
            
            // Read response as text first (can only read once)
            const errorText = await res.text();
            let errorMessage = 'Failed to load dashboard data';
            try {
                const errorData = JSON.parse(errorText);
                errorMessage = errorData.error || errorMessage;
            } catch (e) {
                // If not JSON, use the text (truncated if too long)
                if (errorText && errorText.length < 200) {
                    errorMessage = errorText;
                }
            }
            console.error('Dashboard API error:', res.status, errorMessage);
            throw new Error(errorMessage);
        }
        
        // Parse JSON from response
        const data = await res.json();
        
        // Update KPIs
        updateKPIs(data.kpis);
        
        // Update Campaign Snapshot
        updateCampaignSnapshot(data.campaign_snapshot);
        
        // Update Event Readiness
        updateEventReadiness(data.event_readiness);
        
        // Update Audience Coverage
        updateAudienceCoverage(data.audience_coverage);
        
        // Update Engagement Preview
        updateEngagementPreview(data.engagement_preview);
        
        // Update Partner Snapshot
        updatePartnerSnapshot(data.partner_snapshot);
        
        // Update Content Snapshot
        updateContentSnapshot(data.content_snapshot || {});
        
        // Update Alerts
        updateAlerts(data.alerts);
        
    } catch (err) {
        console.error('Error loading dashboard:', err);
        document.getElementById('kpiGrid').innerHTML = '<div style="grid-column: 1 / -1; text-align:center; padding:24px; color:#dc2626;">Error loading dashboard: ' + err.message + '</div>';
    }
}

function updateKPIs(kpis) {
    document.getElementById('kpiActiveCampaigns').textContent = kpis.active_campaigns || 0;
    document.getElementById('kpiScheduledCampaigns').textContent = kpis.scheduled_campaigns || 0;
    document.getElementById('kpiUpcomingEvents').textContent = kpis.upcoming_events || 0;
    document.getElementById('kpiDefinedSegments').textContent = kpis.defined_segments || 0;
    document.getElementById('kpiPartnerOrgs').textContent = kpis.partner_organizations || 0;
    document.getElementById('kpiFeedbackResponses').textContent = kpis.feedback_responses || 0;
}

function updateCampaignSnapshot(snapshot) {
    // Campaign status chart
    const ctx = document.getElementById('campaignStatusChartCanvas');
    if (campaignStatusChart) {
        campaignStatusChart.destroy();
    }
    
    const statusData = snapshot.by_status || {};
    campaignStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{
                data: Object.values(statusData),
                backgroundColor: [
                    '#f1f5f9', // draft
                    '#dbeafe', // scheduled
                    '#dcfce7', // active
                    '#e0e7ff', // completed
                ],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
    
    // Upcoming campaigns
    const upcomingList = document.getElementById('upcomingCampaignsList');
    if (snapshot.upcoming && snapshot.upcoming.length > 0) {
        upcomingList.innerHTML = snapshot.upcoming.map(c => `
            <li>
                <span><strong>${c.title}</strong></span>
                <span class="status-badge badge-${c.status}">${c.status}</span>
            </li>
        `).join('');
    } else {
        upcomingList.innerHTML = '<li style="color:#64748b;">No upcoming campaigns</li>';
    }
    
    // AI vs Manual
    document.getElementById('aiScheduledCount').textContent = snapshot.ai_scheduled || 0;
    document.getElementById('manualScheduledCount').textContent = snapshot.manual_scheduled || 0;
}

function updateEventReadiness(readiness) {
    // Event type chart
    const ctx = document.getElementById('eventTypeChartCanvas');
    if (eventTypeChart) {
        eventTypeChart.destroy();
    }
    
    const typeData = readiness.by_type || {};
    eventTypeChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: Object.keys(typeData),
            datasets: [{
                label: 'Events',
                data: Object.values(typeData),
                backgroundColor: '#4c8a89',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                }
            }
        }
    });
    
    // Upcoming events
    const eventsList = document.getElementById('upcomingEventsList');
    if (readiness.upcoming && readiness.upcoming.length > 0) {
        eventsList.innerHTML = readiness.upcoming.map(e => `
            <li>
                <div>
                    <strong>${e.event_name}</strong><br>
                    <small style="color:#64748b;">${e.date} ${e.start_time || ''} | ${e.venue || 'TBD'}</small>
                </div>
                <span class="status-badge badge-${e.event_status}">${e.event_status}</span>
            </li>
        `).join('');
    } else {
        eventsList.innerHTML = '<li style="color:#64748b;">No upcoming events</li>';
    }
    
    // Capacity and linkage info
    const capacity = readiness.capacity || {};
    const linkage = readiness.linkage || {};
    document.getElementById('capacityInfo').textContent = 
        `${capacity.events_with_capacity || 0} events with capacity set`;
    document.getElementById('linkageInfo').textContent = 
        `${linkage.linked || 0} linked to campaigns, ${linkage.standalone || 0} standalone`;
}

function updateAudienceCoverage(coverage) {
    document.getElementById('totalSegmentsCount').textContent = coverage.total_segments || 0;
    
    const segmentsList = document.getElementById('mostTargetedSegments');
    if (coverage.most_targeted && coverage.most_targeted.length > 0) {
        segmentsList.innerHTML = coverage.most_targeted.map(s => `
            <li>
                <span>${s.segment_name}</span>
                <span style="color:#64748b;">${s.campaign_count || 0} campaigns</span>
            </li>
        `).join('');
    } else {
        segmentsList.innerHTML = '<li style="color:#64748b;">No segments yet</li>';
    }
    
    const summary = coverage.summary || {};
    document.getElementById('campaignsWithSegments').textContent = summary.campaigns_with_segments || 0;
    document.getElementById('segmentsUsed').textContent = summary.segments_used || 0;
}

function updateEngagementPreview(engagement) {
    document.getElementById('campaignsWithFeedback').textContent = engagement.campaigns_with_feedback || 0;
    document.getElementById('eventsWithAttendance').textContent = engagement.events_with_attendance || 0;
    document.getElementById('totalAttendance').textContent = engagement.total_attendance || 0;
    document.getElementById('recentEngagement').textContent = engagement.recent_engagement || 0;
}

function updatePartnerSnapshot(partners) {
    document.getElementById('activePartners').textContent = partners.active_partners || 0;
    document.getElementById('upcomingPartneredEvents').textContent = partners.upcoming_partnered_events?.length || 0;
    document.getElementById('schoolsCount').textContent = partners.schools_count || 0;
    document.getElementById('ngosCount').textContent = partners.ngos_count || 0;
}

function updateContentSnapshot(content) {
    document.getElementById('totalContent').textContent = content.total_content || 0;
    document.getElementById('approvedContent').textContent = content.approved_content || 0;
    document.getElementById('pendingContent').textContent = content.pending_content || 0;
    document.getElementById('draftContent').textContent = content.draft_content || 0;
    
    // Recent approved content
    const recentList = document.getElementById('recentContentList');
    if (content.recent_content && content.recent_content.length > 0) {
        recentList.innerHTML = content.recent_content.map(c => `
            <li>
                <span><strong>${c.title}</strong></span>
                <span style="color:#64748b; font-size:12px;">${c.content_type || 'N/A'}</span>
            </li>
        `).join('');
    } else {
        recentList.innerHTML = '<li style="color:#64748b;">No approved content yet</li>';
    }
    
    // Top categories
    const categories = content.by_category || {};
    const topCategories = Object.keys(categories).slice(0, 3).join(', ') || 'None';
    document.getElementById('topCategories').textContent = topCategories;
}

function updateAlerts(alerts) {
    const container = document.getElementById('alertsContainer');
    
    if (!alerts || alerts.length === 0) {
        container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No alerts at this time</p>';
        return;
    }
    
    let html = '';
    alerts.forEach(alert => {
        html += `
            <div class="alert-panel ${alert.type === 'info' ? 'info' : ''}">
                <h4>${alert.title} (${alert.count})</h4>
                <ul class="alert-list">
        `;
        if (alert.items && alert.items.length > 0) {
            alert.items.forEach(item => {
                const name = item.title || item.event_name || item.name || 'N/A';
                html += `<li>${name}</li>`;
            });
        }
        html += `
                </ul>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

// Global search
let searchTimeout;
document.getElementById('globalSearch').addEventListener('input', function(e) {
    const query = e.target.value.trim();
    const resultsDiv = document.getElementById('searchResults');
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        resultsDiv.classList.remove('show');
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        try {
            const res = await fetch(apiBase + '/api/v1/dashboard/search?q=' + encodeURIComponent(query), {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const data = await res.json();
            
            if (data.data && data.data.length > 0) {
                resultsDiv.innerHTML = data.data.map(item => `
                    <div class="search-result-item" onclick="window.location.href='${publicPath}/${item.url}#${item.id}'">
                        <strong>${item.name}</strong>
                        <span style="color:#64748b; font-size:12px; margin-left:8px;">${item.type}</span>
                    </div>
                `).join('');
                resultsDiv.classList.add('show');
            } else {
                resultsDiv.innerHTML = '<div class="search-result-item" style="color:#64748b;">No results found</div>';
                resultsDiv.classList.add('show');
            }
        } catch (err) {
            console.error('Search error:', err);
        }
    }, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.search-bar')) {
        document.getElementById('searchResults').classList.remove('show');
    }
});

// Initialize
loadDashboard();

// Toggle system overview expand/collapse
function toggleSystemOverview() {
    const overview = document.getElementById('systemOverview');
    overview.classList.toggle('expanded');
}

// Refresh every 5 minutes
setInterval(loadDashboard, 300000);
</script>
    </main>
</body>
</html>

