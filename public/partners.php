<?php
$pageTitle = 'Partner Management';
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
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="module-partners" data-module="partners">
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
    .partners-page {
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
        <h2 class="section-title">All Partners</h2>
        <div class="section-description">
            <strong>What this shows:</strong> View all partner organizations registered in the system. This includes schools, NGOs, government agencies, and private organizations that collaborate with your barangay on public safety campaigns.
        </div>
        <div class="form-field" style="margin-bottom:16px;">
            <button type="button" class="btn btn-primary" onclick="loadAllPartners()" style="width:100%; padding:14px 20px; font-size:15px; font-weight:600;">
                <i class="fas fa-list" style="margin-right:8px;"></i>View All Partners
            </button>
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
                    </tr>
                </thead>
                <tbody id="partnersTableBody">
                </tbody>
            </table>
        </div>
        <div class="status" id="partnersListStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Add Partner -->
    <section class="card" id="add-partner" style="margin-bottom:32px;">
        <h2 class="section-title">Add Partner</h2>
        <div class="section-description">
            <strong>What this does:</strong> Register a new partner organization (school, NGO, government agency, or private organization) that will collaborate with your barangay on public safety campaigns. You'll need their organization name and contact information.
        </div>
        <form id="partnerForm" class="form-grid">
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
    </section>

    <!-- Engagement History -->
    <section class="card" id="engagement-history" style="margin-bottom:32px;">
        <h2 class="section-title">Engagement History</h2>
        <div class="section-description">
            <strong>What this shows:</strong> View the history of all partner engagements with your campaigns. This shows when partners were invited, what campaigns they participated in, and engagement details. Use this to track collaboration over time.
        </div>
        <div class="form-grid" style="grid-template-columns: 1fr; gap: 20px;">
            <div class="form-field">
                <label>Select Partner <span style="color:#dc2626;">*</span></label>
                <input id="history_pid" type="number" placeholder="Enter the partner number to view their engagement history" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Need help?</strong> Don't know the partner number? Go to "All Partners" section above to see all partners and their numbers.</div>
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
                <input id="e_pid" type="number" placeholder="Enter the partner number" required min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Need help?</strong> Don't know the partner number? Check the "All Partners" section above to see all partners and their numbers.</div>
            </div>
            <div class="form-field">
                <label>Select Campaign <span style="color:#dc2626;">*</span></label>
                <input id="e_cid" type="number" placeholder="Enter the campaign number" required min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Need help?</strong> Don't know the campaign number? Go to the "Campaigns" page in the sidebar to see all campaigns and their numbers.</div>
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
                <input id="a_pid" type="number" placeholder="Enter the partner number to view their assignments" min="1" style="font-size:15px; padding:12px 16px;">
                <div class="helper-text">💡 <strong>Need help?</strong> Don't know the partner number? Go to "All Partners" section above to see all partners and their numbers.</div>
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
    const statusEl = document.getElementById('partnerStatus');
    statusEl.textContent = 'Saving...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        name: document.getElementById('p_name').value.trim(),
        organization_type: document.getElementById('p_type').value,
        contact_person: document.getElementById('p_person').value.trim() || null,
        contact_email: document.getElementById('p_email').value.trim() || null,
        contact_phone: document.getElementById('p_phone').value.trim() || null
    };
    
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
                const date = engagement.starts_at ? new Date(engagement.starts_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';
                const statusBadge = engagement.status === 'completed' ? 
                    '<span style="background:#dcfce7; color:#166534; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Completed</span>' :
                    engagement.status === 'ongoing' ?
                    '<span style="background:#dbeafe; color:#1e40af; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">Ongoing</span>' :
                    '<span style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">' + (engagement.status || 'Active') + '</span>';
                tr.innerHTML = `
                    <td><strong style="color:#0f172a;">${engagement.campaign_title || '-'}</strong></td>
                    <td><span style="background:#e0e7ff; color:#3730a3; padding:4px 10px; border-radius:4px; font-size:12px;">Collaboration</span></td>
                    <td>${engagement.event_name || 'No specific event'}</td>
                    <td>${date}</td>
                    <td style="color:#64748b; font-size:13px;">-</td>
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
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
