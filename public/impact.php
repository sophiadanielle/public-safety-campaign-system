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
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="module-impact" data-module="impact">
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
        max-width: 1400px;
        margin: 0 auto;
        padding: 24px;
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
                <input id="campaign_id" type="number" value="1" placeholder="Enter the campaign number you want to review" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Need help?</strong> Don't know the campaign number? Go to the "Campaigns" page in the sidebar to see all campaigns and their numbers, or ask your administrator for assistance.</div>
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
            <p style="margin:0; font-size:14px; line-height:1.6;">Enter a campaign number above and click <strong>"View Campaign Performance"</strong> to see results. If you see this message after clicking, the campaign may be new or hasn't collected any data yet.</p>
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
            <strong>What this does:</strong> Create official evaluation reports for your campaigns. These reports can be saved, printed, or shared with supervisors. Reports include all key performance metrics in a professional format.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <input id="report_campaign_id" type="number" value="1" placeholder="Enter the campaign number you want to create a report for" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Tip:</strong> Select the campaign you want to review. You can find campaign numbers on the "Campaigns" page in the sidebar.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="generateReport()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-file-alt" style="margin-right:8px;"></i>Create Evaluation Report
                </button>
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
                <input id="metrics_campaign_id" type="number" value="1" placeholder="Enter the campaign number to view all performance metrics" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Tip:</strong> Enter the campaign number you want to review. All key performance metrics will be displayed below.</div>
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
                <input id="analysis_campaign_id" type="number" value="1" placeholder="Enter the campaign number you want to analyze" min="1" style="font-size:15px; padding:12px 16px;">
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
            <strong>What this does:</strong> Download your campaign's performance data as a spreadsheet file (CSV format). You can open this file in Excel or Google Sheets to create your own charts, share with others, or keep as a record. Useful for reports and presentations.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <input id="export_campaign_id" type="number" value="1" placeholder="Enter the campaign number you want to export data for" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Tip:</strong> Select the campaign you want to export. The data will be downloaded as a CSV file that you can open in Excel or Google Sheets.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="exportImpactData()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-download" style="margin-right:8px;"></i>Download Data (CSV)
                </button>
            </div>
        </div>
        <div class="status" id="exportStatus" style="margin-top:12px;"></div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
let chart;

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
        { key: 'reach', label: 'Total Reach', explanation: 'Number of notifications successfully sent to people' },
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

// Load on page load
loadImpact();

// Evaluation Reports
async function generateReport() {
    const cid = document.getElementById('report_campaign_id').value;
    const statusEl = document.getElementById('reportStatus');
    statusEl.textContent = 'Generating report...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/reports/generate/' + cid, {
            method: 'GET',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.file_path) {
            statusEl.textContent = '✓ Report generated successfully! You can view it in the list below.';
            statusEl.style.color = '#166534';
            loadReportList(cid);
        } else {
            statusEl.textContent = '⚠️ ' + (data.error || 'Unable to generate report. Please make sure the campaign number is correct and try again.');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function loadReportList(campaignId) {
    const container = document.getElementById('reportList');
    try {
        const res = await fetch(apiBase + '/api/v1/reports?campaign_id=' + campaignId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.reports && data.reports.length > 0) {
            let html = '<div style="margin-top:16px;"><h3 style="font-size:16px; color:#0f172a; margin-bottom:12px;">Generated Reports</h3>';
            html += '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f1f5f9;"><th style="padding:12px; text-align:left; font-weight:600; color:#475569;">Date Generated</th><th style="padding:12px; text-align:left; font-weight:600; color:#475569;">Actions</th></tr></thead><tbody>';
            data.reports.forEach(report => {
                const date = new Date(report.created_at || report.generated_at).toLocaleString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                html += `<tr style="border-bottom:1px solid #e2e8f0;"><td style="padding:12px; color:#1e293b;">${date}</td><td style="padding:12px;"><a href="${apiBase.replace('/api', '')}/${report.file_path}" target="_blank" class="btn btn-secondary" style="padding:6px 12px; font-size:14px;"><i class="fas fa-eye" style="margin-right:6px;"></i>View Report</a></td></tr>`;
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-file-alt"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No reports generated yet</p><p style="margin:0; font-size:14px; line-height:1.6;">No data loaded yet. Enter a campaign number above and click <strong>"Create Evaluation Report"</strong> to generate your first report for this campaign.</p></div>';
        }
    } catch (err) {
        container.innerHTML = '<p style="color:#dc2626;">Error loading reports: ' + err.message + '</p>';
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
            html += `<div class="metric-card"><div class="metric-label">Total Reach</div><div class="metric-value">${m.reach || 0}</div><div class="metric-explanation">Notifications successfully sent</div></div>`;
            html += `<div class="metric-card"><div class="metric-label">Failed Notifications</div><div class="metric-value">${m.notifications_failed || 0}</div><div class="metric-explanation">Notifications that could not be delivered</div></div>`;
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
            
            const reach = m.reach || 0;
            const attendance = m.attendance_count || 0;
            const responses = m.survey_responses || 0;
            const engagementRate = (m.engagement_rate || 0) * 100;
            
            if (reach > 0) {
                analysis += `<p style="margin:12px 0; color:#1e293b; line-height:1.6;"><strong style="color:#0f172a;">📢 Campaign Reach:</strong> Successfully sent ${reach} notification${reach !== 1 ? 's' : ''} to community members. ${m.notifications_failed || 0} notification${(m.notifications_failed || 0) !== 1 ? 's' : ''} failed to deliver.</p>`;
            } else {
                analysis += `<p style="margin:12px 0; color:#64748b; line-height:1.6;"><strong style="color:#0f172a;">📢 Campaign Reach:</strong> No notifications sent yet for this campaign.</p>`;
            }
            
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
            if (reach > 0 || attendance > 0 || responses > 0) {
                const ctx = document.getElementById('performanceChart');
                if (performanceChart) performanceChart.destroy();
                
                performanceChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Notifications Sent', 'Event Attendance', 'Survey Responses'],
                        datasets: [{
                            data: [reach, attendance, responses],
                            backgroundColor: ['#4c8a89', '#667eea', '#764ba2']
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

// Export Data
async function exportImpactData() {
    const cid = document.getElementById('export_campaign_id').value;
    const statusEl = document.getElementById('exportStatus');
    statusEl.textContent = 'Exporting data...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const m = data.data;
            
            // Create CSV content
            let csv = 'Metric,Value\n';
            csv += `Reach,${m.reach || 0}\n`;
            csv += `Failed Notifications,${m.notifications_failed || 0}\n`;
            csv += `Attendance Count,${m.attendance_count || 0}\n`;
            csv += `Survey Responses,${m.survey_responses || 0}\n`;
            csv += `Average Rating,${m.avg_rating || 'N/A'}\n`;
            csv += `Targeted Segments,${m.targeted_segments || 0}\n`;
            csv += `Engagement Rate,${((m.engagement_rate || 0) * 100).toFixed(2)}%\n`;
            csv += `Response Rate,${((m.response_rate || 0) * 100).toFixed(2)}%\n`;
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `impact_data_campaign_${cid}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            
            statusEl.textContent = '✓ Data exported successfully! The file has been downloaded to your computer.';
            statusEl.style.color = '#166534';
        } else {
            statusEl.textContent = '⚠️ ' + (data.error || 'No data available for this campaign yet');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Unable to export data. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
