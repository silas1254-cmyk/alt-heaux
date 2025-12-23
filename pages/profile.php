<?php
/**
 * USER PROFILE PAGE
 * 
 * Allows logged-in users to view and edit their profile information.
 * Users can change their password securely with validation.
 * Only accessible to authenticated users (redirects to login if not).
 */

require '../includes/config.php';

/**
 * AUTHENTICATION CHECK
 * Ensures user is logged in before allowing access
 * Redirects to login page if session not found
 */
if (!isUserLoggedIn()) {
    header('Location: ' . SITE_URL . 'auth/login.php');
    exit;
}

require '../includes/header.php';

/**
 * INITIALIZE USER SESSION VARIABLES
 * Get user ID and username from session data
 */
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = '';
$success = '';

/**
 * PASSWORD CHANGE FORM HANDLER
 * 
 * Validates form submission and updates password if all checks pass:
 * 1. Validates all fields are filled
 * 2. Ensures new password meets minimum length (6 chars)
 * 3. Verifies passwords match
 * 4. Verifies current password against database
 * 5. Updates password with hashing for security
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    // Sanitize and collect form inputs
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation 1: Check if all fields are provided
    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'All password fields are required.';
    } 
    // Validation 2: Check minimum password length
    elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } 
    // Validation 3: Verify new passwords match
    elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } 
    else {
        // Query database for current password hash
        $query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        // Validation 4: Verify old password is correct
        if (!$result || !password_verify($old_password, $result['password'])) {
            $error = 'Current password is incorrect.';
        } 
        else {
            // Hash new password using PHP's PASSWORD_DEFAULT algorithm (bcrypt)
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            // Update password in database
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param('si', $hashed_password, $user_id);
            
            // Set success or error message based on update result
            if ($update_stmt->execute()) {
                $success = 'Password changed successfully.';
            } else {
                $error = 'Error changing password. Please try again.';
            }
        }
    }
}

/**
 * FETCH CURRENT USER DATA
 * Retrieves full user profile from database for display
 */
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!-- PROFILE PAGE CONTAINER -->
<div class="container py-5">
    <div class="row">
        <!-- LEFT SIDEBAR: NAVIGATION MENU -->
        <!-- Links to other user pages (dashboard, orders, cart, logout) -->
        <div class="col-md-3">
            <!-- Navigation list for user account pages -->
            <div class="list-group">
                <!-- Dashboard link - view account overview -->
                <a href="<?php echo SITE_URL; ?>pages/dashboard.php" class="list-group-item list-group-item-action">Dashboard</a>
                <!-- Profile link - currently active page -->
                <a href="<?php echo SITE_URL; ?>pages/profile.php" class="list-group-item list-group-item-action active">Profile</a>
                <!-- Orders link - view purchase history -->
                <a href="<?php echo SITE_URL; ?>pages/orders.php" class="list-group-item list-group-item-action">Orders</a>
                <!-- Shopping cart link -->
                <a href="<?php echo SITE_URL; ?>pages/cart.php" class="list-group-item list-group-item-action">Shopping Cart</a>
                <!-- Logout link - end session and redirect to home -->
                <a href="<?php echo SITE_URL; ?>auth/logout.php" class="list-group-item list-group-item-action text-danger">Logout</a>
            </div>
        </div>

        <!-- RIGHT COLUMN: PROFILE CONTENT -->
        <!-- Main profile editing form -->
        <div class="col-md-9">
            <!-- PAGE HEADER -->
            <h1 class="mb-4">Edit Profile</h1>

            <!-- ERROR MESSAGE ALERT -->
            <!-- Shown when validation fails or database error occurs -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <!-- Display error message with HTML escaping for security -->
                    <?php echo htmlspecialchars($error); ?>
                    <!-- Dismiss button to close alert -->
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- SUCCESS MESSAGE ALERT -->
            <!-- Shown after successful password change -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <!-- Display success message -->
                    <?php echo htmlspecialchars($success); ?>
                    <!-- Dismiss button -->
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ACCOUNT INFORMATION CARD -->
            <!-- Shows read-only username and email -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <!-- USERNAME FIELD (READ-ONLY) -->
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <!-- Disabled input shows current username, cannot be edited -->
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    </div>
                    <!-- EMAIL FIELD (READ-ONLY) -->
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <!-- Disabled input shows current email, cannot be edited -->
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>
            </div>

            <!-- CHANGE PASSWORD CARD -->
            <!-- Form for securely updating user password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <!-- PASSWORD CHANGE FORM -->
                    <!-- Submits to same page with action="change_password" -->
                    <form method="POST" action="">
                        <!-- Hidden field to identify this form submission -->
                        <input type="hidden" name="action" value="change_password">
                        
                        <!-- CURRENT PASSWORD FIELD -->
                        <!-- User must enter current password to verify identity -->
                        <div class="mb-3">
                            <label for="oldPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="oldPassword" name="old_password" required>
                        </div>

                        <!-- NEW PASSWORD FIELD -->
                        <!-- First entry of new password (min 6 characters required) -->
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="new_password" required>
                        </div>

                        <!-- CONFIRM PASSWORD FIELD -->
                        <!-- Second entry must match new password field -->
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <!-- Submits form to password change handler in PHP -->
                        <div class="mb-3">
                            <button type="submit" class="btn btn-dark">Update Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require '../includes/footer.php'; ?>
