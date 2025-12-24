<?php
session_status() === PHP_SESSION_ACTIVE || session_start();
require_once('../includes/config.php');
require_once('../includes/admin_auth.php');
require_once('../includes/content_helper.php');

requireAdmin();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $image_url = $_POST['image_url'] ?? '';
            $button_text = $_POST['button_text'] ?? '';
            $button_url = $_POST['button_url'] ?? '';
            $position = (int)($_POST['position'] ?? 0);
            
            if (empty($title) || empty($image_url)) {
                $error = 'Title and Image URL are required!';
            } else {
                createSlider($title, $description, $image_url, $button_text, $button_url, $position);
                $message = 'Slider created successfully!';
                // Log the update
                logWebsiteUpdate('Slider', "Created slider: $title", "New hero slider added", 'Create', $conn);
            }
        } elseif ($action === 'edit') {
            $id = (int)$_POST['id'];
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $image_url = $_POST['image_url'] ?? '';
            $button_text = $_POST['button_text'] ?? '';
            $button_url = $_POST['button_url'] ?? '';
            $position = (int)($_POST['position'] ?? 0);
            $active = isset($_POST['active']);
            
            if (empty($title) || empty($image_url)) {
                $error = 'Title and Image URL are required!';
            } else {
                updateSlider($id, $title, $description, $image_url, $button_text, $button_url, $position, $active);
                $message = 'Slider updated successfully!';
                // Log the update
                logWebsiteUpdate('Slider', "Updated slider: $title", "Slider content modified", 'Update', $conn);
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            deleteSlider($id);
            $message = 'Slider deleted successfully!';
            // Log the update
            logWebsiteUpdate('Slider', "Deleted slider", "Hero slider removed", 'Delete', $conn);
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

$sliders = getAllSliders();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Sliders - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="wrapper">
        <?php include('_sidebar.php'); ?>
        <div class="main-content">
            <div class="page-header">
                <h1><i class="fas fa-images"></i> Hero Sliders</h1>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Add New Slider -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-plus"></i> Add New Slider</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position</label>
                                <input type="number" class="form-control" id="position" name="position" value="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL <span class="text-danger">*</span></label>
                            <input type="url" class="form-control" id="image_url" name="image_url" placeholder="https://example.com/image.jpg" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="button_text" class="form-label">Button Text</label>
                                <input type="text" class="form-control" id="button_text" name="button_text" placeholder="Shop Now">
                            </div>
                            <div class="col-md-6">
                                <label for="button_url" class="form-label">Button URL</label>
                                <input type="text" class="form-control" id="button_url" name="button_url" placeholder="/shop">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> Add Slider
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Current Sliders -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list"></i> Current Sliders</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($sliders)): ?>
                        <p class="text-muted"><i class="fas fa-info-circle"></i> No sliders yet. Add one above!</p>
                    <?php else: ?>
                        <?php foreach ($sliders as $slider): ?>
                            <div class="slider-item">
                                <div class="row">
                                    <div class="col-md-3">
                                        <img src="<?php echo htmlspecialchars($slider['image_url']); ?>" alt="Slider" class="slider-image" onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                                    </div>
                                    <div class="col-md-6">
                                        <h5><?php echo htmlspecialchars($slider['title']); ?></h5>
                                        <p class="text-muted"><?php echo htmlspecialchars(substr($slider['description'] ?? '', 0, 100)); ?>...</p>
                                        <div class="mb-2">
                                            <span class="badge bg-<?php echo $slider['active'] ? 'success' : 'secondary'; ?>">
                                                <?php echo $slider['active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                            <span class="badge bg-info">Position: <?php echo $slider['position']; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-md-3 text-end">
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $slider['id']; ?>">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm('Delete this slider?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal<?php echo $slider['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Slider</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="edit">
                                                <input type="hidden" name="id" value="<?php echo $slider['id']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" class="form-control" name="title" 
                                                           value="<?php echo htmlspecialchars($slider['title']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($slider['description'] ?? ''); ?></textarea>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Image URL</label>
                                                    <input type="url" class="form-control" name="image_url" 
                                                           value="<?php echo htmlspecialchars($slider['image_url']); ?>" required>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Button Text</label>
                                                    <input type="text" class="form-control" name="button_text" 
                                                           value="<?php echo htmlspecialchars($slider['button_text'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Button URL</label>
                                                    <input type="text" class="form-control" name="button_url" 
                                                           value="<?php echo htmlspecialchars($slider['button_url'] ?? ''); ?>">
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Position</label>
                                                    <input type="number" class="form-control" name="position" 
                                                           value="<?php echo $slider['position']; ?>">
                                                </div>
                                                
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" name="active" 
                                                           <?php echo $slider['active'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label">Active</label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
