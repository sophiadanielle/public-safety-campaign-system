<?php
$pageTitle = 'Audience Segments';
include __DIR__ . '/../header/includes/header.php';
?>

<style>
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
</style>

<main class="segments-page">
    <div class="page-header">
        <h1>Audience Segments</h1>
        <p>Create and manage target audience segments for campaigns</p>
    </div>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Create Segment</h2>
        <form id="createForm" class="form-grid">
            <div class="form-field">
                <label>Name *</label>
                <input id="name" type="text" placeholder="SMS Residents" required>
            </div>
            <div class="form-field">
                <label>Risk Level</label>
                <select id="risk_level">
                    <option value="">Select...</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                </select>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Criteria JSON</label>
                <textarea id="criteria" rows="3" placeholder='{ "channel": ["sms"] }'>{ "channel": ["sms"] }</textarea>
            </div>
            <div class="form-field">
                <label>Geographic Scope</label>
                <input id="geographic_scope" type="text" placeholder="Quezon City - Barangay 1-5">
            </div>
            <div class="form-field">
                <label>Sector Type</label>
                <input id="sector_type" type="text" placeholder="residential, commercial, education">
            </div>
        </form>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="createSegment()">Create Segment</button>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Import Members (CSV)</h2>
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
        <p style="color:#64748b; font-size:13px; margin:8px 0;">Optional columns: risk_level, geo, preferences (JSON)</p>
        <button type="submit" form="importForm" class="btn btn-primary" style="margin-top:8px;">Import CSV</button>
        <div class="status" id="importStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card">
        <h2 class="section-title">Evaluate Segment</h2>
        <div class="form-grid">
            <div class="form-field">
                <label>Segment ID</label>
                <input id="eval_segment" type="number" placeholder="1">
            </div>
        </div>
        <button class="btn btn-secondary" style="margin-top:16px;" onclick="evaluateSegment()">Evaluate Segment</button>
        <div class="status" id="evalStatus" style="margin-top:12px; white-space:pre-wrap; font-family:monospace; font-size:12px;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

async function createSegment() {
    const statusEl = document.getElementById('createStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    let criteria = {};
    try {
        criteria = JSON.parse(document.getElementById('criteria').value || '{}');
    } catch (e) {
        statusEl.textContent = '✗ Invalid JSON in Criteria field';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    const payload = {
        name: document.getElementById('name').value.trim(),
        criteria: criteria,
        risk_level: document.getElementById('risk_level').value || null,
        geographic_scope: document.getElementById('geographic_scope').value.trim() || null,
        sector_type: document.getElementById('sector_type').value.trim() || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Segment created successfully! ID: ' + (data.id || 'N/A');
            statusEl.style.color = '#166534';
            document.getElementById('createForm').reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

document.getElementById('importForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const statusEl = document.getElementById('importStatus');
    statusEl.textContent = 'Importing...';
    statusEl.style.color = '#64748b';
    
    const form = new FormData(e.target);
    const segmentId = form.get('segment_id');
    form.delete('segment_id');
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + segmentId + '/members/batch', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: form
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Imported successfully! ' + (data.message || '');
            statusEl.style.color = '#166534';
            e.target.reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Import failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
});

async function evaluateSegment() {
    const id = document.getElementById('eval_segment').value;
    if (!id) {
        document.getElementById('evalStatus').textContent = 'Please enter a Segment ID';
        return;
    }
    
    const statusEl = document.getElementById('evalStatus');
    statusEl.textContent = 'Evaluating...';
    
    try {
        const res = await fetch(apiBase + '/api/v1/segments/' + id + '/evaluate', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        statusEl.textContent = JSON.stringify(data, null, 2);
        statusEl.style.color = '#0f172a';
    } catch (err) {
        statusEl.textContent = 'Error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}
</script>
