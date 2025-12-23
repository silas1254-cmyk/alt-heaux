<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constants
define('SITE_URL', 'http://localhost/alt-heaux/');

// Destroy session
session_destroy();
setcookie('PHPSESSID', '', time() - 3600, '/');

header('Location: ' . SITE_URL . 'index.php?message=logged_out');
exit;
?>
