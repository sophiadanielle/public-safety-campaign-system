<?php
/**
 * Reusable Sidebar Component
 * Include this file in your pages where you want a sidebar: <?php include 'sidebar/sidebar.php'; ?>
 * 
 * Features:
 * - Responsive design with mobile toggle
 * - Admin-style navigation
 * - Collapsible sections
 * - Dark mode support
 * - Multiple layout options
 */
?>

<!-- Main Sidebar Component -->
<aside class="main-sidebar sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <?php
                require_once __DIR__ . '/../../header/includes/path_helper.php';
                ?>
                <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="" class="logo-img">
            </div>
        </div>
    </div>
    
    <div class="sidebar-content">
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
            <!-- Navigation -->
            <div class="sidebar-section">
                <h3 class="sidebar-section-title">Navigation</h3>
                <ul class="sidebar-menu">
                    <?php
                        $page = basename($_SERVER['PHP_SELF']);
                        require_once __DIR__ . '/../../header/includes/path_helper.php';
                        
                        // Define modules with their nested features
                        $modules = [
                            'dashboard.php' => [
                                'label' => 'Dashboard',
                                'icon' => 'fa-home',
                                'features' => [
                                    ['label' => 'KPI Overview', 'href' => '#kpi-overview', 'icon' => 'fa-chart-bar'],
                                    ['label' => 'Campaign Planning', 'href' => '#campaign-planning', 'icon' => 'fa-bullhorn'],
                                    ['label' => 'Event Readiness', 'href' => '#event-readiness', 'icon' => 'fa-calendar-check'],
                                    ['label' => 'Audience Coverage', 'href' => '#audience-coverage', 'icon' => 'fa-users'],
                                    ['label' => 'Engagement & Impact', 'href' => '#engagement-impact', 'icon' => 'fa-chart-line'],
                                    ['label' => 'Partners', 'href' => '#partners-snapshot', 'icon' => 'fa-handshake'],
                                    ['label' => 'Content Repository', 'href' => '#content-snapshot', 'icon' => 'fa-book'],
                                    ['label' => 'Alerts & Reminders', 'href' => '#alerts-reminders', 'icon' => 'fa-bell'],
                                ]
                            ],
                            'campaigns.php' => [
                                'label' => 'Campaigns',
                                'icon' => 'fa-bullhorn',
                                'features' => [
                                    ['label' => 'Plan New Campaign', 'href' => '#planning-section', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'All Campaigns', 'href' => '#list-section', 'icon' => 'fa-list'],
                                    ['label' => 'Gantt Chart', 'href' => '#timeline-section', 'icon' => 'fa-chart-gantt'],
                                    ['label' => 'Resource Allocation', 'href' => '#resources-section', 'icon' => 'fa-cubes'],
                                    ['label' => 'AI-Powered Deployment Optimization', 'href' => '#automl-section', 'icon' => 'fa-robot'],
                                    ['label' => 'Target Segments', 'href' => '#segments-section', 'icon' => 'fa-users'],
                                ]
                            ],
                            'content.php' => [
                                'label' => 'Content',
                                'icon' => 'fa-file-alt',
                                'features' => [
                                    ['label' => 'All Content', 'href' => '#content-list', 'icon' => 'fa-list'],
                                    ['label' => 'Create New Content', 'href' => '#create-content', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'Content Library', 'href' => '#content-library', 'icon' => 'fa-book'],
                                    ['label' => 'Templates', 'href' => '#templates', 'icon' => 'fa-file-alt'],
                                    ['label' => 'Media Gallery', 'href' => '#media-gallery', 'icon' => 'fa-images'],
                                    ['label' => 'Record Content Usage', 'href' => '#record-usage', 'icon' => 'fa-clipboard-list'],
                                    ['label' => 'Content Usage History', 'href' => '#usage-history', 'icon' => 'fa-history'],
                                ]
                            ],
                            'segments.php' => [
                                'label' => 'Segments',
                                'icon' => 'fa-users',
                                'features' => [
                                    ['label' => 'All Segments', 'href' => '#segments-list', 'icon' => 'fa-list'],
                                    ['label' => 'Create Segment', 'href' => '#create-segment', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'Audience Members', 'href' => '#audience-members', 'icon' => 'fa-users'],
                                    ['label' => 'Segment Analytics', 'href' => '#segment-analytics', 'icon' => 'fa-chart-bar'],
                                    ['label' => 'Import/Export', 'href' => '#import-export', 'icon' => 'fa-file-import'],
                                ]
                            ],
                            'events.php' => [
                                'label' => 'Events',
                                'icon' => 'fa-calendar',
                                'features' => [
                                    ['label' => 'All Events', 'href' => '#events-list', 'icon' => 'fa-list'],
                                    ['label' => 'Create Event', 'href' => '#create-event', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'Event Calendar', 'href' => '#event-calendar', 'icon' => 'fa-calendar'],
                                    ['label' => 'Attendance Tracking', 'href' => '#attendance', 'icon' => 'fa-check-circle'],
                                    ['label' => 'Event Reports', 'href' => '#event-reports', 'icon' => 'fa-file-alt'],
                                ]
                            ],
                            'surveys.php' => [
                                'label' => 'Surveys',
                                'icon' => 'fa-clipboard-list',
                                'features' => [
                                    ['label' => 'All Surveys', 'href' => '#surveys-list', 'icon' => 'fa-list'],
                                    ['label' => 'Create Survey', 'href' => '#create-survey', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'Survey Builder', 'href' => '#survey-builder', 'icon' => 'fa-tools'],
                                    ['label' => 'Responses', 'href' => '#responses', 'icon' => 'fa-clipboard-check'],
                                    ['label' => 'Analytics', 'href' => '#survey-analytics', 'icon' => 'fa-chart-pie'],
                                ]
                            ],
                            'impact.php' => [
                                'label' => 'Impact',
                                'icon' => 'fa-chart-line',
                                'features' => [
                                    ['label' => 'Dashboard', 'href' => '#impact-dashboard', 'icon' => 'fa-chart-line'],
                                    ['label' => 'Evaluation Reports', 'href' => '#evaluation-reports', 'icon' => 'fa-file-alt'],
                                    ['label' => 'Metrics Overview', 'href' => '#metrics-overview', 'icon' => 'fa-chart-bar'],
                                    ['label' => 'Performance Analysis', 'href' => '#performance-analysis', 'icon' => 'fa-analytics'],
                                    ['label' => 'Export Data', 'href' => '#export-data', 'icon' => 'fa-download'],
                                ]
                            ],
                            'partners.php' => [
                                'label' => 'Partners',
                                'icon' => 'fa-handshake',
                                'features' => [
                                    ['label' => 'All Partners', 'href' => '#partners-list', 'icon' => 'fa-list'],
                                    ['label' => 'Add Partner', 'href' => '#add-partner', 'icon' => 'fa-plus-circle'],
                                    ['label' => 'Partner Portal', 'href' => '#partner-portal', 'icon' => 'fa-door-open'],
                                    ['label' => 'Engagement History', 'href' => '#engagement-history', 'icon' => 'fa-history'],
                                    ['label' => 'Assignments', 'href' => '#assignments', 'icon' => 'fa-tasks'],
                                ]
                            ],
                        ];
                        
                        foreach ($modules as $href => $module):
                            $linkUrl = $publicPath . '/' . $href;
                            $isActive = $page === $href;
                            $moduleName = str_replace('.php', '', $href);
                            $hasFeatures = isset($module['features']) && !empty($module['features']);
                            $hasActiveFeature = false;
                            
                            // Check if any feature is active (for campaigns page with anchors)
                            if ($hasFeatures && $isActive) {
                                foreach ($module['features'] as $feature) {
                                    $featureHref = str_replace('#', '', $feature['href']);
                                    if (isset($_GET['section']) && $_GET['section'] === $featureHref) {
                                        $hasActiveFeature = true;
                                        break;
                                    }
                                }
                            }
                    ?>
                    <li class="sidebar-menu-item <?php echo $hasFeatures ? 'has-children' : ''; ?>">
                        <?php if ($hasFeatures): ?>
                            <a href="<?php echo htmlspecialchars($linkUrl); ?>" class="sidebar-link sidebar-module-toggle <?php echo $isActive ? 'active' : ''; ?>" data-module="<?php echo htmlspecialchars($moduleName); ?>" data-href="<?php echo htmlspecialchars($linkUrl); ?>">
                                <?php if (isset($module['icon'])): ?>
                                    <i class="fas <?php echo htmlspecialchars($module['icon']); ?>"></i>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($module['label']); ?></span>
                                <i class="fas <?php echo ($isActive || $hasActiveFeature) ? 'fa-chevron-up' : 'fa-chevron-down'; ?> submenu-icon" style="margin-left: auto; font-size: 0.75rem;"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo htmlspecialchars($linkUrl); ?>" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>" data-module="<?php echo htmlspecialchars($moduleName); ?>">
                                <?php if (isset($module['icon'])): ?>
                                    <i class="fas <?php echo htmlspecialchars($module['icon']); ?>"></i>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($module['label']); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if ($hasFeatures): ?>
                            <ul class="sidebar-submenu <?php echo ($isActive || $hasActiveFeature) ? 'submenu-open' : ''; ?>" id="submenu-<?php echo htmlspecialchars($moduleName); ?>">
                                <?php foreach ($module['features'] as $feature): ?>
                                    <li class="sidebar-menu-item">
                                        <a href="<?php echo htmlspecialchars($linkUrl . $feature['href']); ?>" class="sidebar-link sidebar-submenu-link" data-section="<?php echo htmlspecialchars(str_replace('#', '', $feature['href'])); ?>" data-href="<?php echo htmlspecialchars($feature['href']); ?>">
                                            <?php if (isset($feature['icon'])): ?>
                                                <i class="fas <?php echo htmlspecialchars($feature['icon']); ?>"></i>
                                            <?php endif; ?>
                                            <span><?php echo htmlspecialchars($feature['label']); ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    </div>
</aside>

<!-- Sidebar Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar functionality
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('sidebar-open');
        sidebarOverlay.classList.toggle('sidebar-overlay-open');
        document.body.classList.toggle('sidebar-open');
    }
    
    // Close sidebar
    function closeSidebar() {
        sidebar.classList.remove('sidebar-open');
        sidebarOverlay.classList.remove('sidebar-overlay-open');
        document.body.classList.remove('sidebar-open');
    }

    // Expose functions globally so other scripts
    // can trigger the sidebar without duplicating logic.
    window.sidebarToggle = toggleSidebar;
    window.sidebarClose = closeSidebar;
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('sidebar-open')) {
            closeSidebar();
        }
    });
    
    // Accordion behavior for module submenus
    function toggleModuleSubmenu(link, preventNavigation = false) {
        const menuItem = link.closest('.sidebar-menu-item');
        const submenu = menuItem ? menuItem.querySelector('.sidebar-submenu') : null;
        const icon = link.querySelector('.submenu-icon');
        const isCurrentlyOpen = submenu && submenu.classList.contains('submenu-open');
        
        // Check if module sidebar is present - if so, don't toggle main sidebar submenu
        const moduleName = link.getAttribute('data-module');
        const moduleSidebar = document.querySelector('.module-sidebar[data-module="' + moduleName + '"]');
        const isModuleSidebarVisible = moduleSidebar && window.getComputedStyle(moduleSidebar).display !== 'none';
        
        // If module sidebar is visible, prevent main sidebar submenu from opening
        if (isModuleSidebarVisible && submenu) {
            // Keep submenu closed and just navigate
            submenu.classList.remove('submenu-open');
            if (icon) {
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            }
            return false;
        }
        
        // Close all other submenus (accordion behavior)
        document.querySelectorAll('.sidebar-submenu').forEach(menu => {
            if (menu !== submenu) {
                menu.classList.remove('submenu-open');
            }
        });
        
        // Remove active class from other module toggles (but keep active if on their page)
        document.querySelectorAll('.sidebar-module-toggle').forEach(toggle => {
            if (toggle !== link) {
                // Only remove active if not on that module's page
                const toggleHref = toggle.getAttribute('data-href') || toggle.getAttribute('href');
                const currentPage = window.location.pathname;
                const togglePage = toggleHref.split('#')[0];
                const isOnTogglePage = currentPage.includes(togglePage.split('/').pop() || '');
                if (!isOnTogglePage) {
                    toggle.classList.remove('active');
                }
            }
        });
        
        // Update all chevron icons for closed submenus
        document.querySelectorAll('.sidebar-module-toggle .submenu-icon').forEach(chevron => {
            if (chevron !== icon) {
                const parentToggle = chevron.closest('.sidebar-module-toggle');
                const parentMenuItem = parentToggle ? parentToggle.closest('.sidebar-menu-item') : null;
                const parentSubmenu = parentMenuItem ? parentMenuItem.querySelector('.sidebar-submenu') : null;
                if (parentSubmenu && !parentSubmenu.classList.contains('submenu-open')) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            }
        });
        
        // Toggle current submenu
        if (submenu) {
            if (isCurrentlyOpen) {
                // Close submenu
                submenu.classList.remove('submenu-open');
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
            } else {
                // Open submenu
                submenu.classList.add('submenu-open');
                link.classList.add('active');
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        }
        
        return false; // Prevent default navigation if called programmatically
    }
    
    // Handle module toggle clicks
    const moduleToggles = document.querySelectorAll('.sidebar-module-toggle');
    moduleToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            const href = this.getAttribute('data-href') || this.getAttribute('href');
            const currentPage = window.location.pathname;
            const targetPage = href.split('#')[0];
            const targetPageName = targetPage.split('/').pop() || '';
            const isOnTargetPage = currentPage.includes(targetPageName) || currentPage.endsWith(targetPageName);
            
            // Get submenu state before navigation
            const menuItem = this.closest('.sidebar-menu-item');
            const submenu = menuItem ? menuItem.querySelector('.sidebar-submenu') : null;
            const isCurrentlyOpen = submenu && submenu.classList.contains('submenu-open');
            
            // If not on the target page, navigate first
            if (!isOnTargetPage) {
                // Navigate to the page - the page will auto-expand the submenu
                window.location.href = href;
                return;
            }
            
            // We're on the target page, toggle submenu
            e.preventDefault();
            e.stopPropagation();
            toggleModuleSubmenu(this, true);
        });
    });
    
    // Expose function globally
    window.toggleModuleSubmenu = toggleModuleSubmenu;
    
    // Force hide main sidebar submenus when module sidebar is present
    function hideMainSidebarSubmenus() {
        const moduleSidebars = document.querySelectorAll('.module-sidebar[data-module]');
        moduleSidebars.forEach(moduleSidebar => {
            const moduleName = moduleSidebar.getAttribute('data-module');
            const isVisible = window.getComputedStyle(moduleSidebar).display !== 'none';
            
            if (isVisible) {
                // Hide the corresponding main sidebar submenu
                const mainSubmenu = document.getElementById('submenu-' + moduleName);
                if (mainSubmenu) {
                    mainSubmenu.style.display = 'none';
                    mainSubmenu.style.visibility = 'hidden';
                    mainSubmenu.style.maxHeight = '0';
                    mainSubmenu.style.margin = '0';
                    mainSubmenu.style.padding = '0';
                    mainSubmenu.style.overflow = 'hidden';
                    mainSubmenu.style.opacity = '0';
                    mainSubmenu.style.height = '0';
                    mainSubmenu.classList.remove('submenu-open');
                    
                    // Also hide all items inside
                    const items = mainSubmenu.querySelectorAll('.sidebar-menu-item, .sidebar-link');
                    items.forEach(item => {
                        item.style.display = 'none';
                        item.style.height = '0';
                        item.style.margin = '0';
                        item.style.padding = '0';
                        item.style.overflow = 'hidden';
                    });
                }
                
                // Update chevron icon
                const toggleLink = document.querySelector('.sidebar-module-toggle[data-module="' + moduleName + '"]');
                if (toggleLink) {
                    const icon = toggleLink.querySelector('.submenu-icon');
                    if (icon) {
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                }
            }
        });
    }
    
    // Run on page load and after a short delay
    hideMainSidebarSubmenus();
    setTimeout(hideMainSidebarSubmenus, 50);
    setTimeout(hideMainSidebarSubmenus, 200);
    
    // Also run when window is resized or module sidebar visibility changes
    const moduleSidebars = document.querySelectorAll('.module-sidebar[data-module]');
    if (moduleSidebars.length > 0) {
        const observer = new MutationObserver(function(mutations) {
            hideMainSidebarSubmenus();
        });
        
        // Observe module sidebar visibility changes
        moduleSidebars.forEach(moduleSidebar => {
            observer.observe(moduleSidebar, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        });
    }
    
    // Auto-expand submenu if parent module is active (on page load)
    // BUT only if module sidebar is not present
    setTimeout(() => {
        const activeModuleLinks = document.querySelectorAll('.sidebar-module-toggle.active');
        activeModuleLinks.forEach(link => {
            const moduleName = link.getAttribute('data-module');
            const moduleSidebar = document.querySelector('.module-sidebar[data-module="' + moduleName + '"]');
            const isModuleSidebarVisible = moduleSidebar && window.getComputedStyle(moduleSidebar).display !== 'none';
            
            // If module sidebar is visible, keep main sidebar submenu closed
            if (isModuleSidebarVisible) {
                const menuItem = link.closest('.sidebar-menu-item');
                const submenu = menuItem ? menuItem.querySelector('.sidebar-submenu') : null;
                const icon = link.querySelector('.submenu-icon');
                
                if (submenu) {
                    submenu.classList.remove('submenu-open');
                    submenu.style.display = 'none';
                }
                if (icon) {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                }
                return; // Skip this link
            }
            
            const menuItem = link.closest('.sidebar-menu-item');
            const submenu = menuItem ? menuItem.querySelector('.sidebar-submenu') : null;
            const icon = link.querySelector('.submenu-icon');
            
            if (submenu && submenu.classList.contains('submenu-open')) {
                // Submenu is already open (from PHP), ensure icon is correct
                if (icon) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                }
            }
        });
    }, 50); // Small delay to ensure DOM is ready
    
    // Handle submenu link clicks (for anchor links)
    const submenuLinks = document.querySelectorAll('.sidebar-submenu-link');
    submenuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const anchorHref = this.getAttribute('data-href') || this.getAttribute('href');
            const fullHref = this.getAttribute('href');
            
            if (anchorHref && anchorHref.startsWith('#')) {
                // Check if we're on the correct page
                const currentPage = window.location.pathname;
                const linkPage = fullHref.split('#')[0];
                const linkPageFile = linkPage.split('/').pop();
                
                // If not on the correct page, navigate first
                if (linkPage && linkPageFile && !currentPage.includes(linkPageFile)) {
                    // Navigate to the page with anchor - let browser handle it
                    return;
                }
                
                // We're on the correct page, scroll to anchor
                e.preventDefault();
                const targetElement = document.querySelector(anchorHref);
                if (targetElement) {
                    const headerOffset = 90;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Update active state
                    submenuLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Ensure parent module is active
                    const parentToggle = this.closest('.sidebar-menu-item.has-children')?.querySelector('.sidebar-module-toggle');
                    if (parentToggle) {
                        document.querySelectorAll('.sidebar-module-toggle').forEach(t => t.classList.remove('active'));
                        parentToggle.classList.add('active');
                    }
                }
            }
        });
    });
    
    // Highlight active submenu item on page load based on hash or scroll position
    function highlightActiveSubmenuItem() {
        const hash = window.location.hash;
        if (hash) {
            const targetLink = document.querySelector(`.sidebar-submenu-link[data-href="${hash}"], .sidebar-submenu-link[href*="${hash}"]`);
            if (targetLink) {
                submenuLinks.forEach(l => l.classList.remove('active'));
                targetLink.classList.add('active');
            }
        } else {
            // Use Intersection Observer to highlight visible section
            const sections = document.querySelectorAll('section[id]');
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.getAttribute('id');
                        const targetLink = document.querySelector(`.sidebar-submenu-link[data-href="#${id}"], .sidebar-submenu-link[href*="#${id}"]`);
                        if (targetLink) {
                            submenuLinks.forEach(l => l.classList.remove('active'));
                            targetLink.classList.add('active');
                        }
                    }
                });
            }, {
                rootMargin: '-90px 0px -66% 0px',
                threshold: 0
            });
            
            sections.forEach(section => observer.observe(section));
        }
    }
    
    // Run on page load
    highlightActiveSubmenuItem();
});
</script>
