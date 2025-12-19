<?php
$pageTitle = 'Events & Seminars';
include __DIR__ . '/../header/includes/header.php';
?>

<style>
    .events-page {
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

<main class="events-page">
    <div class="page-header">
        <h1>Events & Seminars</h1>
        <p>Schedule and manage campaign events, seminars, and workshops</p>
    </div>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Create Event</h2>
        <form id="createForm" class="form-grid">
            <div class="form-field">
                <label>Name *</label>
                <input id="name" type="text" placeholder="Fire Safety Seminar" required>
            </div>
            <div class="form-field">
                <label>Campaign ID</label>
                <input id="campaign_id" type="number" placeholder="1">
            </div>
            <div class="form-field">
                <label>Event Type</label>
                <select id="event_type">
                    <option value="seminar">Seminar</option>
                    <option value="drill">Drill</option>
                    <option value="workshop">Workshop</option>
                    <option value="meeting">Meeting</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-field">
                <label>Location</label>
                <input id="location" type="text" placeholder="Barangay Hall">
            </div>
            <div class="form-field">
                <label>Event Date</label>
                <input id="event_date" type="date">
            </div>
            <div class="form-field">
                <label>Event Time</label>
                <input id="event_time" type="time">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Description</label>
                <textarea id="description" rows="3" placeholder="Event description..."></textarea>
            </div>
            <div class="form-field">
                <label>Facilitators (JSON array)</label>
                <textarea id="facilitators" rows="2" placeholder='["John Doe", "Jane Smith"]'></textarea>
            </div>
        </form>
        <button class="btn btn-primary" style="margin-top:16px;" onclick="createEvent()">Create Event</button>
        <div class="status" id="createStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
            <h2 class="section-title" style="margin:0;">Events List</h2>
            <button class="btn btn-secondary" onclick="loadEvents()">Refresh</button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Campaign</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="eventTable">
                    <tr><td colspan="8" style="text-align:center; padding:24px; color:#64748b;">Loading events...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2 class="section-title">Quick Check-in</h2>
        <form id="checkinForm" class="form-grid">
            <div class="form-field">
                <label>Event ID *</label>
                <input id="checkin_event" type="number" required>
            </div>
            <div class="form-field">
                <label>Full Name *</label>
                <input id="checkin_name" type="text" required>
            </div>
            <div class="form-field">
                <label>Contact</label>
                <input id="checkin_contact" type="text" placeholder="Email or phone">
            </div>
        </form>
        <button type="submit" form="checkinForm" class="btn btn-primary" style="margin-top:16px;" onclick="checkIn(event)">Check In</button>
        <div class="status" id="checkinStatus" style="margin-top:12px;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

async function createEvent() {
    const statusEl = document.getElementById('createStatus');
    statusEl.textContent = 'Creating...';
    statusEl.style.color = '#64748b';
    
    let facilitators = [];
    try {
        const facText = document.getElementById('facilitators').value.trim();
        if (facText) {
            facilitators = JSON.parse(facText);
        }
    } catch (e) {
        statusEl.textContent = '✗ Invalid JSON in Facilitators field';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    const eventDate = document.getElementById('event_date').value;
    const eventTime = document.getElementById('event_time').value;
    const startsAt = eventDate && eventTime ? `${eventDate} ${eventTime}:00` : null;
    
    const payload = {
        name: document.getElementById('name').value.trim(),
        campaign_id: parseInt(document.getElementById('campaign_id').value, 10) || null,
        location: document.getElementById('location').value.trim() || null,
        event_type: document.getElementById('event_type').value,
        description: document.getElementById('description').value.trim() || null,
        event_date: eventDate || null,
        event_time: eventTime || null,
        starts_at: startsAt,
        facilitators: facilitators.length > 0 ? facilitators : null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Event created successfully! ID: ' + (data.id || 'N/A');
            statusEl.style.color = '#166534';
            document.getElementById('createForm').reset();
            loadEvents();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function loadEvents() {
    const tbody = document.getElementById('eventTable');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
        const data = await res.json();
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#64748b;">No events found. Create your first event!</td></tr>';
            return;
        }
        
        data.data.forEach(e => {
            const tr = document.createElement('tr');
            const date = e.event_date || e.starts_at ? (e.event_date || e.starts_at.split(' ')[0]) : '-';
            const time = e.event_time || (e.starts_at ? e.starts_at.split(' ')[1] : '-');
            tr.innerHTML = `
                <td>${e.id}</td>
                <td><strong>${e.name}</strong></td>
                <td><span style="background:#e0f2fe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-size:11px;">${e.event_type || 'seminar'}</span></td>
                <td>${date}</td>
                <td>${time}</td>
                <td>${e.location || '-'}</td>
                <td>${e.campaign_id || '-'}</td>
                <td><span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:4px; font-size:11px;">${e.status || 'scheduled'}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:24px; color:#dc2626;">Error loading events: ' + err.message + '</td></tr>';
    }
}

async function checkIn(e) {
    e.preventDefault();
    const statusEl = document.getElementById('checkinStatus');
    statusEl.textContent = 'Processing...';
    statusEl.style.color = '#64748b';
    
    const eventId = document.getElementById('checkin_event').value;
    const payload = {
        full_name: document.getElementById('checkin_name').value.trim(),
        contact: document.getElementById('checkin_contact').value.trim() || null,
        channel: 'other'
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/events/' + eventId + '/attendance', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Check-in successful!';
            statusEl.style.color = '#166534';
            document.getElementById('checkinForm').reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Check-in failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

loadEvents();
</script>
