<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection only (avoid loading full config with redirects)
define('DB_HOST', 'localhost:3308');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'alt_heaux');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Define constants
define('SITE_NAME', 'ALT HEAUX');
define('SITE_URL', 'http://localhost/alt-heaux/');

// Load guest cart helper for migration
require_once __DIR__ . '/../includes/guest_cart_helper.php';
require_once __DIR__ . '/../includes/cart_helper.php';

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username and password are required.';
    } else {
        // Query user by username
        $query = "SELECT id, username, email, password FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Update last login
                $update_query = "UPDATE users SET last_login = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('i', $user['id']);
                $update_stmt->execute();

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                
                // Migrate guest cart to user cart if guest had items
                if (isset($_SESSION['guest_session_id'])) {
                    migrateGuestCartToUser($_SESSION['guest_session_id'], $user['id'], $conn);
                    unset($_SESSION['guest_session_id']);
                }

                header('Location: ' . SITE_URL . 'pages/dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
        }
        .card {
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        }
        .card-body {
            padding: 3rem;
        }
        .card-title {
            color: #1a1a1a;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .btn-dark {
            background-color: #1a1a1a;
            border-color: #1a1a1a;
        }
        .btn-dark:hover {
            background-color: #333;
            border-color: #333;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Login</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-dark w-100 btn-lg">Login</button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center text-muted">
                    Don't have an account? <a href="<?php echo SITE_URL; ?>auth/register.php" class="text-decoration-none">Register here</a>
                </p>

                <p class="text-center text-muted mb-0">
                    <a href="<?php echo SITE_URL; ?>index.php" class="text-decoration-none">Back to Home</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
