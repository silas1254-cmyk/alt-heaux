<?php
/**
 * Shopping Cart Management Functions
 * Handles cart operations with database persistence for logged-in users
 * Handles guest carts using database instead of session
 */

if (defined('CART_HELPER_LOADED')) {
    return;
}
define('CART_HELPER_LOADED', true);

// Include guest cart helper
require_once __DIR__ . '/guest_cart_helper.php';

/**
 * Get user's cart items with product details
 */
function getCartItems($user_id, $conn) {
    $query = "SELECT 
                c.id, 
                c.product_id, 
                c.quantity as cart_qty, 
                c.selected_color, 
                c.selected_size, 
                p.name, 
                p.price, 
                p.image_url
              FROM cart c
              JOIN products p ON c.product_id = p.id
              WHERE c.user_id = ? AND c.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Normalize the key back to 'quantity' for backwards compatibility
        $row['quantity'] = $row['cart_qty'];
        unset($row['cart_qty']);
        $items[] = $row;
    }
    $stmt->close();
    return $items;
}

/**
 * Get guest cart from database
 * Fetches all cart items for a guest user identified by session ID
 * 
 * @param mysqli $conn Database connection (required for guest carts)
 * @return array Array of cart items with product details
 */
function getGuestCart($conn = null) {
    // If no database connection provided, can't fetch guest cart
    if (!$conn) {
        return [];
    }
    
    $guest_session_id = getGuestSessionId();
    
    $query = "SELECT 
                gc.id,
                gc.product_id,
                gc.quantity,
                gc.selected_color,
                gc.selected_size,
                p.name,
                p.price,
                p.image_url
              FROM guest_carts gc
              JOIN products p ON gc.product_id = p.id
              WHERE gc.guest_session_id = ? AND gc.expires_at > NOW()
              ORDER BY gc.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        error_log("getGuestCart prepare failed: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("s", $guest_session_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        // Normalize for compatibility with logged-in cart
        $row['quantity'] = intval($row['quantity']);
        $items[] = $row;
    }
    
    $stmt->close();
    return $items;
}

/**
 * Add item to cart (with support for color/size variants)
 */
function addToCart($product_id, $quantity, $user_id = null, $conn = null, $color = null, $size = null) {
    // Validate input
    $product_id = intval($product_id);
    $quantity = intval($quantity);
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Normalize empty strings to null
    $color = empty($color) ? null : $color;
    $size = empty($size) ? null : $size;
    
    $success = false;
    
    // If logged in user
    if ($user_id && $conn) {
        $user_id = intval($user_id);
        
        // Check if product with same variant already in cart
        $query = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND selected_color <=> ? AND selected_size <=> ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $user_id, $product_id, $color, $size);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update quantity
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;
            $cart_id = $row['id'];
            
            $update_query = "UPDATE cart SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("ii", $new_quantity, $cart_id);
            $success = $update_stmt->execute();
            $update_stmt->close();
        } else {
            // Insert new item with variant
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity, selected_color, selected_size) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("iisss", $user_id, $product_id, $quantity, $color, $size);
            $success = $insert_stmt->execute();
            $insert_stmt->close();
        }
        $stmt->close();
        return $success;
    } else if ($conn) {
        // Guest cart in database
        $guest_session_id = getGuestSessionId();
        
        // Check if product with same variant already in guest cart
        $query = "SELECT id, quantity FROM guest_carts WHERE guest_session_id = ? AND product_id = ? AND selected_color <=> ? AND selected_size <=> ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("addToCart (guest) prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("siss", $guest_session_id, $product_id, $color, $size);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update quantity
            $row = $result->fetch_assoc();
            $new_quantity = $row['quantity'] + $quantity;
            $cart_id = $row['id'];
            
            $update_query = "UPDATE guest_carts SET quantity = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            if ($update_stmt) {
                $update_stmt->bind_param("ii", $new_quantity, $cart_id);
                $success = $update_stmt->execute();
                $update_stmt->close();
            }
        } else {
            // Insert new item
            $insert_query = "INSERT INTO guest_carts (guest_session_id, product_id, quantity, selected_color, selected_size, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))";
            $insert_stmt = $conn->prepare($insert_query);
            if ($insert_stmt) {
                $insert_stmt->bind_param("siiss", $guest_session_id, $product_id, $quantity, $color, $size);
                $success = $insert_stmt->execute();
                $insert_stmt->close();
            }
        }
        $stmt->close();
        return $success;
    } else {
        // No database connection for guest cart
        error_log("addToCart: Guest user but no database connection provided");
        return false;
    }
}

/**
 * Remove item from cart
 */
function removeFromCart($product_id, $user_id = null, $conn = null, $color = null, $size = null) {
    $product_id = intval($product_id);
    
    // Normalize empty strings to null
    $color = empty($color) ? null : $color;
    $size = empty($size) ? null : $size;
    
    if ($user_id && $conn) {
        $user_id = intval($user_id);
        
        // Build dynamic query that properly handles NULL values
        // Also check for empty strings since old data might have '' instead of NULL
        $conditions = [
            "user_id = ?",
            "product_id = ?"
        ];
        $params = [$user_id, $product_id];
        $types = "ii";
        
        if ($color === null) {
            $conditions[] = "(selected_color IS NULL OR selected_color = '')";
        } else {
            $conditions[] = "selected_color = ?";
            $params[] = $color;
            $types .= "s";
        }
        
        if ($size === null) {
            $conditions[] = "(selected_size IS NULL OR selected_size = '')";
        } else {
            $conditions[] = "selected_size = ?";
            $params[] = $size;
            $types .= "s";
        }
        
        $query = "DELETE FROM cart WHERE " . implode(" AND ", $conditions);
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }
        
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } else if ($conn) {
        // Remove from guest cart database
        error_log("removeFromCart: Removing from guest cart database");
        $guest_session_id = getGuestSessionId();
        
        // Build dynamic conditions for NULL handling
        $conditions = [
            "guest_session_id = ?",
            "product_id = ?"
        ];
        $params = [$guest_session_id, $product_id];
        $types = "si";
        
        if ($color === null) {
            $conditions[] = "(selected_color IS NULL)";
        } else {
            $conditions[] = "selected_color = ?";
            $params[] = $color;
            $types .= "s";
        }
        
        if ($size === null) {
            $conditions[] = "(selected_size IS NULL)";
        } else {
            $conditions[] = "selected_size = ?";
            $params[] = $size;
            $types .= "s";
        }
        
        $query = "DELETE FROM guest_carts WHERE " . implode(" AND ", $conditions);
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("removeFromCart guest prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        error_log("removeFromCart guest: deleted " . $stmt->affected_rows . " rows");
        $stmt->close();
        return $success;
    } else {
        error_log("removeFromCart: No database connection for guest cart");
        return false;
    }
}

/**
 * Update cart item quantity
 */
function updateCartQuantity($product_id, $quantity, $user_id = null, $conn = null, $color = null, $size = null) {
    $product_id = intval($product_id);
    $quantity = intval($quantity);
    
    // Normalize empty strings to null
    $color = empty($color) ? null : $color;
    $size = empty($size) ? null : $size;
    
    error_log("updateCartQuantity: product_id=$product_id, quantity=$quantity, user_id=$user_id, color=$color, size=$size");
    
    // If relative update (up/down), fetch current quantity first
    if ($quantity === 1 || $quantity === -1) {
        if ($user_id && $conn) {
            $user_id = intval($user_id);
            
            // Build query to get current quantity
            $conditions = ["user_id = ?", "product_id = ?"];
            $params = [$user_id, $product_id];
            $types = "ii";
            
            if ($color === null) {
                $conditions[] = "(selected_color IS NULL OR selected_color = '')";
            } else {
                $conditions[] = "selected_color = ?";
                $params[] = $color;
                $types .= "s";
            }
            
            if ($size === null) {
                $conditions[] = "(selected_size IS NULL OR selected_size = '')";
            } else {
                $conditions[] = "selected_size = ?";
                $params[] = $size;
                $types .= "s";
            }
            
            $query = "SELECT quantity FROM cart WHERE " . implode(" AND ", $conditions);
            $stmt = $conn->prepare($query);
            if (!$stmt) return false;
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $current_qty = intval($row['quantity']);
                $quantity = $current_qty + $quantity; // Add relative change
            } else {
                return false; // Item not in cart
            }
            $stmt->close();
        } else {
            // Guest cart
            $cart = getGuestCart($conn);
            $current_qty = 0;
            foreach ($cart as $item) {
                if ($item['product_id'] == $product_id && 
                    ($color === null || $item['selected_color'] === $color) &&
                    ($size === null || $item['selected_size'] === $size)) {
                    $current_qty = intval($item['quantity']);
                    break;
                }
            }
            $quantity = $current_qty + $quantity;
        }
    }
    
    if ($quantity < 1) {
        return removeFromCart($product_id, $user_id, $conn, $color, $size);
    }
    
    if ($user_id && $conn) {
        $user_id = intval($user_id);
        
        // Build dynamic query that properly handles NULL values
        // Also check for empty strings since old data might have '' instead of NULL
        $conditions = [
            "user_id = ?",
            "product_id = ?"
        ];
        $params = [$quantity, $user_id, $product_id];
        $types = "iii";
        
        if ($color === null) {
            $conditions[] = "(selected_color IS NULL OR selected_color = '')";
        } else {
            $conditions[] = "selected_color = ?";
            $params[] = $color;
            $types .= "s";
        }
        
        if ($size === null) {
            $conditions[] = "(selected_size IS NULL OR selected_size = '')";
        } else {
            $conditions[] = "selected_size = ?";
            $params[] = $size;
            $types .= "s";
        }
        
        $query = "UPDATE cart SET quantity = ? WHERE " . implode(" AND ", $conditions);
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } else if ($conn) {
        // Update guest cart in database
        error_log("updateCartQuantity: Updating guest cart database, quantity=$quantity");
        $guest_session_id = getGuestSessionId();
        
        // Build dynamic conditions for NULL handling
        $conditions = [
            "guest_session_id = ?",
            "product_id = ?"
        ];
        $params = [$quantity, $guest_session_id, $product_id];
        $types = "isi";
        
        if ($color === null) {
            $conditions[] = "(selected_color IS NULL)";
        } else {
            $conditions[] = "selected_color = ?";
            $params[] = $color;
            $types .= "s";
        }
        
        if ($size === null) {
            $conditions[] = "(selected_size IS NULL)";
        } else {
            $conditions[] = "selected_size = ?";
            $params[] = $size;
            $types .= "s";
        }
        
        $query = "UPDATE guest_carts SET quantity = ? WHERE " . implode(" AND ", $conditions);
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("updateCartQuantity guest prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param($types, ...$params);
        $success = $stmt->execute();
        error_log("updateCartQuantity guest: updated " . $stmt->affected_rows . " rows");
        $stmt->close();
        return $success;
    } else {
        error_log("updateCartQuantity: No database connection for guest cart");
        return false;
    }
}

/**
 * Clear entire cart
 */
function clearCart($user_id = null, $conn = null) {
    if ($user_id && $conn) {
        $user_id = intval($user_id);
        $query = "DELETE FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } else if ($conn) {
        // Clear guest cart from database
        $guest_session_id = getGuestSessionId();
        $query = "DELETE FROM guest_carts WHERE guest_session_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("clearCart guest prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("s", $guest_session_id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    } else {
        error_log("clearCart: No database connection");
        return false;
    }
}

/**
 * Get cart count (number of items)
 */
function getCartCount($user_id = null, $conn = null) {
    if ($user_id && $conn) {
        $user_id = intval($user_id);
        $query = "SELECT SUM(quantity) as count FROM cart WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return intval($row['count'] ?? 0);
    } else if ($conn) {
        // Guest cart count from database
        $guest_session_id = getGuestSessionId();
        $query = "SELECT SUM(quantity) as count FROM guest_carts WHERE guest_session_id = ? AND expires_at > NOW()";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            error_log("getCartCount guest prepare failed: " . $conn->error);
            return 0;
        }
        
        $stmt->bind_param("s", $guest_session_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $count = intval($row['count'] ?? 0);
        return $count;
    } else {
        error_log("getCartCount: No database connection");
        return 0;
    }
}

/**
 * Calculate cart totals
 */
function calculateCartTotals($items) {
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    // No shipping for digital products
    $total = $subtotal;
    
    return [
        'subtotal' => round($subtotal, 2),
        'total' => round($total, 2),
        'item_count' => count($items)
    ];
}
?>
