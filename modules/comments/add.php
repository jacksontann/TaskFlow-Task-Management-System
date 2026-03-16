<?php
/**
 * Add Comment
 * Handles adding a comment to a task (POST only).
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/modules/tasks/list.php');
}

validateCsrf();

$taskId = (int)($_POST['task_id'] ?? 0);
$content = trim($_POST['content'] ?? '');

if (!$taskId || empty($content)) {
    setFlash('error', 'Comment cannot be empty.');
    redirect('/modules/tasks/view.php?id=' . $taskId);
}

// Verify task exists
$stmt = $pdo->prepare("SELECT title FROM tasks WHERE id = ?");
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('error', 'Task not found.');
    redirect('/modules/tasks/list.php');
}

// Insert comment
$stmt = $pdo->prepare("INSERT INTO task_comments (task_id, user_id, content) VALUES (?, ?, ?)");
$stmt->execute([$taskId, currentUserId(), $content]);

logActivity($pdo, currentUserId(), "Added comment on task: " . $task['title'], 'comment', $pdo->lastInsertId());
setFlash('success', 'Comment added!');
redirect('/modules/tasks/view.php?id=' . $taskId);
