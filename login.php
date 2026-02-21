<?php
/**
 * Login Page for Barangay Public Safety Campaign Management System
 */

// Check for error from OAuth or other sources
$error = isset($_GET['error']) ? $_GET['error'] : '';

require_once __DIR__ . '/header/includes/path_helper.php';

// Production detection and path override
if (isset($_SERVER['HTTP_HOST'])) {
    $host = strtolower($_SERVER['HTTP_HOST']);
    $serverName = strtolower($_SERVER['SERVER_NAME'] ?? '');
    
    $isProduction = (
        strpos($host, 'alertaraqc.com') !== false ||
        strpos($serverName, 'alertaraqc.com') !== false ||
        strpos($host, 'campaign.') !== false ||
        (strpos($host, 'localhost') === false && 
         $host !== '127.0.0.1' && 
         $host !== '' &&
         strpos($host, '.local') === false)
    );
    
    if ($isProduction) {
        $basePath = '';
        $apiPath = '/index.php';
        $cssPath = '/header/css';
        $imgPath = '/header/images';
        $publicPath = '/public';
    }
}

// Determine localhost for JS
$finalHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
$isDefinitelyLocalhost = (
    strpos($finalHost, 'localhost') !== false ||
    $finalHost === '127.0.0.1' ||
    strpos($finalHost, '.local') !== false ||
    strpos($finalHost, 'xampp') !== false ||
    strpos($finalHost, 'wamp') !== false
);

if (!$isDefinitelyLocalhost && $finalHost !== '') {
    $basePath = '';
    $apiPath = '/index.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Public Safety Campaign</title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($imgPath . '/favicon.ico'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/global.css'); ?>">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath . '/buttons.css'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #0d9488;
            --primary-dark: #0f766e;
            --primary-light: #14b8a6;
            --secondary: #0f172a;
            --background: #f0fdfa;
            --surface: #ffffff;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --error: #dc2626;
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

        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #ecfeff 50%, #f8fafc 100%);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Header */
        .login-header {
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(13, 148, 136, 0.1);
        }

        .login-header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .login-header-logo img {
            height: 40px;
            width: auto;
        }

        .login-header-logo span {
            font-size: 18px;
            font-weight: 700;
            color: var(--secondary);
        }

        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-link:hover {
            color: var(--primary);
        }

        /* Main Content */
        .login-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 24px;
            position: relative;
            overflow: hidden;
        }

        .login-main::before {
            content: '';
            position: absolute;
            top: -10%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(13, 148, 136, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-main::after {
            content: '';
            position: absolute;
            bottom: -10%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(20, 184, 166, 0.08) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-container {
            width: 100%;
            max-width: 1200px;
            min-height: 600px;
            max-height: calc(100vh - 80px);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            background: var(--surface);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.12), 0 0 0 1px rgba(13, 148, 136, 0.1);
            z-index: 1;
        }

        .login-card {
            background: var(--surface);
            padding: 60px 56px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-branding {
            background: url('<?php echo htmlspecialchars($basePath); ?>/sidebar/images/shrine.jpg');
            background-size: cover;
            background-position: center;
            padding: 60px 56px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-branding::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(13, 148, 136, 0.85) 0%, rgba(15, 118, 110, 0.82) 100%);
        }

        .branding-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .branding-logo {
            display: none;
        }

        .branding-title {
            font-size: 32px;
            font-weight: 800;
            color: white;
            margin-bottom: 16px;
            text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
            letter-spacing: -0.5px;
        }

        .branding-subtitle {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.95);
            line-height: 1.7;
            max-width: 340px;
            margin: 0 auto;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .branding-features {
            margin-top: 36px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .branding-feature {
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255, 255, 255, 0.98);
            font-size: 14px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .branding-feature i {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .login-icon {
            display: none;
        }

        .login-title {
            font-size: 28px;
            font-weight: 800;
            color: var(--secondary);
            text-align: center;
            margin-bottom: 8px;
            margin-top: 0;
        }

        .login-subtitle {
            font-size: 15px;
            color: var(--text-secondary);
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
            color: var(--text-primary);
            transition: all 0.2s ease;
            outline: none;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.1);
        }

        .input-wrapper input::placeholder {
            color: var(--text-muted);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 18px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-wrapper input:focus + .input-icon,
        .input-wrapper input:focus ~ .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--text-secondary);
        }

        .input-wrapper:has(.password-toggle) input {
            padding-right: 48px;
        }

        .btn-login {
            width: 100%;
            padding: 16px 24px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .status-message {
            margin-top: 16px;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            text-align: center;
            display: none;
        }

        .status-message.error {
            display: block;
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .status-message.success {
            display: block;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .status-message.loading {
            display: block;
            background: #f0fdfa;
            color: var(--primary);
            border: 1px solid #99f6e4;
        }

        /* Footer */
        .login-footer {
            padding: 16px 24px;
            text-align: center;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(13, 148, 136, 0.1);
        }

        .login-footer p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 480px;
            }

            .login-branding {
                display: none;
            }

            .login-card {
                padding: 40px 32px;
            }
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 32px 24px;
            }

            .login-header-logo span {
                display: none;
            }

            .login-title {
                font-size: 24px;
            }
        }

        /* Animation */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.3s ease-in-out;
        }

        /* OTP Modal Styles */
        .otp-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            backdrop-filter: blur(8px);
        }

        .otp-modal-overlay.show {
            display: flex;
        }

        .otp-modal {
            background: var(--surface);
            border-radius: 24px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.25);
            animation: otpModalSlideIn 0.4s ease;
            overflow: hidden;
        }

        @keyframes otpModalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .otp-modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 32px;
            text-align: center;
            position: relative;
        }

        .otp-modal-header img {
            height: 48px;
            margin-bottom: 16px;
        }

        .otp-modal-header .otp-icon {
            width: 64px;
            height: 64px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .otp-modal-header .otp-icon i {
            font-size: 32px;
            color: white;
        }

        .otp-modal-header h2 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 8px;
        }

        .otp-modal-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin: 0;
        }

        .otp-modal-body {
            padding: 32px;
        }

        .otp-email-display {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 24px;
        }

        .otp-email-display i {
            color: var(--primary);
            font-size: 20px;
            margin-bottom: 8px;
        }

        .otp-email-display p {
            margin: 0;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .otp-email-display strong {
            color: var(--primary-dark);
        }

        .otp-input-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 24px;
        }

        .otp-input {
            width: 52px;
            height: 60px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid var(--border);
            border-radius: 12px;
            outline: none;
            transition: all 0.2s ease;
            color: var(--primary-dark);
        }

        .otp-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.15);
        }

        .otp-input.filled {
            border-color: var(--primary);
            background: #f0fdfa;
        }

        .otp-timer {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }

        .otp-timer span {
            color: var(--primary);
            font-weight: 600;
        }

        .otp-resend {
            text-align: center;
            margin-bottom: 24px;
        }

        .otp-resend button {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .otp-resend button:hover:not(:disabled) {
            background: #f0fdfa;
        }

        .otp-resend button:disabled {
            color: var(--text-muted);
            cursor: not-allowed;
        }

        .otp-verify-btn {
            width: 100%;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3);
        }

        .otp-verify-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.4);
        }

        .otp-verify-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .otp-modal-footer {
            padding: 20px 32px;
            background: #f8fafc;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .otp-back-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 14px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .otp-back-btn:hover {
            background: white;
            color: var(--text-primary);
        }

        .otp-status {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: none;
        }

        .otp-status.error {
            display: block;
            background: #fef2f2;
            color: var(--error);
            border: 1px solid #fecaca;
        }

        .otp-status.success {
            display: block;
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="login-header">
        <a href="<?php echo htmlspecialchars($basePath); ?>/" class="login-header-logo">
            <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Alertara Logo">
        </a>
        <a href="<?php echo htmlspecialchars($basePath); ?>/" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
    </header>

    <!-- Main Content -->
    <main class="login-main">
        <div class="login-container">
            <!-- Login Form Card -->
            <div class="login-card">
                <div class="login-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access the campaign management dashboard</p>

                <form id="loginForm" onsubmit="return handleLogin(event)">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="Enter your email"
                                autocomplete="email"
                                required
                            >
                            <i class="fas fa-envelope input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <i class="fas fa-lock input-icon"></i>
                            <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>

                <div id="statusMessage" class="status-message <?php echo $error ? 'error' : ''; ?>">
                    <?php if ($error): ?>
                        <?php echo htmlspecialchars($error); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Branding Card -->
            <div class="login-branding">
                <div class="branding-content">
                    <div class="branding-logo">
                        <img src="<?php echo htmlspecialchars($imgPath . '/logo.svg'); ?>" alt="Alertara QC Logo">
                    </div>
                    <h2 class="branding-title">Alertara QC</h2>
                    <p class="branding-subtitle">
                        Empowering barangays across Quezon City with comprehensive public safety campaign management.
                    </p>
                    <div class="branding-features">
                        <div class="branding-feature">
                            <i class="fas fa-bullhorn"></i>
                            <span>Plan & Execute Safety Campaigns</span>
                        </div>
                        <div class="branding-feature">
                            <i class="fas fa-chart-line"></i>
                            <span>Track Real-time Analytics</span>
                        </div>
                        <div class="branding-feature">
                            <i class="fas fa-users"></i>
                            <span>Engage Your Community</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="login-footer">
        <p>&copy; <?php echo date('Y'); ?> Barangay Public Safety Campaign Management System</p>
    </footer>

    <!-- OTP Verification Modal -->
    <div class="otp-modal-overlay" id="otpModal">
        <div class="otp-modal">
            <div class="otp-modal-header">
                <div class="otp-icon">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Verify Your Identity</h2>
                <p>Enter the 6-digit code sent to your email</p>
            </div>
            <div class="otp-modal-body">
                <div class="otp-email-display">
                    <i class="fas fa-envelope"></i>
                    <p>Code sent to <strong id="otpEmailDisplay">your email</strong></p>
                </div>

                <div id="otpStatus" class="otp-status"></div>

                <div class="otp-input-container">
                    <input type="text" class="otp-input" maxlength="1" data-index="0" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="1" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="2" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="3" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="4" inputmode="numeric" pattern="[0-9]">
                    <input type="text" class="otp-input" maxlength="1" data-index="5" inputmode="numeric" pattern="[0-9]">
                </div>

                <div class="otp-timer">
                    Code expires in <span id="otpTimer">5:00</span>
                </div>

                <div class="otp-resend">
                    <button type="button" id="resendOtpBtn" onclick="resendOTP()" disabled>
                        <i class="fas fa-redo"></i> Resend Code
                    </button>
                </div>

                <button type="button" class="otp-verify-btn" id="verifyOtpBtn" onclick="verifyOTP()">
                    <i class="fas fa-shield-alt"></i>
                    Verify & Sign In
                </button>
            </div>
            <div class="otp-modal-footer">
                <button type="button" class="otp-back-btn" onclick="closeOtpModal()">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </button>
            </div>
        </div>
    </div>

    <script>
        const basePath = '<?php echo isset($basePath) ? $basePath : ''; ?>';
        let otpEmail = '';
        let otpTimerInterval = null;
        let otpExpiresAt = null;
        
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function showStatus(message, type) {
            const statusEl = document.getElementById('statusMessage');
            statusEl.textContent = message;
            statusEl.className = 'status-message ' + type;
        }

        function showOtpStatus(message, type) {
            const statusEl = document.getElementById('otpStatus');
            statusEl.textContent = message;
            statusEl.className = 'otp-status ' + type;
        }

        function setRoleCookieFromJWT(token) {
            try {
                if (!token) return;
                const parts = token.split('.');
                if (parts.length === 3) {
                    const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
                    const roleId = payload.role_id || payload.rid;
                    if (roleId && typeof roleId === 'number') {
                        const expires = new Date();
                        expires.setTime(expires.getTime() + (24 * 60 * 60 * 1000));
                        document.cookie = 'user_role_id=' + roleId + ';path=/;expires=' + expires.toUTCString() + ';SameSite=Lax';
                    }
                }
            } catch (e) {
                console.error('Failed to set role cookie:', e);
            }
        }

        async function handleLogin(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loginCard = document.querySelector('.login-card');

            if (!email || !password) {
                showStatus('Please enter both email and password.', 'error');
                loginCard.classList.add('shake');
                setTimeout(() => loginCard.classList.remove('shake'), 300);
                return false;
            }

            // Disable button and show loading
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
            showStatus('Verifying credentials...', 'loading');

            try {
                // Send OTP request
                const response = await fetch('/index.php/api/v1/auth/otp/send', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Invalid email or password');
                }

                // Store email for OTP verification
                otpEmail = email;
                
                // Show OTP modal
                document.getElementById('otpEmailDisplay').textContent = data.email || email;
                openOtpModal();
                
                showStatus('', '');
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';

            } catch (error) {
                showStatus(error.message, 'error');
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
                loginCard.classList.add('shake');
                setTimeout(() => loginCard.classList.remove('shake'), 300);
            }

            return false;
        }

        function openOtpModal() {
            document.getElementById('otpModal').classList.add('show');
            clearOtpInputs();
            startOtpTimer(300); // 5 minutes
            document.querySelector('.otp-input[data-index="0"]').focus();
        }

        function closeOtpModal() {
            document.getElementById('otpModal').classList.remove('show');
            clearOtpTimer();
            clearOtpInputs();
            document.getElementById('otpStatus').className = 'otp-status';
        }

        function clearOtpInputs() {
            document.querySelectorAll('.otp-input').forEach(input => {
                input.value = '';
                input.classList.remove('filled');
            });
        }

        function startOtpTimer(seconds) {
            clearOtpTimer();
            otpExpiresAt = Date.now() + (seconds * 1000);
            document.getElementById('resendOtpBtn').disabled = true;
            
            updateTimerDisplay();
            otpTimerInterval = setInterval(updateTimerDisplay, 1000);
        }

        function updateTimerDisplay() {
            const remaining = Math.max(0, Math.floor((otpExpiresAt - Date.now()) / 1000));
            const minutes = Math.floor(remaining / 60);
            const secs = remaining % 60;
            document.getElementById('otpTimer').textContent = `${minutes}:${secs.toString().padStart(2, '0')}`;
            
            if (remaining <= 0) {
                clearOtpTimer();
                document.getElementById('resendOtpBtn').disabled = false;
                document.getElementById('otpTimer').textContent = 'Expired';
            }
        }

        function clearOtpTimer() {
            if (otpTimerInterval) {
                clearInterval(otpTimerInterval);
                otpTimerInterval = null;
            }
        }

        function getOtpValue() {
            let otp = '';
            document.querySelectorAll('.otp-input').forEach(input => {
                otp += input.value;
            });
            return otp;
        }

        async function verifyOTP() {
            const otp = getOtpValue();
            
            if (otp.length !== 6) {
                showOtpStatus('Please enter all 6 digits', 'error');
                return;
            }

            const verifyBtn = document.getElementById('verifyOtpBtn');
            verifyBtn.disabled = true;
            verifyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';

            try {
                const response = await fetch('/index.php/api/v1/auth/otp/verify', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: otpEmail, otp })
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Invalid OTP');
                }

                if (!data.token) {
                    throw new Error('Verification failed. Please try again.');
                }

                // Store token
                localStorage.setItem('jwtToken', data.token);
                if (data.user) {
                    localStorage.setItem('currentUser', JSON.stringify(data.user));
                }
                
                // Set role cookie
                setRoleCookieFromJWT(data.token);
                
                // Set session flags
                sessionStorage.setItem('justLoggedIn', 'true');
                sessionStorage.setItem('loginTimestamp', Date.now().toString());

                showOtpStatus('Verification successful! Redirecting...', 'success');
                verifyBtn.innerHTML = '<i class="fas fa-check"></i> Success!';

                // Redirect to dashboard
                setTimeout(() => {
                    window.location.href = basePath + '/public/dashboard.php?logged_in=1&t=' + Date.now();
                }, 800);

            } catch (error) {
                showOtpStatus(error.message, 'error');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="fas fa-shield-alt"></i> Verify & Sign In';
            }
        }

        async function resendOTP() {
            const resendBtn = document.getElementById('resendOtpBtn');
            resendBtn.disabled = true;
            resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            try {
                const response = await fetch('/index.php/api/v1/auth/otp/resend', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: otpEmail })
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    throw new Error(data.error || 'Failed to resend OTP');
                }

                clearOtpInputs();
                startOtpTimer(300);
                showOtpStatus('New code sent to your email', 'success');
                setTimeout(() => {
                    document.getElementById('otpStatus').className = 'otp-status';
                }, 3000);

            } catch (error) {
                showOtpStatus(error.message, 'error');
                resendBtn.disabled = false;
            }

            resendBtn.innerHTML = '<i class="fas fa-redo"></i> Resend Code';
        }

        // OTP input handling
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();

            const otpInputs = document.querySelectorAll('.otp-input');
            
            otpInputs.forEach((input, index) => {
                input.addEventListener('input', function(e) {
                    const value = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = value;
                    
                    if (value) {
                        e.target.classList.add('filled');
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        }
                    } else {
                        e.target.classList.remove('filled');
                    }

                    // Auto-verify when all digits entered
                    if (getOtpValue().length === 6) {
                        verifyOTP();
                    }
                });

                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && !e.target.value && index > 0) {
                        otpInputs[index - 1].focus();
                    }
                });

                input.addEventListener('paste', function(e) {
                    e.preventDefault();
                    const pastedData = (e.clipboardData || window.clipboardData).getData('text');
                    const digits = pastedData.replace(/[^0-9]/g, '').slice(0, 6);
                    
                    digits.split('').forEach((digit, i) => {
                        if (otpInputs[i]) {
                            otpInputs[i].value = digit;
                            otpInputs[i].classList.add('filled');
                        }
                    });

                    if (digits.length === 6) {
                        verifyOTP();
                    }
                });
            });
        });

        // Handle Enter key
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });

        // Close modal on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeOtpModal();
            }
        });
    </script>
</body>
</html>
