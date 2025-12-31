<?php
$pageTitle = 'Sign Up';
$hideNav   = true;
include __DIR__ . '/../header/includes/header.php';
?>
<!-- Material Icons Outlined -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<style>
    .material-symbols-outlined {
        font-family: 'Material Symbols Outlined';
        font-weight: normal;
        font-style: normal;
        font-size: 20px;
        line-height: 1;
        letter-spacing: normal;
        text-transform: none;
        display: inline-block;
        white-space: nowrap;
        word-wrap: normal;
        direction: ltr;
        -webkit-font-feature-settings: 'liga';
        -webkit-font-smoothing: antialiased;
    }
</style>

<style>
    .auth-wrapper {
        min-height: calc(100vh - 140px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .auth-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        padding: 28px;
        width: 100%;
        max-width: 440px;
    }
    .auth-heading {
        margin: 0 0 8px 0;
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
    }
    .auth-subtitle {
        margin: 0 0 20px;
        color: #475569;
    }
    .auth-card label {
        display: block;
        font-weight: 600;
        color: #0f172a;
        margin-top: 12px;
    }
    /* Plain input fields (without wrapper) - very specific selector */
    .auth-card #name,
    .auth-card > input[type="text"],
    .auth-card label + input[type="text"] {
        width: 100% !important;
        padding: 10px 12px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px !important;
        background: #fff !important;
        margin-top: 6px !important;
        font-size: 14px !important;
        outline: none !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
        box-sizing: border-box !important;
        display: block !important;
    }
    .auth-card #name:focus,
    .auth-card > input[type="text"]:focus,
    .auth-card label + input[type="text"]:focus {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
        outline: none !important;
    }
    /* Override for inputs inside wrapper - they use different padding */
    .input-wrapper input {
        margin-top: 0 !important;
    }
    /* Input wrapper with absolute positioned icons */
    .input-wrapper {
        position: relative;
        width: 100%;
        margin-top: 6px;
    }
    .input-wrapper input {
        width: 100%;
        padding-left: 44px;
        padding-right: 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        padding-top: 10px;
        padding-bottom: 10px;
        font-size: 14px;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .input-wrapper input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    /* Password input - extra right padding for toggle icon */
    .input-wrapper:has(.input-icon-right) input {
        padding-right: 44px;
    }
    /* Left icon - absolutely positioned */
    .input-icon-left {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #64748b;
    }
    .input-icon-left .material-symbols-outlined {
        font-size: 20px;
    }
    /* Right icon - absolutely positioned (password toggle only) */
    .input-icon-right {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #64748b;
        background: transparent;
        border: none;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        user-select: none;
        transition: color 0.2s ease;
    }
    .input-icon-right:hover {
        color: #475569;
    }
    .input-icon-right .material-symbols-outlined {
        font-size: 20px;
    }
    /* Remove browser-native password UI elements */
    input[type="password"]::-ms-reveal,
    input[type="password"]::-ms-clear {
        display: none;
    }
    input[type="password"]::-webkit-credentials-auto-fill-button {
        visibility: hidden;
        position: absolute;
        right: 0;
    }
    /* Prevent browser from injecting icons */
    input[type="password"] {
        appearance: none;
        -webkit-appearance: none;
    }
    /* Button styles - ensure full width and centered text */
    .auth-card .btn,
    .auth-card button.btn,
    .auth-card button.btn-primary,
    .auth-card .btn-primary {
        width: 100% !important;
        margin-top: 16px;
        text-align: center !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .status {
        margin-top: 12px;
        white-space: pre-wrap;
        color: #0f172a;
    }
    .auth-switch-row {
        margin-top: 16px;
        font-size: 14px;
        color: #4b5563;
        text-align: center;
    }
    .auth-switch-link {
        color: #2563eb;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
    }
    .auth-switch-link:hover {
        text-decoration: underline;
    }
</style>

<main class="page-content">
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-heading">Create account</h1>
            <p class="auth-subtitle">Sign up to continue.</p>

            <label for="name">Name</label>
            <input id="name" type="text" placeholder="Your name" autocomplete="name">

            <label for="email">Email</label>
            <div class="input-wrapper">
                <span class="input-icon-left">
                    <span class="material-symbols-outlined">person</span>
                </span>
                <input id="email" type="email" placeholder="Enter your email" autocomplete="email">
            </div>

            <label for="password">Password</label>
            <div class="input-wrapper">
                <span class="input-icon-left">
                    <span class="material-symbols-outlined">lock</span>
                </span>
                <input id="password" type="password" placeholder="Enter your password" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                <button type="button" class="input-icon-right" id="password-toggle" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility" tabindex="0">
                    <span class="material-symbols-outlined">visibility_off</span>
                </button>
            </div>

            <button class="btn btn-primary" onclick="signup()">Sign Up</button>
            
            <div style="margin: 20px 0; text-align: center; position: relative;">
                <div style="position: absolute; left: 0; right: 0; top: 50%; border-top: 1px solid #e2e8f0;"></div>
                <span style="background: #fff; padding: 0 12px; color: #64748b; font-size: 14px;">or</span>
            </div>
            
            <button type="button" class="btn btn-google" onclick="googleLogin()" style="background: #fff; border: 1px solid #e2e8f0; color: #1f2937; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                    <g fill="#000" fill-rule="evenodd">
                        <path d="M9 3.48c1.69 0 2.83.73 3.48 1.34l2.54-2.48C13.46.89 11.43 0 9 0 5.48 0 2.44 2.02.96 4.96l2.91 2.26C4.6 5.05 6.62 3.48 9 3.48z" fill="#EA4335"/>
                        <path d="M17.64 9.2c0-.74-.06-1.28-.19-1.84H9v3.34h4.96c-.21 1.18-.84 2.08-1.79 2.68l2.85 2.2c1.7-1.57 2.62-3.88 2.62-6.38z" fill="#4285F4"/>
                        <path d="M3.88 10.78A5.54 5.54 0 0 1 3.58 9c0-.62.11-1.22.29-1.78L.96 4.96A9.008 9.008 0 0 0 0 9c0 1.45.35 2.82.96 4.04l2.92-2.26z" fill="#FBBC05"/>
                        <path d="M9 18c2.43 0 4.47-.8 5.96-2.18l-2.85-2.2c-.76.53-1.78.9-3.11.9-2.38 0-4.4-1.57-5.12-3.74L.96 13.04C2.45 15.98 5.48 18 9 18z" fill="#34A853"/>
                    </g>
                </svg>
                Continue with Google
            </button>

            <p class="auth-switch-row">
                Already have an account?
                <a href="<?php echo htmlspecialchars($basePath . '/index.php'); ?>" class="auth-switch-link">Log in</a>
            </p>

            <div id="status" class="status"></div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
<?php
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';

// Password visibility toggle function
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const toggleId = inputId + '-toggle';
    const toggle = document.getElementById(toggleId);
    
    if (!toggle) return;
    
    // Toggle password visibility
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    
    const icon = toggle.querySelector('.material-symbols-outlined');
    if (!icon) return;
    
    // Update icon: visibility_off when hidden (password type), visibility when visible (text type)
    if (isPassword) {
        // Password was hidden, now showing - switch to visibility icon
        icon.textContent = 'visibility';
    } else {
        // Password was visible, now hiding - switch to visibility_off icon
        icon.textContent = 'visibility_off';
    }
}

async function signup() {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    const statusEl = document.getElementById('status');
    statusEl.style.color = '#0f172a';

    if (!name || !email || !password) {
        statusEl.textContent = 'Please fill in all fields.';
        statusEl.style.color = '#dc2626';
        return;
    }

    const res = await fetch(apiBase + '/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password })
    });

    try {
        const data = await res.json();
        if (data.token) {
            // Store token and user info - use synchronous storage
            try {
                localStorage.setItem('jwtToken', data.token);
                if (data.user) {
                    localStorage.setItem('currentUser', JSON.stringify(data.user));
                }
                // Force synchronous write by reading back immediately
                const verifyToken = localStorage.getItem('jwtToken');
                if (!verifyToken || verifyToken !== data.token) {
                    throw new Error('Token storage failed');
                }
            } catch (e) {
                statusEl.textContent = 'Error: Failed to store authentication token. ' + e.message;
                statusEl.style.color = '#dc2626';
                return;
            }
            statusEl.textContent = 'Account created! Redirecting...';
            // Use replace instead of href to prevent back button issues
            // Add a parameter to indicate successful signup
            setTimeout(() => {
                window.location.replace(basePath + '/public/campaigns.php?signed_up=1');
            }, 300);
        } else {
            statusEl.textContent = 'Error: ' + (data.error || JSON.stringify(data));
            statusEl.style.color = '#dc2626';
        }
    } catch (e) {
        statusEl.textContent = 'Error: Unable to parse response from server.';
        statusEl.style.color = '#dc2626';
    }
}

// Google Login function
function googleLogin() {
    const statusEl = document.getElementById('status');
    if (statusEl) {
        statusEl.textContent = 'Redirecting to Google...';
        statusEl.style.color = '#0f172a';
    }
    
    // Redirect to Google OAuth endpoint
    window.location.href = apiBase + '/api/v1/auth/google';
}
</script>

