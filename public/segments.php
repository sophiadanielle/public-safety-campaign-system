<?php
$pageTitle = 'Segments';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="page-content">
    <h1>Segments</h1>
    <section class="card" style="margin-top:12px;">
        <h2>Create Segment</h2>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:260px;">
                <label>Name <input id="name" type="text" placeholder="SMS Residents"></label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Criteria JSON
                    <textarea id="criteria" rows="3">{ "channel": ["sms"] }</textarea>
                </label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Demographics JSON
                    <textarea id="demographics" rows="3" placeholder='{ "age": "18-35" }'></textarea>
                </label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Geographies JSON
                    <textarea id="geographies" rows="3" placeholder='{ "city": "Quezon" }'></textarea>
                </label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Preferences JSON
                    <textarea id="preferences" rows="3" placeholder='{ "channel_pref": "sms" }'></textarea>
                </label>
            </div>
            <div style="flex:1; min-width:180px;">
                <label>Risk Level <input id="risk_level" type="text" placeholder="high/medium/low"></label>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:12px;" onclick="createSegment()">Create</button>
        <div class="status" id="createStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Import Members (CSV)</h2>
        <form id="importForm">
            <label>Segment ID <input name="segment_id" type="number" required></label>
            <label>CSV File <input type="file" name="file" accept=".csv" required></label>
            <p style="color:#6b7280; font-size:12px; margin:6px 0;">Optional columns: risk_level, geo, preferences (JSON)</p>
            <button type="submit" class="btn btn-primary" style="margin-top:8px;">Import</button>
        </form>
        <div class="status" id="importStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <div style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div style="flex:1; min-width:200px;">
                <label>Segment ID <input id="eval_segment" type="number"></label>
            </div>
            <button class="btn btn-secondary" onclick="evaluateSegment()">Evaluate</button>
        </div>
        <div class="status" id="evalStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
const token = localStorage.getItem('jwtToken') || '';
async function createSegment() {
    const payload = {
        name: document.getElementById('name').value.trim(),
        criteria: JSON.parse(document.getElementById('criteria').value || '{}'),
        demographics: parseOrNull('demographics'),
        geographies: parseOrNull('geographies'),
        preferences: parseOrNull('preferences'),
        risk_level: document.getElementById('risk_level').value.trim() || null
    };
    const res = await fetch('/api/v1/segments', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('createStatus').textContent = JSON.stringify(data);
}

document.getElementById('importForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = new FormData(e.target);
    const segmentId = form.get('segment_id');
    form.delete('segment_id');
    const res = await fetch('/api/v1/segments/' + segmentId + '/members/batch', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: form
    });
    const data = await res.json();
    document.getElementById('importStatus').textContent = JSON.stringify(data);
});

async function evaluateSegment() {
    const id = document.getElementById('eval_segment').value;
    const res = await fetch('/api/v1/segments/' + id + '/evaluate', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token }
    });
    const data = await res.json();
    document.getElementById('evalStatus').textContent = JSON.stringify(data, null, 2);
}

function parseOrNull(id) {
    const raw = document.getElementById(id).value;
    if (!raw) return null;
    try { return JSON.parse(raw); } catch (e) { return null; }
}
</script>



