<?php
/**
 * API Gateway / Entry Point
 * Handles API routing and serves login page for non-API requests
 */

// CRITICAL: Check for API request FIRST - before any other processing
// This must be the absolute first check to ensure API requests are handled correctly
$rawUri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($rawUri, '/api/') !== false) {
    // This is definitely an API request - extract the API path immediately
    $pathPart = parse_url($rawUri, PHP_URL_PATH);
    if ($pathPart && strpos($pathPart, '/api/') !== false) {
        $apiPos = strpos($pathPart, '/api/');
        $apiPath = substr($pathPart, $apiPos);
        // Set a flag to skip HTML rendering
        $GLOBALS['IS_API_REQUEST'] = true;
        $GLOBALS['API_PATH'] = $apiPath;
        error_log("EARLY API DETECTION: Found /api/ in URI, extracted path: " . $apiPath);
    }
}

// CRITICAL: Force production basePath at the VERY START (before any includes)
// This runs first and sets a global flag that path_helper.php will respect
if (isset($_SERVER['HTTP_HOST'])) {
    $earlyHost = strtolower($_SERVER['HTTP_HOST']);
    $isEarlyProduction = (
        strpos($earlyHost, 'alertaraqc.com') !== false ||
        strpos($earlyHost, 'campaign.') !== false ||
        ($earlyHost !== '' && 
         strpos($earlyHost, 'localhost') === false && 
         $earlyHost !== '127.0.0.1' &&
         strpos($earlyHost, '.local') === false)
    );
    
    if ($isEarlyProduction) {
        // Set global flag for path_helper.php to use
        $GLOBALS['FORCE_PRODUCTION_BASEPATH'] = true;
        // Also set it directly here as backup
        $basePath = '';
        $apiPath = '/index.php';
        $cssPath = '/header/css';
        $imgPath = '/header/images';
        $publicPath = '/public';
        error_log("EARLY PRODUCTION DETECTION: Forced empty basePath at index.php start");
    }
}

// Parse request URI
$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);

// CRITICAL: Check for API path FIRST before any manipulation
// This must be the very first check
$rawRequestUri = $_SERVER['REQUEST_URI'] ?? '';
$isApiRequestEarly = strpos($rawRequestUri, '/api/') !== false;

// If we detected API early at the top, use that path
if (isset($GLOBALS['IS_API_REQUEST']) && $GLOBALS['IS_API_REQUEST'] && isset($GLOBALS['API_PATH'])) {
    $requestUri = $GLOBALS['API_PATH'];
    $isApiRequestEarly = true;
    $isApiRequest = true;
    error_log("ROUTING DEBUG: Using proxy-detected API path: " . $requestUri);
    error_log("ROUTING DEBUG: SKIPPING URI normalization - path already set by proxy");
    // Skip all URI normalization - the proxy has already set the correct path
    goto skip_uri_normalization;
}

// Debug logging
error_log("ROUTING DEBUG: REQUEST_URI = " . $rawRequestUri);
error_log("ROUTING DEBUG: SCRIPT_NAME = " . $scriptName);
error_log("ROUTING DEBUG: SCRIPT_DIR = " . $scriptDir);
error_log("ROUTING DEBUG: Initial requestUri = " . $requestUri);
error_log("ROUTING DEBUG: isApiRequestEarly = " . ($isApiRequestEarly ? 'YES' : 'NO'));

// Normalize URI (remove script directory and index.php from path)
// CRITICAL: Handle both root deployment and subdirectory deployment
if ($scriptDir !== '/' && $scriptDir !== '.') {
    if (strpos($requestUri, $scriptDir) === 0) {
        $requestUri = substr($requestUri, strlen($scriptDir));
        error_log("ROUTING DEBUG: After removing scriptDir: " . $requestUri);
    }
}

// Check for API path in normalized URI
$hasApiPath = strpos($requestUri, '/api/') !== false || strpos($requestUri, 'api/') !== false;

// Remove /index.php from start of path (root deployment: /index.php/api/...)
if (strpos($requestUri, '/index.php') === 0) {
    $requestUri = substr($requestUri, strlen('/index.php'));
    error_log("ROUTING DEBUG: After removing /index.php from start: " . $requestUri);
} 
// Remove index.php/ from anywhere in path (subdirectory: /public-safety-campaign-system/index.php/api/...)
// This handles: /public-safety-campaign-system/index.php/api/v1/auth/login
elseif (strpos($requestUri, 'index.php/') !== false) {
    $pos = strpos($requestUri, 'index.php/');
    $requestUri = substr($requestUri, $pos + strlen('index.php/'));
    error_log("ROUTING DEBUG: After removing index.php/: " . $requestUri);
}
// Also handle /index.php/api/ pattern (another subdirectory format)
elseif (strpos($requestUri, '/index.php/api/') !== false) {
    $requestUri = substr($requestUri, strpos($requestUri, '/index.php/api/') + strlen('/index.php'));
    error_log("ROUTING DEBUG: After removing /index.php/api/: " . $requestUri);
}
// Also handle /index.php at end (shouldn't happen for API but handle it)
elseif (strpos($requestUri, '/index.php') !== false && strpos($requestUri, '/index.php') === (strlen($requestUri) - strlen('/index.php'))) {
    $requestUri = substr($requestUri, 0, strpos($requestUri, '/index.php'));
    error_log("ROUTING DEBUG: After removing /index.php from end: " . $requestUri);
}

// Ensure URI starts with /
if ($requestUri === '' || ($requestUri[0] !== '/' && $requestUri !== '')) {
    $requestUri = '/' . $requestUri;
}

skip_uri_normalization:

// Check for API path in the final URI (needed for detection logic below)
$hasApiPath = strpos($requestUri, '/api/') !== false || strpos($requestUri, 'api/') !== false;

// Check if this is an API request - prioritize early detection
// If early detection found it, trust that
// IMPORTANT: Don't overwrite $isApiRequest if it was already set by the proxy
if (!isset($isApiRequest)) {
    $isApiRequest = $isApiRequestEarly;
}

// If not detected early, check normalized URI
if (!$isApiRequest) {
    $isApiRequest = (
        strpos($requestUri, '/api/') === 0 || 
        $hasApiPath ||
        (isset($_SERVER['PATH_INFO']) && strpos($_SERVER['PATH_INFO'], '/api/') !== false)
    );
}

// ABSOLUTE FINAL CHECK: If REQUEST_URI contains /api/, it MUST be an API request
// This is a safety net to catch any edge cases
if (!$isApiRequest && isset($_SERVER['REQUEST_URI'])) {
    $rawCheck = $_SERVER['REQUEST_URI'];
    if (strpos($rawCheck, '/api/') !== false) {
        $isApiRequest = true;
        // Extract the API path from the raw URI
        $pathPart = parse_url($rawCheck, PHP_URL_PATH);
        if ($pathPart && strpos($pathPart, '/api/') !== false) {
            $apiPos = strpos($pathPart, '/api/');
            $requestUri = substr($pathPart, $apiPos);
            error_log("ROUTING DEBUG: ABSOLUTE FINAL CHECK - forced API, path: " . $requestUri);
        }
    }
}

error_log("ROUTING DEBUG: Final requestUri = " . $requestUri);
error_log("ROUTING DEBUG: REQUEST_URI contains /api/: " . (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false ? 'YES' : 'NO'));
error_log("ROUTING DEBUG: isApiRequestEarly = " . ($isApiRequestEarly ? 'YES' : 'NO'));
error_log("ROUTING DEBUG: isApiRequest = " . ($isApiRequest ? 'YES' : 'NO'));

// FINAL FALLBACK: If still not detected but REQUEST_URI has /api/, force extract the API path
if (!$isApiRequest && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    // Extract API path from REQUEST_URI directly
    $fullUri = $_SERVER['REQUEST_URI'];
    $pathPart = parse_url($fullUri, PHP_URL_PATH);
    if ($pathPart) {
        $apiPosInPath = strpos($pathPart, '/api/');
        if ($apiPosInPath !== false) {
            $requestUri = substr($pathPart, $apiPosInPath);
            $isApiRequest = true;
            error_log("ROUTING DEBUG: FORCED API REQUEST - extracted path: " . $requestUri);
        }
    }
}

// ABSOLUTE FINAL CHECK: If REQUEST_URI contains /api/, it MUST be an API request
if (!$isApiRequest && isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    $isApiRequest = true;
    // Try to extract path one more time
    $fullUri = $_SERVER['REQUEST_URI'];
    $pathPart = parse_url($fullUri, PHP_URL_PATH);
    if ($pathPart && strpos($pathPart, '/api/') !== false) {
        $apiPos = strpos($pathPart, '/api/');
        $requestUri = substr($pathPart, $apiPos);
        error_log("ROUTING DEBUG: ABSOLUTE FINAL - forced API, path: " . $requestUri);
    }
}

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
        'otp.php',
        'user_management.php',
        'budgets.php',
        'audit.php',
        'search.php',
    ];

    $allRoutes = [];
    foreach ($routeFiles as $file) {
        $routePath = __DIR__ . '/src/Routes/' . $file;
        error_log("ROUTE LOADING: Attempting to load $routePath");
        
        if (!file_exists($routePath)) {
            error_log("ROUTE LOADING ERROR: File not found: $routePath");
            continue;
        }
        
        try {
            $routes = require $routePath;
            if (!is_array($routes)) {
                error_log("ROUTE LOADING ERROR: $file did not return an array");
                continue;
            }
            error_log("ROUTE LOADING: Loaded " . count($routes) . " routes from $file");
            $allRoutes = array_merge($allRoutes, $routes);
        } catch (Exception $e) {
            error_log("ROUTE LOADING ERROR: Failed to load $file: " . $e->getMessage());
        }
    }
    
    error_log("ROUTE LOADING: Total routes loaded: " . count($allRoutes));

    // Get HTTP method
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Find matching route
    $matchedRoute = null;
    $params = [];
    
    error_log("ROUTE MATCHING: Looking for route with method=$method and path=$requestUri");
    error_log("ROUTE MATCHING: Total routes to check: " . count($allRoutes));

    foreach ($allRoutes as $route) {
        if ($route['method'] !== $method) {
            continue;
        }

        // Convert route path pattern to regex
        $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';
        
        error_log("ROUTE MATCHING: Checking route: " . $route['path'] . " against pattern: " . $pattern);

        if (preg_match($pattern, $requestUri, $matches)) {
            $matchedRoute = $route;
            error_log("ROUTE MATCHING: MATCH FOUND! Route: " . $route['path']);
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
        error_log("ROUTE MATCHING: NO MATCH FOUND for path=$requestUri with method=$method");
        
        // Collect sample routes for debugging
        $sampleRoutes = [];
        $postRoutes = [];
        foreach ($allRoutes as $route) {
            if ($route['method'] === 'POST') {
                $postRoutes[] = $route['path'];
            }
            if (count($sampleRoutes) < 5) {
                $sampleRoutes[] = [
                    'method' => $route['method'],
                    'path' => $route['path']
                ];
            }
        }
        
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => 'Route not found',
            'debug' => [
                'requestUri' => $requestUri,
                'method' => $method,
                'rawRequestUri' => $_SERVER['REQUEST_URI'] ?? 'NOT SET',
                'totalRoutesLoaded' => count($allRoutes),
                'postRoutesAvailable' => $postRoutes,
                'sampleRoutes' => $sampleRoutes,
                'isApiRequest' => $isApiRequest,
                'globalsSet' => isset($GLOBALS['IS_API_REQUEST']) && isset($GLOBALS['API_PATH'])
            ]
        ]);
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
        exit;
    }

    // Handle middleware (support single class or array of classes)
    $user = null;
    if (isset($matchedRoute['middleware'])) {
        $middlewares = is_array($matchedRoute['middleware']) ? $matchedRoute['middleware'] : [$matchedRoute['middleware']];
        
        foreach ($middlewares as $middlewareClass) {
            // JWTMiddleware handles authentication
            if ($middlewareClass === \App\Middleware\JWTMiddleware::class) {
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
            // ViewerBlockMiddleware blocks Viewer from write operations
            elseif ($middlewareClass === \App\Middleware\ViewerBlockMiddleware::class) {
                if ($user) {
                    \App\Middleware\ViewerBlockMiddleware::blockViewer($user, $pdo, $method);
                }
            }
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

// Not an API request - show landing page
require_once __DIR__ . '/landing_page.php';
exit;