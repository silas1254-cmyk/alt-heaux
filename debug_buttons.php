<?php
/**
 * Debug cart buttons to see if they're in the DOM
 */
require 'includes/config.php';
require 'includes/header.php';

$user_id = isUserLoggedIn() ? $_SESSION['user_id'] : null;
if ($user_id) {
    $cart_items = getCartItems($user_id, $conn);
} else {
    $cart_items = getGuestCart($conn);
}

echo "<h1>Cart Button Debug</h1>";
echo "<p>Total items in cart: " . count($cart_items) . "</p>";

if (empty($cart_items)) {
    echo "<p>Cart is empty. Add some items to test.</p>";
} else {
    echo "<h2>Buttons on this page:</h2>";
    echo "<table border='1'>";
    foreach ($cart_items as $item) {
        echo "<tr>";
        echo "<td>Product " . $item['product_id'] . "</td>";
        echo "<td>Qty: " . $item['quantity'] . "</td>";
        echo "<td><button type='button' class='test-btn' data-action='qty-decrease' data-product-id='" . $item['product_id'] . "' data-color='" . ($item['selected_color'] ?? '') . "' data-size='" . ($item['selected_size'] ?? '') . "'>−</button></td>";
        echo "<td><button type='button' class='test-btn' data-action='qty-increase' data-product-id='" . $item['product_id'] . "' data-color='" . ($item['selected_color'] ?? '') . "' data-size='" . ($item['selected_size'] ?? '') . "'>+</button></td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>Try clicking the + or − buttons above and check the PHP error log.</p>";
}
?>

<script>
console.log('Debug script loaded');
console.log('Looking for qty buttons...');
const qtyIncreaseButtons = document.querySelectorAll('[data-action="qty-increase"]');
const qtyDecreaseButtons = document.querySelectorAll('[data-action="qty-decrease"]');
console.log('Found ' + qtyIncreaseButtons.length + ' qty-increase buttons');
console.log('Found ' + qtyDecreaseButtons.length + ' qty-decrease buttons');

// Add direct test listener
document.querySelectorAll('.test-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        console.log('Test button clicked!', this.dataset);
    });
});
</script>

<?php require 'includes/footer.php'; ?>
