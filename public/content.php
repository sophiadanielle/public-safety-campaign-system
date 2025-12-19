<?php
$pageTitle = 'Content Management';
include __DIR__ . '/../header/includes/header.php';
?>

<style>
    .content-page {
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
    .page-header p {
        color: #64748b;
        margin: 0;
    }
    .form-section {
        margin-bottom: 24px;
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
    .library-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .content-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        transition: all 0.2s;
    }
    .content-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
</style>

<main class="content-page">
    <div class="page-header">
        <h1>Content Management</h1>
        <p>Upload, manage, and organize campaign content materials</p>
    </div>

    <section class="card form-section">
        <h2 class="section-title">Upload Content</h2>
        <form id="uploadForm" class="form-grid">
            <div class="form-field">
                <label>File *</label>
                <input type="file" name="file" required>
            </div>
            <div class="form-field">
                <label>Title *</label>
                <input type="text" name="title" placeholder="Fire Safety Poster" required>
            </div>
            <div class="form-field">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Content description..."></textarea>
            </div>
            <div class="form-field">
                <label>Visibility</label>
                <select name="visibility">
                    <option value="public">Public</option>
                    <option value="internal">Internal</option>
                    <option value="private">Private</option>
                </select>
            </div>
            <div class="form-field">
                <label>Campaign ID (optional)</label>
                <input type="number" name="campaign_id" placeholder="1">
            </div>
            <div class="form-field">
                <label>Tags (comma separated)</label>
                <input type="text" name="tags" placeholder="fire,poster,safety">
            </div>
        </form>
        <button type="submit" form="uploadForm" class="btn btn-primary" style="margin-top:16px;">Upload Content</button>
        <div class="status" id="uploadStatus" style="margin-top:12px;"></div>
    </section>

    <section class="card form-section">
        <h2 class="section-title">Content Library</h2>
        <div class="library-grid" id="library"></div>
    </section>

    <section class="card form-section">
        <h2 class="section-title">Record Content Usage</h2>
        <form id="usageForm" class="form-grid">
            <div class="form-field">
                <label>Content ID *</label>
                <input type="number" name="content_id" required>
            </div>
            <div class="form-field">
                <label>Campaign ID (optional)</label>
                <input type="number" name="campaign_id">
            </div>
            <div class="form-field">
                <label>Event ID (optional)</label>
                <input type="number" name="event_id">
            </div>
            <div class="form-field">
                <label>Tag</label>
                <input type="text" name="tag" placeholder="poster">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Usage Context</label>
                <input type="text" name="usage_context" placeholder="pre-event brief">
            </div>
        </form>
        <button type="submit" form="usageForm" class="btn btn-primary" style="margin-top:16px;">Record Usage</button>
        <div class="status" id="usageStatus" style="margin-top:12px;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const statusEl = document.getElementById('uploadStatus');
    statusEl.textContent = 'Uploading...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/content', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Content uploaded successfully!';
            statusEl.style.color = '#166534';
            e.target.reset();
            loadContent();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Upload failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
});

document.getElementById('usageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const contentId = fd.get('content_id');
    const statusEl = document.getElementById('usageStatus');
    statusEl.textContent = 'Recording...';
    statusEl.style.color = '#64748b';
    
    const payload = {
        campaign_id: parseInt(fd.get('campaign_id'), 10) || null,
        event_id: parseInt(fd.get('event_id'), 10) || null,
        survey_id: parseInt(fd.get('survey_id'), 10) || null,
        tag: fd.get('tag') || null,
        usage_context: fd.get('usage_context') || null,
    };
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '/use', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (res.ok) {
            statusEl.textContent = '✓ Usage recorded successfully!';
            statusEl.style.color = '#166534';
            e.target.reset();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
});

async function loadContent() {
    const container = document.getElementById('library');
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading content...</p>';
    
    try {
        const apiUrl = apiBase + '/api/v1/content';
        console.log('Fetching from:', apiUrl);
        console.log('Token present:', !!token);
        
        const res = await fetch(apiUrl, { 
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            } 
        });
        
        console.log('Response status:', res.status);
        console.log('Response headers:', Object.fromEntries(res.headers.entries()));
        
        // Check if response is actually JSON
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            const text = await res.text();
            console.error('Non-JSON response received. Status:', res.status);
            console.error('Response preview:', text.substring(0, 500));
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: Server returned non-JSON response (Status: ' + res.status + ').<br><small>Check browser console (F12) for details.</small></p>';
            return;
        }
        
        const data = await res.json();
        container.innerHTML = '';
        
        if (!res.ok) {
            container.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: ${data.error || 'Failed to load content'}</p>`;
            return;
        }
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">No content items yet. Upload your first content!</p>';
            return;
        }
        
        data.data.forEach(item => {
            const div = document.createElement('div');
            div.className = 'content-card';
            const filePath = item.file_path || '';
            const link = filePath ? `<a href="${filePath.startsWith('http') ? filePath : '/' + filePath}" target="_blank" class="btn btn-secondary" style="margin-top:8px; display:inline-block;">View File</a>` : '';
            div.innerHTML = `
                <div style="font-size:12px;color:#64748b; margin-bottom:8px;">ID: ${item.id}</div>
                <strong style="display:block; margin-bottom:8px; color:#0f172a;">${(item.title || 'Untitled').substring(0, 50)}</strong>
                <div style="font-size:12px; color:#475569; margin-bottom:4px;">
                    <span class="badge" style="background:#e0f2fe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-size:11px;">${item.content_type || 'text'}</span>
                    <span class="badge" style="background:#f1f5f9; color:#475569; padding:2px 8px; border-radius:4px; font-size:11px; margin-left:4px;">${item.visibility || 'public'}</span>
                </div>
                ${link}
            `;
            container.appendChild(div);
        });
    } catch (err) {
        console.error('Error loading content:', err);
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load content: ' + err.message + '<br><small>Check browser console for details</small></p>';
    }
}

loadContent();
</script>
