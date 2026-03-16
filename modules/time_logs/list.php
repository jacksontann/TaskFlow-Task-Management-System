<?php
/**
 * Time Log List
 * Shows all time logs with daily/weekly/total summaries.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Time Tracking';
$userId = currentUserId();

// Fetch time logs
if (isAdmin()) {
    $logs = $pdo->query("
        SELECT tl.*, t.title as task_title, u.name as user_name
        FROM time_logs tl
        JOIN tasks t ON tl.task_id = t.id
        JOIN users u ON tl.user_id = u.id
        ORDER BY tl.created_at DESC
    ")->fetchAll();
    $totalMinutes = $pdo->query("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs")->fetchColumn();
    $todayMinutes = $pdo->query("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $weekMinutes = $pdo->query("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
} else {
    $stmt = $pdo->prepare("
        SELECT tl.*, t.title as task_title, u.name as user_name
        FROM time_logs tl
        JOIN tasks t ON tl.task_id = t.id
        JOIN users u ON tl.user_id = u.id
        WHERE tl.user_id = ?
        ORDER BY tl.created_at DESC
    ");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalMinutes = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userId]);
    $todayMinutes = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt->execute([$userId]);
    $weekMinutes = $stmt->fetchColumn();
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
                <h1>Time Tracking ⏱️</h1>
                <p>Track and manage your work hours</p>
            </div>
            <a href="/modules/time_logs/add.php" class="btn btn-primary">+ Log Time</a>
        </div>

        <!-- Time Summary Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo formatDuration($todayMinutes); ?></div>
                <div class="stat-label">Today</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo formatDuration($weekMinutes); ?></div>
                <div class="stat-label">This Week</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo formatDuration($totalMinutes); ?></div>
                <div class="stat-label">All Time</div>
            </div>
        </div>

        <!-- Time Logs Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Task</th>
                        <?php if (isAdmin()): ?><th>User</th><?php endif; ?>
                        <th>Start</th>
                        <th>End</th>
                        <th>Duration</th>
                        <th>Notes</th>
                        <th>Logged</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="<?php echo isAdmin() ? 7 : 6; ?>" class="text-center text-muted">No time logs yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><a href="/modules/tasks/view.php?id=<?php echo $log['task_id']; ?>"><?php echo e($log['task_title']); ?></a></td>
                                <?php if (isAdmin()): ?><td><?php echo e($log['user_name']); ?></td><?php endif; ?>
                                <td><?php echo $log['start_time'] ? date('M d H:i', strtotime($log['start_time'])) : '—'; ?></td>
                                <td><?php echo $log['end_time'] ? date('M d H:i', strtotime($log['end_time'])) : '—'; ?></td>
                                <td><strong><?php echo formatDuration($log['duration_minutes']); ?></strong></td>
                                <td><?php echo e($log['notes'] ?: '—'); ?></td>
                                <td><?php echo timeAgo($log['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
