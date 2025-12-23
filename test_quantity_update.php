<?php
/**
 * Test cart quantity update functionality
 */

require 'includes/config.php';

// Test data
$test_product_id = 13;
$test_quantity_up = 1;
$test_quantity_down = -1;

echo "<h1>Cart Quantity Update Test</h1>";

// First, make sure there's something in the cart
echo "<h2>Step 1: Add item to cart</h2>";

// Add to cart
$user_id = isUserLoggedIn() ? $_SESSION['user_id'] : null;
$result = addToCart($test_product_id, 1, $user_id, $conn, 'red', 'Small');
echo "addToCart result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";

// Get cart before update
if ($user_id) {
    $items_before = getCartItems($user_id, $conn);
} else {
    $items_before = getGuestCart($conn);
}

echo "<h2>Step 2: Check cart before update</h2>";
echo "Items in cart: " . count($items_before) . "<br>";
foreach ($items_before as $item) {
    if ($item['product_id'] == $test_product_id) {
        echo "Product $test_product_id quantity: " . $item['quantity'] . "<br>";
    }
}

// Update quantity UP
echo "<h2>Step 3: Update quantity UP</h2>";
$update_result = updateCartQuantity($test_product_id, $test_quantity_up, $user_id, $conn, 'red', 'Small');
echo "updateCartQuantity result: " . ($update_result ? 'SUCCESS' : 'FAILED') . "<br>";

// Get cart after update
if ($user_id) {
    $items_after = getCartItems($user_id, $conn);
} else {
    $items_after = getGuestCart($conn);
}

echo "<h2>Step 4: Check cart after update</h2>";
foreach ($items_after as $item) {
    if ($item['product_id'] == $test_product_id) {
        echo "Product $test_product_id quantity: " . $item['quantity'] . "<br>";
    }
}

// Update quantity DOWN
echo "<h2>Step 5: Update quantity DOWN</h2>";
$update_result_down = updateCartQuantity($test_product_id, $test_quantity_down, $user_id, $conn, 'red', 'Small');
echo "updateCartQuantity result: " . ($update_result_down ? 'SUCCESS' : 'FAILED') . "<br>";

// Get cart after down update
if ($user_id) {
    $items_after_down = getCartItems($user_id, $conn);
} else {
    $items_after_down = getGuestCart($conn);
}

echo "<h2>Step 6: Check cart after down update</h2>";
foreach ($items_after_down as $item) {
    if ($item['product_id'] == $test_product_id) {
        echo "Product $test_product_id quantity: " . $item['quantity'] . "<br>";
    }
}

echo "<hr>";
echo "<a href='pages/cart.php'>View Cart</a>";
?>
