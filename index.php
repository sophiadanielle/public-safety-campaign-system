<?php
/**
 * API Gateway / Entry Point
 * Handles API routing and serves login page for non-API requests
 */

// Parse request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);

// Normalize URI (remove script directory and index.php from path)
if ($scriptDir !== '/' && $scriptDir !== '.') {
    if (strpos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
    }
}

if (strpos($requestUri, '/index.php') === 0) {
    $requestUri = substr($requestUri, strlen('/index.php'));
} elseif (strpos($requestUri, 'index.php/') !== false) {
    $requestUri = substr($requestUri, strpos($requestUri, 'index.php/') + strlen('index.php'));
}

if ($requestUri === '' || ($requestUri[0] !== '/' && $requestUri !== '')) {
    $requestUri = '/' . $requestUri;
}

// Check if this is an API request
$isApiRequest = strpos($requestUri, '/api/') === 0;

if ($isApiRequest) {
    // Handle API request
    require __DIR__ . '/vendor/autoload.php';
    require __DIR__ . '/src/Config/db_connect.php';

    // Load environment variables
    if (file_exists(__DIR__ . '/.env')) {
        $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }

    // JWT configuration
    $jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-secret-key-change-in-production';
    $jwtIssuer = $_ENV['JWT_ISSUER'] ?? 'public-safety-campaign-system';
    $jwtAudience = $_ENV['JWT_AUDIENCE'] ?? 'public-safety-campaign-system';
    $jwtExpirySeconds = (int) ($_ENV['JWT_EXPIRY_SECONDS'] ?? 86400); // 24 hours

    // Load all route files
    $routeFiles = [
        'auth.php',
        'campaigns.php',
        'content.php',
        'events.php',
        'surveys.php',
        'segments.php',
        'partners.php',
        'impact.php',
        'autocomplete.php',
        'automl.php',
        'integrations.php',
    ];

    $allRoutes = [];
    foreach ($routeFiles as $file) {
        $routes = require __DIR__ . '/src/Routes/' . $file;
        $allRoutes = array_merge($allRoutes, $routes);
    }

    // Get HTTP method
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Find matching route
    $matchedRoute = null;
    $params = [];

    foreach ($allRoutes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }

        // Convert route path pattern to regex
        $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            $matchedRoute = $route;
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            break;
        }
    }

    if (!$matchedRoute) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Route not found']);
        exit;
    }

    // Handle middleware
    $user = null;
    if (isset($matchedRoute['middleware'])) {
        $middlewareClass = $matchedRoute['middleware'];
        try {
            $user = $middlewareClass::authenticate($pdo, $jwtSecret, $jwtAudience, $jwtIssuer);
        } catch (\RuntimeException $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // Instantiate controller
    $handler = $matchedRoute['handler'];
    $controllerClass = $handler[0];
    $methodName = $handler[1];

    // Get controller dependencies from constructor
    $reflection = new \ReflectionClass($controllerClass);
    $constructor = $reflection->getConstructor();
    $dependencies = [];

    if ($constructor) {
        foreach ($constructor->getParameters() as $param) {
            $paramName = $param->getName();
            if ($paramName === 'pdo') {
                $dependencies[] = $pdo;
            } elseif ($paramName === 'jwtSecret') {
                $dependencies[] = $jwtSecret;
            } elseif ($paramName === 'jwtIssuer') {
                $dependencies[] = $jwtIssuer;
            } elseif ($paramName === 'jwtAudience') {
                $dependencies[] = $jwtAudience;
            } elseif ($paramName === 'jwtExpirySeconds') {
                $dependencies[] = $jwtExpirySeconds;
            } else {
                $dependencies[] = null;
            }
        }
    }

    $controller = new $controllerClass(...$dependencies);

    // Call controller method
    try {
        header('Content-Type: application/json');
        $result = $controller->$methodName($user, $params);
        
        // Set HTTP status code if not already set
        if (http_response_code() === 200) {
            http_response_code(200);
        }
        
        echo json_encode($result);
    } catch (\Throwable $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
        error_log('API Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    exit;
}

// Not an API request - show login page
$pageTitle = 'Login';
$hideNav   = true;
include __DIR__ . '/header/includes/header.php';
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
    /* Ensure button text is centered */
    .auth-card .btn *,
    .auth-card button.btn *,
    .auth-card button.btn-primary * {
        text-align: center;
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
                            <input id="password" type="password" placeholder="Enter your password" autocomplete="current-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                            <button type="button" class="input-icon-right" id="password-toggle" onclick="togglePasswordVisibility('password')" aria-label="Toggle password visibility" tabindex="0">
                                <span class="material-symbols-outlined">visibility_off</span>
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
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <span class="material-symbols-outlined">person</span>
                            </span>
                            <input id="signup-email" type="email" placeholder="Enter your email" autocomplete="email">
                        </div>

                        <label for="signup-password">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <span class="material-symbols-outlined">lock</span>
                            </span>
                            <input id="signup-password" type="password" placeholder="Enter your password" autocomplete="new-password" autocorrect="off" autocapitalize="off" spellcheck="false">
                            <button type="button" class="input-icon-right" id="signup-password-toggle" onclick="togglePasswordVisibility('signup-password')" aria-label="Toggle password visibility" tabindex="0">
                                <span class="material-symbols-outlined">visibility_off</span>
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

<?php include __DIR__ . '/header/includes/footer.php'; ?>

<script>
// Get base path for API calls
<?php
require_once __DIR__ . '/header/includes/path_helper.php';
?>
const basePath = '<?php echo $basePath; ?>';
const apiBase = '<?php echo $apiPath; ?>';
console.log('BASE PATH:', basePath);

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

// Login uses the login panel fields and existing API/redirect logic
async function login() {
    const emailInput = document.querySelector('#login-panel #email');
    const passwordInput = document.querySelector('#login-panel #password');
    const statusEl = document.getElementById('status');

    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : ''; // Get full password value
    
    console.log('Login attempt:', { 
        email: email, 
        passwordLength: password.length,
        passwordValue: password.replace(/./g, '*'), // Show as asterisks for security
        emailNormalized: email.toLowerCase(),
        isAdminEmail: email.toLowerCase() === 'admin@barangay1.qc.gov.ph',
        isPassword123: password === 'password123',
        passwordFirst7: password.substring(0, 7)
    });

    if (!email || !password) {
        statusEl.textContent = 'Please enter both email and password.';
        statusEl.style.color = '#dc2626';
        return;
    }

    statusEl.textContent = 'Logging in...';
    statusEl.style.color = '#0f172a';

    try {
        const res = await fetch(apiBase + '/api/v1/auth/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            statusEl.textContent = 'Error: Server returned non-JSON response. Status: ' + res.status + '. Response: ' + text.substring(0, 200);
            statusEl.style.color = '#dc2626';
            console.error('Non-JSON response:', text);
            return;
        }
        
        const data = await res.json();
        console.log('Login response:', { 
            hasToken: !!data.token, 
            hasError: !!data.error, 
            status: res.status,
            tokenLength: data.token ? data.token.length : 0,
            hasUser: !!data.user,
            fullResponse: data
        });
        
        // Check for errors first (including HTTP error status codes)
        if (res.status !== 200 || data.error) {
            const errorMessage = data.error || 'Login failed. Please check your credentials.';
            statusEl.textContent = 'Error: ' + errorMessage;
            statusEl.style.color = '#dc2626';
            console.error('Login error:', { 
                status: res.status, 
                error: data.error, 
                fullResponse: data,
                email: email,
                passwordLength: password.length
            });
            return;
        }
        
        // Check if we got a token
        if (!data.token) {
            statusEl.textContent = 'Error: No authentication token received from server.';
            statusEl.style.color = '#dc2626';
            console.error('No token in response:', data);
            return;
        }
        
        if (data.token) {
            // Check if localStorage is available
            try {
                const testKey = '__localStorage_test__';
                localStorage.setItem(testKey, 'test');
                const testValue = localStorage.getItem(testKey);
                localStorage.removeItem(testKey);
                if (testValue !== 'test') {
                    throw new Error('localStorage test failed');
                }
            } catch (e) {
                statusEl.textContent = 'Error: localStorage is not available. Please enable cookies/localStorage in your browser.';
                statusEl.style.color = '#dc2626';
                console.error('localStorage not available:', e);
                return;
            }
            
            // Store token and user info - use synchronous storage
            try {
                console.log('Storing token...');
                // Clear any old tokens first
                localStorage.removeItem('jwtToken');
                localStorage.removeItem('currentUser');
                
                // Store new token
                localStorage.setItem('jwtToken', data.token);
                if (data.user) {
                    localStorage.setItem('currentUser', JSON.stringify(data.user));
                }
                
                console.log('Token stored, verifying...');
                
                // Force synchronous write by reading back immediately
                const verifyToken = localStorage.getItem('jwtToken');
                if (!verifyToken || verifyToken !== data.token) {
                    console.error('Token verification failed. Expected:', data.token.substring(0, 20), 'Got:', verifyToken ? verifyToken.substring(0, 20) : 'null');
                    throw new Error('Token storage verification failed');
                }
                
                console.log('Token verified successfully');
                
                // Set a flag in sessionStorage to indicate we just logged in
                // This helps campaigns.php know to be patient with token checking
                try {
                    sessionStorage.setItem('justLoggedIn', 'true');
                    sessionStorage.setItem('loginTimestamp', Date.now().toString());
                } catch (e) {
                    console.warn('Could not set sessionStorage flag:', e);
                }
                
                // Verify token is stored and redirect
                statusEl.textContent = 'Logged in! Redirecting...';
                
                // Multiple verification checks before redirect
                setTimeout(() => {
                    // First verification
                    let doubleCheck = localStorage.getItem('jwtToken');
                    if (!doubleCheck || doubleCheck !== data.token) {
                        console.error('Token lost after storage! First check failed.');
                        statusEl.textContent = 'Error: Token storage failed. Please try again.';
                        statusEl.style.color = '#dc2626';
                        return;
                    }
                    
                    // Second verification after a small delay
                    setTimeout(() => {
                        doubleCheck = localStorage.getItem('jwtToken');
                        if (!doubleCheck || doubleCheck !== data.token) {
                            console.error('Token lost after storage! Second check failed.');
                            statusEl.textContent = 'Error: Token storage failed. Please try again.';
                            statusEl.style.color = '#dc2626';
                            return;
                        }
                        
                        console.log('✅ All checks passed - Ready to redirect');
                        console.log('Token before redirect:', localStorage.getItem('jwtToken') ? 'EXISTS' : 'MISSING');
                        console.log('Token length:', localStorage.getItem('jwtToken') ? localStorage.getItem('jwtToken').length : 0);
                        console.log('Token matches:', localStorage.getItem('jwtToken') === data.token);
                        
                        // Store token one more time right before redirect as a safeguard
                        try {
                            localStorage.setItem('jwtToken', data.token);
                            if (data.user) {
                                localStorage.setItem('currentUser', JSON.stringify(data.user));
                            }
                            console.log('✅ Token re-stored before redirect');
                            
                            // Verify token is still there after setting
                            const finalCheck = localStorage.getItem('jwtToken');
                            console.log('Final token verification:', finalCheck ? 'EXISTS (length: ' + finalCheck.length + ')' : 'MISSING');
                            if (!finalCheck || finalCheck !== data.token) {
                                console.error('❌ Token lost immediately after storage!');
                                statusEl.textContent = 'Error: Token storage failed. Please try again.';
                                statusEl.style.color = '#dc2626';
                                return;
                            }
                        } catch (e) {
                            console.error('❌ Failed to re-store token:', e);
                            statusEl.textContent = 'Error: Token storage failed. Please try again.';
                            statusEl.style.color = '#dc2626';
                            return;
                        }
                        
                        // Use href instead of replace to allow back button, and add small delay
                        console.log('🚀 Redirecting to campaigns page...');
                        console.log('Redirect URL:', basePath + '/public/campaigns.php');
                        console.log('Final token verification before redirect:', localStorage.getItem('jwtToken') ? 'EXISTS' : 'MISSING');
                        
                        // Store token in sessionStorage as backup in case localStorage is blocked
                        try {
                            sessionStorage.setItem('jwtToken_backup', data.token);
                            console.log('Token backed up to sessionStorage');
                        } catch (e) {
                            console.warn('Could not backup token to sessionStorage:', e);
                        }
                        
                        setTimeout(function() {
                            // Double-check token is still there
                            const finalToken = localStorage.getItem('jwtToken');
                            if (!finalToken) {
                                console.error('Token lost before redirect! Trying sessionStorage backup...');
                                const backupToken = sessionStorage.getItem('jwtToken_backup');
                                if (backupToken) {
                                    localStorage.setItem('jwtToken', backupToken);
                                    console.log('Restored token from sessionStorage backup');
                                }
                            }
                            // Add URL parameter to indicate successful login (bypasses Tracking Prevention issue)
                            window.location.href = basePath + '/public/campaigns.php?logged_in=1&t=' + Date.now();
                        }, 200);
                    }, 200);
                }, 500);
            } catch (e) {
                console.error('Token storage error:', e);
                statusEl.textContent = 'Error: Failed to store authentication token. ' + e.message;
                statusEl.style.color = '#dc2626';
                return;
            }
        } else {
            statusEl.textContent = 'Error: ' + (data.error || JSON.stringify(data));
            statusEl.style.color = '#dc2626';
        }
    } catch (e) {
        statusEl.textContent = 'Error: Unable to parse response from server. ' + e.message;
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
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            statusEl.textContent = 'Error: Server returned non-JSON response. Status: ' + res.status + '. Response: ' + text.substring(0, 200);
            statusEl.style.color = '#dc2626';
            return;
        }
        
        const data = await res.json();
        if (data.token) {
            // Check if localStorage is available
            try {
                const testKey = '__localStorage_test__';
                localStorage.setItem(testKey, 'test');
                const testValue = localStorage.getItem(testKey);
                localStorage.removeItem(testKey);
                if (testValue !== 'test') {
                    throw new Error('localStorage test failed');
                }
            } catch (e) {
                statusEl.textContent = 'Error: localStorage is not available. Please enable cookies/localStorage in your browser.';
                statusEl.style.color = '#dc2626';
                console.error('localStorage not available:', e);
                return;
            }
            
            // Store token and user info - use synchronous storage
            try {
                console.log('Storing token...');
                // Clear any old tokens first
                localStorage.removeItem('jwtToken');
                localStorage.removeItem('currentUser');
                
                // Store new token
                localStorage.setItem('jwtToken', data.token);
                if (data.user) {
                    localStorage.setItem('currentUser', JSON.stringify(data.user));
                }
                
                console.log('Token stored, verifying...');
                
                // Force synchronous write by reading back immediately
                const verifyToken = localStorage.getItem('jwtToken');
                if (!verifyToken || verifyToken !== data.token) {
                    console.error('Token verification failed. Expected:', data.token.substring(0, 20), 'Got:', verifyToken ? verifyToken.substring(0, 20) : 'null');
                    throw new Error('Token storage verification failed');
                }
                
                console.log('Token verified successfully');
                
                // Set a flag in sessionStorage to indicate we just signed up
                // This helps campaigns.php know to be patient with token checking
                try {
                    sessionStorage.setItem('justLoggedIn', 'true');
                    sessionStorage.setItem('loginTimestamp', Date.now().toString());
                } catch (e) {
                    console.warn('Could not set sessionStorage flag:', e);
                }
                
                // Verify token is stored and redirect
                statusEl.textContent = 'Account created! Redirecting...';
                
                // Small delay to ensure localStorage is fully written
                setTimeout(() => {
                    const doubleCheck = localStorage.getItem('jwtToken');
                    if (!doubleCheck || doubleCheck !== data.token) {
                        console.error('Token lost after storage!');
                        statusEl.textContent = 'Error: Token storage failed. Please try again.';
                        statusEl.style.color = '#dc2626';
                        return;
                    }
                    
                    console.log('✅ All checks passed - Ready to redirect');
                    console.log('Token before redirect:', localStorage.getItem('jwtToken') ? 'EXISTS' : 'MISSING');
                    console.log('Token length:', localStorage.getItem('jwtToken') ? localStorage.getItem('jwtToken').length : 0);
                    console.log('sessionStorage justLoggedIn:', sessionStorage.getItem('justLoggedIn'));
                    
                        // CRITICAL: Ensure sessionStorage flag is set before redirect
                        try {
                            sessionStorage.setItem('justLoggedIn', 'true');
                            sessionStorage.setItem('loginTimestamp', Date.now().toString());
                            console.log('✅ sessionStorage flags set');
                            console.log('justLoggedIn flag:', sessionStorage.getItem('justLoggedIn'));
                        } catch (e) {
                            console.error('❌ Failed to set sessionStorage flags:', e);
                        }
                    
                    console.log('🚀 Redirecting to campaigns page...');
                    console.log('Redirect URL:', basePath + '/public/campaigns.php');
                    // Add URL parameter to indicate successful signup (bypasses Tracking Prevention issue)
                    setTimeout(function() {
                        window.location.href = basePath + '/public/campaigns.php?signed_up=1&t=' + Date.now();
                    }, 100);
                }, 300);
            } catch (e) {
                console.error('Token storage error:', e);
                statusEl.textContent = 'Error: Failed to store authentication token. ' + e.message;
                statusEl.style.color = '#dc2626';
                return;
            }
        } else {
            statusEl.textContent = 'Error: ' + (data.error || JSON.stringify(data));
            statusEl.style.color = '#dc2626';
        }
    } catch (e) {
        statusEl.textContent = 'Error: Unable to parse response from server. ' + e.message;
        statusEl.style.color = '#dc2626';
    }
}
</script>
