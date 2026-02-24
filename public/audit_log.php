<?php
$pageTitle = 'Audit Log';
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <script>
        (function () {
            const basePath = '<?php echo $basePath; ?>';
            const token = localStorage.getItem('jwtToken');
            if (!token || token.trim() === '') {
                window.location.replace(basePath + '/login.php');
            }
        })();
    </script>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }
        
        .main-content-wrapper {
            margin-left: 280px;
            margin-top: 70px;
            min-height: calc(100vh - 70px);
            transition: margin-left 0.3s ease;
        }
        
        @media (max-width: 768px) {
            .main-content-wrapper {
                margin-left: 0 !important;
            }
        }
        
        .audit-page {
            width: 100%;
            margin: 0;
            padding: 28px;
            box-sizing: border-box;
            max-width: 1400px;
        }
        
        .page-header {
            margin-bottom: 28px;
        }
        
        .page-header h1 {
            font-size: 28px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .page-header h1::before {
            content: '';
            width: 4px;
            height: 28px;
            background: linear-gradient(180deg, #4c8a89 0%, #3d7170 100%);
            border-radius: 2px;
        }
        
        .page-header p {
            margin: 8px 0 0 16px;
            color: #64748b;
            font-size: 14px;
        }
        
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 24px;
            padding: 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .filter-bar select, .filter-bar input {
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
            min-width: 160px;
            transition: all 0.2s;
        }
        
        .filter-bar select:focus, .filter-bar input:focus {
            outline: none;
            border-color: #4c8a89;
            box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
        }
        
        .filter-bar .search-input {
            flex: 1;
            min-width: 250px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .stat-icon.campaigns { background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%); color: #1e40af; }
        .stat-icon.events { background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); color: #166534; }
        .stat-icon.surveys { background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e; }
        .stat-icon.content { background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%); color: #7c3aed; }
        
        .stat-info h3 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            color: #0f172a;
        }
        
        .stat-info p {
            margin: 4px 0 0 0;
            font-size: 13px;
            color: #64748b;
        }
        
        .audit-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .audit-card h2 {
            margin: 0 0 20px 0;
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .audit-card h2 i {
            color: #4c8a89;
        }
        
        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .audit-table th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .audit-table td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
        }
        
        .audit-table tr:hover {
            background: #f8fafc;
        }
        
        .audit-table tr:last-child td {
            border-bottom: none;
        }
        
        .action-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .action-badge.created { background: #dcfce7; color: #166534; }
        .action-badge.updated { background: #dbeafe; color: #1e40af; }
        .action-badge.deleted { background: #fee2e2; color: #991b1b; }
        .action-badge.approved { background: #d1fae5; color: #065f46; }
        .action-badge.submitted { background: #fef3c7; color: #92400e; }
        .action-badge.published { background: #e0e7ff; color: #4338ca; }
        .action-badge.archived { background: #f1f5f9; color: #475569; }
        
        .entity-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .entity-badge.campaign { background: #dbeafe; color: #1e40af; }
        .entity-badge.event { background: #dcfce7; color: #166534; }
        .entity-badge.survey { background: #fef3c7; color: #92400e; }
        .entity-badge.content { background: #f3e8ff; color: #7c3aed; }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4c8a89 0%, #3d7170 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .user-details {
            line-height: 1.3;
        }
        
        .user-name {
            font-weight: 600;
            color: #0f172a;
        }
        
        .user-role {
            font-size: 12px;
            color: #64748b;
        }
        
        .timestamp {
            color: #64748b;
            font-size: 13px;
        }
        
        .details-cell {
            max-width: 300px;
        }
        
        .details-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #64748b;
            font-size: 13px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
        }
        
        .pagination button {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            background: white;
            color: #475569;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .pagination button:hover:not(:disabled) {
            background: #4c8a89;
            color: white;
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination .page-info {
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            font-weight: 600;
            color: #475569;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #cbd5e1;
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            margin: 0 0 8px 0;
            color: #475569;
        }
    </style>
</head>
<body class="module-audit" data-module="audit">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <main class="main-content-wrapper">
        <div class="audit-page">
            <div class="page-header">
                <h1>Audit Log</h1>
                <p>Track all user activities and system changes across the platform</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon campaigns"><i class="fas fa-bullhorn"></i></div>
                    <div class="stat-info">
                        <h3 id="campaignActions">-</h3>
                        <p>Campaign Actions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon events"><i class="fas fa-calendar"></i></div>
                    <div class="stat-info">
                        <h3 id="eventActions">-</h3>
                        <p>Event Actions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon surveys"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-info">
                        <h3 id="surveyActions">-</h3>
                        <p>Survey Actions</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon content"><i class="fas fa-file-alt"></i></div>
                    <div class="stat-info">
                        <h3 id="contentActions">-</h3>
                        <p>Content Actions</p>
                    </div>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="searchInput" class="search-input" placeholder="Search by user, action, or details..." onkeyup="filterAuditLogs()">
                <select id="entityFilter" onchange="filterAuditLogs()">
                    <option value="">All Entities</option>
                    <option value="campaign">Campaigns</option>
                    <option value="event">Events</option>
                    <option value="survey">Surveys</option>
                    <option value="content">Content</option>
                </select>
                <select id="actionFilter" onchange="filterAuditLogs()">
                    <option value="">All Actions</option>
                    <option value="created">Created</option>
                    <option value="updated">Updated</option>
                    <option value="deleted">Deleted</option>
                    <option value="approved">Approved</option>
                    <option value="published">Published</option>
                    <option value="archived">Archived</option>
                </select>
                <select id="dateFilter" onchange="filterAuditLogs()">
                    <option value="">All Time</option>
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
                <button class="btn btn-secondary" onclick="loadAuditLogs()" style="padding: 12px 20px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <!-- Audit Log Table -->
            <div class="audit-card">
                <h2><i class="fas fa-history"></i> Activity History</h2>
                <div class="table-wrapper" style="overflow-x: auto;">
                    <table class="audit-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody id="auditTableBody">
                            <tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Loading audit logs...</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination" id="pagination" style="display: none;">
                    <button onclick="changePage(-1)" id="prevBtn"><i class="fas fa-chevron-left"></i> Previous</button>
                    <span class="page-info" id="pageInfo">Page 1 of 1</span>
                    <button onclick="changePage(1)" id="nextBtn">Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        const token = localStorage.getItem('jwtToken') || '';
        const basePath = '<?php echo $basePath; ?>';
        const apiBase = '<?php echo $apiPath; ?>';
        
        let allAuditLogs = [];
        let filteredLogs = [];
        let currentPage = 1;
        const logsPerPage = 20;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadAuditLogs();
        });
        
        async function loadAuditLogs() {
            const tbody = document.getElementById('auditTableBody');
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:40px; color:#64748b;"><i class="fas fa-spinner fa-spin"></i> Loading audit logs...</td></tr>';
            
            try {
                // Fetch audit logs from API
                console.log('Fetching audit logs from:', apiBase + '/api/v1/audit-logs');
                const res = await fetch(apiBase + '/api/v1/audit-logs', {
                    headers: { 'Authorization': 'Bearer ' + token }
                });
                
                console.log('Audit logs response status:', res.status);
                
                if (res.ok) {
                    const data = await res.json();
                    console.log('Audit logs data:', data);
                    allAuditLogs = data.data || [];
                    console.log('Total audit logs loaded:', allAuditLogs.length);
                } else {
                    const errorData = await res.json().catch(() => ({}));
                    console.error('Audit logs API error:', res.status, errorData);
                    allAuditLogs = [];
                }
                
                updateStats();
                filterAuditLogs();
            } catch (err) {
                console.error('Failed to load audit logs:', err);
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-clipboard-list"></i><h3>No Audit Logs Available</h3><p>Activity logs will appear here as users interact with the system.</p></div></td></tr>';
            }
        }
        
        function updateStats() {
            const campaignCount = allAuditLogs.filter(l => l.entity_type === 'campaign').length;
            const eventCount = allAuditLogs.filter(l => l.entity_type === 'event').length;
            const surveyCount = allAuditLogs.filter(l => l.entity_type === 'survey').length;
            const contentCount = allAuditLogs.filter(l => l.entity_type === 'content').length;
            
            document.getElementById('campaignActions').textContent = campaignCount;
            document.getElementById('eventActions').textContent = eventCount;
            document.getElementById('surveyActions').textContent = surveyCount;
            document.getElementById('contentActions').textContent = contentCount;
        }
        
        function filterAuditLogs() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const entityFilter = document.getElementById('entityFilter').value;
            const actionFilter = document.getElementById('actionFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            
            const now = new Date();
            const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
            const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
            
            filteredLogs = allAuditLogs.filter(log => {
                // Search filter
                if (search) {
                    const searchStr = `${log.user_name || ''} ${log.action || ''} ${log.entity_type || ''} ${log.details || ''}`.toLowerCase();
                    if (!searchStr.includes(search)) return false;
                }
                
                // Entity filter
                if (entityFilter && log.entity_type !== entityFilter) return false;
                
                // Action filter
                if (actionFilter && !log.action?.toLowerCase().includes(actionFilter)) return false;
                
                // Date filter
                if (dateFilter) {
                    const logDate = new Date(log.created_at);
                    if (dateFilter === 'today' && logDate < today) return false;
                    if (dateFilter === 'week' && logDate < weekAgo) return false;
                    if (dateFilter === 'month' && logDate < monthAgo) return false;
                }
                
                return true;
            });
            
            currentPage = 1;
            renderAuditLogs();
        }
        
        function renderAuditLogs() {
            const tbody = document.getElementById('auditTableBody');
            const pagination = document.getElementById('pagination');
            
            if (filteredLogs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><i class="fas fa-clipboard-list"></i><h3>No Audit Logs Found</h3><p>No activities match your current filters.</p></div></td></tr>';
                pagination.style.display = 'none';
                return;
            }
            
            const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
            const start = (currentPage - 1) * logsPerPage;
            const end = start + logsPerPage;
            const pageLogs = filteredLogs.slice(start, end);
            
            tbody.innerHTML = pageLogs.map(log => {
                const initials = getInitials(log.user_name || 'System');
                const actionClass = getActionClass(log.action);
                const entityClass = getEntityClass(log.entity_type);
                const timestamp = formatTimestamp(log.created_at);
                
                return `
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">${initials}</div>
                                <div class="user-details">
                                    <div class="user-name">${log.user_name || 'System'}</div>
                                    <div class="user-role">${log.user_role || 'N/A'}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="action-badge ${actionClass}">
                                <i class="fas ${getActionIcon(log.action)}"></i>
                                ${capitalizeFirst(log.action || 'Action')}
                            </span>
                        </td>
                        <td>
                            <span class="entity-badge ${entityClass}">${log.entity_type || 'Unknown'}</span>
                            ${log.entity_id ? `<span style="color:#64748b; font-size:12px; margin-left:6px;">#${log.entity_id}</span>` : ''}
                        </td>
                        <td class="details-cell">
                            <div class="details-text">${log.details || log.change_details || '-'}</div>
                        </td>
                        <td class="timestamp">${timestamp}</td>
                    </tr>
                `;
            }).join('');
            
            // Update pagination
            if (totalPages > 1) {
                pagination.style.display = 'flex';
                document.getElementById('pageInfo').textContent = `Page ${currentPage} of ${totalPages}`;
                document.getElementById('prevBtn').disabled = currentPage === 1;
                document.getElementById('nextBtn').disabled = currentPage === totalPages;
            } else {
                pagination.style.display = 'none';
            }
        }
        
        function changePage(delta) {
            const totalPages = Math.ceil(filteredLogs.length / logsPerPage);
            currentPage = Math.max(1, Math.min(totalPages, currentPage + delta));
            renderAuditLogs();
        }
        
        function getInitials(name) {
            return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        }
        
        function getActionClass(action) {
            if (!action) return '';
            const a = action.toLowerCase();
            if (a.includes('create') || a.includes('add')) return 'created';
            if (a.includes('update') || a.includes('edit') || a.includes('change')) return 'updated';
            if (a.includes('delete') || a.includes('remove')) return 'deleted';
            if (a.includes('approve')) return 'approved';
            if (a.includes('submit') || a.includes('response')) return 'submitted';
            if (a.includes('publish')) return 'published';
            if (a.includes('archive')) return 'archived';
            return 'updated';
        }
        
        function getActionIcon(action) {
            if (!action) return 'fa-circle';
            const a = action.toLowerCase();
            if (a.includes('create') || a.includes('add')) return 'fa-plus';
            if (a.includes('update') || a.includes('edit')) return 'fa-edit';
            if (a.includes('delete') || a.includes('remove')) return 'fa-trash';
            if (a.includes('approve')) return 'fa-check';
            if (a.includes('submit')) return 'fa-paper-plane';
            if (a.includes('publish')) return 'fa-globe';
            if (a.includes('archive')) return 'fa-archive';
            return 'fa-circle';
        }
        
        function getEntityClass(entity) {
            if (!entity) return '';
            const e = entity.toLowerCase();
            if (e.includes('campaign')) return 'campaign';
            if (e.includes('event')) return 'event';
            if (e.includes('survey')) return 'survey';
            if (e.includes('content')) return 'content';
            return '';
        }
        
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function formatTimestamp(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const now = new Date();
            const diff = now - date;
            
            // Less than 1 minute
            if (diff < 60000) return 'Just now';
            // Less than 1 hour
            if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
            // Less than 24 hours
            if (diff < 86400000) return Math.floor(diff / 3600000) + ' hours ago';
            // Less than 7 days
            if (diff < 604800000) return Math.floor(diff / 86400000) + ' days ago';
            
            // Format as date
            return date.toLocaleDateString('en-PH', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    </script>
</body>
</html>
