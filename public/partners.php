<?php
$pageTitle = 'Partner Management';
include __DIR__ . '/../header/includes/header.php';
?>

<style>
    .partners-page {
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

<main class="partners-page">
    <div class="page-header">
        <h1>Partner Management</h1>
        <p>Manage partnerships with schools, NGOs, and other organizations</p>
    </div>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Add Partner</h2>
        <form id="partnerForm" class="form-grid">
            <div class="form-field">
                <label>Organization Name *</label>
                <input id="p_name" type="text" placeholder="Red Cross Quezon City" required>
            </div>
            <div class="form-field">
                <label>Organization Type</label>
                <select id="p_type">
                    <option value="school">School</option>
                    <option value="ngo">NGO</option>
                    <option value="government">Government</option>
                    <option value="private">Private</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-field">
                <label>Contact Person</label>
                <input id="p_person" type="text" placeholder="John Doe">
            </div>
            <div class="form-field">
                <label>Email</label>
                <input id="p_email" type="email" placeholder="contact@example.com">
            </div>
            <div class="form-field">
                <label>Phone</label>
                <input id="p_phone" type="text" placeholder="+63-2-1234-5678">
            </div>
        </form>
        <button type="submit" form="partnerForm" class="btn btn-primary" style="margin-top:16px;" onclick="addPartner(event)">Save Partner</button>
        <div class="status" id="partnerStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card" style="margin-bottom:24px;">
        <h2 class="section-title">Engage Partner</h2>
        <form id="engageForm" class="form-grid">
            <div class="form-field">
                <label>Partner ID *</label>
                <input id="e_pid" type="number" required>
            </div>
            <div class="form-field">
                <label>Campaign ID *</label>
                <input id="e_cid" type="number" required>
            </div>
            <div class="form-field">
                <label>Engagement Type</label>
                <input id="e_type" type="text" value="collaboration" placeholder="collaboration, co-host, resource_sharing">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Notes</label>
                <textarea id="e_notes" rows="3" placeholder="Engagement details..."></textarea>
            </div>
        </form>
        <button type="submit" form="engageForm" class="btn btn-primary" style="margin-top:16px;" onclick="engage(event)">Engage Partner</button>
        <div class="status" id="engageStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card">
        <h2 class="section-title">Partner Assignments</h2>
        <form id="assignForm" class="form-grid" style="grid-template-columns: 200px auto;">
            <div class="form-field">
                <label>Partner ID</label>
                <input id="a_pid" type="number" placeholder="1">
            </div>
            <div class="form-field" style="align-items:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="loadAssignments()">Load Assignments</button>
            </div>
        </form>
        <div style="overflow-x:auto; margin-top:16px;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Campaign</th>
                        <th>Status</th>
                        <th>Event</th>
                        <th>Starts</th>
                    </tr>
                </thead>
                <tbody id="assignTable">
                    <tr><td colspan="4" style="text-align:center; padding:24px; color:#64748b;">Enter Partner ID and click Load Assignments</td></tr>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

async function addPartner(e) {
    e.preventDefault();
    const statusEl = document.getElementById('partnerStatus');
    statusEl.textContent = 'Saving...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        name: document.getElementById('p_name').value.trim(),
        organization_type: document.getElementById('p_type').value,
        contact_person: document.getElementById('p_person').value.trim() || null,
        contact_email: document.getElementById('p_email').value.trim() || null,
        contact_phone: document.getElementById('p_phone').value.trim() || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Partner added successfully! ID: ' + (data.id || 'N/A');
            statusEl.style.color = '#166534';
            document.getElementById('partnerForm').reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function engage(e) {
    e.preventDefault();
    const statusEl = document.getElementById('engageStatus');
    statusEl.textContent = 'Processing...';
    statusEl.style.color = '#64748b';
    
    const pid = document.getElementById('e_pid').value;
    const payload = {
        campaign_id: parseInt(document.getElementById('e_cid').value, 10),
        engagement_type: document.getElementById('e_type').value.trim() || 'collaboration',
        notes: document.getElementById('e_notes').value.trim() || null
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + pid + '/engage', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Partner engagement recorded!';
            statusEl.style.color = '#166534';
            document.getElementById('engageForm').reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function loadAssignments() {
    const pid = document.getElementById('a_pid').value;
    if (!pid) {
        alert('Please enter a Partner ID');
        return;
    }
    
    const tbody = document.getElementById('assignTable');
    tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#64748b;">Loading...</td></tr>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/partners/' + pid + '/assignments');
        const data = await res.json();
        tbody.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#64748b;">No assignments found for this partner.</td></tr>';
            return;
        }
        
        data.data.forEach(r => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><strong>${r.campaign_title || '-'}</strong></td>
                <td><span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:4px; font-size:11px;">${r.status || 'active'}</span></td>
                <td>${r.event_name || '-'}</td>
                <td>${r.starts_at || '-'}</td>
            `;
            tbody.appendChild(tr);
        });
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:24px; color:#dc2626;">Error: ' + err.message + '</td></tr>';
    }
}
</script>
