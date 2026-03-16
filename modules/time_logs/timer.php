<?php
/**
 * Timer AJAX Endpoint
 * Handles start/stop timer requests via AJAX.
 * This is a simple endpoint for future AJAX timer support.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$taskId = (int)($_POST['task_id'] ?? 0);
$userId = currentUserId();

if ($action === 'start' && $taskId) {
    // Create a time log entry with start time, no end time
    $stmt = $pdo->prepare("INSERT INTO time_logs (task_id, user_id, start_time, duration_minutes) VALUES (?, ?, NOW(), 0)");
    $stmt->execute([$taskId, $userId]);
    echo json_encode(['success' => true, 'log_id' => $pdo->lastInsertId(), 'message' => 'Timer started']);
} elseif ($action === 'stop') {
    $logId = (int)($_POST['log_id'] ?? 0);
    if ($logId) {
        // Update end time and calculate duration
        $stmt = $pdo->prepare("
            UPDATE time_logs 
            SET end_time = NOW(), 
                duration_minutes = TIMESTAMPDIFF(MINUTE, start_time, NOW())
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$logId, $userId]);
        echo json_encode(['success' => true, 'message' => 'Timer stopped']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
