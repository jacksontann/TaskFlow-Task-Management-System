<?php
/**
 * Index Page
 * Redirects logged-in users to their dashboard, others to login.
 */
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . getDashboardUrl());
} else {
    header('Location: /auth/login.php');
}
exit;
