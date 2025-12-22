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

<!-- Sidebar Component -->
<aside class="sidebar" id="sidebar">
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
                        
                        // Add Dashboard as first item
                        $dashboardActive = $page === 'index.php' || $page === 'dashboard.php' || $page === 'campaigns.php';
                        $dashboardUrl = $publicPath . '/campaigns.php';
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo htmlspecialchars($dashboardUrl); ?>" class="sidebar-link <?php echo $dashboardActive ? 'active' : ''; ?>" data-module="dashboard">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <?php
                        $links = [
                            'campaigns.php' => ['label' => 'Campaigns', 'icon' => 'fa-bullhorn'],
                            'content.php' => ['label' => 'Content', 'icon' => 'fa-file-alt'],
                            'segments.php' => ['label' => 'Segments', 'icon' => 'fa-users'],
                            'events.php' => ['label' => 'Events', 'icon' => 'fa-calendar'],
                            'surveys.php' => ['label' => 'Surveys', 'icon' => 'fa-clipboard-list'],
                            'impact.php' => ['label' => 'Impact', 'icon' => 'fa-chart-line'],
                            'partners.php' => ['label' => 'Partners', 'icon' => 'fa-handshake']
                        ];
                        foreach ($links as $href => $item):
                            $linkUrl = $publicPath . '/' . $href;
                            $isActive = $page === $href;
                            $moduleName = str_replace('.php', '', $href);
                    ?>
                    <li class="sidebar-menu-item">
                        <a href="<?php echo htmlspecialchars($linkUrl); ?>" class="sidebar-link <?php echo $isActive ? 'active' : ''; ?>" data-module="<?php echo htmlspecialchars($moduleName); ?>">
                            <?php if (isset($item['icon'])): ?>
                                <i class="fas <?php echo htmlspecialchars($item['icon']); ?>"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($item['label']); ?></span>
                        </a>
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
    
    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const submenu = this.nextElementSibling;
            const icon = this.querySelector('.submenu-icon');
            
            if (submenu) {
                const isOpen = submenu.classList.contains('sidebar-submenu-open');
                submenu.classList.toggle('sidebar-submenu-open');
                this.classList.toggle('active', !isOpen);
                
                // Toggle icon based on new state
                if (icon) {
                    if (submenu.classList.contains('sidebar-submenu-open')) {
                        // Now open - show up chevron
                        icon.classList.remove('fa-chevron-down');
                        icon.classList.add('fa-chevron-up');
                    } else {
                        // Now closed - show down chevron
                        icon.classList.remove('fa-chevron-up');
                        icon.classList.add('fa-chevron-down');
                    }
                }
            }
        });
    });
    
    // Auto-open submenu if it contains active item
    const activeLinks = document.querySelectorAll('.sidebar-submenu .sidebar-link.active');
    activeLinks.forEach(activeLink => {
        const submenu = activeLink.closest('.sidebar-submenu');
        const toggle = submenu ? submenu.previousElementSibling : null;
        
        if (submenu && toggle && toggle.classList.contains('sidebar-submenu-toggle')) {
            submenu.classList.add('sidebar-submenu-open');
            toggle.classList.add('active');
            
            const icon = toggle.querySelector('.submenu-icon');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    });
    
    // Handle module sidebar visibility based on active module
    const moduleLinks = document.querySelectorAll('.sidebar-link[data-module]');
    moduleLinks.forEach(link => {
        link.addEventListener('click', function() {
            const module = this.getAttribute('data-module');
            // Module sidebar visibility is handled by body class set in each page
            // This ensures the sidebar shows/hides correctly when navigating
        });
    });
});
</script>
