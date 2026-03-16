<?php
/**
 * Delete Task
 * Deletes a task. Users can only delete their own; admin can delete any.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/tasks/list.php');

// Fetch task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('error', 'Task not found.');
    redirect('/modules/tasks/list.php');
}

// Check ownership (unless admin)
if (!isAdmin() && $task['user_id'] !== currentUserId()) {
    setFlash('error', 'You do not have permission to delete this task.');
    redirect('/modules/tasks/list.php');
}

// Delete the task
$stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
$stmt->execute([$id]);

logActivity($pdo, currentUserId(), "Deleted task: " . $task['title'], 'task', $id);
setFlash('success', 'Task deleted successfully.');
redirect('/modules/tasks/list.php');
