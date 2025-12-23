<?php
/**
 * Guest Cart Management
 * Handles persistent guest shopping carts using database
 */

if (defined('GUEST_CART_HELPER_LOADED')) {
    return;
}
define('GUEST_CART_HELPER_LOADED', true);

/**
 * Generate or retrieve guest session ID
 * Uses a combination of IP address and user agent for fingerprinting
 * Also creates a random component to avoid collisions
 * 
 * @return string Unique guest session ID (max 64 chars for database)
 */
function getGuestSessionId() {
    // Check if already set in session
    if (isset($_SESSION['guest_session_id'])) {
        return $_SESSION['guest_session_id'];
    }
    
    // Generate new guest session ID
    // Use fingerprinting (IP + user agent) + random component for uniqueness
    // Keep it to 64 chars max for database VARCHAR(64)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'cli';
    $fingerprint = substr(md5($ip . $ua), 0, 32);
    $random = bin2hex(random_bytes(8)); // 16 chars
    $guest_session_id = $fingerprint . '_' . $random; // 32 + 1 + 16 = 49 chars
    
    // Store in session
    $_SESSION['guest_session_id'] = $guest_session_id;
    
    return $guest_session_id;
}

/**
 * Check if a guest session exists in database
 * @param string $guest_session_id
 * @param mysqli $conn
 * @return bool
 */
function guestSessionExists($guest_session_id, $conn) {
    $query = "SELECT id FROM guest_carts WHERE guest_session_id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("guestSessionExists prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $guest_session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    return $exists;
}

/**
 * Clean up expired guest carts (older than 30 days)
 * Call this periodically to prevent database bloat
 * 
 * @param mysqli $conn
 * @return int Number of rows deleted
 */
function cleanupExpiredGuestCarts($conn) {
    $query = "DELETE FROM guest_carts WHERE expires_at < NOW()";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("cleanupExpiredGuestCarts prepare failed: " . $conn->error);
        return 0;
    }
    
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();
    
    error_log("Cleaned up $deleted expired guest carts");
    return $deleted;
}

/**
 * Migrate guest cart to user cart when they log in
 * Merges guest cart items with user's existing cart
 * 
 * @param string $guest_session_id
 * @param int $user_id
 * @param mysqli $conn
 * @return bool
 */
function migrateGuestCartToUser($guest_session_id, $user_id, $conn) {
    error_log("Migrating guest cart ($guest_session_id) to user ($user_id)");
    
    $user_id = intval($user_id);
    
    // Get all items from guest cart
    $query = "SELECT product_id, quantity, selected_color, selected_size FROM guest_carts WHERE guest_session_id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("migrateGuestCartToUser prepare failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("s", $guest_session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $migrated_count = 0;
    
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['product_id'];
        $quantity = $row['quantity'];
        $color = $row['selected_color'];
        $size = $row['selected_size'];
        
        // Check if user already has this product with same variant
        $check_query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND selected_color <=> ? AND selected_size <=> ?";
        $check_stmt = $conn->prepare($check_query);
        if (!$check_stmt) {
            error_log("migrateGuestCartToUser check prepare failed: " . $conn->error);
            continue;
        }
        
        $check_stmt->bind_param("iiss", $user_id, $product_id, $color, $size);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Product exists, add to quantity
            $existing = $check_result->fetch_assoc();
            $new_qty = $existing['quantity'] + $quantity;
            $cart_id = $existing['id'];
            
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            if ($update_stmt) {
                $update_stmt->bind_param("ii", $new_qty, $cart_id);
                $update_stmt->execute();
                $update_stmt->close();
                $migrated_count++;
                error_log("Updated existing cart item: product_id=$product_id, new_qty=$new_qty");
            }
        } else {
            // Product doesn't exist, insert new cart item
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity, selected_color, selected_size) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            if ($insert_stmt) {
                $insert_stmt->bind_param("iisss", $user_id, $product_id, $quantity, $color, $size);
                $insert_stmt->execute();
                $insert_stmt->close();
                $migrated_count++;
                error_log("Inserted new cart item from guest: product_id=$product_id, qty=$quantity");
            }
        }
        
        $check_stmt->close();
    }
    
    $stmt->close();
    
    // Delete guest cart after migration
    $delete_query = "DELETE FROM guest_carts WHERE guest_session_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    if ($delete_stmt) {
        $delete_stmt->bind_param("s", $guest_session_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        error_log("Deleted guest cart after migration: $migrated_count items moved");
    }
    
    return true;
}
