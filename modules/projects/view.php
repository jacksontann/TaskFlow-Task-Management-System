<?php
/**
 * Project Detail View
 * Shows project info, members, and associated tasks.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = currentUserId();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/projects/list.php');

$stmt = $pdo->prepare("SELECT p.*, u.name as owner_name FROM projects p JOIN users u ON p.owner_id = u.id WHERE p.id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('error', 'Project not found.');
    redirect('/modules/projects/list.php');
}

// Check access
$hasAccess = isAdmin() || $project['owner_id'] === $userId;
if (!$hasAccess) {
    $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
    $hasAccess = (bool)$stmt->fetch();
}
if (!$hasAccess) {
    setFlash('error', 'Access denied.');
    redirect('/modules/projects/list.php');
}

$pageTitle = $project['title'];

// Fetch members
$members = $pdo->prepare("
    SELECT pm.*, u.name, u.email 
    FROM project_members pm 
    JOIN users u ON pm.user_id = u.id 
    WHERE pm.project_id = ?
");
$members->execute([$id]);
$members = $members->fetchAll();

// Fetch project tasks
$tasks = $pdo->prepare("
    SELECT t.*, u.name as user_name 
    FROM tasks t 
    JOIN users u ON t.user_id = u.id 
    WHERE t.project_id = ? 
    ORDER BY t.created_at DESC
");
$tasks->execute([$id]);
$tasks = $tasks->fetchAll();

$isOwner = $project['owner_id'] === $userId || isAdmin();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1><?php echo e($project['title']); ?></h1>
                <p>By <?php echo e($project['owner_name']); ?> · <span class="badge <?php echo statusBadge($project['status']); ?>"><?php echo e($project['status']); ?></span></p>
            </div>
            <div class="btn-group">
                <?php if ($isOwner): ?>
                    <a href="/modules/projects/edit.php?id=<?php echo $id; ?>" class="btn btn-outline">Edit</a>
                    <a href="/modules/projects/members.php?id=<?php echo $id; ?>" class="btn btn-outline">👥 Members</a>
                    <a href="/modules/projects/delete.php?id=<?php echo $id; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete('Delete this project?')">Delete</a>
                <?php endif; ?>
                <a href="/modules/projects/list.php" class="btn btn-outline">← Back</a>
            </div>
        </div>

        <div class="detail-grid">
            <div>
                <!-- Project Details -->
                <div class="card mb-3">
                    <div class="card-header"><h3>📋 Details</h3></div>
                    <div class="card-body">
                        <div class="detail-field">
                            <div class="detail-label">Description</div>
                            <div class="detail-value"><?php echo nl2br(e($project['description'] ?: 'No description.')); ?></div>
                        </div>
                        <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:16px;">
                            <div class="detail-field">
                                <div class="detail-label">Start Date</div>
                                <div class="detail-value"><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '—'; ?></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">End Date</div>
                                <div class="detail-value"><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '—'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tasks -->
                <div class="card">
                    <div class="card-header">
                        <h3>✅ Tasks (<?php echo count($tasks); ?>)</h3>
                        <a href="/modules/tasks/create.php?project_id=<?php echo $id; ?>" class="btn btn-sm btn-primary">+ Add Task</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($tasks)): ?>
                            <p class="text-muted">No tasks in this project yet.</p>
                        <?php else: ?>
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr><th>Title</th><th>Assigned To</th><th>Priority</th><th>Status</th><th>Deadline</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tasks as $t): ?>
                                            <tr>
                                                <td><a href="/modules/tasks/view.php?id=<?php echo $t['id']; ?>"><?php echo e($t['title']); ?></a></td>
                                                <td><?php echo e($t['user_name']); ?></td>
                                                <td><span class="badge <?php echo priorityBadge($t['priority']); ?>"><?php echo e($t['priority']); ?></span></td>
                                                <td><span class="badge <?php echo statusBadge($t['status']); ?>"><?php echo str_replace('_', ' ', e($t['status'])); ?></span></td>
                                                <td><?php echo $t['deadline'] ? date('M d', strtotime($t['deadline'])) : '—'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Members Sidebar -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3>👥 Members (<?php echo count($members); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php foreach ($members as $m): ?>
                            <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border-color);">
                                <div class="comment-avatar"><?php echo strtoupper(substr($m['name'], 0, 1)); ?></div>
                                <div>
                                    <div style="font-weight:600; font-size:0.9rem;"><?php echo e($m['name']); ?></div>
                                    <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo e($m['role']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
