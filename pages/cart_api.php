<?php
/**
 * Cart AJAX Handler
 * Handles add/remove/update cart operations
 */

require '../includes/config.php';

header('Content-Type: application/json');

// Get input - handle both JSON and POST data
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$action = $input['action'] ?? '';
$product_id = intval($input['product_id'] ?? 0);
$quantity = intval($input['quantity'] ?? 1);
$color = empty($input['color'] ?? '') ? null : $input['color'];
$size = empty($input['size'] ?? '') ? null : $input['size'];
$direction = $input['direction'] ?? null;

// Get user ID if logged in
$user_id = isUserLoggedIn() ? $_SESSION['user_id'] : null;

$guest_id = getGuestSessionId();

$response = [
    'success' => false,
    'message' => 'Invalid request',
    'cart_count' => 0
];

switch ($action) {
    case 'add':
        if ($product_id > 0) {
            // Check product exists
            $query = "SELECT id, name FROM products WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                
                error_log("Calling addToCart with: product_id=$product_id, quantity=$quantity, user_id=$user_id, color=$color, size=$size");
                $add_result = addToCart($product_id, $quantity, $user_id, $conn, $color, $size);
                error_log("addToCart returned: " . ($add_result ? 'true' : 'false'));
                
                if ($add_result) {
                    $response['success'] = true;
                    $response['message'] = htmlspecialchars($product['name']) . ' added to cart with quantity: ' . $quantity;
                    $response['cart_count'] = getCartCount($user_id, $conn);
                } else {
                    $response['message'] = 'Error adding to cart: addToCart returned false';
                    error_log("addToCart failed for product_id=$product_id");
                }
            } else {
                $response['message'] = 'Product not found';
            }
            $stmt->close();
        }
        break;
        
    case 'remove':
        if ($product_id > 0) {
            error_log("=== REMOVE REQUEST ===");
            error_log("product_id: " . $product_id);
            error_log("user_id: " . ($user_id ?? 'null'));
            error_log("color: " . ($color ?? 'null'));
            error_log("size: " . ($size ?? 'null'));
            error_log("conn: " . ($conn ? 'yes' : 'no'));
            
            if ($color === null || $color === '') error_log("Color is null/empty");
            if ($size === null || $size === '') error_log("Size is null/empty");
            
            $remove_result = removeFromCart($product_id, $user_id, $conn, $color, $size);
            error_log("removeFromCart returned: " . ($remove_result ? 'true' : 'false'));
            
            if ($remove_result) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                $response['cart_count'] = getCartCount($user_id, $conn);
                error_log("Remove SUCCESS - response cart_count: " . $response['cart_count']);
            } else {
                error_log("Remove FAILED - removeFromCart returned false");
            }
        } else {
            error_log("Remove failed - invalid product_id: " . $product_id);
        }
        break;
        
    case 'update':
    case 'update_quantity':
        error_log("=== UPDATE QUANTITY REQUEST ===");
        error_log("product_id: " . $product_id);
        error_log("user_id: " . ($user_id ?? 'null'));
        error_log("direction: " . ($direction ?? 'null'));
        error_log("color: " . ($color ?? 'null'));
        error_log("size: " . ($size ?? 'null'));
        error_log("quantity (before direction): " . $quantity);
        
        if ($product_id > 0 && $direction) {
            // Handle direction-based updates (up/down)
            if ($direction === 'up') {
                $quantity = 1; // Increment by 1
            } elseif ($direction === 'down') {
                $quantity = -1; // Decrement by 1
            }
            
            error_log("quantity (after direction): " . $quantity);
            $update_result = updateCartQuantity($product_id, $quantity, $user_id, $conn, $color, $size);
            error_log("updateCartQuantity returned: " . ($update_result ? 'true' : 'false'));
            
            if ($update_result) {
                $response['success'] = true;
                $response['message'] = 'Cart updated';
                $response['cart_count'] = getCartCount($user_id, $conn);
                error_log("Update SUCCESS - response cart_count: " . $response['cart_count']);
            } else {
                error_log("Update FAILED");
            }
        } elseif ($product_id > 0 && $quantity > 0) {
            // Handle absolute quantity updates
            error_log("Absolute quantity update: " . $quantity);
            $update_result = updateCartQuantity($product_id, $quantity, $user_id, $conn, $color, $size);
            error_log("updateCartQuantity returned: " . ($update_result ? 'true' : 'false'));
            
            if ($update_result) {
                $response['success'] = true;
                $response['message'] = 'Cart updated';
                $response['cart_count'] = getCartCount($user_id, $conn);
                error_log("Update SUCCESS - response cart_count: " . $response['cart_count']);
            } else {
                error_log("Update FAILED");
            }
        } else {
            error_log("Update failed - invalid product_id or direction/quantity");
            error_log("product_id > 0: " . ($product_id > 0 ? 'true' : 'false'));
            error_log("direction: " . ($direction ?? 'null'));
            error_log("quantity: " . $quantity);
        }
        break;
        
    case 'delete':
        // Alias for 'remove'
        if ($product_id > 0) {
            $remove_result = removeFromCart($product_id, $user_id, $conn, $color, $size);
            if ($remove_result) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                $response['cart_count'] = getCartCount($user_id, $conn);
            }
        }
        break;
        
    case 'get_count':
        $response['success'] = true;
        $response['cart_count'] = getCartCount($user_id, $conn);
        break;
        
    case 'get_items':
        $response['success'] = true;
        $response['items'] = [];
        $response['total'] = 0;
        
        if ($user_id) {
            $items = getCartItems($user_id, $conn);
            foreach ($items as $item) {
                $response['items'][] = [
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => floatval($item['price']),
                    'quantity' => intval($item['quantity']),
                    'image_url' => $item['image_url'] ?? ''
                ];
                $response['total'] += floatval($item['price']) * intval($item['quantity']);
            }
        } else {
            $cart = getGuestCart($conn);
            foreach ($cart as $item) {
                $query = "SELECT name, price, image_url FROM products WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $item['product_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $response['items'][] = [
                        'product_id' => $item['product_id'],
                        'name' => $row['name'],
                        'price' => floatval($row['price']),
                        'quantity' => intval($item['quantity']),
                        'image_url' => $row['image_url'] ?? ''
                    ];
                    $response['total'] += floatval($row['price']) * intval($item['quantity']);
                }
                $stmt->close();
            }
        }
        break;
}

echo json_encode($response);
?>
