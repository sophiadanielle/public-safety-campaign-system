<?php
$pageTitle = 'Login';
$hideNav   = true;
include __DIR__ . '/../header/includes/header.php';
?>

<style>
    .auth-wrapper {
        min-height: calc(100vh - 140px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }
    .auth-container {
        width: 100%;
        max-width: 880px;
    }
    .auth-toggle {
        display: none;
    }
    .auth-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        padding: 28px;
        width: 100%;
        overflow: hidden;
    }
    .auth-panels {
        display: flex;
        width: 200%;
        transform: translateX(0);
        transition: transform 0.5s ease;
    }
    .auth-panel {
        width: 50%;
        box-sizing: border-box;
        padding: 0 32px 8px;
    }
    #auth-toggle:checked + .auth-card .auth-panels {
        transform: translateX(-50%);
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
    .auth-card input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-top: 6px;
        font-size: 14px;
        background: #fff;
    }
    .auth-card input:focus {
        outline: none;
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }
    .auth-card .btn {
        width: 100%;
        margin-top: 16px;
    }
    .password-field {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }
    .password-field input {
        margin-top: 0;
        flex: 1;
    }
    .password-toggle {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 8px;
        padding: 8px 10px;
        cursor: pointer;
        font-size: 12px;
        color: #475569;
    }
    .password-toggle:hover {
        background: #e5edff;
        border-color: #2563eb;
        color: #1d4ed8;
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
    }
</style>

<main class="page-content">
    <div class="auth-wrapper">
        <div class="auth-container">
            <!-- Checkbox controls sliding between login & signup panels -->
            <input type="checkbox" id="auth-toggle" class="auth-toggle">
            <div class="auth-card">
                <div class="auth-panels">
                    <!-- Login Panel -->
                    <section class="auth-panel" id="login-panel">
                        <h1 class="auth-heading">Sign in</h1>
                        <p class="auth-subtitle">Access the campaign dashboard.</p>

                        <label for="email">Email</label>
                        <input id="email" type="email" value="admin@barangay1.qc.gov.ph" autocomplete="email">

                        <label for="password">Password</label>
                        <div class="password-field">
                            <input id="password" type="password" value="password123" autocomplete="current-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                                Show
                            </button>
                        </div>

                        <button class="btn btn-primary" onclick="login()">Sign In</button>

                        <p class="auth-switch-row">
                            Don't have an account?
                            <label for="auth-toggle" class="auth-switch-link">Sign up</label>
                        </p>

                        <div id="status" class="status"></div>
                    </section>

                    <!-- Signup Panel -->
                    <section class="auth-panel" id="signup-panel">
                        <h1 class="auth-heading">Create account</h1>
                        <p class="auth-subtitle">Sign up to continue.</p>

                        <label for="name">Name</label>
                        <input id="name" type="text" placeholder="Your name" autocomplete="name">

                        <label for="signup-email">Email</label>
                        <input id="signup-email" type="email" placeholder="you@example.com" autocomplete="email">

                        <label for="signup-password">Password</label>
                        <div class="password-field">
                            <input id="signup-password" type="password" placeholder="Password" autocomplete="new-password">
                            <button type="button" class="password-toggle" onclick="togglePassword('signup-password', this)" aria-label="Toggle password visibility">
                                Show
                            </button>
                        </div>

                        <button class="btn btn-primary" onclick="signup()">Sign Up</button>

                        <p class="auth-switch-row">
                            Already have an account?
                            <label for="auth-toggle" class="auth-switch-link">Log in</label>
                        </p>

                        <div id="signup-status" class="status"></div>
                    </section>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
// Get base path for API calls
<?php
require_once __DIR__ . '/../header/includes/path_helper.php';
?>
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.textContent = isPassword ? 'Hide' : 'Show';
}

// Login uses the login panel fields and existing API/redirect logic
async function login() {
    const emailInput = document.querySelector('#login-panel #email');
    const passwordInput = document.querySelector('#login-panel #password');
    const statusEl = document.getElementById('status');

    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';

    const res = await fetch(apiBase + '/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });

    statusEl.style.color = '#0f172a';

    try {
        const data = await res.json();
        if (data.token) {
            localStorage.setItem('jwtToken', data.token);
            statusEl.textContent = 'Logged in! Redirecting...';
            setTimeout(() => {
                window.location.href = basePath + '/public/campaigns.php';
            }, 1000);
        } else {
            statusEl.textContent = 'Error: ' + (data.error || JSON.stringify(data));
            statusEl.style.color = '#dc2626';
        }
    } catch (e) {
        statusEl.textContent = 'Error: Unable to parse response from server.';
        statusEl.style.color = '#dc2626';
    }
}

// Signup uses the signup panel fields and existing API/redirect logic
async function signup() {
    const nameInput = document.querySelector('#signup-panel #name');
    const emailInput = document.querySelector('#signup-panel #signup-email');
    const passwordInput = document.querySelector('#signup-panel #signup-password');
    const statusEl = document.getElementById('signup-status');

    const name = nameInput ? nameInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';

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
            localStorage.setItem('jwtToken', data.token);
            statusEl.textContent = 'Account created! Redirecting...';
            setTimeout(() => {
                window.location.href = basePath + '/public/campaigns.php';
            }, 1000);
        } else {
            statusEl.textContent = 'Error: ' + (data.error || JSON.stringify(data));
            statusEl.style.color = '#dc2626';
        }
    } catch (e) {
        statusEl.textContent = 'Error: Unable to parse response from server.';
        statusEl.style.color = '#dc2626';
    }
}
</script>

