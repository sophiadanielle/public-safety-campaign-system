<?php
$pageTitle = 'Events & Seminars';
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/module-sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="module-events" data-module="events">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    <?php 
    $moduleName = 'events';
    include __DIR__ . '/../sidebar/includes/module-sidebar.php'; 
    ?>
    
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
    
    .events-page {
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
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 16px;
        margin-top: 16px;
    }
    .form-field {
        display: flex;
        flex-direction: column;
    }
    .form-field label {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .form-field input,
    .form-field textarea,
    .form-field select {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .form-field input:focus,
    .form-field textarea:focus,
    .form-field select:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
    }
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f5f9;
    }
    .data-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 16px;
    }
    .data-table thead {
        background: #f8fafc;
    }
    .data-table th {
        padding: 12px;
        text-align: left;
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
        border-bottom: 2px solid #e2e8f0;
    }
    .data-table td {
        padding: 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #475569;
    }
    .data-table tbody tr:hover {
        background: #f8fafc;
    }
</style>

<div class="events-page">
    <div class="page-header">
        <h1>Events & Seminars</h1>
        <p>Schedule and manage campaign events, seminars, and workshops</p>
    </div>

    <section id="create-event" class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Create Event</h2>
        <div id="conflictWarning" style="display:none; background:#fef3c7; border:2px solid #f59e0b; border-radius:8px; padding:12px; margin-bottom:16px;">
            <strong style="color:#92400e;">⚠ Scheduling Conflicts Detected:</strong>
            <ul id="conflictList" style="margin:8px 0 0 0; padding-left:20px; color:#92400e;"></ul>
        </div>
        <form id="createForm" class="form-grid">
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Event Title *</label>
                <input id="event_title" type="text" placeholder="Fire Safety Seminar" list="event_title_suggestions" autocomplete="off" required>
                <datalist id="event_title_suggestions"></datalist>
            </div>
            <div class="form-field">
                <label>Event Type *</label>
                <select id="event_type" required>
                    <option value="seminar">Seminar</option>
                    <option value="drill">Drill</option>
                    <option value="workshop">Workshop</option>
                    <option value="orientation">Orientation</option>
                </select>
            </div>
            <div class="form-field">
                <label>Event Status</label>
                <select id="event_status">
                    <option value="draft">Draft</option>
                    <option value="scheduled">Scheduled</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-field">
                <label>Linked Campaign ID</label>
                <select id="linked_campaign_id">
                    <option value="">-- Select Campaign --</option>
                </select>
            </div>
            <div class="form-field">
                <label>Target Audience Profile</label>
                <select id="target_audience_profile_id" onchange="loadAudienceProfilePreview()">
                    <option value="">-- Select Audience Segment --</option>
                </select>
                <div id="audienceProfilePreview" style="display:none; margin-top:8px;"></div>
            </div>
            <div class="form-field">
                <label>Hazard Focus</label>
                <input id="hazard_focus" type="text" placeholder="e.g., fire, flood, earthquake" list="hazard_focus_suggestions" autocomplete="off">
                <datalist id="hazard_focus_suggestions"></datalist>
            </div>
            <div class="form-field">
                <label>Date *</label>
                <input id="date" type="date" required onchange="checkConflicts()">
            </div>
            <div class="form-field">
                <label>Start Time *</label>
                <input id="start_time" type="time" required onchange="checkConflicts()">
            </div>
            <div class="form-field">
                <label>End Time</label>
                <input id="end_time" type="time" onchange="checkConflicts()">
            </div>
            <div class="form-field">
                <label>Venue *</label>
                <input id="venue" type="text" placeholder="Barangay Hall" list="venue_suggestions" autocomplete="off" required onchange="checkConflicts()">
                <datalist id="venue_suggestions"></datalist>
            </div>
            <div class="form-field">
                <label>Location</label>
                <input id="location" type="text" placeholder="Address or location details" list="location_suggestions" autocomplete="off">
                <datalist id="location_suggestions"></datalist>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Event Description</label>
                <textarea id="event_description" rows="3" placeholder="Describe the event purpose, objectives, and key activities..."></textarea>
            </div>
            
            <div style="grid-column: 1 / -1; margin-top:16px; padding-top:16px; border-top:2px solid #f1f5f9;">
                <h3 style="font-size:16px; font-weight:600; color:#1e293b; margin-bottom:12px;">Resource Requirements</h3>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Transport Requirements</label>
                <textarea id="transport_requirements" rows="2" placeholder="e.g., 2 vehicles for materials transport, shuttle service for participants"></textarea>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Trainer Requirements</label>
                <textarea id="trainer_requirements" rows="2" placeholder="e.g., 1 certified fire safety instructor, 2 first aid trainers"></textarea>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Equipment Requirements</label>
                <textarea id="equipment_requirements" rows="2" placeholder="e.g., projector, sound system, fire extinguisher demo units"></textarea>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Volunteer Requirements</label>
                <textarea id="volunteer_requirements" rows="2" placeholder="e.g., 5 volunteers for registration, 3 for crowd control"></textarea>
            </div>
            
            <div style="grid-column: 1 / -1; margin-top:16px; padding-top:16px; border-top:2px solid #f1f5f9;">
                <h3 style="font-size:16px; font-weight:600; color:#1e293b; margin-bottom:12px;">Participants & Coordination</h3>
            </div>
            <div class="form-field">
                <label>Facilitator User IDs</label>
                <input id="facilitator_ids" type="text" placeholder="e.g., 1, 2, 3 (comma-separated)">
            </div>
            <div class="form-field">
                <label>Audience Segment IDs</label>
                <input id="segment_ids" type="text" placeholder="e.g., 1, 2, 3 (comma-separated)">
            </div>
        </form>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="createEvent()">Create Event</button>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>
    </section>

    <section id="agency-coordination" class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Agency Coordination</h2>
        <div class="form-field" style="margin-bottom:16px;">
            <label>Select Event</label>
            <select id="agency_event_select" onchange="loadAgencyCoordination()">
                <option value="">-- Select Event --</option>
            </select>
        </div>
        <div id="agencyCoordinationList" style="margin-bottom:16px;"></div>
        <button class="btn btn-secondary" onclick="showAddAgencyForm()">+ Add Agency Coordination</button>
        <div id="addAgencyForm" style="display:none; margin-top:16px; padding:16px; background:#f8fafc; border-radius:8px;">
            <form id="agencyForm" class="form-grid">
                <div class="form-field">
                    <label>Agency Type *</label>
                    <select id="agency_type" required>
                        <option value="">-- Select --</option>
                        <option value="police">Police</option>
                        <option value="fire_rescue">Fire & Rescue</option>
                        <option value="traffic">Traffic & Transport</option>
                        <option value="emergency_response">Emergency Response</option>
                        <option value="community_policing">Community Policing</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Agency Name *</label>
                    <input id="agency_name" type="text" required>
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Request Details</label>
                    <textarea id="request_details" rows="2"></textarea>
                </div>
            </form>
            <button class="btn btn-primary" style="margin-top:12px;" onclick="addAgencyCoordination()">Submit Request</button>
            <button class="btn btn-secondary" style="margin-top:12px; margin-left:8px;" onclick="hideAddAgencyForm()">Cancel</button>
        </div>
    </section>

    <section id="events-list" class="card" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 class="section-title" style="margin:0;">Events List</h2>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-secondary" onclick="switchView('list')" id="listViewBtn">📋 List</button>
                <button class="btn btn-secondary" onclick="switchView('calendar')" id="calendarViewBtn">📅 Calendar</button>
                <button class="btn btn-secondary" onclick="loadEvents()">🔄 Refresh</button>
        </div>
        </div>
        
        <div id="listView" style="display:block;">
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                            <th>Title</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                            <th>Venue</th>
                        <th>Campaign</th>
                        <th>Status</th>
                            <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="eventTable">
                        <tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Loading events...</td></tr>
                </tbody>
            </table>
            </div>
        </div>
        
        <div id="calendarView" style="display:none;">
            <div id="calendarContainer" style="min-height:600px; background:white; border-radius:8px; padding:16px;">
                <p style="text-align:center; color:#64748b; padding:40px;">Loading calendar...</p>
            </div>
        </div>
    </section>

    <section id="event-calendar" class="card" style="margin-bottom:24px; display:none;">
        <h2 class="section-title">Event Calendar</h2>
        <div id="fullCalendarContainer" style="min-height:600px;"></div>
    </section>
    
    <section id="event-detail" class="card" style="margin-bottom:24px; display:none;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 class="section-title" style="margin:0;">Event Details</h2>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-primary" onclick="showPostEventNotes()">Add Post-Event Notes</button>
                <button class="btn btn-secondary" onclick="hideEventDetail()">Close</button>
            </div>
        </div>
        <div id="eventDetailContent"></div>
        <div id="postEventNotesSection" style="display:none; margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
            <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Post-Event Notes</h3>
            <textarea id="post_event_notes" rows="4" style="width:100%; padding:10px 14px; border:2px solid #e2e8f0; border-radius:8px; font-size:14px; margin-bottom:12px;" placeholder="Enter post-event observations, outcomes, and lessons learned..."></textarea>
            <button class="btn btn-primary" onclick="savePostEventNotes()">Save Notes</button>
            <button class="btn btn-secondary" onclick="hidePostEventNotes()" style="margin-left:8px;">Cancel</button>
        </div>
    </section>

    <section id="attendance" class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Attendance Tracking & Check-in</h2>
        <div class="form-grid" style="margin-bottom:16px;">
            <div class="form-field">
                <label>Select Event</label>
                <select id="attendance_event_select" onchange="loadEventAttendance()">
                    <option value="">-- Select Event --</option>
                </select>
            </div>
        </div>
        <div id="attendanceSummary" style="display:none; background:#f8fafc; padding:16px; border-radius:8px; margin-bottom:16px;">
            <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px;">
                <div>
                    <strong style="color:#64748b; font-size:12px;">Total Attendance</strong>
                    <p style="margin:4px 0 0 0; font-size:20px; font-weight:600; color:#1e293b;" id="totalAttendance">0</p>
                </div>
                <div>
                    <strong style="color:#64748b; font-size:12px;">QR Check-ins</strong>
                    <p style="margin:4px 0 0 0; font-size:20px; font-weight:600; color:#1e293b;" id="qrCheckins">0</p>
                </div>
                <div>
                    <strong style="color:#64748b; font-size:12px;">Manual Check-ins</strong>
                    <p style="margin:4px 0 0 0; font-size:20px; font-weight:600; color:#1e293b;" id="manualCheckins">0</p>
                </div>
            </div>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">
            <div>
                <h3 style="font-size:16px; font-weight:600; color:#1e293b; margin-bottom:12px;">Attendance List</h3>
                <div id="attendanceList" style="min-height:200px; max-height:400px; overflow-y:auto;">
                    <p style="text-align:center; color:#64748b; padding:24px;">Select an event to view attendance</p>
                </div>
            </div>
            <div>
                <h3 style="font-size:16px; font-weight:600; color:#1e293b; margin-bottom:12px;">Quick Check-in</h3>
                <form id="checkinForm" class="form-grid">
            <div class="form-field">
                <label>Full Name *</label>
                <input id="checkin_name" type="text" required>
            </div>
            <div class="form-field">
                <label>Contact</label>
                <input id="checkin_contact" type="text" placeholder="Email or phone">
            </div>
                    <input type="hidden" id="checkin_event" value="">
        </form>
                <button class="btn btn-primary" style="margin-top:12px; width:100%;" onclick="checkIn(event)">Check In</button>
        <div class="status" id="checkinStatus" style="margin-top:12px;"></div>
            </div>
        </div>
    </section>
    
    <section id="event-reports" class="card">
        <h2 class="section-title">Event Reports</h2>
        <div class="form-grid" style="margin-bottom:16px;">
            <div class="form-field">
                <label>Select Event</label>
                <select id="report_event_select" onchange="loadEventReport()">
                    <option value="">-- Select Event --</option>
                </select>
            </div>
            <div class="form-field" style="align-self:flex-end;">
                <button class="btn btn-secondary" onclick="exportEventReport()">📥 Export Report</button>
            </div>
        </div>
        <div id="eventReportContent">
            <p style="text-align:center; color:#64748b; padding:24px;">Select an event to view reports</p>
        </div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

// Load campaigns for dropdown
async function loadCampaigns() {
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const select = document.getElementById('linked_campaign_id');
        if (select && data.data) {
            select.innerHTML = '<option value="">-- Select Campaign --</option>';
            data.data.forEach(c => {
                const option = document.createElement('option');
                option.value = c.id;
                option.textContent = `${c.id} - ${c.title || c.name || 'Untitled'}`;
                select.appendChild(option);
            });
        }
    } catch (err) {
        console.error('Error loading campaigns:', err);
    }
}

// Load audience segments for dropdown
async function loadAudienceSegments() {
    try {
        const res = await fetch(apiBase + '/api/v1/segments', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const select = document.getElementById('target_audience_profile_id');
        if (select && data.data) {
            select.innerHTML = '<option value="">-- Select Audience Segment --</option>';
            data.data.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = `${s.id} - ${s.name} (${s.risk_level || 'N/A'})`;
                select.appendChild(option);
            });
        }
    } catch (err) {
        console.error('Error loading audience segments:', err);
    }
}

// Load audience profile preview when selected
async function loadAudienceProfilePreview() {
    const segmentId = document.getElementById('target_audience_profile_id').value;
    const previewDiv = document.getElementById('audienceProfilePreview');
    if (!previewDiv) return;
    
    if (!segmentId) {
        previewDiv.style.display = 'none';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        if (res.ok && data.segment) {
            const s = data.segment;
            previewDiv.innerHTML = `
                <div style="background:#f8fafc; padding:12px; border-radius:6px; border-left:4px solid #4c8a89;">
                    <strong>Audience Profile:</strong> ${s.name}<br>
                    <small>Risk Level: <span style="color:${s.risk_level === 'high' ? '#dc2626' : s.risk_level === 'medium' ? '#f59e0b' : '#166534'}">${s.risk_level || 'N/A'}</span></small><br>
                    <small>Geographic Scope: ${s.geographic_scope || 'N/A'}</small><br>
                    <small>Sector Type: ${s.sector_type || 'N/A'}</small>
                </div>
            `;
            previewDiv.style.display = 'block';
        }
    } catch (err) {
        console.error('Error loading audience profile:', err);
    }
}

// Check for scheduling conflicts
async function checkConflicts() {
    const date = document.getElementById('date').value;
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const venue = document.getElementById('venue').value;
    
    if (!date || !startTime || !venue) {
        document.getElementById('conflictWarning').style.display = 'none';
        return;
    }
    
    try {
        const params = new URLSearchParams({ date, start_time: startTime, end_time: endTime || '', venue });
        const res = await fetch(apiBase + '/api/v1/events/check-conflicts?' + params.toString(), {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (data.conflicts && data.conflicts.length > 0) {
            const warning = document.getElementById('conflictWarning');
            const list = document.getElementById('conflictList');
            list.innerHTML = '';
            data.conflicts.forEach(c => {
                const li = document.createElement('li');
                li.textContent = c.message || `Conflict with event: ${c.conflicting_event_name || c.conflicting_event_id}`;
                list.appendChild(li);
            });
            warning.style.display = 'block';
        } else {
            document.getElementById('conflictWarning').style.display = 'none';
        }
    } catch (err) {
        console.error('Error checking conflicts:', err);
    }
}

async function createEvent() {
    const statusEl = document.getElementById('createStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    // Parse facilitator IDs
    const facilitatorIds = [];
    const facIdsText = document.getElementById('facilitator_ids').value.trim();
    if (facIdsText) {
        facilitatorIds.push(...facIdsText.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id)));
    }
    
    // Parse segment IDs
    const segmentIds = [];
    const segIdsText = document.getElementById('segment_ids').value.trim();
    if (segIdsText) {
        segmentIds.push(...segIdsText.split(',').map(id => parseInt(id.trim())).filter(id => !isNaN(id)));
    }
    
    const payload = {
        event_title: document.getElementById('event_title').value.trim(),
        event_type: document.getElementById('event_type').value,
        event_description: document.getElementById('event_description').value.trim() || null,
        hazard_focus: document.getElementById('hazard_focus').value.trim() || null,
        target_audience_profile_id: parseInt(document.getElementById('target_audience_profile_id').value) || null,
        linked_campaign_id: parseInt(document.getElementById('linked_campaign_id').value) || null,
        date: document.getElementById('date').value || null,
        start_time: document.getElementById('start_time').value || null,
        end_time: document.getElementById('end_time').value || null,
        venue: document.getElementById('venue').value.trim() || null,
        location: document.getElementById('location').value.trim() || null,
        event_status: document.getElementById('event_status').value,
        transport_requirements: document.getElementById('transport_requirements').value.trim() || null,
        trainer_requirements: document.getElementById('trainer_requirements').value.trim() || null,
        equipment_requirements: document.getElementById('equipment_requirements').value.trim() || null,
        volunteer_requirements: document.getElementById('volunteer_requirements').value.trim() || null,
        facilitator_ids: facilitatorIds,
        segment_ids: segmentIds
    };
    
    if (!payload.event_title) {
        statusEl.textContent = '✗ Error: Event Title is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Event created successfully! ID: ' + (data.id || data.event_id || 'N/A');
            statusEl.style.color = '#166534';
            document.getElementById('createForm').reset();
            document.getElementById('conflictWarning').style.display = 'none';
            loadEvents();
            if (data.conflicts && data.conflicts.length > 0) {
                statusEl.textContent += ' (⚠ Conflicts detected - see warning above)';
            }
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
            if (data.conflicts) {
                const warning = document.getElementById('conflictWarning');
                const list = document.getElementById('conflictList');
                list.innerHTML = '';
                data.conflicts.forEach(c => {
                    const li = document.createElement('li');
                    li.textContent = c.message || `Conflict: ${c.conflicting_event_name || c.conflicting_event_id}`;
                    list.appendChild(li);
                });
                warning.style.display = 'block';
            }
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function loadEvents() {
    const tbody = document.getElementById('eventTable');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">No events found. Create your first event!</td></tr>';
            return;
        }
        
        data.data.forEach(e => {
            const tr = document.createElement('tr');
            const eventTitle = e.event_title || e.event_name || e.name || 'Untitled';
            const date = e.date || (e.starts_at ? e.starts_at.split(' ')[0] : '-');
            const time = e.start_time || (e.starts_at ? e.starts_at.split(' ')[1] : '-');
            const status = e.event_status || e.status || 'draft';
            const statusColors = {
                'draft': { bg: '#e5e7eb', color: '#374151' },
                'scheduled': { bg: '#dbeafe', color: '#1e40af' },
                'confirmed': { bg: '#d1fae5', color: '#065f46' },
                'completed': { bg: '#d1fae5', color: '#065f46' },
                'cancelled': { bg: '#fee2e2', color: '#991b1b' }
            };
            const statusStyle = statusColors[status] || statusColors['draft'];
            tr.innerHTML = `
                <td>${e.event_id || e.id}</td>
                <td><strong>${eventTitle}</strong></td>
                <td><span style="background:#e0f2fe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-size:11px;">${e.event_type || 'seminar'}</span></td>
                <td>${date}</td>
                <td>${time}</td>
                <td>${e.venue || e.location || '-'}</td>
                <td>${e.linked_campaign_id || e.campaign_id || '-'}</td>
                <td><span style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:2px 8px; border-radius:4px; font-size:11px;">${status}</span></td>
                <td>
                    <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px;" onclick="viewEventDetails(${e.event_id || e.id})">View</button>
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Update dropdowns for attendance and reports
        updateEventDropdowns(data.data);
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#dc2626;">Error loading events: ' + err.message + '</td></tr>';
    }
}

function updateEventDropdowns(events) {
    const attendanceSelect = document.getElementById('attendance_event_select');
    const reportSelect = document.getElementById('report_event_select');
    const agencySelect = document.getElementById('agency_event_select');
    
    [attendanceSelect, reportSelect, agencySelect].forEach(select => {
        if (!select) return;
        const currentValue = select.value;
        select.innerHTML = '<option value="">-- Select Event --</option>';
        events.forEach(e => {
            const option = document.createElement('option');
            option.value = e.event_id || e.id;
            option.textContent = `${e.event_id || e.id} - ${e.event_title || e.event_name || e.name || 'Untitled'}`;
            select.appendChild(option);
        });
        if (currentValue) select.value = currentValue;
    });
}

let currentEventId = null;

// View event details
async function viewEventDetails(eventId) {
    currentEventId = eventId;
    const detailSection = document.getElementById('event-detail');
    const contentDiv = document.getElementById('eventDetailContent');
    
    if (!detailSection || !contentDiv) return;
    
    contentDiv.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading event details...</p>';
    detailSection.style.display = 'block';
    detailSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.event) {
            const e = data.event;
            const summary = data.attendance_summary || {};
            
            // Set post-event notes if they exist
            if (e.post_event_notes) {
                document.getElementById('post_event_notes').value = e.post_event_notes;
            }
            
            let html = `
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:16px; margin-bottom:24px;">
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Event Title</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600;">${e.event_title || e.event_name || 'N/A'}</p>
                    </div>
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Type</strong>
                        <p style="margin:4px 0 0 0;">${e.event_type || 'N/A'}</p>
                    </div>
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Status</strong>
                        <p style="margin:4px 0 0 0;"><span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-size:11px;">${e.event_status || 'draft'}</span></p>
                    </div>
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Date & Time</strong>
                        <p style="margin:4px 0 0 0;">${e.date || 'TBD'} ${e.start_time || ''} - ${e.end_time || ''}</p>
                    </div>
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Venue</strong>
                        <p style="margin:4px 0 0 0;">${e.venue || e.location || 'TBD'}</p>
                    </div>
                    <div>
                        <strong style="color:#64748b; font-size:12px;">Linked Campaign</strong>
                        <p style="margin:4px 0 0 0;">${e.campaign_title || (e.linked_campaign_id ? 'ID: ' + e.linked_campaign_id : 'None')}</p>
                    </div>
                </div>
                
                ${e.event_description ? `<div style="margin-bottom:16px;"><strong>Description:</strong><p>${e.event_description}</p></div>` : ''}
                ${e.hazard_focus ? `<div style="margin-bottom:16px;"><strong>Hazard Focus:</strong> ${e.hazard_focus}</div>` : ''}
                
                <div style="margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Resource Requirements</h3>
                    ${e.transport_requirements ? `<div style="margin-bottom:8px;"><strong>Transport:</strong> ${e.transport_requirements}</div>` : ''}
                    ${e.trainer_requirements ? `<div style="margin-bottom:8px;"><strong>Trainers:</strong> ${e.trainer_requirements}</div>` : ''}
                    ${e.equipment_requirements ? `<div style="margin-bottom:8px;"><strong>Equipment:</strong> ${e.equipment_requirements}</div>` : ''}
                    ${e.volunteer_requirements ? `<div style="margin-bottom:8px;"><strong>Volunteers:</strong> ${e.volunteer_requirements}</div>` : ''}
                </div>
                
                ${data.facilitators && data.facilitators.length > 0 ? `
                <div style="margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Facilitators</h3>
                    <ul style="margin:0; padding-left:20px;">
                        ${data.facilitators.map(f => `<li>${f.name || f.email || 'N/A'}</li>`).join('')}
                    </ul>
                </div>
                ` : ''}
                
                ${data.agency_coordination && data.agency_coordination.length > 0 ? `
                <div style="margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Agency Coordination</h3>
                    <table class="data-table" style="margin-top:12px;">
                        <thead>
                            <tr>
                                <th>Agency</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.agency_coordination.map(ac => `
                                <tr>
                                    <td>${ac.agency_name}</td>
                                    <td>${ac.agency_type}</td>
                                    <td><span style="background:#dbeafe; color:#1e40af; padding:2px 8px; border-radius:4px; font-size:11px;">${ac.request_status}</span></td>
                                    <td>${ac.request_details || ac.confirmation_details || '-'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
                ` : ''}
                
                <div style="margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Attendance Summary</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap:16px;">
                        <div>
                            <strong style="color:#64748b; font-size:12px;">Total</strong>
                            <p style="margin:4px 0 0 0; font-size:20px; font-weight:600;">${summary.total_attendance || 0}</p>
                        </div>
                        <div>
                            <strong style="color:#64748b; font-size:12px;">QR Check-ins</strong>
                            <p style="margin:4px 0 0 0; font-size:20px; font-weight:600;">${summary.qr_checkins || 0}</p>
                        </div>
                        <div>
                            <strong style="color:#64748b; font-size:12px;">Manual</strong>
                            <p style="margin:4px 0 0 0; font-size:20px; font-weight:600;">${summary.manual_checkins || 0}</p>
                        </div>
                    </div>
                </div>
                
                ${e.post_event_notes ? `
                <div style="margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                    <h3 style="font-size:16px; font-weight:600; margin-bottom:12px;">Post-Event Notes</h3>
                    <p style="background:#f8fafc; padding:12px; border-radius:6px; white-space:pre-wrap;">${e.post_event_notes}</p>
                </div>
                ` : ''}
            `;
            
            contentDiv.innerHTML = html;
        } else {
            contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error loading event details</p>';
        }
    } catch (err) {
        contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

function showPostEventNotes() {
    if (!currentEventId) {
        alert('Please view an event first');
        return;
    }
    document.getElementById('postEventNotesSection').style.display = 'block';
}

function hidePostEventNotes() {
    document.getElementById('postEventNotesSection').style.display = 'none';
}

async function savePostEventNotes() {
    if (!currentEventId) return;
    
    const notes = document.getElementById('post_event_notes').value.trim();
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + currentEventId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ post_event_notes: notes })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Post-event notes saved successfully');
            hidePostEventNotes();
            viewEventDetails(currentEventId); // Refresh view
        } else {
            alert('Error: ' + (data.error || 'Failed to save notes'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

function hideEventDetail() {
    document.getElementById('event-detail').style.display = 'none';
}

// Switch between list and calendar view
function switchView(view) {
    const listView = document.getElementById('listView');
    const calendarView = document.getElementById('calendarView');
    const listBtn = document.getElementById('listViewBtn');
    const calendarBtn = document.getElementById('calendarViewBtn');
    const calendarSection = document.getElementById('event-calendar');
    
    if (view === 'list') {
        listView.style.display = 'block';
        calendarView.style.display = 'none';
        if (calendarSection) calendarSection.style.display = 'none';
        if (listBtn) listBtn.classList.add('active');
        if (calendarBtn) calendarBtn.classList.remove('active');
        loadEvents();
    } else {
        listView.style.display = 'none';
        calendarView.style.display = 'block';
        if (calendarSection) calendarSection.style.display = 'block';
        if (listBtn) listBtn.classList.remove('active');
        if (calendarBtn) calendarBtn.classList.add('active');
        renderCalendar();
    }
}

// Render calendar view
async function renderCalendar(containerId = null) {
    const container = containerId ? document.getElementById(containerId) : document.getElementById('calendarContainer');
    const fullCalendarContainer = document.getElementById('fullCalendarContainer');
    
    const containers = [];
    if (container) containers.push(container);
    if (fullCalendarContainer) containers.push(fullCalendarContainer);
    
    if (containers.length === 0) return;
    
    containers.forEach(c => {
        c.innerHTML = '<p style="text-align:center; color:#64748b; padding:40px;">Loading calendar...</p>';
    });
    
    try {
        const today = new Date();
        const startDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        const endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0];
        
        const res = await fetch(apiBase + '/api/v1/events/calendar?start=' + startDate + '&end=' + endDate, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        let html = '';
        if (data.events && data.events.length > 0) {
            // Group events by date
            const eventsByDate = {};
            data.events.forEach(event => {
                const date = event.start.split('T')[0];
                if (!eventsByDate[date]) eventsByDate[date] = [];
                eventsByDate[date].push(event);
            });
            
            // Create calendar grid
            html = '<div style="display:grid; grid-template-columns: repeat(7, 1fr); gap: 8px; margin-bottom: 16px;">';
            const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            days.forEach(day => {
                html += `<div style="padding:8px; text-align:center; font-weight:600; background:#f8fafc; border-radius:4px;">${day}</div>`;
            });
            html += '</div>';
            
            // Render events by date
            html += '<div style="display:flex; flex-direction:column; gap:12px;">';
            Object.keys(eventsByDate).sort().forEach(date => {
                const dateObj = new Date(date);
                html += `<div style="border:1px solid #e2e8f0; border-radius:8px; padding:12px; background:white;">`;
                html += `<strong style="display:block; margin-bottom:8px; color:#1e293b;">${dateObj.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</strong>`;
                eventsByDate[date].forEach(event => {
                    const statusColors = {
                        'draft': '#e5e7eb',
                        'scheduled': '#3b82f6',
                        'confirmed': '#10b981',
                        'completed': '#10b981',
                        'cancelled': '#ef4444'
                    };
                    const hazardColors = {
                        'fire': '#ef4444',
                        'flood': '#3b82f6',
                        'earthquake': '#f59e0b',
                        'health': '#10b981',
                        'traffic': '#8b5cf6'
                    };
                    const bgColor = statusColors[event.status] || hazardColors[event.hazard_focus] || '#6b7280';
                    html += `
                        <div style="padding:8px; margin-bottom:4px; background:${bgColor}15; border-left:4px solid ${bgColor}; border-radius:4px;">
                            <strong style="color:#1e293b;">${event.title}</strong>
                            <div style="font-size:12px; color:#64748b; margin-top:4px;">
                                ${event.start.split('T')[1]?.substring(0, 5) || ''} - ${event.end ? event.end.split('T')[1]?.substring(0, 5) : 'N/A'} | ${event.venue || 'TBD'}
                            </div>
                            <div style="font-size:11px; margin-top:4px;">
                                <span style="background:${bgColor}; color:white; padding:2px 6px; border-radius:3px;">${event.status}</span>
                                ${event.hazard_focus ? `<span style="background:#f3f4f6; color:#374151; padding:2px 6px; border-radius:3px; margin-left:4px;">${event.hazard_focus}</span>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
            });
            html += '</div>';
        } else {
            html = '<p style="text-align:center; color:#64748b; padding:40px;">No events found for this month.</p>';
        }
        
        containers.forEach(c => {
            c.innerHTML = html;
        });
    } catch (err) {
        const errorHtml = '<p style="text-align:center; color:#dc2626; padding:40px;">Error loading calendar: ' + err.message + '</p>';
        containers.forEach(c => {
            c.innerHTML = errorHtml;
        });
    }
}

// Load event attendance
async function loadEventAttendance() {
    const eventId = document.getElementById('attendance_event_select').value;
    if (!eventId) {
        document.getElementById('attendanceSummary').style.display = 'none';
        document.getElementById('attendanceList').innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Select an event to view attendance</p>';
        return;
    }
    
    document.getElementById('checkin_event').value = eventId;
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId + '/attendance', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            // Update summary
            const summary = {
                total: data.data.length,
                qr: data.data.filter(a => a.checkin_method === 'QR').length,
                manual: data.data.filter(a => a.checkin_method === 'manual').length
            };
            document.getElementById('totalAttendance').textContent = summary.total;
            document.getElementById('qrCheckins').textContent = summary.qr;
            document.getElementById('manualCheckins').textContent = summary.manual;
            document.getElementById('attendanceSummary').style.display = 'block';
            
            // Update list
            if (data.data.length > 0) {
                let html = '<div style="overflow-x:auto;"><table class="data-table"><thead><tr><th>Name</th><th>Check-in Time</th><th>Contact</th><th>Method</th></tr></thead><tbody>';
                data.data.forEach(a => {
                    const name = a.full_name || a.participant_identifier || '-';
                    const time = a.checkin_timestamp ? new Date(a.checkin_timestamp).toLocaleString() : '-';
                    const method = a.checkin_method === 'QR' ? '<span style="background:#dbeafe; color:#1e40af; padding:2px 6px; border-radius:3px; font-size:11px;">QR</span>' : '<span style="background:#f3f4f6; color:#374151; padding:2px 6px; border-radius:3px; font-size:11px;">Manual</span>';
                    html += `<tr><td><strong>${name}</strong></td><td>${time}</td><td>${a.contact || '-'}</td><td>${method}</td></tr>`;
                });
                html += '</tbody></table></div>';
                document.getElementById('attendanceList').innerHTML = html;
            } else {
                document.getElementById('attendanceList').innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No attendance records found</p>';
            }
        }
    } catch (err) {
        document.getElementById('attendanceList').innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

async function checkIn(e) {
    if (e) e.preventDefault();
    const statusEl = document.getElementById('checkinStatus');
    statusEl.textContent = 'Processing...';
    statusEl.style.color = '#64748b';
    
    const eventId = document.getElementById('checkin_event').value;
    if (!eventId) {
        statusEl.textContent = '✗ Error: Please select an event first';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    const payload = {
        full_name: document.getElementById('checkin_name').value.trim(),
        contact: document.getElementById('checkin_contact').value.trim() || null,
        checkin_method: 'manual'
    };
    
    if (!payload.full_name) {
        statusEl.textContent = '✗ Error: Full name is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId + '/attendance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Check-in successful!';
            statusEl.style.color = '#166534';
            document.getElementById('checkin_name').value = '';
            document.getElementById('checkin_contact').value = '';
            loadEventAttendance();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Check-in failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Load event report
async function loadEventReport() {
    const eventId = document.getElementById('report_event_select').value;
    const container = document.getElementById('eventReportContent');
    
    if (!eventId) {
        container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Select an event to view reports</p>';
        return;
    }
    
    container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading report...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.event) {
            const e = data.event;
            const summary = data.attendance_summary || {};
            
            let html = `
                <div style="background:#f8fafc; padding:20px; border-radius:8px; margin-bottom:16px;">
                    <h3 style="margin-top:0;">${e.event_title || e.event_name || 'Untitled'}</h3>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-top:16px;">
                        <div><strong>Date:</strong> ${e.date || 'TBD'}</div>
                        <div><strong>Time:</strong> ${e.start_time || 'TBD'} - ${e.end_time || 'TBD'}</div>
                        <div><strong>Venue:</strong> ${e.venue || 'TBD'}</div>
                        <div><strong>Status:</strong> ${e.event_status || 'draft'}</div>
                        <div><strong>Attendance:</strong> ${summary.total_attendance || 0}</div>
                    </div>
                </div>
                ${e.post_event_notes ? `<div style="background:#f8fafc; padding:20px; border-radius:8px;"><strong>Post-Event Notes:</strong><p>${e.post_event_notes}</p></div>` : ''}
            `;
            container.innerHTML = html;
        }
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

function exportEventReport() {
    const eventId = document.getElementById('report_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    window.location.href = apiBase + '/api/v1/events/' + eventId + '/attendance/export?token=' + encodeURIComponent(token);
}

// Agency coordination functions
async function loadAgencyCoordination() {
    const eventId = document.getElementById('agency_event_select').value;
    const container = document.getElementById('agencyCoordinationList');
    
    if (!eventId) {
        container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Select an event to view agency coordination</p>';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.agency_coordination && data.agency_coordination.length > 0) {
            let html = '<table class="data-table"><thead><tr><th>Agency</th><th>Type</th><th>Status</th><th>Requested</th><th>Details</th></tr></thead><tbody>';
            data.agency_coordination.forEach(ac => {
                const statusColors = {
                    'requested': { bg: '#fef3c7', color: '#92400e' },
                    'confirmed': { bg: '#d1fae5', color: '#065f46' },
                    'fulfilled': { bg: '#dbeafe', color: '#1e40af' },
                    'cancelled': { bg: '#fee2e2', color: '#991b1b' }
                };
                const statusStyle = statusColors[ac.request_status] || statusColors['requested'];
                html += `
                    <tr>
                        <td><strong>${ac.agency_name}</strong></td>
                        <td>${ac.agency_type}</td>
                        <td><span style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:2px 8px; border-radius:4px; font-size:11px;">${ac.request_status}</span></td>
                        <td>${ac.requested_at ? new Date(ac.requested_at).toLocaleDateString() : '-'}</td>
                        <td>${ac.request_details || ac.confirmation_details || '-'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No agency coordination records found for this event</p>';
        }
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

function showAddAgencyForm() {
    const eventId = document.getElementById('agency_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    document.getElementById('addAgencyForm').style.display = 'block';
}

function hideAddAgencyForm() {
    document.getElementById('addAgencyForm').style.display = 'none';
    document.getElementById('agencyForm').reset();
}

async function addAgencyCoordination() {
    const eventId = document.getElementById('agency_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    
    const payload = {
        agency_type: document.getElementById('agency_type').value,
        agency_name: document.getElementById('agency_name').value.trim(),
        request_details: document.getElementById('request_details').value.trim() || null
    };
    
    if (!payload.agency_type || !payload.agency_name) {
        alert('Agency type and name are required');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId + '/agency-coordination', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            alert('Agency coordination request created successfully');
            hideAddAgencyForm();
            loadAgencyCoordination();
        } else {
            alert('Error: ' + (data.error || 'Failed'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Autocomplete functions
let autocompleteTimeouts = {};

function setupAutocomplete(inputId, endpoint, datalistId) {
    const input = document.getElementById(inputId);
    const datalist = document.getElementById(datalistId);
    if (!input || !datalist) return;
    
    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Clear previous timeout
        if (autocompleteTimeouts[inputId]) {
            clearTimeout(autocompleteTimeouts[inputId]);
        }
        
        // Clear datalist if query is too short
        if (query.length < 2) {
            datalist.innerHTML = '';
            return;
        }
        
        // Debounce API call
        autocompleteTimeouts[inputId] = setTimeout(async () => {
            try {
                const res = await fetch(apiBase + '/api/v1/autocomplete/' + endpoint + '?q=' + encodeURIComponent(query), {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                const data = await res.json();
                
                if (data.data && Array.isArray(data.data)) {
                    datalist.innerHTML = '';
                    data.data.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item;
                        datalist.appendChild(option);
                    });
                }
            } catch (err) {
                console.error('Autocomplete error:', err);
            }
        }, 300);
    });
}

// Setup autocomplete for requirement fields (from existing events)
// Note: Datalist doesn't work with textarea, so we'll show suggestions in a tooltip/helper text
function setupRequirementAutocomplete(textareaId, fieldName) {
    const textarea = document.getElementById(textareaId);
    if (!textarea) return;
    
    // Load suggestions from existing events on focus and show as helper text
    let suggestionsLoaded = false;
    textarea.addEventListener('focus', async function() {
        if (suggestionsLoaded) return;
        
        try {
            const res = await fetch(apiBase + '/api/v1/events?limit=50', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const data = await res.json();
            
            if (data.events && Array.isArray(data.events)) {
                const suggestions = new Set();
                data.events.forEach(event => {
                    const value = event[fieldName];
                    if (value && value.trim()) {
                        // Extract first line or first 50 chars as suggestion
                        const suggestion = value.trim().split('\n')[0].substring(0, 50);
                        if (suggestion.length > 0) {
                            suggestions.add(suggestion);
                        }
                    }
                });
                
                if (suggestions.size > 0) {
                    const helperText = document.createElement('small');
                    helperText.style.display = 'block';
                    helperText.style.marginTop = '4px';
                    helperText.style.color = '#64748b';
                    helperText.style.fontSize = '12px';
                    helperText.textContent = 'Tip: Similar entries from past events: ' + Array.from(suggestions).slice(0, 3).join(', ') + (suggestions.size > 3 ? '...' : '');
                    textarea.parentNode.appendChild(helperText);
                }
            }
            suggestionsLoaded = true;
        } catch (err) {
            console.error('Error loading suggestions:', err);
        }
    });
}

// Handle sidebar navigation for event calendar
document.addEventListener('DOMContentLoaded', function() {
    // Show calendar section when sidebar link is clicked
    const calendarLink = document.querySelector('.module-sidebar-link[href="#event-calendar"]');
    if (calendarLink) {
        calendarLink.addEventListener('click', function(e) {
            const calendarSection = document.getElementById('event-calendar');
            if (calendarSection) {
                // Show the section
                calendarSection.style.display = 'block';
                
                // Render calendar if not already rendered
                setTimeout(() => {
                    const fullCalendarContainer = document.getElementById('fullCalendarContainer');
                    if (fullCalendarContainer && (!fullCalendarContainer.innerHTML || fullCalendarContainer.innerHTML.includes('Loading'))) {
                        renderCalendar('fullCalendarContainer');
                    }
                }, 100);
            }
        });
    }
    
    // Setup autocomplete for form fields
    setupAutocomplete('event_title', 'event-titles', 'event_title_suggestions');
    setupAutocomplete('hazard_focus', 'hazard-focus', 'hazard_focus_suggestions');
    setupAutocomplete('venue', 'venues', 'venue_suggestions');
    setupAutocomplete('location', 'locations', 'location_suggestions');
    
    // Setup requirement field suggestions (helper text, since datalist doesn't work with textarea)
    setupRequirementAutocomplete('transport_requirements', 'transport_requirements');
    setupRequirementAutocomplete('trainer_requirements', 'trainer_requirements');
    setupRequirementAutocomplete('equipment_requirements', 'equipment_requirements');
    setupRequirementAutocomplete('volunteer_requirements', 'volunteer_requirements');
});

// Initialize on page load
loadCampaigns();
loadAudienceSegments();
loadEvents();
</script>
    </div>
    </main>
</body>
</html>
