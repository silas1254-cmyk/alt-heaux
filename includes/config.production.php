<?php
/**
 * Production Configuration
 * Use this for alt-heaux.com on ifastnet hosting
 */

// Detect environment
$is_production = ($_SERVER['HTTP_HOST'] === 'alt-heaux.com' || $_SERVER['HTTP_HOST'] === 'www.alt-heaux.com');

// Production Database configuration
if ($is_production) {
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost:3306');
    if (!defined('DB_USER')) define('DB_USER', 'altheaux_yevty');
    if (!defined('DB_PASSWORD')) define('DB_PASSWORD', 'swegman123-');
    if (!defined('DB_NAME')) define('DB_NAME', 'altheaux_website');
} else {
    // Fallback to local development
    if (!defined('DB_HOST')) define('DB_HOST', 'localhost:3308');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'alt_heaux');
}

// Create connection
if (!isset($conn) || !$conn) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        error_log("Database Connection Error: " . $conn->connect_error);
        die("Database connection error. Please try again later.");
    }
    
    $conn->set_charset("utf8");
}

// Site configuration
if ($is_production) {
    if (!defined('SITE_NAME')) define('SITE_NAME', 'ALT HEAUX');
    if (!defined('SITE_URL')) define('SITE_URL', 'https://alt-heaux.com/');
    if (!defined('SITE_ROOT')) define('SITE_ROOT', '/home/altheaux/public_html/');
    if (!defined('ADMIN_PATH')) define('ADMIN_PATH', 'https://alt-heaux.com/admin/');
} else {
    if (!defined('SITE_NAME')) define('SITE_NAME', 'ALT HEAUX');
    if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/alt-heaux/');
    if (!defined('SITE_ROOT')) define('SITE_ROOT', __DIR__ . '/../');
    if (!defined('ADMIN_PATH')) define('ADMIN_PATH', 'http://localhost/alt-heaux/admin/');
}

// Error handling - disable error display in production
if ($is_production) {
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', '/home/altheaux/logs/php_errors.log');
} else {
    ini_set('display_errors', 1);
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security: Set secure session cookies in production
if ($is_production) {
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
}

// Include helper functions (load once globally)
require_once(__DIR__ . '/validation.php');
require_once(__DIR__ . '/content_helper.php');
require_once(__DIR__ . '/products_helper.php');
require_once(__DIR__ . '/admin_auth.php');
?>
