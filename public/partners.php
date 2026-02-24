<?php
$pageTitle = 'Partner Management';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
// Wrapped in try-catch to prevent 502 errors
$isViewer = false;
$currentUserRole = null;
try {
    @require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
} catch (\Throwable $e) {
    error_log('partners.php: block_viewer_access failed: ' . $e->getMessage());
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
    <script src="<?php echo htmlspecialchars($publicPath . '/js/modal-replacer.js'); ?>"></script>
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
<body class="module-partners" data-module="partners">
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/sidebar.php'; 
    } catch (\Throwable $e) {
        error_log('partners.php: sidebar.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/admin-header.php'; 
    } catch (\Throwable $e) {
        error_log('partners.php: admin-header.php failed: ' . $e->getMessage());
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
    .partners-page {
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

<main class="partners-page">
    <div class="page-header">
        <h1>Partner Management</h1>
        <p>Manage partnerships with schools, NGOs, and other organizations to support your public safety campaigns</p>
    </div>

    <!-- All Partners -->
    <section class="card" id="partners-list" style="margin-bottom:32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="section-title" style="margin: 0; border: none; padding: 0;">All Partners</h2>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-primary" onclick="openAddPartnerModal()" style="padding: 10px 16px; font-size: 14px; font-weight: 600;">
                    <i class="fas fa-plus-circle" style="margin-right: 6px;"></i>Add Partner
                </button>
                <button type="button" class="btn btn-secondary" onclick="showArchivedPartners()" style="padding: 10px 16px; font-size: 14px;">
                    <i class="fas fa-archive" style="margin-right: 6px;"></i>View Archived
                </button>
                <button type="button" class="btn btn-secondary" onclick="loadAllPartners()" style="padding: 10px 16px; font-size: 14px;">
                    <i class="fas fa-sync-alt" style="margin-right: 6px;"></i>Refresh
                </button>
            </div>
        </div>
        <div class="section-description">
            <strong>What this shows:</strong> View all partner organizations registered in the system. This includes schools, NGOs, government agencies, and private organizations that collaborate with your barangay on public safety campaigns.
        </div>
        <div class="empty-state" id="partnersListEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-handshake"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No partners loaded yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">Click <strong>"View All Partners"</strong> above to see all registered partner organizations. If no partners appear, you can add new partners using the "Add Partner" section below.</p>
        </div>
        <div style="overflow-x:auto; margin-top:16px;">
            <table class="data-table" id="partnersTable" style="display:none;">
                <thead>
                    <tr>
                        <th>Partner Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="partnersTableBody">
                </tbody>
            </table>
        </div>
        <div class="status" id="partnersListStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Add Partner Modal -->
    <div id="addPartnerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 12px; max-width: 700px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px; border-radius: 12px 12px 0 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;" id="addPartnerModalTitle"><i class="fas fa-plus-circle"></i> Add Partner</h3>
                    <button onclick="closeAddPartnerModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: white; line-height: 1;">&times;</button>
                </div>
            </div>
            <div style="padding: 24px;">
                <p style="color: #64748b; margin: 0 0 20px 0; font-size: 14px;">Register a new partner organization that will collaborate with your barangay on public safety campaigns.</p>
                <form id="partnerForm" class="form-grid" style="gap: 16px;">
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Organization Name <span style="color:#dc2626;">*</span></label>
                <input id="p_name" type="text" placeholder="Example: Red Cross Quezon City, Barangay Elementary School, Local NGO Name" required style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Required:</strong> Enter the full official name of the organization (school, NGO, agency, etc.)</div>
            </div>
            <div class="form-field">
                <label>Organization Type</label>
                <select id="p_type" style="font-size:15px; padding:12px 16px;">
                    <option value="school">School</option>
                    <option value="ngo">NGO (Non-Government Organization)</option>
                    <option value="government">Government Agency</option>
                    <option value="private">Private Organization</option>
                    <option value="other">Other</option>
                </select>
                <div class="helper-text">💡 Select the type of organization this partner represents</div>
            </div>
            <div class="form-field">
                <label>Contact Person</label>
                <input id="p_person" type="text" placeholder="Example: John Doe, Maria Santos" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 Name of the main contact person at this organization (optional)</div>
            </div>
            <div class="form-field">
                <label>Email Address</label>
                <input id="p_email" type="email" placeholder="Example: contact@organization.com" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 Email address for contacting this partner (optional)</div>
            </div>
            <div class="form-field">
                <label>Phone Number</label>
                <input id="p_phone" type="text" placeholder="Example: +63-2-1234-5678 or 0912-345-6789" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 Phone number for contacting this partner (optional)</div>
            </div>
        </form>
        <div class="form-field" style="margin-top:20px;">
            <button type="submit" form="partnerForm" class="btn btn-primary" onclick="addPartner(event)" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-plus-circle" style="margin-right:8px;"></i>Save Partner Organization
            </button>
        </div>
        <div class="status" id="partnerStatus" style="margin-top:12px;"></div>
            </div>
        </div>
    </div>
    <!-- End Add Partner Modal -->

    <!-- View Partner Modal -->
    <div id="viewPartnerModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px; border-radius: 12px 12px 0 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;"><i class="fas fa-building"></i> Partner Details</h3>
                    <button onclick="closeViewPartnerModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: white; line-height: 1;">&times;</button>
                </div>
            </div>
            <div id="viewPartnerContent" style="padding: 24px;"></div>
        </div>
    </div>

    <!-- Archived Partners Modal -->
    <div id="archivedPartnersModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
        <div style="background: white; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px 24px; border-radius: 12px 12px 0 0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 20px; font-weight: 700;"><i class="fas fa-archive"></i> Archived Partners</h3>
                    <button onclick="closeArchivedPartnersModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: white; line-height: 1;">&times;</button>
                </div>
            </div>
            <div id="archivedPartnersList" style="padding: 24px;"></div>
        </div>
    </div>

    <!-- Engagement History -->
    <section class="card" id="engagement-history" style="margin-bottom:32px;">
        <h2 class="section-title">Engagement History</h2>
        <div class="section-description">
            <strong>What this shows:</strong> View the history of all partner engagements with your campaigns. This shows when partners were invited, what campaigns they participated in, and engagement details. Use this to track collaboration over time.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Partner <span style="color:#dc2626;">*</span></label>
                <select id="history_pid" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a partner --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a partner from the dropdown to view their engagement history.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button type="button" class="btn btn-primary" onclick="loadEngagementHistory()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-history" style="margin-right:8px;"></i>View Engagement History
                </button>
            </div>
        </div>
        <div class="empty-state" id="engagementHistoryEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-clipboard-list"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No engagement history loaded yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">No data loaded yet. Enter a partner number above and click <strong>"View Engagement History"</strong> to see all past collaborations and engagements.</p>
        </div>
        <div style="overflow-x:auto; margin-top:20px;">
            <table class="data-table" id="engagementHistoryTable" style="display:none;">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Engagement Type</th>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody id="engagementHistoryTableBody">
                </tbody>
            </table>
        </div>
        <div class="status" id="engagementHistoryStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Engage Partner -->
    <section class="card" id="partner-portal" style="margin-bottom:32px;">
        <h2 class="section-title">Engage Partner with Campaign</h2>
        <div class="section-description">
            <strong>What this does:</strong> Invite a partner organization to collaborate on a specific campaign. This records the partnership and can be used to coordinate activities, share resources, or track participation. Partners can be engaged for events, training sessions, or ongoing campaign support.
        </div>
        <form id="engageForm" class="form-grid">
            <div class="form-field">
                <label>Select Partner <span style="color:#dc2626;">*</span></label>
                <select id="e_pid" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a partner --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a partner from the dropdown to engage with a campaign.</div>
            </div>
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <select id="e_cid" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a campaign --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a campaign from the dropdown to link with the partner.</div>
            </div>
            <div class="form-field">
                <label>Engagement Type</label>
                <select id="e_type" style="font-size:15px; padding:12px 16px;">
                    <option value="collaboration">Collaboration</option>
                    <option value="co-host">Co-Host</option>
                    <option value="resource_sharing">Resource Sharing</option>
                    <option value="training_provider">Training Provider</option>
                    <option value="volunteer_coordination">Volunteer Coordination</option>
                    <option value="sponsor">Sponsor</option>
                </select>
                <div class="helper-text">💡 Select how this partner will be involved in the campaign</div>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Engagement Notes</label>
                <textarea id="e_notes" rows="4" placeholder="Example: Partner will provide training materials and facilitators for the fire safety seminar. Contact person: Maria Santos, available Monday-Friday 9AM-5PM." style="font-size:15px; padding:12px 16px;"></textarea>
                <div class="helper-text">💡 Add any important details about this partnership, such as what the partner will provide, contact information, or special arrangements (optional)</div>
            </div>
        </form>
        <div class="form-field" style="margin-top:20px;">
            <button type="submit" form="engageForm" class="btn btn-primary" onclick="engage(event)" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-handshake" style="margin-right:8px;"></i>Record Partner Engagement
            </button>
        </div>
        <div class="status" id="engageStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Partner Assignments -->
    <section class="card" id="assignments" style="margin-bottom:32px;">
        <h2 class="section-title">Partner Assignments</h2>
        <div class="section-description">
            <strong>What this shows:</strong> View all campaign and event assignments for a specific partner. This shows which campaigns the partner is involved in, associated events, and their status. Useful for tracking what each partner is working on.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Partner <span style="color:#dc2626;">*</span></label>
                <select id="a_pid" required style="font-size:15px; padding:12px 16px;">
                    <option value="">-- Select a partner --</option>
                </select>
                <div class="helper-text">💡 <strong>Tip:</strong> Select a partner from the dropdown to view their assignments.</div>
            </div>
            <div class="form-field" style="margin-top:8px;">
                <button type="button" class="btn btn-primary" onclick="loadAssignments()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                    <i class="fas fa-tasks" style="margin-right:8px;"></i>View Partner Assignments
                </button>
            </div>
        </div>
        <div class="empty-state" id="assignmentsEmptyState" style="display:none;">
            <div class="empty-state-icon"><i class="fas fa-clipboard-check"></i></div>
            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0; color:#475569;">No assignments loaded yet</p>
            <p style="margin:0; font-size:14px; line-height:1.6;">No data loaded yet. Enter a partner number above and click <strong>"View Partner Assignments"</strong> to see all campaigns and events this partner is assigned to.</p>
        </div>
        <div style="overflow-x:auto; margin-top:20px;">
            <table class="data-table" id="assignTableContainer" style="display:none;">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Event</th>
                        <th>Event Date</th>
                    </tr>
                </thead>
                <tbody id="assignTable">
                </tbody>
            </table>
        </div>
        <div class="status" id="assignmentsStatus" style="margin-top:12px;"></div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

// Load All Partners
async function loadAllPartners() {
    const statusEl = document.getElementById('partnersListStatus');
    const emptyState = document.getElementById('partnersListEmptyState');
    const table = document.getElementById('partnersTable');
    const tbody = document.getElementById('partnersTableBody');
    
    statusEl.textContent = 'Loading partners...';
    statusEl.style.color = '#64748b';
    emptyState.style.display = 'none';
    table.style.display = 'none';
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data && data.data.length > 0) {
            tbody.innerHTML = '';
            data.data.forEach(partner => {
                const tr = document.createElement('tr');
                const date = partner.created_at ? new Date(partner.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
                tr.innerHTML = `
                    <td><strong style="color:#0f172a;">${partner.name || '-'}</strong></td>
                    <td>${partner.contact_person || '-'}</td>
                    <td>${partner.contact_email || '-'}</td>
                    <td>${partner.contact_phone || '-'}</td>
                    <td>${date}</td>
                    <td>
                        <button class="btn btn-secondary" onclick="viewPartner(${partner.id})" style="padding:4px 8px; font-size:12px; margin: 2px;"><i class="fas fa-eye"></i> View</button>
                        <button class="btn btn-secondary" onclick="editPartner(${partner.id})" style="padding:4px 8px; font-size:12px; margin: 2px;"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-warning" onclick="archivePartner(${partner.id})" style="padding:4px 8px; font-size:12px; background: #f59e0b; color: white; margin: 2px;"><i class="fas fa-archive"></i> Archive</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            table.style.display = 'table';
            emptyState.style.display = 'none';
            statusEl.textContent = '✓ Loaded ' + data.data.length + ' partner' + (data.data.length !== 1 ? 's' : '') + ' successfully';
            statusEl.style.color = '#166534';
        } else {
            table.style.display = 'none';
            emptyState.style.display = 'block';
            statusEl.textContent = 'ℹ️ No partners registered yet. Use "Add Partner" section below to register your first partner organization.';
            statusEl.style.color = '#64748b';
        }
    } catch (err) {
        table.style.display = 'none';
        emptyState.style.display = 'block';
        statusEl.textContent = '✗ Unable to load partners. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

async function addPartner(e) {
    e.preventDefault();
    const form = document.getElementById('partnerForm');
    const partnerId = form ? form.dataset.partnerId : null;
    const statusEl = document.getElementById('partnerStatus');
    
    const payload = {
        name: document.getElementById('p_name').value.trim(),
        organization_type: document.getElementById('p_type').value,
        contact_person: document.getElementById('p_person').value.trim() || null,
        contact_email: document.getElementById('p_email').value.trim() || null,
        contact_phone: document.getElementById('p_phone').value.trim() || null
    };
    
    if (!payload.name) {
        statusEl.textContent = '⚠️ Error: Organization Name is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    // Check if this is an update
    if (partnerId) {
        statusEl.textContent = 'Updating...';
        statusEl.style.color = '#64748b';
        
        try {
            const res = await fetch(apiBase + '/api/v1/partners/' + partnerId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (res.ok) {
                statusEl.textContent = '✓ Partner updated successfully!';
                statusEl.style.color = '#166534';
                document.getElementById('partnerForm').reset();
                if (form) delete form.dataset.partnerId;
                const submitBtn = document.querySelector('#add-partner button.btn-primary');
                if (submitBtn) submitBtn.textContent = 'Save Partner Organization';
                // Refresh partners list if it was loaded
                if (document.getElementById('partnersTable').style.display === 'table') {
                    setTimeout(() => loadAllPartners(), 500);
                }
            } else {
                statusEl.textContent = '⚠️ ' + (data.error || 'Failed to update partner');
                statusEl.style.color = '#dc2626';
            }
        } catch (err) {
            statusEl.textContent = '✗ Network error: ' + err.message;
            statusEl.style.color = '#dc2626';
        }
        return;
    }
    
    // Create new partner
    statusEl.textContent = 'Saving...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Partner organization added successfully! Partner Number: ' + (data.id || 'N/A') + '. You can now engage this partner with campaigns using the "Engage Partner with Campaign" section.';
            statusEl.style.color = '#166534';
            document.getElementById('partnerForm').reset();
            // Refresh partners list if it was loaded
            if (document.getElementById('partnersTable').style.display === 'table') {
                setTimeout(() => loadAllPartners(), 500);
            }
        } else {
            statusEl.textContent = '⚠️ ' + (data.error || 'Unable to save partner. Please check all required fields and try again.');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function engage(e) {
    e.preventDefault();
    const statusEl = document.getElementById('engageStatus');
    statusEl.textContent = 'Processing...';
    statusEl.style.color = '#64748b';
    
    const pid = document.getElementById('e_pid').value;
    const payload = {
        campaign_id: parseInt(document.getElementById('e_cid').value, 10),
        engagement_type: document.getElementById('e_type').value.trim() || 'collaboration',
        notes: document.getElementById('e_notes').value.trim() || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + pid + '/engage', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Partner engagement recorded successfully! The partner has been linked to the campaign. You can view this engagement in the "Engagement History" section.';
            statusEl.style.color = '#166534';
            document.getElementById('engageForm').reset();
        } else {
            statusEl.textContent = '⚠️ ' + (data.error || 'Unable to record engagement. Please make sure the partner number and campaign number are correct and try again.');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// Load Engagement History
async function loadEngagementHistory() {
    const pid = document.getElementById('history_pid').value;
    if (!pid || pid <= 0) {
        document.getElementById('engagementHistoryStatus').textContent = '⚠️ Please enter a valid partner number';
        document.getElementById('engagementHistoryStatus').style.color = '#dc2626';
        return;
    }
    
    const statusEl = document.getElementById('engagementHistoryStatus');
    const emptyState = document.getElementById('engagementHistoryEmptyState');
    const table = document.getElementById('engagementHistoryTable');
    const tbody = document.getElementById('engagementHistoryTableBody');
    
    statusEl.textContent = 'Loading engagement history...';
    statusEl.style.color = '#64748b';
    emptyState.style.display = 'none';
    table.style.display = 'none';
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        // Note: Using assignments endpoint as it returns engagement data
        const res = await fetch(apiBase + '/api/v1/partners/' + pid + '/assignments', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data && data.data.length > 0) {
            tbody.innerHTML = '';
            data.data.forEach(engagement => {
                const tr = document.createElement('tr');
                // Use engaged_at (when engagement was recorded) or starts_at (event date) or fallback
                const dateStr = engagement.engaged_at || engagement.starts_at;
                const date = dateStr ? new Date(dateStr).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                
                // Format engagement type
                const engagementType = engagement.engagement_type || 'collaboration';
                const typeColors = {
                    'collaboration': { bg: '#e0e7ff', color: '#3730a3' },
                    'sponsorship': { bg: '#dcfce7', color: '#166534' },
                    'training': { bg: '#fef3c7', color: '#92400e' },
                    'resource_sharing': { bg: '#dbeafe', color: '#1e40af' }
                };
                const typeStyle = typeColors[engagementType] || typeColors['collaboration'];
                const typeLabel = engagementType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                
                const statusBadge = engagement.status === 'completed' ? 
                    '<span style="background:#dcfce7; color:#166534; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Completed</span>' :
                    engagement.status === 'ongoing' ?
                    '<span style="background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Ongoing</span>' :
                    '<span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">' + (engagement.status || 'Active') + '</span>';
                tr.innerHTML = `
                    <td><strong style="color:#0f172a;">${engagement.campaign_title || '-'}</strong></td>
                    <td><span style="background:${typeStyle.bg}; color:${typeStyle.color}; padding:4px 10px; border-radius:4px; font-size:12px;">${typeLabel}</span></td>
                    <td>${engagement.event_name || 'No specific event'}</td>
                    <td>${date}</td>
                    <td style="color:#64748b; font-size:13px;">${engagement.notes || '-'}</td>
                `;
                tbody.appendChild(tr);
            });
            table.style.display = 'table';
            emptyState.style.display = 'none';
            statusEl.textContent = '✓ Loaded ' + data.data.length + ' engagement' + (data.data.length !== 1 ? 's' : '') + ' successfully';
            statusEl.style.color = '#166534';
        } else {
            table.style.display = 'none';
            emptyState.style.display = 'block';
            statusEl.textContent = 'ℹ️ No engagement history found for this partner. Use "Engage Partner with Campaign" section to record new engagements.';
            statusEl.style.color = '#64748b';
        }
    } catch (err) {
        table.style.display = 'none';
        emptyState.style.display = 'block';
        statusEl.textContent = '✗ Unable to load engagement history. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

// Add Partner Modal functions
function openAddPartnerModal() {
    document.getElementById('addPartnerModal').style.display = 'flex';
    document.getElementById('addPartnerModalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add Partner';
    document.getElementById('partnerForm').reset();
    const form = document.getElementById('partnerForm');
    if (form) delete form.dataset.partnerId;
    document.getElementById('partnerStatus').textContent = '';
}

function closeAddPartnerModal() {
    document.getElementById('addPartnerModal').style.display = 'none';
}

function closeViewPartnerModal() {
    document.getElementById('viewPartnerModal').style.display = 'none';
}

function closeArchivedPartnersModal() {
    document.getElementById('archivedPartnersModal').style.display = 'none';
}

// View Partner Details - Modern Modal
async function viewPartner(partnerId) {
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const partner = data.data;
            const date = partner.created_at ? new Date(partner.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
            const typeLabels = {
                'school': 'School',
                'ngo': 'NGO (Non-Government Organization)',
                'government': 'Government Agency',
                'private': 'Private Organization',
                'other': 'Other'
            };
            const typeLabel = typeLabels[partner.organization_type] || partner.organization_type || '-';
            
            const content = `
                <div style="display: grid; gap: 20px;">
                    <div style="text-align: center; padding-bottom: 20px; border-bottom: 1px solid #e2e8f0;">
                        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                            <i class="fas fa-building" style="font-size: 32px; color: white;"></i>
                        </div>
                        <h4 style="margin: 0 0 8px 0; font-size: 22px; color: #0f172a;">${partner.name || 'Unknown Partner'}</h4>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600;">${typeLabel}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-user" style="margin-right: 6px;"></i>Contact Person
                            </div>
                            <div style="color: #0f172a; font-size: 15px; font-weight: 500;">${partner.contact_person || 'Not specified'}</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-envelope" style="margin-right: 6px;"></i>Email
                            </div>
                            <div style="color: #0f172a; font-size: 15px; font-weight: 500;">${partner.contact_email || 'Not specified'}</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-phone" style="margin-right: 6px;"></i>Phone
                            </div>
                            <div style="color: #0f172a; font-size: 15px; font-weight: 500;">${partner.contact_phone || 'Not specified'}</div>
                        </div>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 8px;">
                            <div style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 6px;">
                                <i class="fas fa-calendar" style="margin-right: 6px;"></i>Date Added
                            </div>
                            <div style="color: #0f172a; font-size: 15px; font-weight: 500;">${date}</div>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; padding-top: 16px; border-top: 1px solid #e2e8f0;">
                        <button onclick="closeViewPartnerModal(); editPartner(${partner.id});" class="btn btn-secondary" style="padding: 10px 20px;">
                            <i class="fas fa-edit" style="margin-right: 6px;"></i>Edit Partner
                        </button>
                        <button onclick="closeViewPartnerModal();" class="btn btn-primary" style="padding: 10px 20px; background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; border: none;">
                            <i class="fas fa-check" style="margin-right: 6px;"></i>Close
                        </button>
                    </div>
                </div>
            `;
            
            document.getElementById('viewPartnerContent').innerHTML = content;
            document.getElementById('viewPartnerModal').style.display = 'flex';
        } else {
            await customAlert('Unable to load partner details', 'Error');
        }
    } catch (err) {
        await customAlert('Error: ' + err.message, 'Error');
    }
}

// Archive Partner
async function archivePartner(partnerId) {
    const confirmed = await customConfirm('Are you sure you want to archive this partner? You can restore it later from View Archived.', 'Archive Partner');
    if (!confirmed) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId + '/archive', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok) {
            await customAlert('Partner archived successfully!', 'Success');
            loadAllPartners();
        } else {
            await customAlert('Error: ' + (data.error || 'Unable to archive partner'), 'Error');
        }
    } catch (err) {
        await customAlert('Error: ' + err.message, 'Error');
    }
}

// Show Archived Partners
async function showArchivedPartners() {
    document.getElementById('archivedPartnersModal').style.display = 'flex';
    const container = document.getElementById('archivedPartnersList');
    container.innerHTML = '<p style="text-align: center; color: #64748b;">Loading archived partners...</p>';
    
    try {
        // Request archived partners specifically
        const res = await fetch(apiBase + '/api/v1/partners?status=archived', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (!res.ok) {
            container.innerHTML = '<p style="text-align: center; color: #ef4444;">Failed to load archived partners</p>';
            return;
        }
        
        const archivedPartners = data.data || [];
        
        if (archivedPartners.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 24px;">No archived partners.</p>';
            return;
        }
        
        let html = '';
        archivedPartners.forEach(partner => {
            html += `
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: #0f172a;">${partner.name || 'Unknown'}</strong>
                        <span style="color: #64748b; font-size: 12px; margin-left: 8px;">${partner.organization_type || ''}</span>
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="viewPartner(${partner.id})" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px; background: #6366f1; color: white; border: none;">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <button onclick="restorePartner(${partner.id})" class="btn btn-success" style="padding: 6px 12px; font-size: 12px; background: #10b981; color: white; border: none;">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                        <button onclick="deletePartnerPermanently(${partner.id})" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px; background: #ef4444; color: white; border: none;">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #ef4444;">Error loading archived partners</p>';
    }
}

// Restore Partner
async function restorePartner(partnerId) {
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId + '/restore', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok) {
            closeArchivedPartnersModal();
            await customAlert('Partner restored successfully!', 'Success');
            loadAllPartners();
        } else {
            await customAlert('Error: ' + (data.error || 'Unable to restore partner'), 'Error');
        }
    } catch (err) {
        await customAlert('Error: ' + err.message, 'Error');
    }
}

// Delete Partner Permanently
async function deletePartnerPermanently(partnerId) {
    const confirmed = await customConfirm('Are you sure you want to permanently delete this partner? This action cannot be undone.', 'Delete Partner');
    if (!confirmed) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok) {
            await customAlert('Partner deleted permanently!', 'Success');
            showArchivedPartners();
        } else {
            await customAlert('Error: ' + (data.error || 'Unable to delete partner'), 'Error');
        }
    } catch (err) {
        await customAlert('Error: ' + err.message, 'Error');
    }
}

// Edit Partner
async function editPartner(partnerId) {
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const partner = data.data;
            // Open modal
            document.getElementById('addPartnerModal').style.display = 'flex';
            document.getElementById('addPartnerModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Partner';
            
            // Populate form fields
            document.getElementById('p_name').value = partner.name || '';
            document.getElementById('p_type').value = partner.organization_type || 'school';
            document.getElementById('p_person').value = partner.contact_person || '';
            document.getElementById('p_email').value = partner.contact_email || '';
            document.getElementById('p_phone').value = partner.contact_phone || '';
            
            // Set partner ID in form dataset for update
            const form = document.getElementById('partnerForm');
            if (form) form.dataset.partnerId = partnerId;
            
            // Show status
            const statusEl = document.getElementById('partnerStatus');
            statusEl.textContent = '✏️ Editing partner: ' + partner.name;
            statusEl.style.color = '#1e40af';
        } else {
            await customAlert('Unable to load partner details for editing', 'Error');
        }
    } catch (err) {
        await customAlert('Error: ' + err.message, 'Error');
    }
}

// Delete Partner
async function deletePartner(partnerId) {
    if (!confirm('Are you sure you want to delete this partner? This action cannot be undone.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + partnerId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok) {
            alert('✓ Partner deleted successfully');
            // Refresh partners list
            loadAllPartners();
        } else {
            alert('Error: ' + (data.error || 'Unable to delete partner'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function loadAssignments() {
    const pid = document.getElementById('a_pid').value;
    if (!pid || pid <= 0) {
        document.getElementById('assignmentsStatus').textContent = '⚠️ Please enter a valid partner number';
        document.getElementById('assignmentsStatus').style.color = '#dc2626';
        return;
    }
    
    const statusEl = document.getElementById('assignmentsStatus');
    const emptyState = document.getElementById('assignmentsEmptyState');
    const table = document.getElementById('assignTableContainer');
    const tbody = document.getElementById('assignTable');
    
    statusEl.textContent = 'Loading assignments...';
    statusEl.style.color = '#64748b';
    emptyState.style.display = 'none';
    table.style.display = 'none';
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + pid + '/assignments', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data && data.data.length > 0) {
            tbody.innerHTML = '';
            data.data.forEach(r => {
                const tr = document.createElement('tr');
                const date = r.starts_at ? new Date(r.starts_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                const statusBadge = r.status === 'completed' ? 
                    '<span style="background:#dcfce7; color:#166534; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Completed</span>' :
                    r.status === 'ongoing' ?
                    '<span style="background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Ongoing</span>' :
                    '<span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">' + (r.status || 'Active') + '</span>';
                tr.innerHTML = `
                    <td><strong style="color:#0f172a;">${r.campaign_title || '-'}</strong></td>
                    <td>${statusBadge}</td>
                    <td>${r.event_name || 'No specific event'}</td>
                    <td>${date}</td>
                `;
                tbody.appendChild(tr);
            });
            table.style.display = 'table';
            emptyState.style.display = 'none';
            statusEl.textContent = '✓ Loaded ' + data.data.length + ' assignment' + (data.data.length !== 1 ? 's' : '') + ' successfully';
            statusEl.style.color = '#166534';
        } else {
            table.style.display = 'none';
            emptyState.style.display = 'block';
            statusEl.textContent = 'ℹ️ No assignments found for this partner. This partner has not been assigned to any campaigns or events yet.';
            statusEl.style.color = '#64748b';
        }
    } catch (err) {
        table.style.display = 'none';
        emptyState.style.display = 'block';
        statusEl.textContent = '✗ Unable to load assignments. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

// Populate partner dropdowns with data from All Partners
function populatePartnerDropdowns(partners) {
    const dropdownIds = ['history_pid', 'e_pid', 'a_pid'];
    
    dropdownIds.forEach(dropdownId => {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;
        
        // Clear existing options except the first one
        dropdown.innerHTML = '<option value="">-- Select a partner --</option>';
        
        if (partners && partners.length > 0) {
            partners.forEach(partner => {
                const option = document.createElement('option');
                option.value = partner.id;
                option.textContent = partner.name || `Partner #${partner.id}`;
                dropdown.appendChild(option);
            });
        }
    });
}

// Load all campaigns for the engage partner dropdown
async function loadCampaignsForDropdown() {
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        const dropdown = document.getElementById('e_cid');
        if (!dropdown) return;
        
        dropdown.innerHTML = '<option value="">-- Select a campaign --</option>';
        
        if (res.ok && data.data && data.data.length > 0) {
            data.data.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `ID ${campaign.id} - ${campaign.title || 'Untitled Campaign'}`;
                dropdown.appendChild(option);
            });
        }
    } catch (err) {
        console.error('Error loading campaigns for dropdown:', err);
    }
}

// Store partners globally for dropdown population
let allPartnersData = [];

// Modified loadAllPartners to also populate dropdowns
const originalLoadAllPartners = loadAllPartners;
loadAllPartners = async function() {
    const statusEl = document.getElementById('partnersListStatus');
    const emptyState = document.getElementById('partnersListEmptyState');
    const table = document.getElementById('partnersTable');
    const tbody = document.getElementById('partnersTableBody');
    
    statusEl.textContent = 'Loading partners...';
    statusEl.style.color = '#64748b';
    emptyState.style.display = 'none';
    table.style.display = 'none';
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (res.ok && data.data && data.data.length > 0) {
            allPartnersData = data.data;
            populatePartnerDropdowns(data.data);
            
            tbody.innerHTML = '';
            data.data.forEach(partner => {
                const tr = document.createElement('tr');
                const date = partner.created_at ? new Date(partner.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : '-';
                tr.innerHTML = `
                    <td><strong style="color:#0f172a;">${partner.name || '-'}</strong></td>
                    <td>${partner.contact_person || '-'}</td>
                    <td>${partner.contact_email || '-'}</td>
                    <td>${partner.contact_phone || '-'}</td>
                    <td>${date}</td>
                    <td>
                        <button class="btn btn-secondary" onclick="viewPartner(${partner.id})" style="padding:4px 8px; font-size:12px; margin: 2px;"><i class="fas fa-eye"></i> View</button>
                        <button class="btn btn-secondary" onclick="editPartner(${partner.id})" style="padding:4px 8px; font-size:12px; margin: 2px;"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-warning" onclick="archivePartner(${partner.id})" style="padding:4px 8px; font-size:12px; background: #f59e0b; color: white; margin: 2px;"><i class="fas fa-archive"></i> Archive</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            table.style.display = 'table';
            emptyState.style.display = 'none';
            statusEl.textContent = '✓ Loaded ' + data.data.length + ' partner' + (data.data.length !== 1 ? 's' : '') + ' successfully';
            statusEl.style.color = '#166534';
        } else {
            allPartnersData = [];
            populatePartnerDropdowns([]);
            table.style.display = 'none';
            emptyState.style.display = 'block';
            statusEl.textContent = 'ℹ️ No partners registered yet. Use "Add Partner" section below to register your first partner organization.';
            statusEl.style.color = '#64748b';
        }
    } catch (err) {
        table.style.display = 'none';
        emptyState.style.display = 'block';
        statusEl.textContent = '✗ Unable to load partners. Please check your internet connection and try again.';
        statusEl.style.color = '#dc2626';
    }
}

// Auto-load partners and campaigns on page load
document.addEventListener('DOMContentLoaded', function() {
    loadAllPartners();
    loadCampaignsForDropdown();
});
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
