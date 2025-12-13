<?php
$pageTitle = 'Content';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="page-content">
    <h1>Content</h1>
    <section class="card" style="margin-top:12px;">
        <h2>Upload Content</h2>
        <form id="uploadForm">
            <label>File <input type="file" name="file" required></label>
            <label>Title <input type="text" name="title" placeholder="Poster"></label>
            <label>Description <textarea name="description" rows="2"></textarea></label>
            <label>Visibility
                <select name="visibility">
                    <option value="public">public</option>
                    <option value="internal">internal</option>
                    <option value="private">private</option>
                </select>
            </label>
            <label>Campaign ID (optional) <input type="number" name="campaign_id"></label>
            <label>Tags (comma separated) <input type="text" name="tags" placeholder="fire,poster"></label>
            <button type="submit" class="btn btn-primary" style="margin-top:10px;">Upload</button>
        </form>
        <div class="status" id="uploadStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Library</h2>
        <div class="grid" id="library" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:12px; margin-top:12px;"></div>
    </section>

    <section class="card" style="margin-top:16px;">
        <h2>Record Content Usage</h2>
        <form id="usageForm">
            <label>Content ID <input type="number" name="content_id" required></label>
            <label>Campaign ID (optional) <input type="number" name="campaign_id"></label>
            <label>Event ID (optional) <input type="number" name="event_id"></label>
            <label>Survey ID (optional) <input type="number" name="survey_id"></label>
            <label>Tag <input type="text" name="tag" placeholder="poster"></label>
            <label>Usage Context <input type="text" name="usage_context" placeholder="pre-event brief"></label>
            <button type="submit" class="btn btn-primary" style="margin-top:10px;">Record Usage</button>
        </form>
        <div class="status" id="usageStatus" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
const token = localStorage.getItem('jwtToken') || '';

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await fetch('/api/v1/content', {
        method: 'POST',
        headers: { 'Authorization': 'Bearer ' + token },
        body: formData
    });
    const data = await res.json();
    document.getElementById('uploadStatus').textContent = JSON.stringify(data);
    loadContent();
});

document.getElementById('usageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const contentId = fd.get('content_id');
    const payload = {
        campaign_id: parseInt(fd.get('campaign_id'), 10) || null,
        event_id: parseInt(fd.get('event_id'), 10) || null,
        survey_id: parseInt(fd.get('survey_id'), 10) || null,
        tag: fd.get('tag') || null,
        usage_context: fd.get('usage_context') || null,
    };
    const res = await fetch('/api/v1/content/' + contentId + '/use', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    document.getElementById('usageStatus').textContent = JSON.stringify(data);
});

async function loadContent() {
    const res = await fetch('/api/v1/content', { headers: { 'Authorization': 'Bearer ' + token } });
    const data = await res.json();
    const container = document.getElementById('library');
    container.innerHTML = '';
    (data.data || []).forEach(item => {
        const div = document.createElement('div');
        div.className = 'card';
        const link = item.file_path ? `<a href="/${item.file_path}" target="_blank">View</a>` : '';
        div.innerHTML = `<div style="font-size:12px;color:#6b7280;">ID ${item.id}</div>
          <strong>${item.title || 'Untitled'}</strong><br>
          ${item.content_type || ''} | ${item.visibility || ''}<br>
          ${link}`;
        container.appendChild(div);
    });
}

loadContent();
</script>



