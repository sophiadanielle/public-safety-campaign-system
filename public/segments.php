<?php
$pageTitle = 'Audience Segments';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
// Wrapped in try-catch to prevent 502 errors
$isViewer = false;
$currentUserRole = null;
try {
    @require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
} catch (\Throwable $e) {
    error_log('segments.php: block_viewer_access failed: ' . $e->getMessage());
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="<?php echo htmlspecialchars($publicPath . '/js/custom-modals.js'); ?>"></script>
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
    try {
        include __DIR__ . '/../sidebar/includes/sidebar.php'; 
    } catch (\Throwable $e) {
        error_log('segments.php: sidebar.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/admin-header.php'; 
    } catch (\Throwable $e) {
        error_log('segments.php: admin-header.php failed: ' . $e->getMessage());
    }
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
    .segments-page {
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 class="section-title" style="margin: 0; border: none; padding: 0;">
                All Segments
            </h2>
            <div style="display: flex; gap: 8px;">
                <button class="btn btn-primary" onclick="openCreateSegmentModal()" style="padding: 10px 16px; font-size: 14px;">
                    <i class="fas fa-plus"></i> Create Segment
                </button>
                <button class="btn btn-secondary" onclick="toggleArchivedSegments()" style="padding: 10px 16px; font-size: 14px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
                <button class="btn btn-secondary" onclick="loadSegments()" style="padding: 10px 16px; font-size: 14px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        
        <!-- Search and Filters -->
        <div style="background: #f8fafc; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Search</label>
                    <input type="text" id="segmentSearchInput" placeholder="Search segments..." onkeyup="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Geographic Scope</label>
                    <select id="filterGeographicScope" onchange="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Scopes</option>
                        <option value="Barangay">Barangay</option>
                        <option value="Zone">Zone</option>
                        <option value="Purok">Purok</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Location</label>
                    <select id="filterLocation" onchange="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Locations</option>
                        <option value="Barangay Batasan Hills">Batasan Hills</option>
                        <option value="Barangay Commonwealth">Commonwealth</option>
                        <option value="Barangay Holy Spirit">Holy Spirit</option>
                        <option value="Barangay Payatas">Payatas</option>
                        <option value="Barangay Bagong Silangan">Bagong Silangan</option>
                        <option value="Barangay Tandang Sora">Tandang Sora</option>
                        <option value="Barangay Fairview">Fairview</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Sector Type</label>
                    <select id="filterSectorType" onchange="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Sectors</option>
                        <option value="Households">Households</option>
                        <option value="Youth">Youth</option>
                        <option value="Senior Citizens">Senior Citizens</option>
                        <option value="Schools">Schools</option>
                        <option value="NGOs">NGOs</option>
                        <option value="Person with Disabilities">PWD</option>
                        <option value="Pregnant Women">Pregnant Women</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Risk Level</label>
                    <select id="filterRiskLevel" onchange="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Levels</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 4px;">Basis</label>
                    <select id="filterBasis" onchange="filterSegments()" style="width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                        <option value="">All Basis</option>
                        <option value="Historical trend">Historical trend</option>
                        <option value="Inspection results">Inspection results</option>
                        <option value="Attendance records">Attendance records</option>
                        <option value="Incident pattern reference">Incident pattern</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="empty-state" id="segmentsListEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-users"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No segments found</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">No segments match your filters. Try adjusting your search criteria or create a new segment.</p>
        </div>
        <div id="segmentsListContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Loading segments...</p>
        </div>
        
        <!-- Pagination -->
        <div id="segmentsPagination" style="display: none; justify-content: center; align-items: center; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #e2e8f0;"></div>
    </section>
    
    <!-- Archived Segments Modal -->
    <div id="archivedSegmentsModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 900px; width: 90%; max-height: 80vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="fas fa-archive"></i> Archived Segments</h3>
                <button onclick="closeArchivedSegmentsModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
            </div>
            <div id="archivedSegmentsList"></div>
        </div>
    </div>

    <!-- Create Segment Modal -->
    <div id="createSegmentModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a;" id="createSegmentModalTitle"><i class="fas fa-plus-circle"></i> Create Segment</h3>
                <button onclick="closeCreateSegmentModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
            </div>
            <form id="createForm" class="form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Segment Name *</label>
                    <input type="text" id="modal_segment_name" placeholder="Enter segment name..." required style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                </div>
                <div class="form-field">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Geographic Scope</label>
                    <select id="modal_geographic_scope" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select...</option>
                        <option value="Barangay">Barangay</option>
                        <option value="Zone">Zone</option>
                        <option value="Purok">Purok</option>
                    </select>
                </div>
                <div class="form-field">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Location Reference</label>
                    <select id="modal_location_reference" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select Barangay...</option>
                        <option value="Barangay Batasan Hills">Barangay Batasan Hills</option>
                        <option value="Barangay Commonwealth">Barangay Commonwealth</option>
                        <option value="Barangay Holy Spirit">Barangay Holy Spirit</option>
                        <option value="Barangay Payatas">Barangay Payatas</option>
                        <option value="Barangay Bagong Silangan">Barangay Bagong Silangan</option>
                        <option value="Barangay Tandang Sora">Barangay Tandang Sora</option>
                        <option value="Barangay Fairview">Barangay Fairview</option>
                    </select>
                </div>
                <div class="form-field">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Sector Type</label>
                    <select id="modal_sector_type" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
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
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Risk Level *</label>
                    <select id="modal_risk_level" required style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select...</option>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label style="font-weight: 600; margin-bottom: 6px; display: block;">Basis of Segmentation</label>
                    <select id="modal_basis_of_segmentation" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px;">
                        <option value="">Select...</option>
                        <option value="Historical trend">Historical trend</option>
                        <option value="Inspection results">Inspection results</option>
                        <option value="Attendance records">Attendance records</option>
                        <option value="Incident pattern reference">Incident pattern reference</option>
                    </select>
                </div>
            </form>
            <div id="modalCreateStatus" style="margin-top: 12px; padding: 8px; border-radius: 6px;"></div>
            <div style="display: flex; gap: 8px; margin-top: 16px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="closeCreateSegmentModal()" style="padding: 10px 20px;">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveSegmentFromModal()" id="saveSegmentBtn" style="padding: 10px 20px;">Create Segment</button>
            </div>
        </div>
    </div>

    <!-- Hidden Create Segment Section (kept for backward compatibility) -->
    <section id="create-segment" class="card" style="display: none; margin-bottom:32px;">
        <h2 class="section-title">
            <span class="section-step">Step 2</span>
            Create Segment
        </h2>
        <form id="createFormOld" class="form-grid">
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
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e2e8f0;">
                <a href="samples/sample_members_import.csv" download class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; font-size: 13px; text-decoration: none;">
                    <i class="fas fa-download"></i> Download Sample CSV File
                </a>
                <span style="margin-left: 8px; color: #94a3b8; font-size: 12px;">Use this as a template for your import</span>
            </div>
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
    window.location.replace(basePath + '/login.php');
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
            const segmentId = seg.segment_id || seg.id;
            const segmentName = seg.segment_name || seg.name || 'Unnamed Segment';
            option.value = segmentId;
            option.textContent = segmentName;
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
            const segmentId = seg.segment_id || seg.id;
            const segmentName = seg.segment_name || seg.name || 'Unnamed Segment';
            option.value = segmentId;
            option.textContent = segmentName;
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
            const segmentId = seg.segment_id || seg.id;
            const segmentName = seg.segment_name || seg.name || 'Unnamed Segment';
            option.value = segmentId;
            option.textContent = segmentName;
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
            const segmentName = seg.segment_name || seg.name;
            if (segmentName) {
                const option = document.createElement('option');
                option.value = segmentName;
                option.textContent = segmentName;
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

// Pagination variables
let currentSegmentsPage = 1;
const segmentsPerPage = 10;
let filteredSegments = [];

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
            container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Unable to load segments</p><p style="margin:0; font-size:14px;">Please try again or contact the administrator.</p></div>';
            emptyState.style.display = 'none';
            return;
        }
        
        const segments = data.data || [];
        // Filter out archived segments
        allSegmentsCache = segments.filter(s => !s.is_archived);
        
        // Populate dropdowns
        populateSegmentDropdowns(allSegmentsCache);
        
        // Apply filters and render
        filterSegments();
    } catch (err) {
        container.innerHTML = '<div class="empty-state"><div class="empty-state-icon"><i class="fas fa-wifi"></i></div><p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">Connection problem</p><p style="margin:0; font-size:14px;">We couldn\'t connect to the server. Please check your internet connection and try again.</p></div>';
        emptyState.style.display = 'none';
    }
}

// Filter segments based on search and filter inputs
function filterSegments() {
    const search = (document.getElementById('segmentSearchInput')?.value || '').toLowerCase().trim();
    const geoScope = document.getElementById('filterGeographicScope')?.value || '';
    const location = document.getElementById('filterLocation')?.value || '';
    const sectorType = document.getElementById('filterSectorType')?.value || '';
    const riskLevel = document.getElementById('filterRiskLevel')?.value || '';
    const basis = document.getElementById('filterBasis')?.value || '';
    
    filteredSegments = allSegmentsCache.filter(seg => {
        const name = (seg.segment_name || seg.name || '').toLowerCase();
        if (search && !name.includes(search)) return false;
        if (geoScope && seg.geographic_scope !== geoScope) return false;
        if (location && seg.location_reference !== location) return false;
        if (sectorType && seg.sector_type !== sectorType) return false;
        if (riskLevel && seg.risk_level !== riskLevel) return false;
        if (basis && seg.basis_of_segmentation !== basis) return false;
        return true;
    });
    
    currentSegmentsPage = 1;
    renderSegmentsTable();
}

// Render segments table with pagination
function renderSegmentsTable() {
    const container = document.getElementById('segmentsListContainer');
    const emptyState = document.getElementById('segmentsListEmptyState');
    const paginationEl = document.getElementById('segmentsPagination');
    
    if (filteredSegments.length === 0) {
        container.innerHTML = '';
        emptyState.style.display = 'block';
        paginationEl.style.display = 'none';
        return;
    }
    
    emptyState.style.display = 'none';
    
    // Pagination
    const totalPages = Math.ceil(filteredSegments.length / segmentsPerPage);
    const startIdx = (currentSegmentsPage - 1) * segmentsPerPage;
    const endIdx = startIdx + segmentsPerPage;
    const pageSegments = filteredSegments.slice(startIdx, endIdx);
    
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
    
    pageSegments.forEach(seg => {
        const riskClass = seg.risk_level ? seg.risk_level.toLowerCase() : '';
        const segmentId = seg.segment_id || seg.id;
        const segmentName = seg.segment_name || seg.name || 'N/A';
        html += `
            <tr>
                <td>#${segmentId}</td>
                <td><strong>${segmentName}</strong></td>
                <td>${seg.geographic_scope || '—'}</td>
                <td>${seg.location_reference || '—'}</td>
                <td>${seg.sector_type || '—'}</td>
                <td>${seg.risk_level ? `<span class="badge ${riskClass}">${seg.risk_level}</span>` : '—'}</td>
                <td>${seg.basis_of_segmentation || '—'}</td>
                <td>
                    <button onclick="viewSegment(${segmentId})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; margin: 1px;">View</button>
                    <button onclick="openEditSegmentModal(${segmentId})" class="btn btn-secondary" style="padding: 4px 8px; font-size: 11px; margin: 1px;">Edit</button>
                    <button onclick="archiveSegment(${segmentId})" class="btn btn-warning" style="padding: 4px 8px; font-size: 11px; margin: 1px; background: #f59e0b; color: white; border: none;">Archive</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
    
    // Render pagination
    if (totalPages > 1) {
        paginationEl.style.display = 'flex';
        paginationEl.innerHTML = `
            <button onclick="goToSegmentsPage(${currentSegmentsPage - 1})" ${currentSegmentsPage === 1 ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #e2e8f0; background: white; border-radius: 4px; cursor: pointer;">Previous</button>
            <span style="padding: 6px 12px; color: #64748b;">Page ${currentSegmentsPage} of ${totalPages} (${filteredSegments.length} segments)</span>
            <button onclick="goToSegmentsPage(${currentSegmentsPage + 1})" ${currentSegmentsPage === totalPages ? 'disabled' : ''} style="padding: 6px 12px; border: 1px solid #e2e8f0; background: white; border-radius: 4px; cursor: pointer;">Next</button>
        `;
    } else {
        paginationEl.style.display = 'none';
    }
}

function goToSegmentsPage(page) {
    const totalPages = Math.ceil(filteredSegments.length / segmentsPerPage);
    if (page < 1 || page > totalPages) return;
    currentSegmentsPage = page;
    renderSegmentsTable();
}

// Archive segment
async function archiveSegment(segmentId) {
    const confirmed = await customConfirm('Are you sure you want to archive this segment? You can restore it later from View Archived.', 'Archive Segment');
    if (!confirmed) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/archive', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        const data = await res.json();
        if (res.ok) {
            await customAlert('Segment archived successfully!', 'Success');
            loadSegments();
        } else {
            await customAlert('Error: ' + (data.error || 'Failed to archive segment'), 'Error');
        }
    } catch (err) {
        await customAlert('Failed to archive segment: ' + err.message, 'Error');
    }
}

// Toggle archived segments modal
function toggleArchivedSegments() {
    const modal = document.getElementById('archivedSegmentsModal');
    modal.style.display = 'flex';
    loadArchivedSegments();
}

function closeArchivedSegmentsModal() {
    document.getElementById('archivedSegmentsModal').style.display = 'none';
}

// Load archived segments
async function loadArchivedSegments() {
    const container = document.getElementById('archivedSegmentsList');
    container.innerHTML = '<p style="text-align: center; color: #64748b;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        
        if (!res.ok) {
            container.innerHTML = '<p style="text-align: center; color: #ef4444;">Failed to load archived segments</p>';
            return;
        }
        
        const archivedSegments = (data.data || []).filter(s => s.is_archived);
        
        if (archivedSegments.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 24px;">No archived segments.</p>';
            return;
        }
        
        let html = '';
        archivedSegments.forEach(seg => {
            const segmentId = seg.segment_id || seg.id;
            html += `
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>${seg.segment_name || seg.name}</strong>
                        <span style="color: #64748b; font-size: 12px; margin-left: 8px;">${seg.geographic_scope || ''} - ${seg.sector_type || ''}</span>
                    </div>
                    <div style="display: flex; gap: 6px;">
                        <button onclick="viewSegmentFromArchived(${segmentId})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; border: 1px solid #e2e8f0; background: white;">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button onclick="restoreSegment(${segmentId})" class="btn btn-success" style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white; border: none;">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                        <button onclick="deleteArchivedSegment(${segmentId})" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white; border: none;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #ef4444;">Error loading archived segments</p>';
    }
}

// Restore segment
async function restoreSegment(segmentId) {
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/restore', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        const data = await res.json();
        if (res.ok) {
            // Close modal first, then show success
            closeArchivedSegmentsModal();
            showSuccessToast('Segment restored successfully!');
            loadSegments();
        } else {
            showErrorToast('Error: ' + (data.error || 'Failed to restore segment'));
        }
    } catch (err) {
        showErrorToast('Failed to restore segment: ' + err.message);
    }
}

// View segment from archived modal
function viewSegmentFromArchived(segmentId) {
    closeArchivedSegmentsModal();
    viewSegment(segmentId);
}

// Delete archived segment permanently
async function deleteArchivedSegment(segmentId) {
    const confirmed = await customConfirm('Are you sure you want to permanently delete this segment? This action cannot be undone.', 'Delete Segment');
    if (!confirmed) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        
        const data = await res.json();
        if (res.ok) {
            showSuccessToast('Segment deleted permanently!');
            loadArchivedSegments();
        } else {
            showErrorToast('Error: ' + (data.error || 'Failed to delete segment'));
        }
    } catch (err) {
        showErrorToast('Failed to delete segment: ' + err.message);
    }
}

// Toast notification functions
function showSuccessToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 16px 24px; border-radius: 8px; z-index: 9999999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-weight: 500;';
    toast.innerHTML = '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
}

function showErrorToast(message) {
    const toast = document.createElement('div');
    toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #ef4444; color: white; padding: 16px 24px; border-radius: 8px; z-index: 9999999; box-shadow: 0 4px 12px rgba(0,0,0,0.15); font-weight: 500;';
    toast.innerHTML = '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>' + message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 4000);
}

// Create Segment Modal functions
let editingSegmentId = null;

function openCreateSegmentModal() {
    editingSegmentId = null;
    document.getElementById('createSegmentModalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Create Segment';
    document.getElementById('saveSegmentBtn').textContent = 'Create Segment';
    document.getElementById('modal_segment_name').value = '';
    document.getElementById('modal_geographic_scope').value = '';
    document.getElementById('modal_location_reference').value = '';
    document.getElementById('modal_sector_type').value = '';
    document.getElementById('modal_risk_level').value = '';
    document.getElementById('modal_basis_of_segmentation').value = '';
    document.getElementById('modalCreateStatus').textContent = '';
    document.getElementById('createSegmentModal').style.display = 'flex';
}

function openEditSegmentModal(segmentId) {
    editingSegmentId = segmentId;
    const seg = allSegmentsCache.find(s => (s.segment_id || s.id) == segmentId);
    if (!seg) {
        customAlert('Segment not found', 'Error');
        return;
    }
    
    document.getElementById('createSegmentModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Segment';
    document.getElementById('saveSegmentBtn').textContent = 'Update Segment';
    document.getElementById('modal_segment_name').value = seg.segment_name || seg.name || '';
    document.getElementById('modal_geographic_scope').value = seg.geographic_scope || '';
    document.getElementById('modal_location_reference').value = seg.location_reference || '';
    document.getElementById('modal_sector_type').value = seg.sector_type || '';
    document.getElementById('modal_risk_level').value = seg.risk_level || '';
    document.getElementById('modal_basis_of_segmentation').value = seg.basis_of_segmentation || '';
    document.getElementById('modalCreateStatus').textContent = '';
    document.getElementById('createSegmentModal').style.display = 'flex';
}

function closeCreateSegmentModal() {
    document.getElementById('createSegmentModal').style.display = 'none';
    editingSegmentId = null;
}

async function saveSegmentFromModal() {
    const statusEl = document.getElementById('modalCreateStatus');
    const segmentName = document.getElementById('modal_segment_name').value.trim();
    const riskLevel = document.getElementById('modal_risk_level').value;
    
    if (!segmentName) {
        statusEl.textContent = 'Error: Segment Name is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    if (!riskLevel) {
        statusEl.textContent = 'Error: Risk Level is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    const payload = {
        segment_name: segmentName,
        geographic_scope: document.getElementById('modal_geographic_scope').value || null,
        location_reference: document.getElementById('modal_location_reference').value || null,
        sector_type: document.getElementById('modal_sector_type').value || null,
        risk_level: riskLevel,
        basis_of_segmentation: document.getElementById('modal_basis_of_segmentation').value || null
    };
    
    statusEl.textContent = editingSegmentId ? 'Updating...' : 'Creating...';
    statusEl.style.color = '#64748b';
    
    try {
        const url = editingSegmentId 
            ? apiBase + '/api/v1/segments/' + editingSegmentId 
            : apiBase + '/api/v1/segments';
        const method = editingSegmentId ? 'PUT' : 'POST';
        
        const res = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify(payload)
        });
        
        const data = await res.json();
        
        if (res.ok) {
            statusEl.textContent = editingSegmentId ? 'Segment updated!' : 'Segment created!';
            statusEl.style.color = '#166534';
            setTimeout(() => {
                closeCreateSegmentModal();
                loadSegments();
            }, 1000);
        } else {
            statusEl.textContent = 'Error: ' + (data.error || 'Failed to save segment');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = 'Error: ' + err.message;
        statusEl.style.color = '#dc2626';
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
    const form = document.getElementById('createForm');
    if (form) {
        form.reset();
        // Clear segment ID from form dataset (for edit mode)
        if (form.dataset.segmentId) {
            delete form.dataset.segmentId;
        }
    }
    
    // Reset submit button text
    const submitBtn = document.getElementById('createSegmentBtn');
    if (submitBtn) {
        submitBtn.textContent = 'Create Segment';
    }
    
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
    
    const form = document.getElementById('createForm');
    const segmentId = form ? form.dataset.segmentId : null;
    
    // Check if this is an update
    if (segmentId) {
        statusEl.textContent = 'Updating...';
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
            const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + currentToken },
                body: JSON.stringify(payload)
            });
            
            if (res.status === 401) {
                handleTokenExpiration();
                return;
            }
            
            const data = await res.json();
            if (res.ok) {
                statusEl.textContent = 'Segment updated successfully!';
                statusEl.style.color = '#166534';
                resetForm();
                loadSegments();
            } else {
                const errorMsg = data.error || '';
                if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                    handleTokenExpiration();
                    return;
                }
                statusEl.textContent = 'Error: ' + (data.error || 'Failed to update segment');
                statusEl.style.color = '#dc2626';
            }
        } catch (err) {
            statusEl.textContent = 'Network error: ' + err.message;
            statusEl.style.color = '#dc2626';
        }
        return;
    }
    
    // Create new segment
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
// View segment details
async function viewSegment(segmentId) {
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (data.error) {
            await customAlert('Error: ' + data.error, 'Error');
            return;
        }
        
        const seg = data.data;
        const segId = seg.segment_id || seg.id;
        const segmentName = seg.segment_name || seg.name || 'N/A';
        
        // Format dates
        const formatDate = (dateStr) => {
            if (!dateStr) return 'Not set';
            try {
                return new Date(dateStr).toLocaleString('en-US', {dateStyle: 'long', timeStyle: 'short'});
            } catch (e) {
                return dateStr;
            }
        };
        
        // Risk level colors
        const riskColors = {
            'high': { bg: '#fee2e2', color: '#991b1b', border: '#ef4444' },
            'medium': { bg: '#fef3c7', color: '#92400e', border: '#f59e0b' },
            'low': { bg: '#d1fae5', color: '#065f46', border: '#10b981' }
        };
        const riskStyle = riskColors[seg.risk_level?.toLowerCase()] || { bg: '#f3f4f6', color: '#374151', border: '#9ca3af' };
        
        // Create modal with segment details
        const modal = document.createElement('div');
        modal.id = 'viewSegmentModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(4px);';
        modal.innerHTML = `
            <div style="background: white; border-radius: 16px; max-width: 800px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); display: flex; flex-direction: column;">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 24px 32px; position: relative;">
                    <button id="closeViewSegmentBtn" style="position: absolute; top: 16px; right: 16px; background: rgba(255,255,255,0.2); border: none; color: white; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                        <div>
                            <div style="font-size: 14px; opacity: 0.9; font-weight: 500;">Segment #${segId}</div>
                            <h2 style="margin: 4px 0 0 0; font-size: 28px; font-weight: 700; line-height: 1.2;">${segmentName}</h2>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; margin-top: 16px; flex-wrap: wrap;">
                        ${seg.geographic_scope ? `<span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;">${seg.geographic_scope}</span>` : ''}
                        ${seg.sector_type ? `<span style="background: rgba(255,255,255,0.2); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 500;">${seg.sector_type}</span>` : ''}
                        ${seg.risk_level ? `<span style="background: ${riskStyle.bg}; color: ${riskStyle.color}; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">${seg.risk_level}</span>` : ''}
                    </div>
                </div>
                
                <!-- Content -->
                <div style="padding: 32px; overflow-y: auto; flex: 1;">
                    <!-- Location & Scope -->
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                            Location & Scope
                        </h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                            <div style="background: white; border: 2px solid #e2e8f0; padding: 16px; border-radius: 10px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Geographic Scope</div>
                                <div style="font-size: 15px; font-weight: 600; color: #0f172a;">${seg.geographic_scope || 'Not specified'}</div>
                            </div>
                            <div style="background: white; border: 2px solid #e2e8f0; padding: 16px; border-radius: 10px;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px;">Location Reference</div>
                                <div style="font-size: 15px; font-weight: 600; color: #0f172a;">${seg.location_reference || 'Not specified'}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Segment Details -->
                    <div style="margin-bottom: 24px;">
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                            Segment Information
                        </h3>
                        <div style="display: grid; gap: 16px;">
                            ${seg.sector_type ? `
                                <div style="background: #f0fdfa; padding: 16px; border-radius: 10px; border-left: 4px solid #14b8a6;">
                                    <div style="font-size: 12px; font-weight: 600; color: #115e59; margin-bottom: 6px;">Sector Type</div>
                                    <div style="color: #134e4a; font-size: 15px; font-weight: 500;">${seg.sector_type}</div>
                                </div>
                            ` : ''}
                            ${seg.risk_level ? `
                                <div style="background: ${riskStyle.bg}; padding: 16px; border-radius: 10px; border-left: 4px solid ${riskStyle.border};">
                                    <div style="font-size: 12px; font-weight: 600; color: ${riskStyle.color}; margin-bottom: 6px;">Risk Level</div>
                                    <div style="color: ${riskStyle.color}; font-size: 15px; font-weight: 600;">${seg.risk_level}</div>
                                </div>
                            ` : ''}
                            ${seg.basis_of_segmentation ? `
                                <div style="background: #f8fafc; padding: 16px; border-radius: 10px; border-left: 4px solid #4c8a89;">
                                    <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 6px;">Basis of Segmentation</div>
                                    <div style="color: #475569; font-size: 15px;">${seg.basis_of_segmentation}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    <!-- Metadata -->
                    <div style="padding-top: 20px; border-top: 2px solid #f1f5f9;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; font-size: 13px;">
                            <div>
                                <span style="color: #64748b; font-weight: 500;">Created:</span>
                                <span style="color: #0f172a; font-weight: 600; margin-left: 4px;">${formatDate(seg.created_at)}</span>
                            </div>
                            <div>
                                <span style="color: #64748b; font-weight: 500;">Updated:</span>
                                <span style="color: #0f172a; font-weight: 600; margin-left: 4px;">${formatDate(seg.updated_at)}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style="background: #f8fafc; padding: 20px 32px; border-top: 2px solid #e2e8f0; display: flex; gap: 12px; justify-content: flex-end;">
                    <button id="closeViewSegmentFooterBtn" style="padding: 10px 24px; background: white; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; font-weight: 600; color: #475569; transition: all 0.2s; font-size: 14px;" onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1'" onmouseout="this.style.background='white'; this.style.borderColor='#e2e8f0'">Close</button>
                    <button id="editSegmentFromViewBtn" data-segment-id="${segId}" style="padding: 10px 24px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(76, 138, 137, 0.3); font-size: 14px;" onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(76, 138, 137, 0.4)'" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(76, 138, 137, 0.3)'">Edit Segment</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Add event listeners for close buttons
        const closeTopBtn = document.getElementById('closeViewSegmentBtn');
        const closeFooterBtn = document.getElementById('closeViewSegmentFooterBtn');
        const editBtn = document.getElementById('editSegmentFromViewBtn');
        
        if (closeTopBtn) {
            closeTopBtn.addEventListener('click', () => {
                modal.remove();
            });
        }
        
        if (closeFooterBtn) {
            closeFooterBtn.addEventListener('click', () => {
                modal.remove();
            });
        }
        
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                const segmentId = editBtn.getAttribute('data-segment-id');
                modal.remove();
                editSegment(parseInt(segmentId));
            });
        }
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    } catch (err) {
        await customAlert('Failed to load segment: ' + err.message, 'Error');
    }
}

// Edit segment
async function editSegment(segmentId) {
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (data.error) {
            await customAlert('Error: ' + data.error, 'Error');
            return;
        }
        
        const seg = data.data;
        const segmentName = seg.segment_name || seg.name || '';
        
        // Populate form with segment data
        document.getElementById('segment_name').value = segmentName;
        document.getElementById('geographic_scope').value = seg.geographic_scope || '';
        document.getElementById('location_reference').value = seg.location_reference || '';
        document.getElementById('sector_type').value = seg.sector_type || '';
        document.getElementById('risk_level').value = seg.risk_level || '';
        document.getElementById('basis_of_segmentation').value = seg.basis_of_segmentation || '';
        
        // Store segment ID for update
        const form = document.getElementById('createForm');
        if (form) {
            form.dataset.segmentId = segmentId;
        }
        
        // Change submit button text
        const submitBtn = document.getElementById('createSegmentBtn');
        if (submitBtn) {
            submitBtn.textContent = 'Update Segment';
        }
        
        // Scroll to form
        document.getElementById('create-segment').scrollIntoView({ behavior: 'smooth', block: 'start' });
        
    } catch (err) {
        await customAlert('Failed to load segment: ' + err.message, 'Error');
    }
}

// Delete segment
async function deleteSegment(segmentId) {
    const confirmed = await customConfirm('Are you sure you want to delete this segment? This action cannot be undone and will remove all member associations.', 'Delete Segment');
    if (!confirmed) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + getToken()
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            const errorMsg = data.error || 'Failed to delete segment';
            // Handle database table missing error
            if (errorMsg.toLowerCase().includes('sqlstate') || errorMsg.toLowerCase().includes('table') || errorMsg.toLowerCase().includes('1146')) {
                await customAlert('Unable to delete segment due to a database configuration issue. Please contact the system administrator.', 'Database Error');
            } else {
                await customAlert('Error: ' + errorMsg, 'Delete Failed');
            }
            return;
        }
        
        await customAlert('Segment deleted successfully!', 'Success');
        loadSegments();
    } catch (err) {
        await customAlert('Failed to delete segment: ' + err.message, 'Error');
    }
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

