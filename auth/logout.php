<?php
/**
 * Logout
 * Destroys the session and redirects to login page.
 */
require_once __DIR__ . '/../includes/auth.php';

// Destroy all session data
$_SESSION = [];
session_destroy();

// Redirect to login
header('Location: /auth/login.php');
exit;
