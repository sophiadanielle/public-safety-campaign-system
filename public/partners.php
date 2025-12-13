<?php
$pageTitle = 'Partners';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="page-content">
    <h1>Partners</h1>
    <section class="card" style="margin-top:12px;">
        <h2>Add Partner</h2>
        <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <div style="flex:1; min-width:260px;">
                <label>Name <input id="p_name" type="text" placeholder="Red Cross"></label>
                <label>Contact Person <input id="p_person" type="text"></label>
            </div>
            <div style="flex:1; min-width:260px;">
                <label>Email <input id="p_email" type="email"></label>
                <label>Phone <input id="p_phone" type="text"></label>
            </div>
        </div>
        <button class="btn btn-primary" style="margin-top:10px;" onclick="addPartner()">Save</button>
        <div class="status" id="partnerStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Engage Partner</h2>
        <label>Partner ID <input id="e_pid" type="number"></label>
        <label>Campaign ID <input id="e_cid" type="number"></label>
        <label>Event ID (optional) <input id="e_eid" type="number"></label>
        <label>Engagement Type <input id="e_type" type="text" value="collaboration"></label>
        <label>Notes <textarea id="e_notes" rows="2"></textarea></label>
        <label>Webhook URL (optional) <input id="e_webhook" type="text" placeholder="https://example.com/hook"></label>
        <button class="btn btn-primary" style="margin-top:8px;" onclick="engage()">Engage</button>
        <div class="status" id="engageStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Assignments (Partner Portal)</h2>
        <label>Partner ID <input id="a_pid" type="number"></label>
        <button class="btn btn-secondary" style="margin-top:8px;" onclick="loadAssignments()">Load</button>
        <table style="width:100%; border-collapse:collapse; margin-top:12px;">
            <thead><tr><th>Campaign</th><th>Status</th><th>Event</th><th>Starts</th></tr></thead>
            <tbody id="assignTable"></tbody>
        </table>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
const token = localStorage.getItem('jwtToken') || '';
async function addPartner() {
    const payload = {
        name: document.getElementById('p_name').value.trim(),
        contact_person: document.getElementById('p_person').value.trim(),
        contact_email: document.getElementById('p_email').value.trim(),
        contact_phone: document.getElementById('p_phone').value.trim()
    };
    const res = await fetch('/api/v1/partners', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('partnerStatus').textContent = JSON.stringify(data);
}

async function engage() {
    const pid = document.getElementById('e_pid').value;
    const payload = {
        campaign_id: parseInt(document.getElementById('e_cid').value, 10) || 0,
        event_id: parseInt(document.getElementById('e_eid').value, 10) || null,
        engagement_type: document.getElementById('e_type').value,
        notes: document.getElementById('e_notes').value,
        webhook_url: document.getElementById('e_webhook').value || null
    };
    const res = await fetch('/api/v1/partners/' + pid + '/engage', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('engageStatus').textContent = JSON.stringify(data);
}

async function loadAssignments() {
    const pid = document.getElementById('a_pid').value;
    const res = await fetch('/api/v1/partners/' + pid + '/assignments');
    const data = await res.json();
    const tbody = document.getElementById('assignTable');
    tbody.innerHTML = '';
    (data.data || []).forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.campaign_title || ''}</td><td>${r.status || ''}</td><td>${r.event_name || ''}</td><td>${r.starts_at || ''}</td>`;
        tbody.appendChild(tr);
    });
}
</script>



