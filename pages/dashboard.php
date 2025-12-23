<?php
require '../includes/config.php';

// Check if user is logged in
if (!isUserLoggedIn()) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit;
}

require '../includes/header.php';

// Get user data
$user_id = $_SESSION['user_id'];
$query = "SELECT id, username, email FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit;
}

$username = $_SESSION['username'];
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                            <i class="bi bi-person" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h5><?php echo htmlspecialchars($username); ?></h5>
                    <p class="text-muted small">Verified Account</p>
                    <a href="<?php echo SITE_URL; ?>auth/logout.php" class="btn btn-outline-dark btn-sm w-100">Logout</a>
                </div>
            </div>

            <div class="list-group">
                <a href="<?php echo SITE_URL; ?>pages/dashboard.php" class="list-group-item list-group-item-action active">Dashboard</a>
                <a href="<?php echo SITE_URL; ?>pages/profile.php" class="list-group-item list-group-item-action">Profile</a>
                <a href="<?php echo SITE_URL; ?>pages/orders.php" class="list-group-item list-group-item-action">Orders</a>
                <a href="<?php echo SITE_URL; ?>pages/cart.php" class="list-group-item list-group-item-action">Shopping Cart</a>
            </div>
        </div>

        <div class="col-md-9">
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Welcome!</strong> You have successfully logged in.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <h1 class="mb-4">Dashboard</h1>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Account Status</h6>
                            <h3>Active</h3>
                            <p class="text-success small">
                                <i class="bi bi-check-circle"></i> Account verified
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title text-muted">Total Orders</h6>
                            <h3>0</h3>
                            <p class="text-muted small">View your order history</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Username</p>
                            <p class="fw-bold"><?php echo htmlspecialchars($username); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Email</p>
                            <p class="fw-bold"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">First Name</p>
                            <p class="fw-bold"><?php echo htmlspecialchars($user['first_name'] ?? 'Not set'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Last Name</p>
                            <p class="fw-bold"><?php echo htmlspecialchars($user['last_name'] ?? 'Not set'); ?></p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Email</p>
                            <p class="fw-bold"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                    <a href="<?php echo SITE_URL; ?>pages/profile.php" class="btn btn-dark">Edit Profile</a>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo SITE_URL; ?>pages/shop.php" class="btn btn-dark me-2">Continue Shopping</a>
                    <a href="<?php echo SITE_URL; ?>pages/profile.php" class="btn btn-outline-dark">Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
