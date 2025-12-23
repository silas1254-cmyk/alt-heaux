<?php
require '../includes/config.php';

// Destroy session
session_destroy();
setcookie('PHPSESSID', '', time() - 3600, '/');

// Redirect to admin login
header('Location: ' . SITE_URL . 'admin/login.php');
exit;
?>
