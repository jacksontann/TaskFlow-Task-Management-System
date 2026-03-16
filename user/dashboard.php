<?php
/**
 * User Dashboard
 * Shows personal stats, recent tasks, activity, and a productivity chart.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireLogin();

// Redirect admins to admin dashboard
if (isAdmin()) {
    redirect('/admin/dashboard.php');
}

$userId = currentUserId();
$pageTitle = 'Dashboard';

// --- Fetch Stats ---
// Total tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
$stmt->execute([$userId]);
$totalTasks = $stmt->fetchColumn();

// Completed tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$completedTasks = $stmt->fetchColumn();

// Pending tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pendingTasks = $stmt->fetchColumn();

// Overdue tasks
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status != 'completed' AND deadline < CURDATE()");
$stmt->execute([$userId]);
$overdueTasks = $stmt->fetchColumn();

// Total goals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM goals WHERE user_id = ?");
$stmt->execute([$userId]);
$totalGoals = $stmt->fetchColumn();

// Completed goals
$stmt = $pdo->prepare("SELECT COUNT(*) FROM goals WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$completedGoals = $stmt->fetchColumn();

// Total hours tracked
$stmt = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs WHERE user_id = ?");
$stmt->execute([$userId]);
$totalMinutes = $stmt->fetchColumn();
$totalHours = round($totalMinutes / 60, 1);

// Recent tasks (latest 5)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentTasks = $stmt->fetchAll();

// Recent activity (latest 8)
$stmt = $pdo->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$stmt->execute([$userId]);
$recentActivity = $stmt->fetchAll();

// Tasks completed per day this week (for chart)
$stmt = $pdo->prepare("
    SELECT DATE(updated_at) as day, COUNT(*) as count 
    FROM tasks 
    WHERE user_id = ? AND status = 'completed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(updated_at)
    ORDER BY day
");
$stmt->execute([$userId]);
$weeklyData = $stmt->fetchAll();

// Build chart labels and data
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('D', strtotime($date));
    $found = false;
    foreach ($weeklyData as $row) {
        if ($row['day'] === $date) {
            $chartData[] = (int)$row['count'];
            $found = true;
            break;
        }
    }
    if (!$found) $chartData[] = 0;
}

// Include header
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>Welcome back, <?php echo e(currentUserName()); ?>! 👋</h1>
                <p>Here's your productivity overview</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $totalTasks; ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $completedTasks; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $pendingTasks; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?php echo $overdueTasks; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?php echo $totalGoals; ?></div>
                <div class="stat-label">Total Goals</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo $completedGoals; ?></div>
                <div class="stat-label">Goals Done</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo $totalHours; ?>h</div>
                <div class="stat-label">Hours Tracked</div>
            </div>
        </div>

        <!-- Charts and Activity Row -->
        <div class="grid-2">
            <!-- Productivity Chart -->
            <div class="card">
                <div class="card-header">
                    <h3>📈 Weekly Productivity</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="productivityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>🕐 Recent Activity</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted">No recent activity.</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($recentActivity as $act): ?>
                                <li class="activity-item">
                                    <span class="activity-dot"></span>
                                    <span><?php echo e($act['action']); ?></span>
                                    <span class="activity-time"><?php echo timeAgo($act['created_at']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Tasks -->
        <div class="card">
            <div class="card-header">
                <h3>📋 Recent Tasks</h3>
                <a href="/modules/tasks/list.php" class="btn btn-sm btn-outline">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recentTasks)): ?>
                    <p class="text-muted">No tasks yet. <a href="/modules/tasks/create.php">Create one!</a></p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Deadline</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTasks as $task): ?>
                                    <tr class="<?php echo ($task['deadline'] && $task['deadline'] < date('Y-m-d') && $task['status'] !== 'completed') ? 'overdue' : ''; ?>">
                                        <td><a href="/modules/tasks/view.php?id=<?php echo $task['id']; ?>"><?php echo e($task['title']); ?></a></td>
                                        <td><span class="badge <?php echo priorityBadge($task['priority']); ?>"><?php echo e($task['priority']); ?></span></td>
                                        <td><span class="badge <?php echo statusBadge($task['status']); ?>"><?php echo str_replace('_', ' ', e($task['status'])); ?></span></td>
                                        <td><?php echo $task['deadline'] ? date('M d, Y', strtotime($task['deadline'])) : '—'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<!-- Productivity Chart Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('productivityChart').getContext('2d');
    new Chart(ctx, createChartConfig('bar',
        <?php echo json_encode($chartLabels); ?>,
        [{
            label: 'Tasks Completed',
            data: <?php echo json_encode($chartData); ?>,
            backgroundColor: chartColors.purple,
            borderColor: chartColors.purple,
            borderRadius: 6,
            borderWidth: 0
        }]
    ));
});
</script>
