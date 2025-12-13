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
    <title>Public Safety Campaign</title>
    <link rel="icon" type="image/x-icon" href="/assets/header/images/favicon.ico">
    <link rel="stylesheet" href="/assets/header/css/global.css">
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
            <a href="/login.php" class="logo">
                <img src="/assets/header/images/logo.svg" alt="Logo" class="logo-img">
            </a>
            
            <nav class="nav-center">
                <ul class="nav-menu">
                    <?php
                        $page = basename($_SERVER['PHP_SELF']);
                        $links = [
                            'login.php' => 'Login',
                            'signup.php' => 'Sign Up',
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
                    ?>
                        <li><a href="/<?php echo $href; ?>" class="nav-link <?php echo $active; ?>"><?php echo $label; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
            
            <div class="nav-actions">
                <a href="/login.php" class="btn btn-secondary">Login</a>
                <a href="/campaigns.php" class="btn btn-primary">Dashboard</a>
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
                </div>
        
        <!-- Mobile Navigation -->
        <div class="mobile-nav">
            <div class="mobile-nav-header">
                <a href="/login.php" class="logo">
                    <img src="/assets/header/images/logo.svg" alt="" class="logo-img">
                </a>
                <button class="mobile-nav-close" aria-label="Close mobile menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-menu">
                <?php foreach ($links as $href => $label): ?>
                    <li><a href="/<?php echo $href; ?>" class="mobile-nav-link <?php echo $page === $href ? 'active' : ''; ?>"><?php echo $label; ?></a></li>
                <?php endforeach; ?>
                <li class="mobile-nav-divider"></li>
                <li><a href="/login.php" class="mobile-nav-link">Login</a></li>
                <li><a href="/campaigns.php" class="mobile-nav-link">Dashboard</a></li>
            </ul>
        </div>
        
        <!-- Mobile Navigation Overlay -->
        <div class="mobile-nav-overlay"></div>
    </header>
