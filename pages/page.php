<?php
require '../includes/config.php';
require '../includes/header.php';

$slug = $_GET['page'] ?? 'home';

// Check for dynamic page
$page = getPageBySlug($slug, $conn);

if (!$page) {
    http_response_code(404);
    echo '<div class="container py-5"><h1>Page Not Found</h1><p>The page you\'re looking for does not exist.</p></div>';
    require '../includes/footer.php';
    exit;
}
?>

<div class="container py-5">
    <h1><?php echo htmlspecialchars($page['title']); ?></h1>
    <hr>
    <div class="row">
        <div class="col-md-8">
            <?php echo $page['content']; ?>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Page Info</h5>
                    <p class="text-muted small">Last updated: <?php echo date('M d, Y', strtotime($page['created_at'] ?? date('Y-m-d'))); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
