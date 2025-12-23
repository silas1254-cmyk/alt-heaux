<?php
require '../includes/config.php';

requireAdmin();

$message = '';
$error = '';

// Handle delete action (only for Owners)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['admin_role'] === 'Owner') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_log') {
        $update_id = intval($_POST['update_id'] ?? 0);
        
        if (!empty($update_id)) {
            if (deleteWebsiteUpdate($update_id, $conn)) {
                $message = 'Audit log entry has been deleted.';
                logWebsiteUpdate('Audit Log', 'Deleted audit log entry', 'Log entry removed from audit trail', 'Delete', $conn);
            } else {
                $error = 'Error deleting audit log entry.';
            }
        }
    }
}

$page = intval($_GET['page'] ?? 1);
$limit = 25;
$offset = ($page - 1) * $limit;
$category_filter = $_GET['category'] ?? '';

// Get categories for filter
$all_categories = getUpdateCategories($conn);

// Get updates
if (!empty($category_filter)) {
    $updates = getUpdatesByCategory($category_filter, $limit, $conn);
    $total = count($updates);
} else {
    $updates = getWebsiteUpdates($limit, $offset, $conn);
    $total = getTotalUpdatesCount($conn);
}

$total_pages = ceil($total / $limit);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include('_sidebar.php'); ?>

        <!-- Main Content -->
        <div class="col-md-9 main-content">
            <div class="page-header">
                <h1><i class="fas fa-history"></i> Audit Log</h1>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filter by Category -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filter by Category</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="updates.php" class="btn btn-sm <?php echo empty($category_filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                            All Updates
                        </a>
                        <?php foreach ($all_categories as $cat): ?>
                            <a href="updates.php?category=<?php echo urlencode($cat['category']); ?>" 
                               class="btn btn-sm <?php echo $category_filter === $cat['category'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo htmlspecialchars($cat['category']); ?> (<?php echo $cat['count']; ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Updates List -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Update History
                        <span class="badge bg-light text-dark float-end"><?php echo $total; ?> total</span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($updates)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No updates found.
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($updates as $update): ?>
                                <div class="timeline-item mb-4 pb-4 border-bottom" style="border-bottom: 1px solid #e9ecef;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="mb-2">
                                                <span class="badge bg-info"><?php echo htmlspecialchars($update['category']); ?></span>
                                                <span class="badge bg-<?php 
                                                    echo match($update['update_type']) {
                                                        'Create' => 'success',
                                                        'Delete' => 'danger',
                                                        'Update' => 'warning',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo htmlspecialchars($update['update_type']); ?></span>
                                            </div>
                                            <h5 class="mb-2"><?php echo htmlspecialchars($update['title']); ?></h5>
                                            <p class="text-muted mb-2"><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
                                            <small class="text-secondary">
                                                <i class="fas fa-calendar"></i> 
                                                <?php echo date('M d, Y @ H:i', strtotime($update['created_at'])); ?>
                                                <?php if (!empty($update['admin_name'])): ?>
                                                    | <i class="fas fa-user"></i> <?php echo htmlspecialchars($update['admin_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <?php if ($_SESSION['admin_role'] === 'Owner'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="update_id" value="<?php echo $update['id']; ?>">
                                                <button type="submit" name="action" value="delete_log" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this audit log entry?');" title="Delete log entry">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if (!empty($category_filter) === false && $total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="updates.php?page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="updates.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="updates.php?page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="row mt-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-file fa-3x text-primary mb-3"></i>
                            <h5>Total Updates</h5>
                            <h3 class="text-primary"><?php echo $total; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-folder fa-3x text-info mb-3"></i>
                            <h5>Categories</h5>
                            <h3 class="text-info"><?php echo count($all_categories); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-success mb-3"></i>
                            <h5>Latest Update</h5>
                            <small class="text-success">
                                <?php 
                                    $latest = getRecentUpdates(1, $conn);
                                    if (!empty($latest)) {
                                        echo date('M d, Y', strtotime($latest[0]['created_at']));
                                    } else {
                                        echo 'No updates yet';
                                    }
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
