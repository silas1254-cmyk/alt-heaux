<?php
/**
 * Admin API for File Management
 * Handles file uploads, deletions, and metadata updates for products
 */

// Enable output buffering to prevent accidental output before JSON
ob_start();

// Suppress error display - we'll return errors as JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Catch fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        // Log to file for debugging
        $logFile = dirname(__DIR__) . '/files/api_debug.log';
        error_log('[' . date('Y-m-d H:i:s') . '] Fatal Error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'], 3, $logFile);
        
        ob_end_clean();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
    }
});

// Set error handler to catch errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'Error: ' . $errstr, 'file' => $errfile, 'line' => $errline]));
});

// Start session before including any other files
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once('../includes/config.php');
require_once('../includes/admin_auth.php');
require_once('../includes/rate_limit.php');
require_once('../includes/csrf_protection.php');

// Check rate limiting (100 requests per hour per IP)
checkRateLimit($_SESSION['admin_id'] ?? null, 100, 3600);

// Verify database connection
if (!isset($conn) || !$conn) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Check if product_files table exists
$result = $conn->query("SHOW TABLES LIKE 'product_files'");
if (!$result || $result->num_rows === 0) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'Database table product_files not found. Please run migrations.']));
}

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    ob_end_clean();
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'upload':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            handleFileUpload();
            break;

        case 'delete':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            handleFileDelete();
            break;

        case 'list':
            if ($method !== 'GET') {
                throw new Exception('Method not allowed');
            }
            handleFileList();
            break;

        case 'update_metadata':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            handleMetadataUpdate();
            break;

        default:
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
}

/**
 * Upload file for a product
 */
function handleFileUpload() {
    global $conn;

    $product_id = intval($_POST['product_id'] ?? 0);
    $display_name = trim($_POST['display_name'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $size_variant = trim($_POST['size_variant'] ?? '');
    $color_variant = trim($_POST['color_variant'] ?? '');
    $variant_description = trim($_POST['variant_description'] ?? '');

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    if (empty($display_name)) {
        throw new Exception('Display name is required');
    }

    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file selected or upload error');
    }

    // Check for required variants
    if (empty($size_variant)) {
        throw new Exception('Size variant is required');
    }

    if (empty($color_variant)) {
        throw new Exception('Color variant is required');
    }

    // Verify product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Query prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $product_id);
    if (!$stmt->execute()) {
        throw new Exception('Query execute failed: ' . $stmt->error);
    }
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('Product not found');
    }

    // Validate file
    $file = $_FILES['file'];
    $max_size = 500 * 1024 * 1024; // 500MB

    if ($file['size'] > $max_size) {
        throw new Exception('File size exceeds 500MB limit');
    }

    // Create secure file path
    $secure_path = dirname(__DIR__) . '/files/products/' . $product_id;
    if (!is_dir($secure_path)) {
        if (!mkdir($secure_path, 0755, true)) {
            throw new Exception('Failed to create product directory');
        }
    }

    // Generate secure filename
    $original_name = basename($file['name']);
    $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    
    // Block dangerous executable extensions
    $dangerous = ['exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'sh', 'app'];
    if (in_array($file_extension, $dangerous)) {
        throw new Exception('ERROR: Executable files (.exe, .bat, .cmd, etc.) are not allowed for security reasons.');
    }
    
    // Whitelist allowed extensions
    $allowed = ['zip', 'pdf', 'dmg', 'rar', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', '7z', 'iso'];
    if (!in_array($file_extension, $allowed)) {
        throw new Exception('File type not allowed. Allowed: ' . implode(', ', $allowed));
    }

    $unique_filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original_name);
    $file_path = $secure_path . '/' . $unique_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to move uploaded file. Check directory permissions.');
    }

    // Get file size
    $file_size = filesize($file_path);
    if ($file_size === false) {
        @unlink($file_path);
        throw new Exception('Failed to get file size');
    }
    $file_type = $file_extension;

    // Store only the filename in database, not the full path
    $stored_filename = $unique_filename;

    // Insert into database
    $stmt = $conn->prepare("
        INSERT INTO product_files 
        (product_id, original_filename, file_path, file_size, file_type, display_name, version, description, size_variant, color_variant, variant_description, uploaded_by, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    if (!$stmt) {
        @unlink($file_path);
        throw new Exception('Query prepare failed: ' . $conn->error);
    }

    $admin_id = $_SESSION['admin_id'];
    $size_var = !empty($size_variant) ? $size_variant : null;
    $color_var = !empty($color_variant) ? $color_variant : null;
    $var_desc = !empty($variant_description) ? $variant_description : null;
    
    $stmt->bind_param(
        'issssssssssi',
        $product_id,
        $original_name,
        $stored_filename,
        $file_size,
        $file_type,
        $display_name,
        $version,
        $description,
        $size_var,
        $color_var,
        $var_desc,
        $admin_id
    );

    if (!$stmt->execute()) {
        @unlink($file_path);
        throw new Exception('Query execute failed: ' . $stmt->error);
    }
    {
        $file_id = $conn->insert_id;
        
        $variant_label = '';
        if (!empty($size_var) || !empty($color_var)) {
            $parts = [];
            if (!empty($size_var)) $parts[] = "Size: $size_var";
            if (!empty($color_var)) $parts[] = "Color: $color_var";
            $variant_label = ' (' . implode(', ', $parts) . ')';
        }
        
        ob_end_flush();
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_id' => $file_id,
            'file_name' => $original_name,
            'display_name' => $display_name,
            'variant_info' => $variant_label,
            'file_size' => formatBytes($file_size),
            'version' => $version,
            'upload_date' => date('M d, Y H:i')
        ]);
    }
}

/**
 * Delete file
 */
function handleFileDelete() {
    global $conn;

    $file_id = intval($_POST['file_id'] ?? 0);

    if ($file_id <= 0) {
        throw new Exception('Invalid file ID');
    }

    // Get file info
    $stmt = $conn->prepare("SELECT file_path, product_id FROM product_files WHERE id = ?");
    $stmt->bind_param('i', $file_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('File not found');
    }

    $file_info = $result->fetch_assoc();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM product_files WHERE id = ?");
    $stmt->bind_param('i', $file_id);

    if ($stmt->execute()) {
        // Delete physical file
        $file_path = dirname(__DIR__) . '/files/products/' . $file_info['product_id'] . '/' . $file_info['file_path'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }

        ob_end_flush();
        echo json_encode([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
}

/**
 * List files for a product
 */
function handleFileList() {
    global $conn;

    $product_id = intval($_GET['product_id'] ?? 0);

    if ($product_id <= 0) {
        throw new Exception('Invalid product ID');
    }

    $stmt = $conn->prepare("
        SELECT 
            id,
            original_filename,
            display_name,
            file_size,
            version,
            description,
            size_variant,
            color_variant,
            is_active,
            upload_date
        FROM product_files
        WHERE product_id = ? AND is_active = 1
        ORDER BY upload_date DESC
    ");

    if (!$stmt) {
        throw new Exception('Query prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('i', $product_id);
    if (!$stmt->execute()) {
        throw new Exception('Query execute failed: ' . $stmt->error);
    }
    $result = $stmt->get_result();
    $files = [];

    while ($row = $result->fetch_assoc()) {
        $variant_label = '';
        if (!empty($row['size_variant']) || !empty($row['color_variant'])) {
            $parts = [];
            if (!empty($row['size_variant'])) $parts[] = "Size: " . $row['size_variant'];
            if (!empty($row['color_variant'])) $parts[] = "Color: " . $row['color_variant'];
            $variant_label = '(' . implode(', ', $parts) . ')';
        }
        
        $files[] = [
            'id' => $row['id'],
            'original_filename' => $row['original_filename'],
            'display_name' => $row['display_name'],
            'file_size' => formatBytes($row['file_size']),
            'version' => $row['version'],
            'description' => $row['description'],
            'size_variant' => $row['size_variant'],
            'color_variant' => $row['color_variant'],
            'variant_label' => $variant_label,
            'upload_date' => date('M d, Y', strtotime($row['upload_date']))
        ];
    }

    ob_end_flush();
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
}

/**
 * Update file metadata
 */
function handleMetadataUpdate() {
    global $conn;

    $file_id = intval($_POST['file_id'] ?? 0);
    $display_name = trim($_POST['display_name'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($file_id <= 0) {
        throw new Exception('Invalid file ID');
    }

    if (empty($display_name)) {
        throw new Exception('Display name is required');
    }

    $stmt = $conn->prepare("
        UPDATE product_files
        SET display_name = ?, version = ?, description = ?
        WHERE id = ?
    ");

    $stmt->bind_param('sssi', $display_name, $version, $description, $file_id);

    if ($stmt->execute()) {
        ob_end_flush();
        echo json_encode([
            'success' => true,
            'message' => 'File metadata updated successfully'
        ]);
    } else {
        throw new Exception('Database error: ' . $conn->error);
    }
}

/**
 * Format bytes for display
 */
if (!function_exists('formatBytes')) {
    function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
?>
