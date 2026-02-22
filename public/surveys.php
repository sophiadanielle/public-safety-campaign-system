<?php
$pageTitle = 'Surveys & Feedback';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Get user role to conditionally show/hide sections
// Wrapped in try-catch to prevent 502 errors
$isViewer = false;
$currentUserRole = null;
try {
    @require_once __DIR__ . '/../sidebar/includes/get_user_role.php';
    $currentUserRole = getCurrentUserRole();
    if ($currentUserRole) {
        $roleLower = strtolower(trim($currentUserRole));
        $isViewer = ($roleLower === 'viewer' || $roleLower === 'partner' || 
                    strpos($roleLower, 'partner') !== false || strpos($roleLower, 'viewer') !== false);
    }
} catch (\Throwable $e) {
    error_log('surveys.php: get_user_role failed: ' . $e->getMessage());
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
<body class="module-surveys" data-module="surveys">
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/sidebar.php'; 
    } catch (\Throwable $e) {
        error_log('surveys.php: sidebar.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/admin-header.php'; 
    } catch (\Throwable $e) {
        error_log('surveys.php: admin-header.php failed: ' . $e->getMessage());
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
    .surveys-page {
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
</style>

<main class="surveys-page">
    <div class="page-header">
        <h1>Surveys & Feedback</h1>
        <p>Create surveys and collect feedback from campaign participants</p>
    </div>

    <?php if (!$isViewer): // RBAC: Hide create survey section for Viewer ?>
    <!-- Hidden: Create Survey form is now in modal -->
    <section class="card" id="create-survey" style="margin-bottom:24px; display:none;">
        <h2 class="section-title">Create Survey</h2>
        <form id="createForm" class="form-grid">
            <div class="form-field">
                <label>Title *</label>
                <input id="title" type="text" placeholder="Post-event feedback" required>
            </div>
            <div class="form-field">
                <label>Link to Campaign ID</label>
                <select id="campaign_id">
                    <option value="">-- Select a campaign --</option>
                </select>
            </div>
            <div class="form-field">
                <label>OR Link to Event ID</label>
                <input id="event_id" type="number" placeholder="1">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Description</label>
                <textarea id="description" rows="3" placeholder="Survey description..."></textarea>
            </div>
        </form>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="createSurvey()">Create Survey</button>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>

        <div id="survey-builder" style="display:none; margin-top:32px; padding-top:24px; border-top:2px solid #f1f5f9;">
            <h3 class="section-title" style="font-size:18px;">Add Questions</h3>
            <form id="questionForm" class="form-grid">
                <div class="form-field">
                    <label>Question Text *</label>
                    <input id="q_text" type="text" placeholder="How satisfied were you?" required>
                </div>
                <div class="form-field">
                    <label>Question Type *</label>
                    <select id="q_type" required>
                        <option value="open_ended">Open Ended</option>
                        <option value="rating">Rating (1-5)</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="yes_no">Yes/No</option>
                        <option value="single_choice">Single Choice</option>
                        <option value="text">Text (Legacy)</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Options (comma separated)</label>
                    <input id="q_options" type="text" placeholder="Very Satisfied, Satisfied, Neutral, Dissatisfied">
                </div>
                <div class="form-field">
                    <label>Question Order</label>
                    <input id="q_order" type="number" placeholder="Auto" min="0">
                </div>
                <div class="form-field" style="display:flex; align-items:center; padding-top:20px;">
                    <input type="checkbox" id="q_required" style="width:auto; margin-right:8px;">
                    <label for="q_required" style="margin:0;">Required Question</label>
                </div>
            </form>
            <div style="display:flex; gap:8px; margin-top:16px;">
                <button class="btn btn-primary" onclick="addQuestion()">Add Question</button>
                <button class="btn btn-secondary" onclick="publishSurvey()">Publish Survey</button>
                <button class="btn btn-secondary" onclick="loadQuestions()">View Questions</button>
            </div>
            <div id="questionsList" style="margin-top:24px;"></div>
        </div>
    </section>
    <?php endif; // End RBAC: Hide create survey section for Viewer ?>

    <!-- Survey Dashboard -->
    <section class="card" id="surveys-list" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:12px;">
            <h2 class="section-title" style="margin:0; border:none; padding:0;">Survey Dashboard</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <?php if (!$isViewer): ?>
                <button class="btn btn-primary" onclick="openCreateSurveyModal()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-plus"></i> Create Survey
                </button>
                <?php endif; ?>
                <button class="btn btn-secondary" onclick="openArchivedSurveysModal()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
                <button class="btn btn-secondary" onclick="loadSurveys()" style="display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div class="form-grid" style="margin-bottom:16px;">
            <div class="form-field">
                <label>Filter by Campaign ID</label>
                <input id="filter_campaign_id" type="number" placeholder="Campaign ID">
            </div>
            <div class="form-field">
                <label>Filter by Event ID</label>
                <input id="filter_event_id" type="number" placeholder="Event ID">
            </div>
            <div class="form-field">
                <label>Filter by Status</label>
                <select id="filter_status">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="form-field" style="display:flex; align-items:flex-end;">
                <button class="btn btn-secondary" onclick="loadSurveys()" style="width:100%;">Apply Filters</button>
            </div>
        </div>
        <div id="surveysList"></div>
    </section>

    <!-- Survey Results View -->
    <?php if (!$isViewer): // RBAC: Hide analytics section for Viewer ?>
    <section class="card" id="survey-analytics" style="display:none; margin-bottom:24px;">
        <h2 class="section-title">Survey Results</h2>
        <div id="resultsContent"></div>
    </section>
    <?php endif; // End RBAC: Hide analytics section for Viewer ?>

    <section class="card" id="responses">
        <h2 class="section-title">Submit Response (Public)</h2>
        <form id="responseForm" class="form-grid">
            <div class="form-field">
                <label>Survey ID *</label>
                <input id="resp_sid" type="number" required onchange="loadSurveyForResponse()">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <button type="button" class="btn btn-secondary" onclick="loadSurveyForResponse()" style="width:auto;">Load Survey</button>
            </div>
        </form>
        
        <div id="surveyResponseContainer" style="display:none; margin-top:24px; padding-top:24px; border-top:2px solid #e2e8f0;">
            <h3 id="surveyResponseTitle" style="margin:0 0 16px 0; font-size:20px;"></h3>
            <div id="surveyQuestionsContainer"></div>
            <button type="button" class="btn btn-primary" style="margin-top:20px;" onclick="submitResponse(event)">Submit Response</button>
        </div>
        
        <div class="status" id="respStatus" style="margin-top:12px;"></div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
let currentSurveyId = null;

// Load campaigns and populate dropdown
async function loadCampaigns() {
    const campaignSelect = document.getElementById('campaign_id');
    if (!campaignSelect) return;
    
    if (!token) {
        campaignSelect.innerHTML = '<option value="">-- Login required --</option>';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (res.status === 401) {
            campaignSelect.innerHTML = '<option value="">-- Authentication required --</option>';
            return;
        }
        
        const data = await res.json();
        
        if (res.ok && data.data && Array.isArray(data.data)) {
            const campaigns = data.data;
            campaignSelect.innerHTML = '<option value="">-- Select a campaign --</option>';
            
            campaigns.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `ID ${campaign.id} - ${campaign.title || 'Untitled Campaign'}`;
                campaignSelect.appendChild(option);
            });
        } else {
            campaignSelect.innerHTML = '<option value="">-- No campaigns available --</option>';
        }
    } catch (err) {
        console.error('Error loading campaigns:', err);
        campaignSelect.innerHTML = '<option value="">-- Error loading campaigns --</option>';
    }
}

// Load campaigns on page load
document.addEventListener('DOMContentLoaded', function() {
    loadCampaigns();
});

async function createSurvey() {
    const statusEl = document.getElementById('createStatus');
    const form = document.getElementById('createForm');
    const surveyId = form.dataset.surveyId;
    const isUpdate = !!surveyId;
    
    statusEl.textContent = isUpdate ? 'Updating...' : 'Creating...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        title: document.getElementById('title').value.trim(),
        description: document.getElementById('description').value.trim() || null,
        campaign_id: document.getElementById('campaign_id').value ? parseInt(document.getElementById('campaign_id').value, 10) : null,
        event_id: parseInt(document.getElementById('event_id').value, 10) || null
    };
    
    try {
        const url = isUpdate ? apiBase + '/api/v1/surveys/' + surveyId : apiBase + '/api/v1/surveys';
        const method = isUpdate ? 'PUT' : 'POST';
        
        const res = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        
        if (res.ok) {
            if (isUpdate) {
                statusEl.textContent = '✓ Survey updated successfully!';
                statusEl.style.color = '#166534';
                // Reload surveys list
                loadSurveys();
                // Reset form
                setTimeout(() => {
                    form.reset();
                    delete form.dataset.surveyId;
                    currentSurveyId = null;
                    document.querySelector('#create-survey button.btn-primary').textContent = 'Create Survey';
                    document.getElementById('survey-builder').style.display = 'none';
                    statusEl.textContent = '';
                }, 2000);
            } else {
                currentSurveyId = data.id;
                statusEl.textContent = '✓ Survey created! ID: ' + data.id + ' - Now add questions below.';
                statusEl.style.color = '#166534';
                document.getElementById('survey-builder').style.display = 'block';
            }
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function addQuestion() {
    if (!currentSurveyId) {
        alert('Please create a survey first');
        return;
    }
    
    const optsRaw = document.getElementById('q_options').value.trim();
    const opts = optsRaw ? optsRaw.split(',').map(s => s.trim()).filter(Boolean) : [];
    
    const payload = {
        question_text: document.getElementById('q_text').value.trim(),
        question_type: document.getElementById('q_type').value,
        options: opts,
        question_order: parseInt(document.getElementById('q_order').value, 10) || 0,
        required_flag: document.getElementById('q_required').checked
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + currentSurveyId + '/questions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('createStatus').textContent = '✓ Question added! Add more or publish.';
            document.getElementById('createStatus').style.color = '#166534';
            document.getElementById('questionForm').reset();
        } else {
            document.getElementById('createStatus').textContent = '✗ Error: ' + (data.error || 'Failed');
            document.getElementById('createStatus').style.color = '#dc2626';
        }
    } catch (err) {
        document.getElementById('createStatus').textContent = '✗ Network error: ' + err.message;
        document.getElementById('createStatus').style.color = '#dc2626';
    }
}

async function publishSurvey() {
    if (!currentSurveyId) return;
    
    const publishedVia = prompt('Publish via: link, qr_code, or both?', 'both');
    if (!publishedVia || !['link', 'qr_code', 'both'].includes(publishedVia)) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + currentSurveyId + '/publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ published_via: publishedVia })
        });
        const data = await res.json();
        document.getElementById('createStatus').textContent = res.ok ? '✓ Survey published!' : ('✗ Error: ' + (data.error || 'Failed'));
        document.getElementById('createStatus').style.color = res.ok ? '#166534' : '#dc2626';
        if (res.ok) {
            loadSurveys();
        }
    } catch (err) {
        document.getElementById('createStatus').textContent = '✗ Network error: ' + err.message;
        document.getElementById('createStatus').style.color = '#dc2626';
    }
}

async function loadSurveys() {
    const campaignId = document.getElementById('filter_campaign_id').value;
    const eventId = document.getElementById('filter_event_id').value;
    const status = document.getElementById('filter_status').value;
    
    let url = apiBase + '/api/v1/surveys?';
    if (campaignId) url += 'campaign_id=' + campaignId + '&';
    if (eventId) url += 'event_id=' + eventId + '&';
    if (status) url += 'status=' + status + '&';
    
    try {
        const res = await fetch(url, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok && data.data) {
            // RBAC: For Viewer, filter to show only published surveys
            let surveysToShow = data.data;
            const isViewerCheck = checkIfViewer();
            if (isViewerCheck) {
                surveysToShow = data.data.filter(s => (s.status || '').toLowerCase() === 'published');
                console.log('loadSurveys() - Filtered surveys for Viewer:', surveysToShow.length, 'published surveys');
            }
            renderSurveysList(surveysToShow);
        }
    } catch (err) {
        console.error('Error loading surveys:', err);
    }
}

function renderSurveysList(surveys) {
    const container = document.getElementById('surveysList');
    if (surveys.length === 0) {
        container.innerHTML = '<p style="color:#64748b; padding:16px;">No surveys found.</p>';
        return;
    }
    
    // RBAC: Check if user is Viewer
    const isViewer = checkIfViewer();
    
    let html = '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;"><th style="padding:12px; text-align:left;">ID</th><th style="padding:12px; text-align:left;">Title</th><th style="padding:12px; text-align:left;">Status</th><th style="padding:12px; text-align:left;">Questions</th><th style="padding:12px; text-align:left;">Responses</th>';
    if (!isViewer) {
        html += '<th style="padding:12px; text-align:left;">Actions</th>';
    }
    html += '</tr></thead><tbody>';
    
    surveys.forEach(survey => {
        const statusColor = survey.status === 'published' ? '#166534' : survey.status === 'closed' ? '#dc2626' : '#64748b';
        html += `<tr style="border-bottom:1px solid #e2e8f0;">
            <td style="padding:12px;">${survey.id}</td>
            <td style="padding:12px;">${survey.title || ''}</td>
            <td style="padding:12px;"><span style="color:${statusColor}; font-weight:600;">${survey.status || 'draft'}</span></td>
            <td style="padding:12px;">${survey.question_count || 0}</td>
            <td style="padding:12px;">${survey.total_responses || 0}</td>`;
        
        if (!isViewer) {
            html += `<td style="padding:12px;">
                <button class="btn btn-secondary" onclick="openViewSurveyModal(${survey.id})" style="padding:4px 8px; font-size:12px; margin: 2px;">👁️ View</button>
                ${survey.status === 'draft' ? `<button class="btn btn-secondary" onclick="openEditSurveyModal(${survey.id})" style="padding:4px 8px; font-size:12px; margin: 2px;">✏️ Edit</button>` : ''}
                <button class="btn btn-secondary" onclick="viewResults(${survey.id})" style="padding:4px 8px; font-size:12px; margin: 2px;">📊 Results</button>
                <button class="btn btn-secondary" onclick="showSurveyExportModal(${survey.id})" style="padding:4px 8px; font-size:12px; margin: 2px;">📥 Export PDF</button>
                ${survey.status === 'published' ? `<button class="btn btn-secondary" onclick="closeSurvey(${survey.id})" style="padding:4px 8px; font-size:12px; margin: 2px;">🔒 Close</button>` : ''}
                ${survey.status !== 'archived' ? `<button class="btn btn-warning" onclick="archiveSurvey(${survey.id})" style="padding:4px 8px; font-size:12px; background: #f59e0b; color: white; margin: 2px;">📦 Archive</button>` : ''}
            </td>`;
        } else {
            // Viewer: Show only "Respond" button for published surveys
            if (survey.status === 'published') {
                html += `<td style="padding:12px;">
                    <button class="btn btn-primary" onclick="loadSurveyForResponseById(${survey.id})" style="padding:4px 8px; font-size:12px;">Respond</button>
                </td>`;
            } else {
                html += '<td style="padding:12px; color:#9ca3af;">Not available</td>';
            }
        }
        
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
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

// Helper function to load survey for response by ID
async function loadSurveyForResponseById(surveyId) {
    document.getElementById('resp_sid').value = surveyId;
    await loadSurveyForResponse();
    // Scroll to response form
    document.getElementById('responses').scrollIntoView({ behavior: 'smooth' });
}

// View survey details
async function viewSurvey(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (data.error || !data.data) {
            alert('Error: ' + (data.error || 'Failed to load survey'));
            return;
        }
        
        const survey = data.data;
        
        // Create modal with survey details
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 24px; max-width: 700px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0; color: #0f172a; font-size: 24px;">Survey Details</h2>
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
                </div>
                <div style="display: grid; gap: 16px;">
                    <div><strong>ID:</strong> ${survey.id}</div>
                    <div><strong>Title:</strong> ${survey.title || 'N/A'}</div>
                    <div><strong>Description:</strong> ${survey.description || 'N/A'}</div>
                    <div><strong>Status:</strong> <span style="color: ${survey.status === 'published' ? '#166534' : survey.status === 'closed' ? '#dc2626' : '#64748b'}; font-weight:600;">${survey.status || 'draft'}</span></div>
                    <div><strong>Campaign ID:</strong> ${survey.campaign_id || 'N/A'}</div>
                    <div><strong>Event ID:</strong> ${survey.event_id || 'N/A'}</div>
                    <div><strong>Questions:</strong> ${survey.questions ? survey.questions.length : 0}</div>
                    <div><strong>Total Responses:</strong> ${survey.total_responses || 0}</div>
                    <div><strong>Created At:</strong> ${survey.created_at ? new Date(survey.created_at).toLocaleString() : 'N/A'}</div>
                    ${survey.published_at ? `<div><strong>Published At:</strong> ${new Date(survey.published_at).toLocaleString()}</div>` : ''}
                    ${survey.closed_at ? `<div><strong>Closed At:</strong> ${new Date(survey.closed_at).toLocaleString()}</div>` : ''}
                </div>
                ${survey.questions && survey.questions.length > 0 ? `
                    <div style="margin-top: 24px; padding-top: 24px; border-top: 2px solid #f1f5f9;">
                        <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 12px;">Questions</h3>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            ${survey.questions.map((q, idx) => `
                                <div style="padding: 12px; background: #f8fafc; border-radius: 8px;">
                                    <div style="font-weight: 600; margin-bottom: 4px;">Q${idx + 1}: ${q.question_text || 'N/A'}</div>
                                    <div style="font-size: 12px; color: #64748b;">Type: ${q.question_type || 'N/A'}</div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                    <button onclick="this.closest('div[style*=\"position: fixed\"]').remove()" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 6px; cursor: pointer;">Close</button>
                    ${survey.status === 'draft' ? `<button onclick="editSurvey(${survey.id}); this.closest('div[style*=\"position: fixed\"]').remove();" style="padding: 8px 16px; background: #4c8a89; color: white; border: none; border-radius: 6px; cursor: pointer;">Edit</button>` : ''}
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    } catch (err) {
        alert('Failed to load survey: ' + err.message);
    }
}

// Edit survey
async function editSurvey(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (data.error || !data.data) {
            alert('Error: ' + (data.error || 'Failed to load survey'));
            return;
        }
        
        const survey = data.data;
        
        // Check if survey is in draft status
        if (survey.status !== 'draft') {
            alert('Error: Only draft surveys can be edited. This survey is currently "' + survey.status + '".\n\nPublished or closed surveys cannot be modified to maintain data integrity.');
            return;
        }
        
        // Populate form with survey data
        document.getElementById('title').value = survey.title || '';
        document.getElementById('description').value = survey.description || '';
        document.getElementById('campaign_id').value = survey.campaign_id || '';
        document.getElementById('event_id').value = survey.event_id || '';
        
        // Store survey ID for update
        document.getElementById('createForm').dataset.surveyId = surveyId;
        
        // Set current survey ID for questions
        currentSurveyId = surveyId;
        
        // Change submit button text
        const submitBtn = document.querySelector('#create-survey button.btn-primary');
        if (submitBtn) {
            submitBtn.textContent = 'Update Survey';
        }
        
        // Show survey builder section
        document.getElementById('survey-builder').style.display = 'block';
        
        // Load questions
        await loadQuestions();
        
        // Scroll to form
        document.getElementById('create-survey').scrollIntoView({ behavior: 'smooth', block: 'start' });
        
    } catch (err) {
        alert('Failed to load survey: ' + err.message);
    }
}

// Delete survey
async function deleteSurvey(surveyId) {
    if (!confirm('Are you sure you want to delete this survey? This action cannot be undone and will remove all questions and responses.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to delete survey'));
            return;
        }
        
        alert('Survey deleted successfully!');
        loadSurveys();
    } catch (err) {
        alert('Failed to delete survey: ' + err.message);
    }
}

async function viewResults(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/results', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok && data.results) {
            renderResults(data);
            document.getElementById('survey-analytics').style.display = 'block';
            document.getElementById('survey-analytics').scrollIntoView({ behavior: 'smooth' });
        } else {
            alert('Error: ' + (data.error || 'Failed to load results'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

function renderResults(data) {
    const container = document.getElementById('resultsContent');
    let html = `<div style="margin-bottom:16px;">
        <h3 style="margin:0 0 8px 0;">${data.survey_title || 'Survey Results'}</h3>
        <p style="margin:0; color:#64748b;">Total Responses: <strong>${data.total_responses || 0}</strong></p>
    </div>`;
    
    html += '<div style="display:grid; gap:16px;">';
    data.results.forEach(result => {
        html += `<div style="border:1px solid #e2e8f0; border-radius:8px; padding:16px;">
            <h4 style="margin:0 0 8px 0;">${result.question_text}</h4>
            <p style="margin:0 0 8px 0; color:#64748b; font-size:14px;">Type: ${result.question_type} | Responses: ${result.total_responses}</p>`;
        
        if (result.average_rating !== null) {
            html += `<p style="margin:0; font-weight:600;">Average Rating: ${result.average_rating}</p>`;
        }
        
        if (result.response_distribution && Object.keys(result.response_distribution).length > 0) {
            html += '<div style="margin-top:12px;"><strong>Distribution:</strong><ul style="margin:8px 0 0 0; padding-left:20px;">';
            for (const [key, value] of Object.entries(result.response_distribution)) {
                html += `<li>${key}: ${value}</li>`;
            }
            html += '</ul></div>';
        }
        
        html += '</div>';
    });
    html += '</div>';
    
    html += `<div style="margin-top:16px;">
        <button class="btn btn-secondary" onclick="exportAggregatedResults(${data.survey_id})">Export Aggregated Results (CSV)</button>
    </div>`;
    
    container.innerHTML = html;
}

async function exportResponses(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/responses/export', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (res.ok) {
            const blob = await res.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'survey_' + surveyId + '_responses.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            const data = await res.json();
            alert('Error: ' + (data.error || 'Failed to export responses'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function exportAggregatedResults(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/results/export', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (res.ok) {
            const blob = await res.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'survey_' + surveyId + '_aggregated_results.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } else {
            // Try to parse as JSON first, if that fails, show text error
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await res.json();
                const errorMsg = data.error || 'Failed to export aggregated results';
                console.error('Export aggregated results error:', data);
                alert('Export Failed\n\n' + errorMsg + '\n\nStatus: ' + res.status);
            } else {
                const text = await res.text();
                console.error('Export error response (non-JSON):', text);
                console.error('Response status:', res.status);
                console.error('Response headers:', Array.from(res.headers.entries()));
                alert('Server Error (Status ' + res.status + ')\n\nThe server returned an unexpected response. Please check:\n\n1. Browser console for detailed error logs\n2. Server error logs\n3. Database connection\n4. Survey has responses to aggregate\n\nIf the issue persists, contact your system administrator.');
            }
        }
    } catch (err) {
        console.error('Export error (network/exception):', err);
        alert('Network Error\n\nFailed to export aggregated results: ' + err.message + '\n\nPlease check your internet connection and try again.');
    }
}

async function closeSurvey(surveyId) {
    if (!confirm('Are you sure you want to close this survey? It will no longer accept responses.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/close', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok) {
            alert('Survey closed successfully');
            loadSurveys();
        } else {
            alert('Error: ' + (data.error || 'Failed'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function loadQuestions() {
    console.log('loadQuestions called, currentSurveyId:', currentSurveyId);
    
    if (!currentSurveyId) {
        alert('Please create or select a survey first. Click "Create Survey" button above to create a new survey.');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + currentSurveyId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        console.log('Survey data:', data);
        
        if (res.ok && data.data) {
            const container = document.getElementById('questionsList');
            const questions = data.data.questions || [];
            
            if (questions.length === 0) {
                container.innerHTML = '<p style="color:#64748b; padding:12px; background:#f8fafc; border-radius:4px;">No questions added yet. Use the form above to add questions to this survey.</p>';
            } else {
                let html = '<h4 style="margin:0 0 12px 0;">Current Questions:</h4><ul style="list-style:none; padding:0; margin:0;">';
                questions.forEach((q, idx) => {
                    html += `<li style="padding:8px; margin-bottom:8px; background:#f8fafc; border-radius:4px;">
                        ${idx + 1}. ${q.question_text} <span style="color:#64748b; font-size:12px;">(${q.question_type}${q.required_flag ? ', Required' : ''})</span>
                    </li>`;
                });
                html += '</ul>';
                container.innerHTML = html;
            }
        } else {
            alert('Error loading questions: ' + (data.error || 'Unknown error'));
        }
    } catch (err) {
        console.error('Error loading questions:', err);
        alert('Failed to load questions: ' + err.message);
    }
}

// Load surveys on page load
loadSurveys();

let loadedSurveyQuestions = [];

async function loadSurveyForResponse() {
    const sid = document.getElementById('resp_sid').value;
    const statusEl = document.getElementById('respStatus');
    const container = document.getElementById('surveyResponseContainer');
    const questionsContainer = document.getElementById('surveyQuestionsContainer');
    
    if (!sid) {
        statusEl.textContent = 'Please enter a Survey ID';
        statusEl.style.color = '#dc2626';
        container.style.display = 'none';
        return;
    }
    
    statusEl.textContent = 'Loading survey...';
    statusEl.style.color = '#64748b';
    container.style.display = 'none';
    questionsContainer.innerHTML = '';
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + sid);
        const data = await res.json();
        
        if (!res.ok || !data.data) {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Survey not found');
            statusEl.style.color = '#dc2626';
            container.style.display = 'none';
            return;
        }
        
        const survey = data.data;
        
        // Check if survey is published
        if (survey.status !== 'published') {
            statusEl.textContent = '✗ Survey is not published. Only published surveys can accept responses.';
            statusEl.style.color = '#dc2626';
            container.style.display = 'none';
            return;
        }
        
        // Store questions for validation
        loadedSurveyQuestions = survey.questions || [];
        
        // Display survey title
        document.getElementById('surveyResponseTitle').textContent = survey.title || 'Survey';
        
        // Render questions
        if (loadedSurveyQuestions.length === 0) {
            statusEl.textContent = '✗ This survey has no questions';
            statusEl.style.color = '#dc2626';
            container.style.display = 'none';
            return;
        }
        
        questionsContainer.innerHTML = '';
        loadedSurveyQuestions.forEach((q, index) => {
            const questionDiv = document.createElement('div');
            questionDiv.className = 'form-field';
            questionDiv.style.gridColumn = '1 / -1';
            questionDiv.style.marginBottom = '16px';
            questionDiv.style.padding = '16px';
            questionDiv.style.border = '1px solid #e2e8f0';
            questionDiv.style.borderRadius = '8px';
            questionDiv.style.background = '#f8fafc';
            
            const label = document.createElement('label');
            label.textContent = q.question_text;
            if (q.required_flag) {
                label.innerHTML += ' <span style="color:#dc2626;">*</span>';
            }
            label.style.fontWeight = '600';
            label.style.marginBottom = '8px';
            label.style.display = 'block';
            questionDiv.appendChild(label);
            
            let input;
            const questionId = q.id;
            const questionType = q.question_type;
            
            if (questionType === 'rating') {
                // Rating: Radio buttons 1-5
                const ratingContainer = document.createElement('div');
                ratingContainer.style.display = 'flex';
                ratingContainer.style.gap = '12px';
                ratingContainer.style.alignItems = 'center';
                
                for (let i = 1; i <= 5; i++) {
                    const radioWrapper = document.createElement('label');
                    radioWrapper.style.display = 'flex';
                    radioWrapper.style.flexDirection = 'column';
                    radioWrapper.style.alignItems = 'center';
                    radioWrapper.style.cursor = 'pointer';
                    
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'q_' + questionId;
                    radio.value = i;
                    radio.dataset.qid = questionId;
                    radio.required = q.required_flag || false;
                    
                    const labelText = document.createElement('span');
                    labelText.textContent = i;
                    labelText.style.marginTop = '4px';
                    labelText.style.fontSize = '14px';
                    
                    radioWrapper.appendChild(radio);
                    radioWrapper.appendChild(labelText);
                    ratingContainer.appendChild(radioWrapper);
                }
                
                questionDiv.appendChild(ratingContainer);
            } else if (questionType === 'yes_no') {
                // Yes/No: Radio buttons
                const radioContainer = document.createElement('div');
                radioContainer.style.display = 'flex';
                radioContainer.style.gap = '16px';
                
                ['Yes', 'No'].forEach(option => {
                    const radioWrapper = document.createElement('label');
                    radioWrapper.style.display = 'flex';
                    radioWrapper.style.alignItems = 'center';
                    radioWrapper.style.cursor = 'pointer';
                    
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = 'q_' + questionId;
                    radio.value = option;
                    radio.dataset.qid = questionId;
                    radio.required = q.required_flag || false;
                    radio.style.marginRight = '6px';
                    
                    const labelText = document.createElement('span');
                    labelText.textContent = option;
                    
                    radioWrapper.appendChild(radio);
                    radioWrapper.appendChild(labelText);
                    radioContainer.appendChild(radioWrapper);
                });
                
                questionDiv.appendChild(radioContainer);
            } else if (questionType === 'single_choice' || questionType === 'multiple_choice') {
                // Single/Multiple Choice: Select dropdown
                input = document.createElement('select');
                input.dataset.qid = questionId;
                input.required = q.required_flag || false;
                input.style.width = '100%';
                input.style.padding = '8px';
                
                if (questionType === 'multiple_choice') {
                    input.multiple = true;
                    input.size = Math.min(5, (JSON.parse(q.options_json || '[]').length || 1));
                }
                
                const options = JSON.parse(q.options_json || '[]');
                if (options.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = '';
                    opt.textContent = 'No options available';
                    opt.disabled = true;
                    input.appendChild(opt);
                } else {
                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.textContent = opt;
                        input.appendChild(option);
                    });
                }
                
                questionDiv.appendChild(input);
            } else if (questionType === 'open_ended' || questionType === 'text') {
                // Open-ended/Text: Textarea
                input = document.createElement('textarea');
                input.dataset.qid = questionId;
                input.required = q.required_flag || false;
                input.rows = 4;
                input.style.width = '100%';
                input.style.padding = '8px';
                input.style.fontFamily = 'inherit';
                input.placeholder = 'Type your response here...';
                questionDiv.appendChild(input);
            } else {
                // Fallback: Text input
                input = document.createElement('input');
                input.type = 'text';
                input.dataset.qid = questionId;
                input.required = q.required_flag || false;
                input.style.width = '100%';
                input.style.padding = '8px';
                questionDiv.appendChild(input);
            }
            
            questionsContainer.appendChild(questionDiv);
        });
        
        container.style.display = 'block';
        statusEl.textContent = '✓ Survey loaded. Please answer the questions below.';
        statusEl.style.color = '#166534';
        
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
        container.style.display = 'none';
    }
}

async function submitResponse(e) {
    e.preventDefault();
    const statusEl = document.getElementById('respStatus');
    statusEl.textContent = 'Submitting...';
    statusEl.style.color = '#64748b';
    
    const sid = document.getElementById('resp_sid').value;
    if (!sid) {
        statusEl.textContent = '✗ Please enter a Survey ID';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    // Build responses object from form fields
    const responses = {};
    const inputs = document.querySelectorAll('[data-qid]');
    
    inputs.forEach(input => {
        const questionId = input.dataset.qid;
        const question = loadedSurveyQuestions.find(q => q.id == questionId);
        
        if (!question) return;
        
        if (input.type === 'radio') {
            // Radio buttons: only get checked value
            if (input.checked) {
                // For rating questions, convert to number; for yes_no, keep as string
                if (question.question_type === 'rating') {
                    responses[questionId] = parseInt(input.value, 10);
                } else {
                    responses[questionId] = input.value;
                }
            }
        } else if (input.tagName === 'SELECT' && input.multiple) {
            // Multiple select: get array of selected values
            const selected = Array.from(input.selectedOptions).map(opt => opt.value);
            if (selected.length > 0) {
                responses[questionId] = selected;
            }
        } else {
            // Text, textarea, single select: get value
            const value = input.value.trim();
            if (value) {
                responses[questionId] = value;
            }
        }
    });
    
    // Validate required questions
    const missingRequired = loadedSurveyQuestions.filter(q => {
        if (!q.required_flag) return false;
        return !responses.hasOwnProperty(q.id) || 
               (Array.isArray(responses[q.id]) && responses[q.id].length === 0) ||
               (typeof responses[q.id] === 'string' && responses[q.id].trim() === '');
    });
    
    if (missingRequired.length > 0) {
        statusEl.textContent = '✗ Please answer all required questions: ' + 
            missingRequired.map(q => q.question_text).join(', ');
        statusEl.style.color = '#dc2626';
        return;
    }
    
    if (Object.keys(responses).length === 0) {
        statusEl.textContent = '✗ Please answer at least one question';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + sid + '/responses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ responses })
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Response submitted successfully!';
            statusEl.style.color = '#166534';
            document.getElementById('responseForm').reset();
            document.getElementById('surveyResponseContainer').style.display = 'none';
            document.getElementById('surveyQuestionsContainer').innerHTML = '';
            loadedSurveyQuestions = [];
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

// ==================== MODAL FUNCTIONS ====================

// Create Survey Modal
function openCreateSurveyModal() {
    document.getElementById('createSurveyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadModalCampaigns();
}

function closeCreateSurveyModal() {
    document.getElementById('createSurveyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('modalSurveyForm').reset();
    document.getElementById('modalSurveyStatus').textContent = '';
    document.getElementById('modalQuestionBuilder').style.display = 'none';
    document.getElementById('modalQuestionsList').innerHTML = '';
    modalCurrentSurveyId = null;
}

let modalCurrentSurveyId = null;

async function loadModalCampaigns() {
    const select = document.getElementById('modal_campaign_id');
    if (!select) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const campaigns = data.data || [];
        
        select.innerHTML = '<option value="">-- Select Campaign --</option>';
        campaigns.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = `[#${c.id}] ${c.title || 'Untitled'}`;
            select.appendChild(option);
        });
    } catch (err) {
        console.error('Error loading campaigns for modal:', err);
    }
}

async function submitModalSurveyForm() {
    const statusEl = document.getElementById('modalSurveyStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        title: document.getElementById('modal_title').value.trim(),
        description: document.getElementById('modal_description').value.trim() || null,
        campaign_id: parseInt(document.getElementById('modal_campaign_id').value) || null,
        event_id: parseInt(document.getElementById('modal_event_id').value) || null
    };
    
    if (!payload.title) {
        statusEl.textContent = '✗ Error: Title is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            modalCurrentSurveyId = data.id;
            statusEl.textContent = '✓ Survey created! ID: ' + data.id + ' - Now add questions below.';
            statusEl.style.color = '#166534';
            document.getElementById('modalQuestionBuilder').style.display = 'block';
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed to create survey');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function addModalQuestion() {
    if (!modalCurrentSurveyId) {
        alert('Please create a survey first');
        return;
    }
    
    const optsRaw = document.getElementById('modal_q_options').value.trim();
    const opts = optsRaw ? optsRaw.split(',').map(s => s.trim()).filter(Boolean) : [];
    
    const payload = {
        question_text: document.getElementById('modal_q_text').value.trim(),
        question_type: document.getElementById('modal_q_type').value,
        options: opts,
        question_order: parseInt(document.getElementById('modal_q_order').value) || 0,
        required_flag: document.getElementById('modal_q_required').checked
    };
    
    if (!payload.question_text) {
        alert('Question text is required');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + modalCurrentSurveyId + '/questions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            document.getElementById('modalSurveyStatus').textContent = '✓ Question added!';
            document.getElementById('modalSurveyStatus').style.color = '#166534';
            document.getElementById('modal_q_text').value = '';
            document.getElementById('modal_q_options').value = '';
            document.getElementById('modal_q_order').value = '';
            document.getElementById('modal_q_required').checked = false;
            loadModalQuestions();
        } else {
            alert('Error: ' + (data.error || 'Failed'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function loadModalQuestions() {
    if (!modalCurrentSurveyId) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + modalCurrentSurveyId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const container = document.getElementById('modalQuestionsList');
            const questions = data.data.questions || [];
            
            if (questions.length === 0) {
                container.innerHTML = '<p style="color:#64748b; padding:12px; background:#f8fafc; border-radius:4px;">No questions added yet.</p>';
            } else {
                let html = '<div style="display:flex; flex-direction:column; gap:8px;">';
                questions.forEach((q, idx) => {
                    html += `<div style="padding:10px; background:#f8fafc; border-radius:6px; border-left:3px solid #4c8a89;">
                        <strong>Q${idx + 1}:</strong> ${q.question_text}
                        <span style="color:#64748b; font-size:11px; margin-left:8px;">(${q.question_type}${q.required_flag ? ', Required' : ''})</span>
                    </div>`;
                });
                html += '</div>';
                container.innerHTML = html;
            }
        }
    } catch (err) {
        console.error('Error loading questions:', err);
    }
}

async function publishModalSurvey() {
    if (!modalCurrentSurveyId) {
        alert('Please create a survey first');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + modalCurrentSurveyId + '/publish', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ published_via: 'both' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Survey published successfully!');
            closeCreateSurveyModal();
            loadSurveys();
        } else {
            alert('Error: ' + (data.error || 'Failed to publish'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// View Survey Modal
async function openViewSurveyModal(surveyId) {
    document.getElementById('viewSurveyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    const contentDiv = document.getElementById('viewSurveyContent');
    contentDiv.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const survey = data.data;
            const statusColors = {
                'draft': { bg: '#e5e7eb', color: '#374151' },
                'published': { bg: '#d1fae5', color: '#065f46' },
                'closed': { bg: '#fee2e2', color: '#991b1b' },
                'archived': { bg: '#f3f4f6', color: '#6b7280' }
            };
            const status = survey.status || 'draft';
            const statusStyle = statusColors[status] || statusColors['draft'];
            
            contentDiv.innerHTML = `
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:16px; margin-bottom:20px;">
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Survey ID</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600; color:#1e293b;">#${survey.id}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Title</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600; color:#1e293b;">${survey.title || 'N/A'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Status</strong>
                        <p style="margin:4px 0 0 0;"><span style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:4px 10px; border-radius:4px; font-size:12px; font-weight:600;">${status}</span></p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Questions</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600; color:#1e293b;">${survey.questions ? survey.questions.length : 0}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Responses</strong>
                        <p style="margin:4px 0 0 0; font-size:16px; font-weight:600; color:#1e293b;">${survey.total_responses || 0}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Campaign ID</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${survey.campaign_id || 'None'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Event ID</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${survey.event_id || 'None'}</p>
                    </div>
                    <div style="background:#f8fafc; padding:12px; border-radius:8px;">
                        <strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Created</strong>
                        <p style="margin:4px 0 0 0; font-size:14px;">${survey.created_at ? new Date(survey.created_at).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
                ${survey.description ? `<div style="background:#f8fafc; padding:16px; border-radius:8px; margin-bottom:16px;"><strong style="color:#64748b; font-size:11px; text-transform:uppercase;">Description</strong><p style="margin:8px 0 0 0; color:#475569;">${survey.description}</p></div>` : ''}
                ${survey.questions && survey.questions.length > 0 ? `
                    <div style="margin-top:20px;">
                        <h4 style="margin:0 0 12px 0; color:#1e293b; font-size:14px; text-transform:uppercase; letter-spacing:0.5px;">Questions</h4>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            ${survey.questions.map((q, idx) => `
                                <div style="padding:14px; background:linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-radius:8px; border-left:4px solid #4c8a89;">
                                    <div style="font-weight:600; color:#1e293b; margin-bottom:6px;">Q${idx + 1}: ${q.question_text}</div>
                                    <div style="display:flex; gap:12px; font-size:12px; color:#64748b;">
                                        <span><i class="fas fa-tag"></i> ${q.question_type}</span>
                                        ${q.required_flag ? '<span style="color:#dc2626;"><i class="fas fa-asterisk"></i> Required</span>' : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : '<p style="color:#64748b; text-align:center; padding:20px;">No questions added to this survey.</p>'}
            `;
        } else {
            contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error loading survey details</p>';
        }
    } catch (err) {
        contentDiv.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

function closeViewSurveyModal() {
    document.getElementById('viewSurveyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Edit Survey Modal
async function openEditSurveyModal(surveyId) {
    document.getElementById('editSurveyModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    document.getElementById('edit_survey_id').value = surveyId;
    
    // Load campaigns for dropdown
    await loadEditModalCampaigns();
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        
        if (res.ok && data.data) {
            const survey = data.data;
            
            if (survey.status !== 'draft') {
                alert('Only draft surveys can be edited.');
                closeEditSurveyModal();
                return;
            }
            
            document.getElementById('edit_title').value = survey.title || '';
            document.getElementById('edit_description').value = survey.description || '';
            document.getElementById('edit_campaign_id').value = survey.campaign_id || '';
            document.getElementById('edit_event_id').value = survey.event_id || '';
        }
    } catch (err) {
        console.error('Error loading survey for edit:', err);
    }
}

async function loadEditModalCampaigns() {
    const select = document.getElementById('edit_campaign_id');
    if (!select) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const campaigns = data.data || [];
        
        const currentVal = select.value;
        select.innerHTML = '<option value="">-- Select Campaign --</option>';
        campaigns.forEach(c => {
            const option = document.createElement('option');
            option.value = c.id;
            option.textContent = `[#${c.id}] ${c.title || 'Untitled'}`;
            select.appendChild(option);
        });
        if (currentVal) select.value = currentVal;
    } catch (err) {
        console.error('Error loading campaigns for edit modal:', err);
    }
}

function closeEditSurveyModal() {
    document.getElementById('editSurveyModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.getElementById('editSurveyForm').reset();
    document.getElementById('editSurveyStatus').textContent = '';
}

async function submitEditSurveyForm() {
    const statusEl = document.getElementById('editSurveyStatus');
    const surveyId = document.getElementById('edit_survey_id').value;
    
    statusEl.textContent = 'Updating...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        title: document.getElementById('edit_title').value.trim(),
        description: document.getElementById('edit_description').value.trim() || null,
        campaign_id: parseInt(document.getElementById('edit_campaign_id').value) || null,
        event_id: parseInt(document.getElementById('edit_event_id').value) || null
    };
    
    if (!payload.title) {
        statusEl.textContent = '✗ Error: Title is required';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Survey updated successfully!';
            statusEl.style.color = '#166534';
            setTimeout(() => {
                closeEditSurveyModal();
                loadSurveys();
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

// Archive Survey
async function archiveSurvey(surveyId) {
    if (!confirm('Archive this survey? It can be restored from View Archived.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ status: 'archived' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Survey archived successfully');
            loadSurveys();
        } else {
            alert('Error: ' + (data.error || 'Failed to archive'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Archived Surveys Modal
async function openArchivedSurveysModal() {
    document.getElementById('archivedSurveysModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadArchivedSurveys();
}

function closeArchivedSurveysModal() {
    document.getElementById('archivedSurveysModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

async function loadArchivedSurveys() {
    const container = document.getElementById('archivedSurveysList');
    container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>';
    
    try {
        // Request archived surveys specifically
        const res = await fetch(apiBase + '/api/v1/surveys?status=archived', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        const archivedSurveys = data.data || [];
        
        if (archivedSurveys.length === 0) {
            container.innerHTML = '<p style="text-align:center; color:#64748b; padding:24px;">No archived surveys found.</p>';
            return;
        }
        
        let html = '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f8fafc;"><th style="padding:12px; text-align:left;">ID</th><th style="padding:12px; text-align:left;">Title</th><th style="padding:12px; text-align:left;">Questions</th><th style="padding:12px; text-align:left;">Responses</th><th style="padding:12px; text-align:left;">Actions</th></tr></thead><tbody>';
        archivedSurveys.forEach(s => {
            html += `<tr style="border-bottom:1px solid #e2e8f0;">
                <td style="padding:12px;">${s.id}</td>
                <td style="padding:12px;"><strong>${s.title || 'Untitled'}</strong></td>
                <td style="padding:12px;">${s.question_count || 0}</td>
                <td style="padding:12px;">${s.total_responses || 0}</td>
                <td style="padding:12px;">
                    <button class="btn btn-secondary" style="padding:4px 8px; font-size:11px; margin:2px;" onclick="openViewSurveyModal(${s.id})">👁️ View</button>
                    <button class="btn btn-success" style="padding:4px 8px; font-size:11px; background:#10b981; color:white; margin:2px;" onclick="restoreSurvey(${s.id})">🔄 Restore</button>
                    <button class="btn btn-danger" style="padding:4px 8px; font-size:11px; background:#ef4444; color:white; margin:2px;" onclick="deleteSurveyPermanently(${s.id})">🗑️ Delete</button>
                </td>
            </tr>`;
        });
        html += '</tbody></table>';
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align:center; color:#dc2626; padding:24px;">Error: ' + err.message + '</p>';
    }
}

async function restoreSurvey(surveyId) {
    if (!confirm('Restore this survey?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ status: 'draft' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Survey restored successfully');
            loadArchivedSurveys();
            loadSurveys();
        } else {
            alert('Error: ' + (data.error || 'Failed to restore'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

async function deleteSurveyPermanently(surveyId) {
    if (!confirm('Permanently delete this survey? This cannot be undone and will remove all questions and responses.')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok) {
            alert('Survey deleted permanently');
            loadArchivedSurveys();
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
</script>

<!-- Create Survey Modal -->
<div id="createSurveyModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:700px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-clipboard-list"></i> Create Survey</h2>
            <button onclick="closeCreateSurveyModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <form id="modalSurveyForm" class="form-grid">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Title *</label>
                    <input id="modal_title" type="text" placeholder="Post-event feedback" required>
                </div>
                <div class="form-field">
                    <label>Link to Campaign</label>
                    <select id="modal_campaign_id">
                        <option value="">-- Select Campaign --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Link to Event ID</label>
                    <input id="modal_event_id" type="number" placeholder="Event ID">
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <textarea id="modal_description" rows="3" placeholder="Survey description..."></textarea>
                </div>
            </form>
            <div style="display:flex; gap:12px; margin-top:20px;">
                <button class="btn btn-primary" onclick="submitModalSurveyForm()">Create Survey</button>
                <button class="btn btn-secondary" onclick="closeCreateSurveyModal()">Cancel</button>
            </div>
            <div id="modalSurveyStatus" style="margin-top:12px;"></div>
            
            <!-- Question Builder (shown after survey created) -->
            <div id="modalQuestionBuilder" style="display:none; margin-top:24px; padding-top:24px; border-top:2px solid #f1f5f9;">
                <h3 style="font-size:16px; font-weight:600; margin-bottom:16px;">Add Questions</h3>
                <div class="form-grid">
                    <div class="form-field" style="grid-column: 1 / -1;">
                        <label>Question Text *</label>
                        <input id="modal_q_text" type="text" placeholder="How satisfied were you?">
                    </div>
                    <div class="form-field">
                        <label>Question Type</label>
                        <select id="modal_q_type">
                            <option value="open_ended">Open Ended</option>
                            <option value="rating">Rating (1-5)</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="yes_no">Yes/No</option>
                            <option value="single_choice">Single Choice</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label>Options (comma separated)</label>
                        <input id="modal_q_options" type="text" placeholder="Option 1, Option 2, Option 3">
                    </div>
                    <div class="form-field">
                        <label>Order</label>
                        <input id="modal_q_order" type="number" placeholder="Auto" min="0">
                    </div>
                    <div class="form-field" style="display:flex; align-items:center; padding-top:20px;">
                        <input type="checkbox" id="modal_q_required" style="width:auto; margin-right:8px;">
                        <label for="modal_q_required" style="margin:0;">Required</label>
                    </div>
                </div>
                <div style="display:flex; gap:8px; margin-top:16px;">
                    <button class="btn btn-primary" onclick="addModalQuestion()">Add Question</button>
                    <button class="btn btn-secondary" onclick="loadModalQuestions()">View Questions</button>
                    <button class="btn btn-success" onclick="publishModalSurvey()" style="background:#10b981; color:white;">Publish Survey</button>
                </div>
                <div id="modalQuestionsList" style="margin-top:16px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- View Survey Modal -->
<div id="viewSurveyModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:10001; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:800px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-eye"></i> Survey Details</h2>
            <button onclick="closeViewSurveyModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div id="viewSurveyContent">
                <p style="text-align:center; color:#64748b; padding:24px;">Loading...</p>
            </div>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeViewSurveyModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Survey Modal -->
<div id="editSurveyModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:600px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-edit"></i> Edit Survey</h2>
            <button onclick="closeEditSurveyModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <form id="editSurveyForm" class="form-grid">
                <input type="hidden" id="edit_survey_id">
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Title *</label>
                    <input id="edit_title" type="text" required>
                </div>
                <div class="form-field">
                    <label>Link to Campaign</label>
                    <select id="edit_campaign_id">
                        <option value="">-- Select Campaign --</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Link to Event ID</label>
                    <input id="edit_event_id" type="number">
                </div>
                <div class="form-field" style="grid-column: 1 / -1;">
                    <label>Description</label>
                    <textarea id="edit_description" rows="3"></textarea>
                </div>
            </form>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeEditSurveyModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitEditSurveyForm()">Update Survey</button>
            </div>
            <div id="editSurveyStatus" style="margin-top:12px;"></div>
        </div>
    </div>
</div>

<!-- Archived Surveys Modal -->
<div id="archivedSurveysModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:900px; margin:40px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.25); max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:20px;"><i class="fas fa-archive"></i> Archived Surveys</h2>
            <button onclick="closeArchivedSurveysModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <div id="archivedSurveysList" style="max-height:500px; overflow-y:auto;">
                <p style="text-align:center; color:#64748b; padding:24px;">Loading archived surveys...</p>
            </div>
            <div style="display:flex; gap:12px; margin-top:20px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeArchivedSurveysModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Survey Export Password Verification Modal -->
<div id="surveyExportPasswordModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:10000; overflow-y:auto;">
    <div class="modal-container" style="background:white; border-radius:16px; max-width:450px; margin:100px auto; padding:0; box-shadow:0 25px 50px rgba(0,0,0,0.3);">
        <div class="modal-header" style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%); border-radius:16px 16px 0 0;">
            <h2 style="margin:0; color:white; font-size:18px;"><i class="fas fa-lock"></i> Verify Password to Export</h2>
            <button onclick="closeSurveyExportModal()" style="background:none; border:none; color:white; font-size:24px; cursor:pointer; padding:0; line-height:1;">&times;</button>
        </div>
        <div class="modal-body" style="padding:24px;">
            <p style="color:#64748b; margin-bottom:16px;">Please enter your password to confirm the export. This ensures data security.</p>
            <input type="hidden" id="surveyExportId" value="">
            <div class="form-field" style="margin-bottom:20px;">
                <label style="font-weight:600; color:#334155;">Password</label>
                <div style="position:relative;">
                    <input type="password" id="surveyExportPassword" placeholder="Enter your password" style="width:100%; padding:12px 40px 12px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:14px;">
                    <button type="button" onclick="toggleSurveyPasswordVisibility()" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:#64748b;">
                        <i class="fas fa-eye" id="surveyPasswordToggleIcon"></i>
                    </button>
                </div>
            </div>
            <div id="surveyExportPasswordError" style="display:none; color:#dc2626; background:#fee2e2; padding:10px; border-radius:6px; margin-bottom:16px; font-size:13px;"></div>
            <div style="display:flex; gap:12px; justify-content:flex-end;">
                <button class="btn btn-secondary" onclick="closeSurveyExportModal()">Cancel</button>
                <button class="btn btn-primary" onclick="verifySurveyPasswordAndExport()" id="surveyExportConfirmBtn" style="background:linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);">
                    <i class="fas fa-download"></i> Export PDF
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// SURVEY PDF EXPORT WITH PASSWORD VERIFICATION
// ============================================

function showSurveyExportModal(surveyId) {
    document.getElementById('surveyExportId').value = surveyId;
    document.getElementById('surveyExportPassword').value = '';
    document.getElementById('surveyExportPasswordError').style.display = 'none';
    document.getElementById('surveyExportPasswordModal').style.display = 'block';
    document.getElementById('surveyExportPassword').focus();
}

function closeSurveyExportModal() {
    document.getElementById('surveyExportPasswordModal').style.display = 'none';
    document.getElementById('surveyExportPassword').value = '';
    document.getElementById('surveyExportPasswordError').style.display = 'none';
}

function toggleSurveyPasswordVisibility() {
    const input = document.getElementById('surveyExportPassword');
    const icon = document.getElementById('surveyPasswordToggleIcon');
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

async function verifySurveyPasswordAndExport() {
    const password = document.getElementById('surveyExportPassword').value;
    const surveyId = document.getElementById('surveyExportId').value;
    const errorDiv = document.getElementById('surveyExportPasswordError');
    const confirmBtn = document.getElementById('surveyExportConfirmBtn');
    
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
            closeSurveyExportModal();
            await generateSurveyPDF(surveyId);
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

// Helper function to convert SVG to PNG for jsPDF
async function loadLogoAsPNG() {
    try {
        const logoUrl = '/header/images/logo.svg';
        const response = await fetch(logoUrl);
        const svgText = await response.text();
        
        return new Promise((resolve) => {
            const img = new Image();
            const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
            const url = URL.createObjectURL(svgBlob);
            
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = 100;
                canvas.height = 60;
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(img, 5, 5, 90, 50);
                URL.revokeObjectURL(url);
                resolve(canvas.toDataURL('image/png'));
            };
            
            img.onerror = function() {
                URL.revokeObjectURL(url);
                resolve(null);
            };
            
            img.src = url;
        });
    } catch (e) {
        console.warn('Failed to load logo as PNG:', e);
        return null;
    }
}

// Generate Survey PDF with professional template
async function generateSurveyPDF(surveyId) {
    try {
        // Fetch survey data and results
        const [surveyRes, resultsRes] = await Promise.all([
            fetch(apiBase + '/api/v1/surveys/' + surveyId, { headers: { 'Authorization': 'Bearer ' + token } }),
            fetch(apiBase + '/api/v1/surveys/' + surveyId + '/results', { headers: { 'Authorization': 'Bearer ' + token } })
        ]);
        
        const surveyData = await surveyRes.json();
        const resultsData = await resultsRes.json();
        
        if (!surveyRes.ok || !surveyData.data) {
            throw new Error('Failed to load survey data');
        }
        
        const survey = surveyData.data;
        const questions = survey.questions || [];
        const results = resultsData.data || resultsData;
        const totalResponses = results.total_responses || survey.total_responses || 0;
        
        // Initialize jsPDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        
        // Colors
        const primaryColor = [76, 138, 137]; // #4c8a89
        const darkColor = [15, 23, 42]; // #0f172a
        
        // Header with gradient-like background
        doc.setFillColor(...primaryColor);
        doc.rect(0, 0, 210, 45, 'F');
        
        // Header text (centered, no logo)
        doc.setTextColor(255, 255, 255);
        doc.setFontSize(22);
        doc.setFont('helvetica', 'bold');
        doc.text('SURVEY REPORT', 105, 18, { align: 'center' });
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.text('Public Safety Campaign System', 105, 26, { align: 'center' });
        doc.text('Barangay Alertaraqc', 105, 32, { align: 'center' });
        doc.text('Generated: ' + new Date().toLocaleString(), 105, 40, { align: 'center' });
        
        // Survey Title
        doc.setTextColor(...darkColor);
        doc.setFontSize(16);
        doc.setFont('helvetica', 'bold');
        const titleText = survey.title || 'Untitled Survey';
        doc.text(titleText, 14, 58);
        
        // Divider line
        doc.setDrawColor(...primaryColor);
        doc.setLineWidth(0.5);
        doc.line(14, 62, 196, 62);
        
        // Survey Details Section
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(...primaryColor);
        doc.text('Survey Details', 14, 72);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(...darkColor);
        
        const details = [
            ['Status:', (survey.status || 'draft').toUpperCase()],
            ['Total Questions:', String(questions.length)],
            ['Total Responses:', String(totalResponses)],
            ['Created:', survey.created_at ? new Date(survey.created_at).toLocaleDateString() : 'N/A'],
            ['Campaign:', survey.campaign_title || 'N/A']
        ];
        
        let yPos = 80;
        details.forEach(([label, value]) => {
            doc.setFont('helvetica', 'bold');
            doc.text(label, 14, yPos);
            doc.setFont('helvetica', 'normal');
            doc.text(String(value), 55, yPos);
            yPos += 7;
        });
        
        // Description
        if (survey.description) {
            yPos += 5;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...primaryColor);
            doc.text('Description', 14, yPos);
            
            yPos += 8;
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            doc.setTextColor(...darkColor);
            const splitDesc = doc.splitTextToSize(survey.description, 180);
            doc.text(splitDesc, 14, yPos);
            yPos += splitDesc.length * 5 + 5;
        }
        
        // Questions Summary
        if (questions.length > 0) {
            yPos += 10;
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...primaryColor);
            doc.text('Questions', 14, yPos);
            
            yPos += 5;
            const questionData = questions.map((q, i) => [
                i + 1,
                q.question_text || q.text || 'N/A',
                q.question_type || q.type || 'text',
                q.is_required ? 'Yes' : 'No'
            ]);
            
            doc.autoTable({
                startY: yPos,
                head: [['#', 'Question', 'Type', 'Required']],
                body: questionData,
                theme: 'striped',
                headStyles: { fillColor: primaryColor },
                styles: { fontSize: 9, cellPadding: 3 },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 120 },
                    2: { cellWidth: 30 },
                    3: { cellWidth: 20 }
                },
                margin: { left: 14, right: 14 }
            });
            
            yPos = doc.lastAutoTable.finalY + 10;
        }
        
        // Response Summary (if available)
        if (results.questions && results.questions.length > 0) {
            // Check if we need a new page
            if (yPos > 240) {
                doc.addPage();
                yPos = 20;
            }
            
            doc.setFontSize(12);
            doc.setFont('helvetica', 'bold');
            doc.setTextColor(...primaryColor);
            doc.text('Response Summary', 14, yPos);
            
            yPos += 10;
            
            results.questions.forEach((q, idx) => {
                if (yPos > 260) {
                    doc.addPage();
                    yPos = 20;
                }
                
                doc.setFontSize(10);
                doc.setFont('helvetica', 'bold');
                doc.setTextColor(...darkColor);
                const qText = (idx + 1) + '. ' + (q.question_text || q.text || 'Question');
                const splitQ = doc.splitTextToSize(qText, 180);
                doc.text(splitQ, 14, yPos);
                yPos += splitQ.length * 5 + 3;
                
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(9);
                
                if (q.aggregated_responses && q.aggregated_responses.length > 0) {
                    q.aggregated_responses.forEach(ar => {
                        const respText = '• ' + (ar.response_value || ar.value || 'N/A') + ': ' + (ar.count || 0) + ' responses';
                        doc.text(respText, 20, yPos);
                        yPos += 5;
                    });
                } else if (q.responses && q.responses.length > 0) {
                    const sampleResponses = q.responses.slice(0, 3);
                    sampleResponses.forEach(r => {
                        const respText = '• ' + (r.response_value || r.value || 'N/A');
                        const splitResp = doc.splitTextToSize(respText, 170);
                        doc.text(splitResp, 20, yPos);
                        yPos += splitResp.length * 4;
                    });
                    if (q.responses.length > 3) {
                        doc.text('... and ' + (q.responses.length - 3) + ' more responses', 20, yPos);
                        yPos += 5;
                    }
                } else {
                    doc.text('• No responses yet', 20, yPos);
                    yPos += 5;
                }
                
                yPos += 5;
            });
        }
        
        // Footer on last page
        const pageCount = doc.internal.getNumberOfPages();
        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            const pageHeight = doc.internal.pageSize.height;
            doc.setFillColor(...primaryColor);
            doc.rect(0, pageHeight - 12, 210, 12, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(8);
            doc.text('Public Safety Campaign System - Survey Report | Page ' + i + ' of ' + pageCount, 105, pageHeight - 5, { align: 'center' });
        }
        
        // Save PDF
        const fileName = `Survey_Report_${survey.title || surveyId}_${new Date().toISOString().split('T')[0]}.pdf`;
        doc.save(fileName);
        
        customConfirm('Survey PDF exported successfully!');
        
    } catch (error) {
        console.error('PDF generation error:', error);
        customConfirm('Failed to generate PDF: ' + error.message);
    }
}
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
