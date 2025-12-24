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
    
    @media (min-width: 769px) {
        .sidebar {
            transform: translateX(0) !important;
        }
    }
    
    @media (max-width: 1024px) {
        .main-content-wrapper {
            margin-left: 280px !important;
        }
    }
    
    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0 !important;
        }
        .sidebar {
            transform: translateX(-100%);
        }
        .sidebar.sidebar-open {
            transform: translateX(0);
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
    
    .content-card.newly-uploaded {
        position: relative;
        overflow: visible;
    }
    
    @keyframes pulseHighlight {
        0%, 100% {
            box-shadow: 0 0 0 2px rgba(76, 138, 137, 0.2), 0 4px 12px rgba(76, 138, 137, 0.15);
        }
        50% {
            box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.3), 0 6px 16px rgba(76, 138, 137, 0.25);
        }
    }
    
    @keyframes pulseBadge {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 2px 4px rgba(76, 138, 137, 0.3);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(76, 138, 137, 0.4);
        }
    }
    
    .content-card.newly-uploaded {
        position: relative;
        overflow: visible;
    }
    
    @keyframes pulseHighlight {
        0%, 100% {
            box-shadow: 0 0 0 2px rgba(76, 138, 137, 0.2), 0 4px 12px rgba(76, 138, 137, 0.15);
        }
        50% {
            box-shadow: 0 0 0 4px rgba(76, 138, 137, 0.3), 0 6px 16px rgba(76, 138, 137, 0.25);
        }
    }
    
    @keyframes pulseBadge {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 2px 4px rgba(76, 138, 137, 0.3);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 3px 6px rgba(76, 138, 137, 0.4);
        }
    }
    
    /* Media Gallery Styles */
    .media-gallery-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 20px;
    }
    
    .media-gallery-item {
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }
    
    .media-gallery-item:hover {
        border-color: #4c8a89;
        box-shadow: 0 8px 24px rgba(76, 138, 137, 0.15);
        transform: translateY(-4px);
    }
    
    .media-gallery-item .media-preview {
        width: 100%;
        height: 200px;
        background: #f8fafc;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .media-gallery-item .media-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .media-gallery-item:hover .media-preview img {
        transform: scale(1.05);
    }
    
    .media-gallery-item .media-preview video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .media-gallery-item .media-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        opacity: 0;
    }
    
    .media-gallery-item:hover .media-overlay {
        background: rgba(0,0,0,0.5);
        opacity: 1;
    }
    
    .media-gallery-item .media-overlay .play-icon {
        color: white;
        font-size: 48px;
        text-shadow: 0 2px 8px rgba(0,0,0,0.5);
    }
    
    .media-gallery-item .media-info {
        padding: 14px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .media-gallery-item .media-title {
        font-size: 14px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 6px 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .media-gallery-item .media-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: auto;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
    }
    
    .media-gallery-item .media-badge {
        font-size: 11px;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 500;
        background: #f1f5f9;
        color: #475569;
    }
    
    .media-gallery-item .media-badge.fire { background: #fee2e2; color: #991b1b; }
    .media-gallery-item .media-badge.flood { background: #dbeafe; color: #1e40af; }
    .media-gallery-item .media-badge.earthquake { background: #fef3c7; color: #92400e; }
    .media-gallery-item .media-badge.typhoon { background: #e0e7ff; color: #3730a3; }
    .media-gallery-item .media-badge.health { background: #d1fae5; color: #065f46; }
    
    .media-gallery-item .media-actions {
        position: absolute;
        top: 8px;
        right: 8px;
        display: flex;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .media-gallery-item:hover .media-actions {
        opacity: 1;
    }
    
    .media-gallery-item .media-actions button {
        background: rgba(255,255,255,0.95);
        border: none;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        transition: all 0.2s;
    }
    
    .media-gallery-item .media-actions button:hover {
        background: white;
        transform: scale(1.1);
    }
    .content-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        transform: translateY(-2px);
    }
    
    /* Content Templates Styles */
    .templates-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 20px;
    }
    
    .template-card {
        background: #fff;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }
    
    .template-card:hover {
        border-color: #4c8a89;
        box-shadow: 0 8px 24px rgba(76, 138, 137, 0.15);
        transform: translateY(-4px);
    }
    
    .template-card .template-preview {
        width: 100%;
        height: 160px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .template-card .template-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .template-card:hover .template-preview img {
        transform: scale(1.05);
    }
    
    .template-card .template-preview .template-icon {
        font-size: 48px;
        color: #94a3b8;
        transition: all 0.3s ease;
    }
    
    .template-card:hover .template-preview .template-icon {
        color: #4c8a89;
        transform: scale(1.1);
    }
    
    .template-card .template-info {
        padding: 16px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .template-card .template-id {
        font-size: 11px;
        color: #94a3b8;
        margin-bottom: 6px;
        font-weight: 500;
    }
    
    .template-card .template-title {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
        margin: 0 0 12px 0;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 42px;
    }
    
    .template-card .template-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;
    }
    
    .template-card .template-badge {
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    
    .template-card .template-badge.hazard {
        background: #f1f5f9;
        color: #475569;
    }
    
    .template-card .template-badge.hazard.fire { background: #fee2e2; color: #991b1b; }
    .template-card .template-badge.hazard.flood { background: #dbeafe; color: #1e40af; }
    .template-card .template-badge.hazard.earthquake { background: #fef3c7; color: #92400e; }
    .template-card .template-badge.hazard.typhoon { background: #e0e7ff; color: #3730a3; }
    .template-card .template-badge.hazard.health { background: #d1fae5; color: #065f46; }
    .template-card .template-badge.hazard.emergency { background: #fce7f3; color: #9f1239; }
    
    .template-card .template-badge.type {
        background: #e0f2fe;
        color: #1d4ed8;
    }
    
    .template-card .template-actions {
        margin-top: auto;
        padding-top: 12px;
        border-top: 1px solid #f1f5f9;
    }
    
    .template-card .template-actions .btn-use {
        width: 100%;
        padding: 10px;
        background: #4c8a89;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }
    
    .template-card .template-actions .btn-use:hover {
        background: #3d6f6e;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(76, 138, 137, 0.3);
    }
    
    .template-card .template-actions .btn-preview {
        width: 100%;
        padding: 8px;
        background: transparent;
        color: #64748b;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 12px;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 6px;
    }
    
    .template-card .template-actions .btn-preview:hover {
        border-color: #4c8a89;
        color: #4c8a89;
        background: #f0fdfa;
    }
    
    /* Search & Filter Styles */
    .filter-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #4c8a89;
        color: white;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
        animation: slideIn 0.2s ease;
    }
    
    .filter-chip button {
        background: rgba(255,255,255,0.3);
        border: none;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
        padding: 0;
        margin-left: 4px;
        transition: background 0.2s;
    }
    
    .filter-chip button:hover {
        background: rgba(255,255,255,0.5);
    }
    
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .form-field input:focus,
    .form-field select:focus {
        border-color: #4c8a89 !important;
        box-shadow: 0 0 0 3px rgba(76, 138, 137, 0.1) !important;
    }
    
    .form-field input.has-value,
    .form-field select.has-value {
        border-color: #4c8a89;
        background: #f0fdfa;
    }
</style>

<main class="content-page">
    <div class="page-header">
        <h1>Content Repository</h1>
        <p>Centralized library for storing, managing, approving, and distributing educational materials for public safety campaigns</p>
    </div>

    <!-- Search and Filter Section -->
    <section id="content-list" class="card form-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Search & Filter</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Find content quickly - Results appear in Content Library below</p>
            </div>
            <button type="button" class="btn btn-secondary" onclick="showSearchTips()" style="padding: 8px 16px; font-size: 13px;">
                💡 How It Works
            </button>
        </div>
        
        <!-- Quick Filter Suggestions -->
        <div style="margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                <span style="font-size: 12px; font-weight: 600; color: #475569;">💡 Quick Filters:</span>
            </div>
            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                <button type="button" onclick="applyQuickFilter('approved', 'status', this)" class="quick-filter-btn" data-filter-type="status" data-filter-value="approved" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">✓ Approved Only</button>
                <button type="button" onclick="applyQuickFilter('pending', 'status', this)" class="quick-filter-btn" data-filter-type="status" data-filter-value="pending" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">⏳ Pending Review</button>
                <button type="button" onclick="applyQuickFilter('rejected', 'status', this)" class="quick-filter-btn" data-filter-type="status" data-filter-value="rejected" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">✗ Rejected</button>
                <button type="button" onclick="applyQuickFilter('fire', 'hazard', this)" class="quick-filter-btn" data-filter-type="hazard" data-filter-value="fire" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">🔥 Fire Safety</button>
                <button type="button" onclick="applyQuickFilter('video', 'type', this)" class="quick-filter-btn" data-filter-type="type" data-filter-value="video" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">🎥 Videos</button>
                <button type="button" onclick="applyQuickFilter('poster', 'type', this)" class="quick-filter-btn" data-filter-type="type" data-filter-value="poster" style="padding: 6px 12px; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; cursor: pointer; transition: all 0.2s;">📋 Posters</button>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-field" style="position: relative;">
                <label>
                    <span>🔍 Search</span>
                    <span id="searchIndicator" style="font-size: 11px; color: #94a3b8; margin-left: 4px; display: none;">(typing...)</span>
                </label>
                <input type="text" id="searchQuery" placeholder="Try: 'fire safety', 'evacuation plan', 'health tips'..." autocomplete="off" list="searchSuggestionsList">
                <datalist id="searchSuggestionsList">
                    <option value="fire safety">Fire Safety</option>
                    <option value="evacuation">Evacuation Plan</option>
                    <option value="health tips">Health Tips</option>
                    <option value="emergency">Emergency Contacts</option>
                    <option value="preparedness">Disaster Preparedness</option>
                    <option value="first aid">First Aid</option>
                    <option value="flood">Flood Safety</option>
                    <option value="earthquake">Earthquake Safety</option>
                </datalist>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                    💡 Tip: Search by keywords like "fire", "safety", "poster", or content titles. Auto-searches as you type.
                </div>
            </div>
            <div class="form-field">
                <label>Content Type</label>
                <select id="filterContentType">
                    <option value="">All Types</option>
                    <option value="poster">Poster</option>
                    <option value="video">Video</option>
                    <option value="guideline">Guideline</option>
                    <option value="infographic">Infographic</option>
                </select>
            </div>
            <div class="form-field">
                <label>Hazard Category</label>
                <select id="filterHazardCategory">
                    <option value="">All Categories</option>
                    <option value="fire">Fire</option>
                    <option value="flood">Flood</option>
                    <option value="earthquake">Earthquake</option>
                    <option value="typhoon">Typhoon</option>
                    <option value="health">Health</option>
                </select>
            </div>
            <div class="form-field">
                <label>👥 Intended Audience</label>
                <input type="text" id="filterAudience" placeholder="Try: 'households', 'youth', 'senior citizens', 'schools'" autocomplete="off" list="audienceSuggestionsList">
                <datalist id="audienceSuggestionsList">
                    <option value="households">Households</option>
                    <option value="youth">Youth</option>
                    <option value="senior citizens">Senior Citizens</option>
                    <option value="schools">Schools</option>
                    <option value="residential areas">Residential Areas</option>
                    <option value="flood-prone areas">Flood-prone Areas</option>
                </datalist>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                    Common: households, youth, senior citizens, schools, residential areas
                </div>
            </div>
            <div class="form-field">
                <label>Source</label>
                <select id="filterSource">
                    <option value="">All Sources</option>
                    <option value="inspection-based">Inspection-based</option>
                    <option value="training-based">Training-based</option>
                    <option value="barangay-created">Barangay-created</option>
                </select>
            </div>
            <div class="form-field">
                <label>Approval Status</label>
                <select id="filterApprovalStatus">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <div class="form-field" style="grid-column: 1 / -1;">
                <label>
                    <input type="checkbox" id="onlyApproved" style="width: auto; margin-right: 8px;">
                    Show only approved content
                </label>
            </div>
        </div>
        <button type="button" class="btn btn-primary" onclick="applyFilters()" id="applyFiltersBtn" style="margin-top:16px; padding: 10px 24px;">🔍 Apply Filters</button>
        <button type="button" class="btn btn-secondary" onclick="clearFilters()" style="margin-top:16px; margin-left:8px; padding: 10px 24px;">🔄 Clear</button>
    </section>

    <!-- Upload Section -->
    <section id="create-content" class="card form-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Upload Content</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Upload new content as Draft → Find it in Content Library below</p>
            </div>
        </div>
        <div style="padding: 12px; background: #fef3c7; border-radius: 8px; border: 1px solid #fde68a; margin-bottom: 16px; font-size: 13px; color: #92400e;">
            <strong>📌 Where to find your upload:</strong> After uploading, your content will appear in the <strong>Content Library</strong> section below with <strong>Draft</strong> status. 
            You can then submit it for approval or edit it. Scroll down to see the Content Library.
        </div>
        <form id="uploadForm" class="form-grid">
            <div class="form-field">
                <label>File *</label>
                <input type="file" name="file" required accept="image/*,video/*,.pdf">
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
                <label>Content Type *</label>
                <select name="content_type" required>
                    <option value="">Select type...</option>
                    <option value="poster">Poster</option>
                    <option value="video">Video</option>
                    <option value="guideline">Guideline</option>
                    <option value="infographic">Infographic</option>
                </select>
            </div>
            <div class="form-field">
                <label>Hazard Category</label>
                <select name="hazard_category">
                    <option value="">Select category...</option>
                    <option value="fire">Fire</option>
                    <option value="flood">Flood</option>
                    <option value="earthquake">Earthquake</option>
                    <option value="typhoon">Typhoon</option>
                    <option value="health">Health</option>
                </select>
            </div>
            <div class="form-field">
                <label>Intended Audience Segment</label>
                <input type="text" name="intended_audience_segment" placeholder="e.g., households, senior citizens, youth, schools">
            </div>
            <div class="form-field">
                <label>Source</label>
                <select name="source">
                    <option value="">Select source...</option>
                    <option value="inspection-based">Inspection-based</option>
                    <option value="training-based">Training-based</option>
                    <option value="barangay-created">Barangay-created</option>
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
        <button type="submit" form="uploadForm" class="btn btn-primary" style="margin-top:16px;">📤 Upload Content (Draft)</button>
        <div class="status" id="uploadStatus" style="margin-top:12px;"></div>
    </section>

    <!-- Content Library -->
    <section id="content-library" class="card form-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">📚 Content Library</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">All your content items appear here - Use Search & Filter above to find specific items</p>
            </div>
            <button class="btn btn-secondary" onclick="loadContent()" style="padding: 8px 16px;">🔄 Refresh</button>
        </div>
        <div class="library-grid" id="library"></div>
    </section>

    <!-- Templates Section -->
    <section id="templates" class="card form-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Content Templates</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Browse approved templates to quickly create new content</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <select id="templateFilter" onchange="loadTemplates()" style="padding: 8px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="all">All Templates</option>
                    <option value="poster">📋 Posters</option>
                    <option value="video">🎥 Videos</option>
                    <option value="guideline">📖 Guidelines</option>
                    <option value="infographic">📊 Infographics</option>
                </select>
                <button class="btn btn-secondary" onclick="loadTemplates()" style="padding: 8px 16px;">🔄 Refresh</button>
            </div>
        </div>
        <div class="templates-grid" id="templatesGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 20px; margin-top: 20px;"></div>
    </section>

    <!-- Media Gallery Section -->
    <section id="media-gallery" class="card form-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 class="section-title" style="margin: 0 0 4px 0;">Media Gallery</h2>
                <p style="color: #64748b; margin: 0; font-size: 14px;">Browse and manage your media files</p>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <select id="mediaGalleryFilter" onchange="loadMediaGallery()" style="padding: 8px 14px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 14px; background: white; cursor: pointer;">
                    <option value="all">All Media</option>
                    <option value="image">📷 Images</option>
                    <option value="video">🎥 Videos</option>
                </select>
                <button class="btn btn-secondary" onclick="loadMediaGallery()" style="padding: 8px 16px;">🔄 Refresh</button>
            </div>
        </div>
        
        <div class="media-gallery-grid" id="mediaGalleryGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-top: 20px;"></div>
        
        <div id="mediaGalleryLoadMore" style="text-align: center; margin-top: 24px; display: none;">
            <button class="btn btn-secondary" onclick="loadMoreMedia()" style="padding: 10px 24px;">Load More Media</button>
        </div>
    </section>

    <!-- Media Lightbox Modal -->
    <div id="mediaLightbox" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;">
        <div style="position: relative; max-width: 90%; max-height: 90%; display: flex; flex-direction: column; align-items: center;">
            <button onclick="closeMediaLightbox()" style="position: absolute; top: -40px; right: 0; background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
            <div id="lightboxContent" style="max-width: 100%; max-height: 85vh; object-fit: contain;"></div>
            <div id="lightboxInfo" style="color: white; text-align: center; margin-top: 16px; max-width: 600px;">
                <h3 id="lightboxTitle" style="margin: 0 0 8px 0; font-size: 18px;"></h3>
                <p id="lightboxMeta" style="margin: 0; font-size: 14px; color: rgba(255,255,255,0.8);"></p>
            </div>
        </div>
    </div>

    <section id="record-usage" class="card form-section">
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

    <section id="usage-history" class="card" style="margin-top: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h2 class="section-title" style="margin: 0;">Content Usage History</h2>
                <p style="color: #64748b; margin: 8px 0 0 0; font-size: 14px;">View all recorded content usage records</p>
            </div>
            <button onclick="loadUsageHistory()" class="btn btn-secondary" style="padding: 8px 16px; display: flex; align-items: center; gap: 6px;">
                <span>🔄</span>
                <span>Refresh</span>
            </button>
        </div>
        <div id="usageHistoryContainer" style="margin-top: 16px;">
            <p style="text-align: center; color: #64748b; padding: 40px;">Click "Refresh" to load usage records</p>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php require_once __DIR__ . '/../header/includes/path_helper.php'; ?>
const token = localStorage.getItem('jwtToken') || '';
const apiBase = '<?php echo $apiPath; ?>';
const basePath = '<?php echo $basePath; ?>';

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
        console.log('Upload response:', data);
        
        if (res.ok) {
            // Store the uploaded content ID to highlight it
            // Check different possible response structures
            const uploadedContentId = data.data?.id || data.id || data.content_id || null;
            console.log('Uploaded content ID:', uploadedContentId);
            console.log('Full response data:', JSON.stringify(data, null, 2));
            
            if (uploadedContentId) {
                sessionStorage.setItem('lastUploadedContentId', uploadedContentId.toString());
                sessionStorage.setItem('lastUploadedTime', Date.now().toString());
                console.log('Stored in sessionStorage:', {
                    id: uploadedContentId.toString(),
                    time: Date.now()
                });
            } else {
                console.warn('Could not find content ID in response:', data);
            }
            
            statusEl.textContent = '✓ Content uploaded successfully! Your draft is now in the Content Library below.';
            statusEl.style.color = '#166534';
            statusEl.style.fontWeight = '600';
            e.target.reset();
            
            // Auto-scroll to Content Library and reload content
            setTimeout(() => {
                const librarySection = document.getElementById('content-library');
                if (librarySection) {
                    librarySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    // Highlight the section briefly
                    librarySection.style.transition = 'box-shadow 0.3s';
                    librarySection.style.boxShadow = '0 0 0 4px rgba(76, 138, 137, 0.3)';
                    setTimeout(() => {
                        librarySection.style.boxShadow = '';
                    }, 2000);
                }
                // Reload content to show the new upload - wait a bit longer to ensure DB is updated
                setTimeout(() => {
                    console.log('Loading content after upload...');
            loadContent();
                }, 800);
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

// Debounce function for search input
let searchTimeout;
let filterTimeout;

// Track active filters
let activeFilters = {};

// Update active filters display
function updateActiveFilters() {
    const chipsContainer = document.getElementById('filterChips');
    const activeDisplay = document.getElementById('activeFiltersDisplay');
    const filterCount = document.getElementById('filterCount');
    const activeFilterCount = document.getElementById('activeFilterCount');
    const clearBtn = document.getElementById('clearFiltersBtn');
    
    if (!chipsContainer) return;
    
    activeFilters = {};
    const filters = [];
    
    const searchQuery = document.getElementById('searchQuery').value.trim();
    const contentType = document.getElementById('filterContentType').value;
    const hazardCategory = document.getElementById('filterHazardCategory').value;
    const audience = document.getElementById('filterAudience').value.trim();
    const source = document.getElementById('filterSource').value;
    const approvalStatus = document.getElementById('filterApprovalStatus').value;
    const onlyApproved = document.getElementById('onlyApproved').checked;
    
    if (searchQuery) {
        activeFilters.search = searchQuery;
        filters.push({ key: 'search', label: `Search: "${searchQuery}"`, value: searchQuery });
    }
    if (contentType) {
        activeFilters.contentType = contentType;
        filters.push({ key: 'contentType', label: `Type: ${contentType}`, value: contentType });
    }
    if (hazardCategory) {
        activeFilters.hazardCategory = hazardCategory;
        filters.push({ key: 'hazardCategory', label: `Category: ${hazardCategory}`, value: hazardCategory });
    }
    if (audience) {
        activeFilters.audience = audience;
        filters.push({ key: 'audience', label: `Audience: ${audience}`, value: audience });
    }
    if (source) {
        activeFilters.source = source;
        filters.push({ key: 'source', label: `Source: ${source}`, value: source });
    }
    if (approvalStatus) {
        activeFilters.approvalStatus = approvalStatus;
        filters.push({ key: 'approvalStatus', label: `Status: ${approvalStatus}`, value: approvalStatus });
    }
    if (onlyApproved) {
        activeFilters.onlyApproved = true;
        filters.push({ key: 'onlyApproved', label: 'Only Approved', value: true });
    }
    
    chipsContainer.innerHTML = '';
    
    if (filters.length > 0) {
        activeDisplay.style.display = 'block';
        filterCount.style.display = 'inline-block';
        clearBtn.style.display = 'inline-block';
        activeFilterCount.textContent = filters.length;
        
        filters.forEach(filter => {
            const chip = document.createElement('span');
            chip.className = 'filter-chip';
            chip.innerHTML = `
                ${filter.label}
                <button onclick="removeFilter('${filter.key}')" title="Remove filter">×</button>
            `;
            chipsContainer.appendChild(chip);
        });
        
        // Update form field styles
        updateFormFieldStyles();
    } else {
        activeDisplay.style.display = 'none';
        filterCount.style.display = 'none';
        clearBtn.style.display = 'none';
        resetFormFieldStyles();
    }
}

// Update form field visual styles based on values
function updateFormFieldStyles() {
    const searchQuery = document.getElementById('searchQuery');
    const contentType = document.getElementById('filterContentType');
    const hazardCategory = document.getElementById('filterHazardCategory');
    const audience = document.getElementById('filterAudience');
    const source = document.getElementById('filterSource');
    const approvalStatus = document.getElementById('filterApprovalStatus');
    
    if (searchQuery.value.trim()) searchQuery.classList.add('has-value');
    else searchQuery.classList.remove('has-value');
    
    if (contentType.value) contentType.classList.add('has-value');
    else contentType.classList.remove('has-value');
    
    if (hazardCategory.value) hazardCategory.classList.add('has-value');
    else hazardCategory.classList.remove('has-value');
    
    if (audience.value.trim()) audience.classList.add('has-value');
    else audience.classList.remove('has-value');
    
    if (source.value) source.classList.add('has-value');
    else source.classList.remove('has-value');
    
    if (approvalStatus.value) approvalStatus.classList.add('has-value');
    else approvalStatus.classList.remove('has-value');
}

function resetFormFieldStyles() {
    document.querySelectorAll('.form-field input, .form-field select').forEach(el => {
        el.classList.remove('has-value');
    });
}

// Remove individual filter
function removeFilter(key) {
    switch(key) {
        case 'search':
            document.getElementById('searchQuery').value = '';
            break;
        case 'contentType':
            document.getElementById('filterContentType').value = '';
            break;
        case 'hazardCategory':
            document.getElementById('filterHazardCategory').value = '';
            break;
        case 'audience':
            document.getElementById('filterAudience').value = '';
            break;
        case 'source':
            document.getElementById('filterSource').value = '';
            break;
        case 'approvalStatus':
            document.getElementById('filterApprovalStatus').value = '';
            // Reset status quick filter buttons
            document.querySelectorAll('.quick-filter-btn[data-filter-type="status"]').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = '#475569';
                btn.style.borderColor = '#e2e8f0';
                btn.classList.remove('active');
            });
            break;
        case 'onlyApproved':
            document.getElementById('onlyApproved').checked = false;
            // Reset approved quick filter button
            const approvedBtn = document.querySelector('.quick-filter-btn[data-filter-value="approved"]');
            if (approvedBtn) {
                approvedBtn.style.background = 'white';
                approvedBtn.style.color = '#475569';
                approvedBtn.style.borderColor = '#e2e8f0';
                approvedBtn.classList.remove('active');
            }
            break;
        case 'hazardCategory':
            // Reset hazard quick filter buttons
            document.querySelectorAll('.quick-filter-btn[data-filter-type="hazard"]').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = '#475569';
                btn.style.borderColor = '#e2e8f0';
                btn.classList.remove('active');
            });
            break;
        case 'contentType':
            // Reset type quick filter buttons
            document.querySelectorAll('.quick-filter-btn[data-filter-type="type"]').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = '#475569';
                btn.style.borderColor = '#e2e8f0';
                btn.classList.remove('active');
            });
            break;
    }
    updateActiveFilters();
    applyFilters();
}

// Apply filters function
function applyFilters() {
    updateActiveFilters();
    loadContent();
}

// Clear all filters
function clearFilters() {
    document.getElementById('searchQuery').value = '';
    document.getElementById('filterContentType').value = '';
    document.getElementById('filterHazardCategory').value = '';
    document.getElementById('filterAudience').value = '';
    document.getElementById('filterSource').value = '';
    document.getElementById('filterApprovalStatus').value = '';
    document.getElementById('onlyApproved').checked = false;
    
    // Reset all quick filter buttons to inactive state
    document.querySelectorAll('.quick-filter-btn').forEach(btn => {
        btn.style.background = 'white';
        btn.style.color = '#475569';
        btn.style.borderColor = '#e2e8f0';
        btn.classList.remove('active');
    });
    
    updateActiveFilters();
    loadContent();
}

async function loadContent() {
    const container = document.getElementById('library');
    if (!container) {
        console.error('Content library container not found!');
        return;
    }
    
    // Ensure container has correct classes and is visible
    container.classList.add('library-grid');
    container.style.display = 'grid';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
    
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading content...</p>';
    
    // Check token
    const currentToken = localStorage.getItem('jwtToken') || '';
    if (!currentToken || currentToken.trim() === '') {
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">⚠️ Authentication required. Please log in again.</p>';
        console.error('No token available for API call');
        return;
    }
    
    try {
        // Build query string from filters
        const params = new URLSearchParams();
        const q = document.getElementById('searchQuery')?.value.trim() || '';
        if (q) params.append('q', q);
        
        const contentType = document.getElementById('filterContentType')?.value || '';
        if (contentType) params.append('content_type', contentType);
        
        const hazardCategory = document.getElementById('filterHazardCategory')?.value || '';
        if (hazardCategory) params.append('hazard_category', hazardCategory);
        
        const audience = document.getElementById('filterAudience')?.value.trim() || '';
        if (audience) params.append('intended_audience', audience);
        
        const source = document.getElementById('filterSource')?.value || '';
        if (source) params.append('source', source);
        
        const approvalStatus = document.getElementById('filterApprovalStatus')?.value || '';
        if (approvalStatus) params.append('approval_status', approvalStatus);
        
        if (document.getElementById('onlyApproved')?.checked) {
            params.append('only_approved', 'true');
        }
        
        const apiUrl = apiBase + '/api/v1/content' + (params.toString() ? '?' + params.toString() : '');
        
        console.log('=== LOAD CONTENT DEBUG ===');
        console.log('API URL:', apiUrl);
        console.log('Token present:', !!currentToken);
        console.log('Token length:', currentToken.length);
        console.log('Filters:', {
            q, contentType, hazardCategory, audience, source, approvalStatus,
            onlyApproved: document.getElementById('onlyApproved')?.checked
        });
        
        const res = await fetch(apiUrl, { 
            headers: { 
                'Authorization': 'Bearer ' + currentToken,
                'Accept': 'application/json'
            } 
        });
        
        console.log('Response status:', res.status);
        console.log('Response headers:', Object.fromEntries(res.headers.entries()));
        
        if (!res.ok) {
            let errorData = { error: 'Failed to load content' };
            try {
                const errorText = await res.clone().text();
                console.error('API error response:', res.status, errorText);
                try {
                    errorData = JSON.parse(errorText);
                } catch (parseErr) {
                    errorData = { error: errorText || `HTTP ${res.status} Error` };
                }
            } catch (e) {
                console.error('Failed to read error response:', e);
                errorData = { error: `HTTP ${res.status} Error` };
            }
            
            let errorMsg = errorData.error || 'Failed to load content';
            if (res.status === 401) {
                errorMsg = '⚠️ Authentication failed. Please log in again.';
            } else if (res.status === 403) {
                errorMsg = '⚠️ Access denied. You may not have permission to view content.';
            } else if (res.status === 500) {
                errorMsg = '⚠️ Server error. Please try again later.';
            }
            
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:40px;">
                    <div style="color:#dc2626; font-size: 16px; margin-bottom: 8px;">${errorMsg}</div>
                    <div style="color:#94a3b8; font-size: 13px;">Status: ${res.status} | Check browser console for details</div>
                </div>
            `;
            return;
        }
        
        const responseText = await res.text();
        console.log('Response text length:', responseText.length);
        console.log('Response text preview:', responseText.substring(0, 500));
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseErr) {
            console.error('Failed to parse JSON response:', parseErr);
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:40px;">
                    <div style="color:#dc2626; font-size: 16px; margin-bottom: 8px;">⚠️ Invalid response from server</div>
                    <div style="color:#94a3b8; font-size: 13px;">Response: ${responseText.substring(0, 200)}</div>
                </div>
            `;
            return;
        }
        
        console.log('Parsed data:', data);
        console.log('Data items count:', data.data ? data.data.length : 0);
        console.log('Data structure:', Object.keys(data));
        console.log('First item sample:', data.data && data.data.length > 0 ? data.data[0] : 'N/A');
        
        // Ensure container has the library-grid class
        if (!container.classList.contains('library-grid')) {
            container.classList.add('library-grid');
        }
        container.style.display = 'grid';
        
        // Update results count
        const itemCount = data.data ? data.data.length : 0;
        const resultsCount = document.getElementById('resultsCount');
        const resultsNumber = document.getElementById('resultsNumber');
        if (resultsCount && resultsNumber) {
            resultsNumber.textContent = itemCount;
            resultsCount.style.display = 'inline-block';
        }
        
        // Clear container
        container.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:60px 20px;">
                    <div style="font-size: 48px; margin-bottom: 16px;">🔍</div>
                    <p style="color:#64748b; font-size: 16px; margin-bottom: 8px;">No content items found</p>
                    <p style="color:#94a3b8; font-size: 14px;">Try adjusting your filters or upload new content</p>
                    <p style="color:#cbd5e1; font-size: 12px; margin-top: 8px;">Applied filters: ${document.getElementById('searchQuery')?.value || '(none)'}</p>
                </div>
            `;
            console.log('No results found with current filters');
            return;
        }
        
        console.log(`Rendering ${data.data.length} content items...`);
        
        // Check for newly uploaded content
        const lastUploadedId = sessionStorage.getItem('lastUploadedContentId');
        const lastUploadedTime = parseInt(sessionStorage.getItem('lastUploadedTime') || '0');
        const timeSinceUpload = Date.now() - lastUploadedTime;
        const isRecentUpload = timeSinceUpload < 60000; // Highlight for 60 seconds
        
        console.log('=== NEW UPLOAD CHECK ===');
        console.log('Last uploaded ID from sessionStorage:', lastUploadedId, typeof lastUploadedId);
        console.log('Last uploaded time:', lastUploadedTime);
        console.log('Time since upload:', timeSinceUpload, 'ms');
        console.log('Is recent upload:', isRecentUpload);
        
        data.data.forEach((item, index) => {
            const itemIdStr = item.id.toString();
            const isNewlyUploaded = lastUploadedId && itemIdStr === lastUploadedId.toString() && isRecentUpload;
            
            console.log(`Rendering item ${index + 1}/${data.data.length}:`, item.title || 'Untitled', 'ID:', item.id, 'Type:', typeof item.id);
            console.log(`  Comparing: "${itemIdStr}" === "${lastUploadedId}" = ${itemIdStr === lastUploadedId}`);
            
            if (isNewlyUploaded) {
                console.log('🎉 FOUND NEWLY UPLOADED ITEM!', item.id, item.title);
            }
            
            const div = document.createElement('div');
            
            // Add special styling for newly uploaded content
            if (isNewlyUploaded) {
                div.className = 'content-card newly-uploaded';
                div.style.border = '3px solid #4c8a89';
                div.style.boxShadow = '0 0 0 2px rgba(76, 138, 137, 0.2), 0 4px 12px rgba(76, 138, 137, 0.15)';
                div.style.background = 'linear-gradient(to bottom, #f0fdfa 0%, #ffffff 100%)';
                div.style.animation = 'pulseHighlight 2s ease-in-out';
            } else {
                div.className = 'content-card';
            }
            
            const filePath = item.file_path || item.file_reference || '';
            // Fix file path - ensure it starts with basePath if it's a relative path
            let fileUrl = '';
            if (filePath) {
                console.log('Original file path:', filePath, 'for item ID:', item.id);
                if (filePath.startsWith('http')) {
                    fileUrl = filePath;
                } else {
                    // Remove leading slash if present
                    let cleanPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
                    const basePathValue = typeof basePath !== 'undefined' ? basePath : '/public-safety-campaign-system';
                    
                    // Files are stored as 'uploads/content_repository/filename' in database
                    // Files are physically in: public/uploads/content_repository/filename
                    // In XAMPP, web root is usually the project root, so we need:
                    // basePath + '/public/' + cleanPath
                    // Result: '/public-safety-campaign-system/public/uploads/content_repository/filename'
                    
                    // Add 'public/' prefix since files are in public/uploads/ directory
                    if (!cleanPath.startsWith('public/')) {
                        cleanPath = 'public/' + cleanPath;
                    }
                    
                    fileUrl = basePathValue + (basePathValue.endsWith('/') ? '' : '/') + cleanPath;
                    console.log('Constructed file URL:', fileUrl);
                    console.log('basePath value:', basePathValue);
                    console.log('cleanPath:', cleanPath);
                }
            } else {
                console.warn('No file path found for item:', item.id, item.title);
            }
            const contentTypeSafe = (item.content_type || 'file').replace(/'/g, "\\'");
            const link = fileUrl ? `<button onclick="viewFileInModal('${fileUrl}', '${contentTypeSafe}')" class="btn btn-secondary" style="margin-top:8px; display:inline-block; font-size:12px;">📄 View File</button>` : '';
            
            // Approval status badge
            const statusColors = {
                'draft': { bg: '#f1f5f9', color: '#475569' },
                'pending': { bg: '#fef3c7', color: '#92400e' },
                'approved': { bg: '#d1fae5', color: '#065f46' },
                'rejected': { bg: '#fee2e2', color: '#991b1b' }
            };
            const statusStyle = statusColors[item.approval_status] || statusColors.draft;
            
            // Calculate time since upload
            let timeAgo = '';
            if (item.date_uploaded || item.created_at) {
                const uploadDate = new Date(item.date_uploaded || item.created_at);
                const now = new Date();
                const diffMs = now - uploadDate;
                const diffMins = Math.floor(diffMs / 60000);
                const diffHours = Math.floor(diffMs / 3600000);
                const diffDays = Math.floor(diffMs / 86400000);
                
                if (diffMins < 1) {
                    timeAgo = 'Just now';
                } else if (diffMins < 60) {
                    timeAgo = `${diffMins} min${diffMins > 1 ? 's' : ''} ago`;
                } else if (diffHours < 24) {
                    timeAgo = `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
                } else {
                    timeAgo = `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
                }
            }
            
            div.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                    <div style="font-size:11px;color:#64748b;">ID: ${item.id} | Version: ${item.version_number || 1}</div>
                    ${isNewlyUploaded ? `<span style="background: linear-gradient(135deg, #4c8a89 0%, #2d5f5e 100%); color: white; padding: 4px 10px; border-radius: 12px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 4px rgba(76, 138, 137, 0.3); animation: pulseBadge 2s ease-in-out infinite;">✨ NEW</span>` : ''}
                </div>
                <strong style="display:block; margin-bottom:8px; color:#0f172a; font-size:15px;">${(item.title || 'Untitled').substring(0, 60)}</strong>
                ${timeAgo ? `<div style="font-size:10px; color:#94a3b8; margin-bottom:6px; font-style:italic;">🕒 Uploaded ${timeAgo}</div>` : ''}
                ${item.body ? `<div style="font-size:12px; color:#64748b; margin-bottom:8px; line-height:1.4;">${item.body.substring(0, 100)}${item.body.length > 100 ? '...' : ''}</div>` : ''}
                <div style="font-size:11px; color:#475569; margin-bottom:8px; display:flex; flex-wrap:wrap; gap:4px;">
                    <span class="badge" style="background:#e0f2fe; color:#1d4ed8; padding:2px 8px; border-radius:4px;">${item.content_type || 'text'}</span>
                    <span class="badge" style="background:${statusStyle.bg}; color:${statusStyle.color}; padding:2px 8px; border-radius:4px;">${(item.approval_status || 'draft').toUpperCase()}</span>
                    ${item.hazard_category ? `<span class="badge" style="background:#fef3c7; color:#92400e; padding:2px 8px; border-radius:4px;">${item.hazard_category}</span>` : ''}
                </div>
                ${item.intended_audience_segment ? `<div style="font-size:11px; color:#64748b; margin-bottom:4px;">👥 ${item.intended_audience_segment.substring(0, 40)}</div>` : ''}
                ${item.source ? `<div style="font-size:11px; color:#64748b; margin-bottom:4px;">📌 Source: ${item.source}</div>` : ''}
                ${link}
                <div style="margin-top:8px; display:flex; gap:4px; flex-wrap:wrap;">
                    ${item.approval_status === 'draft' ? `<button onclick="submitForApproval(${item.id})" class="btn btn-secondary" style="padding:4px 8px; font-size:11px;">📤 Submit</button>` : ''}
                    ${item.approval_status === 'pending' ? `<button onclick="approveContent(${item.id})" class="btn btn-primary" style="padding:4px 8px; font-size:11px;">✓ Approve</button>
                        <button onclick="rejectContent(${item.id})" class="btn btn-secondary" style="padding:4px 8px; font-size:11px;">✗ Reject</button>` : ''}
                    ${item.approval_status === 'approved' ? `<button onclick="attachToCampaign(${item.id})" class="btn btn-secondary" style="padding:4px 8px; font-size:11px;">🔗 Attach to Campaign</button>` : ''}
                    <button onclick="viewDetails(${item.id})" class="btn btn-secondary" style="padding:4px 8px; font-size:11px;">👁️ Details</button>
                </div>
            `;
            container.appendChild(div);
            
            // Scroll to newly uploaded item if it's the first one
            if (isNewlyUploaded && index === 0) {
                setTimeout(() => {
                    div.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
            }
            
            console.log(`✓ Added item ${index + 1} to container${isNewlyUploaded ? ' (NEWLY UPLOADED - HIGHLIGHTED)' : ''}`);
        });
        
        // Clear the highlight after 60 seconds
        if (lastUploadedId && isRecentUpload) {
            setTimeout(() => {
                sessionStorage.removeItem('lastUploadedContentId');
                sessionStorage.removeItem('lastUploadedTime');
                // Re-render to remove highlight
                loadContent();
            }, 60000);
        }
        
        // Ensure container is visible after rendering
        container.style.display = 'grid';
        container.style.visibility = 'visible';
        container.style.opacity = '1';
        
        console.log(`✅ Successfully rendered ${data.data.length} items in content library`);
        console.log('Container element:', container);
        console.log('Container children count:', container.children.length);
        console.log('Container classes:', container.className);
        console.log('Container display style:', window.getComputedStyle(container).display);
        console.log('Container computed styles:', {
            display: window.getComputedStyle(container).display,
            visibility: window.getComputedStyle(container).visibility,
            opacity: window.getComputedStyle(container).opacity,
            gridTemplateColumns: window.getComputedStyle(container).gridTemplateColumns
        });
        
        // Scroll to library section if not visible
        const librarySection = document.getElementById('content-library');
        if (librarySection) {
            librarySection.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    } catch (err) {
        console.error('=== ERROR LOADING CONTENT ===');
        console.error('Error:', err);
        console.error('Error message:', err.message);
        console.error('Error stack:', err.stack);
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align:center; padding:40px;">
                <div style="color:#dc2626; font-size: 16px; margin-bottom: 8px;">⚠️ Failed to load content</div>
                <div style="color:#94a3b8; font-size: 13px; margin-bottom: 8px;">${err.message || 'Unknown error'}</div>
                <div style="color:#64748b; font-size: 12px;">Check browser console (F12) for details</div>
            </div>
        `;
    }
}

async function submitForApproval(contentId) {
    if (!confirm('Submit this content for approval?')) return;
    
    try {
        const res = await fetch(apiBase + `/api/v1/content/${contentId}/approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ approval_status: 'pending' })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Content submitted for approval');
            loadContent();
        } else {
            alert('Error: ' + (data.error || 'Failed to submit'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function approveContent(contentId) {
    const notes = prompt('Approval notes (optional):');
    try {
        const res = await fetch(apiBase + `/api/v1/content/${contentId}/approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ 
                approval_status: 'approved',
                approval_notes: notes || null
            })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Content approved successfully');
            loadContent();
        } else {
            alert('Error: ' + (data.error || 'Failed to approve'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function rejectContent(contentId) {
    const notes = prompt('Rejection reason (required):');
    if (!notes) {
        alert('Rejection reason is required');
        return;
    }
    try {
        const res = await fetch(apiBase + `/api/v1/content/${contentId}/approval`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify({ 
                approval_status: 'rejected',
                approval_notes: notes
            })
        });
        const data = await res.json();
        if (res.ok) {
            alert('Content rejected');
            loadContent();
        } else {
            alert('Error: ' + (data.error || 'Failed to reject'));
        }
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

async function attachToCampaign(contentId) {
    // Fetch available campaigns
    try {
        const campaignsRes = await fetch(apiBase + '/api/v1/campaigns', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const campaignsData = await campaignsRes.json();
        
        if (!campaignsRes.ok || !campaignsData.data || campaignsData.data.length === 0) {
            alert('No campaigns available. Please create a campaign first.');
            return;
        }
        
        // Create a modal to select campaign
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2);';
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                <h3 style="margin: 0; color: #0f172a; font-size: 18px;">🔗 Attach Content to Campaign</h3>
                <button id="closeAttachModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">&times;</button>
            </div>
            <p style="color: #64748b; margin: 0 0 16px 0; font-size: 14px;">Select a campaign to attach this content to:</p>
            <select id="campaignSelect" style="width: 100%; padding: 10px 14px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 16px; font-size: 14px; background: white;">
                <option value="">-- Select Campaign --</option>
                ${campaignsData.data.map(c => `<option value="${c.id}">${c.title} (${c.status || 'active'})</option>`).join('')}
            </select>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button id="cancelAttach" class="btn btn-secondary" style="padding: 8px 16px;">Cancel</button>
                <button id="confirmAttach" class="btn btn-primary" style="padding: 8px 16px;">Attach</button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Handle close button
        document.getElementById('closeAttachModal').onclick = () => {
            document.body.removeChild(modal);
        };
        
        // Handle cancel
        document.getElementById('cancelAttach').onclick = () => {
            document.body.removeChild(modal);
        };
        
        // Handle confirm
        document.getElementById('confirmAttach').onclick = async () => {
            const campaignId = document.getElementById('campaignSelect').value;
            if (!campaignId) {
                alert('Please select a campaign');
                return;
            }
            
            try {
                const res = await fetch(apiBase + `/api/v1/content/${contentId}/attach-campaign`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({ campaign_id: parseInt(campaignId) })
                });
                const data = await res.json();
                
                document.body.removeChild(modal);
                
                if (res.ok) {
                    alert('✅ Content attached to campaign successfully!');
                    loadContent();
                    // Reload templates if we're in templates section
                    if (document.getElementById('templatesGrid')) {
                        loadTemplates();
                    }
                    // If details modal is open, refresh it
                    const detailsModal = document.getElementById('detailsModal');
                    if (detailsModal) {
                        viewDetails(contentId);
                    }
                } else {
                    alert('❌ Error: ' + (data.error || 'Failed to attach'));
                }
            } catch (err) {
                document.body.removeChild(modal);
                alert('❌ Error: ' + err.message);
            }
        };
        
    } catch (err) {
        alert('❌ Error loading campaigns: ' + err.message);
    }
}

// View file in modal/lightbox with close button
function viewFileInModal(fileUrl, contentType) {
    console.log('Opening file in modal:', fileUrl, contentType);
    
    // Create modal overlay
    const modal = document.createElement('div');
    modal.id = 'fileViewModal';
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
    
    // Create modal content container
    const modalContent = document.createElement('div');
    modalContent.style.cssText = 'position: relative; max-width: 90vw; max-height: 90vh; background: #1e293b; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5);';
    
    // Close button (X)
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 28px; cursor: pointer; z-index: 10001; display: flex; align-items: center; justify-content: center; line-height: 1; transition: all 0.2s; font-weight: 300;';
    closeBtn.onmouseover = () => { 
        closeBtn.style.background = 'rgba(220, 38, 38, 0.9)'; 
        closeBtn.style.transform = 'scale(1.1)';
    };
    closeBtn.onmouseout = () => { 
        closeBtn.style.background = 'rgba(0,0,0,0.7)'; 
        closeBtn.style.transform = 'scale(1)';
    };
    closeBtn.onclick = () => {
        document.body.removeChild(modal);
        document.body.style.overflow = '';
    };
    
    // File container
    const fileContainer = document.createElement('div');
    fileContainer.style.cssText = 'display: flex; align-items: center; justify-content: center; padding: 40px; min-height: 200px;';
    
    // Determine file type and create appropriate viewer
    const isImage = contentType === 'image' || contentType === 'poster' || contentType === 'infographic' || 
                    /\.(jpg|jpeg|png|gif|webp)$/i.test(fileUrl);
    const isVideo = contentType === 'video' || /\.(mp4|webm|ogg)$/i.test(fileUrl);
    const isPDF = contentType === 'guideline' || /\.pdf$/i.test(fileUrl);
    
    if (isImage) {
        const img = document.createElement('img');
        img.src = fileUrl;
        img.style.cssText = 'max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px;';
        img.onerror = () => {
            fileContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><p style="font-size: 24px; margin-bottom: 8px;">❌</p><p style="margin-bottom: 8px;">Failed to load image</p><p style="font-size: 14px; color: #94a3b8;">' + fileUrl.split('/').pop() + '</p></div>';
        };
        fileContainer.appendChild(img);
    } else if (isVideo) {
        const video = document.createElement('video');
        video.src = fileUrl;
        video.controls = true;
        video.style.cssText = 'max-width: 100%; max-height: 85vh; border-radius: 8px;';
        video.onerror = () => {
            fileContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><p style="font-size: 24px; margin-bottom: 8px;">❌</p><p style="margin-bottom: 8px;">Failed to load video</p><p style="font-size: 14px; color: #94a3b8;">' + fileUrl.split('/').pop() + '</p></div>';
        };
        fileContainer.appendChild(video);
    } else if (isPDF) {
        const iframe = document.createElement('iframe');
        iframe.src = fileUrl;
        iframe.style.cssText = 'width: 80vw; height: 85vh; border: none; border-radius: 8px;';
        iframe.onerror = () => {
            fileContainer.innerHTML = '<div style="color: white; text-align: center; padding: 40px;"><p style="font-size: 24px; margin-bottom: 8px;">❌</p><p style="margin-bottom: 8px;">Failed to load PDF</p><p style="font-size: 14px; color: #94a3b8; margin-top: 16px;"><a href="' + fileUrl + '" target="_blank" style="color: #60a5fa; text-decoration: underline;">Open in new tab</a></p></div>';
        };
        fileContainer.appendChild(iframe);
    } else {
        // Generic file - show download link
        fileContainer.innerHTML = `
            <div style="color: white; text-align: center; padding: 40px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
                <p style="font-size: 18px; margin-bottom: 8px;">File Preview Not Available</p>
                <p style="font-size: 14px; color: #94a3b8; margin-bottom: 20px;">${fileUrl.split('/').pop()}</p>
                <a href="${fileUrl}" target="_blank" style="display: inline-block; padding: 10px 20px; background: #4c8a89; color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: background 0.2s;" onmouseover="this.style.background='#3d6f6e'" onmouseout="this.style.background='#4c8a89'">Download File</a>
            </div>
        `;
    }
    
    modalContent.appendChild(closeBtn);
    modalContent.appendChild(fileContainer);
    modal.appendChild(modalContent);
    
    // Prevent body scroll when modal is open
    document.body.style.overflow = 'hidden';
    
    // Close on Escape key
    const escapeHandler = (e) => {
        if (e.key === 'Escape') {
            document.body.removeChild(modal);
            document.body.style.overflow = '';
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
    
    // Close on background click
    modal.onclick = (e) => {
        if (e.target === modal) {
            document.body.removeChild(modal);
            document.body.style.overflow = '';
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    
    document.body.appendChild(modal);
}

async function viewDetails(contentId) {
    try {
        const res = await fetch(apiBase + `/api/v1/content/${contentId}`, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        if (!res.ok) {
            alert('Error: ' + (data.error || 'Failed to load details'));
            return;
        }
        
        const item = data.data;
        
        // Load campaigns
        const campaigns = await loadContentCampaigns(contentId);
        
        // Create modal for better display
        const modal = document.createElement('div');
        modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center; padding: 20px;';
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = 'background: white; padding: 24px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 85vh; overflow-y: auto; box-shadow: 0 10px 40px rgba(0,0,0,0.2); position: relative;';
        
        // Build versions HTML
        let versionsHtml = '';
        if (item.versions && item.versions.length > 0) {
            versionsHtml = '<div style="margin-top: 16px;"><strong style="color: #0f172a;">Version History:</strong><ul style="margin: 8px 0 0 20px; padding: 0; color: #475569;">';
            item.versions.forEach(v => {
                versionsHtml += `<li style="margin: 4px 0;">v${v.version_number}: ${v.title} (${new Date(v.created_at).toLocaleDateString()})${v.change_notes ? ' - ' + v.change_notes : ''}</li>`;
            });
            versionsHtml += '</ul></div>';
        }
        
        // Build campaigns HTML
        let campaignsHtml = '';
        if (campaigns && campaigns.length > 0) {
            campaignsHtml = '<div style="margin-top: 16px;"><strong style="color: #0f172a;">Attached to Campaigns:</strong><ul style="margin: 8px 0 0 20px; padding: 0; color: #475569;">';
            campaigns.forEach(c => {
                campaignsHtml += `<li style="margin: 4px 0;">Campaign #${c.id}: ${c.title} (${c.status || 'active'})</li>`;
            });
            campaignsHtml += '</ul></div>';
        } else {
            campaignsHtml = '<div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; border-left: 3px solid #94a3b8;"><p style="margin: 0; color: #64748b; font-size: 14px;"><strong>No campaigns attached yet.</strong><br><span style="font-size: 12px; margin-top: 4px; display: block;">This content hasn\'t been linked to any campaign. Click "Attach to Campaign" below to link it to an existing campaign.</span></p></div>';
        }
        
        // Build tags HTML
        let tagsHtml = '';
        if (item.tags && item.tags.length > 0) {
            tagsHtml = '<div style="margin-top: 12px;"><strong style="color: #0f172a;">Tags:</strong> <span style="color: #475569;">' + item.tags.join(', ') + '</span></div>';
        }
        
        // Status badge color
        const statusColors = {
            'approved': '#10b981',
            'pending': '#f59e0b',
            'rejected': '#ef4444',
            'draft': '#64748b'
        };
        const statusColor = statusColors[item.approval_status] || '#64748b';
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #0f172a; font-size: 20px; font-weight: 600;">Content Details</h3>
                <button id="closeDetailsModal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; transition: color 0.2s;" onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">&times;</button>
            </div>
            <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: auto 1fr; gap: 12px 16px; align-items: start;">
                    <strong style="color: #64748b;">ID:</strong>
                    <span style="color: #0f172a;">${item.id}</span>
                    <strong style="color: #64748b;">Title:</strong>
                    <span style="color: #0f172a; font-weight: 500;">${item.title || 'Untitled'}</span>
                    <strong style="color: #64748b;">Description:</strong>
                    <span style="color: #475569;">${item.body || 'N/A'}</span>
                    <strong style="color: #64748b;">Type:</strong>
                    <span style="color: #0f172a;">${item.content_type || 'N/A'}</span>
                    <strong style="color: #64748b;">Status:</strong>
                    <span style="color: ${statusColor}; font-weight: 600; text-transform: capitalize;">${item.approval_status || 'N/A'}</span>
                    <strong style="color: #64748b;">Version:</strong>
                    <span style="color: #0f172a;">${item.version_number || 1}</span>
                    <strong style="color: #64748b;">Hazard:</strong>
                    <span style="color: #0f172a;">${item.hazard_category || 'N/A'}</span>
                    <strong style="color: #64748b;">Audience:</strong>
                    <span style="color: #0f172a;">${item.intended_audience_segment || 'N/A'}</span>
                    <strong style="color: #64748b;">Source:</strong>
                    <span style="color: #0f172a;">${item.source || 'N/A'}</span>
                </div>
                ${tagsHtml}
            </div>
            ${versionsHtml}
            ${campaignsHtml}
            ${item.approval_status === 'approved' ? '<div style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #e2e8f0;"><button onclick="attachToCampaign(' + contentId + '); document.body.removeChild(document.getElementById(\'detailsModal\'));" class="btn btn-primary" style="padding: 8px 16px; background: #4c8a89; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: background 0.2s;" onmouseover="this.style.background=\'#3d6f6e\'" onmouseout="this.style.background=\'#4c8a89\'">🔗 Attach to Campaign</button></div>' : ''}
        `;
        
        modal.id = 'detailsModal';
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Handle close button
        document.getElementById('closeDetailsModal').onclick = () => {
            document.body.removeChild(modal);
        };
        
        // Close on click outside
        modal.onclick = (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        };
        
        // Close on ESC key
        const escHandler = (e) => {
            if (e.key === 'Escape' && document.getElementById('detailsModal')) {
                document.body.removeChild(modal);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);
        
    } catch (err) {
        alert('Error: ' + err.message);
    }
}

// Load campaigns for the content item
async function loadContentCampaigns(contentId) {
    try {
        const res = await fetch(apiBase + `/api/v1/content/${contentId}/campaigns`, {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        return data.data || [];
    } catch (err) {
        console.error('Error loading campaigns:', err);
        return [];
    }
}

// Load Templates (approved content that can be used as templates) - Redesigned
async function loadTemplates() {
    const container = document.getElementById('templatesGrid');
    if (!container) return;
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading templates...</p>';
    
    try {
        const filterType = document.getElementById('templateFilter') ? document.getElementById('templateFilter').value : 'all';
        let apiUrl = apiBase + '/api/v1/content?approval_status=approved&only_approved=true';
        
        if (filterType !== 'all') {
            apiUrl += `&content_type=${filterType}`;
        }
        
        const res = await fetch(apiUrl, { 
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            } 
        });
        
        if (!res.ok) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load templates</p>';
            return;
        }
        
        const data = await res.json();
        container.innerHTML = '';
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">No templates available. Approved content will appear here.</p>';
            return;
        }
        
        data.data.forEach(item => {
            const div = document.createElement('div');
            div.className = 'template-card';
            
            const filePath = item.file_path || item.file_reference || '';
            const isImage = item.content_type === 'image' || item.content_type === 'poster' || item.content_type === 'infographic' || 
                          (filePath && /\.(jpg|jpeg|png|gif|webp)$/i.test(filePath));
            const isVideo = item.content_type === 'video' || (filePath && /\.(mp4|webm)$/i.test(filePath));
            
            // Get icon based on content type
            let icon = '📄';
            if (isImage) icon = '🖼️';
            else if (isVideo) icon = '🎥';
            else if (item.content_type === 'guideline') icon = '📖';
            else if (item.content_type === 'infographic') icon = '📊';
            else if (item.content_type === 'poster') icon = '📋';
            
            // Build preview
            let preview = '';
            if (isImage && filePath) {
                const imagePath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
                preview = `<img src="/${imagePath}" alt="${item.title || 'Template'}" onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\\'template-icon\\'>${icon}</div>'">`;
            } else {
                preview = `<div class="template-icon">${icon}</div>`;
            }
            
            // Hazard category badge
            const hazardClass = item.hazard_category ? `hazard ${item.hazard_category.toLowerCase()}` : '';
            const hazardIcon = item.hazard_category === 'fire' ? '🔥' : 
                              item.hazard_category === 'flood' ? '💧' :
                              item.hazard_category === 'earthquake' ? '🌍' :
                              item.hazard_category === 'typhoon' ? '🌀' :
                              item.hazard_category === 'health' ? '🏥' :
                              item.hazard_category === 'emergency' ? '🚨' : '🏷️';
            
            div.innerHTML = `
                <div class="template-preview">
                    ${preview}
                </div>
                <div class="template-info">
                    <div class="template-id">Template ID: ${item.id}</div>
                    <h3 class="template-title">${item.title || 'Untitled Template'}</h3>
                    <div class="template-tags">
                        ${item.hazard_category ? `<span class="template-badge ${hazardClass}">${hazardIcon} ${item.hazard_category}</span>` : ''}
                        <span class="template-badge type">${icon} ${item.content_type || 'content'}</span>
                    </div>
                    <div class="template-actions">
                        <button class="btn-use" onclick="event.stopPropagation(); useTemplate(${item.id})">
                            <span>📋</span>
                            <span>Use Template</span>
                        </button>
                        <button class="btn-preview" onclick="event.stopPropagation(); viewDetails(${item.id})">
                            👁️ Preview Details
                        </button>
                    </div>
                </div>
            `;
            
            // Add click handler for quick use
            div.onclick = function(e) {
                if (!e.target.closest('.template-actions')) {
                    useTemplate(item.id);
                }
            };
            
            container.appendChild(div);
        });
    } catch (err) {
        console.error('Error loading templates:', err);
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load templates: ' + err.message + '</p>';
    }
}

// Use template to create new content - Enhanced with better UX
function useTemplate(templateId) {
    // Show loading state
    const loadingMsg = document.createElement('div');
    loadingMsg.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px 30px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); z-index: 10001; text-align: center;';
    loadingMsg.innerHTML = '<div style="font-size: 18px; margin-bottom: 8px;">⏳</div><div>Loading template...</div>';
    document.body.appendChild(loadingMsg);
    
    fetch(apiBase + `/api/v1/content/${templateId}`, {
        headers: { 'Authorization': 'Bearer ' + token }
    })
    .then(res => res.json())
    .then(data => {
        document.body.removeChild(loadingMsg);
        
        if (data.data) {
            const template = data.data;
            const form = document.getElementById('uploadForm');
            
            if (!form) {
                alert('Upload form not found. Please navigate to the Upload Content section.');
                return;
            }
            
            // Populate form fields
            const titleField = form.querySelector('[name="title"]');
            const descField = form.querySelector('[name="description"]');
            const typeField = form.querySelector('[name="content_type"]');
            const hazardField = form.querySelector('[name="hazard_category"]');
            const audienceField = form.querySelector('[name="intended_audience_segment"]');
            const sourceField = form.querySelector('[name="source"]');
            
            if (titleField) titleField.value = template.title + ' (Copy)';
            if (descField) descField.value = template.body || '';
            if (typeField) typeField.value = template.content_type || '';
            if (hazardField) hazardField.value = template.hazard_category || '';
            if (audienceField) audienceField.value = template.intended_audience_segment || '';
            if (sourceField) sourceField.value = template.source || '';
            
            // Scroll to upload form with smooth animation
            const uploadSection = document.getElementById('create-content');
            if (uploadSection) {
                uploadSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
                // Highlight the form briefly
                setTimeout(() => {
                    uploadSection.style.transition = 'box-shadow 0.3s ease';
                    uploadSection.style.boxShadow = '0 0 0 4px rgba(76, 138, 137, 0.2)';
                    setTimeout(() => {
                        uploadSection.style.boxShadow = '';
                    }, 2000);
                }, 500);
            }
            
            // Show success notification
            const successMsg = document.createElement('div');
            successMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #d1fae5; color: #065f46; padding: 16px 24px; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,0.15); z-index: 10001; display: flex; align-items: center; gap: 12px; max-width: 400px;';
            successMsg.innerHTML = `
                <div style="font-size: 24px;">✅</div>
                <div>
                    <div style="font-weight: 600; margin-bottom: 4px;">Template Loaded!</div>
                    <div style="font-size: 13px; opacity: 0.8;">Fill in the file field and submit to create new content.</div>
                </div>
            `;
            document.body.appendChild(successMsg);
            
            setTimeout(() => {
                successMsg.style.transition = 'opacity 0.3s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => document.body.removeChild(successMsg), 300);
            }, 4000);
        } else {
            alert('Template data not found.');
        }
    })
    .catch(err => {
        document.body.removeChild(loadingMsg);
        alert('Error loading template: ' + err.message);
    });
}

// Media gallery state
let mediaGalleryAllItems = [];
let mediaGalleryDisplayed = 0;
const MEDIA_GALLERY_LIMIT = 6; // Show only 6 items initially

// Load Media Gallery (images and videos) - Limited display
async function loadMediaGallery() {
    const container = document.getElementById('mediaGalleryGrid');
    const loadMoreBtn = document.getElementById('mediaGalleryLoadMore');
    if (!container) return;
    container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">Loading media...</p>';
    
    try {
        const mediaType = document.getElementById('mediaGalleryFilter') ? document.getElementById('mediaGalleryFilter').value : 'all';
        let apiUrl = apiBase + '/api/v1/content?approval_status=approved';
        
        if (mediaType === 'image') {
            apiUrl += '&content_type=image';
        } else if (mediaType === 'video') {
            apiUrl += '&content_type=video';
        }
        
        const res = await fetch(apiUrl, { 
            headers: { 
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json'
            } 
        });
        
        if (!res.ok) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load media</p>';
            return;
        }
        
        const data = await res.json();
        
        if (!data.data || data.data.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">No media files found.</p>';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            return;
        }
        
        // Filter to only show items with actual media files
        mediaGalleryAllItems = data.data.filter(item => {
            const filePath = item.file_path || item.file_reference || '';
            return filePath && (item.content_type === 'image' || item.content_type === 'video' || 
                   /\.(jpg|jpeg|png|gif|webp|mp4|webm)$/i.test(filePath));
        });
        
        if (mediaGalleryAllItems.length === 0) {
            container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#64748b; padding:40px;">No media files found.</p>';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            return;
        }
        
        // Reset displayed count and show limited items
        mediaGalleryDisplayed = 0;
        displayMediaItems(container, loadMoreBtn, MEDIA_GALLERY_LIMIT);
    } catch (err) {
        console.error('Error loading media gallery:', err);
        container.innerHTML = '<p style="grid-column: 1/-1; text-align:center; color:#dc2626; padding:40px;">Failed to load media: ' + err.message + '</p>';
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
    }
}

// Display media items (helper function) - Redesigned with better UX
function displayMediaItems(container, loadMoreBtn, limit) {
    const itemsToShow = mediaGalleryAllItems.slice(mediaGalleryDisplayed, mediaGalleryDisplayed + limit);
    
    if (mediaGalleryDisplayed === 0) {
        container.innerHTML = '';
    }
    
    itemsToShow.forEach(item => {
        const div = document.createElement('div');
        div.className = 'media-gallery-item';
        
        const filePath = item.file_path || item.file_reference || '';
        const isVideo = item.content_type === 'video' || /\.(mp4|webm)$/i.test(filePath);
        const isImage = item.content_type === 'image' || /\.(jpg|jpeg|png|gif|webp)$/i.test(filePath);
        
        // Build media preview
        let mediaPreview = '';
        if (isImage && filePath) {
            // Fix path - remove leading slash if basePath already has it
            const imagePath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
            mediaPreview = `
                <div class="media-preview">
                    <img src="/${imagePath}" alt="${item.title || 'Media'}" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'padding:60px 20px; text-align:center; color:#64748b;\\'><div style=\\'font-size:32px; margin-bottom:8px;\\'>📄</div><div>Image not found</div></div>'">
                    <div class="media-overlay">
                        <span style="color: white; font-size: 14px; font-weight: 600;">Click to view</span>
                    </div>
                </div>
            `;
        } else if (isVideo && filePath) {
            const videoPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
            mediaPreview = `
                <div class="media-preview">
                    <video preload="metadata" muted>
                        <source src="/${videoPath}" type="video/mp4">
                    </video>
                    <div class="media-overlay">
                        <div class="play-icon">▶</div>
                    </div>
                </div>
            `;
        } else {
            mediaPreview = `
                <div class="media-preview" style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);">
                    <div style="text-align: center; color: #64748b;">
                        <div style="font-size: 48px; margin-bottom: 8px;">📄</div>
                        <div style="font-size: 12px; font-weight: 500;">${item.content_type || 'file'}</div>
                    </div>
                </div>
            `;
        }
        
        // Hazard category badge class
        const hazardClass = item.hazard_category ? item.hazard_category.toLowerCase() : '';
        
        div.innerHTML = `
            ${mediaPreview}
            <div class="media-actions">
                <button onclick="event.stopPropagation(); openMediaLightbox(${item.id}, '${filePath}', ${isVideo}, '${(item.title || '').replace(/'/g, "\\'")}')" title="View full size">👁️</button>
                <button onclick="event.stopPropagation(); viewMediaDetails(${item.id})" title="View details">ℹ️</button>
            </div>
            <div class="media-info">
                <h3 class="media-title">${item.title || 'Untitled'}</h3>
                <div class="media-meta">
                    ${item.hazard_category ? `<span class="media-badge ${hazardClass}">${item.hazard_category}</span>` : ''}
                    <span style="font-size: 11px; color: #94a3b8; margin-left: auto;">ID: ${item.id}</span>
                </div>
            </div>
        `;
        
        // Add click handler for lightbox
        div.onclick = function(e) {
            if (!e.target.closest('.media-actions')) {
                openMediaLightbox(item.id, filePath, isVideo, item.title || 'Untitled');
            }
        };
        
        container.appendChild(div);
    });
    
    mediaGalleryDisplayed += itemsToShow.length;
    
    // Show/hide "Load More" button
    if (loadMoreBtn) {
        if (mediaGalleryDisplayed < mediaGalleryAllItems.length) {
            loadMoreBtn.style.display = 'block';
        } else {
            loadMoreBtn.style.display = 'none';
        }
    }
}

// Load more media items
function loadMoreMedia() {
    const container = document.getElementById('mediaGalleryGrid');
    const loadMoreBtn = document.getElementById('mediaGalleryLoadMore');
    if (container && mediaGalleryAllItems.length > mediaGalleryDisplayed) {
        displayMediaItems(container, loadMoreBtn, MEDIA_GALLERY_LIMIT);
    }
}

// View media details
function viewMediaDetails(contentId) {
    viewDetails(contentId);
}

// Open media lightbox
function openMediaLightbox(contentId, filePath, isVideo, title) {
    const lightbox = document.getElementById('mediaLightbox');
    const lightboxContent = document.getElementById('lightboxContent');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const lightboxMeta = document.getElementById('lightboxMeta');
    
    if (!lightbox || !lightboxContent) return;
    
    const mediaPath = filePath.startsWith('/') ? filePath.substring(1) : filePath;
    
    if (isVideo) {
        lightboxContent.innerHTML = `
            <video controls autoplay style="max-width: 100%; max-height: 85vh; border-radius: 8px;">
                <source src="/${mediaPath}" type="video/mp4">
                Your browser does not support video playback.
            </video>
        `;
    } else {
        lightboxContent.innerHTML = `
            <img src="/${mediaPath}" alt="${title}" style="max-width: 100%; max-height: 85vh; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);">
        `;
    }
    
    lightboxTitle.textContent = title || 'Media Preview';
    lightboxMeta.textContent = `Content ID: ${contentId} | ${isVideo ? 'Video' : 'Image'}`;
    
    lightbox.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

// Close media lightbox
function closeMediaLightbox() {
    const lightbox = document.getElementById('mediaLightbox');
    const lightboxContent = document.getElementById('lightboxContent');
    
    if (lightbox) {
        lightbox.style.display = 'none';
        document.body.style.overflow = '';
    }
    
    if (lightboxContent) {
        // Stop video playback
        const video = lightboxContent.querySelector('video');
        if (video) {
            video.pause();
            video.src = '';
        }
        lightboxContent.innerHTML = '';
    }
}

// Close lightbox on ESC key or click outside
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMediaLightbox();
    }
});

document.addEventListener('click', function(e) {
    const lightbox = document.getElementById('mediaLightbox');
    if (lightbox && e.target === lightbox) {
        closeMediaLightbox();
    }
});

// Load usage history
async function loadUsageHistory() {
    const container = document.getElementById('usageHistoryContainer');
    if (!container) return;
    
    container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 20px;">Loading usage records...</p>';
    
    try {
        const res = await fetch(apiBase + '/api/v1/content/usage', {
            headers: { 'Authorization': 'Bearer ' + token }
        });
        const data = await res.json();
        
        if (!res.ok) {
            container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Error: ' + (data.error || 'Failed to load usage records') + '</p>';
            return;
        }
        
        const records = data.data || [];
        
        if (records.length === 0) {
            container.innerHTML = '<p style="text-align: center; color: #64748b; padding: 40px;">No usage records found. Record content usage above to see it here.</p>';
            return;
        }
        
        // Create table
        let html = `
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">ID</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">Content</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">Campaign</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">Tag</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">Usage Context</th>
                            <th style="padding: 12px; text-align: left; font-weight: 600; color: #0f172a;">Date Recorded</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        records.forEach(record => {
            const date = new Date(record.created_at).toLocaleString();
            html += `
                <tr style="border-bottom: 1px solid #e2e8f0; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <td style="padding: 12px; color: #475569;">#${record.id}</td>
                    <td style="padding: 12px;">
                        <div style="font-weight: 500; color: #0f172a;">${record.content_title || 'N/A'}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">ID: ${record.content_item_id} | ${record.content_type || 'N/A'}</div>
                    </td>
                    <td style="padding: 12px; color: #475569;">
                        ${record.campaign_title ? `<div style="font-weight: 500; color: #0f172a;">${record.campaign_title}</div><div style="font-size: 12px; color: #64748b;">ID: ${record.campaign_id}</div>` : '<span style="color: #94a3b8;">—</span>'}
                    </td>
                    <td style="padding: 12px;">
                        ${record.tag_name ? `<span style="background: #e0f2fe; color: #0c4a6e; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500;">${record.tag_name}</span>` : '<span style="color: #94a3b8;">—</span>'}
                    </td>
                    <td style="padding: 12px; color: #475569;">${record.usage_context || '<span style="color: #94a3b8;">—</span>'}</td>
                    <td style="padding: 12px; color: #64748b; font-size: 13px;">${date}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 16px; padding: 12px; background: #f8fafc; border-radius: 8px; text-align: center; color: #64748b; font-size: 14px;">
                <strong>Total Records:</strong> ${records.length}
            </div>
        `;
        
        container.innerHTML = html;
    } catch (err) {
        console.error('Error loading usage history:', err);
        container.innerHTML = '<p style="text-align: center; color: #dc2626; padding: 20px;">Network error: ' + err.message + '</p>';
    }
}

// Quick filter function - toggleable
function applyQuickFilter(value, type, buttonElement) {
    let isActive = false;
    let shouldActivate = true;
    
    // Check current state and toggle
    switch(type) {
        case 'status':
            const statusField = document.getElementById('filterApprovalStatus');
            const onlyApprovedCheck = document.getElementById('onlyApproved');
            isActive = statusField.value === value || (value === 'approved' && onlyApprovedCheck.checked);
            
            if (isActive) {
                // Toggle off - clear the filter
                statusField.value = '';
                onlyApprovedCheck.checked = false;
                shouldActivate = false;
            } else {
                // Toggle on - set the filter
                statusField.value = value;
                if (value === 'approved') {
                    onlyApprovedCheck.checked = true;
                }
                shouldActivate = true;
            }
            break;
        case 'hazard':
            const hazardField = document.getElementById('filterHazardCategory');
            isActive = hazardField.value === value;
            
            if (isActive) {
                hazardField.value = '';
                shouldActivate = false;
            } else {
                hazardField.value = value;
                shouldActivate = true;
            }
            break;
        case 'type':
            const typeField = document.getElementById('filterContentType');
            isActive = typeField.value === value;
            
            if (isActive) {
                typeField.value = '';
                shouldActivate = false;
            } else {
                typeField.value = value;
                shouldActivate = true;
            }
            break;
    }
    
    // Update button visual state
    if (shouldActivate) {
        buttonElement.style.background = '#4c8a89';
        buttonElement.style.color = 'white';
        buttonElement.style.borderColor = '#4c8a89';
        buttonElement.classList.add('active');
    } else {
        buttonElement.style.background = 'white';
        buttonElement.style.color = '#475569';
        buttonElement.style.borderColor = '#e2e8f0';
        buttonElement.classList.remove('active');
    }
    
    // Update other buttons of the same type to inactive state
    if (shouldActivate) {
        document.querySelectorAll(`.quick-filter-btn[data-filter-type="${type}"]`).forEach(btn => {
            if (btn !== buttonElement) {
                btn.style.background = 'white';
                btn.style.color = '#475569';
                btn.style.borderColor = '#e2e8f0';
                btn.classList.remove('active');
            }
        });
    }
    
    updateActiveFilters();
    applyFilters();
}

// Show search tips modal
function showSearchTips() {
    const tips = `
        <div style="max-width: 500px; padding: 20px;">
            <h3 style="margin: 0 0 16px 0; color: #0f172a;">🔍 Search Tips</h3>
            <div style="line-height: 1.8; color: #475569;">
                <p style="margin: 0 0 12px 0;"><strong>How Search & Filter Works:</strong></p>
                <ul style="margin: 0 0 16px 0; padding-left: 20px;">
                    <li><strong>Search</strong> - Finds content by title or description (auto-searches as you type)</li>
                    <li><strong>Content Type</strong> - Filter by poster, video, guideline, or infographic</li>
                    <li><strong>Hazard Category</strong> - Filter by fire, flood, earthquake, typhoon, or health</li>
                    <li><strong>Audience</strong> - Filter by target audience (households, youth, schools, etc.)</li>
                    <li><strong>Source</strong> - Filter by inspection-based, training-based, or barangay-created</li>
                    <li><strong>Status</strong> - Filter by draft, pending, approved, or rejected</li>
                </ul>
                <p style="margin: 0 0 12px 0;"><strong>💡 Pro Tips:</strong></p>
                <ul style="margin: 0; padding-left: 20px;">
                    <li>Use multiple filters together for precise results</li>
                    <li>Click filter chips to remove individual filters</li>
                    <li>Filtered content appears in Content Library below</li>
                    <li>Approved content can be used as Templates</li>
                    <li>Media files appear in Media Gallery section</li>
                </ul>
            </div>
        </div>
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.onclick = function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    
    const content = document.createElement('div');
    content.style.cssText = 'background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); max-width: 500px; max-height: 80vh; overflow-y: auto;';
    content.innerHTML = tips + '<button onclick="this.closest(\'div[style*=\\\'position: fixed\\\']\').remove()" style="margin-top: 16px; padding: 8px 16px; background: #4c8a89; color: white; border: none; border-radius: 6px; cursor: pointer; width: 100%;">Got it!</button>';
    content.onclick = function(e) {
        if (e.target.tagName === 'BUTTON') {
            document.body.removeChild(modal);
        }
    };
    
    modal.appendChild(content);
    document.body.appendChild(modal);
}

// Quick filter function - toggleable (replaced by the one above, keeping this as fallback)
// This function is now handled by the toggleable version above

// Show search tips modal
function showSearchTips() {
    const tips = `
        <div style="max-width: 600px; padding: 24px;">
            <h3 style="margin: 0 0 20px 0; color: #0f172a; font-size: 20px;">🔍 How Search & Filter Works</h3>
            <div style="line-height: 1.8; color: #475569; font-size: 14px;">
                <div style="margin-bottom: 20px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 8px;">📋 Connection to Other Features:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Content Library</strong> - Shows filtered results here (you can approve, reject, attach to campaigns)</li>
                        <li><strong>Content Templates</strong> - Only approved content appears as reusable templates</li>
                        <li><strong>Media Gallery</strong> - Approved images/videos appear in visual gallery</li>
                        <li><strong>Upload Content</strong> - New uploads appear in search results after creation</li>
                    </ul>
                </div>
                <div style="margin-bottom: 20px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 8px;">🔍 Search Tips:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>Search by <strong>keywords</strong>: "fire", "safety", "evacuation", "health"</li>
                        <li>Search by <strong>content titles</strong>: Type part of the title</li>
                        <li><strong>Auto-searches</strong> as you type (500ms delay)</li>
                        <li>Use <strong>datalist suggestions</strong> for common searches</li>
                    </ul>
                </div>
                <div style="margin-bottom: 20px;">
                    <strong style="color: #0f172a; display: block; margin-bottom: 8px;">🎯 Filter Tips:</strong>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Combine filters</strong> for precise results (e.g., Fire + Poster + Approved)</li>
                        <li><strong>Quick Filters</strong> buttons set common filter combinations</li>
                        <li><strong>Active filters</strong> shown as chips - click × to remove</li>
                        <li><strong>Only Approved</strong> checkbox shows content ready for use</li>
                    </ul>
                </div>
                <div style="background: #f0fdfa; padding: 12px; border-radius: 8px; border-left: 4px solid #4c8a89;">
                    <strong style="color: #065f46;">💡 Workflow Example:</strong><br>
                    1. Search "fire safety" → 2. Filter by "Poster" → 3. Filter by "Approved" → 
                    4. See results in Content Library → 5. Use approved items as Templates or in Media Gallery
                </div>
            </div>
        </div>
    `;
    
    // Create modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.onclick = function(e) {
        if (e.target === modal) {
            document.body.removeChild(modal);
        }
    };
    
    const content = document.createElement('div');
    content.style.cssText = 'background: white; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); max-width: 600px; max-height: 80vh; overflow-y: auto;';
    content.innerHTML = tips + '<button onclick="this.closest(\'div[style*=\\\'position: fixed\\\']\').remove()" style="margin-top: 20px; padding: 10px 24px; background: #4c8a89; color: white; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: 600;">Got it!</button>';
    content.onclick = function(e) {
        if (e.target.tagName === 'BUTTON') {
            document.body.removeChild(modal);
        }
    };
    
    modal.appendChild(content);
    document.body.appendChild(modal);
}

// Ensure sidebar navigation works for content module
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== CONTENT MODULE INITIALIZATION ===');
    console.log('API Base:', apiBase);
    console.log('Token available:', !!token);
    
    // Wait for sidebar script to initialize
    setTimeout(() => {
        // Load content first
        console.log('Loading content on page load...');
loadContent();
        
        // Load templates and media gallery
        loadTemplates();
        loadMediaGallery();
        
        // Load usage history
        if (document.getElementById('usageHistoryContainer')) {
            loadUsageHistory();
        }
        
        // Set up real-time filter updates
        const searchInput = document.getElementById('searchQuery');
        const audienceInput = document.getElementById('filterAudience');
        const searchIndicator = document.getElementById('searchIndicator');
        
        // Debounced search input
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                if (this.value.trim()) {
                    if (searchIndicator) searchIndicator.style.display = 'inline';
                } else {
                    if (searchIndicator) searchIndicator.style.display = 'none';
                }
                
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    if (typeof updateActiveFilters === 'function') updateActiveFilters();
                    loadContent();
                    if (searchIndicator) searchIndicator.style.display = 'none';
                }, 500);
            });
        }
        
        // Debounced audience input
        if (audienceInput) {
            audienceInput.addEventListener('input', function() {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    if (typeof updateActiveFilters === 'function') updateActiveFilters();
                }, 300);
            });
        }
        
        // Auto-update filters on dropdown change
        ['filterContentType', 'filterHazardCategory', 'filterSource', 'filterApprovalStatus', 'onlyApproved'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('change', function() {
                    if (typeof updateActiveFilters === 'function') updateActiveFilters();
                });
            }
        });
        
        // Initial filter update
        if (typeof updateActiveFilters === 'function') updateActiveFilters();
        
        // Handle hash navigation on page load
        if (window.location.hash) {
            const targetId = window.location.hash.substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                setTimeout(() => {
                    const headerOffset = 90;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 300);
            }
        }
    }, 300);
});
</script>
    </main>
</body>
</html>
