<?php
/**
 * Authentication Helper
 * Handles session checks and role-based access control.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if current user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Get current user ID
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user name
 */
function currentUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Require user to be logged in — redirect to login if not
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Require user to be admin — redirect if not
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /user/dashboard.php');
        exit;
    }
}

/**
 * Get the dashboard URL based on user role
 */
function getDashboardUrl() {
    return isAdmin() ? '/admin/dashboard.php' : '/user/dashboard.php';
}
