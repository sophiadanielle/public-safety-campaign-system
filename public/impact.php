<?php
$pageTitle = 'Impact Monitoring';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="<?php echo htmlspecialchars($basePath . '/public/js/viewer-restrictions.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($basePath . '/public/js/viewer-restrictions.js'); ?>"></script>
    <script>
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
    </script>
</head>
<body class="module-impact" data-module="impact">
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
    }
    
    @media (min-width: 769px) {
        .sidebar {
            transform: translateX(0) !important;
        }
    }
    
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
    .impact-page {
        width: 100%;
        margin: 0;
        padding: 24px;
        box-sizing: border-box;
    }
    .page-header {
        margin-bottom: 40px;
    }
    .page-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
    }
    .page-header p {
        font-size: 16px;
        color: #64748b;
        margin: 0;
    }
    .metrics-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .metric-card {
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        padding: 24px;
        border-radius: 12px;
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .metric-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border-color: #4c8a89;
    }
    .metric-label {
        font-size: 12px;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .metric-value {
        font-size: 32px;
        font-weight: 800;
        color: #0f172a;
        background: linear-gradient(135deg, #4c8a89 0%, #667eea 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .chart-container {
        margin-top: 32px;
        padding: 24px;
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
    }
    .section-description {
        color: #64748b;
        font-size: 14px;
        line-height: 1.6;
        margin-bottom: 24px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
        border-left: 4px solid #4c8a89;
    }
    .helper-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        font-style: italic;
    }
    .empty-state {
        text-align: center;
        padding: 48px 24px;
        color: #64748b;
        background: #f8fafc;
        border-radius: 8px;
        border: 2px dashed #e2e8f0;
        margin-top: 20px;
    }
    .empty-state-icon {
        font-size: 48px;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    .metric-explanation {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
        line-height: 1.4;
    }
    .instruction-text {
        color: #475569;
        font-size: 13px;
        margin-bottom: 16px;
        padding: 12px;
        background: #f1f5f9;
        border-radius: 6px;
    }
    .section-title {
        font-size: 24px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 20px 0;
        padding-bottom: 16px;
        border-bottom: 2px solid #f1f5f9;
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
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 24px;
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
    .form-field select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.2s;
        background: #fff;
    }
    .form-field input:focus,
    .form-field select:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
    }
</style>

<main class="impact-page">
    <div class="page-header">
        <h1>Impact Monitoring & Evaluation</h1>
        <p>Track campaign performance, engagement metrics, and effectiveness</p>
    </div>

    <!-- Impact Dashboard -->
    <section class="card" id="impact-dashboard" style="margin-bottom:32px;">
        <h2 class="section-title">Campaign Impact Dashboard</h2>
        <div class="section-description">
            <strong>What this shows:</strong> View how well your campaign is performing. See how many people were reached, who attended events, and how they responded to surveys. This helps you understand if your campaign is working effectively.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="campaign_id" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Need help?</strong> Select a campaign from the dropdown above. All campaigns from the Campaigns module are listed here.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="loadImpact()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-chart-line" style="margin-right:8px;"></i>View Campaign Performance
                </button>
            </div>
        </div>
        
        <div class="empty-state" id="dashboardEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-chart-bar"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No data loaded yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">Select a campaign above and click <strong>"View Campaign Performance"</strong> to see results. If you see this message after clicking, the campaign may be new or hasn't collected any data yet.</p>
            <div style="margin-top:16px; padding:12px; background:#f1f5f9; border-radius:6px; border-left:3px solid #4c8a89;">
                <p style="margin:0 0 8px 0; font-size:13px; font-weight:600; color:#0f172a;">💡 To generate impact data:</p>
                <ul style="margin:0; padding-left:20px; font-size:12px; color:#475569; line-height:1.8;">
                    <li><strong>Reach:</strong> Send notifications via Campaign Schedules</li>
                    <li><strong>Attendance:</strong> Create events linked to this campaign and record attendance</li>
                    <li><strong>Survey Responses:</strong> Create surveys linked to this campaign and collect responses</li>
                </ul>
                <p style="margin:8px 0 0 0; font-size:11px; color:#64748b; font-style:italic;">See <code>HOW_TO_GENERATE_CAMPAIGN_IMPACT_DATA.md</code> for detailed instructions.</p>
            </div>
        </div>
        
        <div class="metrics-grid" id="metricsCards" style="display:none;"></div>
        
        <div class="instruction-text" id="chartInstruction" style="display:none;">
            <i class="fas fa-info-circle" style="margin-right:8px; color:#4c8a89;"></i>
            <strong>Chart Explanation:</strong> This chart compares the number of people reached, who attended events, and who completed surveys. Higher bars mean better engagement.
        </div>
        <div class="chart-container" id="chartContainer" style="display:none;">
            <canvas id="chart" height="100"></canvas>
        </div>
        
        <div class="status" id="status" style="margin-top:20px;"></div>
    </section>

    <!-- Evaluation Reports -->
    <section class="card" id="evaluation-reports" style="margin-bottom:32px;">
        <h2 class="section-title">Evaluation Reports</h2>
        <div class="section-description">
            <strong>What this does:</strong> View and download official evaluation reports for your campaigns. Reports are automatically generated and include all key performance metrics in a professional PDF format.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="report_campaign_id" required style="font-size:15px; padding:12px 16px;" onchange="onReportCampaignSelect()">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a campaign to automatically view available reports. Click "Download as PDF" to export.</div>
            </div>
        </div>
        <div id="reportList" style="margin-top:20px;"></div>
        <div class="status" id="reportStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Metrics Overview -->
    <section class="card" id="metrics-overview" style="margin-bottom:32px;">
        <h2 class="section-title">Metrics Overview</h2>
        <div class="section-description">
            <strong>What this shows:</strong> See all the important numbers for your campaign in one place. This includes how many people you reached, attendance at events, survey responses, and engagement rates. Use this to quickly understand your campaign's performance.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="metrics_campaign_id" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select the campaign you want to review. All key performance metrics will be displayed below.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="loadMetricsOverview()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-chart-bar" style="margin-right:8px;"></i>View Key Metrics
                </button>
            </div>
        </div>
        <div class="empty-state" id="metricsEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-chart-pie"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No metrics data available</p>
            <p style="margin:0; font-size:14px;">Enter a campaign number and click "View Key Metrics" to see performance data.</p>
        </div>
        <div id="metricsOverviewContent" style="margin-top:20px;"></div>
        <div class="status" id="metricsStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Performance Analysis -->
    <section class="card" id="performance-analysis" style="margin-bottom:32px;">
        <h2 class="section-title">Performance Analysis</h2>
        <div class="section-description">
            <strong>What this does:</strong> Get a detailed analysis of how your campaign is performing. See a summary of key achievements, engagement rates, and a visual breakdown of reach, attendance, and survey participation. This helps you understand what's working well and what might need improvement.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="analysis_campaign_id" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select the campaign you want to analyze. You'll see a detailed breakdown of performance with charts and summaries.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="loadPerformanceAnalysis()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-analytics" style="margin-right:8px;"></i>Analyze Performance
                </button>
            </div>
        </div>
        <div class="empty-state" id="analysisEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-chart-line"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No performance data available</p>
            <p style="margin:0; font-size:14px;">Enter a campaign number and click "Analyze Performance" to see detailed analysis.</p>
        </div>
        <div id="performanceAnalysisContent" style="margin-top:20px;"></div>
        <div class="instruction-text" id="performanceChartInstruction" style="display:none; margin-top:20px;">
            <i class="fas fa-info-circle" style="margin-right:8px; color:#4c8a89;"></i>
            <strong>Chart Explanation:</strong> This chart shows the breakdown of your campaign's reach, event attendance, and survey responses. Each slice represents a different type of engagement.
        </div>
        <div class="chart-container" id="performanceChartContainer" style="margin-top:20px; display:none;">
            <canvas id="performanceChart" height="100"></canvas>
        </div>
        <div class="status" id="analysisStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Export Data -->
    <section class="card" id="export-data" style="margin-bottom:32px;">
        <h2 class="section-title">Export Data</h2>
        <div class="section-description">
            <strong>What this does:</strong> Download your campaign's performance data as a professional PDF report. This includes all key metrics, charts, and analysis in a formatted document suitable for presentations and official records.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="export_campaign_id" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select the campaign you want to export. You will need to verify your password before downloading.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="showExportPasswordModal()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-file-pdf" style="margin-right:8px;"></i>Download Data as PDF
                </button>
            </div>
        </div>
        <div class="status" id="exportStatus" style="margin-top:12px;"></div>
    </section>

<!-- Password Verification Modal for Export -->
<div id="exportPasswordModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:10000; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:450px; margin:100px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.3);">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:18px;"><i class="fas fa-lock"></i> Verify Password to Export</h2>
            <button onclick="closeExportPasswordModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <p style="color:#64748b; margin-bottom:16px;">Please enter your password to confirm the export. This ensures data security.</p>
            <div class="form-field" style="margin-bottom:20px;">
                <label style="font-weight:600; color:#334155;">Password</label>
                <div style="position:relative;">
                    <input type="password" id="exportPassword" placeholder="Enter your password" style="width:100%; padding:12px 40px 12px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    <button type="button" onclick="togglePasswordVisibility()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b;">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>
            <div id="exportPasswordError" style="display:none; color:#dc2626; background:#fee2e2; padding:10px; border-radius:6px; margin-bottom:16px; font-size:13px;"></div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeExportPasswordModal()">Cancel</button>
                <button class="btn btn-primary" onclick="verifyPasswordAndExport()" id="exportConfirmBtn" style="background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);">
                    <i class="fas fa-download"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
const basePath = '<?php echo $basePath; ?>';
let chart;

// Load all campaigns and populate all dropdowns in Impact module
async function loadAllCampaigns() {
    try {
        if (!token || token.trim() === '') {
            console.warn('No token available for loading campaigns');
            return;
        }
        
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (!res.ok) {
            console.error('Failed to load campaigns:', res.status);
            if (res.status === 401) {
                console.error('Authentication failed - token may be expired');
            }
            return;
        }
        
        const data = await res.json();
        const campaigns = data.data || [];
        
        console.log('Impact Module: Loaded', campaigns.length, 'campaigns from API');
        
        // Get all campaign dropdown elements in Impact module
        const dropdownIds = [
            'campaign_id',           // Impact Dashboard
            'report_campaign_id',     // Evaluation Reports
            'metrics_campaign_id',   // Metrics Overview
            'analysis_campaign_id',   // Performance Analysis
            'export_campaign_id'      // Export Data
        ];
        
        // Populate each dropdown with all campaigns
        dropdownIds.forEach(dropdownId => {
            const dropdown = document.getElementById(dropdownId);
            if (!dropdown) {
                console.warn('Impact Module: Dropdown not found:', dropdownId);
                return;
            }
            
            // Clear existing options
            dropdown.innerHTML = '<option value="">-- Select a campaign --</option>';
            
            // Add all campaigns to dropdown
            if (campaigns.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = '-- No campaigns available --';
                option.disabled = true;
                dropdown.appendChild(option);
            } else {
                campaigns.forEach(campaign => {
                    const option = document.createElement('option');
                    option.value = campaign.id;
                    const title = campaign.title || 'Untitled Campaign';
                    option.textContent = `ID ${campaign.id} - ${title}`;
                    dropdown.appendChild(option);
                });
            }
            
            console.log(`Impact Module: Populated ${dropdownId} with ${campaigns.length} campaigns`);
        });
        
    } catch (err) {
        console.error('Impact Module: Error loading campaigns:', err);
    }
}

// Load campaigns when page loads - ensure it runs after DOM is ready
(function() {
    function initCampaignDropdowns() {
        // Wait a bit to ensure token is available
        setTimeout(loadAllCampaigns, 300);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCampaignDropdowns);
    } else {
        // DOM already loaded
        initCampaignDropdowns();
    }
})();

async function loadImpact() {
    const cid = document.getElementById('campaign_id').value;
    if (!cid || cid <= 0) {
        document.getElementById('status').textContent = '⚠️ Please enter a valid campaign number';
        document.getElementById('status').style.color = '#dc2626';
        return;
    }
    
    const statusEl = document.getElementById('status');
    const emptyState = document.getElementById('dashboardEmptyState');
    const metricsCards = document.getElementById('metricsCards');
    const chartContainer = document.getElementById('chartContainer');
    const chartInstruction = document.getElementById('chartInstruction');
    
    statusEl.textContent = 'Loading campaign data...';
    statusEl.style.color = '#64748b';
    emptyState.style.display = 'none';
    metricsCards.style.display = 'none';
    chartContainer.style.display = 'none';
    chartInstruction.style.display = 'none';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', { 
            headers: { 'Authorization': 'Bearer ' + token } 
        });
        const data = await res.json();
        
        if (!data.data) {
            emptyState.style.display = 'block';
            statusEl.textContent = '⚠️ ' + (data.error || 'No data available for this campaign yet. The campaign may be new or has not collected any data.');
            statusEl.style.color = '#dc2626';
            return;
        }
        
        const m = data.data;
        const hasData = (m.reach || 0) > 0 || (m.attendance_count || 0) > 0 || (m.survey_responses || 0) > 0;
        
        if (!hasData) {
            emptyState.style.display = 'block';
            // Update the empty state message with helpful instructions
            const emptyStateMsg = emptyState.querySelector('p:last-child');
            if (emptyStateMsg) {
                emptyStateMsg.innerHTML = 'Campaign found, but no engagement data available yet. <strong>To generate impact data:</strong><br>' +
                    '• <strong>Reach:</strong> Send notifications via Campaign Schedules in the Campaigns module<br>' +
                    '• <strong>Attendance:</strong> Create events linked to this campaign (set Linked Campaign) and record attendance<br>' +
                    '• <strong>Survey Responses:</strong> Create surveys linked to this campaign and collect responses';
            }
            statusEl.textContent = 'ℹ️ Campaign found, but no engagement data available yet. Data will appear once notifications are sent, events are attended, or surveys are completed.';
            statusEl.style.color = '#64748b';
            return;
        }
        
        renderCards(m);
        renderChart(m);
        metricsCards.style.display = 'grid';
        chartContainer.style.display = 'block';
        chartInstruction.style.display = 'block';
        statusEl.textContent = '✓ Campaign performance data loaded successfully';
        statusEl.style.color = '#166534';
    } catch (err) {
        emptyState.style.display = 'block';
        statusEl.textContent = '✗ Unable to load data. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

function renderCards(m) {
    const container = document.getElementById('metricsCards');
    container.innerHTML = '';
    
    const metrics = [
        { key: 'attendance_count', label: 'Event Attendance', explanation: 'Number of people who attended campaign events' },
        { key: 'survey_responses', label: 'Survey Responses', explanation: 'Number of completed survey responses received' },
        { key: 'avg_rating', label: 'Average Rating', explanation: 'Average satisfaction rating from survey responses (out of 5)' },
        { key: 'engagement_rate', label: 'Engagement Rate', explanation: 'Percentage showing how many people actively engaged (attended events or completed surveys)' }
    ];
    
    metrics.forEach(metric => {
        const value = m[metric.key] || 0;
        const displayValue = typeof value === 'number' && value < 1 ? (value * 100).toFixed(1) + '%' : (value === null || value === undefined ? 'N/A' : value);
        const div = document.createElement('div');
        div.className = 'metric-card';
        div.innerHTML = `
            <div class="metric-label">${metric.label}</div>
            <div class="metric-value">${displayValue}</div>
            <div class="metric-explanation">${metric.explanation}</div>
        `;
        container.appendChild(div);
    });
}

function renderChart(m) {
    const ctx = document.getElementById('chart');
    const labels = ['Reach', 'Attendance', 'Survey Responses'];
    const vals = [m.reach || 0, m.attendance_count || 0, m.survey_responses || 0];
    
    if (chart) chart.destroy();
    
    chart = new Chart(ctx, {
        type: 'bar',
        data: { 
            labels, 
            datasets: [{ 
                label: 'Counts', 
                data: vals, 
                backgroundColor: ['#4c8a89', '#667eea', '#764ba2'],
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                } 
            } 
        }
    });
}

// Note: loadImpact() is called when user clicks "View Campaign Performance" button
// Don't auto-load on page load - wait for user to select a campaign

// Evaluation Reports - Auto-load when campaign is selected
async function onReportCampaignSelect() {
    const cid = document.getElementById('report_campaign_id').value;
    const statusEl = document.getElementById('reportStatus');
    const container = document.getElementById('reportList');
    
    if (!cid) {
        container.innerHTML = '';
        statusEl.textContent = '';
        return;
    }
    
    statusEl.textContent = 'Loading reports...';
    statusEl.style.color = '#64748b';
    
    // Auto-generate report if none exists, then load the list
    try {
        // First try to load existing reports
        const res = await fetch(apiBase + '/api/v1/reports?campaign_id=' + cid, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.reports && data.reports.length > 0) {
            // Reports exist, display them
            displayReportList(data.reports, cid);
            statusEl.textContent = '✓ Found ' + data.reports.length + ' report(s) for this campaign';
            statusEl.style.color = '#166534';
        } else {
            // No reports exist, auto-generate one
            statusEl.textContent = 'Generating evaluation report...';
            const genRes = await fetch(apiBase + '/api/v1/reports/generate/' + cid, {
                method: 'GET',
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const genData = await genRes.json();
            
            if (genRes.ok && genData.file_path) {
                // Reload the report list
                const reloadRes = await fetch(apiBase + '/api/v1/reports?campaign_id=' + cid, {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const reloadData = await reloadRes.json();
                
                if (reloadRes.ok && reloadData.reports && reloadData.reports.length > 0) {
                    displayReportList(reloadData.reports, cid);
                    statusEl.textContent = '✓ Report generated successfully';
                    statusEl.style.color = '#166534';
                } else {
                    displayReportList([{ file_path: genData.file_path, created_at: new Date().toISOString() }], cid);
                    statusEl.textContent = '✓ Report generated successfully';
                    statusEl.style.color = '#166534';
                }
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-file-alt"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No report data available</p><p style="margin:0; font-size:14px; line-height:1.6;">This campaign may not have enough data to generate a report yet.</p></div>';
                statusEl.textContent = '⚠️ ' + (genData.error || 'Unable to generate report for this campaign');
                statusEl.style.color = '#dc2626';
            }
        }
    } catch (err) {
        container.innerHTML = '<p style="color:#dc2626;">Error loading reports: ' + err.message + '</p>';
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

function displayReportList(reports, campaignId) {
    const container = document.getElementById('reportList');
    
    let html = '<div style="margin-top:16px;"><h3 style="font-size:16px; color:#0f172a; margin-bottom:12px;">Generated Reports</h3>';
    html += '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f1f5f9;"><th style="padding:12px; text-align:left; font-weight:600; color:#475569;">Date Generated</th><th style="padding:12px; text-align:left; font-weight:600; color:#475569;">Actions</th></tr></thead><tbody>';
    
    reports.forEach((report, index) => {
        // Use Asia/Manila timezone for display
        const date = new Date(report.created_at || report.generated_at).toLocaleString('en-US', { 
            timeZone: 'Asia/Manila',
            year: 'numeric', 
            month: 'long', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        html += `<tr style="border-bottom:1px solid #e2e8f0;">
            <td style="padding:12px; color:#1e293b;">${date}</td>
            <td style="padding:12px;">
                <button onclick="showEvaluationReportPasswordModal(${campaignId}, ${index})" class="btn btn-primary" style="padding:6px 12px; font-size:14px; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);">
                    <i class="fas fa-file-pdf" style="margin-right:6px;"></i>Download as PDF
                </button>
            </td>
        </tr>`;
    });
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

// Store current report for download
let currentEvaluationReportCampaignId = null;

function showEvaluationReportPasswordModal(campaignId, reportIndex) {
    currentEvaluationReportCampaignId = campaignId;
    
    // Reuse the export password modal
    document.getElementById('exportPassword').value = '';
    document.getElementById('exportPasswordError').style.display = 'none';
    document.getElementById('exportPasswordModal').style.display = 'block';
    document.getElementById('exportPassword').focus();
    
    // Change the confirm button to call evaluation report download
    const confirmBtn = document.getElementById('exportConfirmBtn');
    confirmBtn.onclick = verifyPasswordAndDownloadEvaluationReport;
}

async function verifyPasswordAndDownloadEvaluationReport() {
    const password = document.getElementById('exportPassword').value;
    const errorDiv = document.getElementById('exportPasswordError');
    const confirmBtn = document.getElementById('exportConfirmBtn');
    
    if (!password) {
        errorDiv.textContent = 'Please enter your password';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Get user email from JWT token
    let userEmail = '';
    try {
        const parts = token.split('.');
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        userEmail = payload.email || payload.sub || '';
    } catch (e) {
        errorDiv.textContent = 'Session error. Please log in again.';
        errorDiv.style.display = 'block';
        return;
    }
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    try {
        let verified = false;
        
        try {
            const verifyRes = await fetch(apiBase + '/api/v1/auth/verify-password', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ password: password })
            });
            
            if (verifyRes.ok) {
                const verifyData = await verifyRes.json();
                verified = verifyData.valid === true || verifyData.success === true;
            }
        } catch (verifyErr) {
            console.log('verify-password failed, trying login endpoint');
        }
        
        if (!verified) {
            try {
                const loginRes = await fetch(apiBase + '/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: userEmail, password: password })
                });
                
                const contentType = loginRes.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const loginData = await loginRes.json();
                    verified = loginRes.ok && loginData.token;
                } else if (loginRes.status >= 500) {
                    verified = true;
                }
            } catch (loginErr) {
                if (token) verified = true;
            }
        }
        
        if (verified) {
            closeExportPasswordModal();
            // Reset the confirm button onclick
            confirmBtn.onclick = verifyPasswordAndExport;
            await generateEvaluationReportPDF(currentEvaluationReportCampaignId);
        } else {
            errorDiv.textContent = 'Incorrect password. Please try again.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Verification failed: ' + error.message;
        errorDiv.style.display = 'block';
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-download"></i> Export PDF';
    }
}

// Generate Evaluation Report PDF
async function generateEvaluationReportPDF(campaignId) {
    const statusEl = document.getElementById('reportStatus');
    statusEl.textContent = 'Generating PDF report...';
    statusEl.style.color = '#64748b';
    
    try {
        // Fetch campaign data
        const campaignRes = await fetch(apiBase + '/api/v1/campaigns/' + campaignId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const campaignData = await campaignRes.json();
        const campaign = campaignData.data || campaignData.campaign || {};
        
        // Fetch impact data
        const impactRes = await fetch(apiBase + '/api/v1/campaigns/' + campaignId + '/impact', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const impactData = await impactRes.json();
        const m = impactData.data || {};
        
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Colors
        const primaryColor = [76, 138, 137];
        const darkColor = [15, 23, 42];
        
        // Header
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 45, 'F');
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('EVALUATION REPORT', 105, 18, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Public Safety Campaign System', 105, 26, { align: 'center' });
        doc.text('Barangay Alertaraqc', 105, 32, { align: 'center' });
        const manilaTime = new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' });
        doc.text('Generated: ' + manilaTime, 105, 40, { align: 'center' });
        
        // Campaign Title
        doc.setTextColor(...darkColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text(campaign.title || `Campaign #${campaignId}`, 14, 58);
        
        doc.setDrawColor(...primaryColor);
        doc.setLineWidth(0.5);
        doc.line(14, 62, 196, 62);
        
        // Campaign Details
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Campaign Information', 14, 72);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        let yPos = 80;
        const details = [
            ['Category:', campaign.category || 'N/A'],
            ['Status:', (campaign.status || 'N/A').toUpperCase()],
            ['Location:', campaign.location || 'N/A'],
            ['Description:', campaign.description || 'N/A']
        ];
        
        details.forEach(([label, value]) => {
            doc.setFont('helvetica', 'bold');
            doc.text(label, 14, yPos);
            doc.setFont('helvetica', 'normal');
            const splitValue = doc.splitTextToSize(String(value), 140);
            doc.text(splitValue, 50, yPos);
            yPos += splitValue.length * 5 + 3;
        });
        
        // Performance Metrics
        yPos += 10;
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Performance Metrics', 14, yPos);
        
        yPos += 10;
        
        const metricsData = [
            ['Event Attendance', String(m.attendance_count || 0)],
            ['Survey Responses', String(m.survey_responses || 0)],
            ['Average Rating', m.avg_rating ? String(m.avg_rating) + ' / 5' : 'N/A'],
            ['Engagement Rate', ((m.engagement_rate || 0) * 100).toFixed(2) + '%']
        ];
        
        doc.autoTable({
            startY: yPos,
            head: [['Metric', 'Value']],
            body: metricsData,
            theme: 'striped',
            headStyles: { fillColor: primaryColor },
            styles: { fontSize: 10 },
            margin: { left: 14, right: 14 }
        });
        
        // Evaluation Summary
        yPos = doc.lastAutoTable.finalY + 15;
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Evaluation Summary', 14, yPos);
        
        yPos += 8;
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        const attendance = m.attendance_count || 0;
        const responses = m.survey_responses || 0;
        const engagementRate = ((m.engagement_rate || 0) * 100).toFixed(2);
        
        let summaryText = `Campaign "${campaign.title || 'Untitled'}" achieved an overall engagement rate of ${engagementRate}%. `;
        if (attendance > 0) {
            summaryText += `${attendance} participant(s) attended campaign events. `;
        }
        if (responses > 0) {
            summaryText += `${responses} survey response(s) were collected`;
            if (m.avg_rating) {
                summaryText += ` with an average satisfaction rating of ${m.avg_rating} out of 5`;
            }
            summaryText += '.';
        }
        
        const splitSummary = doc.splitTextToSize(summaryText, 180);
        doc.text(splitSummary, 14, yPos);
        
        // Footer
        const pageHeight = doc.internal.pageSize.height;
        doc.setFillColor(...primaryColor);
        doc.rect(0, pageHeight - 15, 210, 15, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(8);
        doc.text('Public Safety Campaign System - Official Evaluation Report', 105, pageHeight - 7, { align: 'center' });
        
        // Save PDF
        const fileName = `Evaluation_Report_Campaign_${campaignId}_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        statusEl.textContent = '✓ PDF report downloaded successfully!';
        statusEl.style.color = '#166534';
        
    } catch (err) {
        console.error('PDF generation error:', err);
        statusEl.textContent = '✗ Unable to generate PDF: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Metrics Overview
async function loadMetricsOverview() {
    const cid = document.getElementById('metrics_campaign_id').value;
    const statusEl = document.getElementById('metricsStatus');
    const container = document.getElementById('metricsOverviewContent');
    statusEl.textContent = 'Loading metrics...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const m = data.data;
            const emptyState = document.getElementById('metricsEmptyState');
            emptyState.style.display = 'none';
            
            let html = '<div class="metrics-grid">';
            html += `<div class="metric-card"><div class="metric-label">Event Attendance</div><div class="metric-value">${m.attendance_count || 0}</div><div class="metric-explanation">People who attended campaign events</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Survey Responses</div><div class="metric-value">${m.survey_responses || 0}</div><div class="metric-explanation">Completed survey responses received</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Average Rating</div><div class="metric-value">${m.avg_rating || 'N/A'}</div><div class="metric-explanation">Average satisfaction rating (out of 5)</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Targeted Segments</div><div class="metric-value">${m.targeted_segments || 0}</div><div class="metric-explanation">Number of audience groups targeted</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Engagement Rate</div><div class="metric-value">${((m.engagement_rate || 0) * 100).toFixed(1)}%</div><div class="metric-explanation">Percentage who actively engaged</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Response Rate</div><div class="metric-value">${((m.response_rate || 0) * 100).toFixed(1)}%</div><div class="metric-explanation">Percentage who completed surveys</div></div>`;
            html += '</div>';
            container.innerHTML = html;
            statusEl.textContent = '✓ All metrics loaded successfully';
            statusEl.style.color = '#166534';
        } else {
            const emptyState = document.getElementById('metricsEmptyState');
            emptyState.style.display = 'block';
            emptyState.querySelector('p:last-child').innerHTML = 'No data loaded yet. Enter a campaign number above and click <strong>"View Key Metrics"</strong> to see performance data.';
            statusEl.textContent = '⚠️ ' + (data.error || 'No data available for this campaign yet');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Performance Analysis
let performanceChart;
async function loadPerformanceAnalysis() {
    const cid = document.getElementById('analysis_campaign_id').value;
    const statusEl = document.getElementById('analysisStatus');
    const container = document.getElementById('performanceAnalysisContent');
    statusEl.textContent = 'Analyzing performance...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const m = data.data;
            const emptyState = document.getElementById('analysisEmptyState');
            const chartContainer = document.getElementById('performanceChartContainer');
            const chartInstruction = document.getElementById('performanceChartInstruction');
            
            emptyState.style.display = 'none';
            
            // Analysis text
            let analysis = '<div style="padding:24px; background:#f8fafc; border-radius:8px; margin-bottom:20px; border-left:4px solid #4c8a89;">';
            analysis += '<h3 style="margin:0 0 16px 0; color:#0f172a; font-size:18px;"><i class="fas fa-clipboard-check" style="margin-right:8px; color:#4c8a89;"></i>Performance Summary</h3>';
            
            const attendance = m.attendance_count || 0;
            const responses = m.survey_responses || 0;
            const engagementRate = (m.engagement_rate || 0) * 100;
            
            if (attendance > 0) {
                analysis += `<p style="margin:12px 0; color:#1e293b; line-height:1.6;"><strong style="color:#0f172a;">👥 Event Attendance:</strong> ${attendance} participant${attendance !== 1 ? 's' : ''} attended campaign events.</p>`;
            } else {
                analysis += `<p style="margin:12px 0; color:#64748b; line-height:1.6;"><strong style="color:#0f172a;">👥 Event Attendance:</strong> No attendance recorded yet. Data will appear once events are held and people check in.</p>`;
            }
            
            if (responses > 0) {
                analysis += `<p style="margin:12px 0; color:#1e293b; line-height:1.6;"><strong style="color:#0f172a;">📋 Survey Engagement:</strong> Received ${responses} survey response${responses !== 1 ? 's' : ''}. Average satisfaction rating: <strong>${m.avg_rating || 'N/A'}</strong> out of 5.</p>`;
            } else {
                analysis += `<p style="margin:12px 0; color:#64748b; line-height:1.6;"><strong style="color:#0f172a;">📋 Survey Engagement:</strong> No survey responses received yet. Responses will appear once surveys are published and completed.</p>`;
            }
            
            analysis += `<p style="margin:12px 0; color:#1e293b; line-height:1.6;"><strong style="color:#0f172a;">📊 Overall Engagement Rate:</strong> <strong style="color:#4c8a89; font-size:16px;">${engagementRate.toFixed(2)}%</strong> - This shows what percentage of people reached actually engaged with your campaign (by attending events or completing surveys).</p>`;
            analysis += `<p style="margin:12px 0; color:#1e293b; line-height:1.6;"><strong style="color:#0f172a;">🎯 Targeted Audience:</strong> Campaign targeted ${m.targeted_segments || 0} audience segment${(m.targeted_segments || 0) !== 1 ? 's' : ''} (different groups of people based on demographics or risk factors).</p>`;
            analysis += '</div>';
            container.innerHTML = analysis;
            
            // Performance chart
            if (attendance > 0 || responses > 0) {
                const ctx = document.getElementById('performanceChart');
                if (performanceChart) performanceChart.destroy();
                
                performanceChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Event Attendance', 'Survey Responses'],
                        datasets: [{
                            data: [attendance, responses],
                            backgroundColor: ['#4c8a89', '#667eea']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
                chartContainer.style.display = 'block';
                chartInstruction.style.display = 'block';
            } else {
                chartContainer.style.display = 'none';
                chartInstruction.style.display = 'none';
            }
            
            statusEl.textContent = '✓ Performance analysis completed successfully';
            statusEl.style.color = '#166534';
        } else {
            const emptyState = document.getElementById('analysisEmptyState');
            emptyState.style.display = 'block';
            emptyState.querySelector('p:last-child').innerHTML = 'No data loaded yet. Enter a campaign number above and click <strong>"Analyze Performance"</strong> to see detailed analysis.';
            statusEl.textContent = '⚠️ ' + (data.error || 'No data available for this campaign yet');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// ============================================
// PDF EXPORT WITH PASSWORD VERIFICATION
// ============================================

function showExportPasswordModal() {
    const cid = document.getElementById('export_campaign_id').value;
    if (!cid) {
        document.getElementById('exportStatus').textContent = '⚠️ Please select a campaign first';
        document.getElementById('exportStatus').style.color = '#dc2626';
        return;
    }
    
    document.getElementById('exportPassword').value = '';
    document.getElementById('exportPasswordError').style.display = 'none';
    document.getElementById('exportPasswordModal').style.display = 'block';
    document.getElementById('exportPassword').focus();
}

function closeExportPasswordModal() {
    document.getElementById('exportPasswordModal').style.display = 'none';
    document.getElementById('exportPassword').value = '';
    document.getElementById('exportPasswordError').style.display = 'none';
}

function togglePasswordVisibility() {
    const input = document.getElementById('exportPassword');
    const icon = document.getElementById('passwordToggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

async function verifyPasswordAndExport() {
    const password = document.getElementById('exportPassword').value;
    const errorDiv = document.getElementById('exportPasswordError');
    const confirmBtn = document.getElementById('exportConfirmBtn');
    
    if (!password) {
        errorDiv.textContent = 'Please enter your password';
        errorDiv.style.display = 'block';
        return;
    }
    
    // Get user email from JWT token
    let userEmail = '';
    try {
        const parts = token.split('.');
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        userEmail = payload.email || payload.sub || '';
    } catch (e) {
        errorDiv.textContent = 'Session error. Please log in again.';
        errorDiv.style.display = 'block';
        return;
    }
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    
    try {
        // Try verify-password endpoint first
        let verified = false;
        
        try {
            const verifyRes = await fetch(apiBase + '/api/v1/auth/verify-password', {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({ password: password })
            });
            
            if (verifyRes.ok) {
                const verifyData = await verifyRes.json();
                verified = verifyData.valid === true || verifyData.success === true;
            }
        } catch (verifyErr) {
            console.log('verify-password failed, trying login endpoint:', verifyErr);
        }
        
        // Fallback to login endpoint
        if (!verified) {
            try {
                const loginRes = await fetch(apiBase + '/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: userEmail, password: password })
                });
                
                const contentType = loginRes.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const loginData = await loginRes.json();
                    verified = loginRes.ok && loginData.token;
                } else if (loginRes.status >= 500) {
                    console.warn('Auth endpoint returned server error, allowing export for authenticated user');
                    verified = true;
                }
            } catch (loginErr) {
                console.log('login endpoint also failed:', loginErr);
                if (token) {
                    console.warn('Auth endpoints unavailable, allowing export for authenticated user');
                    verified = true;
                }
            }
        }
        
        if (verified) {
            closeExportPasswordModal();
            await generateImpactPDF();
        } else {
            errorDiv.textContent = 'Incorrect password. Please try again.';
            errorDiv.style.display = 'block';
        }
    } catch (error) {
        errorDiv.textContent = 'Verification failed: ' + error.message;
        errorDiv.style.display = 'block';
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-download"></i> Export PDF';
    }
}

// Generate Impact PDF with professional template
async function generateImpactPDF() {
    const cid = document.getElementById('export_campaign_id').value;
    const statusEl = document.getElementById('exportStatus');
    statusEl.textContent = 'Generating PDF report...';
    statusEl.style.color = '#64748b';
    
    try {
        // Fetch campaign data
        const campaignRes = await fetch(apiBase + '/api/v1/campaigns/' + cid, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const campaignData = await campaignRes.json();
        const campaign = campaignData.data || campaignData.campaign || {};
        
        // Fetch impact data
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (!res.ok || !data.data) {
            statusEl.textContent = '⚠️ ' + (data.error || 'No data available for this campaign yet');
            statusEl.style.color = '#dc2626';
            return;
        }
        
        const m = data.data;
        
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Colors
        const primaryColor = [76, 138, 137]; // #4c8a89
        const darkColor = [15, 23, 42]; // #0f172a
        const grayColor = [100, 116, 139]; // #64748b
        
        // Header with gradient-like background
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 45, 'F');
        
        // Header text
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('CAMPAIGN IMPACT REPORT', 105, 18, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Public Safety Campaign System', 105, 26, { align: 'center' });
        doc.text('Barangay Alertaraqc', 105, 32, { align: 'center' });
        // Use Asia/Manila timezone for report generation time
        const manilaTime = new Date().toLocaleString('en-US', { timeZone: 'Asia/Manila' });
        doc.text('Generated: ' + manilaTime, 105, 40, { align: 'center' });
        
        // Campaign Title
        doc.setTextColor(...darkColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text(campaign.title || `Campaign #${cid}`, 14, 58);
        
        // Divider line
        doc.setDrawColor(...primaryColor);
        doc.setLineWidth(0.5);
        doc.line(14, 62, 196, 62);
        
        // Campaign Details Section
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Campaign Details', 14, 72);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        const details = [
            ['Category:', campaign.category || 'N/A'],
            ['Status:', (campaign.status || 'N/A').toUpperCase()],
            ['Location:', campaign.location || 'N/A']
        ];
        
        let yPos = 80;
        details.forEach(([label, value]) => {
            doc.setFont('helvetica', 'bold');
            doc.text(label, 14, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(String(value), 50, yPos);
            yPos += 7;
        });
        
        // Impact Metrics Section
        yPos += 10;
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Impact Metrics', 14, yPos);
        
        yPos += 10;
        
        // Metrics table
        const metricsData = [
            ['Event Attendance', String(m.attendance_count || 0)],
            ['Survey Responses', String(m.survey_responses || 0)],
            ['Average Rating', m.avg_rating ? String(m.avg_rating) + ' / 5' : 'N/A'],
            ['Targeted Segments', String(m.targeted_segments || 0)],
            ['Engagement Rate', ((m.engagement_rate || 0) * 100).toFixed(2) + '%'],
            ['Response Rate', ((m.response_rate || 0) * 100).toFixed(2) + '%']
        ];
        
        doc.autoTable({
            startY: yPos,
            head: [['Metric', 'Value']],
            body: metricsData,
            theme: 'striped',
            headStyles: { fillColor: primaryColor },
            styles: { fontSize: 10 },
            margin: { left: 14, right: 14 }
        });
        
        // Performance Summary
        yPos = doc.lastAutoTable.finalY + 15;
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Performance Summary', 14, yPos);
        
        yPos += 8;
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        const attendance = m.attendance_count || 0;
        const responses = m.survey_responses || 0;
        const engagementRate = ((m.engagement_rate || 0) * 100).toFixed(2);
        
        let summaryText = `This campaign achieved an engagement rate of ${engagementRate}%. `;
        if (attendance > 0) {
            summaryText += `A total of ${attendance} participant(s) attended campaign events. `;
        }
        if (responses > 0) {
            summaryText += `${responses} survey response(s) were collected`;
            if (m.avg_rating) {
                summaryText += ` with an average satisfaction rating of ${m.avg_rating} out of 5`;
            }
            summaryText += '.';
        }
        
        const splitSummary = doc.splitTextToSize(summaryText, 180);
        doc.text(splitSummary, 14, yPos);
        
        // Footer
        const pageHeight = doc.internal.pageSize.height;
        doc.setFillColor(...primaryColor);
        doc.rect(0, pageHeight - 15, 210, 15, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(8);
        doc.text('Public Safety Campaign System - Confidential Report', 105, pageHeight - 7, { align: 'center' });
        
        // Save PDF
        const fileName = `Impact_Report_Campaign_${cid}_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        statusEl.textContent = '✓ PDF report exported successfully! The file has been downloaded to your computer.';
        statusEl.style.color = '#166534';
        
    } catch (err) {
        console.error('PDF generation error:', err);
        statusEl.textContent = '✗ Unable to export data. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
