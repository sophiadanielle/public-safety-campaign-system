# Module Sidebar Setup Guide

## ✅ Completed

1. **Module Sidebar Component** (`sidebar/includes/module-sidebar.php`)
   - Reusable component that shows contextual navigation for each module
   - Automatically displays module-specific features

2. **Module Sidebar CSS** (`sidebar/css/module-sidebar.css`)
   - Styling for the module sidebar
   - Responsive design
   - Positioned next to main sidebar

3. **Main Sidebar Updated** (`sidebar/includes/sidebar.php`)
   - Added Dashboard as first item
   - Added icons to all menu items
   - Added data-module attributes for module detection

4. **Campaigns Page Updated** (`public/campaigns.php`)
   - Integrated module sidebar
   - Updated layout to account for module sidebar
   - Removed old campaign-sidebar

## 📋 To Complete for Other Modules

For each module page (content.php, segments.php, events.php, surveys.php, impact.php, partners.php), apply these changes:

### 1. Update Header Structure

Replace:
```php
<?php
$pageTitle = 'Module Name';
include __DIR__ . '/../header/includes/header.php';
?>
```

With:
```php
<?php
$pageTitle = 'Module Name';
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
    <link rel="stylesheet" href="<?php echo htmlspecialchars($basePath . '/sidebar/css/module-sidebar.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
</head>
<body class="has-module-sidebar" data-module="MODULE_NAME">
    <?php include __DIR__ . '/../sidebar/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/../sidebar/includes/admin-header.php'; ?>
    
    <?php
    $moduleName = 'MODULE_NAME'; // campaigns, content, segments, events, surveys, impact, partners
    include __DIR__ . '/../sidebar/includes/module-sidebar.php';
    ?>
    
    <main class="main-content-wrapper">
```

### 2. Add CSS for Main Content Wrapper

Add this CSS at the top of your page styles:
```css
<style>
    .main-content-wrapper {
        margin-left: 540px; /* 280px main sidebar + 260px module sidebar */
        margin-top: 70px;
        min-height: calc(100vh - 70px);
        transition: margin-left 0.3s ease;
    }
    
    body:not(.has-module-sidebar) .main-content-wrapper {
        margin-left: 280px;
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
    }
    
    /* Your existing page styles here */
</style>
```

### 3. Close Tags at End

At the end of the file, before closing PHP, add:
```php
    </main>
</body>
</html>
```

### 4. Module Names Mapping

- `content.php` → `$moduleName = 'content'`
- `segments.php` → `$moduleName = 'segments'`
- `events.php` → `$moduleName = 'events'`
- `surveys.php` → `$moduleName = 'surveys'`
- `impact.php` → `$moduleName = 'impact'`
- `partners.php` → `$moduleName = 'partners'`

## 🎯 Module Sidebar Features

Each module has predefined navigation items in `module-sidebar.php`:

- **Campaigns**: Plan New Campaign, All Campaigns, Gantt Chart, Resource Allocation, AI-Powered Deployment Optimization, Target Segments
- **Content**: All Content, Create New Content, Content Library, Templates, Media Gallery
- **Segments**: All Segments, Create Segment, Audience Members, Segment Analytics, Import/Export
- **Events**: All Events, Create Event, Event Calendar, Attendance Tracking, Event Reports
- **Surveys**: All Surveys, Create Survey, Survey Builder, Responses, Analytics
- **Impact**: Dashboard, Evaluation Reports, Metrics Overview, Performance Analysis, Export Data
- **Partners**: All Partners, Add Partner, Partner Portal, Engagement History, Assignments

## 🔧 Customization

To add or modify module sidebar items, edit `sidebar/includes/module-sidebar.php` and update the `$moduleNavItems` array.
