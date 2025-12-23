<?php
/**
 * USER ORDERS PAGE
 * 
 * Displays all orders placed by the logged-in user.
 * Shows order history with dates, totals, and statuses.
 * Only accessible to authenticated users (redirects to login if not).
 */

require '../includes/config.php';
require '../includes/header.php';

/**
 * AUTHENTICATION CHECK
 * Ensures user is logged in before allowing access
 * Redirects to login page if no session found
 */
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit;
}

/**
 * INITIALIZE USER VARIABLE
 * Gets user ID from session data
 */
$user_id = $_SESSION['user_id'];

/**
 * FETCH USER ORDERS FROM DATABASE
 * Retrieves all orders for current user, sorted by newest first
 * Prepared statement prevents SQL injection
 */
$query = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);
?>

<!-- ORDERS PAGE CONTAINER -->
<div class="container py-5">
    <div class="row">
        <!-- LEFT SIDEBAR: NAVIGATION MENU -->
        <!-- Links to other user account pages -->
        <div class="col-md-3">
            <!-- Navigation list for user pages -->
            <div class="list-group">
                <!-- Dashboard link - account overview -->
                <a href="<?php echo SITE_URL; ?>pages/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <!-- Profile link - edit account info -->
                <a href="<?php echo SITE_URL; ?>pages/profile.php" class="list-group-item list-group-item-action">Profile</a>
                <!-- Orders link - currently active page -->
                <a href="<?php echo SITE_URL; ?>pages/orders.php" class="list-group-item list-group-item-action active">Orders</a>
                <!-- Shopping cart link -->
                <a href="<?php echo SITE_URL; ?>pages/cart.php" class="list-group-item list-group-item-action">Shopping Cart</a>
                <!-- Logout link -->
                <a href="<?php echo SITE_URL; ?>auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>

        <!-- RIGHT COLUMN: ORDERS CONTENT -->
        <!-- Main orders display area -->
        <div class="col-md-9">
            <!-- PAGE HEADER -->
            <h1 class="mb-4">Your Orders</h1>

            <!-- EMPTY STATE -->
            <!-- Shown when user has not placed any orders -->
            <?php if (empty($orders)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <!-- Empty orders message -->
                        <h5 class="card-title">No Orders Yet</h5>
                        <p class="card-text text-muted">You haven't placed any orders yet. Start shopping!</p>
                        <!-- Link to shop page to encourage browsing -->
                        <a href="<?php echo SITE_URL; ?>pages/shop.php" class="btn btn-dark">Shop Now</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- ORDERS TABLE -->
                <!-- Responsive table showing all user orders -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <!-- TABLE HEADER -->
                        <thead class="table-dark">
                            <tr>
                                <!-- Order ID column -->
                                <th>Order ID</th>
                                <!-- Order date column -->
                                <th>Date</th>
                                <!-- Order total amount column -->
                                <th>Total</th>
                                <!-- Order status column (pending, completed, etc.) -->
                                <th>Status</th>
                                <!-- Action column for view/manage options -->
                                <th>Action</th>
                            </tr>
                        </thead>
                        <!-- TABLE BODY -->
                        <tbody>
                            <!-- ORDERS LOOP -->
                            <!-- Iterates through all user orders -->
                            <?php foreach ($orders as $order): ?>
                                <!-- ORDER ROW -->
                                <tr>
                                    <!-- Order ID with # prefix -->
                                    <!-- Order ID with # prefix -->
                                    <td>#<?php echo $order['id']; ?></td>
                                    <!-- Formatted order date (Month Day, Year) -->
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <!-- Order total formatted as currency -->
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <!-- Order status badge (green for completed, yellow for pending) -->
                                    <td>
                                        <span class="badge bg-<?php echo $order['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                            <!-- Display status capitalized (Completed, Pending, etc.) -->
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <!-- Action buttons for order management -->
                                    <td>
                                        <!-- View order details button (currently placeholder) -->
                                        <a href="#" class="btn btn-sm btn-outline-dark">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
