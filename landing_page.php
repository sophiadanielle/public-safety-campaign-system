<?php
/**
 * Landing Page for Barangay Public Safety Campaign Management System
 */
require_once __DIR__ . '/header/includes/path_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Public Safety Campaign Management System</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #14b8a6;
            --secondary: #0f172a;
            --accent: #f59e0b;
            --background: #f8fafc;
            --surface: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            z-index: 1000;
            padding: 0 24px;
        }

        .navbar-container {
            max-width: 1280px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }

        .navbar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .navbar-logo img {
            height: 44px;
            width: auto;
        }

        .navbar-logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
        }

        .navbar-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-login {
            padding: 10px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
        }

        .btn-login-outline {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }

        .btn-login-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-login-primary {
            background: var(--primary);
            color: white;
        }

        .btn-login-primary:hover {
            background: var(--primary-dark);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 100px 24px 60px;
            background: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 50%, #ecfeff 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 150%;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .hero-container {
            max-width: 1280px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .hero-content {
            max-width: 600px;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(13, 148, 136, 0.1);
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 24px;
        }

        .hero-badge i {
            font-size: 12px;
        }

        .hero-title {
            font-size: 56px;
            font-weight: 800;
            line-height: 1.1;
            color: var(--secondary);
            margin-bottom: 24px;
        }

        .hero-title span {
            color: var(--primary);
        }

        .hero-description {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.7;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }

        .btn-hero {
            padding: 16px 32px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
        }

        .btn-hero-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
        }

        .btn-hero-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.5);
        }

        .btn-hero-secondary {
            background: white;
            color: var(--secondary);
            border: 2px solid var(--border);
        }

        .btn-hero-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .hero-visual {
            position: relative;
        }

        .hero-image-container {
            position: relative;
            padding: 20px;
        }

        .hero-card {
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            padding: 32px;
            position: relative;
        }

        .hero-card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .hero-card-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        .hero-card-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--secondary);
        }

        .hero-card-subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .hero-stat {
            text-align: center;
            padding: 16px;
            background: var(--background);
            border-radius: 12px;
        }

        .hero-stat-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--primary);
        }

        .hero-stat-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .floating-card {
            position: absolute;
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: float 3s ease-in-out infinite;
        }

        .floating-card-1 {
            top: -20px;
            right: -30px;
            animation-delay: 0s;
        }

        .floating-card-2 {
            bottom: 40px;
            left: -40px;
            animation-delay: 1.5s;
        }

        .floating-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .floating-icon.green {
            background: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .floating-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .floating-text {
            font-size: 13px;
            font-weight: 600;
            color: var(--secondary);
        }

        .floating-subtext {
            font-size: 11px;
            color: var(--text-muted);
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Features Section */
        .features {
            padding: 100px 24px;
            background: white;
        }

        .features-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            max-width: 700px;
            margin: 0 auto 60px;
        }

        .section-label {
            display: inline-block;
            padding: 6px 14px;
            background: rgba(13, 148, 136, 0.1);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }

        .section-title {
            font-size: 40px;
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 16px;
        }

        .section-description {
            font-size: 18px;
            color: var(--text-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .feature-card {
            background: var(--background);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .feature-card:hover {
            background: white;
            border-color: var(--border);
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .feature-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin-bottom: 24px;
        }

        .feature-icon.teal {
            background: rgba(13, 148, 136, 0.1);
            color: var(--primary);
        }

        .feature-icon.amber {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .feature-icon.blue {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
        }

        .feature-icon.purple {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .feature-icon.rose {
            background: rgba(244, 63, 94, 0.1);
            color: #f43f5e;
        }

        .feature-icon.emerald {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .feature-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 12px;
        }

        .feature-description {
            font-size: 15px;
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* How It Works Section */
        .how-it-works {
            padding: 100px 24px;
            background: linear-gradient(180deg, #f8fafc 0%, #f0fdfa 100%);
        }

        .how-it-works-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 32px;
            position: relative;
        }

        .steps-grid::before {
            content: '';
            position: absolute;
            top: 48px;
            left: 15%;
            right: 15%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 100%);
            z-index: 0;
        }

        .step-card {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .step-number {
            width: 96px;
            height: 96px;
            background: white;
            border: 3px solid var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 800;
            color: var(--primary);
            margin: 0 auto 24px;
            box-shadow: var(--shadow-md);
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
            margin-bottom: 8px;
        }

        .step-description {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* Testimonials Section */
        .testimonials {
            padding: 100px 24px;
            background: white;
        }

        .testimonials-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
        }

        .testimonial-card {
            background: var(--background);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            position: relative;
        }

        .testimonial-card:hover {
            background: white;
            border-color: var(--border);
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .testimonial-rating {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
        }

        .testimonial-rating i {
            color: #fbbf24;
            font-size: 16px;
        }

        .testimonial-text {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text-secondary);
            margin-bottom: 24px;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .author-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            flex-shrink: 0;
        }

        .author-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--secondary);
        }

        .author-role {
            font-size: 13px;
            color: var(--text-muted);
        }

        /* CTA Section */
        .cta {
            padding: 100px 24px;
            background: linear-gradient(135deg, var(--secondary) 0%, #1e293b 100%);
            position: relative;
            overflow: hidden;
        }

        .cta::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.5;
        }

        .cta-container {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .cta-title {
            font-size: 44px;
            font-weight: 800;
            color: white;
            margin-bottom: 20px;
        }

        .cta-description {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
        }

        .cta-button {
            padding: 18px 40px;
            font-size: 18px;
            font-weight: 600;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.4);
        }

        .cta-button:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.5);
        }

        /* Footer */
        .footer {
            padding: 80px 24px 40px;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            overflow: hidden;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%2314b8a6' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.4;
        }

        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-logo img {
            height: 48px;
            width: auto;
            filter: brightness(0) invert(1) drop-shadow(0 2px 8px rgba(20, 184, 166, 0.3));
        }

        .footer-logo-text {
            font-size: 18px;
            font-weight: 700;
            color: white;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .footer-links {
            display: flex;
            gap: 32px;
        }

        .footer-link {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .footer-link:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            font-size: 14px;
            position: relative;
            z-index: 1;
            padding-top: 8px;
        }

        .footer-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 48px;
            margin-bottom: 48px;
            position: relative;
            z-index: 1;
        }

        .footer-section h3 {
            font-size: 16px;
            font-weight: 700;
            color: white;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-section h3 i {
            color: var(--primary-light);
            font-size: 18px;
        }

        .footer-section p,
        .footer-section ul {
            font-size: 14px;
            line-height: 1.8;
            color: rgba(255, 255, 255, 0.7);
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
            padding-left: 20px;
            position: relative;
        }

        .footer-section ul li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary-light);
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .footer-section a:hover {
            color: var(--primary-light);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 60px;
            }

            .hero-content {
                text-align: center;
                max-width: 100%;
            }

            .hero-buttons {
                justify-content: center;
            }

            .hero-visual {
                max-width: 500px;
                margin: 0 auto;
            }

            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .testimonials-grid {
                grid-template-columns: 1fr;
            }

            .steps-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 48px;
            }

            .steps-grid::before {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .navbar-logo-text {
                display: none;
            }

            .hero-title {
                font-size: 36px;
            }

            .hero-description {
                font-size: 16px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 32px;
            }

            .cta-title {
                font-size: 32px;
            }

            .floating-card {
                display: none;
            }

            .footer-info {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .footer-content {
                flex-direction: column;
                gap: 24px;
            }

            .footer-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="<?php echo htmlspecialchars($basePath); ?>/" class="navbar-logo">
                <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Alertara Logo">
                <span class="navbar-logo-text">Alertara QC</span>
            </a>
            <div class="navbar-actions">
                <a href="<?php echo htmlspecialchars($basePath); ?>/login.php" class="btn-login btn-login-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-shield-alt"></i>
                    Barangay Public Safety
                </div>
                <h1 class="hero-title">
                    Empowering Communities Through <span>Safety Campaigns</span>
                </h1>
                <p class="hero-description">
                    A comprehensive management system designed to help barangays plan, execute, and monitor public safety campaigns effectively. Streamline your community outreach and make a real impact.
                </p>
                <div class="hero-buttons">
                    <a href="<?php echo htmlspecialchars($basePath); ?>/login.php" class="btn-hero btn-hero-primary">
                        Get Started <i class="fas fa-arrow-right"></i>
                    </a>
                    <a href="#features" class="btn-hero btn-hero-secondary">
                        <i class="fas fa-play-circle"></i> Learn More
                    </a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="hero-image-container">
                    <div class="hero-card">
                        <div class="hero-card-header">
                            <div class="hero-card-icon">
                                <i class="fas fa-bullhorn"></i>
                            </div>
                            <div>
                                <div class="hero-card-title">Campaign Dashboard</div>
                                <div class="hero-card-subtitle">Real-time monitoring & analytics</div>
                            </div>
                        </div>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <div class="hero-stat-value">24</div>
                                <div class="hero-stat-label">Active</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-value">1.2K</div>
                                <div class="hero-stat-label">Reached</div>
                            </div>
                            <div class="hero-stat">
                                <div class="hero-stat-value">89%</div>
                                <div class="hero-stat-label">Success</div>
                            </div>
                        </div>
                    </div>
                    <div class="floating-card floating-card-1">
                        <div class="floating-icon green">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <div class="floating-text">Campaign Approved</div>
                            <div class="floating-subtext">Fire Safety Week</div>
                        </div>
                    </div>
                    <div class="floating-card floating-card-2">
                        <div class="floating-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <div class="floating-text">+156 Participants</div>
                            <div class="floating-subtext">This month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="features-container">
            <div class="section-header">
                <span class="section-label">Features</span>
                <h2 class="section-title">Everything You Need to Run Effective Campaigns</h2>
                <p class="section-description">
                    Our platform provides all the tools necessary to plan, execute, and measure the impact of your public safety initiatives.
                </p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon teal">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3 class="feature-title">Campaign Planning</h3>
                    <p class="feature-description">
                        Create and manage comprehensive safety campaigns with detailed planning tools, timelines, and resource allocation.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon amber">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Audience Segmentation</h3>
                    <p class="feature-description">
                        Target specific community groups with tailored messaging to maximize engagement and campaign effectiveness.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon blue">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="feature-title">Event Management</h3>
                    <p class="feature-description">
                        Schedule and coordinate safety events, workshops, and community gatherings with ease.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon purple">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Impact Analytics</h3>
                    <p class="feature-description">
                        Track campaign performance with detailed analytics and measure real community impact.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon rose">
                        <i class="fas fa-poll"></i>
                    </div>
                    <h3 class="feature-title">Community Surveys</h3>
                    <p class="feature-description">
                        Gather feedback and insights from residents to improve future safety initiatives.
                    </p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon emerald">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3 class="feature-title">Partner Collaboration</h3>
                    <p class="feature-description">
                        Coordinate with external partners, agencies, and stakeholders for unified safety efforts.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="how-it-works-container">
            <div class="section-header">
                <span class="section-label">Process</span>
                <h2 class="section-title">How It Works</h2>
                <p class="section-description">
                    Get started with your public safety campaigns in four simple steps.
                </p>
            </div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Plan Campaign</h3>
                    <p class="step-description">Define objectives, target audience, and resources needed.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Get Approval</h3>
                    <p class="step-description">Route through proper channels for review and authorization.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Execute</h3>
                    <p class="step-description">Launch your campaign and engage with the community.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Measure Impact</h3>
                    <p class="step-description">Analyze results and gather insights for improvement.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="testimonials-container">
            <div class="section-header">
                <span class="section-label">Success Stories</span>
                <h2 class="section-title">Trusted by Barangays Across Quezon City</h2>
                <p class="section-description">
                    See how communities are making a difference with our platform.
                </p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "This platform has transformed how we manage our safety campaigns. The analytics help us understand what works and what doesn't."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <div class="author-name">Maria Santos</div>
                            <div class="author-role">Barangay Captain, Brgy. Commonwealth</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "The event management features are incredible. We've organized more safety workshops this year than ever before."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <div class="author-name">Juan Dela Cruz</div>
                            <div class="author-role">Safety Officer, Brgy. Batasan Hills</div>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">
                        "Easy to use and very effective. Our community engagement has increased significantly since we started using Alertara QC."
                    </p>
                    <div class="testimonial-author">
                        <div class="author-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="author-info">
                            <div class="author-name">Rosa Garcia</div>
                            <div class="author-role">Campaign Coordinator, Brgy. Payatas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
        <div class="cta-container">
            <h2 class="cta-title">Ready to Make Your Community Safer?</h2>
            <p class="cta-description">
                Join barangays across Quezon City in creating impactful public safety campaigns that protect and empower communities.
            </p>
            <a href="<?php echo htmlspecialchars($basePath); ?>/login.php" class="cta-button">
                Start Now <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-info">
                <div class="footer-section">
                    <h3><i class="fas fa-shield-alt"></i> About Alertara QC</h3>
                    <p>
                        A comprehensive platform designed to empower barangays across Quezon City in planning, executing, and monitoring public safety campaigns that protect and strengthen communities.
                    </p>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-link"></i> Quick Links</h3>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="<?php echo htmlspecialchars($basePath); ?>/login.php">Login</a></li>
                        <li><a href="<?php echo htmlspecialchars($basePath); ?>/public/dashboard.php">Dashboard</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3><i class="fas fa-info-circle"></i> Resources</h3>
                    <ul>
                        <li><a href="#features">Campaign Planning</a></li>
                        <li><a href="#features">Event Management</a></li>
                        <li><a href="#features">Impact Analytics</a></li>
                        <li><a href="#features">Community Surveys</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-content">
                <div class="footer-logo">
                    <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Alertara Logo">
                    <span class="footer-logo-text">Alertara QC</span>
                </div>
                <div class="footer-links">
                    <a href="#features" class="footer-link">Features</a>
                    <a href="<?php echo htmlspecialchars($basePath); ?>/login.php" class="footer-link">Login</a>
                    <a href="#" class="footer-link">Contact</a>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?php echo date('Y'); ?> Barangay Public Safety Campaign Management System. All rights reserved.
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
            } else {
                navbar.style.boxShadow = 'none';
            }
        });
    </script>
</body>
</html>
