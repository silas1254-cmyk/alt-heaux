<?php
require '../includes/config.php';
requireAdmin();

// Get statistics
$product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$order_count = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as total FROM orders")->fetch_assoc()['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/admin.css">
</head>
<body>
    <div class="wrapper">
        <?php include('_sidebar.php'); ?>

        <div class="main-content">
            <div class="page-header">
                <div>
                    <h1>Dashboard</h1>
                    <small>Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></small>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <h6>Total Products</h6>
                        <h3><?php echo $product_count; ?></h3>
                        <small>Products in inventory</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card blue">
                        <h6>Total Users</h6>
                        <h3><?php echo $user_count; ?></h3>
                        <small>Registered customers</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card green">
                        <h6>Total Orders</h6>
                        <h3><?php echo $order_count; ?></h3>
                        <small>Customer orders</small>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card orange">
                        <h6>Total Revenue</h6>
                        <h3>$<?php echo number_format($total_revenue, 2); ?></h3>
                        <small>Sales revenue</small>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Store Management</h5>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo SITE_URL; ?>admin/products.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-plus"></i> Add New Product
                            </a>
                            <a href="<?php echo SITE_URL; ?>admin/products.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-box"></i> Manage Products
                            </a>
                            <a href="<?php echo SITE_URL; ?>admin/categories.php" class="btn btn-outline-secondary btn-sm d-block">
                                <i class="bi bi-tags"></i> Categories
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Content & Site Management</h5>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo SITE_URL; ?>admin/settings.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-gear"></i> Site Settings
                            </a>
                            <a href="<?php echo SITE_URL; ?>admin/menus.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-list"></i> Navigation Menu
                            </a>
                            <a href="<?php echo SITE_URL; ?>admin/sliders.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-images"></i> Hero Sliders
                            </a>
                            <a href="<?php echo SITE_URL; ?>admin/sections.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                <i class="bi bi-file-text"></i> Content Sections
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Analytics</h5>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo SITE_URL; ?>admin/sales_dashboard.php" class="btn btn-outline-secondary btn-sm d-block">
                                <i class="bi bi-graph-up"></i> Sales Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Admin Settings</h5>
                        </div>
                        <div class="card-body">
                            <?php if (isAdmin()): ?>
                                <a href="<?php echo SITE_URL; ?>admin/manage_admins.php" class="btn btn-outline-secondary btn-sm d-block mb-2">
                                    <i class="bi bi-people"></i> Manage Admin Users
                                </a>
                                <a href="<?php echo SITE_URL; ?>admin/updates.php" class="btn btn-outline-secondary btn-sm d-block">
                                    <i class="bi bi-clock-history"></i> Audit Log
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Updates Widget -->
            <?php if (isAdmin()): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recent_updates = getRecentUpdates(8, $conn);
                        if (empty($recent_updates)):
                        ?>
                            <p class="text-muted mb-0">No updates recorded yet. Updates will be tracked as you make changes to the website.</p>
                        <?php else: ?>
                            <div class="timeline">
                                <?php foreach ($recent_updates as $update): ?>
                                    <div class="d-flex mb-3 pb-3 border-bottom">
                                        <div class="flex-shrink-0">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($update['category']); ?></span>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($update['title']); ?></h6>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar"></i> <?php echo date('M d, Y @ H:i', strtotime($update['created_at'])); ?>
                                                <?php if (!empty($update['admin_name'])): ?>
                                                    | <i class="bi bi-person"></i> <?php echo htmlspecialchars($update['admin_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="<?php echo SITE_URL; ?>admin/updates.php" class="btn btn-sm btn-outline-secondary">
                                    View Full Audit Log →
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>js/admin.js"></script>
</body>
</html>
