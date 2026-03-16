<?php
/**
 * Delete Project
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/projects/list.php');

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('error', 'Project not found.');
    redirect('/modules/projects/list.php');
}

if (!isAdmin() && $project['owner_id'] !== currentUserId()) {
    setFlash('error', 'You can only delete your own projects.');
    redirect('/modules/projects/list.php');
}

$stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
$stmt->execute([$id]);

logActivity($pdo, currentUserId(), "Deleted project: " . $project['title'], 'project', $id);
setFlash('success', 'Project deleted.');
redirect('/modules/projects/list.php');
