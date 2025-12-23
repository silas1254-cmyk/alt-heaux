<?php
// Prevent multiple inclusions - MUST BE FIRST
if (defined('ADMIN_AUTH_LOADED')) {
    return;
}
define('ADMIN_AUTH_LOADED', true);

/**
 * Admin authentication helper functions
 */

/**
 * Check if user is logged in as admin
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Redirect to login if not admin
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . SITE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * Check if user is admin (has Administrator or Owner role)
 * @return bool
 */
function isAdmin() {
    return isAdminLoggedIn() && isset($_SESSION['admin_role']) && in_array($_SESSION['admin_role'], ['Administrator', 'Owner']);
}

/**
 * Redirect to dashboard if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'admin/login.php');
        exit;
    }
}

/**
 * Keep legacy function names for backward compatibility
 */
/**
 * Keep legacy function names for backward compatibility
 */
function isSuperAdmin() {
    return isAdmin();
}

function requireSuperAdmin() {
    return requireAdmin();
}

/**
 * Validate admin credentials
 * @param string $username Admin username
 * @param string $password Admin password
 * @param mysqli $conn Database connection
 * @return array|null Admin user or null
 */
function validateAdminLogin($username, $password, $conn) {
    $query = "SELECT * FROM admin_users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $admin = $result->fetch_assoc();
    
    // Check password
    if (password_verify($password, $admin['password'])) {
        return $admin;
    }
    
    return null;
}

/**
 * Create admin user (for initial setup)
 * @param string $username Admin username
 * @param string $password Admin password
 * @param string $email Admin email
 * @param string $name Admin name (unused, kept for compatibility)
 * @param mysqli $conn Database connection
 * @param string $role Admin role (default: Administrator)
 * @param string $status Status (default: active)
 * @return bool
 */
function createAdminUser($username, $password, $email, $name, $conn, $role = 'Administrator', $status = 'active') {
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    $query = "INSERT INTO admin_users (username, password, email, role, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssss', $username, $hashed_password, $email, $role, $status);
    
    return $stmt->execute();
}
/**
 * Get all active admins
 * @param mysqli $conn Database connection
 * @return array Active admins
 */
function getApprovedAdmins($conn) {
    $query = "SELECT id, username, email, role, created_at FROM admin_users WHERE status = 'active' ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Delete an admin user
 * @param int $admin_id Admin ID to delete
 * @param mysqli $conn Database connection
 * @return bool
 */
function deleteAdmin($admin_id, $conn) {
    // Prevent deletion of the current logged-in user
    if ($_SESSION['admin_id'] == $admin_id) {
        return false; // Cannot delete yourself
    }
    
    // Check what admin is being deleted
    $check_query = "SELECT role, username FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('i', $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $admin = $result->fetch_assoc();
    
    // Prevent deletion of Yevty
    if ($admin['username'] === 'Yevty') {
        return false; // Yevty cannot be deleted
    }
    
    // Only Yevty can delete other Owners
    if ($admin['role'] === 'Owner' && $_SESSION['admin_name'] !== 'Yevty') {
        return false; // Only Yevty can delete other Owners
    }
    
    $query = "DELETE FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $admin_id);
    
    return $stmt->execute();
}

/**
 * Update admin role
 * @param int $admin_id Admin ID to update
 * @param string $new_role New role (Administrator or Owner)
 * @param mysqli $conn Database connection
 * @return bool
 */
function updateAdminRole($admin_id, $new_role, $conn) {
    // Only allow Administrator or Owner roles
    if (!in_array($new_role, ['Administrator', 'Owner'])) {
        return false;
    }
    
    $query = "UPDATE admin_users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $new_role, $admin_id);
    
    return $stmt->execute();
}
?>
