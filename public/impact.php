<?php
$pageTitle = 'Impact';
include __DIR__ . '/../header/includes/header.php';
?>

<main class="container mx-auto py-10">
    <h1>Impact Dashboard</h1>
    <section class="card" style="margin-top:12px;">
        <label>Campaign ID <input id="campaign_id" type="number" value="1"></label>
        <button class="btn btn-primary" style="margin-top:8px;" onclick="loadImpact()">Load</button>
        <div class="cards" id="cards" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap:12px; margin-top:12px;"></div>
        <canvas id="chart" height="140" style="margin-top:20px;"></canvas>
        <div class="status" id="status" style="margin-top:10px; white-space:pre-wrap;"></div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
const token = localStorage.getItem('jwtToken') || '';
let chart;
async function loadImpact() {
    const cid = document.getElementById('campaign_id').value;
    const res = await fetch('/api/v1/campaigns/' + cid + '/impact', { headers: { 'Authorization': 'Bearer ' + token } });
    const data = await res.json();
    if (!data.data) { document.getElementById('status').textContent = JSON.stringify(data); return; }
    const m = data.data;
    renderCards(m);
    renderChart(m);
    document.getElementById('status').textContent = '';
}
function renderCards(m) {
    const c = document.getElementById('cards');
    c.innerHTML = '';
    Object.entries(m).forEach(([k,v]) => {
        const div = document.createElement('div');
        div.className='card';
        div.style.padding='12px';
        div.style.background='#f8fafc';
        div.style.border='1px solid #e2e8f0';
        div.style.borderRadius='10px';
        div.innerHTML = `<div style="font-size:12px;color:#475569;">${k}</div><div style="font-size:20px;font-weight:800;">${v}</div>`;
        c.appendChild(div);
    });
}
function renderChart(m) {
    const ctx = document.getElementById('chart');
    const labels = ['Reach','Attendance','Survey Responses'];
    const vals = [m.reach || 0, m.attendance_count || 0, m.survey_responses || 0];
    if (chart) chart.destroy();
    chart = new Chart(ctx, {
        type: 'bar',
        data: { labels, datasets: [{ label:'Counts', data: vals, backgroundColor:'#2563eb' }]},
        options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
}
loadImpact();
</script>



