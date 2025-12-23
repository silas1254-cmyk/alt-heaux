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
        if ($action === 'edit') {
            $section_key = $_POST['section_key'] ?? '';
            $title = $_POST['title'] ?? '';
            $content = $_POST['content'] ?? '';
            $image_url = $_POST['image_url'] ?? '';
            $active = isset($_POST['active']);
            
            if (empty($section_key)) {
                $error = 'Section key is required!';
            } else {
                updateSection($section_key, $title, $content, $image_url, $active);
                $message = 'Section updated successfully!';
                // Log the update
                logWebsiteUpdate('Section', "Updated section: $section_key", "Content section modified", 'Update', $conn);
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Initialize default sections if they don't exist
$default_sections = [
    'hero' => ['title' => 'Welcome to ALT HEAUX', 'content' => 'Premium Fashion & Clothing for the Modern Individual'],
    'why-choose-us' => ['title' => 'Why Choose Us', 'content' => ''],
    'about' => ['title' => 'About ALT HEAUX', 'content' => ''],
    'testimonials' => ['title' => 'What Our Customers Say', 'content' => ''],
    'featured' => ['title' => 'Featured Collection', 'content' => ''],
];

foreach ($default_sections as $key => $data) {
    $existing = getSection($key);
    if (!$existing) {
        updateSection($key, $data['title'], $data['content'], '', true);
    }
}

// Get all sections
$query = "SELECT * FROM sections ORDER BY section_key ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$sections_result = $stmt->get_result();
$sections = $sections_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Sections - Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/k4q02syhyy66cvsk0rspwv3wc5jykgfaqs6vz8xgqcrvsczt/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <?php include('_sidebar.php'); ?>
        
        <div class="col-md-9 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1><i class="fas fa-file-alt"></i> Content Sections</h1>
                <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back</a>
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
            
            <?php foreach ($sections as $section): ?>
                <div class="card section-card mb-4">
                    <div class="card-header">
                        <i class="fas fa-edit"></i> <?php echo htmlspecialchars($section['title']); ?>
                        <span class="badge bg-<?php echo $section['active'] ? 'success' : 'secondary'; ?> float-end">
                            <?php echo $section['active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="section_key" value="<?php echo htmlspecialchars($section['section_key']); ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Section Key</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($section['section_key']); ?>" disabled>
                                <small class="text-muted">Unique identifier for this section</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="title_<?php echo $section['id']; ?>" class="form-label">Section Title</label>
                                <input type="text" class="form-control" id="title_<?php echo $section['id']; ?>" 
                                       name="title" value="<?php echo htmlspecialchars($section['title']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_<?php echo $section['id']; ?>" class="form-label">Content</label>
                                <textarea class="form-control tinymce-editor" id="content_<?php echo $section['id']; ?>" 
                                          name="content"><?php echo htmlspecialchars($section['content']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image_<?php echo $section['id']; ?>" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="image_<?php echo $section['id']; ?>" 
                                       name="image_url" value="<?php echo htmlspecialchars($section['image_url'] ?? ''); ?>"
                                       placeholder="https://example.com/image.jpg">
                                <?php if (!empty($section['image_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($section['image_url']); ?>" alt="Section Image" 
                                             style="max-width: 200px; max-height: 150px;" onerror="this.src='https://via.placeholder.com/200x150?text=No+Image'">
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="active_<?php echo $section['id']; ?>" 
                                       name="active" <?php echo $section['active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="active_<?php echo $section['id']; ?>">
                                    Active (visible on frontend)
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
tinymce.init({
    selector: '.tinymce-editor',
    plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
    toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | code | fullscreen',
    height: 300,
    menubar: true,
    relative_urls: false,
    branding: false
});
</script>
</body>
</html>
