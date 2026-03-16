<?php
/**
 * Project List
 * Shows all projects. Users see own + member projects; admin sees all.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Projects';
$userId = currentUserId();

if (isAdmin()) {
    $projects = $pdo->query("
        SELECT p.*, u.name as owner_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
        FROM projects p 
        JOIN users u ON p.owner_id = u.id 
        ORDER BY p.created_at DESC
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, u.name as owner_name,
               (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
               (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
        FROM projects p 
        JOIN users u ON p.owner_id = u.id 
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE p.owner_id = ? OR pm.user_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $projects = $stmt->fetchAll();
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Projects 📁</h1>
                <p><?php echo count($projects); ?> project<?php echo count($projects) !== 1 ? 's' : ''; ?></p>
            </div>
            <a href="/modules/projects/create.php" class="btn btn-primary">+ New Project</a>
        </div>

        <?php if (empty($projects)): ?>
            <div class="empty-state">
                <div class="empty-icon">📁</div>
                <p>No projects yet. Create your first project!</p>
                <a href="/modules/projects/create.php" class="btn btn-primary">Create Project</a>
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <?php foreach ($projects as $p): ?>
                    <div class="card" style="cursor:pointer;" onclick="window.location='/modules/projects/view.php?id=<?php echo $p['id']; ?>'">
                        <div class="card-header">
                            <h3><?php echo e($p['title']); ?></h3>
                            <span class="badge <?php echo statusBadge($p['status']); ?>"><?php echo e($p['status']); ?></span>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-2" style="font-size:0.85rem;"><?php echo e(substr($p['description'] ?? '', 0, 100)); ?><?php echo strlen($p['description'] ?? '') > 100 ? '...' : ''; ?></p>
                            <div class="d-flex justify-between" style="font-size:0.85rem; color:var(--text-muted);">
                                <span>👤 <?php echo e($p['owner_name']); ?></span>
                                <span>📋 <?php echo $p['task_count']; ?> tasks</span>
                                <span>👥 <?php echo $p['member_count']; ?> members</span>
                            </div>
                            <?php if ($p['end_date']): ?>
                                <div class="mt-1" style="font-size:0.8rem; color:var(--text-muted);">
                                    Due: <?php echo date('M d, Y', strtotime($p['end_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
