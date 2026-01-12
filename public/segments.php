<?php
$pageTitle = 'Audience Segments';
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
<body class="module-segments" data-module="segments">
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
        <p>Create and manage target audience segments for campaigns</p>
    </div>

    <!-- All Segments List -->
    <section id="segments-list" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">All Segments</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">View and manage all audience segments</p>
            </div>
            <button class="btn btn-secondary" onclick="loadSegments()" style="padding: 8px 16px;">🔄 Refresh</button>
        </div>
        <div id="segmentsListContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Loading segments...</p>
        </div>
    </section>

    <!-- Create Segment -->
    <section id="create-segment" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Create Segment</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Define target audience segments for your campaigns</p>
            </div>
            <button type="button" onclick="showSegmentHelp()" class="btn btn-secondary" style="padding: 8px 16px; display: flex; align-items: center; gap: 6px;">
                <span>💡</span>
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
                    <input id="segment_name" type="text" placeholder="e.g., High-Risk Households in Payatas" required 
                           oninput="updateSegmentNameSuggestions()">
                    <div id="segmentNameSuggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 2px solid #e2e8f0; border-radius: 8px; margin-top: 4px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                        <div style="padding: 8px 12px; font-size: 12px; color: #64748b; border-bottom: 1px solid #e2e8f0; font-weight: 600;">Suggestions:</div>
                        <div id="suggestionsList"></div>
                    </div>
                </div>
                <p style="color: #64748b; font-size: 12px; margin: 4px 0 0 0;">💡 Tip: Select fields below and click "Auto-Generate" for smart suggestions</p>
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
    <section id="audience-members" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Segment Members</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">View members for a specific segment</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <input type="number" id="viewMembersSegmentId" placeholder="Segment ID" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; width: 120px;">
                <button class="btn btn-secondary" onclick="viewSegmentMembers()" style="padding: 8px 16px;">View Members</button>
            </div>
        </div>
        <div id="audienceMembersContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Enter a Segment ID and click "View Members" to see segment members</p>
        </div>
    </section>

    <!-- Participation History -->
    <section id="segment-analytics" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Participation History</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">View historical participation data (read-only)</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <input type="number" id="viewHistorySegmentId" placeholder="Segment ID" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; width: 120px;">
                <button class="btn btn-secondary" onclick="viewParticipationHistory()" style="padding: 8px 16px;">View History</button>
            </div>
        </div>
        <div id="segmentAnalyticsContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Enter a Segment ID and click "View History" to see participation history</p>
        </div>
    </section>

    <!-- Import Members CSV -->
    <section id="import-export" class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Import Members (CSV)</h2>
        <p style="color: #64748b; margin: 0 0 16px 0; font-size: 14px;">Bulk import segment members from CSV file</p>
        <form id="importForm" class="form-grid">
            <div class="form-field">
                <label>Segment ID *</label>
                <input name="segment_id" type="number" required>
            </div>
            <div class="form-field">
                <label>CSV File *</label>
                <input type="file" name="file" accept=".csv" required>
            </div>
        </form>
        <p style="color:#64748b; font-size:13px; margin:8px 0; padding: 12px; background: #f8fafc; border-radius: 6px;">
            <strong>CSV Format:</strong><br>
            Required column: <code>name</code> (or <code>full_name</code>)<br>
            Optional columns: <code>sector</code>, <code>barangay</code>, <code>zone</code>, <code>purok</code>, <code>contact</code>
        </p>
        <button type="submit" form="importForm" class="btn btn-primary" style="margin-top:8px;">Import CSV</button>
        <div class="status" id="importStatus" style="margin-top:12px;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

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

// Load segments list
async function loadSegments() {
    const container = document.getElementById('segmentsListContainer');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading segments...</p>';
    
    const currentToken = getToken();
    if (!currentToken) {
        // Token missing - show error but don't redirect immediately
        // Let the API call handle authentication errors
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Authentication required. Please log in.</p>';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments', {
            headers: { 'Authorization': 'Bearer ' + currentToken }
        });
        
        // Handle 401 Unauthorized (expired token)
        if (res.status === 401) {
            handleTokenExpiration();
            return;
        }
        
        const data = await res.json();
        
        if (!res.ok) {
            const errorMsg = data.error || 'Failed to load segments';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + errorMsg + '</p>';
            return;
        }
        
        const segments = data.data || [];
        
        if (segments.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No segments found. Create a segment to get started.</p>';
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
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Network error: ' + err.message + '</p>';
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
    
    // Show suggestions
    if (suggestions.length > 0) {
        const suggestionsDiv = document.getElementById('segmentNameSuggestions');
        const suggestionsList = document.getElementById('suggestionsList');
        
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
    document.getElementById('segment_name').value = suggestion;
    document.getElementById('segmentNameSuggestions').style.display = 'none';
    updateSegmentPreview();
}

// Update segment preview
function updateSegmentPreview() {
    const preview = document.getElementById('segmentPreview');
    const previewContent = document.getElementById('previewContent');
    
    const segmentName = document.getElementById('segment_name').value.trim();
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
    const segmentName = document.getElementById('segment_name').value.trim();
    const riskLevel = document.getElementById('risk_level').value;
    const geographicScope = document.getElementById('geographic_scope').value;
    const locationRef = document.getElementById('location_reference').value;
    
    // Validation
    if (!segmentName) {
        statusEl.textContent = '✗ Error: Segment Name is required';
        statusEl.style.color = '#dc2626';
        document.getElementById('segment_name').focus();
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
            statusEl.textContent = '✓ Segment created successfully! ID: ' + (data.id || 'N/A');
            statusEl.style.color = '#166534';
            resetForm();
            loadSegments();
            
            // Scroll to segments list
            setTimeout(() => {
                document.getElementById('segments-list').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 500);
        } else {
            const errorMsg = data.error || 'Failed';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            statusEl.textContent = '✗ Error: ' + errorMsg;
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
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

// View segment members
async function viewSegmentMembersById(segmentId) {
    document.getElementById('viewMembersSegmentId').value = segmentId;
    await viewSegmentMembers();
}

async function viewSegmentMembers() {
    const segmentId = document.getElementById('viewMembersSegmentId').value;
    if (!segmentId) {
        alert('Please enter a Segment ID');
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const container = document.getElementById('audienceMembersContainer');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading members...</p>';
    
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
            const errorMsg = data.error || 'Failed to load members';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + errorMsg + '</p>';
            return;
        }
        
        const members = data.data || [];
        
        if (members.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No members found for this segment.</p>';
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
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Network error: ' + err.message + '</p>';
    }
}

// View participation history
async function viewParticipationHistory() {
    const segmentId = document.getElementById('viewHistorySegmentId').value;
    if (!segmentId) {
        alert('Please enter a Segment ID');
        return;
    }
    
    const currentToken = getToken();
    if (!currentToken) return;
    
    const container = document.getElementById('segmentAnalyticsContainer');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading participation history...</p>';
    
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
            const errorMsg = data.error || 'Failed to load history';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + errorMsg + '</p>';
            return;
        }
        
        const history = data.data || [];
        
        if (history.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No participation history found for this segment.</p>';
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
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Network error: ' + err.message + '</p>';
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
            let message = '✓ Imported successfully! ' + (data.message || '');
            if (data.errors && data.errors.length > 0) {
                message += '\n\nErrors: ' + data.errors.slice(0, 5).join('; ');
                if (data.errors.length > 5) {
                    message += ' (and ' + (data.errors.length - 5) + ' more)';
                }
            }
            statusEl.textContent = message;
            statusEl.style.color = '#166534';
            statusEl.style.whiteSpace = 'pre-wrap';
            e.target.reset();
        } else {
            const errorMsg = data.error || 'Import failed';
            if (errorMsg.toLowerCase().includes('expired') || errorMsg.toLowerCase().includes('token')) {
                handleTokenExpiration();
                return;
            }
            statusEl.textContent = '✗ Error: ' + errorMsg;
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
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
});
</script>
    </main>
</body>
</html>
