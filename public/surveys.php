<?php
$pageTitle = 'Surveys';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="page-content">
    <h1>Surveys</h1>
    <section class="card" style="margin-top:12px;">
        <h2>Create Survey</h2>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:260px;">
                <label>Title <input id="title" type="text" placeholder="Post-event feedback"></label>
                <label>Campaign ID <input id="campaign_id" type="number" placeholder="1"></label>
                <label>Event ID (optional) <input id="event_id" type="number" placeholder="1"></label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Description <textarea id="description" rows="3"></textarea></label>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:12px;" onclick="createSurvey()">Create</button>
        <div class="status" id="createStatus" style="margin-top:10px; white-space:pre-wrap;"></div>

        <div id="builder" style="display:none; margin-top:24px;">
            <h3>Add Question</h3>
            <label>Question Text <input id="q_text" type="text"></label>
            <label>Type
                <select id="q_type">
                    <option value="text">text</option>
                    <option value="single_choice">single_choice</option>
                    <option value="multiple_choice">multiple_choice</option>
                    <option value="rating">rating</option>
                </select>
            </label>
            <label>Options (comma separated) <input id="q_options" type="text"></label>
            <button class="btn btn-primary" style="margin-top:8px;" onclick="addQuestion()">Add Question</button>
            <button class="btn btn-secondary" style="margin-top:8px;" onclick="publishSurvey()">Publish</button>
        </div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Submit Response (public)</h2>
        <label>Survey ID <input id="resp_sid" type="number"></label>
        <label>Responses JSON <textarea id="resp_json" rows="3">{ "1": "Yes" }</textarea></label>
        <button class="btn btn-primary" style="margin-top:8px;" onclick="submitResponse()">Submit</button>
        <div class="status" id="respStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
const token = localStorage.getItem('jwtToken') || '';
let currentSurveyId = null;

async function createSurvey() {
    const payload = {
        title: document.getElementById('title').value.trim(),
        description: document.getElementById('description').value.trim(),
        campaign_id: parseInt(document.getElementById('campaign_id').value, 10) || null,
        event_id: parseInt(document.getElementById('event_id').value, 10) || null
    };
    const res = await fetch('/api/v1/surveys', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('createStatus').textContent = JSON.stringify(data);
    if (data.id) {
        currentSurveyId = data.id;
        document.getElementById('builder').style.display = 'block';
    }
}

async function addQuestion() {
    if (!currentSurveyId) return;
    const optsRaw = document.getElementById('q_options').value.trim();
    const opts = optsRaw ? optsRaw.split(',').map(s => s.trim()).filter(Boolean) : [];
    const payload = {
        question_text: document.getElementById('q_text').value.trim(),
        question_type: document.getElementById('q_type').value,
        options: opts
    };
    const res = await fetch('/api/v1/surveys/' + currentSurveyId + '/questions', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('createStatus').textContent = JSON.stringify(data);
}

async function publishSurvey() {
    if (!currentSurveyId) return;
    const res = await fetch('/api/v1/surveys/' + currentSurveyId + '/publish', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await res.json();
    document.getElementById('createStatus').textContent = JSON.stringify(data);
}

async function submitResponse() {
    const sid = document.getElementById('resp_sid').value;
    let responses = {};
    try { responses = JSON.parse(document.getElementById('resp_json').value || '{}'); } catch (e) {}
    const res = await fetch('/api/v1/surveys/' + sid + '/responses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ responses })
    });
    const data = await res.json();
    document.getElementById('respStatus').textContent = JSON.stringify(data);
}
</script>



