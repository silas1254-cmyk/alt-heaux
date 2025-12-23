<?php
require '../includes/config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_product') {
    $product_id = intval($_GET['id'] ?? 0);
    
    if ($product_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    $product = getProductDetails($product_id, $conn);
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
