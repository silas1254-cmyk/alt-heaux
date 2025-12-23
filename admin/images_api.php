<?php
/**
 * Admin Images API Handler
 * Handles image management operations via AJAX
 */

require '../includes/config.php';
requireAdmin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'delete_image') {
    $image_id = intval($_POST['image_id'] ?? 0);
    
    if ($image_id > 0) {
        // Get image path
        $query = "SELECT image_path FROM product_images WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $image_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $image = $result->fetch_assoc();
        
        if ($image) {
            // Delete file
            $file_path = SITE_ROOT . $image['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database
            if (deleteProductImage($image_id, $conn)) {
                echo json_encode(['success' => true, 'message' => 'Image deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error deleting image']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Image not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
