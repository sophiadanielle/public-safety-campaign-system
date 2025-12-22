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

<?php include __DIR__ . '/header/includes/footer.php'; ?>

<script>
// Get base path for API calls
<?php
require_once __DIR__ . '/header/includes/path_helper.php';
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
        const contentType = res.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await res.text();
            statusEl.textContent = 'Error: Server returned non-JSON response. Status: ' + res.status + '. Response: ' + text.substring(0, 200);
            statusEl.style.color = '#dc2626';
            return;
        }
        
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
        statusEl.textContent = 'Error: Unable to parse response from server. ' + e.message;
        statusEl.style.color = '#dc2626';
    }
}
</script>
