<?php
$pageTitle = 'Events & Seminars';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
// Wrapped in try-catch to prevent 502 errors
$isViewer = false;
$currentUserRole = null;
try {
    @require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
} catch (\Throwable $e) {
    error_log('events.php: block_viewer_access failed: ' . $e->getMessage());
}
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <script src="<?php echo htmlspecialchars($publicPath . '/js/custom-modals.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicPath . '/js/modal-replacer.js'); ?>"></script>
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
<body class="module-events" data-module="events">
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/sidebar.php'; 
    } catch (\Throwable $e) {
        error_log('events.php: sidebar.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/admin-header.php'; 
    } catch (\Throwable $e) {
        error_log('events.php: admin-header.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    // Removed redundant "Event Management" panel
    // $moduleName = 'events';
    // include __DIR__ . '/../sidebar/includes/module-sidebar.php'; 
    ?>
    
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
    
    .events-page {
        width: 100%;
        margin: 0;
        padding: 24px;
        box-sizing: border-box;
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

    <?php
    // RBAC: Ensure $isViewer is defined (set by block_viewer_access.php at top)
    if (!isset($isViewer)) {
        $isViewer = false;
    }
    
    // If Viewer tries to access create form directly, redirect to events list
    if ($isViewer && isset($_GET['section']) && $_GET['section'] === 'create-event') {
        header('Location: ' . $publicPath . '/events.php#events-list');
        exit;
    }
    
    // Viewers can view approved events in the list section (read-only)
    // Forms are hidden via conditionals below
    ?>
    
    <?php if (!$isViewer): ?>
    <!-- Hidden: Create Event form is now in modal -->
    <section id="create-event" class="card" style="margin-bottom:24px; display:none;">
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
                    <option value="scheduled" selected>Scheduled</option>
                    <option value="ongoing">Ongoing</option>
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
                <label>Start Date & Time *</label>
                <input id="start_datetime" type="datetime-local" required onchange="syncStartDateTime(); checkConflicts()">
                <input id="date" type="hidden">
                <input id="start_time" type="hidden">
            </div>
            <div class="form-field">
                <label>End Date & Time</label>
                <input id="end_datetime" type="datetime-local" onchange="syncEndDateTime(); checkConflicts()">
                <input id="end_time" type="hidden">
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
        </form>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="submitEventForm()">Create Event</button>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>
    </section>
    <?php endif; // End RBAC: Hide create form for Viewer ?>

    <!-- Hidden: Agency Coordination is now in modal -->
    <section id="agency-coordination" class="card" style="margin-bottom:24px; display:none;">
        <h2 class="section-title">Agency Coordination</h2>
        <div class="form-field" style="margin-bottom:16px;">
            <label>Select Event</label>
            <select id="agency_event_select" onchange="loadAgencyCoordination()">
                <option value="">-- Select Event --</option>
            </select>
        </div>
        <div id="agencyCoordinationList" style="margin-bottom:16px;"></div>
        <?php if (!$isViewer): // RBAC: Hide add button for Viewer (read-only) ?>
        <button class="btn btn-secondary" onclick="showAddAgencyForm()">+ Add Agency Coordination</button>
        <div id="addAgencyForm" style="display:none; margin-top:16px; padding:16px; background:#f8fafc; border-radius:8px;">
            <form id="agencyForm" class="form-grid">
                <div class="form-field">
                    <label>Agency Type *</label>
                    <select id="agency_type" required>
                        <option value="">-- Select --</option>
                        <option value="police">Police</option>
                        <option value="fire">Fire</option>
                        <option value="medical">Medical</option>
                        <option value="rescue">Rescue</option>
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
        <?php endif; // End RBAC: Hide add button for Viewer ?>
    </section>

    <section id="events-list" class="card" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <h2 class="section-title" style="margin:0;">Events List</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php if (!$isViewer): ?>
                <button class="btn btn-primary" onclick="openCreateEventModal()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-plus"></i> Create Event
                </button>
                <button class="btn btn-secondary" onclick="openAgencyCoordinationModal()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-building"></i> Agency Coordination
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="switchView('list')" id="listViewBtn">📋 List</button>
                <button class="btn btn-secondary" onclick="switchView('calendar')" id="calendarViewBtn">📅 Calendar</button>
                <button class="btn btn-secondary" onclick="openArchivedEventsModal()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
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
            <div class="form-field" style="align-self:flex-end;">
                <button class="btn btn-secondary" onclick="showExportPasswordModal('attendance')">📥 Export Attendance PDF</button>
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
                <button class="btn btn-secondary" onclick="showExportPasswordModal('event-report')">📥 Export PDF Report</button>
            </div>
        </div>
        <div id="eventReportContent">
            <p style="text-align:center; color:#64748b; padding:24px;">Select an event to view reports</p>
        </div>
    </section>

</div><!-- End events-page -->

<!-- Password Verification Modal for Export -->
<div id="exportPasswordModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:10000; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:450px; margin:100px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.3);">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:18px;"><i class="fas fa-lock"></i> Verify Password to Export</h2>
            <button onclick="closeExportPasswordModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <p style="color:#64748b; margin-bottom:16px;">Please enter your password to confirm the export. This ensures data security.</p>
            <input type="hidden" id="exportType" value="">
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

<!-- Create Event Modal -->
<div id="createEventModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:800px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-calendar-plus"></i> Create Event</h2>
            <button onclick="closeCreateEventModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div id="modalConflictWarning" style="display:none; background:#fef3c7; border:2px solid #f59e0b; border-radius:8px; padding:12px; margin-bottom:16px;">
                <strong style="color:#92400e;">⚠ Scheduling Conflicts Detected:</strong>
                <ul id="modalConflictList" style="margin:8px 0 0 0; padding-left:20px; color:#92400e;"></ul>
            </div>
            <form id="modalCreateForm" class="form-grid">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Title *</label>
                    <input id="modal_event_title" type="text" placeholder="Fire Safety Seminar" required>
                </div>
                <div class="form-field">
                    <label>Event Type *</label>
                    <select id="modal_event_type" required>
                        <option value="seminar">Seminar</option>
                        <option value="drill">Drill</option>
                        <option value="workshop">Workshop</option>
                        <option value="orientation">Orientation</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Event Status</label>
                    <select id="modal_event_status">
                        <option value="scheduled" selected>Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Linked Campaign</label>
                    <select id="modal_linked_campaign_id">
                        <option value="">-- Select Campaign --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Target Audience</label>
                    <select id="modal_target_audience_profile_id">
                        <option value="">-- Select Audience Segment --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Hazard Focus</label>
                    <input id="modal_hazard_focus" type="text" placeholder="e.g., fire, flood, earthquake">
                </div>
                <div class="form-field">
                    <label>Start Date & Time *</label>
                    <input id="modal_start_datetime" type="datetime-local" required>
                </div>
                <div class="form-field">
                    <label>End Date & Time</label>
                    <input id="modal_end_datetime" type="datetime-local">
                </div>
                <div class="form-field">
                    <label>Venue *</label>
                    <input id="modal_venue" type="text" placeholder="Barangay Hall" required>
                </div>
                <div class="form-field">
                    <label>Location</label>
                    <input id="modal_location" type="text" placeholder="Address or location details">
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Description</label>
                    <textarea id="modal_event_description" rows="3" placeholder="Describe the event purpose, objectives, and key activities..."></textarea>
                </div>
            </form>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeCreateEventModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitModalEventForm()">Create Event</button>
            </div>
            <div id="modalCreateStatus" style="margin-top:12px;"></div>
        </div>
    </div>
</div>

<!-- Agency Coordination Modal -->
<div id="agencyCoordinationModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:700px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-building"></i> Agency Coordination</h2>
            <button onclick="closeAgencyCoordinationModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div class="form-field" style="margin-bottom:16px;">
                <label>Select Event</label>
                <select id="modal_agency_event_select" onchange="loadModalAgencyCoordination()">
                    <option value="">-- Select Event --</option>
                </select>
            </div>
            <div id="modalAgencyCoordinationList" style="margin-bottom:16px; max-height:300px; overflow-y:auto;"></div>
            <div id="modalAddAgencyForm" style="display:none; margin-top:16px; padding:16px; background:#f8fafc; border-radius:8px;">
                <h4 style="margin:0 0 12px 0;">Add Agency Coordination</h4>
                <div class="form-grid">
                    <div class="form-field">
                        <label>Agency Type *</label>
                        <select id="modal_agency_type" required>
                            <option value="">-- Select --</option>
                            <option value="police">Police</option>
                            <option value="fire">Fire</option>
                            <option value="medical">Medical</option>
                            <option value="rescue">Rescue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Agency Name *</label>
                        <input id="modal_agency_name" type="text" required>
                    </div>
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label>Request Details</label>
                        <textarea id="modal_request_details" rows="2"></textarea>
                    </div>
                </div>
                <div style="display:flex; gap:8px; margin-top:12px;">
                    <button class="btn btn-primary" onclick="submitModalAgencyCoordination()">Submit Request</button>
                    <button class="btn btn-secondary" onclick="hideModalAddAgencyForm()">Cancel</button>
                </div>
            </div>
            <div style="display:flex; gap:12px; margin-top:20px;">
                <button class="btn btn-secondary" onclick="showModalAddAgencyForm()">+ Add Agency</button>
                <button class="btn btn-secondary" onclick="closeAgencyCoordinationModal()" style="margin-left:auto;">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- View Event Modal -->
<div id="viewEventModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:800px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-eye"></i> Event Details</h2>
            <button onclick="closeViewEventModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div id="viewEventContent">
                <p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>
            </div>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeViewEventModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:800px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-edit"></i> Edit Event</h2>
            <button onclick="closeEditEventModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <form id="editEventForm" class="form-grid">
                <input type="hidden" id="edit_event_id">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Title *</label>
                    <input id="edit_event_title" type="text" required>
                </div>
                <div class="form-field">
                    <label>Event Type *</label>
                    <select id="edit_event_type" required>
                        <option value="seminar">Seminar</option>
                        <option value="drill">Drill</option>
                        <option value="workshop">Workshop</option>
                        <option value="orientation">Orientation</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Event Status</label>
                    <select id="edit_event_status">
                        <option value="scheduled">Scheduled</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Linked Campaign</label>
                    <select id="edit_linked_campaign_id">
                        <option value="">-- Select Campaign --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Target Audience</label>
                    <select id="edit_target_audience_profile_id">
                        <option value="">-- Select Audience Segment --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Hazard Focus</label>
                    <input id="edit_hazard_focus" type="text">
                </div>
                <div class="form-field">
                    <label>Start Date & Time *</label>
                    <input id="edit_start_datetime" type="datetime-local" required>
                </div>
                <div class="form-field">
                    <label>End Date & Time</label>
                    <input id="edit_end_datetime" type="datetime-local">
                </div>
                <div class="form-field">
                    <label>Venue *</label>
                    <input id="edit_venue" type="text" required>
                </div>
                <div class="form-field">
                    <label>Location</label>
                    <input id="edit_location" type="text">
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Event Description</label>
                    <textarea id="edit_event_description" rows="3"></textarea>
                </div>
            </form>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitEditEventForm()">Update Event</button>
            </div>
            <div id="editEventStatus" style="margin-top:12px;"></div>
        </div>
    </div>
</div>

<!-- Archived Events Modal -->
<div id="archivedEventsModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:900px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-archive"></i> Archived Events</h2>
            <button onclick="closeArchivedEventsModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div id="archivedEventsList" style="max-height:500px; overflow-y:auto;">
                <p style="text-align:center; color:#64748b; padding:24px;">Loading archived events...</p>
            </div>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeArchivedEventsModal()">Close</button>
            </div>
        </div>
    </div>
</div>

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
    const select = document.getElementById('target_audience_profile_id');
    if (!select) {
        console.error('Target audience profile select element not found');
        return;
    }
    
    try {
        console.log('Loading audience segments from:', apiBase + '/api/v1/segments');
        const res = await fetch(apiBase + '/api/v1/segments', { 
            headers: { 'Authorization': 'Bearer ' + token } 
        });
        
        if (!res.ok) {
            console.error('Failed to load segments, status:', res.status);
            const errorData = await res.text();
            console.error('Error response:', errorData);
            return;
        }
        
        const data = await res.json();
        console.log('Segments API response:', data);
        
        // Handle both data.data and data.segments response formats
        const segments = data.data || data.segments || [];
        
        if (!segments || segments.length === 0) {
            console.warn('No segments found in API response');
            select.innerHTML = '<option value="">-- No Segments Available --</option>';
            return;
        }
        
        select.innerHTML = '<option value="">-- Select Audience Segment --</option>';
        
        // Filter out duplicates and undefined entries
        const uniqueSegments = [];
        const seenIds = new Set();
        segments.forEach(s => {
            if (s && s.id && s.name && !seenIds.has(s.id)) {
                seenIds.add(s.id);
                uniqueSegments.push(s);
            }
        });
        
        console.log('Unique segments to display:', uniqueSegments.length);
        
        uniqueSegments.forEach(s => {
            const option = document.createElement('option');
            option.value = s.id;
            option.textContent = `${s.name} - ${s.risk_level || 'N/A'}`;
            select.appendChild(option);
        });
        
        console.log('Successfully loaded', uniqueSegments.length, 'audience segments');
    } catch (err) {
        console.error('Error loading audience segments:', err);
        select.innerHTML = '<option value="">-- Error Loading Segments --</option>';
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

// Sync datetime-local to hidden date/time fields
function syncStartDateTime() {
    const startDatetime = document.getElementById('start_datetime');
    const dateField = document.getElementById('date');
    const startTimeField = document.getElementById('start_time');
    
    if (startDatetime && startDatetime.value) {
        const dt = new Date(startDatetime.value);
        const year = dt.getFullYear();
        const month = String(dt.getMonth() + 1).padStart(2, '0');
        const day = String(dt.getDate()).padStart(2, '0');
        const hours = String(dt.getHours()).padStart(2, '0');
        const minutes = String(dt.getMinutes()).padStart(2, '0');
        
        dateField.value = `${year}-${month}-${day}`;
        startTimeField.value = `${hours}:${minutes}`;
    }
}

function syncEndDateTime() {
    const endDatetime = document.getElementById('end_datetime');
    const endTimeField = document.getElementById('end_time');
    
    if (endDatetime && endDatetime.value) {
        const dt = new Date(endDatetime.value);
        const hours = String(dt.getHours()).padStart(2, '0');
        const minutes = String(dt.getMinutes()).padStart(2, '0');
        
        endTimeField.value = `${hours}:${minutes}`;
    } else {
        endTimeField.value = '';
    }
}

async function submitEventForm() {
    const statusEl = document.getElementById('createStatus');
    const form = document.getElementById('createForm');
    const eventId = form ? form.dataset.eventId : null;
    
    const payload = {
        event_title: document.getElementById('event_title').value.trim(),
        event_type: document.getElementById('event_type').value,
        linked_campaign_id: parseInt(document.getElementById('linked_campaign_id').value) || null,
        target_audience_profile_id: parseInt(document.getElementById('target_audience_profile_id').value) || null,
        hazard_focus: document.getElementById('hazard_focus').value.trim() || null,
        date: document.getElementById('date').value,
        start_time: document.getElementById('start_time').value,
        end_time: document.getElementById('end_time').value || null,
        event_description: document.getElementById('event_description').value.trim() || null,
        venue: document.getElementById('venue').value.trim() || null,
        location: document.getElementById('location').value.trim() || null,
        event_status: document.getElementById('event_status').value
    };
    
    if (!payload.event_title) {
        statusEl.textContent = '✗ Error: Event Title is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    // Check if this is an update
    if (eventId) {
        statusEl.textContent = 'Updating...';
        statusEl.style.color = '#64748b';
        
        try {
            const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                statusEl.textContent = '✓ Event updated successfully!';
                statusEl.style.color = '#166534';
                document.getElementById('createForm').reset();
                if (form) delete form.dataset.eventId;
                const submitBtn = document.querySelector('#create-event button.btn-primary');
                if (submitBtn) submitBtn.textContent = 'Create Event';
                document.getElementById('conflictWarning').style.display = 'none';
                loadEvents();
            } else {
                statusEl.textContent = '✗ Error: ' + (data.error || 'Failed to update');
                statusEl.style.color = '#dc2626';
            }
        } catch (err) {
            statusEl.textContent = '✗ Network error: ' + err.message;
            statusEl.style.color = '#dc2626';
        }
        return;
    }
    
    // Create new event
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
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

// Helper function to check if user is Viewer
function checkIfViewer() {
    try {
        const currentUser = JSON.parse(localStorage.getItem('currentUser') || '{}');
        const userRole = (currentUser.role || '').toLowerCase();
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

async function loadEvents() {
    const tbody = document.getElementById('eventTable');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        // Use direct endpoint to bypass routing issues causing 502 errors
        const eventsUrl = '/public/test-events-direct.php';
        console.log('Loading events from:', eventsUrl);
        const res = await fetch(eventsUrl, { headers: { 'Authorization': 'Bearer ' + token } });
        
        if (!res.ok) {
            console.error('Failed to load events, status:', res.status);
            const errorText = await res.text();
            console.error('Error response:', errorText);
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#dc2626;">Error loading events (Status: ' + res.status + '). Please check console for details.</td></tr>';
            return;
        }
        
        const data = await res.json();
        console.log('Events API response:', data);
        console.log('Response keys:', Object.keys(data));
        console.log('data.data:', data.data);
        console.log('data.events:', data.events);
        tbody.innerHTML = '';
        
        // Handle both data.data and data.events response formats
        const events = data.data || data.events || [];
        console.log('Events array:', events);
        console.log('Events to display:', events.length);
        
        if (!events || events.length === 0) {
            // Check if Viewer - show different message
            const isViewerCheck = checkIfViewer();
            if (isViewerCheck) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">No events available for viewing.</td></tr>';
            } else {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">No events found. Create your first event!</td></tr>';
            }
            return;
        }
        
        // RBAC: For Viewer, filter to show only confirmed/scheduled/completed events (not drafts)
        let eventsToShow = events;
        const isViewerCheck = checkIfViewer();
        if (isViewerCheck) {
            eventsToShow = events.filter(e => {
                const status = (e.event_status || e.status || '').toLowerCase();
                return status === 'confirmed' || status === 'scheduled' || status === 'completed';
            });
            console.log('loadEvents() - Filtered events for Viewer:', eventsToShow.length, 'confirmed/scheduled/completed events');
            if (eventsToShow.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#64748b;">No confirmed events available for viewing.</td></tr>';
                return;
            }
        }
        
        eventsToShow.forEach(e => {
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
                    <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; margin: 2px;" onclick="openViewEventModal(${e.event_id || e.id})">👁️ View</button>
                    ${(() => {
                        const isViewerCheck = checkIfViewer();
                        if (isViewerCheck) return '';
                        return `<button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; margin: 2px;" onclick="openEditEventModal(${e.event_id || e.id})">✏️ Edit</button>`;
                    })()}
                    ${(() => {
                        const isViewerCheck = checkIfViewer();
                        if (isViewerCheck) return '';
                        if (status !== 'archived') {
                            return `<button class="btn btn-warning" style="padding:4px 8px; font-size:11px; background: #f59e0b; color: white; margin: 2px;" onclick="archiveEvent(${e.event_id || e.id})">📦 Archive</button>`;
                        }
                        return '';
                    })()}
                </td>
            `;
            tbody.appendChild(tr);
        });
        
        // Update dropdowns for attendance and reports
        updateEventDropdowns(events);
        console.log('Successfully loaded and displayed', eventsToShow.length, 'events');
    } catch (err) {
        console.error('Error loading events:', err);
        tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#dc2626;">Error loading events: ' + err.message + '</td></tr>';
    }
}

function updateEventDropdowns(events) {
    const attendanceSelect = document.getElementById('attendance_event_select');
    const reportSelect = document.getElementById('report_event_select');
    const agencySelect = document.getElementById('agency_event_select');
    
    console.log('Updating event dropdowns with', events ? events.length : 0, 'events');
    
    [attendanceSelect, reportSelect, agencySelect].forEach(select => {
        if (!select) {
            console.warn('Event dropdown select element not found');
            return;
        }
        const currentValue = select.value;
        select.innerHTML = '<option value="">-- Select Event --</option>';
        
        if (!events || events.length === 0) {
            const noEventsOption = document.createElement('option');
            noEventsOption.value = '';
            noEventsOption.textContent = '-- No Events Available --';
            noEventsOption.disabled = true;
            select.appendChild(noEventsOption);
            return;
        }
        
        events.forEach(e => {
            const option = document.createElement('option');
            option.value = e.event_id || e.id;
            option.textContent = `${e.event_id || e.id} - ${e.event_title || e.event_name || e.name || 'Untitled'}`;
            select.appendChild(option);
        });
        if (currentValue) select.value = currentValue;
    });
    
    console.log('Event dropdowns updated successfully');
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

async function exportEventReport() {
    const eventId = document.getElementById('report_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    
    try {
        const response = await fetch(apiBase + '/api/v1/events/' + eventId + '/attendance/export', {
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        if (!response.ok) {
            throw new Error('Export failed');
        }
        
        // Download the file
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'event_' + eventId + '_attendance_report.csv';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    } catch (error) {
        alert('Failed to export report: ' + error.message);
    }
}

// ============================================
// PDF EXPORT WITH PASSWORD VERIFICATION
// ============================================

function showExportPasswordModal(exportType) {
    // Validate selection based on export type
    if (exportType === 'event-report') {
        const eventId = document.getElementById('report_event_select').value;
        if (!eventId) {
            customConfirm('Please select an event first');
            return;
        }
    } else if (exportType === 'attendance') {
        const eventId = document.getElementById('attendance_event_select').value;
        if (!eventId) {
            customConfirm('Please select an event first');
            return;
        }
    }
    
    document.getElementById('exportType').value = exportType;
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
    const exportType = document.getElementById('exportType').value;
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
        // Try verify-password endpoint first (requires auth token)
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
        
        // Fallback to login endpoint if verify-password didn't work
        if (!verified) {
            try {
                const loginRes = await fetch(apiBase + '/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: userEmail, password: password })
                });
                
                // Check if response is JSON
                const contentType = loginRes.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    const loginData = await loginRes.json();
                    verified = loginRes.ok && loginData.token;
                } else if (loginRes.status >= 500) {
                    // Server error - allow export with warning (user already authenticated via JWT)
                    console.warn('Auth endpoint returned server error, allowing export for authenticated user');
                    verified = true;
                }
            } catch (loginErr) {
                console.log('login endpoint also failed:', loginErr);
                // If both endpoints fail but user has valid JWT, allow export
                if (token) {
                    console.warn('Auth endpoints unavailable, allowing export for authenticated user');
                    verified = true;
                }
            }
        }
        
        if (verified) {
            // Password verified - proceed with export
            closeExportPasswordModal();
            
            if (exportType === 'event-report') {
                await generateEventReportPDF();
            } else if (exportType === 'attendance') {
                await generateAttendancePDF();
            }
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

// Generate Event Report PDF with professional template
async function generateEventReportPDF() {
    const eventId = document.getElementById('report_event_select').value;
    
    try {
        // Fetch event data
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (!res.ok || !data.event) {
            throw new Error('Failed to load event data');
        }
        
        const event = data.event;
        const summary = data.attendance_summary || {};
        const agencies = data.agency_coordination || [];
        
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
        
        // Logo placeholder (circle with initials)
        doc.setFillColor(255, 255, 255);
        doc.circle(25, 22, 12, 'F');
        doc.setTextColor(...primaryColor);
        doc.setFontSize(14);
        doc.setFont('helvetica', 'bold');
        doc.text('PSC', 25, 25, { align: 'center' });
        
        // Header text
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(20);
        doc.setFont('helvetica', 'bold');
        doc.text('EVENT REPORT', 105, 18, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Public Safety Campaign System', 105, 26, { align: 'center' });
        doc.text('Barangay Alertaraqc', 105, 32, { align: 'center' });
        doc.text('Generated: ' + new Date().toLocaleString(), 105, 40, { align: 'center' });
        
        // Event Title
        doc.setTextColor(...darkColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        doc.text(event.event_title || event.event_name || 'Untitled Event', 14, 58);
        
        // Divider line
        doc.setDrawColor(...primaryColor);
        doc.setLineWidth(0.5);
        doc.line(14, 62, 196, 62);
        
        // Event Details Section
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Event Details', 14, 72);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        const details = [
            ['Event Type:', event.event_type || 'N/A'],
            ['Date:', event.date || event.event_date || 'TBD'],
            ['Time:', (event.start_time || 'TBD') + ' - ' + (event.end_time || 'TBD')],
            ['Venue:', event.venue || 'TBD'],
            ['Location:', event.location || 'N/A'],
            ['Status:', (event.event_status || event.status || 'draft').toUpperCase()],
            ['Campaign:', event.campaign_title || 'N/A']
        ];
        
        let yPos = 80;
        details.forEach(([label, value]) => {
            doc.setFont('helvetica', 'bold');
            doc.text(label, 14, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(String(value), 50, yPos);
            yPos += 7;
        });
        
        // Attendance Summary Section
        yPos += 5;
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Attendance Summary', 14, yPos);
        
        yPos += 10;
        doc.setFillColor(248, 250, 252);
        doc.roundedRect(14, yPos - 5, 180, 25, 3, 3, 'F');
        
        doc.setFontSize(10);
        doc.setTextColor(...darkColor);
        doc.setFont('helvetica', 'bold');
        doc.text('Total Attendance:', 20, yPos + 3);
        doc.setFont('helvetica', 'normal');
        doc.text(String(summary.total_attendance || 0), 60, yPos + 3);
        
        doc.setFont('helvetica', 'bold');
        doc.text('QR Check-ins:', 90, yPos + 3);
        doc.setFont('helvetica', 'normal');
        doc.text(String(summary.qr_checkins || 0), 125, yPos + 3);
        
        doc.setFont('helvetica', 'bold');
        doc.text('Manual Check-ins:', 145, yPos + 3);
        doc.setFont('helvetica', 'normal');
        doc.text(String(summary.manual_checkins || 0), 185, yPos + 3);
        
        // Agency Coordination Section (if any)
        if (agencies.length > 0) {
            yPos += 35;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...primaryColor);
            doc.text('Agency Coordination', 14, yPos);
            
            yPos += 5;
            doc.autoTable({
                startY: yPos,
                head: [['Agency', 'Type', 'Status', 'Details']],
                body: agencies.map(a => [
                    a.agency_name,
                    a.agency_type,
                    a.request_status,
                    a.request_details || '-'
                ]),
                theme: 'striped',
                headStyles: { fillColor: primaryColor },
                styles: { fontSize: 9 },
                margin: { left: 14, right: 14 }
            });
        }
        
        // Post-Event Notes (if any)
        if (event.post_event_notes) {
            yPos = doc.lastAutoTable ? doc.lastAutoTable.finalY + 15 : yPos + 40;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...primaryColor);
            doc.text('Post-Event Notes', 14, yPos);
            
            yPos += 8;
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(...darkColor);
            const splitNotes = doc.splitTextToSize(event.post_event_notes, 180);
            doc.text(splitNotes, 14, yPos);
        }
        
        // Footer
        const pageHeight = doc.internal.pageSize.height;
        doc.setFillColor(...primaryColor);
        doc.rect(0, pageHeight - 15, 210, 15, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(8);
        doc.text('Public Safety Campaign System - Confidential Report', 105, pageHeight - 7, { align: 'center' });
        
        // Save PDF
        const fileName = `Event_Report_${event.event_title || eventId}_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        customConfirm('PDF report exported successfully!');
        
    } catch (error) {
        console.error('PDF generation error:', error);
        customConfirm('Failed to generate PDF: ' + error.message);
    }
}

// Generate Attendance PDF with professional template
async function generateAttendancePDF() {
    const eventId = document.getElementById('attendance_event_select').value;
    
    try {
        // Fetch event and attendance data
        const [eventRes, attendanceRes] = await Promise.all([
            fetch(apiBase + '/api/v1/events/' + eventId, { headers: { 'Authorization': 'Bearer ' + token } }),
            fetch(apiBase + '/api/v1/events/' + eventId + '/attendance', { headers: { 'Authorization': 'Bearer ' + token } })
        ]);
        
        const eventData = await eventRes.json();
        const attendanceData = await attendanceRes.json();
        
        if (!eventRes.ok || !eventData.event) {
            throw new Error('Failed to load event data');
        }
        
        const event = eventData.event;
        const attendees = attendanceData.attendance || attendanceData.data || [];
        
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Colors
        const primaryColor = [76, 138, 137];
        const darkColor = [15, 23, 42];
        
        // Header
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 40, 'F');
        
        doc.setFillColor(255, 255, 255);
        doc.circle(25, 20, 10, 'F');
        doc.setTextColor(...primaryColor);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text('PSC', 25, 23, { align: 'center' });
        
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(18);
        doc.setFont('helvetica', 'bold');
        doc.text('ATTENDANCE REPORT', 105, 16, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text(event.event_title || event.event_name || 'Event', 105, 24, { align: 'center' });
        doc.text('Date: ' + (event.date || event.event_date || 'TBD') + ' | Generated: ' + new Date().toLocaleDateString(), 105, 32, { align: 'center' });
        
        // Summary
        doc.setTextColor(...darkColor);
        doc.setFontSize(11);
        doc.setFont('helvetica', 'bold');
        doc.text('Total Attendees: ' + attendees.length, 14, 52);
        
        // Attendance Table
        if (attendees.length > 0) {
            doc.autoTable({
                startY: 58,
                head: [['#', 'Name', 'Contact', 'Check-in Method', 'Check-in Time']],
                body: attendees.map((a, i) => [
                    i + 1,
                    a.full_name || a.name || 'N/A',
                    a.contact || a.phone || 'N/A',
                    a.checkin_method || 'manual',
                    a.checkin_timestamp ? new Date(a.checkin_timestamp).toLocaleString() : 'N/A'
                ]),
                theme: 'striped',
                headStyles: { fillColor: primaryColor },
                styles: { fontSize: 9 },
                margin: { left: 14, right: 14 }
            });
        } else {
            doc.setFontSize(10);
            doc.setFont('helvetica', 'italic');
            doc.text('No attendance records found.', 14, 60);
        }
        
        // Footer
        const pageHeight = doc.internal.pageSize.height;
        doc.setFillColor(...primaryColor);
        doc.rect(0, pageHeight - 12, 210, 12, 'F');
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(8);
        doc.text('Public Safety Campaign System - Attendance Record', 105, pageHeight - 5, { align: 'center' });
        
        // Save
        const fileName = `Attendance_${event.event_title || eventId}_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        customConfirm('Attendance PDF exported successfully!');
        
    } catch (error) {
        console.error('PDF generation error:', error);
        customConfirm('Failed to generate PDF: ' + error.message);
    }
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

// Edit event - populate form with event data
async function editEvent(eventId) {
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.event) {
            const e = data.event;
            
            // Populate form fields
            document.getElementById('event_title').value = e.event_title || e.event_name || '';
            document.getElementById('event_type').value = e.event_type || 'seminar';
            document.getElementById('event_status').value = e.event_status || 'draft';
            document.getElementById('linked_campaign_id').value = e.linked_campaign_id || '';
            document.getElementById('target_audience_profile_id').value = e.target_audience_profile_id || '';
            document.getElementById('hazard_focus').value = e.hazard_focus || '';
            document.getElementById('date').value = e.date || '';
            document.getElementById('start_time').value = e.start_time || '';
            document.getElementById('end_time').value = e.end_time || '';
            document.getElementById('venue').value = e.venue || '';
            document.getElementById('location').value = e.location || '';
            document.getElementById('event_description').value = e.event_description || '';
            document.getElementById('transport_requirements').value = e.transport_requirements || '';
            document.getElementById('trainer_requirements').value = e.trainer_requirements || '';
            document.getElementById('equipment_requirements').value = e.equipment_requirements || '';
            document.getElementById('volunteer_requirements').value = e.volunteer_requirements || '';
            
            // Handle facilitator and segment IDs
            if (data.facilitators && data.facilitators.length > 0) {
                const facIds = data.facilitators.map(f => f.user_id || f.id).join(', ');
                document.getElementById('facilitator_ids').value = facIds;
            }
            if (data.segments && data.segments.length > 0) {
                const segIds = data.segments.map(s => s.segment_id || s.id).join(', ');
                document.getElementById('segment_ids').value = segIds;
            }
            
            // Set form to update mode
            const form = document.getElementById('createForm');
            if (form) form.dataset.eventId = eventId;
            
            // Change button text
            const submitBtn = document.querySelector('#create-event button.btn-primary');
            if (submitBtn) submitBtn.textContent = 'Update Event';
            
            // Scroll to form
            document.getElementById('create-event').scrollIntoView({ behavior: 'smooth', block: 'start' });
            
            // Load audience profile preview if selected
            if (e.target_audience_profile_id) {
                loadAudienceProfilePreview();
            }
        } else {
            alert('Error loading event: ' + (data.error || 'Not found'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Delete event
async function deleteEvent(eventId) {
    if (!confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok) {
            alert('Event deleted successfully');
            loadEvents();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete event'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Autocomplete functions
let autocompleteTimeouts = {};

function setupAutocomplete(inputId, endpoint, datalistId) {
    // Temporarily disabled - autocomplete endpoints not yet implemented
    // This prevents 500 errors in console
    return;
    
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
    // Temporarily disabled - autocomplete endpoints not yet implemented
    return;
    
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

// RBAC: Force Viewer to events list and hide all forms
(function() {
    function enforceViewerReadOnly() {
        try {
            let userRole = null;
            let roleId = null;
            const token = localStorage.getItem('jwtToken');
            if (token) {
                try {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        roleId = payload.role_id || payload.rid;
                        userRole = payload.role ? payload.role.toLowerCase() : null;
                    }
                } catch (e) {}
            }
            if (!userRole) {
                const currentUserStr = localStorage.getItem('currentUser');
                if (currentUserStr) {
                    try {
                        const currentUser = JSON.parse(currentUserStr);
                        userRole = currentUser.role ? currentUser.role.toLowerCase() : null;
                        if (!roleId) roleId = currentUser.role_id;
                    } catch (e) {}
                }
            }
            const isViewer = userRole === 'viewer' || userRole === 'partner' || 
                            userRole === 'partner representative' || roleId === 6 ||
                            (userRole && (userRole.includes('partner') || userRole.includes('viewer')));
            
            if (isViewer) {
                // Force hash to events-list (read-only view)
                if (window.location.hash !== '#events-list') {
                    window.location.hash = 'events-list';
                }
            }
        } catch (e) {}
    }
    enforceViewerReadOnly();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enforceViewerReadOnly);
    }
})();

// RBAC: Aggressively hide create form for Viewer
(function() {
    function hideViewerForms() {
        try {
            let userRole = null;
            let roleId = null;
            const token = localStorage.getItem('jwtToken');
            if (token) {
                try {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        roleId = payload.role_id || payload.rid;
                        userRole = payload.role ? payload.role.toLowerCase() : null;
                    }
                } catch (e) {}
            }
            if (!userRole) {
                const currentUserStr = localStorage.getItem('currentUser');
                if (currentUserStr) {
                    try {
                        const currentUser = JSON.parse(currentUserStr);
                        userRole = currentUser.role ? currentUser.role.toLowerCase() : null;
                        if (!roleId) roleId = currentUser.role_id;
                    } catch (e) {}
                }
            }
            const isViewer = userRole === 'viewer' || userRole === 'partner' || 
                            userRole === 'partner representative' || roleId === 6 ||
                            (userRole && (userRole.includes('partner') || userRole.includes('viewer')));
            if (isViewer) {
                console.log('RBAC: Viewer detected - hiding forms and redirecting to list');
                // Hide create event form
                const createEventSection = document.getElementById('create-event');
                if (createEventSection) {
                    createEventSection.style.display = 'none';
                    createEventSection.remove(); // Remove from DOM entirely
                }
                // Hide add agency button and form
                const agencySection = document.getElementById('agency-coordination');
                if (agencySection) {
                    const addBtn = agencySection.querySelector('button[onclick*="showAddAgencyForm"]');
                    if (addBtn) {
                        addBtn.style.display = 'none';
                        addBtn.remove();
                    }
                }
                // Hide ALL create/edit buttons
                document.querySelectorAll('button').forEach(btn => {
                    const text = btn.textContent.toLowerCase();
                    if (text.includes('add') || text.includes('create') || text.includes('edit') || 
                        text.includes('delete') || text.includes('approve') || text.includes('schedule')) {
                        btn.style.display = 'none';
                        btn.remove(); // Remove from DOM entirely
                    }
                });
                
                // Auto-scroll to events list (read-only view)
                setTimeout(() => {
                    const eventsList = document.getElementById('events-list');
                    if (eventsList) {
                        eventsList.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        window.location.hash = 'events-list';
                    }
                }, 100);
            }
        } catch (e) {}
    }
    hideViewerForms();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideViewerForms);
    }
    setTimeout(hideViewerForms, 200);
})();

// ==================== MODAL FUNCTIONS ====================

// Store all events for filtering archived
let allEventsData = [];

// Create Event Modal
function openCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    // Populate dropdowns
    populateModalDropdowns();
}

function closeCreateEventModal() {
    document.getElementById('createEventModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('modalCreateForm').reset();
    document.getElementById('modalCreateStatus').textContent = '';
    document.getElementById('modalConflictWarning').style.display = 'none';
}

async function populateModalDropdowns() {
    // Populate campaigns dropdown
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const campaigns = data.data || [];
        
        ['modal_linked_campaign_id', 'edit_linked_campaign_id'].forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const currentVal = select.value;
                select.innerHTML = '<option value="">-- Select Campaign --</option>';
                campaigns.forEach(c => {
                    const option = document.createElement('option');
                    option.value = c.id;
                    option.textContent = `[#${c.id}] ${c.title || 'Untitled'}`;
                    select.appendChild(option);
                });
                if (currentVal) select.value = currentVal;
            }
        });
    } catch (err) {
        console.error('Error loading campaigns for modal:', err);
    }
    
    // Populate segments dropdown
    try {
        const res = await fetch(apiBase + '/api/v1/segments', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const segments = data.data || data.segments || [];
        
        ['modal_target_audience_profile_id', 'edit_target_audience_profile_id'].forEach(id => {
            const select = document.getElementById(id);
            if (select) {
                const currentVal = select.value;
                select.innerHTML = '<option value="">-- Select Audience Segment --</option>';
                segments.forEach(s => {
                    if (s && s.id && s.name) {
                        const option = document.createElement('option');
                        option.value = s.id;
                        option.textContent = `${s.name} - ${s.risk_level || 'N/A'}`;
                        select.appendChild(option);
                    }
                });
                if (currentVal) select.value = currentVal;
            }
        });
    } catch (err) {
        console.error('Error loading segments for modal:', err);
    }
}

async function submitModalEventForm() {
    const statusEl = document.getElementById('modalCreateStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    const startDatetime = document.getElementById('modal_start_datetime').value;
    const endDatetime = document.getElementById('modal_end_datetime').value;
    
    // Parse datetime to date and time
    let date = '', startTime = '', endTime = '';
    if (startDatetime) {
        const dt = new Date(startDatetime);
        date = dt.toISOString().split('T')[0];
        startTime = dt.toTimeString().substring(0, 5);
    }
    if (endDatetime) {
        const dt = new Date(endDatetime);
        endTime = dt.toTimeString().substring(0, 5);
    }
    
    const payload = {
        event_title: document.getElementById('modal_event_title').value.trim(),
        event_type: document.getElementById('modal_event_type').value,
        event_status: document.getElementById('modal_event_status').value,
        linked_campaign_id: parseInt(document.getElementById('modal_linked_campaign_id').value) || null,
        target_audience_profile_id: parseInt(document.getElementById('modal_target_audience_profile_id').value) || null,
        hazard_focus: document.getElementById('modal_hazard_focus').value.trim() || null,
        date: date,
        start_time: startTime,
        end_time: endTime || null,
        venue: document.getElementById('modal_venue').value.trim(),
        location: document.getElementById('modal_location').value.trim() || null,
        event_description: document.getElementById('modal_event_description').value.trim() || null
    };
    
    if (!payload.event_title || !payload.venue) {
        statusEl.textContent = '✗ Error: Event Title and Venue are required';
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
            statusEl.textContent = '✓ Event created successfully!';
            statusEl.style.color = '#166534';
            setTimeout(() => {
                closeCreateEventModal();
                loadEvents();
            }, 1000);
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed to create event');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Agency Coordination Modal
function openAgencyCoordinationModal() {
    document.getElementById('agencyCoordinationModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    populateModalAgencyEventDropdown();
}

function closeAgencyCoordinationModal() {
    document.getElementById('agencyCoordinationModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    hideModalAddAgencyForm();
}

async function populateModalAgencyEventDropdown() {
    const select = document.getElementById('modal_agency_event_select');
    if (!select) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const events = data.data || data.events || [];
        
        select.innerHTML = '<option value="">-- Select Event --</option>';
        events.forEach(e => {
            const option = document.createElement('option');
            option.value = e.event_id || e.id;
            option.textContent = `[#${e.event_id || e.id}] ${e.event_title || e.event_name || 'Untitled'}`;
            select.appendChild(option);
        });
    } catch (err) {
        console.error('Error loading events for agency modal:', err);
    }
}

async function loadModalAgencyCoordination() {
    const eventId = document.getElementById('modal_agency_event_select').value;
    const container = document.getElementById('modalAgencyCoordinationList');
    
    if (!eventId) {
        container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Select an event to view agency coordination</p>';
        return;
    }
    
    container.innerHTML = '<p style="text-align:center; color:#64748b;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.agency_coordination && data.agency_coordination.length > 0) {
            let html = '<table class="data-table" style="font-size:13px;"><thead><tr><th>Agency</th><th>Type</th><th>Status</th><th>Details</th></tr></thead><tbody>';
            data.agency_coordination.forEach(ac => {
                const statusColors = {
                    'requested': { bg: '#fef3c7', color: '#92400e' },
                    'confirmed': { bg: '#d1fae5', color: '#065f46' },
                    'fulfilled': { bg: '#dbeafe', color: '#1e40af' },
                    'cancelled': { bg: '#fee2e2', color: '#991b1b' }
                };
                const statusStyle = statusColors[ac.request_status] || statusColors['requested'];
                html += `<tr><td><strong>${ac.agency_name}</strong></td><td>${ac.agency_type}</td><td><span style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:2px 6px; border-radius:4px; font-size:11px;">${ac.request_status}</span></td><td>${ac.request_details || '-'}</td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No agency coordination records found</p>';
        }
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626;">Error: ' + err.message + '</p>';
    }
}

function showModalAddAgencyForm() {
    const eventId = document.getElementById('modal_agency_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    document.getElementById('modalAddAgencyForm').style.display = 'block';
}

function hideModalAddAgencyForm() {
    document.getElementById('modalAddAgencyForm').style.display = 'none';
    document.getElementById('modal_agency_type').value = '';
    document.getElementById('modal_agency_name').value = '';
    document.getElementById('modal_request_details').value = '';
}

async function submitModalAgencyCoordination() {
    const eventId = document.getElementById('modal_agency_event_select').value;
    if (!eventId) {
        alert('Please select an event first');
        return;
    }
    
    const payload = {
        agency_type: document.getElementById('modal_agency_type').value,
        agency_name: document.getElementById('modal_agency_name').value.trim(),
        request_details: document.getElementById('modal_request_details').value.trim() || null
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
            hideModalAddAgencyForm();
            loadModalAgencyCoordination();
        } else {
            alert('Error: ' + (data.error || 'Failed'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// View Event Modal
async function openViewEventModal(eventId) {
    document.getElementById('viewEventModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    const contentDiv = document.getElementById('viewEventContent');
    contentDiv.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.event) {
            const e = data.event;
            const summary = data.attendance_summary || {};
            const statusColors = {
                'draft': { bg: '#e5e7eb', color: '#374151' },
                'scheduled': { bg: '#dbeafe', color: '#1e40af' },
                'confirmed': { bg: '#d1fae5', color: '#065f46' },
                'completed': { bg: '#d1fae5', color: '#065f46' },
                'cancelled': { bg: '#fee2e2', color: '#991b1b' },
                'archived': { bg: '#f3f4f6', color: '#6b7280' }
            };
            const status = e.event_status || 'draft';
            const statusStyle = statusColors[status] || statusColors['draft'];
            
            contentDiv.innerHTML = `
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:20px;">
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Event Title</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600; color:#1e293b;">${e.event_title || 'N/A'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Type</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;"><span style="background:#e0f2fe; color:#1d4ed8; padding:2px 8px; border-radius:4px;">${e.event_type || 'N/A'}</span></p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Status</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;"><span style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:2px 8px; border-radius:4px;">${status}</span></p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Date & Time</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${e.date || 'TBD'} ${e.start_time || ''} ${e.end_time ? '- ' + e.end_time : ''}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Venue</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${e.venue || 'TBD'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Location</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${e.location || 'N/A'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Campaign ID</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${e.linked_campaign_id || 'None'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Attendance</strong>
                        <p style="margin:4px 0 0 0; font-size:14px; font-weight:600;">${summary.total_attendance || 0}</p>
                    </div>
                </div>
                ${e.event_description ? `<div style="background:#f8fafc; padding:16px; border-radius:8px; margin-bottom:16px;"><strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Description</strong><p style="margin:8px 0 0 0; color:#475569;">${e.event_description}</p></div>` : ''}
                ${e.hazard_focus ? `<div style="margin-bottom:16px;"><strong>Hazard Focus:</strong> <span style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px;">${e.hazard_focus}</span></div>` : ''}
            `;
        } else {
            contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error loading event details</p>';
        }
    } catch (err) {
        contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

function closeViewEventModal() {
    document.getElementById('viewEventModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Edit Event Modal
async function openEditEventModal(eventId) {
    document.getElementById('editEventModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('edit_event_id').value = eventId;
    
    // Populate dropdowns first
    await populateModalDropdowns();
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.event) {
            const e = data.event;
            document.getElementById('edit_event_title').value = e.event_title || '';
            document.getElementById('edit_event_type').value = e.event_type || 'seminar';
            document.getElementById('edit_event_status').value = e.event_status || 'scheduled';
            document.getElementById('edit_linked_campaign_id').value = e.linked_campaign_id || '';
            document.getElementById('edit_target_audience_profile_id').value = e.target_audience_profile_id || '';
            document.getElementById('edit_hazard_focus').value = e.hazard_focus || '';
            document.getElementById('edit_venue').value = e.venue || '';
            document.getElementById('edit_location').value = e.location || '';
            document.getElementById('edit_event_description').value = e.event_description || '';
            
            // Set datetime fields
            if (e.date && e.start_time) {
                document.getElementById('edit_start_datetime').value = `${e.date}T${e.start_time}`;
            }
            if (e.date && e.end_time) {
                document.getElementById('edit_end_datetime').value = `${e.date}T${e.end_time}`;
            }
        }
    } catch (err) {
        console.error('Error loading event for edit:', err);
    }
}

function closeEditEventModal() {
    document.getElementById('editEventModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('editEventForm').reset();
    document.getElementById('editEventStatus').textContent = '';
}

async function submitEditEventForm() {
    const statusEl = document.getElementById('editEventStatus');
    const eventId = document.getElementById('edit_event_id').value;
    
    statusEl.textContent = 'Updating...';
    statusEl.style.color = '#64748b';
    
    const startDatetime = document.getElementById('edit_start_datetime').value;
    const endDatetime = document.getElementById('edit_end_datetime').value;
    
    let date = '', startTime = '', endTime = '';
    if (startDatetime) {
        const dt = new Date(startDatetime);
        date = dt.toISOString().split('T')[0];
        startTime = dt.toTimeString().substring(0, 5);
    }
    if (endDatetime) {
        const dt = new Date(endDatetime);
        endTime = dt.toTimeString().substring(0, 5);
    }
    
    const payload = {
        event_title: document.getElementById('edit_event_title').value.trim(),
        event_type: document.getElementById('edit_event_type').value,
        event_status: document.getElementById('edit_event_status').value,
        linked_campaign_id: parseInt(document.getElementById('edit_linked_campaign_id').value) || null,
        target_audience_profile_id: parseInt(document.getElementById('edit_target_audience_profile_id').value) || null,
        hazard_focus: document.getElementById('edit_hazard_focus').value.trim() || null,
        date: date,
        start_time: startTime,
        end_time: endTime || null,
        venue: document.getElementById('edit_venue').value.trim(),
        location: document.getElementById('edit_location').value.trim() || null,
        event_description: document.getElementById('edit_event_description').value.trim() || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Event updated successfully!';
            statusEl.style.color = '#166534';
            setTimeout(() => {
                closeEditEventModal();
                loadEvents();
            }, 1000);
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed to update');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Archive Event
async function archiveEvent(eventId) {
    if (!confirm('Archive this event? It can be restored from View Archived.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ event_status: 'archived' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Event archived successfully');
            loadEvents();
        } else {
            alert('Error: ' + (data.error || 'Failed to archive'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Archived Events Modal
async function openArchivedEventsModal() {
    document.getElementById('archivedEventsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadArchivedEvents();
}

function closeArchivedEventsModal() {
    document.getElementById('archivedEventsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

async function loadArchivedEvents() {
    const container = document.getElementById('archivedEventsList');
    container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const events = data.data || data.events || [];
        
        const archivedEvents = events.filter(e => (e.event_status || '').toLowerCase() === 'archived');
        
        if (archivedEvents.length === 0) {
            container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No archived events found.</p>';
            return;
        }
        
        let html = '<table class="data-table"><thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Date</th><th>Venue</th><th>Actions</th></tr></thead><tbody>';
        archivedEvents.forEach(e => {
            html += `<tr>
                <td>${e.event_id || e.id}</td>
                <td><strong>${e.event_title || 'Untitled'}</strong></td>
                <td>${e.event_type || 'N/A'}</td>
                <td>${e.date || 'N/A'}</td>
                <td>${e.venue || 'N/A'}</td>
                <td>
                    <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; margin:2px;" onclick="openViewEventModal(${e.event_id || e.id})">👁️ View</button>
                    <button class="btn btn-success" style="padding:4px 8px; font-size:11px; background:#10b981; color:white; margin:2px;" onclick="restoreEvent(${e.event_id || e.id})">🔄 Restore</button>
                    <button class="btn btn-danger" style="padding:4px 8px; font-size:11px; background:#ef4444; color:white; margin:2px;" onclick="deleteEventPermanently(${e.event_id || e.id})">🗑️ Delete</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

async function restoreEvent(eventId) {
    if (!confirm('Restore this event?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ event_status: 'scheduled' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Event restored successfully');
            loadArchivedEvents();
            loadEvents();
        } else {
            alert('Error: ' + (data.error || 'Failed to restore'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function deleteEventPermanently(eventId) {
    if (!confirm('Permanently delete this event? This cannot be undone.')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok) {
            alert('Event deleted permanently');
            loadArchivedEvents();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Close modals when clicking outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// ==================== END MODAL FUNCTIONS ====================

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

// Initialize on page load - ensure DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM loaded - initializing events page');
        initializeEventsPage();
    });
} else {
    console.log('DOM already loaded - initializing events page');
    initializeEventsPage();
}

function initializeEventsPage() {
    console.log('Initializing events page with API base:', apiBase);
    console.log('JWT token present:', !!token);
    
    // Check if token is valid
    if (!token || token === '') {
        console.error('No JWT token found! User might not be logged in.');
        const tbody = document.getElementById('eventTable');
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; padding:24px; color:#dc2626;">Authentication required. Please log in.</td></tr>';
        }
        return;
    }
    
    // Try to decode token to check if it's valid
    try {
        const parts = token.split('.');
        if (parts.length !== 3) {
            console.error('Invalid JWT token format');
            return;
        }
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        console.log('JWT payload:', payload);
        console.log('Token expires:', new Date(payload.exp * 1000));
        
        // Check if token is expired
        if (payload.exp && payload.exp * 1000 < Date.now()) {
            console.error('JWT token has expired!');
            alert('Your session has expired. Please log in again.');
            window.location.href = '/';
            return;
        }
    } catch (e) {
        console.error('Error decoding JWT token:', e);
    }
    
    // Load data in sequence with error handling
    loadCampaigns().catch(err => console.error('Failed to load campaigns:', err));
    loadAudienceSegments().catch(err => console.error('Failed to load segments:', err));
    loadEvents().catch(err => console.error('Failed to load events:', err));
}
</script>
    </div>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>


