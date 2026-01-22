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
                        
                        // Get current user role for module filtering
                        // CRITICAL: This must work consistently across ALL pages
                        require_once __DIR__ . '/get_user_role.php';
                        $currentUserRole = getCurrentUserRole();
                        
                        // CRITICAL FIX: If role is still null, try one more time with session fallback
                        // This handles cases where cookies aren't available but user is logged in
                        if (!$currentUserRole) {
                            // Try to get from session if available (only if headers not sent)
                            if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
                                session_start();
                            }
                            if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_role'])) {
                                $currentUserRole = $_SESSION['user_role'];
                                error_log('RBAC SIDEBAR: Using session fallback - role=' . $currentUserRole);
                            } else {
                                // Last resort: Try to decode JWT from cookie one more time with more aggressive error handling
                                $jwtToken = $_COOKIE['jwt_token'] ?? null;
                                if ($jwtToken) {
                                    try {
                                        require_once __DIR__ . '/../../vendor/autoload.php';
                                        $envPath = __DIR__ . '/../../.env';
                                        $jwtSecret = 'your-secret-key-change-in-production';
                                        $jwtIssuer = 'public-safety-campaign-system';
                                        $jwtAudience = 'public-safety-campaign-system';
                                        
                                        if (file_exists($envPath)) {
                                            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                            foreach ($lines as $line) {
                                                $line = trim($line);
                                                if ($line === '' || strpos($line, '#') === 0) continue;
                                                if (strpos($line, '=') === false) continue;
                                                list($name, $value) = explode('=', $line, 2);
                                                $name = trim($name);
                                                $value = trim($value);
                                                if ($name === 'JWT_SECRET') $jwtSecret = $value;
                                                if ($name === 'JWT_ISSUER') $jwtIssuer = $value;
                                                if ($name === 'JWT_AUDIENCE') $jwtAudience = $value;
                                            }
                                        }
                                        
                                        $decoded = Firebase\JWT\JWT::decode($jwtToken, new Firebase\JWT\Key($jwtSecret, 'HS256'));
                                        if (($decoded->aud ?? null) === $jwtAudience && ($decoded->iss ?? null) === $jwtIssuer) {
                                            $roleId = (int) ($decoded->role_id ?? 0);
                                            if ($roleId > 0) {
                                                require_once __DIR__ . '/../../src/Config/db_connect.php';
                                                if (isset($pdo) && $pdo instanceof PDO) {
                                                    $stmt = $pdo->prepare('SELECT r.name FROM campaign_department_roles r WHERE r.id = :role_id LIMIT 1');
                                                    $stmt->execute(['role_id' => $roleId]);
                                                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                    if ($result) {
                                                        $roleName = strtolower(trim($result['name']));
                                                        $roleMappings = [
                                                            'partner' => 'viewer',
                                                            'partner representative' => 'viewer',
                                                            'partner_representative' => 'viewer',
                                                            'viewer' => 'viewer',
                                                            'staff' => 'staff',
                                                            'secretary' => 'secretary',
                                                            'kagawad' => 'kagawad',
                                                            'captain' => 'captain',
                                                            'admin' => 'admin',
                                                            'barangay administrator' => 'admin',
                                                            'barangay admin' => 'admin',
                                                            'barangay staff' => 'staff',
                                                            'system_admin' => 'admin',
                                                            'barangay_admin' => 'admin',
                                                        ];
                                                        $currentUserRole = $roleMappings[$roleName] ?? $roleName;
                                                        if (strpos($roleName, 'partner') !== false || (strpos($roleName, 'viewer') !== false && !isset($roleMappings[$roleName]))) {
                                                            $currentUserRole = 'viewer';
                                                        }
                                                        // Store in session for future requests (only if headers not sent)
                                                        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
                                                            session_start();
                                                        }
                                                        if (session_status() === PHP_SESSION_ACTIVE) {
                                                            $_SESSION['user_role'] = $currentUserRole;
                                                        }
                                                        // Also set cookie for future requests
                                                        if (!headers_sent()) {
                                                            setcookie('user_role_id', (string)$roleId, [
                                                                'expires' => time() + (30 * 24 * 60 * 60),
                                                                'path' => '/',
                                                                'samesite' => 'Lax'
                                                            ]);
                                                            $_COOKIE['user_role_id'] = (string)$roleId;
                                                        }
                                                        error_log('RBAC SIDEBAR: Last resort JWT decode SUCCESS - role=' . $currentUserRole);
                                                    }
                                                }
                                            }
                                        }
                                    } catch (Exception $e) {
                                        error_log('RBAC SIDEBAR: Last resort JWT decode error: ' . $e->getMessage());
                                    }
                                }
                            }
                            
                            // Final check: If still null, log critical error
                            if (!$currentUserRole) {
                                error_log('RBAC SIDEBAR CRITICAL: getCurrentUserRole() returned NULL after all fallbacks. ' .
                                          'Cookies available: ' . json_encode(array_keys($_COOKIE)) . 
                                          ', Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
                                          ', Page: ' . basename($_SERVER['PHP_SELF'] ?? 'unknown'));
                            }
                        }
                        
                        // DEBUG: Log role detection and available cookies
                        $availableCookies = array_keys($_COOKIE);
                        $hasRoleCookie = isset($_COOKIE['user_role_id']);
                        $hasJwtCookie = isset($_COOKIE['jwt_token']);
                        error_log('RBAC SIDEBAR DEBUG: Detected role = ' . ($currentUserRole ?? 'NULL') . 
                                  ', user_role_id cookie = ' . ($_COOKIE['user_role_id'] ?? 'NOT SET') .
                                  ', jwt_token cookie = ' . ($hasJwtCookie ? 'SET' : 'NOT SET') .
                                  ', All cookies: ' . implode(', ', $availableCookies));
                        
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
                        
                        // RBAC: Filter modules based on user role
                        // SINGLE SOURCE OF TRUTH: This is the ONLY place where module visibility is determined
                        // Module visibility is based ONLY on user role from session/cookie - NOT page-specific
                        // OFFICIAL USER ROLES (FROM RESEARCH DEFENSE)
                        // Module visibility must match role exactly - server-side enforcement
                        $roleModulePermissions = [
                            // Viewer (Partner Representative) - Limited external collaborator
                            // Can: Dashboard (read-only), Campaigns (view only), Events (view only), Surveys (respond only), Impact (view reports if shared)
                            // Cannot: Content, Segments, Partners, Planning tools, Creation/edit forms
                            'viewer' => ['dashboard.php', 'campaigns.php', 'events.php', 'surveys.php', 'impact.php'],
                            // Partner (legacy - maps to viewer)
                            'partner' => ['dashboard.php', 'campaigns.php', 'events.php', 'surveys.php', 'impact.php'],
                            
                            // Staff (BDRRMO / Admin Staff - Encoder) - Operational worker
                            // Can: Create campaign drafts, Encode events, Manage segments, Upload content drafts, View impact reports
                            'staff' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php'],
                            
                            // Secretary - Coordinator role
                            // Can: Everything Staff can + Organize scheduling drafts, Forward campaigns for approval
                            'secretary' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php'],
                            
                            // Kagawad (Public Safety) - Reviewer / oversight
                            // Can: View campaigns, Review drafts, View impact data, Provide recommendations, View content and segments
                            'kagawad' => ['dashboard.php', 'campaigns.php', 'events.php', 'impact.php', 'content.php'],
                            
                            // Captain - Final authority
                            // Can: Approve campaigns, Finalize schedules, Close campaigns, View all operational data, Partners
                            'captain' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php', 'partners.php'],
                            
                            // Admin (Technical Admin Staff) - System maintainer
                            // Can: Access everything, Manage users, Manage roles, System configuration
                            'admin' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php', 'partners.php'],
                            
                            // Legacy role support (case-insensitive normalization handled by get_user_role.php)
                            'barangay administrator' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php', 'partners.php'],
                            'barangay staff' => ['dashboard.php', 'campaigns.php', 'content.php', 'segments.php', 'events.php', 'surveys.php', 'impact.php'],
                        ];
                        
                        // Filter modules based on role (STRICT RBAC)
                        // SINGLE SOURCE OF TRUTH: Module visibility is determined ONLY here, based on user role
                        // This ensures consistent sidebar visibility across ALL pages for the same role
                        // If role is null/not detected, try to get from JWT cookie as fallback
                        if (!$currentUserRole) {
                            // Fallback: Try to get role from JWT cookie if role cookie is missing
                            $jwtToken = $_COOKIE['jwt_token'] ?? null;
                            if ($jwtToken) {
                                try {
                                    require_once __DIR__ . '/../../vendor/autoload.php';
                                    $envPath = __DIR__ . '/../../.env';
                                    $jwtSecret = 'your-secret-key-change-in-production';
                                    $jwtIssuer = 'public-safety-campaign-system';
                                    $jwtAudience = 'public-safety-campaign-system';
                                    
                                    if (file_exists($envPath)) {
                                        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                        foreach ($lines as $line) {
                                            $line = trim($line);
                                            if ($line === '' || strpos($line, '#') === 0) continue;
                                            if (strpos($line, '=') === false) continue;
                                            list($name, $value) = explode('=', $line, 2);
                                            $name = trim($name);
                                            $value = trim($value);
                                            if ($name === 'JWT_SECRET') $jwtSecret = $value;
                                            if ($name === 'JWT_ISSUER') $jwtIssuer = $value;
                                            if ($name === 'JWT_AUDIENCE') $jwtAudience = $value;
                                        }
                                    }
                                    
                                    $decoded = Firebase\JWT\JWT::decode($jwtToken, new Firebase\JWT\Key($jwtSecret, 'HS256'));
                                    if (($decoded->aud ?? null) === $jwtAudience && ($decoded->iss ?? null) === $jwtIssuer) {
                                        $roleId = (int) ($decoded->role_id ?? 0);
                                        if ($roleId > 0) {
                                            require_once __DIR__ . '/../../src/Config/db_connect.php';
                                            if (isset($pdo) && $pdo instanceof PDO) {
                                                $stmt = $pdo->prepare('SELECT r.name FROM campaign_department_roles r WHERE r.id = :role_id LIMIT 1');
                                                $stmt->execute(['role_id' => $roleId]);
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                if ($result) {
                                                    $roleName = strtolower(trim($result['name']));
                                                    $roleMappings = [
                                                        'partner' => 'viewer',
                                                        'partner representative' => 'viewer',
                                                        'partner_representative' => 'viewer',
                                                        'viewer' => 'viewer',
                                                        'staff' => 'staff',
                                                        'secretary' => 'secretary',
                                                        'kagawad' => 'kagawad',
                                                        'captain' => 'captain',
                                                        'admin' => 'admin',
                                                        'barangay administrator' => 'admin',
                                                        'barangay admin' => 'admin',
                                                        'barangay staff' => 'staff',
                                                        'system_admin' => 'admin',
                                                        'barangay_admin' => 'admin',
                                                    ];
                                                    $currentUserRole = $roleMappings[$roleName] ?? $roleName;
                                                    if (strpos($roleName, 'partner') !== false || (strpos($roleName, 'viewer') !== false && !isset($roleMappings[$roleName]))) {
                                                        $currentUserRole = 'viewer';
                                                    }
                                                    error_log('RBAC SIDEBAR: Fallback JWT decode - role=' . $currentUserRole);
                                                }
                                            }
                                        }
                                    }
                                } catch (Exception $e) {
                                    error_log('RBAC SIDEBAR: JWT fallback error: ' . $e->getMessage());
                                }
                            }
                        }
                        
                        // If role is still null/not detected, default to Viewer permissions (most restrictive)
                        if ($currentUserRole && isset($roleModulePermissions[$currentUserRole])) {
                            $allowedModules = $roleModulePermissions[$currentUserRole];
                        } else {
                            // If role cannot be determined, default to Viewer (safest for security)
                            // This is a server-side decision - no client-side override
                            error_log('RBAC SIDEBAR WARNING: Role not detected, defaulting to viewer. Current role: ' . ($currentUserRole ?? 'NULL'));
                            $allowedModules = $roleModulePermissions['viewer'] ?? [];
                        }
                        
                        // Filter modules - only show allowed ones
                        $modules = array_filter($modules, function($key) use ($allowedModules) {
                            return in_array($key, $allowedModules, true);
                        }, ARRAY_FILTER_USE_KEY);
                        
                        foreach ($modules as $href => $module):
                            $linkUrl = $publicPath . '/' . $href;
                            $isActive = $page === $href;
                            $moduleName = str_replace('.php', '', $href);
                            $hasFeatures = isset($module['features']) && !empty($module['features']);
                            $hasActiveFeature = false;
                            
                            // RBAC: Filter features for Viewer (hide create/edit/management features)
                            if ($hasFeatures && $currentUserRole === 'viewer') {
                                // Viewer can only see read-only features
                                $allowedFeatures = [
                                    'dashboard.php' => ['kpi-overview', 'engagement-impact'], // Limited dashboard features
                                    'campaigns.php' => ['list-section'], // Only "All Campaigns", not "Plan New Campaign" or AI tools
                                    'events.php' => ['events-list'], // Only "All Events", not create
                                    'surveys.php' => ['responses'], // Surveys: Only "Submit Response" section, no create/edit/analytics
                                    'impact.php' => ['impact-dashboard', 'evaluation-reports', 'metrics-overview'], // Read-only reports
                                ];
                                
                                if (isset($allowedFeatures[$href])) {
                                    $allowedFeatureIds = $allowedFeatures[$href];
                                    $module['features'] = array_filter($module['features'], function($feature) use ($allowedFeatureIds) {
                                        $featureId = str_replace('#', '', $feature['href']);
                                        return in_array($featureId, $allowedFeatureIds, true);
                                    });
                                    $hasFeatures = !empty($module['features']);
                                } else {
                                    // No allowed features for this module - hide all features
                                    $module['features'] = [];
                                    $hasFeatures = false;
                                }
                            }
                            
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
// RBAC FIX: Set role cookie via server-side endpoint for reliable PHP access
// This ensures the cookie is set server-side and available immediately to PHP
(function() {
    try {
        const token = localStorage.getItem('jwtToken');
        if (token) {
            // Decode JWT payload to get role_id
            try {
                const parts = token.split('.');
                if (parts.length === 3) {
                    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                    const roleId = payload.role_id || payload.rid;
                    
                    if (roleId && typeof roleId === 'number') {
                        // Call server-side endpoint to set cookie reliably
                        // This ensures cookie is set before PHP tries to read it
                        const apiBase = '<?php 
                            if (!isset($basePath)) {
                                require_once __DIR__ . "/../../header/includes/path_helper.php";
                            }
                            echo $basePath ?? "/public-safety-campaign-system"; 
                        ?>';
                        fetch(apiBase + '/api/set_role_cookie.php?role_id=' + roleId, {
                            method: 'GET',
                            credentials: 'include' // Important: include cookies in request
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                console.log('RBAC: Role cookie set via server:', roleId);
                            } else {
                                console.warn('RBAC: Failed to set cookie via server:', data.error);
                                // Fallback: set cookie client-side as backup
                                const expires = new Date();
                                expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000));
                                document.cookie = 'user_role_id=' + roleId + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
                            }
                        }).catch(err => {
                            console.error('RBAC: Error calling set_role_cookie endpoint:', err);
                            // Fallback: set cookie client-side
                            const expires = new Date();
                            expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000));
                            document.cookie = 'user_role_id=' + roleId + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
                        });
                    }
                }
            } catch (e) {
                console.error('RBAC: Failed to decode JWT for role cookie:', e);
            }
        }
    } catch (e) {
        console.error('RBAC: Error setting role cookie:', e);
    }
})();

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
    
    // Auto-expand submenu if parent module is active (on page load)
    setTimeout(() => {
        const activeModuleLinks = document.querySelectorAll('.sidebar-module-toggle.active');
        activeModuleLinks.forEach(link => {
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
                    // Make element visible if it's hidden (for sections like survey-builder, survey-analytics)
                    const originalDisplay = window.getComputedStyle(targetElement).display;
                    if (originalDisplay === 'none') {
                        targetElement.style.display = 'block';
                        // Force reflow to ensure element is laid out
                        targetElement.offsetHeight;
                    }
                    
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
                } else {
                    // Element not found - log for debugging
                    console.warn('Navigation target not found:', anchorHref);
                }
            }
        });
    });
    
    // Highlight active submenu item on page load based on hash or scroll position
    function highlightActiveSubmenuItem() {
        const hash = window.location.hash;
        if (hash) {
            // Make target element visible if hidden, then scroll to it
            const targetElement = document.querySelector(hash);
            if (targetElement) {
                const originalDisplay = window.getComputedStyle(targetElement).display;
                if (originalDisplay === 'none') {
                    targetElement.style.display = 'block';
                    // Force reflow
                    targetElement.offsetHeight;
                }
                
                // Scroll to element after a brief delay to ensure it's visible
                setTimeout(() => {
                    const headerOffset = 90;
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }, 100);
            }
            
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
    
    // RBAC: Sidebar visibility is handled server-side by PHP
    // JavaScript should NOT filter the sidebar as it causes inconsistent behavior
    // PHP already correctly filters modules based on user role from cookie/session
    // This ensures consistent sidebar visibility across all pages for the same role
    
    // Note: If PHP role detection fails, the sidebar will default to Viewer permissions
    // (most restrictive) which is the safe fallback behavior
});
</script>
