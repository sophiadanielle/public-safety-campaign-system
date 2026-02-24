<?php
/**
 * Cloudinary Configuration
 * 
 * To use Cloudinary for file uploads:
 * 1. Create a free account at https://cloudinary.com
 * 2. Get your Cloud Name, API Key, and API Secret from the Dashboard
 * 3. Add these to your .env file:
 *    CLOUDINARY_CLOUD_NAME=your_cloud_name
 *    CLOUDINARY_API_KEY=your_api_key
 *    CLOUDINARY_API_SECRET=your_api_secret
 */

// Load environment variables if not already loaded
if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Cloudinary configuration
define('CLOUDINARY_CLOUD_NAME', $_ENV['CLOUDINARY_CLOUD_NAME'] ?? '');
define('CLOUDINARY_API_KEY', $_ENV['CLOUDINARY_API_KEY'] ?? '');
define('CLOUDINARY_API_SECRET', $_ENV['CLOUDINARY_API_SECRET'] ?? '');
define('CLOUDINARY_UPLOAD_PRESET', $_ENV['CLOUDINARY_UPLOAD_PRESET'] ?? ''); // Optional: for unsigned uploads

/**
 * Check if Cloudinary is configured
 */
function isCloudinaryConfigured(): bool {
    return !empty(CLOUDINARY_CLOUD_NAME) && !empty(CLOUDINARY_API_KEY) && !empty(CLOUDINARY_API_SECRET);
}

/**
 * Upload file to Cloudinary
 * 
 * @param string $filePath Local file path
 * @param string $folder Cloudinary folder to upload to
 * @param array $options Additional upload options
 * @return array|false Returns upload result or false on failure
 */
function uploadToCloudinary(string $filePath, string $folder = 'campaign_materials', array $options = []) {
    if (!isCloudinaryConfigured()) {
        error_log('Cloudinary not configured');
        return false;
    }
    
    if (!file_exists($filePath)) {
        error_log('File not found: ' . $filePath);
        return false;
    }
    
    $timestamp = time();
    $publicId = $folder . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '_' . $timestamp;
    
    // Determine resource type based on file extension
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $videoExtensions = ['mp4', 'webm', 'mov', 'avi', 'mkv', 'wmv', 'flv', '3gp'];
    $resourceType = in_array($extension, $videoExtensions) ? 'video' : 'auto';
    
    // Build parameters for signature
    $params = [
        'timestamp' => $timestamp,
        'folder' => $folder,
        'public_id' => $publicId,
        'resource_type' => $resourceType
    ];
    
    // Merge with additional options
    $params = array_merge($params, $options);
    
    // Generate signature
    ksort($params);
    $signatureString = '';
    foreach ($params as $key => $value) {
        if ($key !== 'file' && $key !== 'api_key' && $key !== 'resource_type') {
            $signatureString .= $key . '=' . $value . '&';
        }
    }
    $signatureString = rtrim($signatureString, '&') . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);
    
    // Prepare upload data
    $uploadUrl = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/' . $resourceType . '/upload';
    
    $postData = [
        'file' => new CURLFile($filePath),
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature,
        'folder' => $folder,
        'public_id' => $publicId
    ];
    
    // Add additional options
    foreach ($options as $key => $value) {
        if (!isset($postData[$key])) {
            $postData[$key] = $value;
        }
    }
    
    // Execute upload
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $uploadUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 2 minute timeout for large files
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log('Cloudinary upload curl error: ' . $error);
        return false;
    }
    
    $result = json_decode($response, true);
    
    if ($httpCode !== 200 || isset($result['error'])) {
        error_log('Cloudinary upload failed: ' . ($result['error']['message'] ?? $response));
        return false;
    }
    
    return $result;
}

/**
 * Delete file from Cloudinary
 * 
 * @param string $publicId The public ID of the file to delete
 * @param string $resourceType The resource type (image, video, raw)
 * @return bool
 */
function deleteFromCloudinary(string $publicId, string $resourceType = 'image'): bool {
    if (!isCloudinaryConfigured()) {
        return false;
    }
    
    $timestamp = time();
    $signatureString = 'public_id=' . $publicId . '&timestamp=' . $timestamp . CLOUDINARY_API_SECRET;
    $signature = sha1($signatureString);
    
    $url = 'https://api.cloudinary.com/v1_1/' . CLOUDINARY_CLOUD_NAME . '/' . $resourceType . '/destroy';
    
    $postData = [
        'public_id' => $publicId,
        'api_key' => CLOUDINARY_API_KEY,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    return isset($result['result']) && $result['result'] === 'ok';
}

/**
 * Get Cloudinary URL for a file
 * 
 * @param string $publicId The public ID of the file
 * @param array $transformations Optional transformations
 * @return string
 */
function getCloudinaryUrl(string $publicId, array $transformations = []): string {
    if (!CLOUDINARY_CLOUD_NAME) {
        return '';
    }
    
    $baseUrl = 'https://res.cloudinary.com/' . CLOUDINARY_CLOUD_NAME;
    
    $transformString = '';
    if (!empty($transformations)) {
        $parts = [];
        foreach ($transformations as $key => $value) {
            $parts[] = $key . '_' . $value;
        }
        $transformString = '/' . implode(',', $parts);
    }
    
    return $baseUrl . '/image/upload' . $transformString . '/' . $publicId;
}
