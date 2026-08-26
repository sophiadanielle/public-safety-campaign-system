<?php
$pageTitle = 'Content Repository';
require_once __DIR__ . '/../header/includes/path_helper.php';

// RBAC: Block Viewer role from accessing operational pages (contains forms/workflows)
// Wrapped in try-catch to prevent 502 errors
$isViewer = false;
$currentUserRole = null;
try {
    @require_once __DIR__ . '/../sidebar/includes/block_viewer_access.php';
} catch (\Throwable $e) {
    error_log('content.php: block_viewer_access failed: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Public Safety Campaign</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/content.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/sidebar.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/admin-header.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="<?php echo htmlspecialchars($publicPath . '/js/custom-modals.js'); ?>"></script>
    <script src="<?php echo htmlspecialchars($publicPath . '/js/modal-replacer.js'); ?>"></script>
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
        
        // RBAC FIX: Set role cookie IMMEDIATELY in <head> BEFORE sidebar renders
        // This ensures PHP can read the cookie when sidebar is included
        (function() {
            try {
                const token = localStorage.getItem('jwtToken');
                if (token) {
                    const parts = token.split('.');
                    if (parts.length === 3) {
                        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                        const roleId = payload.role_id || payload.rid;
                        if (roleId && typeof roleId === 'number') {
                            const expires = new Date();
                            expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000));
                            document.cookie = 'user_role_id=' + roleId + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
                            console.log('RBAC: Set user_role_id cookie in <head> =', roleId);
                        }
                    }
                }
            } catch (e) {
                console.error('RBAC: Failed to set role cookie in <head>:', e);
            }
        })();
    </script>
</head>
<body class="module-content" data-module="content">
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/sidebar.php'; 
    } catch (\Throwable $e) {
        error_log('content.php: sidebar.php failed: ' . $e->getMessage());
    }
    ?>
    <?php 
    try {
        include __DIR__ . '/../sidebar/includes/admin-header.php'; 
    } catch (\Throwable $e) {
        error_log('content.php: admin-header.php failed: ' . $e->getMessage());
    }
    ?>
    
    <main class="main-content-wrapper">
<style>
                html, body {
            margin: 13px;
            padding: 0;
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
    
    .content-page {
        width: 100%;
        margin: 0;
        padding: 24px;
        box-sizing: border-box;
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
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #e2e8f0;
    }
    .card {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 24px;
    }
    .section-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 32px 0;
        border: none;
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
        box-sizing: border-box;
    }
    .form-field input:focus,
    .form-field textarea:focus,
    .form-field select:focus {
        outline: none;
        border-color: #4c8a89;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1);
    }
    .form-field small {
        display: block;
        margin-top: 6px;
        color: #94a3b8;
        font-size: 12px;
        line-height: 1.4;
    }
    
    /* Search & Filter Section */
    .filter-section {
        background: #f8fafc;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 24px;
    }
    .filter-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }
    .filter-field {
        display: flex;
        flex-direction: column;
    }
    .filter-field label {
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .filter-field input,
    .filter-field select {
        padding: 8px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
    }
    .filter-checkbox {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
    }
    .filter-checkbox input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    .filter-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    
    /* Content Library */
    .library-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-top: 24px;
    }
    .content-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .content-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        transform: translateY(-4px);
        border-color: #4c8a89;
    }
    .content-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 16px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
    }
    .content-card-id {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 500;
        letter-spacing: 0.3px;
    }
    .content-status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .status-draft { background: #f1f5f9; color: #475569; }
    .status-pending-review { background: #fef3c7; color: #92400e; }
    .status-pending_review { background: #fef3c7; color: #92400e; }
    .status-pending { background: #fef3c7; color: #92400e; }
    .status-under-review { background: #fef3c7; color: #92400e; }
    .status-under_review { background: #fef3c7; color: #92400e; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-archived { background: #f1f5f9; color: #64748b; }
    .content-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 12px 0;
        line-height: 1.4;
    }
    .content-card-description {
        font-size: 14px;
        color: #64748b;
        line-height: 1.6;
        margin-bottom: 16px;
        flex: 1;
    }
    .content-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 500;
        gap: 4px;
    }
    .badge-type { background: #e0f2fe; color: #1d4ed8; }
    .badge-visibility { background: #f1f5f9; color: #475569; }
    .badge-hazard { background: #fee2e2; color: #991b1b; }
    .content-card-info {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .content-card-info i {
        width: 16px;
        color: #94a3b8;
    }
    .content-card-info strong {
        color: #475569;
        margin-right: 4px;
    }
    .content-card-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
        flex-wrap: wrap;
    }
    .content-card-actions .btn,
    .content-card-actions a.btn {
        flex: 1 1 0% !important;
        min-width: 80px !important;
        max-width: 100%;
        padding: 10px 12px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        box-sizing: border-box;
        text-align: center;
        white-space: nowrap;
        overflow: visible;
    }
    .content-card-actions .btn span,
    .content-card-actions a.btn span {
        display: inline-block;
        overflow: visible;
        white-space: nowrap;
    }
    .content-card-actions .btn i {
        font-size: 14px;
    }
    .btn-icon-only {
        min-width: auto;
        width: 40px;
        padding: 10px;
    }
    
    /* Badges */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 600;
    }
    .badge-type { background: #e0f2fe; color: #1d4ed8; }
    .badge-visibility { background: #f1f5f9; color: #475569; }
    .badge-hazard { background: #fee2e2; color: #991b1b; }
    
    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 8px;
        margin-top: 32px;
        padding: 20px;
        background: #f8fafc;
        border-radius: 12px;
    }
    .pagination button {
        padding: 10px 16px;
        border: 2px solid #e2e8f0;
        background: #fff;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .pagination button:hover:not(:disabled) {
        border-color: #4c8a89;
        background: #4c8a89;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(76, 138, 137, 0.2);
    }
    .pagination button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        background: #f1f5f9;
    }
    .pagination-info {
        color: #475569;
        font-size: 14px;
        font-weight: 600;
        margin: 0 12px;
        padding: 8px 16px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    /* Media Gallery */
    .gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 16px;
        margin-top: 20px;
    }
    .gallery-item {
        position: relative;
        aspect-ratio: 1;
        border-radius: 12px;
        overflow: hidden;
        border: 2px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.2s;
    }
    .gallery-item:hover {
        transform: scale(1.05);
        border-color: #4c8a89;
        box-shadow: 0 8px 16px rgba(0,0,0,0.15);
    }
    .gallery-item img,
    .gallery-item video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .gallery-item-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        padding: 12px;
        color: white;
        font-size: 12px;
    }
    
    /* Enhanced Multi-Select Component (same as Campaigns module) */
    .multi-select-container {
        position: relative;
        width: 100%;
    }
    
    .multi-select-container .multi-select-dropdown {
        width: 100%;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 8px 12px;
        font-size: 14px;
        transition: all 0.2s;
        background: #fafbfc;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        overflow-y: auto;
        overflow-x: hidden;
        position: relative;
        box-sizing: border-box;
        height: auto;
        min-height: 0;
    }
    
    .multi-select-container .multi-select-dropdown:focus {
        outline: none;
        border-color: #4c8a89;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.1);
    }
    
    .multi-select-container .multi-select-dropdown:hover {
        border-color: #cbd5e1;
    }
    
    .multi-select-container .multi-select-dropdown option {
        padding: 10px 12px;
        cursor: pointer;
        line-height: 1.5;
    }
    
    .multi-select-container .multi-select-dropdown option:checked {
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        color: #0f172a;
        font-weight: 600;
    }
    
    .multi-select-container .multi-select-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;
        width: 100%;
        min-height: 0;
    }
    
    .multi-select-container .multi-select-tag {
        background: linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%);
        color: #0c4a6e;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid #bae6fd;
        transition: all 0.2s;
    }
    
    .multi-select-container .multi-select-tag:hover {
        background: linear-gradient(135deg, #bae6fd 0%, #93c5fd 100%);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .multi-select-container .multi-select-tag-remove {
        cursor: pointer;
        font-weight: bold;
        font-size: 16px;
        line-height: 1;
        color: #1e40af;
        padding: 0 2px;
        border-radius: 3px;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 18px;
        height: 18px;
    }
    
    .multi-select-container .multi-select-tag-remove:hover {
        background: rgba(30, 64, 175, 0.1);
        color: #1e3a8a;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar {
        width: 8px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    
    .multi-select-container .multi-select-dropdown::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>

<div class="content-page">
    <div class="page-header">
        <h1>Content Repository</h1>
        <p>Internal LGU system for managing, validating, approving, organizing, and reusing public safety campaign materials</p>
    </div>

    <section id="announcements-management" class="card" style="margin-bottom:24px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px;">
            <div>
                <h2 class="section-title" style="margin:0;">Announcements</h2>
                <p style="margin:6px 0 0; color:#64748b; font-size:14px;">Announcements shown on the public landing page during their effectivity period.</p>
            </div>
            <button type="button" class="btn btn-primary" onclick="openAnnouncementModal()">
                <i class="fas fa-bullhorn"></i> Add Announcement
            </button>
        </div>
        <div style="overflow-x:auto;">
            <table class="data-table" style="width:100%;">
                <thead>
                    <tr>
                        <th>Announcement Title</th>
                        <th>Announcement Content</th>
                        <th>Effectivity Date</th>
                        <th>Status</th>
                        <th style="width:150px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="announcementTableBody">
                    <tr><td colspan="5" style="text-align:center; color:#64748b; padding:24px;">Loading announcements...</td></tr>
                </tbody>
            </table>
        </div>
    </section>

    <div id="announcementModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.72); z-index:1000000; align-items:center; justify-content:center; padding:20px;">
        <div style="background:#fff; width:min(680px,96vw); border-radius:16px; box-shadow:0 24px 70px rgba(0,0,0,.28); overflow:hidden;">
            <div style="padding:20px 24px; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                <h3 id="announcementModalTitle" style="margin:0; color:#0f172a;">Add Announcement</h3>
                <button type="button" onclick="closeAnnouncementModal()" style="border:0; background:none; font-size:26px; cursor:pointer; color:#64748b;">&times;</button>
            </div>
            <div style="padding:24px;">
                <input type="hidden" id="announcement_id">
                <div class="form-field" style="margin-bottom:14px;">
                    <label>Announcement Title *</label>
                    <input type="text" id="announcement_title" maxlength="255" required placeholder="Enter announcement title">
                </div>
                <div class="form-field" style="margin-bottom:14px;">
                    <label>Announcement Content *</label>
                    <textarea id="announcement_content" rows="5" required placeholder="Enter announcement details"></textarea>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                    <div class="form-field">
                        <label>Effectivity From *</label>
                        <input type="date" id="announcement_from" required>
                    </div>
                    <div class="form-field">
                        <label>Effectivity To *</label>
                        <input type="date" id="announcement_to" required>
                    </div>
                </div>
                <div class="form-field" id="announcementStatusField" style="margin-top:14px; display:none;">
                    <label>Status</label>
                    <select id="announcement_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <small style="color:#64748b;">New announcements are automatically Active. Status can be changed when editing.</small>
                </div>
                <div id="announcementStatusMessage" style="margin-top:12px; font-size:13px;"></div>
            </div>
            <div style="padding:16px 24px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" class="btn btn-secondary" onclick="closeAnnouncementModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAnnouncement()"><i class="fas fa-save"></i> Save Announcement</button>
            </div>
        </div>
    </div>

    <!-- Upload Campaign Material Modal -->
    <div id="uploadContentModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 999999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 12px; max-width: 800px; width: 90%; max-height: 90vh; overflow-y: auto; padding: 24px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a;" id="uploadContentModalTitle"><i class="fas fa-upload"></i> Upload Campaign Material</h3>
                <button onclick="closeUploadContentModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
            </div>
            <p style="color: #64748b; margin: 0 0 16px 0; font-size: 14px;">Upload campaign materials for internal LGU review and approval workflow</p>
            
            <!-- Cloudinary Status Indicator -->
            <div id="cloudinaryStatus" style="margin-bottom: 16px; padding: 10px 14px; border-radius: 8px; font-size: 13px; display: none;">
                <i class="fas fa-cloud"></i> <span id="cloudinaryStatusText">Checking storage...</span>
            </div>
            
        <form id="uploadForm" class="form-grid" enctype="multipart/form-data">
            <div class="form-field">
                <label>File *</label>
                <input type="file" name="file" required accept="image/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Upload campaign material file (poster, video, guideline, etc.) - Max 100MB</small>
            </div>
            <div class="form-field">
                <label>Title *</label>
                <input type="text" name="title" id="contentTitle" list="titleSuggestions" placeholder="Fire Safety Poster" required>
                <datalist id="titleSuggestions">
                    <option value="Fire Safety Tips for Households">
                    <option value="Fire Safety Poster">
                    <option value="Flood Preparedness Checklist">
                    <option value="Flood Safety Guidelines">
                    <option value="Earthquake Safety Video">
                    <option value="Earthquake Preparedness Guide">
                    <option value="Typhoon Preparedness Infographic">
                    <option value="Typhoon Safety Tips">
                    <option value="Health Safety Tips for Senior Citizens">
                    <option value="Health and Safety Guidelines">
                    <option value="Emergency Contact Numbers Quick Reference">
                    <option value="First Aid Basics Video">
                    <option value="Fire Safety for Schools">
                    <option value="Dengue Prevention Infographic">
                    <option value="Road Safety Infographic">
                    <option value="Crime Prevention Tips Poster">
                    <option value="Disaster Preparedness Workshop Guide">
                    <option value="Youth Safety Awareness Video">
                </datalist>
            </div>
            <div class="form-field">
                <label>Content Type *</label>
                <select name="content_type" required>
                    <option value="">Select...</option>
                    <option value="poster">Poster</option>
                    <option value="video">Video</option>
                    <option value="guideline">Guideline</option>
                    <option value="infographic">Infographic</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                </select>
            </div>
            <div class="form-field">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Campaign material description..."></textarea>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Brief description of the campaign material for internal LGU reference</small>
            </div>
            <div class="form-field">
                <label>Hazard Category</label>
                <select name="hazard_category" id="hazardCategory">
                    <option value="">Select...</option>
                    <option value="Fire">Fire</option>
                    <option value="Flood">Flood</option>
                    <option value="Earthquake">Earthquake</option>
                    <option value="Typhoon">Typhoon</option>
                    <option value="Health">Health</option>
                    <option value="Emergency">Emergency</option>
                </select>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Auto-filled from linked campaign, or select manually</small>
            </div>
            <div class="form-field">
                <label>Campaign Topic</label>
                <input type="text" name="campaign_topic" id="campaignTopic" placeholder="Auto-filled from linked campaign" readonly style="background: #f8fafc;">
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Auto-filled from linked campaign title</small>
            </div>
            <div class="form-field">
                <label>Intended Audience</label>
                <select name="intended_audience_segment" id="intendedAudienceSelect" style="width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 12px; background: #fafbfc; font-size: 14px;">
                    <option value="">Select audience...</option>
                    <option value="general public">General Public</option>
                    <option value="households">Households</option>
                    <option value="residential areas">Residential Areas</option>
                    <option value="youth">Youth</option>
                    <option value="teenagers">Teenagers</option>
                    <option value="students">Students</option>
                    <option value="schools">Schools</option>
                    <option value="teachers">Teachers</option>
                    <option value="senior citizens">Senior Citizens</option>
                    <option value="elderly">Elderly</option>
                    <option value="caregivers">Caregivers</option>
                    <option value="families">Families</option>
                    <option value="flood-prone areas">Flood-prone Areas</option>
                    <option value="coastal communities">Coastal Communities</option>
                    <option value="commercial districts">Commercial Districts</option>
                    <option value="workplaces">Workplaces</option>
                    <option value="community volunteers">Community Volunteers</option>
                    <option value="barangay health workers">Barangay Health Workers</option>
                    <option value="building administrators">Building Administrators</option>
                    <option value="trainers">Trainers</option>
                    <option value="facilitators">Facilitators</option>
                    <option value="barangay officials">Barangay Officials</option>
                    <option value="residential buildings">Residential Buildings</option>
                    <option value="commercial buildings">Commercial Buildings</option>
                </select>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Select the primary target audience for this material.</small>
            </div>
            <div class="form-field">
                <label>Linked Campaign *</label>
                <select name="campaign_id" id="linkedCampaign" required onchange="handleCampaignChange()">
                    <option value="">Select Campaign...</option>
                </select>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Select the campaign this material is linked to. This will auto-fill related fields.</small>
            </div>
            <div class="form-field">
                <label>Source</label>
                <div class="multi-select-container">
                    <div class="multi-select-tags" id="source_tags"></div>
                    <select class="multi-select-dropdown" name="source[]" id="sourceSelect" multiple size="3">
                        <option value="Inspection-based">Inspection-based</option>
                        <option value="Training-based">Training-based</option>
                        <option value="Barangay-created">Barangay-created</option>
                    </select>
                </div>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Select multiple sources. Selected items will appear as tags above.</small>
            </div>
            <div class="form-field">
                <label>Visibility</label>
                <div class="multi-select-container">
                    <div class="multi-select-tags" id="visibility_tags"></div>
                    <select class="multi-select-dropdown" name="visibility[]" id="visibilitySelect" multiple size="3">
                        <option value="public">For Official Use</option>
                        <option value="internal">Internal</option>
                        <option value="private">Private</option>
                    </select>
                </div>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Select multiple visibility levels for LGU internal governance. Selected items will appear as tags above.</small>
            </div>
        </form>
        <button type="submit" form="uploadForm" class="btn btn-primary" style="margin-top:16px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-upload"></i> Upload Campaign Material
        </button>
        <div class="status" id="uploadStatus" style="margin-top:12px;"></div>
        </div>
    </div>
    <!-- End Upload Content Modal -->

    <hr class="section-divider">
    
    <!-- Content Library -->
    <section id="content-library" class="card filter-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="section-title" style="margin: 0;">Content Library</h2>
            <div style="display: flex; gap: 8px;">
                <button type="button" onclick="openUploadContentModal()" class="btn btn-primary" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-upload"></i> Upload Campaign Material
                </button>
                <button type="button" onclick="showArchivedContent()" class="btn btn-secondary" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
                <button type="button" onclick="showSearchFilterHelp()" class="btn btn-secondary" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-question-circle"></i> How It Works
                </button>
            </div>
        </div>
        <div class="filter-grid">
            <div class="filter-field">
                <label>Search</label>
                <input type="text" id="filterSearch" placeholder="Search by title or description..." onkeyup="debounceSearch()">
            </div>
            <div class="filter-field">
                <label>Content Type</label>
                <select id="filterContentType" onchange="loadContent()">
                    <option value="">All Types</option>
                    <option value="poster">Poster</option>
                    <option value="video">Video</option>
                    <option value="guideline">Guideline</option>
                    <option value="infographic">Infographic</option>
                    <option value="image">Image</option>
                    <option value="file">File</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Hazard Category</label>
                <select id="filterHazardCategory" onchange="loadContent()">
                    <option value="">All Categories</option>
                    <option value="Fire">Fire</option>
                    <option value="Flood">Flood</option>
                    <option value="Earthquake">Earthquake</option>
                    <option value="Typhoon">Typhoon</option>
                    <option value="Health">Health</option>
                    <option value="Emergency">Emergency</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Intended Audience</label>
                <input type="text" id="filterIntendedAudience" placeholder="e.g., youth, households..." onkeyup="debounceSearch()">
            </div>
            <div class="filter-field">
                <label>Source</label>
                <select id="filterSource" onchange="loadContent()">
                    <option value="">All Sources</option>
                    <option value="Inspection-based">Inspection-based</option>
                    <option value="Training-based">Training-based</option>
                    <option value="Barangay-created">Barangay-created</option>
                </select>
            </div>
            <div class="filter-field">
                <label>Approval Status</label>
                <select id="filterApprovalStatus" onchange="loadContent()">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="pending_review">Pending Review</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="archived">Archived</option>
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <div class="filter-checkbox">
                <input type="checkbox" id="filterOnlyApproved" onchange="loadContent()">
                <label for="filterOnlyApproved" style="margin: 0; font-weight: 500; text-transform: none;">Show only validated content</label>
            </div>
            <button class="btn btn-secondary" onclick="clearFilters()" style="margin-left: auto;">Clear All</button>
        </div>
        
        <div style="margin-top: 24px;">
            <div class="library-grid" id="library"></div>
            <div class="pagination" id="pagination" style="display: none;"></div>
        </div>
    </section>

    <hr class="section-divider">
    
    <!-- Templates -->
    <section id="templates" class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f1f5f9;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fas fa-layer-group" style="color: #4c8a89; font-size: 24px;"></i>
                    Templates
                </h2>
                <p style="color: #64748b; margin: 8px 0 0 0; font-size: 14px;">Browse validated and approved campaign materials that have been cleared for official LGU use and can be reused in new campaigns</p>
            </div>
            <button class="btn btn-secondary" onclick="loadTemplates()" style="padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; font-weight: 600;">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
        <div id="templatesContainer">
            <div class="library-grid" style="margin-top: 0;"></div>
            <div class="pagination" id="templatesPagination" style="display: none;"></div>
        </div>
    </section>

    <hr class="section-divider">
    
    <!-- Media Gallery -->
    <section id="media-gallery" class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Media Gallery</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Visual archive of validated and approved media files (images and videos) for internal LGU reference</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <select id="mediaTypeFilter" onchange="currentMediaGalleryPage = 1; loadMediaGallery();" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                    <option value="all">All Media</option>
                    <option value="image">Images Only</option>
                    <option value="video">Videos Only</option>
                </select>
                <button class="btn btn-secondary" onclick="currentMediaGalleryPage = 1; loadMediaGallery();" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
        <div id="mediaGalleryContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Loading media gallery...</p>
        </div>
        <div class="pagination" id="mediaGalleryPagination" style="display: none;"></div>
    </section>

    <hr class="section-divider">
    
    <!-- Log Content Usage for Evaluation -->
    <section id="record-usage" class="card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Log Content Usage for Evaluation</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Record when and where approved content materials are used in campaigns or events to support impact evaluation and reporting</p>
            </div>
        </div>
        <form id="usageForm" class="form-grid">
            <div class="form-field">
                <label>Content *</label>
                <select name="content_id" id="usageContentSelect" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                    <option value="">Select approved content...</option>
                </select>
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Select the approved content material to log usage</small>
            </div>
            <div class="form-field">
                <label>Campaign (optional)</label>
                <select name="campaign_id" id="usageCampaignSelect" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                    <option value="">Select campaign...</option>
                </select>
            </div>
            <div class="form-field">
                <label>Event (optional)</label>
                <select name="event_id" id="usageEventSelect" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                    <option value="">Select event...</option>
                </select>
            </div>
            <div class="form-field">
                <label>Tag</label>
                <input type="text" name="tag" placeholder="poster" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>Usage Context</label>
                <input type="text" name="usage_context" placeholder="pre-event brief">
                <small style="color: #94a3b8; font-size: 12px; margin-top: 4px; display: block;">Describe where/how the content was used (e.g., "pre-event brief", "community meeting", "social media post")</small>
            </div>
        </form>
        <button type="submit" form="usageForm" class="btn btn-primary" style="margin-top:16px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus-circle"></i> Log Usage
        </button>
        <div class="status" id="usageStatus" style="margin-top:12px;"></div>
    </section>

    <hr class="section-divider">
    
    <!-- Content Usage Records -->
    <section id="usage-history" class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Content Usage Records</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">View historical usage records for content items to support impact evaluation and reporting</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <select id="usageHistoryContentId" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; min-width: 300px; font-size: 14px;">
                    <option value="">All approved content...</option>
                </select>
                <button class="btn btn-secondary" onclick="loadUsageHistory()" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-history"></i> Load History
                </button>
                <button class="btn btn-secondary" onclick="showArchivedUsageRecords()" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-archive"></i> View Archived
                </button>
            </div>
        </div>
        <div id="usageHistoryContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Select a content item from the dropdown above or leave blank to see all usage history</p>
        </div>
    </section>
</div>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';

// Always read the latest JWT token. This helper is used by the Announcements API
// and avoids ReferenceError when the page token changes after login/refresh.
function getToken() {
    return localStorage.getItem('jwtToken') || token || '';
}

const apiBase = '<?php echo $apiPath; ?>';
const publicPath = '<?php echo $publicPath; ?>';

let announcements = [];

function escapeAnnouncementHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
}

async function loadAnnouncements() {
    const tbody = document.getElementById('announcementTableBody');
    if (!tbody) return;
    try {
        const res = await fetch(apiBase + '/api/v1/content/announcements', {
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load announcements');
        announcements = data.data || [];
        if (!announcements.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; color:#64748b; padding:24px;">No announcements yet.</td></tr>';
            return;
        }
        tbody.innerHTML = announcements.map(a => {
            const status = String(a.status || 'active').toLowerCase();
            const badge = status === 'active'
                ? 'background:#dcfce7;color:#166534;'
                : 'background:#f1f5f9;color:#475569;';
            return `<tr>
                <td><strong>${escapeAnnouncementHtml(a.announcement_title)}</strong></td>
                <td style="max-width:440px; white-space:normal;">${escapeAnnouncementHtml(a.announcement_content)}</td>
                <td>${escapeAnnouncementHtml(a.effectivity_from)} <strong>to</strong> ${escapeAnnouncementHtml(a.effectivity_to)}</td>
                <td><span style="${badge}padding:5px 10px;border-radius:999px;font-size:12px;font-weight:700;text-transform:uppercase;">${escapeAnnouncementHtml(status)}</span></td>
                <td>
                    <button class="btn btn-secondary" style="padding:6px 10px;font-size:12px;" onclick="openAnnouncementModal(${Number(a.id)})">Edit</button>
                    <button class="btn btn-danger" style="padding:6px 10px;font-size:12px;background:#dc2626;color:white;" onclick="deleteAnnouncement(${Number(a.id)})">Delete</button>
                </td>
            </tr>`;
        }).join('');
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:#dc2626; padding:24px;">${escapeAnnouncementHtml(err.message)}</td></tr>`;
    }
}

function openAnnouncementModal(id = null) {
    const modal = document.getElementById('announcementModal');
    const record = id ? announcements.find(a => Number(a.id) === Number(id)) : null;
    document.getElementById('announcement_id').value = record ? record.id : '';
    document.getElementById('announcement_title').value = record?.announcement_title || '';
    document.getElementById('announcement_content').value = record?.announcement_content || '';
    document.getElementById('announcement_from').value = record?.effectivity_from || '';
    document.getElementById('announcement_to').value = record?.effectivity_to || '';
    document.getElementById('announcement_status').value = record?.status || 'active';
    document.getElementById('announcementStatusField').style.display = record ? 'block' : 'none';
    document.getElementById('announcementModalTitle').textContent = record ? 'Edit Announcement' : 'Add Announcement';
    document.getElementById('announcementStatusMessage').textContent = record ? '' : 'Status will automatically be Active.';
    modal.style.display = 'flex';
}

function closeAnnouncementModal() {
    const modal = document.getElementById('announcementModal');
    if (modal) modal.style.display = 'none';
}

async function saveAnnouncement() {
    const id = document.getElementById('announcement_id').value;
    const statusEl = document.getElementById('announcementStatusMessage');
    const payload = {
        announcement_title: document.getElementById('announcement_title').value.trim(),
        announcement_content: document.getElementById('announcement_content').value.trim(),
        effectivity_from: document.getElementById('announcement_from').value,
        effectivity_to: document.getElementById('announcement_to').value
    };
    if (id) payload.status = document.getElementById('announcement_status').value;
    if (!payload.announcement_title || !payload.announcement_content || !payload.effectivity_from || !payload.effectivity_to) {
        statusEl.textContent = 'Please complete all required announcement fields.';
        statusEl.style.color = '#dc2626';
        return;
    }
    try {
        const res = await fetch(apiBase + '/api/v1/content/announcements' + (id ? '/' + id : ''), {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getToken()
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to save announcement');
        closeAnnouncementModal();
        await loadAnnouncements();
    } catch (err) {
        statusEl.textContent = err.message;
        statusEl.style.color = '#dc2626';
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('Delete this announcement?')) return;
    try {
        const res = await fetch(apiBase + '/api/v1/content/announcements/' + id, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + getToken() }
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to delete announcement');
        await loadAnnouncements();
    } catch (err) {
        alert(err.message);
    }
}

// Single source of truth for all content data
let contents = [];

let currentPage = 1;
let currentTemplatesPage = 1;
let currentMediaGalleryPage = 1;
let searchTimeout = null;

// Debounce search input
function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadContent();
    }, 500);
}

// Clear all filters
function clearFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterContentType').value = '';
    document.getElementById('filterHazardCategory').value = '';
    document.getElementById('filterIntendedAudience').value = '';
    document.getElementById('filterSource').value = '';
    document.getElementById('filterApprovalStatus').value = '';
    document.getElementById('filterOnlyApproved').checked = false;
    currentPage = 1;
    loadContent();
}

// Build filter query string
function buildFilterQuery() {
    const params = new URLSearchParams();
    
    const search = document.getElementById('filterSearch').value.trim();
    if (search) params.set('q', search);
    
    const contentType = document.getElementById('filterContentType').value;
    if (contentType) params.set('content_type', contentType);
    
    const hazardCategory = document.getElementById('filterHazardCategory').value;
    if (hazardCategory) params.set('hazard_category', hazardCategory);
    
    const intendedAudience = document.getElementById('filterIntendedAudience').value.trim();
    if (intendedAudience) params.set('intended_audience', intendedAudience);
    
    const source = document.getElementById('filterSource').value;
    if (source) params.set('source', source);
    
    const approvalStatus = document.getElementById('filterApprovalStatus').value;
    if (approvalStatus) params.set('approval_status', approvalStatus);
    
    const onlyApproved = document.getElementById('filterOnlyApproved').checked;
    if (onlyApproved) params.set('only_approved', 'true');
    
    params.set('page', currentPage);
    params.set('per_page', 6); // Show 6 items per page for compact layout
    
    return params.toString();
}

// Show Content Help Modal
function showContentHelp() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 20px;">💡 Content Repository - How It Works</h3>
            <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="color: #475569; line-height: 1.8; font-size: 14px;">
            <div style="margin-bottom: 24px; padding: 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #4c8a89;">
                <strong style="color: #065f46; display: block; margin-bottom: 12px; font-size: 16px;">📋 LGU Content Governance Workflow:</strong>
                <ol style="margin: 0; padding-left: 20px; line-height: 2;">
                    <li><strong>Upload Campaign Material</strong> - Barangay Secretary/Information Officer uploads materials</li>
                    <li><strong>Content Saved as Draft</strong> - New content starts as "Draft" status for internal review</li>
                    <li><strong>Submit for Review</strong> - Change status to "Pending Review" for validation and approval</li>
                    <li><strong>Validation & Approval</strong> - BDRRMO validates technical accuracy, Barangay Captain approves for official use</li>
                    <li><strong>Content Available</strong> - Validated and approved content can be used in campaigns</li>
                    <li><strong>Log Usage</strong> - Record when content is used in campaigns/events to support impact evaluation</li>
                </ol>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">📋 Required Fields:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>File</strong> - Upload image, video, PDF, or other campaign material file</li>
                    <li><strong>Title</strong> - Descriptive name for the campaign material</li>
                    <li><strong>Content Type</strong> - Poster, Video, Guideline, Infographic, Image, or File</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">💡 LGU Internal Use Guidelines:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>This system is for internal LGU workflows only, not for public-facing content</li>
                    <li>Fill out Hazard Category and Intended Audience for proper organization</li>
                    <li>Add tags to make content easier to find in the repository</li>
                    <li>Only validated and approved content can be attached to campaigns</li>
                    <li>Use "Approved Reusable Materials" section to reuse validated content</li>
                    <li>"Approved Media Archive" shows visual preview of validated images and videos</li>
                </ul>
            </div>
            
            <div style="background: #fff7ed; padding: 12px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <strong style="color: #92400e;">⚠️ Important Notes:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 13px;">
                    <li>Content starts as "Draft" and must be submitted for review</li>
                    <li>Approval Workflow: Draft → Pending Review → Approved/Rejected</li>
                    <li>Only approved content appears in "Approved Reusable Materials" and can be attached to campaigns</li>
                    <li>Content can be archived (admin only) - archived content is hidden by default</li>
                    <li>File size limit is 5MB</li>
                    <li>Supported formats: PNG, JPEG, GIF, WebP, PDF</li>
                </ul>
            </div>
        </div>
        <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
}

// Show Search & Filter Help Modal
function showSearchFilterHelp() {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 700px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 20px;">💡 Search & Filter - How It Works</h3>
            <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="color: #475569; line-height: 1.8; font-size: 14px;">
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🔍 Search & Filter Features:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Search Field</strong> - Searches title and description (auto-searches as you type)</li>
                    <li><strong>Content Type</strong> - Filter by Poster, Video, Guideline, Infographic, Image, or File</li>
                    <li><strong>Hazard Category</strong> - Filter by Fire, Flood, Earthquake, Typhoon, Health, or Emergency</li>
                    <li><strong>Intended Audience</strong> - Filter by target audience (e.g., youth, households, schools)</li>
                    <li><strong>Source</strong> - Filter by Inspection-based, Training-based, or Barangay-created</li>
                    <li><strong>Approval Status</strong> - Filter by Draft, Pending Review, Approved, Rejected, or Archived</li>
                    <li><strong>Show Only Validated</strong> - Quick checkbox to show only validated and approved content</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px; padding: 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #4c8a89;">
                <strong style="color: #065f46; display: block; margin-bottom: 12px; font-size: 16px;">💡 Usage Tips:</strong>
                <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                    <li>Combine multiple filters for precise results</li>
                    <li>Search automatically runs 500ms after you stop typing</li>
                    <li>Use "Clear All" to reset all filters</li>
                    <li>Content Library shows filtered results below</li>
                    <li>Click "Approve" or "Reject" on pending review items (admin only)</li>
                    <li>Click "Archive" to archive content (admin only)</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🔗 Connections to Other Sections:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Approved Reusable Materials</strong> - Automatically shows only validated and approved content</li>
                    <li><strong>Approved Media Archive</strong> - Shows validated images and videos from filtered results</li>
                    <li><strong>Campaigns</strong> - Only validated and approved content can be attached to campaigns</li>
                </ul>
            </div>
        </div>
        <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
}

// Multi-Select Component (same as Campaigns module)
function initMultiSelectEnhanced(selectId, options = {}) {
    const select = document.getElementById(selectId);
    if (!select) {
        console.warn('MultiSelectEnhanced: Element not found', selectId);
        return;
    }
    
    // Get tags container
    let tagsDiv = null;
    if (selectId === 'intendedAudience') {
        tagsDiv = document.getElementById('intended_audience_tags');
    } else if (selectId === 'sourceSelect') {
        tagsDiv = document.getElementById('source_tags');
    } else if (selectId === 'visibilitySelect') {
        tagsDiv = document.getElementById('visibility_tags');
    }
    
    if (!tagsDiv) {
        console.warn('MultiSelectEnhanced: Tags container not found', selectId);
        return;
    }
    
    // Mark as initialized
    select.dataset.multiSelectInit = 'true';
    console.log('MultiSelectEnhanced initialized for:', selectId);

    const staticOptions = Array.isArray(options.staticOptions) ? options.staticOptions : [];
    
    // Populate options if provided
    staticOptions.forEach(option => {
        const optionEl = document.createElement('option');
        optionEl.value = option;
        optionEl.textContent = option;
        select.appendChild(optionEl);
    });
    
    // Update tags when selection changes
    function updateTags() {
        if (!tagsDiv) return;
        
        const selectedOptions = Array.from(select.selectedOptions);
        tagsDiv.innerHTML = '';
        
        selectedOptions.forEach(option => {
            const tag = document.createElement('div');
            tag.className = 'multi-select-tag';
            tag.innerHTML = `
                <span>${option.textContent}</span>
                <span class="multi-select-tag-remove" data-value="${option.value}">×</span>
            `;
            tag.querySelector('.multi-select-tag-remove').addEventListener('click', (e) => {
                e.stopPropagation();
                option.selected = false;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                updateTags();
            });
            tagsDiv.appendChild(tag);
        });
    }
    
    // Handle multiple selection - allow single click to toggle (not replace)
    select.addEventListener('mousedown', function(e) {
        const option = e.target;
        if (option.tagName === 'OPTION') {
            // If Ctrl/Cmd is held, allow default behavior (toggle)
            if (e.ctrlKey || e.metaKey) {
                return; // Let browser handle it
            }
            // For single click, toggle the selection without deselecting others
            e.preventDefault();
            option.selected = !option.selected;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            updateTags();
        }
    });
    
    // Listen for changes
    select.addEventListener('change', updateTags);
    
    // Initial update
    updateTags();
    
    // Expose getSelectedValues for form submission
    select.getSelectedValues = () => Array.from(select.selectedOptions).map(opt => opt.value);
    
    // Expose setSelectedValues for editing content
    select.setSelectedValues = (values) => {
        if (!Array.isArray(values)) return;
        
        // Clear all selections first
        Array.from(select.options).forEach(opt => {
            opt.selected = false;
        });
        
        // Set selected values
        values.forEach(value => {
            const option = Array.from(select.options).find(opt => opt.value === value);
            if (option) {
                option.selected = true;
            }
        });
        
        select.dispatchEvent(new Event('change', { bubbles: true }));
        updateTags();
    };
    
    // Expose updateTags method
    select.updateTags = updateTags;
}

// Load campaigns for Linked Campaign dropdown
async function loadCampaigns() {
    const campaignSelect = document.getElementById('linkedCampaign');
    if (!campaignSelect) {
        console.warn('linkedCampaign element not found');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (res.ok) {
            const data = await res.json();
            // Handle both {data: [...]} and direct array responses
            let campaigns = [];
            if (Array.isArray(data)) {
                campaigns = data;
            } else if (data.data && Array.isArray(data.data)) {
                campaigns = data.data;
            }
            
            // Clear existing options except the first one
            campaignSelect.innerHTML = '<option value="">Select Campaign...</option>';
            
            campaigns.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `[#${campaign.id}] ${campaign.title || 'Untitled Campaign'}`;
                option.setAttribute('data-category', campaign.category || '');
                option.setAttribute('data-title', campaign.title || '');
                campaignSelect.appendChild(option);
            });
            
            console.log('Loaded', campaigns.length, 'campaigns for linked campaign dropdown');
        } else {
            console.error('Failed to load campaigns:', res.status, res.statusText);
        }
    } catch (err) {
        console.error('Error loading campaigns:', err);
    }
}

// Handle campaign selection change - auto-fill fields
function handleCampaignChange() {
    const campaignSelect = document.getElementById('linkedCampaign');
    const hazardCategorySelect = document.getElementById('hazardCategory');
    const campaignTopicInput = document.getElementById('campaignTopic');
    
    if (!campaignSelect || !campaignSelect.value) {
        // Clear auto-filled fields if no campaign selected
        if (hazardCategorySelect) hazardCategorySelect.value = '';
        if (campaignTopicInput) campaignTopicInput.value = '';
        return;
    }
    
    const selectedOption = campaignSelect.options[campaignSelect.selectedIndex];
    const category = selectedOption.getAttribute('data-category') || '';
    const title = selectedOption.getAttribute('data-title') || '';
    
    // Auto-fill Hazard Category
    if (hazardCategorySelect && category) {
        // Try to match category to hazard category options
        const categoryLower = category.toLowerCase();
        const hazardOptions = Array.from(hazardCategorySelect.options);
        const matchedOption = hazardOptions.find(opt => 
            opt.value.toLowerCase() === categoryLower || 
            opt.textContent.toLowerCase().includes(categoryLower)
        );
        
        if (matchedOption) {
            hazardCategorySelect.value = matchedOption.value;
        } else {
            // If no exact match, try to set based on common patterns
            if (categoryLower.includes('fire')) hazardCategorySelect.value = 'Fire';
            else if (categoryLower.includes('flood')) hazardCategorySelect.value = 'Flood';
            else if (categoryLower.includes('earthquake')) hazardCategorySelect.value = 'Earthquake';
            else if (categoryLower.includes('typhoon')) hazardCategorySelect.value = 'Typhoon';
            else if (categoryLower.includes('health')) hazardCategorySelect.value = 'Health';
            else if (categoryLower.includes('emergency')) hazardCategorySelect.value = 'Emergency';
        }
    }
    
    // Auto-fill Campaign Topic
    if (campaignTopicInput && title) {
        campaignTopicInput.value = title;
    }
}

// Upload form handler
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    // Handle intended_audience as array - use Array.from(select.selectedOptions).map(o => o.value)
    const audienceSelect = document.getElementById('intendedAudience');
    if (audienceSelect) {
        formData.delete('intended_audience_segment[]');
        formData.delete('intended_audience[]');
        const audienceValues = Array.from(audienceSelect.selectedOptions).map(o => o.value).filter(v => v);
        if (audienceValues.length > 0) {
        audienceValues.forEach(value => {
                formData.append('intended_audience[]', value);
                formData.append('intended_audience_segment[]', value); // Backward compatibility
        });
        }
    }
    
    // Handle source as array - use Array.from(select.selectedOptions).map(o => o.value)
    const sourceSelect = document.getElementById('sourceSelect');
    if (sourceSelect) {
        formData.delete('source[]');
        const sourceValues = Array.from(sourceSelect.selectedOptions).map(o => o.value).filter(v => v);
        if (sourceValues.length > 0) {
            sourceValues.forEach(value => {
                formData.append('source[]', value);
            });
        }
    }
    
    // Handle visibility as array - use Array.from(select.selectedOptions).map(o => o.value)
    const visibilitySelect = document.getElementById('visibilitySelect');
    if (visibilitySelect) {
        formData.delete('visibility[]');
        formData.delete('visibility');
        const visibilityValues = Array.from(visibilitySelect.selectedOptions).map(o => o.value).filter(v => v);
        if (visibilityValues.length > 0) {
            visibilityValues.forEach(value => {
                formData.append('visibility[]', value);
            });
        } else {
            // Default to 'For Official Use' if none selected
            formData.append('visibility[]', 'For Official Use');
        }
    }
    
    // Capture linked campaign ID and title
    const campaignSelect = document.getElementById('linkedCampaign');
    const campaignId = campaignSelect ? campaignSelect.value : null;
    let campaignTitle = null;
    if (campaignId && campaignSelect) {
        const selectedOption = campaignSelect.options[campaignSelect.selectedIndex];
        campaignTitle = selectedOption ? selectedOption.textContent : null;
        if (campaignTitle === 'Select Campaign...') campaignTitle = null;
    }
    
    // Ensure campaign_id is sent
    if (campaignId) {
        formData.set('campaign_id', campaignId);
        // Also send as linked_campaign_id for consistency
        formData.append('linked_campaign_id', campaignId);
        if (campaignTitle) {
            formData.append('linked_campaign_title', campaignTitle);
        }
    }
    
    const statusEl = document.getElementById('uploadStatus');
    const form = e.target;
    const contentId = form.dataset.contentId;
    
    // Check if this is an update
    if (contentId) {
        // Update existing content
        statusEl.textContent = 'Updating...';
        statusEl.style.color = '#64748b';
        
        try {
            // For updates, we need to send JSON (not FormData) since we're not uploading a new file
            const updateData = {
                title: formData.get('title'),
                body: formData.get('description'),
                content_type: formData.get('content_type'),
                hazard_category: formData.get('hazard_category'),
                intended_audience_segment: formData.get('intended_audience_segment'),
                source: formData.get('source'),
                visibility: formData.get('visibility')
            };
            
            const res = await fetch(apiBase + '/api/v1/content/' + contentId, {
                method: 'PUT',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token 
                },
                body: JSON.stringify(updateData)
            });
            
            // Check if response is JSON before parsing
            const contentType = res.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                // Server returned HTML or other non-JSON response (likely an error)
                const text = await res.text();
                console.error('Server returned non-JSON response:', text);
                statusEl.innerHTML = '✗ Server Error: The server returned an unexpected response.<br><small>Check browser console for details. This usually means a PHP error occurred.</small>';
                statusEl.style.color = '#dc2626';
                return;
            }
            
            if (res.ok) {
                statusEl.textContent = '✓ Content updated successfully!';
                statusEl.style.color = '#059669';
                
                // Clear form and reset
                form.reset();
                delete form.dataset.contentId;
                const submitBtn = document.querySelector('#contentForm button[type="submit"]');
                if (submitBtn) {
                    submitBtn.textContent = 'Upload Campaign Material';
                }
                
                // Clear multi-select tags
                const audienceTags = document.getElementById('intended_audience_tags');
                const sourceTags = document.getElementById('source_tags');
                const visibilityTags = document.getElementById('visibility_tags');
                if (audienceTags) audienceTags.innerHTML = '';
                if (sourceTags) sourceTags.innerHTML = '';
                if (visibilityTags) visibilityTags.innerHTML = '';
                
                // Refresh content list
                currentPage = 1;
                loadContent();
                loadTemplates();
                loadMediaGallery();
            } else {
                statusEl.textContent = '✗ Error: ' + (data.error || 'Update failed');
                statusEl.style.color = '#dc2626';
            }
        } catch (err) {
            statusEl.textContent = '✗ Network error: ' + err.message;
            statusEl.style.color = '#dc2626';
        }
        return;
    }
    
    // Create new content
    statusEl.textContent = 'Uploading...';
    statusEl.style.color = '#64748b';
    
    try {
        const res = await fetch(apiBase + '/api/v1/content', {
            method: 'POST',
            headers: { 'Authorization': 'Bearer ' + token },
            body: formData
        });
        
        // Check if response is JSON before parsing
        const contentType = res.headers.get('content-type');
        let data;
        
        if (contentType && contentType.includes('application/json')) {
            data = await res.json();
        } else {
            // Server returned HTML or other non-JSON response (likely an error)
            const text = await res.text();
            console.error('Server returned non-JSON response:', text);
            statusEl.innerHTML = '✗ Server Error: The server returned an unexpected response.<br><small>Check browser console for details. This usually means a PHP error occurred.</small>';
            statusEl.style.color = '#dc2626';
            return;
        }
        
        if (res.ok) {
            statusEl.textContent = '✓ Campaign material uploaded successfully!';
            statusEl.style.color = '#059669';
            
            console.log('Upload successful, response:', data);
            
            // Extract the new content item from response
            const newItem = data.data || data;
            
            // Normalize multi-select fields to arrays
            if (newItem.intended_audience_segment && !Array.isArray(newItem.intended_audience)) {
                newItem.intended_audience = Array.isArray(newItem.intended_audience_segment) 
                    ? newItem.intended_audience_segment 
                    : newItem.intended_audience_segment.split(',').map(s => s.trim());
            }
            if (newItem.source && !Array.isArray(newItem.source)) {
                newItem.source = Array.isArray(newItem.source) 
                    ? newItem.source 
                    : [newItem.source];
            }
            if (newItem.visibility && !Array.isArray(newItem.visibility)) {
                newItem.visibility = Array.isArray(newItem.visibility) 
                    ? newItem.visibility 
                    : [newItem.visibility];
            }
            
            // Add linked campaign info if available
            if (campaignId) {
                newItem.linked_campaign_id = parseInt(campaignId);
                newItem.linked_campaign_title = campaignTitle;
            }
            
            // Save to localStorage and append to contents array
            if (newItem.id) {
                // Load existing uploaded content from localStorage
                const uploaded = JSON.parse(localStorage.getItem("content_repository_uploaded") || "[]");
                // Add new item
                uploaded.push(newItem);
                // Save back to localStorage
                localStorage.setItem("content_repository_uploaded", JSON.stringify(uploaded));
                // Append to contents array
                contents.push(newItem);
                console.log('✓ Saved to localStorage and added to contents array, total:', contents.length);
            }
            
            // Reset form
            e.target.reset();
            
            // Clear multi-select tags
            const audienceTags = document.getElementById('intended_audience_tags');
            const sourceTags = document.getElementById('source_tags');
            const visibilityTags = document.getElementById('visibility_tags');
            if (audienceTags) audienceTags.innerHTML = '';
            if (sourceTags) sourceTags.innerHTML = '';
            if (visibilityTags) visibilityTags.innerHTML = '';
            
            // Reset multi-select dropdowns
            const audienceSelect = document.getElementById('intendedAudience');
            const sourceSelect = document.getElementById('sourceSelect');
            const visibilitySelect = document.getElementById('visibilitySelect');
            if (audienceSelect) {
                Array.from(audienceSelect.options).forEach(opt => opt.selected = false);
                if (typeof audienceSelect.updateTags === 'function') audienceSelect.updateTags();
            }
            if (sourceSelect) {
                Array.from(sourceSelect.options).forEach(opt => opt.selected = false);
                if (typeof sourceSelect.updateTags === 'function') sourceSelect.updateTags();
            }
            if (visibilitySelect) {
                Array.from(visibilitySelect.options).forEach(opt => opt.selected = false);
                if (typeof visibilitySelect.updateTags === 'function') visibilitySelect.updateTags();
            }
            
            // Clear ALL filters that might hide new draft content
            document.getElementById('filterSearch').value = '';
            document.getElementById('filterContentType').value = '';
            document.getElementById('filterHazardCategory').value = '';
            document.getElementById('filterIntendedAudience').value = '';
            document.getElementById('filterSource').value = '';
            document.getElementById('filterApprovalStatus').value = '';
            const filterOnlyApproved = document.getElementById('filterOnlyApproved');
            if (filterOnlyApproved) {
                filterOnlyApproved.checked = false;
            }
            
            // Reset to first page
            currentPage = 1;
            currentTemplatesPage = 1;
            currentMediaGalleryPage = 1;
            
            // Immediately re-render all sections from contents array
            loadContent();
            loadTemplates();
            loadMediaGallery();
            
            statusEl.textContent = '✓ Campaign material uploaded successfully! Check the Content Library below.';
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Upload failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
});

// Usage form handler
document.getElementById('usageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const contentId = parseInt(fd.get('content_id'), 10);
    const statusEl = document.getElementById('usageStatus');
    statusEl.textContent = 'Logging usage...';
    statusEl.style.color = '#64748b';
    
    if (!contentId) {
        statusEl.textContent = '✗ Error: Please select a content item';
        statusEl.style.color = '#dc2626';
        return;
    }
    
    const payload = {
        campaign_id: fd.get('campaign_id') ? parseInt(fd.get('campaign_id'), 10) : null,
        event_id: fd.get('event_id') ? parseInt(fd.get('event_id'), 10) : null,
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
            statusEl.textContent = '✓ Usage logged successfully for evaluation!';
            statusEl.style.color = '#166534';
            e.target.reset();
            loadUsageHistory();
        } else {
            statusEl.textContent = '✗ Error: ' + (data.error || 'Failed');
            statusEl.style.color = '#dc2626';
        }
    } catch (err) {
        statusEl.textContent = '✗ Network error: ' + err.message;
        statusEl.style.color = '#dc2626';
    }
});

// Update approval status
async function updateApproval(contentId, status, notes = '') {
    try {
        console.log('Updating approval status:', { contentId, status, notes });
        
        // Use standalone endpoint for pending_review to bypass workflow validation
        if (status === 'pending_review') {
            const basePath = publicPath.replace(/\/public$/, '');
            const endpointUrl = basePath + '/api/submit_for_review.php';
            
            console.log('Submitting for review via:', endpointUrl);
            
            const res = await fetch(endpointUrl, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: contentId })
            });
            
            console.log('Response status:', res.status, res.statusText);
            
            // Check if response is JSON
            const contentType = res.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                // If not JSON, read as text
                const text = await res.text();
                console.error('Non-JSON response:', text);
                alert('Error: Server returned invalid response. Status: ' + res.status + '\n' + text.substring(0, 200));
                return;
            }
            
            console.log('Response data:', data);
            
            if (res.ok && data.success) {
                console.log('Successfully submitted for review');
                
                // Force clear the contents array to ensure fresh data from API
                contents = [];
                
                currentTemplatesPage = 1;
                currentPage = 1;
                await loadAllContent();
                await loadContent(true);
                loadTemplates();
                loadMediaGallery();
                // Refresh usage dropdowns in case content was already approved
                loadApprovedContentForUsage();
                loadApprovedContentForUsageHistory();
                alert('Content submitted for review successfully!');
            } else {
                const errorMsg = data.error || data.message || 'Failed to submit for review';
                console.error('API error:', errorMsg);
                alert('Error: ' + errorMsg);
            }
        } else {
            // For other statuses (rejected, archived), use the existing API route
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '/approval', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token 
            },
            body: JSON.stringify({
                approval_status: status,
                approval_notes: notes
            })
        });
            
            const contentType = res.headers.get('content-type');
            let data;
            
            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                const text = await res.text();
                console.error('Non-JSON response:', text);
                alert('Error: Server returned invalid response. Status: ' + res.status);
                return;
            }
            
        if (res.ok) {
            console.log('Approval status updated successfully, refreshing content...');
            currentTemplatesPage = 1;
            
            // Force clear the contents array to ensure fresh data
            contents = [];
            
            // Reload content to reflect status change
            await loadAllContent();
            await loadContent(true);
            loadTemplates();
            loadMediaGallery();
            
            console.log('Content refreshed after status update');
            alert('Content status updated to: ' + status);
        } else {
            const errorMsg = data.error || data.message || 'Failed to update approval status';
            alert('Error: ' + errorMsg);
        }
        }
    } catch (err) {
        console.error('Network/parse error:', err);
        alert('Network error: ' + err.message + '\n\nPlease check the browser console for details.');
    }
}

// Reject content with confirmation
async function rejectContent(contentId) {
    const confirmed = await customConfirm('Are you sure you want to reject this content?', 'Reject Content');
    if (!confirmed) {
        return;
    }
    
    const notes = await customPrompt('Enter rejection reason (optional):', 'Content rejected') || 'Content rejected';
    await updateApproval(contentId, 'rejected', notes);
}

// Approve content - minimal endpoint
async function approveContent(contentId) {
    const confirmed = await customConfirm('Are you sure you want to approve this content? It will appear in Templates and Media Gallery.', 'Approve Content');
    if (!confirmed) {
        return;
    }
    
    try {
        // Use standalone endpoint that bypasses workflow validation (allows direct approval from draft)
        // Extract base path from publicPath (e.g., /public-safety-campaign-system)
        const basePath = publicPath.replace(/\/public$/, '');
        const endpointUrl = basePath + '/api/approve_content.php';
        
        console.log('Approving content:', contentId, 'via:', endpointUrl);
        
        const res = await fetch(endpointUrl, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: contentId })
        });
        
        // Check if response is actually JSON before parsing
        const contentType = res.headers.get('content-type') || '';
        let data;
        
        if (!contentType.includes('application/json')) {
            const text = await res.text();
            console.error('Non-JSON response from approve endpoint:', text.substring(0, 500));
            // Try to parse as JSON anyway in case header is wrong
            try {
                data = JSON.parse(text);
            } catch (e) {
                throw new Error('Server returned HTML instead of JSON. Check endpoint path: ' + endpointUrl);
            }
        } else {
            data = await res.json();
        }
        
        if (res.ok && (data.success || data.data)) {
            // Force clear the contents array to ensure fresh data from API
            contents = [];
            
            // Re-render all views with force refresh
            currentPage = 1;
            currentTemplatesPage = 1;
            currentMediaGalleryPage = 1;
            await loadAllContent();
            await loadContent(true);
            loadTemplates();
            loadMediaGallery();
            // Refresh usage dropdowns to include newly approved content
            loadApprovedContentForUsage();
            loadApprovedContentForUsageHistory();
            
            alert('Content approved successfully!');
        } else {
            alert('Error: ' + (data.error || 'Failed to approve content'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Archive content
async function archiveContent(contentId) {
    const confirmed = await customConfirm('Are you sure you want to archive this content? Archived content will be hidden by default.', 'Archive Content');
    if (!confirmed) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '/archive', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token 
            }
        });
        const data = await res.json();
        if (res.ok) {
            // Update the local contents array immediately to mark item as archived
            const itemIndex = contents.findIndex(item => item.id === contentId || item.id === String(contentId));
            if (itemIndex !== -1) {
                contents[itemIndex].approval_status = 'archived';
                console.log('Content ' + contentId + ' marked as archived in local array');
            }
            
            // Also update localStorage if it exists there
            const uploaded = JSON.parse(localStorage.getItem("content_repository_uploaded") || "[]");
            const uploadedIndex = uploaded.findIndex(item => item.id === contentId || item.id === String(contentId));
            if (uploadedIndex !== -1) {
                uploaded[uploadedIndex].approval_status = 'archived';
                localStorage.setItem("content_repository_uploaded", JSON.stringify(uploaded));
            }
            
            currentTemplatesPage = 1; // Reset to first page when content is archived
            currentPage = 1;
            currentMediaGalleryPage = 1;
            
            await customAlert('Content archived successfully!', 'Success');
            
            // Force clear the contents array to ensure fresh data from API
            contents = [];
            
            // Reload views with force refresh - the filter in loadContent will now exclude archived items
            await loadAllContent();
            await loadContent(true);
            loadTemplates();
            loadMediaGallery();
        } else {
            await customAlert('Error: ' + (data.error || 'Failed to archive content'), 'Error');
        }
    } catch (err) {
        await customAlert('Network error: ' + err.message, 'Error');
    }
}

// View content (alias for showContentDetails for consistency)
async function viewContent(contentId) {
    await showContentDetails(contentId);
}

// Edit content
async function editContent(contentId) {
    try {
        const res = await fetch(apiBase + '/api/v1/content/' + contentId, {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (!res.ok) {
            const error = await res.json();
            alert('Error: ' + (error.error || 'Failed to load content'));
            return;
        }
        
        const data = await res.json();
        const item = data.data || data;
        
        if (!item || !item.id) {
            await customAlert('Failed to load content: Content not found', 'Error');
            return;
        }
        
        // Check if content can be edited (draft, pending_review, under_review, or approved)
        const status = (item.approval_status || '').toLowerCase().trim();
        const editableStatuses = ['draft', 'pending_review', 'under_review', 'approved', 'pending review', 'under review'];
        console.log('Edit content - status:', status, 'item:', item);
        if (!editableStatuses.includes(status)) {
            await customAlert('Only draft, pending review, or approved content can be edited. Current status: ' + (item.approval_status || 'unknown'), 'Notice');
            return;
        }
        
        // Populate form with content data (with null checks)
        const titleEl = document.getElementById('contentTitle');
        const descEl = document.getElementById('contentDescription');
        const typeEl = document.getElementById('contentType');
        const hazardEl = document.getElementById('hazardCategory');
        
        if (titleEl) titleEl.value = item.title || '';
        if (descEl) descEl.value = item.body || '';
        if (typeEl) typeEl.value = item.content_type || 'text';
        if (hazardEl) hazardEl.value = item.hazard_category || '';
        
        // Handle multi-select fields
        if (item.intended_audience_segment) {
            const audienceEl = document.getElementById('intendedAudience');
            if (audienceEl && typeof audienceEl.setSelectedValues === 'function') {
                const audience = typeof item.intended_audience_segment === 'string' 
                    ? item.intended_audience_segment.split(',').map(s => s.trim())
                    : item.intended_audience_segment;
                audienceEl.setSelectedValues(audience);
            }
        }
        
        if (item.source) {
            const sourceEl = document.getElementById('sourceSelect');
            if (sourceEl && typeof sourceEl.setSelectedValues === 'function') {
                const source = typeof item.source === 'string' 
                    ? item.source.split(',').map(s => s.trim())
                    : item.source;
                sourceEl.setSelectedValues(source);
            }
        }
        
        if (item.visibility) {
            const visibilityEl = document.getElementById('visibilitySelect');
            if (visibilityEl && typeof visibilityEl.setSelectedValues === 'function') {
                const visibility = typeof item.visibility === 'string' 
                    ? [item.visibility]
                    : item.visibility;
                visibilityEl.setSelectedValues(visibility);
            }
        }
        
        // Store content ID for update
        const formEl = document.getElementById('uploadForm');
        if (formEl) {
            formEl.dataset.contentId = contentId;
        }
        
        // Change submit button text
        const submitBtn = document.querySelector('#uploadForm button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = 'Update Content';
        }
        
        // Scroll to form and open modal
        const createSection = document.getElementById('create-content');
        const uploadModal = document.getElementById('uploadContentModal');
        if (uploadModal) {
            uploadModal.style.display = 'flex';
        }
        if (createSection) {
            createSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
    } catch (err) {
        await customAlert('Failed to load content: ' + err.message, 'Error');
    }
}

// Delete content
async function deleteContent(contentId) {
    if (!confirm('Are you sure you want to delete this content? This action cannot be undone and will permanently remove the content and its file.')) {
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/' + contentId, {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to delete content'));
            return;
        }
        
        alert('Content deleted successfully!');
        
        // Remove from contents array
        contents = contents.filter(c => c.id !== contentId);
        
        // Remove from localStorage if it exists there
        const uploaded = JSON.parse(localStorage.getItem('content_repository_uploaded') || '[]');
        const filtered = uploaded.filter(c => c.id !== contentId);
        localStorage.setItem('content_repository_uploaded', JSON.stringify(filtered));
        
        // Refresh views
        currentPage = 1;
        currentTemplatesPage = 1;
        currentMediaGalleryPage = 1;
        loadContent();
        loadTemplates();
        loadMediaGallery();
    } catch (err) {
        alert('Failed to delete content: ' + err.message);
    }
}

// Show content details modal
async function showContentDetails(contentId) {
    try {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '?_t=' + timestamp, {
            headers: { 
                'Authorization': 'Bearer ' + token, 
                'Accept': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            }
        });
        
        if (!res.ok) {
            const error = await res.json();
            alert('Error: ' + (error.error || 'Failed to load content details'));
            return;
        }
        
        const data = await res.json();
        const item = data.data;
        
        // Update the local contents array with fresh data from API
        // This ensures the card will show the correct status after viewing
        const contentIndex = contents.findIndex(c => String(c.id) === String(contentId));
        let statusChanged = false;
        if (contentIndex !== -1) {
            const previousStatus = contents[contentIndex].approval_status;
            contents[contentIndex] = { ...contents[contentIndex], ...item };
            statusChanged = previousStatus !== item.approval_status;
            console.log(`Updated content #${contentId} in local array: ${previousStatus} → ${item.approval_status}`, statusChanged ? '(CHANGED)' : '(same)');
        }
        
        // Format dates
        const formatDate = (dateStr) => {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        };
        
        // Build file URL
        const filePath = item.file_reference || item.file_path || '';
        let fileUrl = '';
        if (filePath) {
            if (filePath.startsWith('http')) {
                fileUrl = filePath;
            } else {
                const cleanPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
                fileUrl = publicPath + '/' + cleanPath;
            }
        }
        
        // Build modal content
        const statusText = (item.approval_status || 'draft').replace(/_/g, ' ').split(' ').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
        
        const modal = document.createElement('div');
        modal.id = 'contentDetailsModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px; overflow-y: auto;';
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = 'background: white; padding: 0; border-radius: 12px; max-width: 800px; width: 100%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); position: relative;';
        
        // Function to close modal and refresh grid
        const closeModal = () => {
            modal.remove();
            // Always refresh the content grid to ensure it shows the latest status
            // This handles cases where status was updated elsewhere (e.g., by another user or API)
            console.log('Refreshing content grid after modal close');
            loadContent(true);
        };
        
        modalContent.innerHTML = `
            <div style="position: sticky; top: 0; background: white; border-bottom: 1px solid #e2e8f0; padding: 20px 24px; z-index: 1; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 24px; font-weight: 700; color: #0f172a;">Content Details</h2>
                <button id="closeModalBtn" style="background: none; border: none; font-size: 24px; color: #64748b; cursor: pointer; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 6px; transition: all 0.2s;" onmouseover="this.style.background='#f1f5f9'; this.style.color='#0f172a';" onmouseout="this.style.background='none'; this.style.color='#64748b';">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Content ID</div>
                        <div style="font-size: 16px; color: #0f172a; font-weight: 600;">#${item.id}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Status</div>
                        <span class="content-status-badge status-${(item.approval_status || 'draft').replace(/_/g, '-')}" style="padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; display: inline-block;">${statusText}</span>
                    </div>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 600;">Title</div>
                    <div style="font-size: 20px; color: #0f172a; font-weight: 700; line-height: 1.4;">${item.title || 'Untitled'}</div>
                </div>
                
                ${item.body ? `
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 600;">Description</div>
                        <div style="font-size: 14px; color: #475569; line-height: 1.6; white-space: pre-wrap; max-height: 120px; overflow-y: auto;">${item.body}</div>
                    </div>
                ` : ''}
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 20px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Content Type</div>
                        <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${(item.content_type || 'text').charAt(0).toUpperCase() + (item.content_type || 'text').slice(1)}</div>
                    </div>
                    ${item.hazard_category ? `
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Hazard Category</div>
                            <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${item.hazard_category}</div>
                        </div>
                    ` : '<div></div>'}
                    ${item.intended_audience_segment ? `
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Intended Audience</div>
                            <div style="font-size: 14px; color: #0f172a; font-weight: 500; word-break: break-word;">${item.intended_audience_segment.length > 30 ? item.intended_audience_segment.substring(0, 30) + '...' : item.intended_audience_segment}</div>
                        </div>
                    ` : '<div></div>'}
                    ${item.source ? `
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Source</div>
                            <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${item.source}</div>
                        </div>
                    ` : '<div></div>'}
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Visibility</div>
                        <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${(item.visibility || 'public').charAt(0).toUpperCase() + (item.visibility || 'public').slice(1)}</div>
                    </div>
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Version</div>
                        <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${item.version_number || 1}</div>
                    </div>
                </div>
                
                ${item.tags && item.tags.length > 0 ? `
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px; font-weight: 600;">Tags</div>
                        <div style="display: flex; flex-wrap: gap: 8px;">
                            ${item.tags.map(tag => `<span style="background: #e0f2fe; color: #1d4ed8; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 500;">${tag}</span>`).join('')}
                        </div>
                    </div>
                ` : ''}
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                    <div>
                        <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Uploaded By</div>
                        <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${item.uploaded_by_name || 'N/A'}</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">${formatDate(item.date_uploaded || item.created_at)}</div>
                    </div>
                    ${item.approved_by_name ? `
                        <div>
                            <div style="font-size: 12px; color: #64748b; margin-bottom: 4px; font-weight: 600;">Approved By</div>
                            <div style="font-size: 14px; color: #0f172a; font-weight: 500;">${item.approved_by_name}</div>
                            ${item.approval_notes ? `
                                <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">${item.approval_notes}</div>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
                
                ${item.versions && item.versions.length > 0 ? `
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">Version History</div>
                        <div style="display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto;">
                            ${item.versions.map(version => `
                                <div style="padding: 12px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #4c8a89;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <span style="font-weight: 600; color: #0f172a;">Version ${version.version_number}</span>
                                        <span style="font-size: 11px; color: #64748b;">${formatDate(version.created_at)}</span>
                                    </div>
                                    ${version.changed_by_name ? `
                                        <div style="font-size: 12px; color: #64748b;">Changed by: ${version.changed_by_name}</div>
                                    ` : ''}
                                    ${version.change_notes ? `
                                        <div style="font-size: 12px; color: #475569; margin-top: 4px;">${version.change_notes}</div>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${item.campaigns && item.campaigns.length > 0 ? `
                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">Linked Campaigns</div>
                        <div style="display: flex; flex-direction: column; gap: 8px; max-height: 150px; overflow-y: auto;">
                            ${item.campaigns.map(campaign => `
                                <div style="padding: 12px; background: #f8fafc; border-radius: 8px;">
                                    <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">${campaign.title}</div>
                                    <div style="font-size: 12px; color: #64748b;">
                                        Status: ${campaign.status} | Attached: ${formatDate(campaign.attached_at)}
                                        ${campaign.attached_by_name ? ` | By: ${campaign.attached_by_name}` : ''}
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}
                
                ${fileUrl ? `
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <a href="${fileUrl}" target="_blank" class="btn btn-primary" style="width: 100%; text-align: center; text-decoration: none; display: inline-block;">
                            <i class="fas fa-eye"></i> View File
                        </a>
                    </div>
                ` : ''}
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Close button functionality
        const closeBtn = modal.querySelector('#closeModalBtn');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });
        
        // Close on Escape key
        const escapeHandler = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', escapeHandler);
            }
        };
        document.addEventListener('keydown', escapeHandler);
        
    } catch (err) {
        console.error('Error loading content details:', err);
        alert('Error loading content details. Please try again.');
    }
}

// Use template
function useTemplate(contentId) {
    // Open the upload modal first
    const uploadModal = document.getElementById('uploadContentModal');
    if (uploadModal) {
        uploadModal.style.display = 'flex';
    }
    
    // Show loading state
    const statusEl = document.getElementById('uploadStatus');
    if (statusEl) {
        statusEl.textContent = 'Loading approved reusable material...';
        statusEl.style.color = '#64748b';
    }
    
    // Fetch content details and populate form
    fetch(apiBase + '/api/v1/content/' + contentId, {
        headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
    })
    .then(res => {
        if (!res.ok) {
            throw new Error('Failed to load template');
        }
        return res.json();
    })
    .then(data => {
        // Handle both {data: {...}} and direct {...} response formats
        const item = data.data || data;
        
        if (item && item.id) {
            const form = document.getElementById('uploadForm');
            if (!form) {
                console.error('Upload form not found');
                return;
            }
            if (form.elements['title']) form.elements['title'].value = 'Copy of ' + (item.title || '');
            if (form.elements['description']) form.elements['description'].value = item.body || '';
            if (form.elements['content_type']) form.elements['content_type'].value = item.content_type || '';
            if (form.elements['hazard_category']) form.elements['hazard_category'].value = item.hazard_category || '';
            // Handle multi-select intended_audience_segment[] field
            // Handle multi-select intended_audience_segment[] field
            const audienceSelect = document.getElementById('intendedAudience');
            if (audienceSelect) {
                if (item.intended_audience_segment) {
                    const audiences = item.intended_audience_segment.split(/\s*,\s*/).filter(Boolean).map(a => a.trim());
                    if (typeof audienceSelect.setSelectedValues === 'function') {
                        audienceSelect.setSelectedValues(audiences);
                    } else {
                        Array.from(audienceSelect.options).forEach(opt => opt.selected = false);
                        audiences.forEach(audience => {
                            const option = Array.from(audienceSelect.options).find(opt => opt.value === audience);
                            if (option) option.selected = true;
                        });
                        if (typeof audienceSelect.updateTags === 'function') {
                            audienceSelect.updateTags();
                        }
                    }
                }
            }
            
            // Handle multi-select source[]
            const sourceSelect = document.getElementById('sourceSelect');
            if (sourceSelect && item.source) {
                const sources = item.source.split(/\s*,\s*/).filter(Boolean).map(s => s.trim());
                if (typeof sourceSelect.setSelectedValues === 'function') {
                    sourceSelect.setSelectedValues(sources);
                } else {
                    Array.from(sourceSelect.options).forEach(opt => opt.selected = false);
                    sources.forEach(source => {
                        const option = Array.from(sourceSelect.options).find(opt => opt.value === source);
                        if (option) option.selected = true;
                    });
                    if (typeof sourceSelect.updateTags === 'function') {
                        sourceSelect.updateTags();
                    }
                }
            }
            
            // Handle multi-select visibility[]
            const visibilitySelect = document.getElementById('visibilitySelect');
            if (visibilitySelect) {
                const visibility = item.visibility || 'public';
                const visibilities = visibility.split(/\s*,\s*/).filter(Boolean).map(v => v.trim());
                if (typeof visibilitySelect.setSelectedValues === 'function') {
                    visibilitySelect.setSelectedValues(visibilities);
                } else {
                    Array.from(visibilitySelect.options).forEach(opt => opt.selected = false);
                    visibilities.forEach(vis => {
                        const option = Array.from(visibilitySelect.options).find(opt => opt.value === vis);
                        if (option) option.selected = true;
                    });
                    if (typeof visibilitySelect.updateTags === 'function') {
                        visibilitySelect.updateTags();
                    }
                }
            }
            
            // Handle campaign_id if available
            if (form.elements['campaign_id'] && item.campaign_id) {
                form.elements['campaign_id'].value = item.campaign_id;
                handleCampaignChange();
            }
            
            statusEl.textContent = '✓ Approved reusable material loaded! Update the title and upload a new file.';
            statusEl.style.color = '#059669';
            setTimeout(() => {
                statusEl.textContent = '';
            }, 5000);
        } else {
            throw new Error('Invalid template data');
        }
    })
    .catch(err => {
        console.error('Error loading template:', err);
        statusEl.textContent = '✗ Error loading approved reusable material: ' + err.message;
        statusEl.style.color = '#dc2626';
    });
}

// Load approved content for usage form dropdown
async function loadApprovedContentForUsage() {
    const select = document.getElementById('usageContentSelect');
    if (!select) {
        console.warn('usageContentSelect element not found');
        return;
    }
    
    // Use the contents array (single source of truth) - filter for approved content
    const normalizeStatus = (raw) => {
        if (!raw) return "draft";
        return raw.toString().trim().toLowerCase().replace(/\s+/g, "_");
    };
    
    // Filter approved content from the contents array
    const approvedContent = contents.filter(item => {
        const status = normalizeStatus(item.approval_status);
        return status === 'approved';
    });
    
    // Clear existing options except the first one
    select.innerHTML = '<option value="">Select approved content...</option>';
    
    // Sort by title for easier selection
    approvedContent.sort((a, b) => {
        const titleA = (a.title || 'Untitled').toLowerCase();
        const titleB = (b.title || 'Untitled').toLowerCase();
        return titleA.localeCompare(titleB);
    });
    
    approvedContent.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        const title = item.title || 'Untitled';
        const contentType = item.content_type || '';
        const displayText = `${item.id} - ${title}${contentType ? ' (' + contentType + ')' : ''}`;
        option.textContent = displayText;
        select.appendChild(option);
    });
    
    console.log('Loaded', approvedContent.length, 'approved content items for usage form from contents array');
}

// Load approved content for usage history dropdown (same as usage form)
async function loadApprovedContentForUsageHistory() {
    const select = document.getElementById('usageHistoryContentId');
    if (!select) {
        console.warn('usageHistoryContentId element not found');
        return;
    }
    
    // Use the contents array (single source of truth) - filter for approved content
    const normalizeStatus = (raw) => {
        if (!raw) return "draft";
        return raw.toString().trim().toLowerCase().replace(/\s+/g, "_");
    };
    
    // Filter approved content from the contents array
    const approvedContent = contents.filter(item => {
        const status = normalizeStatus(item.approval_status);
        return status === 'approved';
    });
    
    // Clear existing options except the first one
    const currentValue = select.value; // Preserve current selection
    select.innerHTML = '<option value="">All approved content...</option>';
    
    // Sort by title for easier selection
    approvedContent.sort((a, b) => {
        const titleA = (a.title || 'Untitled').toLowerCase();
        const titleB = (b.title || 'Untitled').toLowerCase();
        return titleA.localeCompare(titleB);
    });
    
    approvedContent.forEach(item => {
        const option = document.createElement('option');
        option.value = item.id;
        const title = item.title || 'Untitled';
        const contentType = item.content_type || '';
        const displayText = `${item.id} - ${title}${contentType ? ' (' + contentType + ')' : ''}`;
        option.textContent = displayText;
        select.appendChild(option);
    });
    
    // Restore previous selection if it still exists
    if (currentValue) {
        select.value = currentValue;
    }
    
    console.log('Loaded', approvedContent.length, 'approved content items for usage history dropdown from contents array');
}

// Load campaigns for usage form dropdown
async function loadCampaignsForUsage() {
    const select = document.getElementById('usageCampaignSelect');
    if (!select) {
        console.warn('usageCampaignSelect element not found');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (res.ok) {
            const data = await res.json();
            // Handle both {data: [...]} and direct array responses
            let campaigns = [];
            if (Array.isArray(data)) {
                campaigns = data;
            } else if (data.data && Array.isArray(data.data)) {
                campaigns = data.data;
            }
            
            // Clear existing options except the first one
            select.innerHTML = '<option value="">Select campaign...</option>';
            
            campaigns.forEach(campaign => {
                const option = document.createElement('option');
                option.value = campaign.id;
                option.textContent = `${campaign.id} - ${campaign.title || 'Untitled Campaign'}`;
                select.appendChild(option);
            });
            
            console.log('Loaded', campaigns.length, 'campaigns for usage form');
        } else {
            console.error('Failed to load campaigns for usage form:', res.status, res.statusText);
        }
    } catch (err) {
        console.error('Error loading campaigns for usage form:', err);
    }
}

// Load events for usage form dropdown
async function loadEventsForUsage() {
    const select = document.getElementById('usageEventSelect');
    if (!select) {
        console.warn('usageEventSelect element not found');
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/events', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (res.ok) {
            const data = await res.json();
            // Handle both {data: [...]} and direct array responses
            let events = [];
            if (Array.isArray(data)) {
                events = data;
            } else if (data.data && Array.isArray(data.data)) {
                events = data.data;
            }
            
            // Clear existing options except the first one
            select.innerHTML = '<option value="">Select event...</option>';
            
            events.forEach(event => {
                const option = document.createElement('option');
                option.value = event.event_id || event.id;
                const eventName = event.event_title || event.event_name || 'Untitled Event';
                option.textContent = `${event.event_id || event.id} - ${eventName}`;
                select.appendChild(option);
            });
            
            console.log('Loaded', events.length, 'events for usage form');
        } else {
            console.error('Failed to load events for usage form:', res.status, res.statusText);
        }
    } catch (err) {
        console.error('Error loading events for usage form:', err);
    }
}

// Sample data removed - content now loads from API only
function getSampleContentData() {
    return [];
}

// Load all content from API and merge with sample data and localStorage
async function loadAllContent() {
    const sampleSeedData = getSampleContentData();
    
    // Load uploaded content from localStorage
    const uploaded = JSON.parse(localStorage.getItem("content_repository_uploaded") || "[]");
    
    try {
        // Fetch ALL content including archived to populate contents array
        // Note: API max per_page is 200
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const res = await fetch(apiBase + '/api/v1/content?per_page=200&include_archived=true&_t=' + timestamp, {
            method: 'GET',
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            cache: 'no-store'
        });
        
        // Check if token is missing or invalid
        if (!token) {
            console.warn('No JWT token found - user may need to log in');
        }
        
        let apiData = [];
        console.log('API response status:', res.status, res.statusText);
        
        if (res.ok) {
            const data = await res.json();
            console.log('API response data structure:', Object.keys(data));
            
            // Extract content array from response
            if (Array.isArray(data)) {
                apiData = data;
            } else if (data.data && Array.isArray(data.data)) {
                apiData = data.data;
            } else if (data.content && Array.isArray(data.content)) {
                apiData = data.content;
            }
            
            console.log('Extracted', apiData.length, 'items from API');
            
            // Debug: Log first few items to check content_type
            if (apiData.length > 0) {
                console.log('API content sample (first 3):', apiData.slice(0, 3).map(item => ({
                    id: item.id,
                    title: item.title,
                    content_type: item.content_type,
                    approval_status: item.approval_status,
                    hazard_category: item.hazard_category
                })));
            } else {
                console.warn('API returned 0 items - check if database has content or if there is an auth issue');
            }
        } else {
            console.error('API request failed:', res.status, res.statusText);
            const errorText = await res.text();
            console.error('API error response:', errorText.substring(0, 500));
        }
        
        // API data takes priority - merge with sample and uploaded, avoiding duplicates
        // Create a map of API items by ID (convert to string for consistent comparison)
        const apiMap = new Map(apiData.map(item => [String(item.id), item]));
        
        // Filter out sample/uploaded items that exist in API (API has latest data)
        // Convert IDs to string for consistent comparison
        const filteredSample = sampleSeedData.filter(item => !apiMap.has(String(item.id)));
        const filteredUploaded = uploaded.filter(item => !apiMap.has(String(item.id)));
        
        // Debug: Log any localStorage items that were filtered out
        const skippedUploaded = uploaded.filter(item => apiMap.has(String(item.id)));
        if (skippedUploaded.length > 0) {
            console.log('Skipped localStorage items (using API data instead):', skippedUploaded.map(i => ({id: i.id, status: i.approval_status})));
        }
        
        // Combine: API data first (most accurate), then filtered sample/uploaded for items not in API
        // This ensures API data takes priority while still showing localStorage items that aren't in DB yet
        const combined = [...apiData, ...filteredSample, ...filteredUploaded];
        contents = combined;
        console.log('✓ Loaded', apiData.length, 'API +', filteredSample.length, 'sample +', filteredUploaded.length, 'uploaded =', contents.length, 'total');
    } catch (err) {
        console.error('Error loading content:', err);
        // On error, merge sample data with uploaded data
        const combined = [...sampleSeedData, ...uploaded];
        contents = combined;
    }
}

// Load content library (with filters) - filters from contents array
async function loadContent(forceRefresh = false) {
    const container = document.getElementById('library');
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading content...</p>';
    
    try {
        // ALWAYS refresh from API to ensure we have the latest status
        // This prevents stale data from being displayed
        await loadAllContent();
        
        // Apply filters client-side to contents array
        // First, filter out archived items (they should only appear in View Archived)
        console.log('Contents before filtering:', contents.length, 'items');
        console.log('All statuses:', [...new Set(contents.map(c => c.approval_status))]);
        
        let filtered = contents.filter(item => {
            const status = normalizeStatus(item.approval_status);
            return status !== 'archived';
        });
        console.log('After archived filter:', filtered.length, 'items');
        
        const search = document.getElementById('filterSearch').value.trim().toLowerCase();
        if (search) {
            filtered = filtered.filter(item => 
                (item.title || '').toLowerCase().includes(search) ||
                (item.description || item.body || '').toLowerCase().includes(search)
            );
        }
        
        const contentType = document.getElementById('filterContentType').value;
        if (contentType) {
            filtered = filtered.filter(item => 
                (item.content_type || '').toLowerCase() === contentType.toLowerCase()
            );
        }
        
        const hazardCategory = document.getElementById('filterHazardCategory').value;
        if (hazardCategory) {
            filtered = filtered.filter(item => item.hazard_category === hazardCategory);
        }
        
        const intendedAudience = document.getElementById('filterIntendedAudience').value.trim();
        if (intendedAudience) {
            filtered = filtered.filter(item => {
                const audiences = Array.isArray(item.intended_audience) ? item.intended_audience : 
                    (item.intended_audience_segment ? [item.intended_audience_segment] : []);
                return audiences.some(a => a.toLowerCase().includes(intendedAudience.toLowerCase()));
            });
        }
        
        const source = document.getElementById('filterSource').value;
        if (source) {
            filtered = filtered.filter(item => {
                const sources = Array.isArray(item.source) ? item.source : 
                    (item.source ? [item.source] : []);
                return sources.includes(source);
            });
        }
        
        const approvalStatus = document.getElementById('filterApprovalStatus').value;
        if (approvalStatus) {
            filtered = filtered.filter(item => normalizeStatus(item.approval_status) === normalizeStatus(approvalStatus));
        }
        
        const onlyApproved = document.getElementById('filterOnlyApproved').checked;
        if (onlyApproved) {
            filtered = filtered.filter(item => normalizeStatus(item.approval_status) === 'approved');
        }
        
        container.innerHTML = '';
        
        // Show sample data notice if using sample data and no filters
        const hasFilters = search || contentType || hazardCategory || intendedAudience || source || approvalStatus || onlyApproved;
        const isSampleData = contents.length > 0 && contents[0].id >= 1000;
        
        if (isSampleData && !hasFilters && !localStorage.getItem('content_sample_data_shown')) {
                const wrapper = document.createElement('div');
                wrapper.style.cssText = 'grid-column: 1/-1;';
                const notice = document.createElement('div');
                notice.style.cssText = 'padding: 16px; background: #f0fdfa; border: 1px solid #4c8a89; border-radius: 8px; margin-bottom: 16px;';
                notice.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #4c8a89; font-size: 20px;"></i>
                        <div style="flex: 1;">
                            <strong style="color: #065f46; display: block; margin-bottom: 4px;">Sample Content Data</strong>
                            <p style="color: #047857; margin: 0; font-size: 14px;">These are sample LGU public safety campaign materials for demonstration. Upload your own materials to start building your content repository.</p>
                        </div>
                        <button onclick="this.closest('div[style*=\\'grid-column\\']').remove(); localStorage.setItem('content_sample_data_shown', 'true'); loadContent();" style="background: none; border: none; color: #64748b; cursor: pointer; padding: 4px 8px; font-size: 18px;" title="Dismiss">×</button>
                    </div>
                `;
                wrapper.appendChild(notice);
                container.appendChild(wrapper);
        }
        
        // Handle empty state
        if (filtered.length === 0) {
            if (hasFilters) {
                container.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <i class="fas fa-filter" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No content matches your filters</p>
                        <p style="color: #94a3b8; font-size: 14px; margin: 0 0 16px 0;">Try adjusting your search criteria or clear filters to see all content.</p>
                        <button onclick="clearFilters()" class="btn btn-secondary" style="margin-top: 8px;">Clear All Filters</button>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                        <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                        <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No content items found</p>
                        <p style="color: #94a3b8; font-size: 14px; margin: 0 0 16px 0;">Upload campaign materials to start building your LGU content repository.</p>
                        <button onclick="document.getElementById('create-content').scrollIntoView({behavior: 'smooth', block: 'start'});" class="btn btn-primary" style="margin-top: 8px;">
                            <i class="fas fa-upload"></i> Upload Campaign Material
                        </button>
                    </div>
                `;
            }
            document.getElementById('pagination').style.display = 'none';
            return;
        }
        
        // Paginate client-side - 10 items per page
        const itemsPerPage = 10;
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedItems = filtered.slice(startIndex, endIndex);
        
        renderContentGrid(container, paginatedItems);
        
        // Render pagination
        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        const paginationEl = document.getElementById('pagination');
        if (filtered.length > itemsPerPage) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentPage = 1; loadContent();" ${currentPage === 1 ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentPage--; loadContent();" ${currentPage === 1 ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${currentPage} of ${totalPages} (${filtered.length} items)</span>
                <button onclick="currentPage++; loadContent();" ${currentPage >= totalPages ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentPage = ${totalPages}; loadContent();" ${currentPage >= totalPages ? 'disabled' : ''} title="Last Page">
                    Last <i class="fas fa-angle-double-right"></i>
                </button>
            `;
        } else {
            paginationEl.style.display = 'none';
        }
    } catch (err) {
        console.error('Error loading content:', err);
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: ' + err.message + '</p>';
    }
}

// Normalize approval status - defensive function that handles all edge cases
function normalizeStatus(raw) {
    if (!raw) return "draft";
    let normalized = raw
        .toString()
        .trim()
        .toLowerCase()
        .replace(/\s+/g, "_");
    
    // Map variations to standard values
    if (normalized === 'pending' || normalized === 'under_review') {
        normalized = 'pending_review';
    }
    return normalized;
}

// Render content grid
function renderContentGrid(container, items, isTemplate = false) {
    container.innerHTML = '';
    
    items.forEach(item => {
        // Normalize status using defensive function
        const status = normalizeStatus(item.approval_status);
        
        // Debug logging to verify normalization
        console.log("NORMALIZED:", item.id, item.approval_status, "→", status);
        
        const div = document.createElement('div');
        div.className = 'content-card';
        
        const statusClass = 'status-' + status.replace(/_/g, '-');
        let statusText = status.replace(/_/g, ' ');
        // Normalize status text for display
        if (statusText === 'pending review' || statusText === 'pending') {
            statusText = 'Under Review';
        } else {
            statusText = statusText.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }
        
        const filePath = item.file_reference || item.file_path || '';
        // Construct file URL with base path - files are in public/uploads/
        let fileUrl = '';
        if (filePath) {
            if (filePath.startsWith('http')) {
                fileUrl = filePath;
            } else {
                // Remove leading slash if present, then add public path
                const cleanPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
                fileUrl = publicPath + '/' + cleanPath;
            }
        }
        
        // Get icon for content type
        const typeIcons = {
            'poster': 'fa-image',
            'video': 'fa-video',
            'guideline': 'fa-file-pdf',
            'infographic': 'fa-chart-bar',
            'image': 'fa-image',
            'file': 'fa-file'
        };
        const typeIcon = typeIcons[item.content_type] || 'fa-file';
        
        // Build action buttons
        let actionButtons = '';
        
        if (isTemplate) {
            actionButtons = `
                <button class="btn btn-primary" onclick="useTemplate(${item.id})" title="Use this approved material in your campaign">
                    <i class="fas fa-check-circle"></i> <span>Use</span>
                </button>
                <button class="btn btn-secondary" onclick="showContentDetails(${item.id})">
                    <i class="fas fa-info-circle"></i> <span>Details</span>
                </button>
                ${fileUrl ? `
                    <a href="${fileUrl}" target="_blank" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                        <i class="fas fa-eye"></i> <span>Preview</span>
                    </a>
                ` : ''}
            `;
        } else {
            // Use normalized status for button rendering - works for ALL real DB records and sample data
            // Status is already normalized above using normalizeStatus()
            
            // Always show View button
            const viewButton = `<button class="btn btn-secondary" onclick="viewContent(${item.id})" style="margin: 2px;">
                <i class="fas fa-eye"></i> <span>View</span>
            </button>`;
            
            // Edit button (only for draft or pending_review)
            const editButton = (status === 'draft' || status === 'pending_review' || status === 'under_review') 
                ? `<button class="btn btn-secondary" onclick="editContent(${item.id})" style="margin: 2px;">
                    <i class="fas fa-edit"></i> <span>Edit</span>
                </button>` 
                : '';
            
            // Archive button for all non-archived content
            const archiveButton = `<button class="btn btn-secondary" onclick="archiveContent(${item.id})" style="background: #64748b; color: white; margin: 2px;" title="Archive">
                <i class="fas fa-archive"></i> <span>Archive</span>
            </button>`;
            
            if (status === 'approved') {
                // APPROVED: Show View + Edit + Archive (row layout)
                actionButtons = `
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        ${viewButton}
                        <button class="btn btn-secondary" onclick="editContent(${item.id})" style="margin: 2px;">
                            <i class="fas fa-edit"></i> <span>Edit</span>
                        </button>
                        ${archiveButton}
                    </div>
                `;
            } else if (status === 'draft') {
                // DRAFT: Show View + Edit + Archive (row 1), Approve + Submit for Review (row 2)
                actionButtons = `
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 4px;">
                        ${viewButton}
                        ${editButton}
                        ${archiveButton}
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        <button class="btn btn-primary" onclick="approveContent(${item.id})" style="background: #059669; color: white; margin: 2px; font-size: 11px; padding: 4px 8px;">
                            <i class="fas fa-check-circle"></i> <span>Approve</span>
                        </button>
                        <button class="btn btn-secondary" onclick="updateApproval(${item.id}, 'pending_review')" style="margin: 2px; font-size: 11px; padding: 4px 8px;">
                            <i class="fas fa-paper-plane"></i> <span>Submit</span>
                        </button>
                    </div>
                `;
            } else if (status === 'pending_review') {
                // PENDING_REVIEW / UNDER_REVIEW: Show View + Edit + Archive + Approve + Reject
                actionButtons = `
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        ${viewButton}
                        ${editButton}
                        <button class="btn btn-secondary" onclick="archiveContent(${item.id})" style="background: #64748b; color: white; margin: 2px;" title="Archive">
                            <i class="fas fa-archive"></i> <span>Archive</span>
                        </button>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px;">
                        <button class="btn btn-primary" onclick="approveContent(${item.id})" style="background: #059669; color: white; margin: 2px;">
                            <i class="fas fa-check-circle"></i> <span>Approve</span>
                        </button>
                        <button class="btn btn-secondary" onclick="rejectContent(${item.id})" style="background: #dc2626; color: white; margin: 2px;">
                            <i class="fas fa-times-circle"></i> <span>Reject</span>
                        </button>
                    </div>
                `;
            } else if (status === 'rejected') {
                // REJECTED: Show View + Archive
                actionButtons = `
                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                        ${viewButton}
                        ${archiveButton}
                    </div>
                `;
            } else {
                // ANY UNKNOWN STATUS: Fallback to View only
                actionButtons = viewButton;
            }
        }
        
        div.innerHTML = `
            <div class="content-card-header">
                <span class="content-card-id">#${item.id}</span>
                <span class="content-status-badge ${statusClass}">${statusText}</span>
            </div>
            <div class="content-card-title">
                <i class="fas ${typeIcon}" style="color: #94a3b8; margin-right: 8px; font-size: 16px;"></i>
                ${item.title || 'Untitled'}
            </div>
            ${item.body ? `<div class="content-card-description">${item.body.substring(0, 120)}${item.body.length > 120 ? '...' : ''}</div>` : ''}
            <div class="content-card-meta">
                <span class="badge badge-type">
                    <i class="fas ${typeIcon}"></i>
                    ${(item.content_type || 'text').charAt(0).toUpperCase() + (item.content_type || 'text').slice(1)}
                </span>
                ${item.hazard_category ? `<span class="badge badge-hazard"><i class="fas fa-exclamation-triangle"></i> ${item.hazard_category}</span>` : ''}
                ${item.visibility ? (() => {
                    const vis = Array.isArray(item.visibility) ? item.visibility.join(', ') : item.visibility;
                    return vis !== 'public' ? `<span class="badge badge-visibility"><i class="fas fa-lock"></i> ${vis}</span>` : '';
                })() : ''}
            </div>
            ${item.intended_audience_segment || item.source ? `
                <div style="margin-bottom: 12px;">
                    ${item.intended_audience_segment ? `<div class="content-card-info"><i class="fas fa-users"></i><strong>Audience:</strong> ${item.intended_audience_segment.substring(0, 50)}${item.intended_audience_segment.length > 50 ? '...' : ''}</div>` : ''}
                    ${item.source ? `<div class="content-card-info"><i class="fas fa-tag"></i><strong>Source:</strong> ${Array.isArray(item.source) ? item.source.join(', ') : item.source}</div>` : ''}
                </div>
            ` : ''}
            ${item.linked_campaign_id || item.linked_campaign_title || item.campaign_id || item.campaign_title ? `
                <div style="margin-bottom: 12px; padding: 8px; background: #f0fdfa; border-radius: 6px; border-left: 3px solid #4c8a89;">
                    <div class="content-card-info">
                        <i class="fas fa-link" style="color: #4c8a89;"></i>
                        <strong style="color: #065f46;">Linked to:</strong> 
                        <span style="color: #047857;">${item.linked_campaign_title || item.campaign_title || `Campaign #${item.linked_campaign_id || item.campaign_id}`}</span>
                    </div>
                </div>
            ` : `
                <div style="margin-bottom: 12px; padding: 8px; background: #f8fafc; border-radius: 6px;">
                    <div class="content-card-info" style="color: #94a3b8;">
                        <i class="fas fa-unlink"></i>
                        <span>Not yet linked</span>
                    </div>
                </div>
            `}
            ${actionButtons ? `<div class="content-card-actions">${actionButtons}</div>` : ''}
        `;
        container.appendChild(div);
    });
}

// Load templates (approved content with "For Official Use" visibility) - filters from contents array
async function loadTemplates() {
    const container = document.getElementById('templatesContainer');
    const grid = container.querySelector('.library-grid');
    
    if (grid) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading approved reusable materials...</p>';
    } else {
        container.innerHTML = '<div class="library-grid"><p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading approved reusable materials...</p></div>';
    }
    
    try {
        // Ensure contents array is populated
        if (contents.length === 0) {
            await loadAllContent();
        }
        
        // Filter from combined data: approval_status === "APPROVED"
        const templateItems = contents.filter(item => {
            const approvalStatus = (item.approval_status || '').toUpperCase();
            return approvalStatus === 'APPROVED';
        });
        
        const gridEl = container.querySelector('.library-grid');
        if (!gridEl) {
            container.innerHTML = '<div class="library-grid"></div>';
        }
        
        const finalGrid = container.querySelector('.library-grid');
        
        if (!templateItems || templateItems.length === 0) {
            finalGrid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-layer-group" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No templates available</p>
                    <p style="color: #94a3b8; font-size: 14px; margin: 0 0 16px 0;">Approve content with "For Official Use" visibility to make it reusable. Uploaded materials will appear here after validation and approval.</p>
                    <button onclick="document.getElementById('create-content').scrollIntoView({behavior: 'smooth', block: 'start'});" class="btn btn-primary" style="margin-top: 8px;">
                        <i class="fas fa-upload"></i> Upload Campaign Material
                    </button>
                </div>
            `;
            document.getElementById('templatesPagination').style.display = 'none';
            return;
        }
        
        // Paginate client-side for templates
        const itemsPerPage = 6;
        const startIndex = (currentTemplatesPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedItems = templateItems.slice(startIndex, endIndex);
        
        renderContentGrid(finalGrid, paginatedItems, true);
        
        // Render pagination
        const totalPages = Math.ceil(templateItems.length / itemsPerPage);
        const paginationEl = document.getElementById('templatesPagination');
        if (templateItems.length > itemsPerPage) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentTemplatesPage = 1; loadTemplates();" ${currentTemplatesPage === 1 ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentTemplatesPage--; loadTemplates();" ${currentTemplatesPage === 1 ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${currentTemplatesPage} of ${totalPages} (${templateItems.length} approved materials)</span>
                <button onclick="currentTemplatesPage++; loadTemplates();" ${currentTemplatesPage >= totalPages ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentTemplatesPage = ${totalPages}; loadTemplates();" ${currentTemplatesPage >= totalPages ? 'disabled' : ''} title="Last Page">
                    Last <i class="fas fa-angle-double-right"></i>
                </button>
            `;
        } else {
            paginationEl.style.display = 'none';
        }
    } catch (err) {
        const gridEl = container.querySelector('.library-grid') || container;
        gridEl.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: ${err.message}</p>`;
        document.getElementById('templatesPagination').style.display = 'none';
    }
}

// Load media gallery - filters from contents array
async function loadMediaGallery() {
    const container = document.getElementById('mediaGalleryContainer');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading approved media archive...</p>';
    document.getElementById('mediaGalleryPagination').style.display = 'none';
    
    try {
        // Ensure contents array is populated
        if (contents.length === 0) {
            await loadAllContent();
        }
        
        const mediaType = document.getElementById('mediaTypeFilter').value;
        
        // Filter from combined data: approval_status === "APPROVED" AND has media content
        // Normalize status helper
        const normalizeStatus = (raw) => {
            if (!raw) return "draft";
            return raw.toString().trim().toLowerCase().replace(/\s+/g, "_");
        };
        
        const approvedItems = contents.filter(i => normalizeStatus(i.approval_status) === 'approved');
        console.log('Media Gallery - Total contents:', contents.length);
        console.log('Media Gallery - Approved items:', approvedItems.length);
        
        // If no approved items, show all items with files for debugging
        if (approvedItems.length === 0) {
            console.log('Media Gallery - No approved items found. All statuses:', 
                [...new Set(contents.map(i => i.approval_status))]);
        }
        
        let mediaItems = contents.filter(item => {
            const status = normalizeStatus(item.approval_status);
            if (status !== 'approved') return false;
            
            const fileType = item.file_type || item.mime_type || '';
            const contentType = (item.content_type || '').toLowerCase();
            const filePath = item.file_reference || item.file_path || '';
            
            // Check if it's an image or video by mime type
            const isImageMime = fileType && (fileType.startsWith('image/') || fileType.includes('image'));
            const isVideoMime = fileType && (fileType.startsWith('video/') || fileType.includes('video'));
            
            // Check by content_type field - expanded list
            const isMediaContentType = ['image', 'video', 'poster', 'infographic', 'graphic', 'photo', 'banner', 'flyer'].includes(contentType);
            
            // Check by file extension - expanded list
            const imageExts = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.svg', '.bmp', '.ico', '.tiff', '.heic'];
            const videoExts = ['.mp4', '.webm', '.mov', '.avi', '.mkv', '.wmv', '.flv', '.m4v'];
            const lowerPath = filePath.toLowerCase();
            const hasImageExt = imageExts.some(ext => lowerPath.endsWith(ext));
            const hasVideoExt = videoExts.some(ext => lowerPath.endsWith(ext));
            
            // Check if file_reference contains 'uploads/' which indicates an uploaded file
            const hasUploadedFile = filePath && (filePath.includes('uploads/') || filePath.includes('uploads\\'));
            
            // Check if there's any file at all (some content might have files without proper type indicators)
            const hasAnyFile = filePath && filePath.length > 0;
            
            // Return true if any media indicator is present
            const isMedia = isImageMime || isVideoMime || isMediaContentType || hasImageExt || hasVideoExt;
            
            // Also include if it has an uploaded file (even without explicit media type)
            // OR if it has any file path at all (be more permissive)
            return isMedia || hasUploadedFile || hasAnyFile;
        });
        
        console.log('Media Gallery - Media items found:', mediaItems.length);
        if (mediaItems.length > 0) {
            console.log('Media Gallery - First item:', mediaItems[0]);
        }
        
        // Apply additional media type filter if specified
        if (mediaType === 'image') {
            mediaItems = mediaItems.filter(item => {
                const fileType = item.file_type || item.mime_type || '';
                if (fileType) return fileType.startsWith('image/');
                const contentType = (item.content_type || '').toLowerCase();
                return contentType === 'image' || contentType === 'poster' || contentType === 'infographic';
            });
        } else if (mediaType === 'video') {
            mediaItems = mediaItems.filter(item => {
                const fileType = item.file_type || item.mime_type || '';
                if (fileType) return fileType.startsWith('video/');
                return (item.content_type || '').toLowerCase() === 'video';
            });
        }
        
        if (mediaItems.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-images" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No approved media files found</p>
                    <p style="color: #94a3b8; font-size: 14px; margin: 0;">Upload and approve images or videos to see them in the media gallery.</p>
                </div>
            `;
            document.getElementById('mediaGalleryPagination').style.display = 'none';
            return;
        }
        
        // Paginate client-side
        const itemsPerPage = 6;
        const startIndex = (currentMediaGalleryPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedItems = mediaItems.slice(startIndex, endIndex);
        
        container.innerHTML = '<div class="gallery-grid"></div>';
        const grid = container.querySelector('.gallery-grid');
        
        paginatedItems.forEach(item => {
            const div = document.createElement('div');
            div.className = 'gallery-item';
            const filePath = item.file_reference || item.file_path || '';
            // Construct file URL with base path - files are in public/uploads/
            let fileUrl = '';
            if (filePath) {
                if (filePath.startsWith('http')) {
                    fileUrl = filePath;
                } else {
                    // Remove leading slash if present, then add public path
                    const cleanPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
                    fileUrl = publicPath + '/' + cleanPath;
                }
            }
            
            if (item.content_type === 'video' || (item.file_type && item.file_type.startsWith('video/'))) {
            div.innerHTML = `
                    <video src="${fileUrl}" controls></video>
                    <div class="gallery-item-overlay">
                        <div style="font-weight: 600;">${item.title || 'Untitled'}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${item.content_type}</div>
                </div>
                `;
            } else {
            div.innerHTML = `
                    <img src="${fileUrl}" alt="${item.title || 'Image'}" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'200\\' height=\\'200\\'%3E%3Crect fill=\\'%23e2e8f0\\' width=\\'200\\' height=\\'200\\'/%3E%3Ctext fill=\\'%2394a3b8\\' font-family=\\'sans-serif\\' font-size=\\'14\\' x=\\'50%25\\' y=\\'50%25\\' text-anchor=\\'middle\\' dy=\\'.3em\\'%3ENo Preview%3C/text%3E%3C/svg%3E'">
                    <div class="gallery-item-overlay">
                        <div style="font-weight: 600;">${item.title || 'Untitled'}</div>
                        <div style="font-size: 11px; opacity: 0.9;">${item.content_type}</div>
                </div>
                `;
            }
            
            div.onclick = () => window.open(fileUrl, '_blank');
            grid.appendChild(div);
        });
        
        // Render pagination
        const totalPages = Math.ceil(mediaItems.length / itemsPerPage);
        const paginationEl = document.getElementById('mediaGalleryPagination');
        if (mediaItems.length > itemsPerPage) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentMediaGalleryPage = 1; loadMediaGallery();" ${currentMediaGalleryPage === 1 ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentMediaGalleryPage--; loadMediaGallery();" ${currentMediaGalleryPage === 1 ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${currentMediaGalleryPage} of ${totalPages} (${mediaItems.length} items)</span>
                <button onclick="currentMediaGalleryPage++; loadMediaGallery();" ${currentMediaGalleryPage >= totalPages ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentMediaGalleryPage = ${totalPages}; loadMediaGallery();" ${currentMediaGalleryPage >= totalPages ? 'disabled' : ''} title="Last Page">
                    Last <i class="fas fa-angle-double-right"></i>
                </button>
            `;
        } else {
            paginationEl.style.display = 'none';
        }
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + err.message + '</p>';
        document.getElementById('mediaGalleryPagination').style.display = 'none';
    }
}

// Load usage history
async function loadUsageHistory() {
    const container = document.getElementById('usageHistoryContainer');
    const contentSelect = document.getElementById('usageHistoryContentId');
    const contentId = contentSelect ? contentSelect.value : '';
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading usage history...</p>';
    
    try {
        const params = new URLSearchParams();
        if (contentId) params.set('content_id', contentId);
        
        const res = await fetch(apiBase + '/api/v1/content/usage?' + params.toString(), {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (!res.ok || !data.data || data.data.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <i class="fas fa-history" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No usage records found</p>
                    <p style="color: #94a3b8; font-size: 14px; margin: 0;">Log content usage in campaigns or events to track impact evaluation data.</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <table class="data-table" style="width: 100%; border-collapse: collapse; margin-top: 16px;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Content</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Campaign</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Event</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Tag</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Context</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Date</th>
                        <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0; font-weight: 600;">Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        data.data.forEach(record => {
            const date = record.created_at ? new Date(record.created_at).toLocaleString() : '—';
            html += `
                <tr style="border-bottom: 1px solid #e2e8f0;">
                    <td style="padding: 12px;">${record.content_title || '—'}</td>
                    <td style="padding: 12px;">${record.campaign_title || '—'}</td>
                    <td style="padding: 12px;">${record.event_id ? 'Event #' + record.event_id : '—'}</td>
                    <td style="padding: 12px;">${record.tag_name || '—'}</td>
                    <td style="padding: 12px;">${record.usage_context || '—'}</td>
                    <td style="padding: 12px;">${date}</td>
                    <td style="padding: 12px;">
                        <button onclick="editUsageRecord(${record.id})" style="padding: 4px 8px; background: #3b82f6; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px; font-size: 11px;" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="archiveUsageRecord(${record.id})" style="padding: 4px 8px; background: #64748b; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;" title="Archive">
                            <i class="fas fa-archive"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                </tbody>
            </table>
            <p style="margin-top: 16px; color: #64748b; font-size: 14px;"><strong>Total Records:</strong> ${data.data.length}</p>
        `;
        
        container.innerHTML = html;
    } catch (err) {
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + err.message + '</p>';
    }
}

// Edit usage record - full edit modal
async function editUsageRecord(recordId) {
    // First fetch the current record data
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage?record_id=' + recordId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        const records = data.data || [];
        const record = records.find(r => r.id == recordId) || {};
        
        // Remove existing modal if any
        const existingModal = document.getElementById('editUsageModal');
        if (existingModal) existingModal.remove();
        
        // Create edit modal
        const modal = document.createElement('div');
        modal.id = 'editUsageModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
        
        // Fetch campaigns, events, content items, and tags for dropdowns
        let campaignOptions = '<option value="">-- Select Campaign --</option>';
        let eventOptions = '<option value="">-- Select Event --</option>';
        let contentOptions = '<option value="">-- Select Content --</option>';
        let tagOptions = '<option value="">-- Select Tag --</option>';
        
        try {
            const campRes = await fetch(apiBase + '/api/v1/campaigns', { headers: { 'Authorization': 'Bearer ' + token } });
            const campData = await campRes.json();
            const campaigns = campData.data || [];
            campaigns.forEach(c => {
                const selected = record.campaign_id == c.id ? 'selected' : '';
                campaignOptions += `<option value="${c.id}" ${selected}>${c.title}</option>`;
            });
        } catch (e) { console.error('Failed to load campaigns for edit modal'); }
        
        try {
            const eventRes = await fetch(apiBase + '/api/v1/events', { headers: { 'Authorization': 'Bearer ' + token } });
            const eventData = await eventRes.json();
            const events = eventData.data || [];
            events.forEach(e => {
                const selected = record.event_id == e.event_id ? 'selected' : '';
                eventOptions += `<option value="${e.event_id}" ${selected}>${e.event_title || e.name}</option>`;
            });
        } catch (e) { console.error('Failed to load events for edit modal'); }
        
        try {
            const contentRes = await fetch(apiBase + '/api/v1/content?per_page=200', { headers: { 'Authorization': 'Bearer ' + token } });
            const contentData = await contentRes.json();
            const contentItems = contentData.data || [];
            // Usage records use content_item_id, not content_id
            const recordContentId = record.content_item_id || record.content_id;
            contentItems.forEach(c => {
                const selected = recordContentId == c.id ? 'selected' : '';
                contentOptions += `<option value="${c.id}" ${selected}>${c.title}</option>`;
            });
        } catch (e) { console.error('Failed to load content items for edit modal:', e); }
        
        // Get tag value from record (API returns tag_name from the tags table)
        const tagValue = record.tag_name || record.tag || '';
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; max-width: 500px; width: 100%; padding: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #0f172a;">Edit Usage Record</h3>
                    <button onclick="document.getElementById('editUsageModal').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
                </div>
                <form id="editUsageForm">
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #374151;">Content</label>
                        <select id="editUsageContent" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                            ${contentOptions}
                        </select>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #374151;">Campaign</label>
                        <select id="editUsageCampaign" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                            ${campaignOptions}
                        </select>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #374151;">Event</label>
                        <select id="editUsageEvent" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                            ${eventOptions}
                        </select>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #374151;">Tag</label>
                        <input type="text" id="editUsageTag" value="${tagValue}" placeholder="e.g., poster, video, social media post" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #374151;">Usage Context</label>
                        <input type="text" id="editUsageContext" value="${record.usage_context || ''}" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box;">
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" onclick="document.getElementById('editUsageModal').remove()" style="padding: 10px 20px; background: #f1f5f9; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Cancel</button>
                        <button type="submit" style="padding: 10px 20px; background: #3b82f6; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600;">Save Changes</button>
                    </div>
                </form>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Handle form submission
        document.getElementById('editUsageForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const updateData = {
                content_item_id: document.getElementById('editUsageContent').value || null,
                campaign_id: document.getElementById('editUsageCampaign').value || null,
                event_id: document.getElementById('editUsageEvent').value || null,
                tag: document.getElementById('editUsageTag').value || null,
                usage_context: document.getElementById('editUsageContext').value
            };
            
            try {
                const updateRes = await fetch(apiBase + '/api/v1/content/usage/' + recordId, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
                    body: JSON.stringify(updateData)
                });
                const updateResult = await updateRes.json();
                if (updateRes.ok) {
                    alert('Usage record updated successfully');
                    modal.remove();
                    loadUsageHistory();
                } else {
                    alert('Error: ' + (updateResult.error || 'Failed to update'));
                }
            } catch (err) {
                alert('Network error: ' + err.message);
            }
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
        
    } catch (err) {
        alert('Failed to load record: ' + err.message);
    }
}

// Archive usage record
async function archiveUsageRecord(recordId) {
    if (!confirm('Archive this usage record? It can be restored from View Archived.')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage/' + recordId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ is_archived: 1 })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Usage record archived successfully');
            loadUsageHistory();
        } else {
            alert('Error: ' + (data.error || 'Failed to archive'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Show archived usage records modal
async function showArchivedUsageRecords() {
    // Remove existing modal if any
    const existingModal = document.getElementById('archivedUsageModal');
    if (existingModal) existingModal.remove();
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage?is_archived=1', {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        let archivedRecords = [];
        if (res.ok) {
            const data = await res.json();
            archivedRecords = data.data || [];
        }
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'archivedUsageModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
        
        let tableRows = '';
        if (archivedRecords.length === 0) {
            tableRows = '<tr><td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No archived usage records found.</td></tr>';
        } else {
            archivedRecords.forEach(record => {
                const date = record.created_at ? new Date(record.created_at).toLocaleString() : '—';
                tableRows += `
                    <tr>
                        <td style="padding: 10px;">${record.content_title || '—'}</td>
                        <td style="padding: 10px;">${record.campaign_title || '—'}</td>
                        <td style="padding: 10px;">${record.event_id ? 'Event #' + record.event_id : '—'}</td>
                        <td style="padding: 10px;">${record.tag_name || '—'}</td>
                        <td style="padding: 10px;">${record.usage_context || '—'}</td>
                        <td style="padding: 10px;">${date}</td>
                        <td style="padding: 10px;">
                            <button onclick="restoreUsageRecord(${record.id})" style="padding: 4px 8px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px; font-size: 11px;">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button onclick="deleteUsageRecord(${record.id})" style="padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; max-width: 1200px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h3 style="margin: 0; font-size: 18px;"><i class="fas fa-archive"></i> Archived Usage Records</h3>
                    <button onclick="document.getElementById('archivedUsageModal').remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px;">&times;</button>
                </div>
                <div style="padding: 20px; overflow-y: auto; flex: 1;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8fafc;">
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Content</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Campaign</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Event</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Tag</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Context</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Date</th>
                                <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>${tableRows}</tbody>
                    </table>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    } catch (err) {
        alert('Error loading archived records: ' + err.message);
    }
}

// Restore usage record
async function restoreUsageRecord(recordId) {
    if (!confirm('Restore this usage record?')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage/' + recordId, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Authorization': 'Bearer ' + token },
            body: JSON.stringify({ is_archived: 0 })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Usage record restored successfully');
            showArchivedUsageRecords();
            loadUsageHistory();
        } else {
            alert('Error: ' + (data.error || 'Failed to restore'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Delete usage record permanently
async function deleteUsageRecord(recordId) {
    if (!confirm('Permanently delete this usage record? This cannot be undone.')) return;
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage/' + recordId, {
            method: 'DELETE',
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (res.ok) {
            alert('Usage record deleted permanently');
            showArchivedUsageRecords();
        } else {
            alert('Error: ' + (data.error || 'Failed to delete'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Upload Content Modal functions
function openUploadContentModal() {
    document.getElementById('uploadContentModal').style.display = 'flex';
    document.getElementById('uploadContentModalTitle').innerHTML = '<i class="fas fa-upload"></i> Upload Campaign Material';
    // Reset form
    const form = document.getElementById('uploadForm');
    if (form) form.reset();
    const statusEl = document.getElementById('uploadStatus');
    if (statusEl) statusEl.textContent = '';
    
    // Check Cloudinary status
    checkCloudinaryStatus();
}

// Check if Cloudinary is configured
async function checkCloudinaryStatus() {
    const statusDiv = document.getElementById('cloudinaryStatus');
    const statusText = document.getElementById('cloudinaryStatusText');
    if (!statusDiv || !statusText) return;
    
    // Get fresh token in case it was updated after page load
    const currentToken = localStorage.getItem('jwtToken') || token;
    if (!currentToken) {
        // No token available, show local storage message without API call
        statusDiv.style.display = 'block';
        statusDiv.style.background = '#fef3c7';
        statusDiv.style.color = '#92400e';
        statusDiv.style.border = '1px solid #fcd34d';
        statusText.textContent = 'Local storage - Files will be stored on server';
        return;
    }
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/storage-status', {
            headers: { 'Authorization': 'Bearer ' + currentToken }
        });
        const data = await res.json();
        
        statusDiv.style.display = 'block';
        if (data.cloudinary_enabled) {
            statusDiv.style.background = '#dcfce7';
            statusDiv.style.color = '#166534';
            statusDiv.style.border = '1px solid #86efac';
            statusText.textContent = 'Cloud storage enabled (Cloudinary) - Files will be stored in the cloud';
        } else {
            statusDiv.style.background = '#fef3c7';
            statusDiv.style.color = '#92400e';
            statusDiv.style.border = '1px solid #fcd34d';
            statusText.textContent = 'Local storage - Files will be stored on server';
        }
    } catch (err) {
        statusDiv.style.display = 'block';
        statusDiv.style.background = '#f1f5f9';
        statusDiv.style.color = '#64748b';
        statusDiv.style.border = '1px solid #e2e8f0';
        statusText.textContent = 'Local storage - Files will be stored on server';
    }
}

function closeUploadContentModal() {
    document.getElementById('uploadContentModal').style.display = 'none';
}

// Open edit content in modal
function openEditContentModal(contentId) {
    document.getElementById('uploadContentModal').style.display = 'flex';
    document.getElementById('uploadContentModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Content';
    // Load content data into form
    editContent(contentId);
}

// Show Archived Content Modal
async function showArchivedContent() {
    // Remove existing modal if any to prevent duplication
    const existingModal = document.getElementById('archivedContentModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    try {
        // Fetch archived content directly from API - this has the most accurate data
        // Add timestamp to prevent caching and per_page=200 to get all items
        const timestamp = new Date().getTime();
        const res = await fetch(apiBase + '/api/v1/content?approval_status=archived&include_archived=true&per_page=200&_t=' + timestamp, {
            headers: { 
                'Authorization': 'Bearer ' + token, 
                'Accept': 'application/json',
                'Cache-Control': 'no-cache, no-store, must-revalidate'
            }
        });
        
        let archivedContent = [];
        
        if (res.ok) {
            const data = await res.json();
            archivedContent = data.data || [];
            console.log('Archived content from API:', archivedContent.length, archivedContent);
        } else {
            console.error('Failed to fetch archived content from API:', res.status);
            // Fallback to local array
            const normalizeStatus = (raw) => {
                if (!raw) return "draft";
                return raw.toString().trim().toLowerCase().replace(/\s+/g, "_");
            };
            archivedContent = contents.filter(item => normalizeStatus(item.approval_status) === 'archived');
            console.log('Archived content from local array (fallback):', archivedContent.length);
        }
        
        console.log('Total archived content:', archivedContent.length);
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'archivedContentModal';
        modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
        
        let tableRows = '';
        if (archivedContent.length === 0) {
            tableRows = '<tr><td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">No archived content found.</td></tr>';
        } else {
            archivedContent.forEach(item => {
                // Get content type - check multiple possible field names and mime_type
                let contentType = item.content_type || item.type || '';
                
                // If content_type is empty, try to determine from mime_type
                if (!contentType && item.mime_type) {
                    if (item.mime_type.startsWith('image/')) contentType = 'image';
                    else if (item.mime_type.startsWith('video/')) contentType = 'video';
                    else if (item.mime_type.startsWith('audio/')) contentType = 'audio';
                    else if (item.mime_type.includes('pdf')) contentType = 'document';
                    else if (item.mime_type.includes('presentation') || item.mime_type.includes('powerpoint')) contentType = 'presentation';
                    else if (item.mime_type.includes('document') || item.mime_type.includes('word')) contentType = 'document';
                }
                
                // Map content types to display names
                const typeMap = {
                    'infographic': 'Infographic',
                    'video': 'Video',
                    'poster': 'Poster',
                    'flyer': 'Flyer',
                    'social_media': 'Social Media',
                    'article': 'Article',
                    'document': 'Document',
                    'image': 'Image',
                    'audio': 'Audio',
                    'presentation': 'Presentation',
                    'template': 'Template',
                    'text': 'Text'
                };
                const displayType = typeMap[(contentType || '').toLowerCase()] || (contentType ? contentType.charAt(0).toUpperCase() + contentType.slice(1) : 'Not Set');
                
                // Get hazard category
                const hazardCategory = item.hazard_category || item.hazard || '';
                
                tableRows += `
                    <tr>
                        <td>#${item.id}</td>
                        <td><strong>${item.title || 'Untitled'}</strong></td>
                        <td><span style="background: #e0f2fe; color: #1d4ed8; padding: 4px 8px; border-radius: 4px; font-size: 12px;">${displayType}</span></td>
                        <td>${hazardCategory || '—'}</td>
                        <td>
                            <button onclick="viewContent(${item.id})" style="padding: 4px 8px; background: #6366f1; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px; font-size: 11px;">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button onclick="restoreContent(${item.id})" style="padding: 4px 8px; background: #10b981; color: white; border: none; border-radius: 4px; cursor: pointer; margin-right: 4px; font-size: 11px;">
                                <i class="fas fa-undo"></i> Restore
                            </button>
                            <button onclick="deleteContentPermanent(${item.id})" style="padding: 4px 8px; background: #ef4444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 11px;">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                `;
            });
        }
        
        modal.innerHTML = `
            <div style="background: white; border-radius: 12px; max-width: 1200px; width: 100%; max-height: 90vh; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                <div style="background: linear-gradient(135deg, #4c8a89 0%, #2d5a59 100%); color: white; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <h2 style="margin: 0; font-size: 20px; font-weight: 700;">
                        <i class="fas fa-archive"></i> Archived Content
                    </h2>
                    <button onclick="document.getElementById('archivedContentModal').remove()" style="background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px;">
                        &times;
                    </button>
                </div>
                <div style="padding: 20px; overflow-y: auto; flex: 1;">
                    <p style="margin: 0 0 16px 0; color: #64748b; font-size: 14px;">
                        Archived content is hidden from the main library. You can restore it or delete it permanently.
                    </p>
                    <div style="overflow-x: auto;">
                        <table class="data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">ID</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Title</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Type</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Category</th>
                                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${tableRows}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    } catch (err) {
        alert('Failed to load archived content: ' + err.message);
    }
}

// Restore Content from Archive
async function restoreContent(contentId) {
    const confirmed = await customConfirm('Restore this content from archive? It will be set to Pending Review status.', 'Restore Content');
    if (!confirmed) {
        return;
    }
    
    try {
        console.log('Restoring content ID:', contentId);
        
        // First check the current status of the content
        const checkRes = await fetch(apiBase + '/api/v1/content/' + contentId, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        
        if (checkRes.ok) {
            const checkData = await checkRes.json();
            const currentStatus = (checkData.data?.approval_status || checkData.approval_status || '').toLowerCase();
            console.log('Current content status:', currentStatus);
            
            if (currentStatus !== 'archived') {
                await customAlert('This content has already been restored. Current status: ' + currentStatus.replace('_', ' ').toUpperCase(), 'Already Restored');
                // Refresh the archived modal to remove this item
                const archivedModal = document.getElementById('archivedContentModal');
                if (archivedModal) archivedModal.remove();
                showArchivedContent();
                return;
            }
        }
        
        // Use pending_review status since the API only allows: pending_review, approved, rejected, archived
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '/approval', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token 
            },
            body: JSON.stringify({
                approval_status: 'pending_review',
                approval_notes: 'Restored from archive - needs review'
            })
        });
        
        const data = await res.json();
        console.log('Restore response:', res.status, data);
        
        if (!res.ok) {
            // Check if the error is because it's already restored
            if (data.error && data.error.includes('Pending content')) {
                await customAlert('This content has already been restored and is now in Pending Review status.', 'Already Restored');
                const archivedModal = document.getElementById('archivedContentModal');
                if (archivedModal) archivedModal.remove();
                contents = [];
                await loadAllContent();
                await loadContent(true);
                return;
            }
            await customAlert('Error: ' + (data.error || 'Failed to restore content'), 'Error');
            return;
        }
        
        await customAlert('Content restored successfully! It is now in Pending Review status.', 'Success');
        
        // Close the archived modal
        const archivedModal = document.getElementById('archivedContentModal');
        if (archivedModal) archivedModal.remove();
        
        // Reload all content to refresh the library with force refresh
        console.log('Reloading content after restore...');
        
        // Force clear the contents array to ensure fresh data
        contents = [];
        
        await loadAllContent();
        await loadContent(true);  // Force refresh to get latest content_type from API
        loadTemplates();
        loadMediaGallery();
        
        console.log('Content refreshed after restore');
        
    } catch (err) {
        console.error('Restore error:', err);
        await customAlert('Failed to restore content: ' + err.message, 'Error');
    }
}

// Delete Content Permanently
async function deleteContentPermanent(contentId) {
    const confirmed = await customConfirm('Permanently delete this content? This action cannot be undone.', 'Delete Content');
    if (!confirmed) {
        return;
    }
    
    try {
        // Use force_delete parameter to allow deletion of archived content
        const res = await fetch(apiBase + '/api/v1/content/' + contentId + '?force_delete=true', {
            method: 'DELETE',
            headers: {
                'Authorization': 'Bearer ' + token
            }
        });
        
        const data = await res.json();
        if (!res.ok) {
            await customAlert('Error: ' + (data.error || 'Failed to delete content'), 'Error');
            return;
        }
        
        await customAlert('Content deleted permanently!', 'Success');
        showArchivedContent(); // Refresh the modal
    } catch (err) {
        await customAlert('Failed to delete content: ' + err.message, 'Error');
    }
}

// Load content on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded - Initializing Content Repository...');
    loadAnnouncements();
    
    // Initialize Audience Dropdown
    function initAudienceDropdown() {
        const trigger = document.getElementById('audienceDropdownTrigger');
        const menu = document.getElementById('audienceDropdownMenu');
        const tagsContainer = document.getElementById('intended_audience_tags');
        const textSpan = document.getElementById('audienceDropdownText');
        
        if (!trigger || !menu) return;
        
        // Toggle dropdown
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            const isOpen = menu.style.display === 'block';
            menu.style.display = isOpen ? 'none' : 'block';
            trigger.querySelector('i').style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
            trigger.style.borderColor = isOpen ? '#e2e8f0' : '#4c8a89';
        });
        
        // Close on outside click
        document.addEventListener('click', function(e) {
            if (!trigger.contains(e.target) && !menu.contains(e.target)) {
                menu.style.display = 'none';
                trigger.querySelector('i').style.transform = 'rotate(0deg)';
                trigger.style.borderColor = '#e2e8f0';
            }
        });
        
        // Handle checkbox changes
        const options = menu.querySelectorAll('.audience-option');
        options.forEach(option => {
            option.style.cssText = 'padding: 10px 12px; cursor: pointer; border-radius: 8px; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; transition: all 0.2s;';
            option.addEventListener('mouseenter', function() { this.style.background = '#f1f5f9'; });
            option.addEventListener('mouseleave', function() { this.style.background = 'transparent'; });
            
            const checkbox = option.querySelector('input[type="checkbox"]');
            option.addEventListener('click', function(e) {
                if (e.target !== checkbox) {
                    checkbox.checked = !checkbox.checked;
                }
                updateAudienceTags();
            });
        });
        
        function updateAudienceTags() {
            const checked = menu.querySelectorAll('input[type="checkbox"]:checked');
            tagsContainer.innerHTML = '';
            
            checked.forEach(cb => {
                const tag = document.createElement('span');
                tag.className = 'multi-select-tag';
                tag.innerHTML = `${cb.value} <span class="multi-select-tag-remove" data-value="${cb.value}">&times;</span>`;
                tag.querySelector('.multi-select-tag-remove').addEventListener('click', function(e) {
                    e.stopPropagation();
                    const val = this.dataset.value;
                    const targetCb = menu.querySelector(`input[value="${val}"]`);
                    if (targetCb) targetCb.checked = false;
                    updateAudienceTags();
                });
                tagsContainer.appendChild(tag);
            });
            
            textSpan.textContent = checked.length > 0 ? `${checked.length} selected` : 'Select audiences...';
        }
    }
    
    initAudienceDropdown();
    
    // Initialize multi-select components - try multiple times to ensure DOM is ready
    function initMultiSelects() {
        const sourceSelect = document.getElementById('sourceSelect');
        const visibilitySelect = document.getElementById('visibilitySelect');
        
        console.log('Initializing multi-selects:', {
            sourceSelect: !!sourceSelect,
            visibilitySelect: !!visibilitySelect
        });
        
        if (sourceSelect && !sourceSelect.dataset.multiSelectInit) {
            console.log('Initializing sourceSelect multi-select');
            initMultiSelectEnhanced('sourceSelect');
        }
        if (visibilitySelect && !visibilitySelect.dataset.multiSelectInit) {
            console.log('Initializing visibilitySelect multi-select');
            initMultiSelectEnhanced('visibilitySelect');
        }
    }
    
    // Try immediately
    initMultiSelects();
    
    // Try again after a short delay
    setTimeout(initMultiSelects, 100);
    setTimeout(initMultiSelects, 300);
    
    // Load campaigns and content
    console.log('Loading campaigns and content...');
    loadCampaigns();
    // Load content views - loadContent() will call loadAllContent() internally
    loadContent().then(() => {
        loadTemplates();
        loadMediaGallery();
    }).catch(err => {
        console.error('Error loading content:', err);
        // Still try to load other views even if loadContent fails
        loadTemplates();
        loadMediaGallery();
    });
    
    // Load dropdowns for usage form - wait for content to be loaded first
    console.log('Loading usage form dropdowns...');
    // Load campaigns and events immediately (they don't depend on contents array)
    loadCampaignsForUsage();
    loadEventsForUsage();
    // Load approved content after contents array is populated
    setTimeout(() => {
        loadApprovedContentForUsage();
        loadApprovedContentForUsageHistory(); // Also populate usage history dropdown
    }, 500);
});
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
