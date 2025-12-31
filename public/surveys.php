<?php
$pageTitle = 'Surveys & Feedback';
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
<body class="module-surveys" data-module="surveys">
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
    .surveys-page {
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
</style>

<main class="surveys-page">
    <div class="page-header">
        <h1>Surveys & Feedback</h1>
        <p>Create surveys and collect feedback from campaign participants</p>
    </div>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Create Survey</h2>
        <form id="createForm" class="form-grid">
            <div class="form-field">
                <label>Title *</label>
                <input id="title" type="text" placeholder="Post-event feedback" required>
            </div>
            <div class="form-field">
                <label>Link to Campaign ID</label>
                <input id="campaign_id" type="number" placeholder="1">
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

        <div id="builder" style="display:none; margin-top:32px; padding-top:24px; border-top:2px solid #f1f5f9;">
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

    <!-- Survey Dashboard -->
    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Survey Dashboard</h2>
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
    <section class="card" id="resultsSection" style="display:none; margin-bottom:24px;">
        <h2 class="section-title">Survey Results</h2>
        <div id="resultsContent"></div>
    </section>

    <section class="card">
        <h2 class="section-title">Submit Response (Public)</h2>
        <form id="responseForm" class="form-grid">
            <div class="form-field">
                <label>Survey ID *</label>
                <input id="resp_sid" type="number" required>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Responses JSON *</label>
                <textarea id="resp_json" rows="4" placeholder='{ "1": "Yes", "2": 5 }' required></textarea>
            </div>
        </form>
        <button type="submit" form="responseForm" class="btn btn-primary" style="margin-top:16px;" onclick="submitResponse(event)">Submit Response</button>
        <div class="status" id="respStatus" style="margin-top:12px;"></div>
    </section>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
let currentSurveyId = null;

async function createSurvey() {
    const statusEl = document.getElementById('createStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        title: document.getElementById('title').value.trim(),
        description: document.getElementById('description').value.trim() || null,
        campaign_id: parseInt(document.getElementById('campaign_id').value, 10) || null,
        event_id: parseInt(document.getElementById('event_id').value, 10) || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok && data.id) {
            currentSurveyId = data.id;
            statusEl.textContent = '✓ Survey created! ID: ' + data.id + ' - Now add questions below.';
            statusEl.style.color = '#166534';
            document.getElementById('builder').style.display = 'block';
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
            renderSurveysList(data.data);
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
    
    let html = '<table style="width:100%; border-collapse:collapse;"><thead><tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0;"><th style="padding:12px; text-align:left;">ID</th><th style="padding:12px; text-align:left;">Title</th><th style="padding:12px; text-align:left;">Status</th><th style="padding:12px; text-align:left;">Questions</th><th style="padding:12px; text-align:left;">Responses</th><th style="padding:12px; text-align:left;">Actions</th></tr></thead><tbody>';
    
    surveys.forEach(survey => {
        const statusColor = survey.status === 'published' ? '#166534' : survey.status === 'closed' ? '#dc2626' : '#64748b';
        html += `<tr style="border-bottom:1px solid #e2e8f0;">
            <td style="padding:12px;">${survey.id}</td>
            <td style="padding:12px;">${survey.title || ''}</td>
            <td style="padding:12px;"><span style="color:${statusColor}; font-weight:600;">${survey.status || 'draft'}</span></td>
            <td style="padding:12px;">${survey.question_count || 0}</td>
            <td style="padding:12px;">${survey.total_responses || 0}</td>
            <td style="padding:12px;">
                <button class="btn btn-secondary" onclick="viewResults(${survey.id})" style="padding:4px 8px; font-size:12px; margin-right:4px;">Results</button>
                <button class="btn btn-secondary" onclick="exportResponses(${survey.id})" style="padding:4px 8px; font-size:12px; margin-right:4px;">Export</button>
                ${survey.status === 'published' ? `<button class="btn btn-secondary" onclick="closeSurvey(${survey.id})" style="padding:4px 8px; font-size:12px;">Close</button>` : ''}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

async function viewResults(surveyId) {
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + surveyId + '/results', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok && data.results) {
            renderResults(data);
            document.getElementById('resultsSection').style.display = 'block';
            document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
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
    window.location.href = apiBase + '/api/v1/surveys/' + surveyId + '/responses/export?token=' + encodeURIComponent(token);
}

async function exportAggregatedResults(surveyId) {
    window.location.href = apiBase + '/api/v1/surveys/' + surveyId + '/results/export?token=' + encodeURIComponent(token);
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
    if (!currentSurveyId) {
        alert('Please create or select a survey first');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + currentSurveyId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok && data.data && data.data.questions) {
            const container = document.getElementById('questionsList');
            let html = '<h4 style="margin:0 0 12px 0;">Current Questions:</h4><ul style="list-style:none; padding:0; margin:0;">';
            data.data.questions.forEach((q, idx) => {
                html += `<li style="padding:8px; margin-bottom:8px; background:#f8fafc; border-radius:4px;">
                    ${idx + 1}. ${q.question_text} <span style="color:#64748b; font-size:12px;">(${q.question_type}${q.required_flag ? ', Required' : ''})</span>
                </li>`;
            });
            html += '</ul>';
            container.innerHTML = html;
        }
    } catch (err) {
        console.error('Error loading questions:', err);
    }
}

// Load surveys on page load
loadSurveys();

async function submitResponse(e) {
    e.preventDefault();
    const statusEl = document.getElementById('respStatus');
    statusEl.textContent = 'Submitting...';
    statusEl.style.color = '#64748b';
    
    const sid = document.getElementById('resp_sid').value;
    let responses = {};
    try {
        responses = JSON.parse(document.getElementById('resp_json').value || '{}');
    } catch (e) {
        statusEl.textContent = '✗ Invalid JSON format';
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
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}
</script>
    </main>
</body>
</html>
