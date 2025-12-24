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
                <label>Campaign ID</label>
                <input id="campaign_id" type="number" placeholder="1">
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
                        <option value="text">Text</option>
                        <option value="single_choice">Single Choice</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="rating">Rating (1-5)</option>
                    </select>
                </div>
                <div class="form-field">
                    <label>Options (comma separated)</label>
                    <input id="q_options" type="text" placeholder="Very Satisfied, Satisfied, Neutral, Dissatisfied">
                </div>
            </form>
            <div style="display:flex; gap:8px; margin-top:16px;">
                <button class="btn btn-primary" onclick="addQuestion()">Add Question</button>
                <button class="btn btn-secondary" onclick="publishSurvey()">Publish Survey</button>
            </div>
        </div>
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
        campaign_id: parseInt(document.getElementById('campaign_id').value, 10) || null
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
        options: opts
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
    
    try {
        const res = await fetch(apiBase + '/api/v1/surveys/' + currentSurveyId + '/publish', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        document.getElementById('createStatus').textContent = res.ok ? '✓ Survey published!' : ('✗ Error: ' + (data.error || 'Failed'));
        document.getElementById('createStatus').style.color = res.ok ? '#166534' : '#dc2626';
    } catch (err) {
        document.getElementById('createStatus').textContent = '✗ Network error: ' + err.message;
        document.getElementById('createStatus').style.color = '#dc2626';
    }
}

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
