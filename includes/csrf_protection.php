<?php
/**
 * CSRF Token Helper Functions
 */

/**
 * Generate a CSRF token for the session
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token
 */
function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        generateCSRFToken();
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST/REQUEST
 * @return bool
 */
function verifyCSRFToken() {
    // Get token from POST, REQUEST, or AJAX header
    $token = $_POST['csrf_token'] ?? 
             $_REQUEST['csrf_token'] ?? 
             $_SERVER['HTTP_X_CSRF_TOKEN'] ?? 
             null;
    
    if (!$token) {
        return false;
    }
    
    // Verify token matches session
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    return true;
}

/**
 * Verify CSRF token and exit with error if invalid
 */
function requireValidCSRFToken() {
    if (!verifyCSRFToken()) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'CSRF token validation failed']));
    }
}

/**
 * Output CSRF token as hidden form field
 */
function csrfField() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(getCSRFToken()) . '">';
}

/**
 * Output CSRF token as HTML meta tag (for AJAX)
 */
function csrfMeta() {
    echo '<meta name="csrf-token" content="' . htmlspecialchars(getCSRFToken()) . '">';
}
