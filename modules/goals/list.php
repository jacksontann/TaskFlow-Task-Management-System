<?php
/**
 * Goal List
 * View all goals with progress bars.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Goals';
$userId = currentUserId();

if (isAdmin()) {
    $goals = $pdo->query("SELECT g.*, u.name as user_name FROM goals g JOIN users u ON g.user_id = u.id ORDER BY g.created_at DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT g.*, u.name as user_name FROM goals g JOIN users u ON g.user_id = u.id WHERE g.user_id = ? ORDER BY g.created_at DESC");
    $stmt->execute([$userId]);
    $goals = $stmt->fetchAll();
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
                <h1>Goals 🎯</h1>
                <p>Track your personal goals and milestones</p>
            </div>
            <a href="/modules/goals/create.php" class="btn btn-primary">+ New Goal</a>
        </div>

        <?php if (empty($goals)): ?>
            <div class="empty-state">
                <div class="empty-icon">🎯</div>
                <p>No goals yet. Set your first goal!</p>
                <a href="/modules/goals/create.php" class="btn btn-primary">Create Goal</a>
            </div>
        <?php else: ?>
            <div class="stats-grid">
                <?php foreach ($goals as $g): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3><?php echo e($g['title']); ?></h3>
                            <span class="badge <?php echo statusBadge($g['status']); ?>"><?php echo e($g['status']); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (isAdmin()): ?>
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:8px;">By <?php echo e($g['user_name']); ?></div>
                            <?php endif; ?>
                            <p class="text-muted mb-2" style="font-size:0.85rem;"><?php echo e(substr($g['description'] ?? '', 0, 80)); ?></p>
                            <div class="d-flex justify-between mb-1" style="font-size:0.8rem;">
                                <span class="badge <?php echo $g['type'] === 'short-term' ? 'badge-medium' : 'badge-progress'; ?>"><?php echo e($g['type']); ?></span>
                                <span><?php echo $g['progress']; ?>%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $g['progress']; ?>%"></div>
                            </div>
                            <?php if ($g['target_date']): ?>
                                <div class="mt-1" style="font-size:0.8rem; color:var(--text-muted);">Target: <?php echo date('M d, Y', strtotime($g['target_date'])); ?></div>
                            <?php endif; ?>
                            <div class="btn-group mt-2">
                                <a href="/modules/goals/edit.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                <a href="/modules/goals/delete.php?id=<?php echo $g['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirmDelete('Delete this goal?')">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
