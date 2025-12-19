<?php
/**
 * Reusable Header Template
 * Include this file at the top of your pages: <?php include 'includes/header.php'; ?>
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Public Safety Campaign'; ?></title>
    <?php
    // Include path helper
    require_once __DIR__ . '/path_helper.php';
    ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/forms.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/cards.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/content.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script>
        // Force light theme to avoid unintended dark backgrounds
        document.documentElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    </script>
    <?php if (isset($extraCSS)): ?>
        <?php foreach ($extraCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <header class="header">
        <div class="header-container">
            <a href="<?php echo htmlspecialchars($publicPath . '/index.php'); ?>" class="logo">
                <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Logo" class="logo-img">
            </a>
            
            <?php if (empty($hideNav)): ?>
                <nav class="nav-center">
                    <ul class="nav-menu">
                        <?php
                            $page = basename($_SERVER['PHP_SELF']);
                            $links = [
                                'campaigns.php' => 'Campaigns',
                                'content.php' => 'Content',
                                'segments.php' => 'Segments',
                                'events.php' => 'Events',
                                'surveys.php' => 'Surveys',
                                'impact.php' => 'Impact',
                                'partners.php' => 'Partners',
                            ];
                            foreach ($links as $href => $label):
                                $active = $page === $href ? 'active' : '';
                                $linkUrl = $publicPath . '/' . $href;
                        ?>
                            <li><a href="<?php echo htmlspecialchars($linkUrl); ?>" class="nav-link <?php echo $active; ?>"><?php echo $label; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
                
                <div class="nav-actions">
                    <a href="<?php echo htmlspecialchars($publicPath . '/campaigns.php'); ?>" class="btn btn-primary">Dashboard</a>
                    <button type="button" class="btn btn-secondary" onclick="logout()">Logout</button>
                    <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($hideNav)): ?>
            <!-- Mobile Navigation -->
            <div class="mobile-nav">
                <div class="mobile-nav-header">
                    <a href="<?php echo htmlspecialchars($publicPath . '/index.php'); ?>" class="logo">
                        <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="" class="logo-img">
                    </a>
                    <button class="mobile-nav-close" aria-label="Close mobile menu">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <ul class="mobile-nav-menu">
                    <?php foreach ($links as $href => $label): 
                        $linkUrl = $publicPath . '/' . $href;
                    ?>
                        <li><a href="<?php echo htmlspecialchars($linkUrl); ?>" class="mobile-nav-link <?php echo $page === $href ? 'active' : ''; ?>"><?php echo $label; ?></a></li>
                    <?php endforeach; ?>
                    <li class="mobile-nav-divider"></li>
                    <li><a href="<?php echo htmlspecialchars($publicPath . '/campaigns.php'); ?>" class="mobile-nav-link">Dashboard</a></li>
                    <li><a href="#" class="mobile-nav-link" onclick="logout(); return false;">Logout</a></li>
                </ul>
            </div>
            
            <!-- Mobile Navigation Overlay -->
            <div class="mobile-nav-overlay"></div>
        <?php endif; ?>
    </header>
