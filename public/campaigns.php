<?php
$pageTitle = 'Campaign Planning';
include __DIR__ . '/../header/includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css" rel="stylesheet">
<style>
    .campaign-page {
        max-width: 1600px;
        margin: 0 auto 48px;
        padding: 120px 24px 0;
        background: linear-gradient(to bottom, #f8fafc 0%, #ffffff 100%);
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
    
    @media (max-width: 768px) {
        .campaign-page {
            padding: 100px 16px 0;
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

    <!-- Planning Form -->
    <section class="card">
        <div class="section-header">
            <h2 class="section-title analytics-accent">Plan New Campaign</h2>
        </div>
        
        <form id="planningForm">
            <div class="form-grid">
                <div class="form-field">
                    <label for="title">Campaign Title *</label>
                    <input id="title" type="text" placeholder="Fire Safety Week 2025" required>
            </div>
                <div class="form-field">
                    <label for="status">Status</label>
                    <select id="status">
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
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
                <div class="form-field">
                    <label for="location">Location</label>
                    <input id="location" type="text" placeholder="Barangay Hall, Quezon City">
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
                    <label for="barangay_zones">Barangay Target Zones (comma-separated)</label>
                    <input id="barangay_zones" type="text" placeholder="Barangay 1, Barangay 2, Barangay 3">
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
                    <label for="assigned_staff">Assigned Staff (JSON array of names/IDs)</label>
                    <textarea id="assigned_staff" rows="2" placeholder='["John Doe", "Jane Smith"]'></textarea>
                </div>
                <div class="form-field full-width">
                    <label for="materials_json">Materials (JSON object)</label>
                    <textarea id="materials_json" rows="2" placeholder='{"posters": 100, "flyers": 500, "banners": 5}'></textarea>
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
    <section class="card">
        <div class="section-header">
            <h2 class="section-title analytics-accent">AI-Powered Deployment Optimization</h2>
        </div>
        <div class="automl-panel">
            <h3>Google AutoML Predictions</h3>
            <p style="margin: 0 0 20px; opacity: 0.95;">Get AI-suggested optimal dates and times for campaign deployment based on historical data, audience engagement patterns, and performance analytics.</p>
            <div class="form-grid" style="grid-template-columns: 1fr auto; gap: 16px;">
                <div class="form-field">
                    <label for="automl_campaign_id" style="color: white; opacity: 0.95;">Campaign ID</label>
                    <input id="automl_campaign_id" type="number" placeholder="Enter campaign ID" style="background: rgba(255,255,255,0.95); border: 2px solid rgba(255,255,255,0.3); color: #0f172a;">
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
                    <strong>🔍 Features Used:</strong>
                    <span id="pred_features" style="font-size: 12px;">-</span>
                </div>
                <div class="prediction-item">
                    <strong>💡 Recommendation:</strong>
                    <span id="pred_recommendation" style="font-size: 13px;">Based on historical performance data</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline & Calendar Tabs -->
    <section class="card">
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
    <section class="card">
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
    <section class="card">
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
                    <th>Status</th>
                    <th>Start</th>
                    <th>End</th>
                    <th>Location</th>
                    <th>Budget</th>
                </tr>
            </thead>
            <tbody id="campaignTable">
                <tr><td colspan="7" style="text-align:center; padding:24px;">Loading...</td></tr>
            </tbody>
        </table>
    </section>

    <!-- Target Segments -->
    <section class="card">
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
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<script>
    const token = localStorage.getItem('jwtToken') || '';
let calendar, gantt;
let activeCampaignId = null;
let allCampaigns = [];

// Form handling
document.getElementById('planningForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('createStatus');
    statusEl.style.display = 'block';
    statusEl.className = 'status-text';
    statusEl.textContent = 'Creating...';
    
    try {
        const barangayZones = document.getElementById('barangay_zones').value.split(',').map(s => s.trim()).filter(Boolean);
        let assignedStaff = [];
        try {
            assignedStaff = JSON.parse(document.getElementById('assigned_staff').value || '[]');
        } catch (e) {}
        let materialsJson = {};
        try {
            materialsJson = JSON.parse(document.getElementById('materials_json').value || '{}');
        } catch (e) {}
        
        const payload = {
            title: document.getElementById('title').value.trim(),
            description: document.getElementById('description').value.trim(),
            status: document.getElementById('status').value,
            start_date: document.getElementById('start_date').value || null,
            end_date: document.getElementById('end_date').value || null,
            objectives: document.getElementById('objectives').value.trim() || null,
            location: document.getElementById('location').value.trim() || null,
            assigned_staff: assignedStaff,
            barangay_target_zones: barangayZones,
            budget: parseFloat(document.getElementById('budget').value) || null,
            staff_count: parseInt(document.getElementById('staff_count').value) || null,
            materials_json: materialsJson,
        };
        
        if (!payload.title) {
            statusEl.textContent = 'Title is required.';
            statusEl.className = 'status-text error';
            return;
        }
        
        const res = await fetch('/api/v1/campaigns', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        if (!res.ok) {
            statusEl.textContent = data.error || 'Failed to create campaign.';
            statusEl.className = 'status-text error';
            return;
        }
        
        statusEl.textContent = 'Campaign created successfully!';
        statusEl.className = 'status-text success';
        clearForm();
        loadCampaigns();
        refreshGantt();
        if (calendar) calendar.refetchEvents();
    } catch (err) {
        statusEl.textContent = 'Network error. Please try again.';
        statusEl.className = 'status-text error';
    }
});

function clearForm() {
    document.getElementById('planningForm').reset();
    document.getElementById('createStatus').style.display = 'none';
}

// AutoML
async function getAutoMLPrediction() {
    const cid = parseInt(document.getElementById('automl_campaign_id').value);
    if (!cid) {
        alert('Please enter a campaign ID');
        return;
    }
    
    const resultDiv = document.getElementById('automlResult');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = '<div style="text-align:center; padding:20px;">Loading prediction...</div>';
    
    try {
        const res = await fetch('/api/v1/automl/predict', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ campaign_id: cid })
        });
        const data = await res.json();
        
        if (data.error) {
            resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5;">
                <strong>Error:</strong>
                <span>${data.error}</span>
            </div>`;
            return;
        }
        
        const pred = data.prediction || {};
        const suggestedDateTime = pred.suggested_datetime || new Date().toISOString().slice(0, 16).replace('T', ' ');
        const confidence = pred.confidence_score ? (pred.confidence_score * 100).toFixed(1) + '%' : 'N/A';
        const features = pred.features_used || {};
        const featuresText = Object.keys(features).length > 0 
            ? Object.entries(features).map(([k, v]) => `${k}: ${v}`).join(', ')
            : 'Historical data, engagement patterns, time-based trends';
        
        let recommendation = 'Optimal deployment time based on historical performance';
        if (pred.confidence_score && pred.confidence_score > 0.8) {
            recommendation = 'High confidence recommendation - Strong historical match';
        } else if (pred.confidence_score && pred.confidence_score > 0.6) {
            recommendation = 'Moderate confidence - Good historical indicators';
        } else if (pred.confidence_score) {
            recommendation = 'Lower confidence - Consider additional factors';
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
                <strong>🔍 Features Used:</strong>
                <span style="font-size: 12px;">${featuresText}</span>
            </div>
            <div class="prediction-item">
                <strong>💡 Recommendation:</strong>
                <span style="font-size: 13px;">${recommendation}</span>
            </div>
        `;
    } catch (err) {
        resultDiv.innerHTML = `<div class="prediction-item" style="color: #fee2e2; border-color: #fca5a5;">
            <strong>Error:</strong>
            <span>Failed to get prediction: ${err.message}</span>
        </div>`;
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
                const res = await fetch('/api/v1/campaigns', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const data = await res.json();
                const events = (data.data || [])
                    .filter(c => c.start_date)
                    .map(c => ({
                        id: c.id,
                        title: c.title,
                        start: c.start_date,
                        end: c.end_date ? new Date(new Date(c.end_date).getTime() + 86400000) : new Date(new Date(c.start_date).getTime() + 86400000),
                        backgroundColor: getStatusColor(c.status),
                        borderColor: getStatusColor(c.status),
                        textColor: '#fff',
                        extendedProps: {
                            status: c.status,
                            location: c.location,
                            budget: c.budget
                        }
                    }));
                successCallback(events);
            } catch (err) {
                failureCallback(err);
            }
        },
        eventClick: function(info) {
            const event = info.event;
            const extended = event.extendedProps;
            alert(`Campaign: ${event.title}\nStatus: ${extended.status}\nLocation: ${extended.location || 'N/A'}\nBudget: ${extended.budget ? '₱' + parseFloat(extended.budget).toLocaleString() : 'N/A'}`);
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
        const res = await fetch('/api/v1/campaigns', {
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
        const res = await fetch('/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        allCampaigns = data.data || [];
        
        if (!allCampaigns.length) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px;">No campaigns yet.</td></tr>';
            return;
        }
        
        tbody.innerHTML = '';
        const select = document.getElementById('active_campaign');
        select.innerHTML = '';
        
        allCampaigns.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${c.id}</td>
                <td>${c.title || ''}</td>
                <td><span class="badge ${c.status || 'draft'}">${(c.status || 'draft').charAt(0).toUpperCase() + (c.status || 'draft').slice(1)}</span></td>
                <td>${c.start_date || '-'}</td>
                <td>${c.end_date || '-'}</td>
                <td>${c.location || '-'}</td>
                <td>${c.budget ? '₱' + parseFloat(c.budget).toLocaleString('en-US', {minimumFractionDigits: 2}) : '-'}</td>
            `;
            tbody.appendChild(tr);
            
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = `${c.id} - ${c.title || ''}`;
            select.appendChild(opt);
        });
        
        if (!activeCampaignId && allCampaigns.length) {
            activeCampaignId = allCampaigns[0].id;
            select.value = activeCampaignId;
        }
        
        refreshGantt();
        loadResources();
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:24px; color:#dc2626;">Failed to load campaigns.</td></tr>';
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
        const res = await fetch('/api/v1/campaigns/' + cid + '/segments', {
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
        const res = await fetch('/api/v1/campaigns/' + cid + '/segments', {
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
    loadCampaigns();
loadResources();
setTimeout(() => {
    if (document.getElementById('gantt-tab').classList.contains('active')) {
        refreshGantt();
    }
}, 500);
</script>
