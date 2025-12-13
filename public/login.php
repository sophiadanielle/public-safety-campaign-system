<?php
$pageTitle = 'Login';
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
    .status {
        margin-top: 12px;
        white-space: pre-wrap;
        color: #0f172a;
    }
</style>

<main class="page-content">
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1>Sign in</h1>
            <p>Access the campaign dashboard.</p>
            <label>Email
                <input id="email" type="email" value="test.user@example.com">
            </label>
            <label>Password
                <input id="password" type="password" value="password">
            </label>
            <button class="btn btn-primary" onclick="login()">Sign In</button>
            <div id="status" class="status"></div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../header/includes/footer.php'; ?>

<script>
async function login() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const res = await fetch('/api/v1/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    const data = await res.json();
    if (data.token) {
        localStorage.setItem('jwtToken', data.token);
        document.getElementById('status').textContent = 'Logged in. Token saved to localStorage.';
    } else {
        document.getElementById('status').textContent = JSON.stringify(data);
    }
}
</script>
