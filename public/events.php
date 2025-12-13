<?php
$pageTitle = 'Events';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="page-content">
    <h1>Events</h1>
    <section class="card" style="margin-top:12px;">
        <h2>Create Event</h2>
        <label>Name <input id="name" type="text" placeholder="Fire Safety Seminar"></label>
        <label>Campaign ID <input id="campaign_id" type="number" placeholder="1"></label>
        <label>Location <input id="location" type="text" placeholder="Barangay Hall"></label>
        <label>Starts At (YYYY-MM-DD HH:MM:SS) <input id="starts_at" type="text" placeholder="2025-12-15 09:00:00"></label>
        <label>Ends At (optional) <input id="ends_at" type="text"></label>
        <label>Logistics JSON (optional) <textarea id="logistics" rows="2" placeholder='{ "venue":"Hall", "equipment":["projector"] }'></textarea></label>
        <label>Materials JSON (optional) <textarea id="materials" rows="2" placeholder='{ "handouts":100, "posters":10 }'></textarea></label>
        <button class="btn btn-primary" style="margin-top:10px;" onclick="createEvent()">Create</button>
        <div class="status" id="createStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <h2 style="margin:0;">Events</h2>
            <button class="btn btn-secondary" onclick="loadEvents()">Refresh</button>
        </div>
        <table style="width:100%; border-collapse:collapse; margin-top:12px;">
            <thead><tr><th>ID</th><th>Campaign</th><th>Name</th><th>Starts</th><th>Location</th><th>Logistics</th><th>Materials</th></tr></thead>
            <tbody id="eventTable"></tbody>
        </table>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Quick Check-in (simulated)</h2>
        <label>Event ID <input id="checkin_event" type="number"></label>
        <label>Full Name <input id="checkin_name" type="text"></label>
        <label>Contact <input id="checkin_contact" type="text"></label>
        <button class="btn btn-primary" style="margin-top:10px;" onclick="checkIn()">Check In</button>
        <div class="status" id="checkinStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
const token = localStorage.getItem('jwtToken') || '';
async function createEvent() {
    const payload = {
        name: document.getElementById('name').value.trim(),
        campaign_id: parseInt(document.getElementById('campaign_id').value, 10) || null,
        location: document.getElementById('location').value.trim(),
        starts_at: document.getElementById('starts_at').value.trim(),
        ends_at: document.getElementById('ends_at').value.trim() || null,
        logistics: parseOrNull('logistics'),
        materials: parseOrNull('materials')
    };
    const res = await fetch('/api/v1/events', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('createStatus').textContent = JSON.stringify(data);
    loadEvents();
}

async function loadEvents() {
    const res = await fetch('/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
    const data = await res.json();
    const tbody = document.getElementById('eventTable');
    tbody.innerHTML = '';
    (data.data || []).forEach(e => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${e.id}</td><td>${e.campaign_id || ''}</td><td>${e.name}</td><td>${e.starts_at}</td><td>${e.location || ''}</td>
            <td><code style="font-size:12px;">${safeJson(e.logistics_json)}</code></td>
            <td><code style="font-size:12px;">${safeJson(e.materials_json)}</code></td>`;
        tbody.appendChild(tr);
    });
}

async function checkIn() {
    const eventId = document.getElementById('checkin_event').value;
    const payload = {
        full_name: document.getElementById('checkin_name').value.trim(),
        contact: document.getElementById('checkin_contact').value.trim(),
        channel: 'other'
    };
    const res = await fetch('/api/v1/events/' + eventId + '/attendance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('checkinStatus').textContent = JSON.stringify(data);
}

function parseOrNull(id) {
    const raw = document.getElementById(id).value;
    if (!raw) return null;
    try { return JSON.parse(raw); } catch (e) { return null; }
}

function safeJson(val) {
    if (!val) return '';
    if (typeof val === 'string') return val;
    try { return JSON.stringify(val); } catch (e) { return ''; }
}

loadEvents();
</script>



