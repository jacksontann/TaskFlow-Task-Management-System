<?php
/**
 * Delete Goal
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/goals/list.php');

$stmt = $pdo->prepare("SELECT * FROM goals WHERE id = ?");
$stmt->execute([$id]);
$goal = $stmt->fetch();

if (!$goal) {
    setFlash('error', 'Goal not found.');
    redirect('/modules/goals/list.php');
}

if (!isAdmin() && $goal['user_id'] !== currentUserId()) {
    setFlash('error', 'You can only delete your own goals.');
    redirect('/modules/goals/list.php');
}

$stmt = $pdo->prepare("DELETE FROM goals WHERE id = ?");
$stmt->execute([$id]);

logActivity($pdo, currentUserId(), "Deleted goal: " . $goal['title'], 'goal', $id);
setFlash('success', 'Goal deleted.');
redirect('/modules/goals/list.php');
