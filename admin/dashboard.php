<?php
/**
 * Admin Dashboard
 * Shows system-wide stats, user summary, and overview chart.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$pageTitle = 'Admin Dashboard';

// --- Fetch System Stats ---
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$totalGoals = $pdo->query("SELECT COUNT(*) FROM goals")->fetchColumn();
$totalComments = $pdo->query("SELECT COUNT(*) FROM task_comments")->fetchColumn();
$totalMinutes = $pdo->query("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs")->fetchColumn();
$totalHours = round($totalMinutes / 60, 1);

// Completed tasks
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();

// Users summary (top 5 most active)
$usersSummary = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
           (SELECT COUNT(*) FROM tasks WHERE user_id = u.id) as task_count,
           (SELECT COUNT(*) FROM tasks WHERE user_id = u.id AND status = 'completed') as completed_count
    FROM users u
    ORDER BY task_count DESC
    LIMIT 5
")->fetchAll();

// Tasks by status (for chart)
$tasksByStatus = $pdo->query("
    SELECT status, COUNT(*) as count FROM tasks GROUP BY status
")->fetchAll();

$statusLabels = [];
$statusData = [];
$statusColors = [];
$colorMap = ['pending' => 'rgba(255,179,71,0.8)', 'in_progress' => 'rgba(108,99,255,0.8)', 'completed' => 'rgba(0,200,150,0.8)'];
foreach ($tasksByStatus as $row) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
    $statusData[] = (int)$row['count'];
    $statusColors[] = $colorMap[$row['status']] ?? 'rgba(108,108,128,0.8)';
}

// Recent activity (all users, latest 10)
$recentActivity = $pdo->query("
    SELECT al.*, u.name as user_name 
    FROM activity_logs al 
    JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC LIMIT 10
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Admin Dashboard 🛡️</h1>
                <p>System overview and analytics</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">📋</div>
                <div class="stat-value"><?php echo $totalTasks; ?></div>
                <div class="stat-label">Total Tasks</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $completedTasks; ?></div>
                <div class="stat-label">Completed Tasks</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?php echo $totalProjects; ?></div>
                <div class="stat-label">Projects</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon">🎯</div>
                <div class="stat-value"><?php echo $totalGoals; ?></div>
                <div class="stat-label">Goals</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">💬</div>
                <div class="stat-value"><?php echo $totalComments; ?></div>
                <div class="stat-label">Comments</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo $totalHours; ?>h</div>
                <div class="stat-label">Hours Tracked</div>
            </div>
        </div>

        <div class="grid-2">
            <!-- Tasks by Status Chart -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 Tasks by Status</h3>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3>🕐 System Activity</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recentActivity)): ?>
                        <p class="text-muted">No recent activity.</p>
                    <?php else: ?>
                        <ul class="activity-list">
                            <?php foreach ($recentActivity as $act): ?>
                                <li class="activity-item">
                                    <span class="activity-dot"></span>
                                    <span><strong><?php echo e($act['user_name']); ?></strong> — <?php echo e($act['action']); ?></span>
                                    <span class="activity-time"><?php echo timeAgo($act['created_at']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Users Summary -->
        <div class="card">
            <div class="card-header">
                <h3>👥 Top Users</h3>
                <a href="/admin/users.php" class="btn btn-sm btn-outline">Manage Users</a>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Tasks</th>
                                <th>Completed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersSummary as $u): ?>
                                <tr>
                                    <td><?php echo e($u['name']); ?></td>
                                    <td><?php echo e($u['email']); ?></td>
                                    <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-high' : 'badge-active'; ?>"><?php echo e($u['role']); ?></span></td>
                                    <td><span class="badge <?php echo statusBadge($u['status']); ?>"><?php echo e($u['status']); ?></span></td>
                                    <td><?php echo $u['task_count']; ?></td>
                                    <td><?php echo $u['completed_count']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('statusChart').getContext('2d');
    new Chart(ctx, createChartConfig('doughnut',
        <?php echo json_encode($statusLabels); ?>,
        [{
            data: <?php echo json_encode($statusData); ?>,
            backgroundColor: <?php echo json_encode($statusColors); ?>,
            borderWidth: 0,
            hoverOffset: 8
        }],
        {
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#a0a0b0', padding: 16, font: { family: 'Inter', size: 12 } }
                }
            }
        }
    ));
});
</script>
