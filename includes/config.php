<?php
// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost:3308');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'alt_heaux');

// Create connection
if (!isset($conn) || !$conn) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
}

// Site configuration
if (!defined('SITE_NAME')) define('SITE_NAME', 'ALT HEAUX');
if (!defined('SITE_URL')) define('SITE_URL', 'http://localhost/alt-heaux/');
if (!defined('SITE_ROOT')) define('SITE_ROOT', __DIR__ . '/../');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include helper functions (load once globally)
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/user_auth.php';
require_once __DIR__ . '/content_helper.php';
require_once __DIR__ . '/page_builder_helper.php';
require_once __DIR__ . '/products_helper.php';
require_once __DIR__ . '/cart_helper.php';
require_once __DIR__ . '/updates_helper.php';
require_once __DIR__ . '/product_images_helper.php';
require_once __DIR__ . '/backup_helper.php';
?>
