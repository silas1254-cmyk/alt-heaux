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

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . 'pages/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    // Validation
    if (empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email or username already exists
        $check_query = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'Email or username already exists.';
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $insert_query = "INSERT INTO users (email, username, password, first_name, last_name, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('sssss', $email, $username, $hashed_password, $first_name, $last_name);

            if ($stmt->execute()) {
                $success = 'Account created successfully! You can now log in.';
                // Clear form
                $_POST = [];
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .register-container {
            width: 100%;
            max-width: 500px;
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
    <div class="register-container">
        <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Create Account</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-dark w-100 btn-lg">Create Account</button>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center text-muted">
                    Already have an account? <a href="<?php echo SITE_URL; ?>auth/login.php" class="text-decoration-none">Log in here</a>
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
