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
        <h1>Audience Segments</h1>
        <p>Create and manage target audience segments for campaigns. Segment residents by location, risk level, or sector to target your public safety messages effectively.</p>
    </div>

    <!-- All Segments List -->
    <section id="segments-list" class="card" style="margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 1</span>
            All Segments
        </h2>
        <div class="section-description">
            <strong>What this shows:</strong> View all audience segments you've created. Each segment represents a group of residents (e.g., senior citizens in Payatas, high-risk households in Zone 5) that can be targeted for specific campaigns. Use this list to find segments, view their details, or manage members.
        </div>
        <div class="form-field" style="margin-bottom:16px;">
            <button class="btn btn-primary" onclick="loadSegments()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-list" style="margin-right:8px;"></i>View All Segments
            </button>
        </div>
        <div class="empty-state" id="segmentsListEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-users"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No segments created yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">You haven't created any audience segments yet. Use the "Create Segment" section below to define your first segment. Segments help you target specific groups of residents for your campaigns.</p>
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
        <div class="section-description">
            <strong>What this does:</strong> Define groups of residents (e.g., senior citizens, students, high-risk areas) so campaigns can be targeted properly. Segmentation may use data from incidents, disaster reports, and attendance records. This helps ensure your messages reach the right people at the right time.
            <br><br>
            <strong>When to use:</strong> Create a segment when you want to target a specific group of residents for a campaign. For example, create a "High-Risk Households in Payatas" segment to target fire safety campaigns to vulnerable areas.
        </div>
        <div style="display: flex; justify-content: flex-end; margin-bottom: 16px;">
            <button type="button" onclick="showSegmentHelp()" class="btn btn-secondary" style="padding: 10px 16px; display: flex; align-items: center; gap: 6px;">
                <i class="fas fa-question-circle"></i>
                <span>How It Works</span>
            </button>
        </div>
        <form id="createForm" class="form-grid">
            <div class="form-field" style="grid-column: 1 / -1;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <label>Segment Name *</label>
                    <button type="button" onclick="generateSegmentName()" class="btn btn-secondary" style="padding: 4px 12px; font-size: 12px; display: flex; align-items: center; gap: 4px;">
                        <span>✨</span>
                        <span>Auto-Generate</span>
                    </button>
                </div>
                <div style="position: relative;">
                    <select id="segment_name" required onchange="updateSegmentPreview()" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white;">
                        <option value="">Select existing segment or create new...</option>
                        <option value="__new__">+ Create New Segment Name</option>
                    </select>
                    <input id="segment_name_new" type="text" placeholder="Enter new segment name..." style="display: none; width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; margin-top: 8px;" oninput="updateSegmentNameSuggestions(); updateSegmentPreview();">
                    <div id="segmentNameSuggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e2e8f0; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="padding: 8px 12px; font-size: 12px; color: #64748b; border-bottom: 1px solid #e2e8f0; font-weight: 600;">Suggestions:</div>
                        <div id="suggestionsList"></div>
                    </div>
                </div>
                <p style="color: #64748b; font-size: 12px; margin: 4px 0 0 0;">💡 Tip: Select existing segment or create new. Use "Auto-Generate" for smart suggestions.</p>
            </div>
            <div class="form-field">
                <label>Geographic Scope</label>
                <select id="geographic_scope" onchange="updateSegmentNameSuggestions(); updateFormDependencies();">
                    <option value="">Select...</option>
                    <option value="Barangay">Barangay</option>
                    <option value="Zone">Zone</option>
                    <option value="Purok">Purok</option>
                </select>
                <p style="color: #64748b; font-size: 11px; margin: 4px 0 0 0;">Select the geographic level</p>
            </div>
            <div class="form-field">
                <label>Location Reference (Quezon City Barangay)</label>
                <select id="location_reference" onchange="updateSegmentNameSuggestions();">
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
                <p style="color: #64748b; font-size: 11px; margin: 4px 0 0 0;">Required if Geographic Scope is selected</p>
            </div>
            <div class="form-field">
                <label>Sector Type</label>
                <select id="sector_type" onchange="updateSegmentNameSuggestions();">
                    <option value="">Select...</option>
                    <option value="Households">Households</option>
                    <option value="Youth">Youth</option>
                    <option value="Senior Citizens">Senior Citizens</option>
                    <option value="Schools">Schools</option>
                    <option value="NGOs">NGOs</option>
                    <option value="Person with Disabilities">Person with Disabilities</option>
                    <option value="Pregnant Women">Pregnant Women</option>
                </select>
                <p style="color: #64748b; font-size: 11px; margin: 4px 0 0 0;">Target audience category</p>
            </div>
            <div class="form-field">
                <label>Risk Level *</label>
                <select id="risk_level" required onchange="updateSegmentNameSuggestions();">
                    <option value="">Select...</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>
                <p style="color: #64748b; font-size: 11px; margin: 4px 0 0 0;">⚠️ Required: Must be explicitly assigned</p>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Basis of Segmentation</label>
                <select id="basis_of_segmentation" onchange="updateSegmentNameSuggestions();">
                    <option value="">Select...</option>
                    <option value="Historical trend">Historical trend</option>
                    <option value="Inspection results">Inspection results</option>
                    <option value="Attendance records">Attendance records</option>
                    <option value="Incident pattern reference">Incident pattern reference</option>
                </select>
                <p style="color: #64748b; font-size: 11px; margin: 4px 0 0 0;">How this segment was determined</p>
            </div>
            
            <!-- Segment Preview -->
            <div id="segmentPreview" style="grid-column: 1 / -1; padding: 16px; background: #f8fafc; border-radius: 8px; border: 2px dashed #cbd5e1; margin-top: 0; display: none;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 18px;">👁️</span>
                    <strong style="color: #0f172a;">Segment Preview</strong>
                </div>
                <div id="previewContent" style="color: #475569; font-size: 14px; line-height: 1.6;"></div>
            </div>
        </form>
        <div style="display: flex; gap: 8px; margin-top: 16px; align-items: center;">
            <button class="btn btn-primary" onclick="createSegment()" id="createSegmentBtn" style="flex: 1;">
                <span>✅</span>
                <span>Create Segment</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="resetForm()" style="padding: 10px 16px;">
                <span>🔄</span>
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
            <strong>What this shows:</strong> This section shows the list of residents belonging to the selected segment. You can see who is included in each segment, their contact information, and location details. This helps you verify that the right people are included in your target audience.
            <br><br>
            <strong>When to use:</strong> Review segment members after creating a segment or importing members to ensure the correct residents are included. You can also use this to check contact information before sending campaign messages.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select id="viewMembersSegmentId" style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to view its members --</option>
                </select>
                <div class="helper-text">💡 <strong>Need help?</strong> Don't see any segments? Go to "All Segments" section above to create your first segment, or wait a moment for segments to load.</div>
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
            <p style="margin:0; font-size:14px; line-height:1.6;">Choose a segment above and click <strong>"View Segment Members"</strong> to see the list of residents in that segment. If a segment has no members yet, you can add them using the "Import Members" section below.</p>
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
            <strong>What this shows:</strong> This shows past participation of this audience in campaigns and events. Data shown includes attendance from training, events, and simulations. Insights may be influenced by reports from police, emergency response, and disaster systems. This helps you understand how engaged each segment has been with your campaigns.
            <br><br>
            <strong>When to use:</strong> Review participation history to see which segments are most engaged, track attendance at events, and identify segments that may need more outreach. This data helps improve future campaign targeting.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select id="viewHistorySegmentId" style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to view its participation history --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a segment to see all past campaign and event participation for residents in that segment.</div>
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
            <p style="margin:0; font-size:14px; line-height:1.6;">Choose a segment above and click <strong>"View Participation History"</strong> to see past campaign and event participation. If no history appears, this segment may not have participated in any campaigns or events yet.</p>
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
            <strong>What this does:</strong> Upload a CSV file if you received a list of residents from another office or system. This allows you to bulk add residents to a segment instead of entering them one by one. Useful when you have lists from barangay records, census data, or other government systems.
            <br><br>
            <strong>When to use:</strong> Use this when you have a spreadsheet or CSV file with resident information that you want to add to a segment. This is faster than manually entering each resident.
        </div>
        <form id="importForm" class="form-grid">
            <div class="form-field">
                <label>Select Segment <span style="color:#dc2626;">*</span></label>
                <select name="segment_id" id="importSegmentId" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Choose a segment to add members to --</option>
                </select>
                <div class="helper-text">💡 <strong>Required:</strong> Select which segment you want to add members to. If you don't see any segments, create one first using the "Create Segment" section above.</div>
            </div>
            <div class="form-field">
                <label>CSV File <span style="color:#dc2626;">*</span></label>
                <input type="file" name="file" accept=".csv" required style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>File format:</strong> Your CSV file should have a header row with column names. See format requirements below.</div>
            </div>
        </form>
        <div style="color:#64748b; font-size:13px; margin:16px 0; padding: 16px; background: #f8fafc; border-radius: 8px; border-left: 4px solid #4c8a89;">
            <strong style="color:#0f172a; display:block; margin-bottom:8px;">📋 CSV File Format Requirements:</strong>
            <ul style="margin:8px 0 0 0; padding-left:20px; line-height:1.8;">
                <li><strong>Required column:</strong> <code>name</code> (or <code>full_name</code>) - The resident's full name</li>
                <li><strong>Optional columns:</strong> <code>sector</code>, <code>barangay</code>, <code>zone</code>, <code>purok</code>, <code>contact</code></li>
                <li>Make sure your CSV file has a header row with column names</li>
                <li>The file should be saved as .csv format (not Excel .xlsx)</li>
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
        
        // Populate segment name dropdown with existing segments
        const segmentNameSelect = document.getElementById('segment_name');
        if (segmentNameSelect) {
            // Keep first two options (empty and "Create New")
            const emptyOpt = segmentNameSelect.options[0];
            const newOpt = segmentNameSelect.options[1];
            segmentNameSelect.innerHTML = '';
            if (emptyOpt) segmentNameSelect.appendChild(emptyOpt);
            if (newOpt) segmentNameSelect.appendChild(newOpt);
            
            // Add existing segments
            segments.forEach(seg => {
                if (seg.segment_name) {
                    const option = document.createElement('option');
                    option.value = seg.segment_name;
                    option.textContent = seg.segment_name;
                    segmentNameSelect.appendChild(option);
                }
            });
        }
        
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
                        <button onclick="editSegment(${seg.segment_id})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px; margin-right: 4px;">Edit</button>
                        <button onclick="viewSegmentMembersById(${seg.segment_id})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;">View Members</button>
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

// Generate segment name suggestions based on selected fields
function generateSegmentName() {
    const geographicScope = document.getElementById('geographic_scope').value;
    const locationRef = document.getElementById('location_reference').value;
    const sectorType = document.getElementById('sector_type').value;
    const riskLevel = document.getElementById('risk_level').value;
    const basis = document.getElementById('basis_of_segmentation').value;
    
    let suggestions = [];
    
    // Build name parts
    const parts = [];
    
    if (riskLevel) {
        parts.push(riskLevel + '-Risk');
    }
    
    if (sectorType) {
        parts.push(sectorType);
    }
    
    if (locationRef) {
        // Extract barangay name (remove "Barangay" prefix if present)
        const barangayName = locationRef.replace(/^Barangay\s+/i, '');
        if (geographicScope === 'Barangay') {
            parts.push('in ' + barangayName);
        } else if (geographicScope) {
            parts.push('in ' + barangayName + ' (' + geographicScope + ')');
        } else {
            parts.push('in ' + barangayName);
        }
    } else if (geographicScope) {
        parts.push('(' + geographicScope + ')');
    }
    
    // Generate multiple suggestions
    if (parts.length > 0) {
        suggestions.push(parts.join(' '));
        
        // Alternative formats
        if (sectorType && locationRef) {
            suggestions.push(sectorType + ' - ' + locationRef.replace(/^Barangay\s+/i, ''));
        }
        if (riskLevel && sectorType) {
            suggestions.push(riskLevel + ' Risk ' + sectorType + ' Segment');
        }
        if (sectorType && basis) {
            suggestions.push(sectorType + ' (' + basis + ')');
        }
    }
    
    // Show suggestions and auto-select first one if available
    if (suggestions.length > 0) {
        const suggestionsDiv = document.getElementById('segmentNameSuggestions');
        const suggestionsList = document.getElementById('suggestionsList');
        const segmentNameInput = document.getElementById('segment_name');
        
        // Auto-set the first suggestion if creating new segment
        const segmentNameSelect = document.getElementById('segment_name');
        const segmentNameNew = document.getElementById('segment_name_new');
        if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew && !segmentNameNew.value.trim()) {
            segmentNameNew.value = suggestions[0];
            updateSegmentPreview();
        }
        
        suggestionsList.innerHTML = suggestions.map((suggestion, index) => `
            <div onclick="selectSuggestion('${suggestion.replace(/'/g, "\\'")}')" 
                 style="padding: 10px 12px; cursor: pointer; border-bottom: 1px solid #e2e8f0; transition: background 0.2s;"
                 onmouseover="this.style.background='#f1f5f9'" 
                 onmouseout="this.style.background='white'">
                <div style="font-weight: 500; color: #0f172a;">${suggestion}</div>
            </div>
        `).join('');
        
        suggestionsDiv.style.display = 'block';
    } else {
        document.getElementById('segmentNameSuggestions').style.display = 'none';
    }
}

// Update suggestions as user types or changes fields
function updateSegmentNameSuggestions() {
    const nameInput = document.getElementById('segment_name');
    const currentValue = nameInput.value.trim();
    
    // Only show suggestions if input is empty or very short
    if (currentValue.length < 3) {
        generateSegmentName();
    } else {
        document.getElementById('segmentNameSuggestions').style.display = 'none';
    }
    
    // Update preview
    updateSegmentPreview();
}

// Select a suggestion
function selectSuggestion(suggestion) {
    const segmentNameSelect = document.getElementById('segment_name');
    const segmentNameNew = document.getElementById('segment_name_new');
    
    if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew) {
        segmentNameNew.value = suggestion;
    } else if (segmentNameSelect) {
        // If not in "new" mode, switch to new mode and set value
        segmentNameSelect.value = '__new__';
        if (segmentNameNew) {
            segmentNameNew.style.display = 'block';
            segmentNameNew.required = true;
            segmentNameNew.value = suggestion;
        }
    }
    
    document.getElementById('segmentNameSuggestions').style.display = 'none';
    updateSegmentPreview();
}

// Update segment preview
function updateSegmentPreview() {
    const preview = document.getElementById('segmentPreview');
    const previewContent = document.getElementById('previewContent');
    
    // Get segment name from dropdown or new input
    const segmentNameSelect = document.getElementById('segment_name');
    const segmentNameNew = document.getElementById('segment_name_new');
    let segmentName = '';
    
    if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew) {
        segmentName = segmentNameNew.value.trim();
    } else if (segmentNameSelect && segmentNameSelect.value) {
        segmentName = segmentNameSelect.value.trim();
    }
    
    const geographicScope = document.getElementById('geographic_scope').value;
    const locationRef = document.getElementById('location_reference').value;
    const sectorType = document.getElementById('sector_type').value;
    const riskLevel = document.getElementById('risk_level').value;
    const basis = document.getElementById('basis_of_segmentation').value;
    
    if (!segmentName && !geographicScope && !locationRef && !sectorType && !riskLevel && !basis) {
        preview.style.display = 'none';
        return;
    }
    
    let previewHtml = '';
    
    if (segmentName) {
        previewHtml += `<div style="margin-bottom: 8px;"><strong>Name:</strong> ${segmentName}</div>`;
    }
    
    const details = [];
    if (riskLevel) details.push(`<span style="background: ${riskLevel === 'High' ? '#fee2e2' : riskLevel === 'Medium' ? '#fef3c7' : '#d1fae5'}; color: ${riskLevel === 'High' ? '#991b1b' : riskLevel === 'Medium' ? '#92400e' : '#065f46'}; padding: 2px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">${riskLevel} Risk</span>`);
    if (sectorType) details.push(`<strong>Sector:</strong> ${sectorType}`);
    if (locationRef) details.push(`<strong>Location:</strong> ${locationRef}`);
    if (geographicScope) details.push(`<strong>Scope:</strong> ${geographicScope}`);
    if (basis) details.push(`<strong>Basis:</strong> ${basis}`);
    
    if (details.length > 0) {
        previewHtml += `<div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">${details.join(' • ')}</div>`;
    }
    
    if (previewHtml) {
        previewContent.innerHTML = previewHtml;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
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
    document.getElementById('segmentNameSuggestions').style.display = 'none';
    document.getElementById('segmentPreview').style.display = 'none';
    document.getElementById('createStatus').textContent = '';
    document.getElementById('createStatus').style.color = '';
}

// Show help modal
function showSegmentHelp() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 20px;">💡 How to Create Segments</h3>
            <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="color: #475569; line-height: 1.6;">
            <div style="margin-bottom: 16px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 8px;">✨ Auto-Generate Segment Name:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Fill in the fields below (Risk Level, Sector Type, Location, etc.)</li>
                    <li>Click the "Auto-Generate" button next to Segment Name</li>
                    <li>Select a suggestion from the dropdown, or customize it</li>
                </ul>
            </div>
            <div style="margin-bottom: 16px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 8px;">📋 Required Fields:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Segment Name</strong> - Must be unique</li>
                    <li><strong>Risk Level</strong> - Must be explicitly assigned (Low, Medium, or High)</li>
                </ul>
            </div>
            <div style="margin-bottom: 16px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 8px;">📍 Location Guidelines:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>If you select a Geographic Scope, select a Location Reference (Barangay)</li>
                    <li>Only Quezon City barangays are allowed</li>
                    <li>You can target Barangay, Zone, or Purok level</li>
                </ul>
            </div>
            <div style="margin-bottom: 16px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 8px;">👁️ Segment Preview:</strong>
                <p style="margin: 4px 0;">As you fill in fields, a preview will appear showing how your segment will look. This helps ensure accuracy before creating.</p>
            </div>
            <div style="background: #f0fdfa; padding: 12px; border-radius: 8px; border-left: 4px solid #4c8a89;">
                <strong style="color: #065f46;">💡 Pro Tip:</strong> Start by selecting Risk Level and Sector Type, then use Auto-Generate for quick, consistent naming!
            </div>
        </div>
        <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
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
        statusEl.textContent = '✗ Error: Segment Name is required';
        statusEl.style.color = '#dc2626';
        if (segmentNameSelect && segmentNameSelect.value === '__new__' && segmentNameNew) {
            segmentNameNew.focus();
        } else if (segmentNameSelect) {
            segmentNameSelect.focus();
        }
        return;
    }
    
    if (!riskLevel) {
        statusEl.textContent = '✗ Error: Risk Level must be explicitly assigned';
        statusEl.style.color = '#dc2626';
        document.getElementById('risk_level').focus();
        return;
    }
    
    if (geographicScope && !locationRef) {
        statusEl.textContent = '✗ Error: Location Reference is required when Geographic Scope is selected';
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
            statusEl.textContent = '✓ Segment created successfully! You can now view it in "All Segments" or add members to it using "Import Members".';
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
            // Hide technical errors
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database') || errorMsg.toLowerCase().includes('duplicate')) {
                statusEl.textContent = '⚠️ Unable to create segment. This segment name may already exist, or there was a system issue. Please try a different name or contact the administrator.';
            } else if (errorMsg.toLowerCase().includes('required') || errorMsg.toLowerCase().includes('missing')) {
                statusEl.textContent = '⚠️ ' + (errorMsg || 'Please fill in all required fields.');
            } else {
                statusEl.textContent = '⚠️ ' + (errorMsg || 'Unable to create segment. Please check all fields and try again.');
            }
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '⚠️ Connection problem. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

// Close suggestions when clicking outside
document.addEventListener('click', function(e) {
    const suggestions = document.getElementById('segmentNameSuggestions');
    const nameInput = document.getElementById('segment_name');
    if (suggestions && !suggestions.contains(e.target) && e.target !== nameInput) {
        suggestions.style.display = 'none';
    }
});

// Edit segment (placeholder - would open edit modal)
function editSegment(segmentId) {
    alert('Edit functionality: Load segment ' + segmentId + ' data into form for editing');
    // TODO: Implement edit modal or form population
}

// View segment members by ID (used from segments list)
async function viewSegmentMembersById(segmentId) {
    const select = document.getElementById('viewMembersSegmentId');
    if (select) {
        select.value = segmentId;
        await viewSegmentMembers();
        // Scroll to members section
        setTimeout(() => {
            document.getElementById('audience-members').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    }
}

async function viewSegmentMembers() {
    const segmentId = document.getElementById('viewMembersSegmentId').value;
    if (!segmentId) {
        document.getElementById('audienceMembersEmptyState').style.display = 'block';
        document.getElementById('audienceMembersContainer').innerHTML = '';
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const container = document.getElementById('audienceMembersContainer');
    const emptyState = document.getElementById('audienceMembersEmptyState');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading members...</p>';
    emptyState.style.display = 'none';
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/members', {
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
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database') || errorMsg.toLowerCase().includes('not found')) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load members</p><p style="margin:0; font-size:14px;">We couldn\'t load members for this segment right now. Please try again or contact the administrator.</p></div>';
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load members</p><p style="margin:0; font-size:14px;">' + (errorMsg || 'Please try again or contact the administrator.') + '</p></div>';
            }
            emptyState.style.display = 'none';
            return;
        }
        
        const members = data.data || [];
        
        if (members.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'block';
            emptyState.querySelector('p:last-child').innerHTML = 'This segment has no members yet. Use the "Import Members" section below to add residents to this segment.';
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
                    <td><strong>${member.name || 'N/A'}</strong></td>
                    <td>${member.sector || '—'}</td>
                    <td>${member.barangay || '—'}</td>
                    <td>${member.zone || '—'}</td>
                    <td>${member.purok || '—'}</td>
                    <td>${member.contact || '—'}</td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            <p style="margin-top: 16px; color: #64748b; font-size: 14px;"><strong>Total Members:</strong> ${members.length}</p>
        `;
        
        container.innerHTML = html;
        emptyState.style.display = 'none';
    } catch (err) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        emptyState.style.display = 'none';
    }
}

// View participation history
async function viewParticipationHistory() {
    const segmentId = document.getElementById('viewHistorySegmentId').value;
    if (!segmentId) {
        document.getElementById('participationHistoryEmptyState').style.display = 'block';
        document.getElementById('segmentAnalyticsContainer').innerHTML = '';
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const container = document.getElementById('segmentAnalyticsContainer');
    const emptyState = document.getElementById('participationHistoryEmptyState');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading participation history...</p>';
    emptyState.style.display = 'none';
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/participation-history', {
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
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('database') || errorMsg.toLowerCase().includes('not found')) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load history</p><p style="margin:0; font-size:14px;">We couldn\'t load participation history for this segment right now. Please try again or contact the administrator.</p></div>';
            } else {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load history</p><p style="margin:0; font-size:14px;">' + (errorMsg || 'Please try again or contact the administrator.') + '</p></div>';
            }
            emptyState.style.display = 'none';
            return;
        }
        
        const history = data.data || [];
        
        if (history.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'block';
            emptyState.querySelector('p:last-child').innerHTML = 'No history found for this segment yet. This segment has not participated in any campaigns or events. Once members of this segment attend events or respond to campaigns, their participation will appear here.';
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
                    <td><strong>${record.campaign_name || 'N/A'}</strong></td>
                    <td>${record.event_name || '—'}</td>
                    <td>${record.event_type || '—'}</td>
                    <td>${eventDate}</td>
                    <td>${record.attendance_count || 0}</td>
                    <td>${record.member_name || '—'}</td>
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
        emptyState.style.display = 'none';
    } catch (err) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        emptyState.style.display = 'none';
    }
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
    
    // Add real-time preview updates
    ['geographic_scope', 'location_reference', 'sector_type', 'risk_level', 'basis_of_segmentation'].forEach(id => {
        const field = document.getElementById(id);
        if (field) {
            field.addEventListener('change', updateSegmentPreview);
        }
    });
    
    // Update preview on name input
    const nameInput = document.getElementById('segment_name');
    if (nameInput) {
        nameInput.addEventListener('input', updateSegmentPreview);
    }
    
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
