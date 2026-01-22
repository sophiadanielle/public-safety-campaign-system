<?php
$pageTitle = 'Audience Segments';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
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
<body class="module-segments" data-module="segments">
    <?php
    // RBAC: Page-level protection - Viewer cannot access Segments module
    require_once __DIR__ . '/../sidebar/includes/get_user_role.php';
    $currentUserRole = getCurrentUserRole();
    $isViewer = false;
    if ($currentUserRole) {
        $roleLower = strtolower(trim($currentUserRole));
        $isViewer = ($roleLower === 'viewer' || $roleLower === 'partner' || 
                    strpos($roleLower, 'partner') !== false || strpos($roleLower, 'viewer') !== false);
    }
    if ($isViewer) {
        http_response_code(403);
        header('Location: ' . $publicPath . '/dashboard.php');
        exit;
    }
    ?>
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
    .segments-page {
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
    .form-field textarea,
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
    .form-field textarea:focus,
    .form-field select:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
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
    .section-step {
        display: inline-block;
        background: #4c8a89;
        color: white;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-right: 8px;
    }
    .helper-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 6px;
        font-style: italic;
        line-height: 1.5;
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
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 16px;
    }
    .data-table th,
    .data-table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }
    .data-table th {
        background: #f8fafc;
        font-weight: 600;
        color: #0f172a;
        font-size: 14px;
    }
    .data-table td {
        color: #475569;
        font-size: 14px;
    }
    .data-table tr:hover {
        background: #f8fafc;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    .badge.low { background: #d1fae5; color: #065f46; }
    .badge.medium { background: #fef3c7; color: #92400e; }
    .badge.high { background: #fee2e2; color: #991b1b; }
</style>

<main class="segments-page">
    <div class="page-header">
        <h1>Target Audience Segmentation</h1>
        <p>Create and manage reusable target audience segments that integrate with Campaign Management, Content Distribution, Scheduling/Events, and Reporting & Analytics modules. Segment residents by location, risk level, or sector to enable precise targeting across the system.</p>
    </div>

    <!-- All Segments List -->
    <section id="segments-list" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 1</span>
            All Segments
        </h2>
        <div class="section-description">
            <strong>What this shows:</strong> This is the system's master list of targeting groups. Each segment is a persistent, reusable entity that can be used by Campaign Management, Content Distribution, Scheduling/Events, and Reporting & Analytics modules. Segments represent groups of residents (e.g., senior citizens in Payatas, high-risk households in Zone 5) that serve as targeting entities across the entire system—not just temporary filters, but reusable targeting groups for campaigns, content, events, and analysis.
            <br><br>
            <strong>When to use:</strong> Use this list to find existing segments for campaign creation, content targeting, event participation, and reporting. Each segment can be reused across multiple modules, ensuring consistent targeting throughout the system.
        </div>
        <div class="form-field" style="margin-bottom:16px;">
            <button class="btn btn-primary" onclick="loadSegments()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-list" style="margin-right:8px;"></i>View All Segments
            </button>
        </div>
        <div class="empty-state" id="segmentsListEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-users"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No segments created yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">You haven't created any audience segments yet. Use the "Create Segment" section below to define your first segment. Segments are reusable targeting entities that integrate with Campaign Management, Content Distribution, Scheduling/Events, and Reporting & Analytics modules.</p>
        </div>
        <div id="segmentsListContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Click "View All Segments" above to load your segments</p>
        </div>
    </section>

    <!-- Create Segment -->
    <section id="create-segment" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 2</span>
            Create Segment
        </h2>
        <form id="createForm" class="form-grid">
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Segment Name *</label>
                <select id="segment_name" required onchange="handleSegmentNameChange()" style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; background: white;">
                    <option value="">-- Select existing or create new --</option>
                    <option value="__new__">+ Create New Segment Name</option>
                </select>
                <input id="segment_name_new" type="text" placeholder="Enter new segment name..." style="display: none; width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 15px; margin-top: 8px; background: white;">
            </div>
            <div class="form-field">
                <label>Geographic Scope</label>
                <select id="geographic_scope" onchange="updateFormDependencies();">
                    <option value="">Select...</option>
                    <option value="Barangay">Barangay</option>
                    <option value="Zone">Zone</option>
                    <option value="Purok">Purok</option>
                </select>
            </div>
            <div class="form-field">
                <label>Location Reference (Quezon City Barangay)</label>
                <select id="location_reference">
                    <option value="">Select Barangay...</option>
                    <option value="Barangay Batasan Hills">Barangay Batasan Hills</option>
                    <option value="Barangay Commonwealth">Barangay Commonwealth</option>
                    <option value="Barangay Holy Spirit">Barangay Holy Spirit</option>
                    <option value="Barangay Payatas">Barangay Payatas</option>
                    <option value="Barangay Bagong Silangan">Barangay Bagong Silangan</option>
                    <option value="Barangay Tandang Sora">Barangay Tandang Sora</option>
                    <option value="Barangay UP Campus">Barangay UP Campus</option>
                    <option value="Barangay Diliman">Barangay Diliman</option>
                    <option value="Barangay Matandang Balara">Barangay Matandang Balara</option>
                    <option value="Barangay Loyola Heights">Barangay Loyola Heights</option>
                    <option value="Barangay Cubao">Barangay Cubao</option>
                    <option value="Barangay Kamuning">Barangay Kamuning</option>
                    <option value="Barangay Project 6">Barangay Project 6</option>
                    <option value="Barangay Project 8">Barangay Project 8</option>
                    <option value="Barangay Fairview">Barangay Fairview</option>
                    <option value="Barangay Nagkaisang Nayon">Barangay Nagkaisang Nayon</option>
                </select>
            </div>
            <div class="form-field">
                <label>Sector Type</label>
                <select id="sector_type">
                    <option value="">Select...</option>
                    <option value="Households">Households</option>
                    <option value="Youth">Youth</option>
                    <option value="Senior Citizens">Senior Citizens</option>
                    <option value="Schools">Schools</option>
                    <option value="NGOs">NGOs</option>
                    <option value="Person with Disabilities">Person with Disabilities</option>
                    <option value="Pregnant Women">Pregnant Women</option>
                </select>
            </div>
            <div class="form-field">
                <label>Risk Level *</label>
                <select id="risk_level" required>
                    <option value="">Select...</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Basis of Segmentation</label>
                <select id="basis_of_segmentation">
                    <option value="">Select...</option>
                    <option value="Historical trend">Historical trend</option>
                    <option value="Inspection results">Inspection results</option>
                    <option value="Attendance records">Attendance records</option>
                    <option value="Incident pattern reference">Incident pattern reference</option>
                </select>
            </div>
        </form>
        <div style="display: flex; gap: 8px; margin-top: 16px; align-items: center;">
            <button class="btn btn-primary" onclick="createSegment()" id="createSegmentBtn" style="flex: 1;">
                <span>Create Segment</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()" style="padding: 10px 16px;">
                <span>Clear</span>
            </button>
        </div>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Audience Members View -->
    <section id="audience-members" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 3</span>
            Segment Members
        </h2>
        <div class="section-description">
            <strong>What this shows:</strong> This connects residents to segments, enabling campaign targeting, attendance tracking, participation analysis, and message personalization across the system. Members represent actual residents whose membership affects targeting behavior system-wide. You can see who is included in each segment, their contact information, and location details.
            <br><br>
            <strong>When to use:</strong> Review segment members after creating a segment or importing members to ensure the correct residents are included. This membership data is used by Campaign Management for targeting, Scheduling/Events for attendance tracking, and Reporting & Analytics for participation analysis. Verify members before using segments in campaigns or events.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select id="viewMembersSegmentId" style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to view its members --</option>
                </select>
                <div class="helper-text">💡 <strong>Need help?</strong> Don't see any segments? Go to "All Segments" section above to create your first segment, or wait a moment for segments to load. Segments are reusable across Campaign Management, Content Distribution, and Scheduling modules.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="viewSegmentMembers()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-users" style="margin-right:8px;"></i>View Segment Members
                </button>
            </div>
        </div>
        <div class="empty-state" id="audienceMembersEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-user-friends"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No segment selected yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">Choose a segment above and click <strong>"View Segment Members"</strong> to see the list of residents in that segment. Member data affects targeting behavior system-wide for campaigns, content distribution, and events. If a segment has no members yet, you can add them using the "Import Members" section below.</p>
        </div>
        <div id="audienceMembersContainer" style="margin-top: 20px;"></div>
    </section>

    <!-- Participation History -->
    <section id="segment-analytics" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 4</span>
            Participation History
        </h2>
        <div class="section-description">
            <strong>What this shows:</strong> This is the evaluation layer of segmentation, supporting campaign effectiveness analysis, engagement tracking, and evidence for research evaluation. Data shown includes attendance from training, events, and simulations. Insights may be influenced by reports from police, emergency response, and disaster systems. This participation data is reused by Reporting & Analytics modules for effectiveness analysis.
            <br><br>
            <strong>When to use:</strong> Review participation history to evaluate campaign effectiveness, track engagement patterns, and identify segments that may need more outreach. Historical engagement data improves future targeting decisions and provides evidence for research evaluation. This data supports integration with Reporting & Analytics for comprehensive effectiveness analysis.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select id="viewHistorySegmentId" style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to view its participation history --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a segment to see all past campaign and event participation for residents in that segment. This data supports campaign effectiveness analysis and is integrated with Reporting & Analytics modules.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button class="btn btn-primary" onclick="viewParticipationHistory()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-history" style="margin-right:8px;"></i>View Participation History
                </button>
            </div>
        </div>
        <div class="empty-state" id="participationHistoryEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No segment selected yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">Choose a segment above and click <strong>"View Participation History"</strong> to see past campaign and event participation. This evaluation data is reused by Reporting & Analytics for effectiveness analysis and engagement tracking. If no history appears, this segment may not have participated in any campaigns or events yet.</p>
        </div>
        <div id="segmentAnalyticsContainer" style="margin-top: 20px;"></div>
    </section>

    <!-- Import Members CSV -->
    <section id="import-export" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 5</span>
            Import Members
        </h2>
        <div class="section-description">
            <strong>What this does:</strong> Supports real-world interoperability with external systems. Upload CSV files from LGU systems, census exports, barangay records, or other government datasets to bulk add residents to segments. This enables integration with external data sources and ensures the system can work with data from various government and organizational systems.
            <br><br>
            <strong>Integration-ready:</strong> This feature supports integration with external data sources including CSV from LGU systems, census exports, barangay records, and other government datasets. The system accepts standardized CSV formats to enable seamless data import from partner organizations.
            <br><br>
            <strong>When to use:</strong> Use this when you have a spreadsheet or CSV file with resident information from external systems (LGU databases, census data, barangay records) that you want to add to a segment. This is faster than manually entering each resident and enables integration with existing government data systems.
        </div>
        <form id="importForm" class="form-grid">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select name="segment_id" id="importSegmentId" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to add members to --</option>
                </select>
                <div class="helper-text">💡 <strong>Required:</strong> Select which segment you want to add members to. If you don't see any segments, create one first using the "Create Segment" section above. This feature supports integration with external data sources (CSV from LGU systems, census exports, barangay records).</div>
            </div>
            <div class="form-field">
                <label>CSV File <span style="color:#dc2626;">*</span></label>
                <input type="file" name="file" accept=".csv" required style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>File format:</strong> Your CSV file should have a header row with column names. This format supports integration with external systems (LGU databases, census exports, government datasets). See format requirements below.</div>
            </div>
        </form>
        <div style="color:#64748b; font-size:13px; margin:16px 0; padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #4c8a89;">
            <strong style="color:#0f172a; display:block; margin-bottom:8px;">📋 CSV File Format Requirements (External System Integration):</strong>
            <ul style="margin:8px 0 0 0; padding-left:20px; line-height:1.8;">
                <li><strong>Required column:</strong> <code>name</code> (or <code>full_name</code>) - The resident's full name</li>
                <li><strong>Optional columns:</strong> <code>sector</code>, <code>barangay</code>, <code>zone</code>, <code>purok</code>, <code>contact</code></li>
                <li>Make sure your CSV file has a header row with column names</li>
                <li>The file should be saved as .csv format (not Excel .xlsx)</li>
                <li><strong>Integration-ready:</strong> This format supports imports from LGU systems, census exports, barangay records, and other government datasets</li>
            </ul>
        </div>
        <div class="form-field" style="margin-top:20px;">
            <button type="submit" form="importForm" class="btn btn-primary" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-file-upload" style="margin-right:8px;"></i>Import Members from CSV
            </button>
        </div>
        <div class="status" id="importStatus" style="margin-top:12px;"></div>
    </section>
</main>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
const basePath = '<?php echo $basePath; ?>';

// Helper function to handle token expiration
function handleTokenExpiration() {
    localStorage.removeItem('jwtToken');
    localStorage.removeItem('currentUser');
    alert('Your session has expired. Please log in again.');
    window.location.replace(basePath + '/index.php');
}

// Helper function to get token with validation
function getToken() {
    const t = localStorage.getItem('jwtToken') || '';
    if (!t || t.trim() === '') {
        // Don't immediately redirect - let the API call handle 401 responses
        // This allows for retry logic and better error handling
        console.warn('getToken() - No token found in localStorage');
        return '';
    }
    return t.trim();
}

// Global variable to store segments for dropdowns
let allSegmentsCache = [];

// Populate segment dropdowns in various sections
function populateSegmentDropdowns(segments) {
    // Populate View Members dropdown
    const viewMembersSelect = document.getElementById('viewMembersSegmentId');
    if (viewMembersSelect) {
        const currentValue = viewMembersSelect.value;
        viewMembersSelect.innerHTML = '<option value="">-- Choose a segment to view its members --</option>';
        segments.forEach(seg => {
            const option = document.createElement('option');
            option.value = seg.segment_id;
            option.textContent = seg.segment_name || 'Unnamed Segment';
            viewMembersSelect.appendChild(option);
        });
        if (currentValue) {
            viewMembersSelect.value = currentValue;
        }
    }
    
    // Populate Participation History dropdown
    const viewHistorySelect = document.getElementById('viewHistorySegmentId');
    if (viewHistorySelect) {
        const currentValue = viewHistorySelect.value;
        viewHistorySelect.innerHTML = '<option value="">-- Choose a segment to view its participation history --</option>';
        segments.forEach(seg => {
            const option = document.createElement('option');
            option.value = seg.segment_id;
            option.textContent = seg.segment_name || 'Unnamed Segment';
            viewHistorySelect.appendChild(option);
        });
        if (currentValue) {
            viewHistorySelect.value = currentValue;
        }
    }
    
    // Populate Import Members dropdown
    const importSelect = document.getElementById('importSegmentId');
    if (importSelect) {
        const currentValue = importSelect.value;
        importSelect.innerHTML = '<option value="">-- Choose a segment to add members to --</option>';
        segments.forEach(seg => {
            const option = document.createElement('option');
            option.value = seg.segment_id;
            option.textContent = seg.segment_name || 'Unnamed Segment';
            importSelect.appendChild(option);
        });
        if (currentValue) {
            importSelect.value = currentValue;
        }
    }
    
    // Populate Create Segment Name dropdown
    const segmentNameSelect = document.getElementById('segment_name');
    if (segmentNameSelect) {
        const currentValue = segmentNameSelect.value;
        const isNewMode = segmentNameSelect.value === '__new__';
        const newInputValue = document.getElementById('segment_name_new') ? document.getElementById('segment_name_new').value : '';
        
        segmentNameSelect.innerHTML = '<option value="">-- Select existing or create new --</option>';
        segmentNameSelect.innerHTML += '<option value="__new__">+ Create New Segment Name</option>';
        
        segments.forEach(seg => {
            if (seg.segment_name) {
                const option = document.createElement('option');
                option.value = seg.segment_name;
                option.textContent = seg.segment_name;
                segmentNameSelect.appendChild(option);
            }
        });
        
        if (isNewMode) {
            segmentNameSelect.value = '__new__';
            const newInput = document.getElementById('segment_name_new');
            if (newInput) {
                newInput.style.display = 'block';
                newInput.required = true;
                if (newInputValue) {
                    newInput.value = newInputValue;
                }
            }
        } else if (currentValue && currentValue !== '__new__') {
            segmentNameSelect.value = currentValue;
        }
    }
}

// Load segments list and populate dropdowns
async function loadSegments() {
    const container = document.getElementById('segmentsListContainer');
    const emptyState = document.getElementById('segmentsListEmptyState');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading segments...</p>';
    emptyState.style.display = 'none';
    
    const currentToken = getToken();
    if (!currentToken) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Authentication required</p><p style="margin:0; font-size:14px;">Please log in to view segments.</p></div>';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments', {
            headers: { 'Authorization': 'Bearer ' + currentToken }
        });
        
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const data = await res.json();
        
        if (!res.ok) {
            const errorMsg = data.error || '';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            // Hide technical errors
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database')) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load segments</p><p style="margin:0; font-size:14px;">We couldn\'t load segments right now. Please try again or contact the administrator if the problem persists.</p></div>';
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load segments</p><p style="margin:0; font-size:14px;">' + (errorMsg || 'Please try again or contact the administrator.') + '</p></div>';
            }
            emptyState.style.display = 'none';
            return;
        }
        
        const segments = data.data || [];
        allSegmentsCache = segments; // Cache for dropdowns
        
        // Populate dropdowns
        populateSegmentDropdowns(segments);
        
        if (segments.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'block';
            return;
        }
        
        let html = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Segment Name</th>
                        <th>Geographic Scope</th>
                        <th>Location</th>
                        <th>Sector Type</th>
                        <th>Risk Level</th>
                        <th>Basis</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        segments.forEach(seg => {
            const riskClass = seg.risk_level ? seg.risk_level.toLowerCase() : '';
            html += `
                <tr>
                    <td>#${seg.segment_id}</td>
                    <td><strong>${seg.segment_name || 'N/A'}</strong></td>
                    <td>${seg.geographic_scope || '—'}</td>
                    <td>${seg.location_reference || '—'}</td>
                    <td>${seg.sector_type || '—'}</td>
                    <td>${seg.risk_level ? `<span class="badge ${riskClass}">${seg.risk_level}</span>` : '—'}</td>
                    <td>${seg.basis_of_segmentation || '—'}</td>
                    <td>
                        <button onclick="viewSegmentMembersById(${seg.segment_id})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">View Segment Details</button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
        `;
        
        container.innerHTML = html;
        emptyState.style.display = 'none';
    } catch (err) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        emptyState.style.display = 'none';
    }
}


// Update form dependencies
function updateFormDependencies() {
    const geographicScope = document.getElementById('geographic_scope').value;
    const locationRef = document.getElementById('location_reference');
    
    if (geographicScope && !locationRef.value) {
        locationRef.style.borderColor = '#f59e0b';
    } else {
        locationRef.style.borderColor = '#e2e8f0';
    }
}

// Reset form
function resetForm() {
    document.getElementById('createForm').reset();
    document.getElementById('createStatus').textContent = '';
    document.getElementById('createStatus').style.color = '';
}

// Handle segment name dropdown change
function handleSegmentNameChange() {
    const segmentNameSelect = document.getElementById('segment_name');
    const segmentNameNew = document.getElementById('segment_name_new');
    const statusEl = document.getElementById('createStatus');
    
    if (segmentNameSelect && segmentNameNew) {
        if (segmentNameSelect.value === '__new__') {
            segmentNameNew.style.display = 'block';
            segmentNameNew.required = true;
            segmentNameNew.focus();
            if (statusEl) {
                statusEl.textContent = '';
            }
        } else if (segmentNameSelect.value && segmentNameSelect.value !== '') {
            // User selected an existing segment - warn them
            segmentNameNew.style.display = 'none';
            segmentNameNew.required = false;
            segmentNameNew.value = '';
            if (statusEl) {
                statusEl.textContent = 'Note: You selected an existing segment. To create a new segment, select "+ Create New Segment Name" and enter a unique name.';
                statusEl.style.color = '#f59e0b';
            }
        } else {
            segmentNameNew.style.display = 'none';
            segmentNameNew.required = false;
            segmentNameNew.value = '';
            if (statusEl) {
                statusEl.textContent = '';
            }
        }
    }
}

// Create segment with validation
async function createSegment() {
    const statusEl = document.getElementById('createStatus');
    
    // Get segment name from dropdown or new input field
    const segmentNameSelect = document.getElementById('segment_name');
    const segmentNameNew = document.getElementById('segment_name_new');
    let segmentName = '';
    
    if (segmentNameSelect && segmentNameSelect.value === '__new__') {
        // User selected "Create New" - use the new input field
        segmentName = segmentNameNew ? segmentNameNew.value.trim() : '';
    } else if (segmentNameSelect && segmentNameSelect.value) {
        // User selected existing segment
        segmentName = segmentNameSelect.value.trim();
    }
    
    const riskLevel = document.getElementById('risk_level').value;
    const geographicScope = document.getElementById('geographic_scope').value;
    const locationRef = document.getElementById('location_reference').value;
    
    // Validation
    if (!segmentName) {
        statusEl.textContent = 'Error: Segment Name is required';
        statusEl.style.color = '#dc2626';
        if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew) {
            segmentNameNew.focus();
        } else if (segmentNameSelect) {
            segmentNameSelect.focus();
        }
        return;
    }
    
    if (!riskLevel) {
        statusEl.textContent = 'Error: Risk Level is required';
        statusEl.style.color = '#dc2626';
        document.getElementById('risk_level').focus();
        return;
    }
    
    if (geographicScope && !locationRef) {
        statusEl.textContent = 'Error: Location Reference is required when Geographic Scope is selected';
        statusEl.style.color = '#dc2626';
        document.getElementById('location_reference').focus();
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        segment_name: segmentName,
        geographic_scope: geographicScope || null,
        location_reference: locationRef || null,
        sector_type: document.getElementById('sector_type').value || null,
        risk_level: riskLevel,
        basis_of_segmentation: document.getElementById('basis_of_segmentation').value || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + currentToken },
            body: JSON.stringify(payload)
        });
        
        // Handle 401 Unauthorized (expired token)
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = 'Segment created successfully. You can now view it in "All Segments" or add members using "Import Members".';
            statusEl.style.color = '#166534';
            resetForm();
            loadSegments();
            
            setTimeout(() => {
                document.getElementById('segments-list').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 500);
        } else {
            const errorMsg = data.error || '';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            // Handle duplicate name error specifically
            if (errorMsg.toLowerCase().includes('already exists') || errorMsg.toLowerCase().includes('duplicate')) {
                statusEl.textContent = 'Error: This segment name already exists. Please select "+ Create New Segment Name" from the dropdown and enter a different, unique name.';
                statusEl.style.color = '#dc2626';
                // Focus on the new input field if in new mode
                const segmentNameSelect = document.getElementById('segment_name');
                const segmentNameNew = document.getElementById('segment_name_new');
                if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew) {
                    segmentNameNew.focus();
                    segmentNameNew.select();
                } else if (segmentNameSelect) {
                    // Switch to new mode
                    segmentNameSelect.value = '__new__';
                    handleSegmentNameChange();
                    if (segmentNameNew) {
                        segmentNameNew.focus();
                    }
                }
            } else if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database')) {
                statusEl.textContent = 'Unable to create segment. There was a system issue. Please try again or contact the administrator.';
                statusEl.style.color = '#dc2626';
            } else if (errorMsg.toLowerCase().includes('required') || errorMsg.toLowerCase().includes('missing')) {
                statusEl.textContent = errorMsg || 'Please fill in all required fields.';
                statusEl.style.color = '#dc2626';
            } else {
                statusEl.textContent = errorMsg || 'Unable to create segment. Please check all fields and try again.';
                statusEl.style.color = '#dc2626';
            }
        }
    } catch (err) {
        statusEl.textContent = 'Connection problem. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}


// Edit segment (placeholder - would open edit modal)
function editSegment(segmentId) {
    alert('Edit functionality: Load segment ' + segmentId + ' data into form for editing');
    // TODO: Implement edit modal or form population
}

// View segment members by ID (used from segments list)
async function viewSegmentMembersById(segmentId) {
    if (!segmentId) {
        console.error('No segment ID provided');
        return;
    }
    
    // Ensure dropdowns are populated first
    if (allSegmentsCache.length === 0) {
        await loadSegments();
    }
    
    const select = document.getElementById('viewMembersSegmentId');
    if (select) {
        // Set the value in the dropdown
        select.value = segmentId;
        
        // Ensure the option exists in the dropdown
        let optionExists = false;
        for (let i = 0; i < select.options.length; i++) {
            if (select.options[i].value == segmentId) {
                optionExists = true;
                break;
            }
        }
        
        // If option doesn't exist, add it
        if (!optionExists && allSegmentsCache.length > 0) {
            const segment = allSegmentsCache.find(s => s.segment_id == segmentId);
            if (segment) {
                const option = document.createElement('option');
                option.value = segment.segment_id;
                option.textContent = segment.segment_name || 'Unnamed Segment';
                select.appendChild(option);
                select.value = segmentId;
            }
        }
    }
    
    // Directly fetch and display members using the segment ID
    await viewSegmentMembersDirect(segmentId);
    
    // Scroll to members section
    setTimeout(() => {
        const membersSection = document.getElementById('audience-members');
        if (membersSection) {
            membersSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }, 300);
}

// Direct function to view segment members by ID
async function viewSegmentMembersDirect(segmentId) {
    if (!segmentId) {
        const emptyState = document.getElementById('audienceMembersEmptyState');
        const container = document.getElementById('audienceMembersContainer');
        if (emptyState) emptyState.style.display = 'block';
        if (container) container.innerHTML = '';
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const container = document.getElementById('audienceMembersContainer');
    const emptyState = document.getElementById('audienceMembersEmptyState');
    if (container) {
        container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading members...</p>';
    }
    if (emptyState) emptyState.style.display = 'none';
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + encodeURIComponent(segmentId) + '/members', {
            headers: { 'Authorization': 'Bearer ' + currentToken }
        });
        
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const data = await res.json();
        
        if (!res.ok) {
            const errorMsg = data.error || '';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            if (container) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load members</p><p style="margin:0; font-size:14px;">' + (errorMsg || 'Please try again or contact the administrator.') + '</p></div>';
            }
            if (emptyState) emptyState.style.display = 'none';
            return;
        }
        
        const members = data.data || [];
        
        if (members.length === 0) {
            if (container) container.innerHTML = '';
            if (emptyState) {
                emptyState.style.display = 'block';
                const lastP = emptyState.querySelector('p:last-child');
                if (lastP) {
                    lastP.innerHTML = 'This segment has no members yet. Use the "Import Members" section below to add residents to this segment.';
                }
            }
            return;
        }
        
        let html = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Sector</th>
                        <th>Barangay</th>
                        <th>Zone</th>
                        <th>Purok</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        members.forEach(member => {
            html += `
                <tr>
                    <td><strong>${escapeHtml(member.name || member.full_name || 'N/A')}</strong></td>
                    <td>${escapeHtml(member.sector || '—')}</td>
                    <td>${escapeHtml(member.barangay || '—')}</td>
                    <td>${escapeHtml(member.zone || '—')}</td>
                    <td>${escapeHtml(member.purok || '—')}</td>
                    <td>${escapeHtml(member.contact || '—')}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            <p style="margin-top: 16px; color: #64748b; font-size: 14px;"><strong>Total Members:</strong> ${members.length}</p>
        `;
        
        if (container) {
            container.innerHTML = html;
        }
        if (emptyState) emptyState.style.display = 'none';
    } catch (err) {
        console.error('Error fetching segment members:', err);
        if (container) {
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        }
        if (emptyState) emptyState.style.display = 'none';
    }
}

async function viewSegmentMembers() {
    const segmentSelect = document.getElementById('viewMembersSegmentId');
    if (!segmentSelect) {
        console.error('viewMembersSegmentId element not found');
        return;
    }
    
    const segmentId = segmentSelect.value;
    if (!segmentId || segmentId === '') {
        const emptyState = document.getElementById('audienceMembersEmptyState');
        const container = document.getElementById('audienceMembersContainer');
        if (emptyState) emptyState.style.display = 'block';
        if (container) container.innerHTML = '';
        return;
    }
    
    // Use the direct function to fetch members
    await viewSegmentMembersDirect(segmentId);
}

// View participation history
async function viewParticipationHistory() {
    const segmentSelect = document.getElementById('viewHistorySegmentId');
    if (!segmentSelect) {
        console.error('viewHistorySegmentId element not found');
        return;
    }
    
    const segmentId = segmentSelect.value;
    if (!segmentId || segmentId === '') {
        const emptyState = document.getElementById('participationHistoryEmptyState');
        const container = document.getElementById('segmentAnalyticsContainer');
        if (emptyState) emptyState.style.display = 'block';
        if (container) container.innerHTML = '';
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) {
        console.error('No authentication token available');
        return;
    }
    
    const container = document.getElementById('segmentAnalyticsContainer');
    const emptyState = document.getElementById('participationHistoryEmptyState');
    
    if (!container) {
        console.error('segmentAnalyticsContainer element not found');
        return;
    }
    
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading participation history...</p>';
    if (emptyState) emptyState.style.display = 'none';
    
    try {
        const url = apiBase + '/api/v1/segments/' + encodeURIComponent(segmentId) + '/participation-history';
        const res = await fetch(url, {
            method: 'GET',
            headers: { 
                'Authorization': 'Bearer ' + currentToken,
                'Content-Type': 'application/json'
            }
        });
        
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        let data;
        try {
            data = await res.json();
        } catch (parseError) {
            console.error('Failed to parse response:', parseError);
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Invalid response from server</p><p style="margin:0; font-size:14px;">The server returned an unexpected response. Please try again or contact the administrator.</p></div>';
            if (emptyState) emptyState.style.display = 'none';
            return;
        }
        
        if (!res.ok) {
            const errorMsg = data.error || data.message || 'Unknown error';
            console.error('API error:', errorMsg, 'Status:', res.status);
            
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            
            // Handle database view errors specifically
            if (errorMsg.includes('participation_history') || errorMsg.includes('View') || errorMsg.includes('references invalid table')) {
                if (container) {
                    container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-triangle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Database Configuration Issue</p><p style="margin:0; font-size:14px;">The participation history view needs to be configured in the database. Please contact the system administrator to fix the database view.</p></div>';
                }
                if (emptyState) emptyState.style.display = 'none';
                return;
            }
            
            // Only show user-friendly error messages for other errors
            if (container) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load history</p><p style="margin:0; font-size:14px;">' + (errorMsg || 'Please try again or contact the administrator.') + '</p></div>';
            }
            if (emptyState) emptyState.style.display = 'none';
            return;
        }
        
        const history = Array.isArray(data.data) ? data.data : (Array.isArray(data) ? data : []);
        
        if (history.length === 0) {
            container.innerHTML = '';
            if (emptyState) {
                emptyState.style.display = 'block';
                const lastP = emptyState.querySelector('p:last-child');
                if (lastP) {
                    lastP.innerHTML = 'No history found for this segment yet. This segment has not participated in any campaigns or events. Once members of this segment attend events or respond to campaigns, their participation will appear here.';
                }
            }
            return;
        }
        
        let html = `
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Event</th>
                        <th>Event Type</th>
                        <th>Event Date</th>
                        <th>Attendance</th>
                        <th>Member</th>
                        <th>Check-in</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        history.forEach(record => {
            const eventDate = record.event_date ? new Date(record.event_date).toLocaleDateString() : '—';
            const checkIn = record.check_in ? new Date(record.check_in).toLocaleString() : '—';
            html += `
                <tr>
                    <td><strong>${escapeHtml(record.campaign_name || 'N/A')}</strong></td>
                    <td>${escapeHtml(record.event_name || '—')}</td>
                    <td>${escapeHtml(record.event_type || '—')}</td>
                    <td>${eventDate}</td>
                    <td>${record.attendance_count || 0}</td>
                    <td>${escapeHtml(record.member_name || '—')}</td>
                    <td>${checkIn}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            <p style="margin-top: 16px; color: #64748b; font-size: 14px;"><strong>Total Records:</strong> ${history.length}</p>
        `;
        
        container.innerHTML = html;
        if (emptyState) emptyState.style.display = 'none';
    } catch (err) {
        console.error('Error fetching participation history:', err);
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        if (emptyState) emptyState.style.display = 'none';
    }
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// CSV Import
document.getElementById('importForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('importStatus');
    statusEl.textContent = 'Importing...';
    statusEl.style.color = '#64748b';
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const form = new FormData(e.target);
    const segmentId = form.get('segment_id');
    form.delete('segment_id');
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/members/batch', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + currentToken },
            body: form
        });
        
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const data = await res.json();
        if (res.ok) {
            let message = '✓ Members imported successfully! ' + (data.message || '');
            if (data.errors && data.errors.length > 0) {
                message += '\n\nNote: Some rows had issues (' + data.errors.length + ' row' + (data.errors.length !== 1 ? 's' : '') + '). Please check your CSV file format and try again if needed.';
            }
            statusEl.textContent = message;
            statusEl.style.color = '#166534';
            statusEl.style.whiteSpace = 'pre-wrap';
            e.target.reset();
            // Refresh members view if viewing the same segment
            const importSegmentId = document.getElementById('importSegmentId').value;
            if (importSegmentId && document.getElementById('viewMembersSegmentId').value === importSegmentId) {
                setTimeout(() => viewSegmentMembers(), 500);
            }
        } else {
            const errorMsg = data.error || '';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            // Hide technical errors
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database') || errorMsg.toLowerCase().includes('parse') || errorMsg.toLowerCase().includes('format')) {
                statusEl.textContent = '⚠️ Unable to import members. Please check that your CSV file has the correct format (see requirements above) and try again.';
            } else {
                statusEl.textContent = '⚠️ ' + (errorMsg || 'Unable to import members. Please check your file and try again.');
            }
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '⚠️ Connection problem. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
});

// Handle hash navigation on page load
if (window.location.hash) {
    const targetId = window.location.hash.substring(1);
    const targetElement = document.getElementById(targetId);
    if (targetElement) {
        setTimeout(() => {
            const headerOffset = 90;
            const elementPosition = targetElement.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }, 300);
    }
}

// Link segment to campaign
async function linkToCampaign(segmentId) {
    const currentToken = getToken();
    if (!currentToken) return;
    
    // Fetch available campaigns
    try {
        const campaignsRes = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + currentToken }
        });
        
        if (campaignsRes.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const campaignsData = await campaignsRes.json();
        
        if (!campaignsRes.ok || !campaignsData.data || campaignsData.data.length === 0) {
            alert('No campaigns available. Please create a campaign first.');
            return;
        }
        
        // Create modal to select campaign
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 500px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a; font-size: 18px;">🔗 Link Segment to Campaign</h3>
                <button id="closeLinkModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0; width: 32px; height: 32px;">&times;</button>
            </div>
            <p style="color: #64748b; margin: 0 0 16px 0; font-size: 14px;">Select a campaign to link this segment to:</p>
            <select id="campaignSelect" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px; font-size: 14px;">
                <option value="">-- Select Campaign --</option>
                ${campaignsData.data.map(c => `<option value="${c.id}">${c.title} (${c.status || 'active'})</option>`).join('')}
            </select>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button id="cancelLink" class="btn btn-secondary" style="padding: 8px 16px;">Cancel</button>
                <button id="confirmLink" class="btn btn-primary" style="padding: 8px 16px;">Link</button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        document.getElementById('closeLinkModal').onclick = () => document.body.removeChild(modal);
        document.getElementById('cancelLink').onclick = () => document.body.removeChild(modal);
        
        document.getElementById('confirmLink').onclick = async () => {
            const campaignId = document.getElementById('campaignSelect').value;
            if (!campaignId) {
                alert('Please select a campaign');
                return;
            }
            
            try {
                const linkToken = getToken();
                if (!linkToken) {
                    document.body.removeChild(modal);
                    return;
                }
                
                const res = await fetch(apiBase + `/api/v1/segments/${segmentId}/link-campaign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + linkToken
                    },
                    body: JSON.stringify({ campaign_id: parseInt(campaignId) })
                });
                
                if (res.status === 401) {
                    document.body.removeChild(modal);
                    handleTokenExpiration();
                    return;
                }
                
                const data = await res.json();
                
                document.body.removeChild(modal);
                
                if (res.ok) {
                    alert('✅ Segment linked to campaign successfully!');
                } else {
                    const errorMsg = data.error || 'Failed to link';
                    if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                        handleTokenExpiration();
                        return;
                    }
                    alert('❌ Error: ' + errorMsg);
                }
            } catch (err) {
                document.body.removeChild(modal);
                alert('❌ Error: ' + err.message);
            }
        };
        
    } catch (err) {
        alert('❌ Error loading campaigns: ' + err.message);
    }
}

// Load segments on page load
document.addEventListener('DOMContentLoaded', function() {
    loadSegments();
    
    // Auto-populate dropdowns when segments are loaded
    const observer = new MutationObserver(() => {
        if (allSegmentsCache.length > 0) {
            populateSegmentDropdowns(allSegmentsCache);
        }
    });
    
    const segmentsContainer = document.getElementById('segmentsListContainer');
    if (segmentsContainer) {
        observer.observe(segmentsContainer, { childList: true, subtree: true });
    }
});
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>

