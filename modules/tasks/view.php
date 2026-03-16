<?php
/**
 * Task Detail View
 * Shows task details, comments, and time logs.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$userId = currentUserId();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/tasks/list.php');

// Fetch task with user info
$stmt = $pdo->prepare("SELECT t.*, u.name as user_name FROM tasks t JOIN users u ON t.user_id = u.id WHERE t.id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('error', 'Task not found.');
    redirect('/modules/tasks/list.php');
}

// Check access: owner, admin, or project member
$hasAccess = isAdmin() || $task['user_id'] === $userId;
if (!$hasAccess && $task['project_id']) {
    $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$task['project_id'], $userId]);
    $hasAccess = (bool)$stmt->fetch();
}

if (!$hasAccess) {
    setFlash('error', 'You do not have permission to view this task.');
    redirect('/modules/tasks/list.php');
}

$pageTitle = $task['title'];

// Fetch comments
$comments = $pdo->prepare("
    SELECT tc.*, u.name as user_name 
    FROM task_comments tc 
    JOIN users u ON tc.user_id = u.id 
    WHERE tc.task_id = ? 
    ORDER BY tc.created_at ASC
");
$comments->execute([$id]);
$comments = $comments->fetchAll();

// Fetch time logs
$timeLogs = $pdo->prepare("
    SELECT tl.*, u.name as user_name 
    FROM time_logs tl 
    JOIN users u ON tl.user_id = u.id 
    WHERE tl.task_id = ? 
    ORDER BY tl.created_at DESC
");
$timeLogs->execute([$id]);
$timeLogs = $timeLogs->fetchAll();

// Total time on this task
$stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE task_id = ?");
$stmt->execute([$id]);
$totalTime = $stmt->fetchColumn();

// Get project name if assigned
$projectName = null;
if ($task['project_id']) {
    $stmt = $pdo->prepare("SELECT title FROM projects WHERE id = ?");
    $stmt->execute([$task['project_id']]);
    $projectName = $stmt->fetchColumn();
}

$isOverdue = $task['deadline'] && $task['deadline'] < date('Y-m-d') && $task['status'] !== 'completed';

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1><?php echo e($task['title']); ?></h1>
                <p>Created by <?php echo e($task['user_name']); ?> · <?php echo timeAgo($task['created_at']); ?></p>
            </div>
            <div class="btn-group">
                <?php if (isAdmin() || $task['user_id'] === $userId): ?>
                    <a href="/modules/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-outline">Edit</a>
                <?php endif; ?>
                <a href="/modules/tasks/list.php" class="btn btn-outline">← Back</a>
            </div>
        </div>

        <div class="detail-grid">
            <!-- Main Content -->
            <div>
                <!-- Task Info Card -->
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="detail-field">
                            <div class="detail-label">Description</div>
                            <div class="detail-value"><?php echo nl2br(e($task['description'] ?: 'No description provided.')); ?></div>
                        </div>

                        <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:20px;">
                            <div class="detail-field">
                                <div class="detail-label">Priority</div>
                                <div><span class="badge <?php echo priorityBadge($task['priority']); ?>"><?php echo e($task['priority']); ?></span></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">Status</div>
                                <div><span class="badge <?php echo statusBadge($task['status']); ?>"><?php echo str_replace('_', ' ', e($task['status'])); ?></span></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">Category</div>
                                <div class="detail-value"><?php echo e($task['category'] ?: '—'); ?></div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">Deadline</div>
                                <div class="detail-value <?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                    <?php echo $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : '—'; ?>
                                    <?php if ($isOverdue): ?> <span class="badge badge-high">Overdue</span><?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">Project</div>
                                <div class="detail-value">
                                    <?php if ($projectName): ?>
                                        <a href="/modules/projects/view.php?id=<?php echo $task['project_id']; ?>"><?php echo e($projectName); ?></a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="detail-field">
                                <div class="detail-label">Time Spent</div>
                                <div class="detail-value"><?php echo formatDuration($totalTime); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="card">
                    <div class="card-header">
                        <h3>💬 Comments (<?php echo count($comments); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($comments)): ?>
                            <p class="text-muted">No comments yet. Be the first to comment!</p>
                        <?php else: ?>
                            <ul class="comment-list">
                                <?php foreach ($comments as $c): ?>
                                    <li class="comment-item">
                                        <div class="comment-avatar"><?php echo strtoupper(substr($c['user_name'], 0, 1)); ?></div>
                                        <div class="comment-body">
                                            <div class="comment-header">
                                                <span class="comment-author"><?php echo e($c['user_name']); ?></span>
                                                <span class="comment-time"><?php echo timeAgo($c['created_at']); ?></span>
                                                <?php if (isAdmin() || $c['user_id'] === $userId): ?>
                                                    <a href="/modules/comments/delete.php?id=<?php echo $c['id']; ?>&task_id=<?php echo $id; ?>"
                                                       class="text-danger" style="font-size:0.8rem;"
                                                       onclick="return confirmDelete('Delete this comment?')">Delete</a>
                                                <?php endif; ?>
                                            </div>
                                            <div class="comment-content"><?php echo nl2br(e($c['content'])); ?></div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Add Comment Form -->
                        <form class="comment-form" method="POST" action="/modules/comments/add.php">
                            <input type="hidden" name="task_id" value="<?php echo $id; ?>">
                            <?php echo csrfField(); ?>
                            <textarea name="content" class="form-control" placeholder="Write a comment..." rows="2" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm">Post</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Quick Actions -->
                <div class="card mb-3">
                    <div class="card-header"><h3>⚡ Quick Actions</h3></div>
                    <div class="card-body">
                        <div style="display:flex; flex-direction:column; gap:8px;">
                            <?php if ($task['status'] !== 'completed' && ($task['user_id'] === $userId || isAdmin())): ?>
                                <a href="/modules/tasks/edit.php?id=<?php echo $task['id']; ?>&mark_complete=1" class="btn btn-success btn-sm">✅ Mark Complete</a>
                            <?php endif; ?>
                            <a href="/modules/time_logs/add.php?task_id=<?php echo $task['id']; ?>" class="btn btn-outline btn-sm">⏱ Log Time</a>
                        </div>
                    </div>
                </div>

                <!-- Time Logs -->
                <div class="card">
                    <div class="card-header"><h3>⏱️ Time Logs</h3></div>
                    <div class="card-body">
                        <?php if (empty($timeLogs)): ?>
                            <p class="text-muted">No time logged yet.</p>
                        <?php else: ?>
                            <?php foreach ($timeLogs as $tl): ?>
                                <div style="padding:10px 0; border-bottom:1px solid var(--border-color);">
                                    <div style="font-weight:600; font-size:0.9rem;"><?php echo formatDuration($tl['duration_minutes']); ?></div>
                                    <div class="text-muted" style="font-size:0.8rem;">
                                        <?php echo e($tl['user_name']); ?> · <?php echo timeAgo($tl['created_at']); ?>
                                    </div>
                                    <?php if ($tl['notes']): ?>
                                        <div style="font-size:0.85rem; margin-top:4px;"><?php echo e($tl['notes']); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
