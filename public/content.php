<?php
$pageTitle = 'Content Management';
require_once __DIR__ . '/../header/includes/path_helper.php';
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
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="module-content" data-module="content">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <main class="main-content-wrapper">
<style>
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
    .section-title {
        font-size: 20px;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 16px 0;
        padding-bottom: 12px;
        border-bottom: 2px solid #f1f5f9;
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
    .status-pending_review { background: #fef3c7; color: #92400e; }
    .status-pending { background: #fef3c7; color: #92400e; }
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
</style>

<div class="content-page">
    <div class="page-header">
        <h1>Content Repository</h1>
        <p>Upload, manage, approve, and organize campaign content materials</p>
    </div>


    <!-- Create New Content -->
    <section id="create-content" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Create New Content</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Upload new content materials for campaigns</p>
            </div>
            <button type="button" onclick="showContentHelp()" class="btn btn-secondary" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-question-circle"></i> How It Works
            </button>
        </div>
        <form id="uploadForm" class="form-grid" enctype="multipart/form-data">
            <div class="form-field">
                <label>File *</label>
                <input type="file" name="file" required>
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
                <textarea name="description" rows="3" placeholder="Content description..."></textarea>
            </div>
            <div class="form-field">
                <label>Hazard Category</label>
                <select name="hazard_category">
                    <option value="">Select...</option>
                    <option value="Fire">Fire</option>
                    <option value="Flood">Flood</option>
                    <option value="Earthquake">Earthquake</option>
                    <option value="Typhoon">Typhoon</option>
                    <option value="Health">Health</option>
                    <option value="Emergency">Emergency</option>
                </select>
            </div>
            <div class="form-field">
                <label>Intended Audience</label>
                <input type="text" name="intended_audience_segment" id="intendedAudience" list="audienceSuggestions" placeholder="e.g., youth, households, schools...">
                <datalist id="audienceSuggestions">
                    <option value="general public">
                    <option value="households">
                    <option value="residential areas">
                    <option value="youth">
                    <option value="teenagers">
                    <option value="students">
                    <option value="schools">
                    <option value="teachers">
                    <option value="senior citizens">
                    <option value="elderly">
                    <option value="caregivers">
                    <option value="families">
                    <option value="flood-prone areas">
                    <option value="coastal communities">
                    <option value="commercial districts">
                    <option value="workplaces">
                    <option value="community volunteers">
                    <option value="barangay health workers">
                    <option value="building administrators">
                    <option value="trainers">
                    <option value="facilitators">
                    <option value="barangay officials">
                    <option value="residential buildings">
                    <option value="commercial buildings">
                </datalist>
            </div>
            <div class="form-field">
                <label>Source</label>
                <select name="source">
                    <option value="">Select...</option>
                    <option value="Inspection-based">Inspection-based</option>
                    <option value="Training-based">Training-based</option>
                    <option value="Barangay-created">Barangay-created</option>
                </select>
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
                <label>Tags (comma separated)</label>
                <input type="text" name="tags" placeholder="fire,poster,safety">
            </div>
        </form>
        <button type="submit" form="uploadForm" class="btn btn-primary" style="margin-top:16px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-upload"></i> Upload Content
        </button>
        <div class="status" id="uploadStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Search & Filter Section -->
    <section id="content-library" class="card filter-section" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="section-title" style="margin: 0;">Search & Filter</h2>
            <button type="button" onclick="showSearchFilterHelp()" class="btn btn-secondary" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fas fa-question-circle"></i> How It Works
            </button>
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
                <label for="filterOnlyApproved" style="margin: 0; font-weight: 500; text-transform: none;">Only show approved content</label>
            </div>
            <button class="btn btn-secondary" onclick="clearFilters()" style="margin-left: auto;">Clear All</button>
        </div>
        
        <div style="margin-top: 24px;">
        <div class="library-grid" id="library"></div>
            <div class="pagination" id="pagination" style="display: none;"></div>
        </div>
    </section>

    <!-- Content Templates -->
    <section id="templates" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #f1f5f9;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0; display: inline-flex; align-items: center; gap: 10px;">
                    <i class="fas fa-layer-group" style="color: #4c8a89; font-size: 24px;"></i>
                    Content Templates
                </h2>
                <p style="color: #64748b; margin: 8px 0 0 0; font-size: 14px;">Browse approved content that can be reused as templates for new campaigns</p>
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

    <!-- Media Gallery -->
    <section id="media-gallery" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Media Gallery</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Visual gallery of approved media files (images and videos)</p>
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

    <!-- Record Content Usage -->
    <section id="record-usage" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Record Content Usage</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Track when and where content is used in campaigns or events</p>
            </div>
        </div>
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
        <button type="submit" form="usageForm" class="btn btn-primary" style="margin-top:16px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-plus-circle"></i> Record Usage
        </button>
        <div class="status" id="usageStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Content Usage History -->
    <section id="usage-history" class="card" style="margin-bottom:24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Content Usage History</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">View historical usage records for content items</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <input type="number" id="usageHistoryContentId" placeholder="Content ID (optional)" style="padding: 8px 12px; border: 2px solid #e2e8f0; border-radius: 6px; width: 150px;">
                <button class="btn btn-secondary" onclick="loadUsageHistory()" style="padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fas fa-history"></i> Load History
                </button>
            </div>
        </div>
        <div id="usageHistoryContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 20px;">Enter a Content ID or leave blank to see all usage history</p>
        </div>
    </section>
</div>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
const publicPath = '<?php echo $publicPath; ?>';

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
            <h3 style="margin: 0; color: #0f172a; font-size: 20px;">💡 Content Module - How It Works</h3>
            <button onclick="this.closest('div[style*=\\'position: fixed\\']').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>
        <div style="color: #475569; line-height: 1.8; font-size: 14px;">
            <div style="margin-bottom: 24px; padding: 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #4c8a89;">
                <strong style="color: #065f46; display: block; margin-bottom: 12px; font-size: 16px;">📋 Complete Content Workflow:</strong>
                <ol style="margin: 0; padding-left: 20px; line-height: 2;">
                    <li><strong>Upload Content</strong> - Fill out all fields and upload your file</li>
                    <li><strong>Content Saved as Draft</strong> - New content starts as "Draft" status</li>
                    <li><strong>Submit for Review</strong> - Change status to "Pending Review" for approval</li>
                    <li><strong>Admin Approves</strong> - Administrator reviews and approves content</li>
                    <li><strong>Content Available</strong> - Approved content can be used in campaigns</li>
                    <li><strong>Track Usage</strong> - Record when content is used in campaigns/events</li>
                </ol>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">📋 Required Fields:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>File</strong> - Upload image, video, PDF, or other file</li>
                    <li><strong>Title</strong> - Descriptive name for the content</li>
                    <li><strong>Content Type</strong> - Poster, Video, Guideline, Infographic, Image, or File</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">💡 Pro Tips:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Fill out Hazard Category and Intended Audience for better organization</li>
                    <li>Add tags to make content easier to find later</li>
                    <li>Only approved content can be attached to campaigns</li>
                    <li>Use Templates section to reuse approved content</li>
                    <li>Media Gallery shows visual preview of images and videos</li>
                </ul>
            </div>
            
            <div style="background: #fff7ed; padding: 12px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <strong style="color: #92400e;">⚠️ Important Notes:</strong>
                <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 13px;">
                    <li>Content starts as "Draft" and must be submitted for review</li>
                    <li>Workflow: Draft → Pending Review → Approved/Rejected</li>
                    <li>Only approved content appears in Templates and can be attached to campaigns</li>
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
                    <li><strong>Only Approved</strong> - Quick checkbox to show only approved content</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px; padding: 16px; background: #f0fdfa; border-radius: 8px; border-left: 4px solid #4c8a89;">
                <strong style="color: #065f46; display: block; margin-bottom: 12px; font-size: 16px;">💡 Usage Tips:</strong>
                <ul style="margin: 0; padding-left: 20px; line-height: 2;">
                    <li>Combine multiple filters for precise results</li>
                    <li>Search automatically runs 500ms after you stop typing</li>
                    <li>Use "Clear All" to reset all filters</li>
                    <li>Content Library shows filtered results below</li>
                    <li>Click "Approve" or "Reject" on pending review items</li>
                    <li>Click "Archive" to archive content (admin only)</li>
                </ul>
            </div>
            
            <div style="margin-bottom: 24px;">
                <strong style="color: #0f172a; display: block; margin-bottom: 12px; font-size: 16px;">🔗 Connections to Other Sections:</strong>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Templates</strong> - Automatically shows only approved content</li>
                    <li><strong>Media Gallery</strong> - Shows approved images and videos from filtered results</li>
                    <li><strong>Campaigns</strong> - Only approved content can be attached to campaigns</li>
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

// Upload form handler
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
            statusEl.textContent = '✓ Content uploaded successfully! Refreshing all sections...';
            statusEl.style.color = '#059669';
            e.target.reset();
            
            // Refresh all sections to show new content
            setTimeout(() => {
            loadContent();
                loadTemplates();
                loadMediaGallery();
                statusEl.textContent = '✓ Content uploaded successfully!';
            }, 500);
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
        const data = await res.json();
        if (res.ok) {
            currentTemplatesPage = 1; // Reset to first page when content is approved/rejected
            loadContent();
            loadTemplates();
            loadMediaGallery();
        } else {
            alert('Error: ' + (data.error || 'Failed to update approval status'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Archive content
async function archiveContent(contentId) {
    if (!confirm('Are you sure you want to archive this content? Archived content will be hidden by default.')) {
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
            currentTemplatesPage = 1; // Reset to first page when content is approved/rejected
            loadContent();
            loadTemplates();
            loadMediaGallery();
        } else {
            alert('Error: ' + (data.error || 'Failed to archive content'));
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
}

// Show content details modal
async function showContentDetails(contentId) {
    try {
        const res = await fetch(apiBase + '/api/v1/content/' + contentId, {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (!res.ok) {
            const error = await res.json();
            alert('Error: ' + (error.error || 'Failed to load content details'));
            return;
        }
        
        const data = await res.json();
        const item = data.data;
        
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
        
        // Function to close modal
        const closeModal = () => {
            modal.remove();
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
    // Show loading state
    const statusEl = document.getElementById('uploadStatus');
    statusEl.textContent = 'Loading template...';
    statusEl.style.color = '#64748b';
    
    // Scroll to create content section
    document.getElementById('create-content').scrollIntoView({ behavior: 'smooth', block: 'start' });
    
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
            if (form.elements['title']) form.elements['title'].value = 'Copy of ' + (item.title || '');
            if (form.elements['description']) form.elements['description'].value = item.body || '';
            if (form.elements['content_type']) form.elements['content_type'].value = item.content_type || '';
            if (form.elements['hazard_category']) form.elements['hazard_category'].value = item.hazard_category || '';
            if (form.elements['intended_audience_segment']) form.elements['intended_audience_segment'].value = item.intended_audience_segment || '';
            if (form.elements['source']) form.elements['source'].value = item.source || '';
            if (form.elements['visibility']) form.elements['visibility'].value = item.visibility || 'public';
            
            statusEl.textContent = '✓ Template loaded! Update the title and upload a new file.';
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
        statusEl.textContent = '✗ Error loading template: ' + err.message;
        statusEl.style.color = '#dc2626';
    });
}

// Load content library (with filters)
async function loadContent() {
    const container = document.getElementById('library');
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading content...</p>';
    
    try {
        const queryString = buildFilterQuery();
        const apiUrl = apiBase + '/api/v1/content?' + queryString;
        
        const res = await fetch(apiUrl, { 
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            } 
        });
        
        if (!res.ok) {
            const data = await res.json();
            container.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: ${data.error || 'Failed to load content'}</p>`;
            return;
        }
        
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: Server returned non-JSON response.</p>';
            return;
        }
        
        const data = await res.json();
        container.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">No content items found. Adjust your filters or upload new content!</p>';
            document.getElementById('pagination').style.display = 'none';
            return;
        }
        
        renderContentGrid(container, data.data);
        
        // Render pagination (always show if more than 6 items)
        const paginationEl = document.getElementById('pagination');
        if (data.pagination && data.pagination.total > 6) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentPage = 1; loadContent();" ${!data.pagination.has_prev ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentPage--; loadContent();" ${!data.pagination.has_prev ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${data.pagination.current_page} of ${data.pagination.total_pages} (${data.pagination.total} items)</span>
                <button onclick="currentPage++; loadContent();" ${!data.pagination.has_next ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentPage = ${data.pagination.total_pages}; loadContent();" ${!data.pagination.has_next ? 'disabled' : ''} title="Last Page">
                    Last <i class="fas fa-angle-double-right"></i>
                </button>
            `;
        } else {
            paginationEl.style.display = 'none';
        }
        
    } catch (err) {
        console.error('Error loading content:', err);
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load content: ' + err.message + '</p>';
    }
}

// Render content grid
function renderContentGrid(container, items, isTemplate = false) {
    container.innerHTML = '';
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'content-card';
        
        const status = item.approval_status || 'draft';
        const statusClass = 'status-' + status.replace('_', '-');
        let statusText = status.replace(/_/g, ' ');
        statusText = statusText.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        
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
                <button class="btn btn-primary" onclick="useTemplate(${item.id})">
                    <i class="fas fa-check-circle"></i> <span>Use Template</span>
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
            if (item.approval_status === 'pending_review' || item.approval_status === 'pending') {
                actionButtons = `
                    <button class="btn btn-primary" onclick="updateApproval(${item.id}, 'approved')" style="background: #059669; color: white;">
                        <i class="fas fa-check-circle"></i> <span>Approve</span>
                    </button>
                    <button class="btn btn-secondary" onclick="updateApproval(${item.id}, 'rejected')" style="background: #dc2626; color: white;">
                        <i class="fas fa-times-circle"></i> <span>Reject</span>
                    </button>
                `;
            } else if (item.approval_status === 'draft') {
                actionButtons = `
                    <button class="btn btn-primary" onclick="updateApproval(${item.id}, 'pending_review')">
                        <i class="fas fa-paper-plane"></i> <span>Submit for Review</span>
                    </button>
                `;
            }
            
            if (fileUrl) {
                actionButtons += `
                    <a href="${fileUrl}" target="_blank" class="btn btn-secondary" style="text-align: center; text-decoration: none;">
                        <i class="fas fa-eye"></i> <span>View</span>
                    </a>
                `;
            }
            
            actionButtons += `
                <button class="btn btn-secondary" onclick="showContentDetails(${item.id})">
                    <i class="fas fa-info-circle"></i> <span>Details</span>
                </button>
            `;
            
            if (item.approval_status !== 'archived' && item.approval_status !== 'draft') {
                actionButtons += `
                    <button class="btn btn-secondary" onclick="archiveContent(${item.id})" style="background: #64748b; color: white;" title="Archive">
                        <i class="fas fa-archive"></i> <span>Archive</span>
                    </button>
                `;
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
                ${item.visibility && item.visibility !== 'public' ? `<span class="badge badge-visibility"><i class="fas fa-lock"></i> ${item.visibility}</span>` : ''}
            </div>
            ${item.intended_audience_segment || item.source ? `
                <div style="margin-bottom: 12px;">
                    ${item.intended_audience_segment ? `<div class="content-card-info"><i class="fas fa-users"></i><strong>Audience:</strong> ${item.intended_audience_segment.substring(0, 50)}${item.intended_audience_segment.length > 50 ? '...' : ''}</div>` : ''}
                    ${item.source ? `<div class="content-card-info"><i class="fas fa-tag"></i><strong>Source:</strong> ${item.source}</div>` : ''}
                </div>
            ` : ''}
            ${actionButtons ? `<div class="content-card-actions">${actionButtons}</div>` : ''}
        `;
        container.appendChild(div);
    });
}

// Load templates (approved content only)
async function loadTemplates() {
    const container = document.getElementById('templatesContainer');
    const grid = container.querySelector('.library-grid');
    
    if (grid) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading templates...</p>';
    } else {
        container.innerHTML = '<div class="library-grid"><p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading templates...</p></div>';
    }
    
    try {
        // Use pagination with 6 items per page
        const params = new URLSearchParams({
            approval_status: 'approved',
            per_page: 6,
            page: currentTemplatesPage
        });
        
        const res = await fetch(apiBase + '/api/v1/content?' + params.toString(), {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (!res.ok) {
            const errorData = await res.json().catch(() => ({ error: 'Failed to load templates' }));
            const gridEl = container.querySelector('.library-grid') || container;
            gridEl.innerHTML = `<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Error: ${errorData.error || 'Failed to load templates'}</p>`;
            document.getElementById('templatesPagination').style.display = 'none';
            return;
        }
        
        const data = await res.json();
        
        const gridEl = container.querySelector('.library-grid');
        if (!gridEl) {
            container.innerHTML = '<div class="library-grid"></div>';
        }
        
        const finalGrid = container.querySelector('.library-grid');
        
        if (!data.data || data.data.length === 0) {
            finalGrid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px;">
                    <i class="fas fa-inbox" style="font-size: 64px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #64748b; font-size: 16px; margin: 0 0 8px 0; font-weight: 600;">No Approved Templates Available</p>
                    <p style="color: #94a3b8; font-size: 14px; margin: 0;">Approve some content first to make it available as a template!</p>
                </div>
            `;
            document.getElementById('templatesPagination').style.display = 'none';
            return;
        }
        
        renderContentGrid(finalGrid, data.data, true);
        
        // Render pagination for templates (always show if more than 6 items)
        const paginationEl = document.getElementById('templatesPagination');
        if (data.pagination && data.pagination.total > 6) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentTemplatesPage = 1; loadTemplates();" ${!data.pagination.has_prev ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentTemplatesPage--; loadTemplates();" ${!data.pagination.has_prev ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${data.pagination.current_page} of ${data.pagination.total_pages} (${data.pagination.total} templates)</span>
                <button onclick="currentTemplatesPage++; loadTemplates();" ${!data.pagination.has_next ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentTemplatesPage = ${data.pagination.total_pages}; loadTemplates();" ${!data.pagination.has_next ? 'disabled' : ''} title="Last Page">
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

// Load media gallery
async function loadMediaGallery() {
    const container = document.getElementById('mediaGalleryContainer');
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading media gallery...</p>';
    document.getElementById('mediaGalleryPagination').style.display = 'none';
    
    try {
        const mediaType = document.getElementById('mediaTypeFilter').value;
        
        // Fetch 6 items per page - we'll filter client-side for media types
        const params = new URLSearchParams({
            approval_status: 'approved',
            per_page: 6,
            page: currentMediaGalleryPage
        });
        
        const res = await fetch(apiBase + '/api/v1/content?' + params.toString(), {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        
        if (!res.ok) {
            const errorData = await res.json().catch(() => ({ error: 'Failed to load media gallery' }));
            container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 40px;">Error: ' + (errorData.error || 'Failed to load media gallery') + '</p>';
            document.getElementById('mediaGalleryPagination').style.display = 'none';
            return;
        }
        
        const data = await res.json();
        
        // Filter to only show images and videos (and posters/infographics)
        let mediaItems = (data.data || []).filter(item => 
            item.content_type === 'image' || item.content_type === 'video' || 
            item.content_type === 'poster' || item.content_type === 'infographic'
        );
        
        // Apply additional media type filter if specified
        if (mediaType === 'image') {
            mediaItems = mediaItems.filter(item => 
                item.content_type === 'image' || item.content_type === 'poster' || item.content_type === 'infographic'
            );
        } else if (mediaType === 'video') {
            mediaItems = mediaItems.filter(item => item.content_type === 'video');
        }
        
        if (mediaItems.length === 0 && currentMediaGalleryPage === 1) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No media files found.</p>';
            document.getElementById('mediaGalleryPagination').style.display = 'none';
            return;
        }
        
        // If no items on this page, try going back or show empty state
        if (mediaItems.length === 0) {
            if (currentMediaGalleryPage > 1) {
                currentMediaGalleryPage = 1;
                loadMediaGallery();
                return;
            }
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No media files found.</p>';
            document.getElementById('mediaGalleryPagination').style.display = 'none';
            return;
        }
        
        container.innerHTML = '<div class="gallery-grid"></div>';
        const grid = container.querySelector('.gallery-grid');
        
        mediaItems.forEach(item => {
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
            
            if (item.content_type === 'video') {
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
        
        // Show pagination if there are more pages
        const paginationEl = document.getElementById('mediaGalleryPagination');
        if (data.pagination && (data.pagination.has_next || currentMediaGalleryPage > 1)) {
            paginationEl.style.display = 'flex';
            paginationEl.innerHTML = `
                <button onclick="currentMediaGalleryPage = 1; loadMediaGallery();" ${currentMediaGalleryPage === 1 ? 'disabled' : ''} title="First Page">
                    <i class="fas fa-angle-double-left"></i> First
                </button>
                <button onclick="currentMediaGalleryPage--; loadMediaGallery();" ${currentMediaGalleryPage === 1 ? 'disabled' : ''} title="Previous Page">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span class="pagination-info">Page ${data.pagination.current_page} of ${data.pagination.total_pages} (${data.pagination.total} items)</span>
                <button onclick="currentMediaGalleryPage++; loadMediaGallery();" ${!data.pagination.has_next ? 'disabled' : ''} title="Next Page">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="currentMediaGalleryPage = ${data.pagination.total_pages}; loadMediaGallery();" ${!data.pagination.has_next ? 'disabled' : ''} title="Last Page">
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
    const contentId = document.getElementById('usageHistoryContentId').value;
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading usage history...</p>';
    
    try {
        const params = new URLSearchParams();
        if (contentId) params.set('content_id', contentId);
        
        const res = await fetch(apiBase + '/api/v1/content/usage?' + params.toString(), {
            headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' }
        });
        const data = await res.json();
        
        if (!res.ok || !data.data || data.data.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No usage history found.</p>';
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

// Load content on page load
document.addEventListener('DOMContentLoaded', function() {
loadContent();
    loadTemplates();
    loadMediaGallery();
});
</script>
    
    <?php include __DIR__ . '/../header/includes/footer.php'; ?>
    </main>
