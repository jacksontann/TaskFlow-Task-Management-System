<?php
/**
 * Delete Comment
 * Deletes a comment. Users can only delete their own; admin can delete any.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$taskId = (int)($_GET['task_id'] ?? 0);

if (!$id) redirect('/modules/tasks/list.php');

// Fetch comment
$stmt = $pdo->prepare("SELECT * FROM task_comments WHERE id = ?");
$stmt->execute([$id]);
$comment = $stmt->fetch();

if (!$comment) {
    setFlash('error', 'Comment not found.');
    redirect('/modules/tasks/view.php?id=' . $taskId);
}

// Check ownership
if (!isAdmin() && $comment['user_id'] !== currentUserId()) {
    setFlash('error', 'You can only delete your own comments.');
    redirect('/modules/tasks/view.php?id=' . $taskId);
}

$stmt = $pdo->prepare("DELETE FROM task_comments WHERE id = ?");
$stmt->execute([$id]);

setFlash('success', 'Comment deleted.');
redirect('/modules/tasks/view.php?id=' . ($taskId ?: $comment['task_id']));
