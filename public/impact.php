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
        margin-bottom: 32px;
    }
    .page-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
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
</style>

<main class="impact-page">
    <div class="page-header">
        <h1>Impact Monitoring & Evaluation</h1>
        <p>Track campaign performance, engagement metrics, and effectiveness</p>
    </div>

    <section class="card">
        <h2 class="section-title">Campaign Impact Dashboard</h2>
        <div class="form-grid" style="grid-template-columns: 200px auto;">
            <div class="form-field">
                <label>Campaign ID</label>
                <input id="campaign_id" type="number" value="1">
            </div>
            <div class="form-field" style="align-items:flex-end;">
                <button class="btn btn-primary" onclick="loadImpact()">Load Impact Data</button>
            </div>
        </div>
        
        <div class="metrics-grid" id="metricsCards"></div>
        
        <div class="chart-container">
            <canvas id="chart" height="100"></canvas>
        </div>
        
        <div class="status" id="status" style="margin-top:20px;"></div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
let chart;

async function loadImpact() {
    const cid = document.getElementById('campaign_id').value;
    const statusEl = document.getElementById('status');
    statusEl.textContent = 'Loading...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns/' + cid + '/impact', { 
            headers: { 'Authorization': 'Bearer ' + token } 
        });
        const data = await res.json();
        
        if (!data.data) {
            statusEl.textContent = '✗ Error: ' + (data.error || 'No data available');
            statusEl.style.color = '#dc2626';
            return;
        }
        
        const m = data.data;
        renderCards(m);
        renderChart(m);
        statusEl.textContent = '✓ Impact data loaded successfully';
        statusEl.style.color = '#166534';
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

function renderCards(m) {
    const container = document.getElementById('metricsCards');
    container.innerHTML = '';
    
    const metrics = [
        { key: 'reach', label: 'Total Reach' },
        { key: 'attendance_count', label: 'Attendance' },
        { key: 'survey_responses', label: 'Survey Responses' },
        { key: 'avg_rating', label: 'Avg Rating' },
        { key: 'engagement_rate', label: 'Engagement Rate' }
    ];
    
    metrics.forEach(metric => {
        const value = m[metric.key] || 0;
        const div = document.createElement('div');
        div.className = 'metric-card';
        div.innerHTML = `
            <div class="metric-label">${metric.label}</div>
            <div class="metric-value">${typeof value === 'number' && value < 1 ? (value * 100).toFixed(1) + '%' : value}</div>
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
</script>
    </main>
</body>
</html>
