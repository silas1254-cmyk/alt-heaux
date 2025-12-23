<?php
require 'includes/config.php';

$user_id = isUserLoggedIn() ? $_SESSION['user_id'] : null;

if ($user_id) {
    $cart_items = getCartItems($user_id, $conn);
    echo "Logged in user ID: $user_id<br>";
} else {
    $cart_items = getGuestCart($conn);
    echo "Guest user<br>";
}

echo "Cart items: " . count($cart_items) . "<br>";
foreach ($cart_items as $item) {
    echo "- Product ID: " . $item['product_id'] . ", Qty: " . $item['quantity'] . ", Color: " . ($item['selected_color'] ?? 'N/A') . ", Size: " . ($item['selected_size'] ?? 'N/A') . "<br>";
}
?>
