<?php
// Prevent multiple inclusions - MUST BE FIRST
if (defined('USER_AUTH_LOADED')) {
    return;
}
define('USER_AUTH_LOADED', true);

/**
 * User authentication helper functions
 */

/**
 * Check if user is logged in
 * @return bool
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

/**
 * Redirect to login if not logged in
 */
function requireLogin() {
    if (!isUserLoggedIn()) {
        header('Location: ' . SITE_URL . 'auth/login.php');
        exit;
    }
}

/**
 * Get user data by ID
 * @param int $user_id User ID
 * @param mysqli $conn Database connection
 * @return array|null User data or null
 */
function getUserById($user_id, $conn) {
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Update user profile
 * @param int $user_id User ID
 * @param array $data User data to update
 * @param mysqli $conn Database connection
 * @return bool Success status
 */
function updateUserProfile($user_id, $data, $conn) {
    $first_name = $data['first_name'] ?? '';
    $last_name = $data['last_name'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    $city = $data['city'] ?? '';
    $state = $data['state'] ?? '';
    $zip_code = $data['zip_code'] ?? '';

    $query = "UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssssi', $first_name, $last_name, $phone, $address, $city, $state, $zip_code, $user_id);
    
    return $stmt->execute();
}

/**
 * Update user password
 * @param int $user_id User ID
 * @param string $old_password Old password
 * @param string $new_password New password
 * @param mysqli $conn Database connection
 * @return array Status array with success and message
 */
function updateUserPassword($user_id, $old_password, $new_password, $conn) {
    // Get current password hash
    $query = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    $user = $result->fetch_assoc();
    
    // Verify old password
    if (!password_verify($old_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect.'];
    }
    
    // Hash and update new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $update_query = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($update_stmt->execute()) {
        return ['success' => true, 'message' => 'Password updated successfully.'];
    }
    
    return ['success' => false, 'message' => 'Error updating password.'];
}
?>
