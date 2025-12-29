<?php
/**
 * Module Sub-Sidebar Component
 * Contextual sidebar that appears for each module
 * 
 * Usage: Include this file in module pages and set $moduleName variable
 * Example: $moduleName = 'campaigns'; include 'module-sidebar.php';
 */

if (!isset($moduleName)) {
    return; // Don't render if module name not set
}

require_once __DIR__ . '/../../header/includes/path_helper.php';

// Define module-specific navigation items
$moduleNavItems = [
    'dashboard' => [
        ['label' => 'KPI Overview', 'href' => '#kpi-overview', 'icon' => 'fa-chart-bar'],
        ['label' => 'Campaign Planning', 'href' => '#campaign-planning', 'icon' => 'fa-bullhorn'],
        ['label' => 'Event Readiness', 'href' => '#event-readiness', 'icon' => 'fa-calendar-check'],
        ['label' => 'Audience Coverage', 'href' => '#audience-coverage', 'icon' => 'fa-users'],
        ['label' => 'Engagement & Impact', 'href' => '#engagement-impact', 'icon' => 'fa-chart-line'],
        ['label' => 'Partners', 'href' => '#partners-snapshot', 'icon' => 'fa-handshake'],
        ['label' => 'Content Repository', 'href' => '#content-snapshot', 'icon' => 'fa-book'],
    ],
    'campaigns' => [
        ['label' => 'Plan New Campaign', 'href' => '#planning-section', 'icon' => 'fa-plus-circle'],
        ['label' => 'All Campaigns', 'href' => '#list-section', 'icon' => 'fa-list'],
        ['label' => 'Gantt Chart', 'href' => '#timeline-section', 'icon' => 'fa-chart-gantt'],
        ['label' => 'Resource Allocation', 'href' => '#resources-section', 'icon' => 'fa-cubes'],
        ['label' => 'AI-Powered Deployment Optimization', 'href' => '#automl-section', 'icon' => 'fa-robot'],
        ['label' => 'Target Segments', 'href' => '#segments-section', 'icon' => 'fa-users'],
    ],
    'content' => [
        ['label' => 'All Content', 'href' => '#content-list', 'icon' => 'fa-list'],
        ['label' => 'Create New Content', 'href' => '#create-content', 'icon' => 'fa-plus-circle'],
        ['label' => 'Content Library', 'href' => '#content-library', 'icon' => 'fa-book'],
        ['label' => 'Templates', 'href' => '#templates', 'icon' => 'fa-file-alt'],
        ['label' => 'Media Gallery', 'href' => '#media-gallery', 'icon' => 'fa-images'],
        ['label' => 'Record Content Usage', 'href' => '#record-usage', 'icon' => 'fa-clipboard-list'],
        ['label' => 'Content Usage History', 'href' => '#usage-history', 'icon' => 'fa-history'],
    ],
    'segments' => [
        ['label' => 'All Segments', 'href' => '#segments-list', 'icon' => 'fa-list'],
        ['label' => 'Create Segment', 'href' => '#create-segment', 'icon' => 'fa-plus-circle'],
        ['label' => 'Audience Members', 'href' => '#audience-members', 'icon' => 'fa-users'],
        ['label' => 'Segment Analytics', 'href' => '#segment-analytics', 'icon' => 'fa-chart-bar'],
        ['label' => 'Import/Export', 'href' => '#import-export', 'icon' => 'fa-file-import'],
    ],
    'events' => [
        ['label' => 'All Events', 'href' => '#events-list', 'icon' => 'fa-list'],
        ['label' => 'Create Event', 'href' => '#create-event', 'icon' => 'fa-plus-circle'],
        ['label' => 'Event Calendar', 'href' => '#event-calendar', 'icon' => 'fa-calendar'],
        ['label' => 'Attendance Tracking', 'href' => '#attendance', 'icon' => 'fa-check-circle'],
        ['label' => 'Event Reports', 'href' => '#event-reports', 'icon' => 'fa-file-alt'],
    ],
    'surveys' => [
        ['label' => 'All Surveys', 'href' => '#surveys-list', 'icon' => 'fa-list'],
        ['label' => 'Create Survey', 'href' => '#create-survey', 'icon' => 'fa-plus-circle'],
        ['label' => 'Survey Builder', 'href' => '#survey-builder', 'icon' => 'fa-tools'],
        ['label' => 'Responses', 'href' => '#responses', 'icon' => 'fa-clipboard-check'],
        ['label' => 'Analytics', 'href' => '#survey-analytics', 'icon' => 'fa-chart-pie'],
    ],
    'impact' => [
        ['label' => 'Dashboard', 'href' => '#impact-dashboard', 'icon' => 'fa-chart-line'],
        ['label' => 'Evaluation Reports', 'href' => '#evaluation-reports', 'icon' => 'fa-file-alt'],
        ['label' => 'Metrics Overview', 'href' => '#metrics-overview', 'icon' => 'fa-chart-bar'],
        ['label' => 'Performance Analysis', 'href' => '#performance-analysis', 'icon' => 'fa-analytics'],
        ['label' => 'Export Data', 'href' => '#export-data', 'icon' => 'fa-download'],
    ],
    'partners' => [
        ['label' => 'All Partners', 'href' => '#partners-list', 'icon' => 'fa-list'],
        ['label' => 'Add Partner', 'href' => '#add-partner', 'icon' => 'fa-plus-circle'],
        ['label' => 'Partner Portal', 'href' => '#partner-portal', 'icon' => 'fa-door-open'],
        ['label' => 'Engagement History', 'href' => '#engagement-history', 'icon' => 'fa-history'],
        ['label' => 'Assignments', 'href' => '#assignments', 'icon' => 'fa-tasks'],
    ],
];

// Get navigation items for current module
$navItems = $moduleNavItems[$moduleName] ?? [];
?>

<!-- Module Sub-Sidebar -->
<aside class="module-sidebar" id="moduleSidebar" data-module="<?php echo htmlspecialchars($moduleName); ?>">
    <div class="module-sidebar-header">
        <h3 class="module-sidebar-title">
            <?php
            $moduleTitles = [
                'dashboard' => 'Dashboard Features',
                'campaigns' => 'Campaign Features',
                'content' => 'Content Management',
                'segments' => 'Audience Segments',
                'events' => 'Event Management',
                'surveys' => 'Survey Tools',
                'impact' => 'Impact Analysis',
                'partners' => 'Partner Management',
            ];
            echo htmlspecialchars($moduleTitles[$moduleName] ?? ucfirst($moduleName));
            ?>
        </h3>
    </div>
    
    <div class="module-sidebar-content">
        <nav class="module-sidebar-nav">
            <ul class="module-sidebar-menu">
                <?php foreach ($navItems as $item): ?>
                    <li class="module-sidebar-menu-item">
                        <a href="<?php echo htmlspecialchars($item['href']); ?>" class="module-sidebar-link" data-section="<?php echo htmlspecialchars(str_replace('#', '', $item['href'])); ?>">
                            <?php if (isset($item['icon'])): ?>
                                <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scroll to sections when clicking module sidebar links
    const moduleLinks = document.querySelectorAll('.module-sidebar-link[href^="#"]');
    moduleLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href && href.startsWith('#')) {
                const targetId = href.substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    e.preventDefault();
                    const headerOffset = 90; // Account for fixed header
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Update active state
                    moduleLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                }
            }
        });
    });
    
    // Highlight active section on scroll
    const sections = document.querySelectorAll('section[id]');
    const observerOptions = {
        rootMargin: '-90px 0px -66% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                moduleLinks.forEach(link => {
                    if (link.getAttribute('href') === '#' + id) {
                        moduleLinks.forEach(l => l.classList.remove('active'));
                        link.classList.add('active');
                    }
                });
            }
        });
    }, observerOptions);
    
    sections.forEach(section => observer.observe(section));
});
</script>
