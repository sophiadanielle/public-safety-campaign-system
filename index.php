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
    // Start output buffering early to catch any warnings/errors
    if (ob_get_level() == 0) {
        ob_start();
    }
    
    // Suppress error display but log them
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    
    // Handle API request
    require __DIR__ . '/vendor/autoload.php';
    
    // Load environment variables BEFORE database connection
    $envPath = __DIR__ . '/.env';
    error_log('DB DEBUG: index.php - Checking for .env at: ' . $envPath);
    if (file_exists($envPath)) {
        error_log('DB DEBUG: index.php - .env file found, loading...');
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $loadedCount = 0;
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // IMPORTANT: Set even if empty (for empty passwords like LOCAL_DB_PASS=)
            $_ENV[$name] = $value;
            putenv("$name=$value");
            if (strpos($name, 'DB_') === 0 || strpos($name, 'LOCAL_DB_') === 0) {
                error_log("DB DEBUG: index.php - Loaded \$_ENV['$name'] = " . ($value === '' ? '[empty]' : "'$value'") . " (line " . ($lineNum + 1) . ")");
                $loadedCount++;
            }
        }
        error_log("DB DEBUG: index.php - Loaded $loadedCount DB-related variables from .env");
    } else {
        error_log('DB DEBUG: index.php - WARNING: .env file NOT found at: ' . $envPath);
    }
    error_log('DB DEBUG: index.php - $_ENV now has ' . count($_ENV) . ' total keys');
    
    // Load database connection - catch exceptions to return proper JSON error
    $isAuthRequest = strpos($requestUri, '/api/v1/auth/login') !== false || 
                     strpos($requestUri, '/api/v1/auth/register') !== false;
    
    try {
        require __DIR__ . '/src/Config/db_connect.php';
        // Verify PDO was created successfully (unless this is an auth request)
        if (!$isAuthRequest) {
            if (!isset($pdo)) {
                error_log('DB ERROR: $pdo variable is not set after requiring db_connect.php');
                throw new RuntimeException('Database connection variable is not set');
            }
            if ($pdo === null) {
                error_log('DB ERROR: $pdo is null after requiring db_connect.php');
                throw new RuntimeException('Database connection is null - this should not happen for non-auth requests');
            }
            if (!($pdo instanceof PDO)) {
                error_log('DB ERROR: $pdo is not a PDO instance, type: ' . gettype($pdo));
                throw new RuntimeException('Database connection is not a valid PDO instance');
            }
            // Test the connection
            try {
                $testResult = $pdo->query('SELECT 1 as test')->fetch();
                if (!$testResult || $testResult['test'] != 1) {
                    error_log('DB ERROR: Connection test query failed - unexpected result');
                    throw new RuntimeException('Database connection test failed - query returned unexpected result');
                }
                error_log('DB DEBUG: index.php - PDO connection verified successfully');
            } catch (\PDOException $testEx) {
                error_log('DB ERROR: Connection test query failed: ' . $testEx->getMessage());
                throw new RuntimeException('Database connection test failed: ' . $testEx->getMessage(), 0, $testEx);
            }
        }
    } catch (PDOException $e) {
        $errorMsg = 'Database connection failed: ' . $e->getMessage();
        error_log('DB ERROR: PDOException caught in index.php: ' . $e->getMessage());
        error_log('DB ERROR: PDOException code: ' . $e->getCode());
        error_log('DB ERROR: PDOException file: ' . $e->getFile() . ':' . $e->getLine());
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $errorMsg,
            'message' => $e->getMessage(),
            'details' => [
                'code' => $e->getCode(),
                'file' => basename($e->getFile()),
                'line' => $e->getLine(),
            ]
        ]);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
    } catch (Exception $e) {
        $errorMsg = 'Database initialization failed: ' . $e->getMessage();
        error_log('DB ERROR: Exception caught in index.php: ' . $e->getMessage());
        error_log('DB ERROR: Exception type: ' . get_class($e));
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $errorMsg,
            'message' => $e->getMessage(),
        ]);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
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
        'dashboard.php',
        'notifications.php',
        'messages.php',
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
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Route not found']);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
    }

    // Handle middleware
    $user = null;
    if (isset($matchedRoute['middleware'])) {
        $middlewareClass = $matchedRoute['middleware'];
        try {
            $user = $middlewareClass::authenticate($pdo, $jwtSecret, $jwtAudience, $jwtIssuer);
        } catch (\RuntimeException $e) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
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
                // For non-auth requests, PDO must be set and valid
                if (!$isAuthRequest) {
                    if (!isset($pdo) || $pdo === null) {
                        error_log('Dependency injection ERROR: Attempting to pass null PDO to ' . $controllerClass);
                        throw new RuntimeException('Database connection is required but PDO is null');
                    }
                    if (!($pdo instanceof PDO)) {
                        error_log('Dependency injection ERROR: Attempting to pass non-PDO to ' . $controllerClass . ', type: ' . gettype($pdo));
                        throw new RuntimeException('Database connection is not a valid PDO instance');
                    }
                }
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
        // Clear any output that might have been generated
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        // Set headers FIRST before any controller code runs
        if (!headers_sent()) {
            header('Content-Type: application/json');
            header('X-Content-Type-Options: nosniff');
        }
        
        // Call controller method
        $result = $controller->$methodName($user, $params);
        
        // Check for any unexpected output after controller call
        if (ob_get_level() > 0) {
            $output = ob_get_contents();
            if (!empty($output)) {
                error_log('Unexpected output before JSON (length: ' . strlen($output) . '): ' . substr($output, 0, 500));
                ob_clean();
            }
        }
        
        // Check if controller already set a status code
        $currentStatus = http_response_code();
        
        // If no status was set by controller, set 200 for success
        if ($currentStatus === false) {
            http_response_code(200);
        }
        
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($json === false) {
            $errorMsg = json_last_error_msg();
            error_log('JSON encoding failed: ' . $errorMsg);
            error_log('Data type: ' . gettype($result));
            if (is_array($result)) {
                error_log('Array keys: ' . implode(', ', array_keys($result)));
                if (count($result) > 0) {
                    error_log('Array sample: ' . print_r(array_slice($result, 0, 2, true), true));
                }
            }
            throw new \RuntimeException('Failed to encode JSON: ' . $errorMsg);
        }
        
        echo $json;
    } catch (\Throwable $e) {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }
        $errorMsg = $e->getMessage();
        // Don't expose internal errors in production
        if (strpos($errorMsg, 'Stack trace') !== false || strpos($errorMsg, 'Fatal error') !== false) {
            $errorMsg = 'Internal server error';
        }
        $errorResponse = ['error' => $errorMsg];
        echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        error_log('API Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    
    // End output buffering and flush
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit;
}

// Not an API request - show login page
$pageTitle = 'Login';
$hideNav   = true;

// Check for error from Google OAuth
$error = isset($_GET['error']) ? $_GET['error'] : '';

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
                        
                        <div style="margin: 20px 0; text-align: center; position: relative; height: 1px;">
                            <div style="position: absolute; left: 0; right: 0; top: 0; border-top: 1px solid #e2e8f0;"></div>
                            <span style="position: relative; background: #fff; padding: 0 12px; color: #64748b; font-size: 14px; z-index: 1; display: inline-block; top: -10px;">or</span>
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
                            Don't have an account?
                            <label for="auth-toggle" class="auth-switch-link">Sign up</label>
                        </p>

                        <div id="status" class="status"><?php if ($error): ?><span style="color: #dc2626;">Error: <?php echo htmlspecialchars($error); ?></span><?php endif; ?></div>
                    </section>

                    <!-- Signup Panel -->
                    <section class="auth-panel" id="signup-panel">
                        <h1 class="auth-heading">Create account</h1>
                        <p class="auth-subtitle">Sign up to continue.</p>

                        <label for="name">Name</label>
                        <div class="input-wrapper">
                            <span class="input-icon-left">
                                <span class="material-symbols-outlined">person</span>
                            </span>
                            <input id="name" type="text" placeholder="Your name" autocomplete="name">
                        </div>

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

                        <label for="signup-role">Role <span style="color: #dc2626;">*</span></label>
                        <select id="signup-role" name="role" required style="width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; margin-top: 6px; background: #fff;">
                            <option value="">Select your role</option>
                            <option value="staff">Staff - Create campaign drafts</option>
                            <option value="secretary">Secretary - Review and route drafts</option>
                            <option value="kagawad">Kagawad - Review and recommend approval</option>
                            <option value="captain">Captain - Final approval authority</option>
                            <option value="partner">Partner - External partner access</option>
                            <option value="viewer">Viewer - Read-only access</option>
                        </select>

                        <button class="btn btn-primary" onclick="signup()">Sign Up</button>
                        
                        <div style="margin: 20px 0; text-align: center; position: relative; height: 1px;">
                            <div style="position: absolute; left: 0; right: 0; top: 0; border-top: 1px solid #e2e8f0;"></div>
                            <span style="position: relative; background: #fff; padding: 0 12px; color: #64748b; font-size: 14px; z-index: 1; display: inline-block; top: -10px;">or</span>
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
                        console.log('Redirect URL:', basePath + '/public/dashboard.php');
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
                            window.location.href = basePath + '/public/dashboard.php?logged_in=1&t=' + Date.now();
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
    const roleInput = document.querySelector('#signup-panel #signup-role');
    const statusEl = document.getElementById('signup-status');

    const name = nameInput ? nameInput.value.trim() : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';
    const role = roleInput ? roleInput.value.trim() : '';

    statusEl.style.color = '#0f172a';

    if (!name || !email || !password || !role) {
        statusEl.textContent = 'Please fill in all fields including role selection.';
        statusEl.style.color = '#dc2626';
        return;
    }

    const res = await fetch(apiBase + '/api/v1/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password, role })
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
        
        // Provide helpful error messages
        if (!data.token && data.error) {
            let errorMsg = data.error;
            if (errorMsg.includes('already exists')) {
                errorMsg = 'An account with this email already exists. Please try logging in instead, or use a different email address.';
            }
            statusEl.textContent = 'Error: ' + errorMsg;
            statusEl.style.color = '#dc2626';
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
                        window.location.href = basePath + '/public/dashboard.php?signed_up=1&t=' + Date.now();
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

// Google Login function
function googleLogin() {
    const statusEl = document.getElementById('status') || document.getElementById('signup-status');
    if (statusEl) {
        statusEl.textContent = 'Redirecting to Google...';
        statusEl.style.color = '#0f172a';
    }
    
    // Redirect to Google OAuth endpoint
    window.location.href = apiBase + '/api/v1/auth/google';
}
</script>
