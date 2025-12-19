<?php
$pageTitle = 'Sign Up';
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
    .auth-card {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        padding: 28px;
        width: 100%;
        max-width: 440px;
    }
    .auth-card h1 {
        margin: 0 0 8px 0;
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
    }
    .auth-card p {
        margin: 0 0 20px 0;
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
</style>

<main class="page-content">
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Create account</h1>
            <p>Sign up to continue.</p>
            <label>Name
                <input id="name" type="text" placeholder="Your name">
            </label>
            <label>Email
                <input id="email" type="email" placeholder="you@example.com">
            </label>
            <label>Password
                <div class="password-field">
                    <input id="password" type="password" placeholder="Password">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                        Show
                    </button>
                </div>
            </label>
            <button class="btn btn-primary" onclick="signup()">Sign Up</button>
            <p style="margin-top:12px; font-size:14px; color:#4b5563;">
                Already have an account?
                <a href="<?php echo htmlspecialchars($basePath . '/public/index.php'); ?>">Log in</a>
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

function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.textContent = isPassword ? 'Hide' : 'Show';
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

