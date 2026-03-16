<?php
/**
 * Helper Functions
 * Common utility functions used across the application.
 */

/**
 * Escape output to prevent XSS attacks
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Generate a CSRF token and store it in the session
 */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field for forms
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

/**
 * Validate a submitted CSRF token
 */
function validateCsrf() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        die('CSRF token validation failed.');
    }
}

/**
 * Set a flash message to display on the next page load
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear the flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = e($flash['type']); // success, error, warning, info
        $message = e($flash['message']);
        echo "<div class='flash-message flash-{$type}'>{$message}</div>";
    }
}

/**
 * Log an activity to the activity_logs table
 */
function logActivity($pdo, $userId, $action, $entityType = null, $entityId = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, entity_type, entity_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $entityType, $entityId]);
}

/**
 * Convert a datetime string to a human-readable "time ago" format
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

/**
 * Format minutes into hours and minutes string
 */
function formatDuration($minutes) {
    if ($minutes < 60) return $minutes . 'm';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return $hours . 'h ' . $mins . 'm';
}

/**
 * Get a priority badge CSS class
 */
function priorityBadge($priority) {
    $classes = [
        'low' => 'badge-low',
        'medium' => 'badge-medium',
        'high' => 'badge-high',
    ];
    return $classes[$priority] ?? 'badge-medium';
}

/**
 * Get a status badge CSS class
 */
function statusBadge($status) {
    $classes = [
        'pending' => 'badge-pending',
        'in_progress' => 'badge-progress',
        'completed' => 'badge-completed',
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'archived' => 'badge-archived',
    ];
    return $classes[$status] ?? 'badge-pending';
}
