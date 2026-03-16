<?php
/**
 * Admin Analytics
 * System-wide analytics with Chart.js charts.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireAdmin();

$pageTitle = 'Admin Analytics';

// --- Data ---

// 1. Task completion trend (last 7 days, all users)
$weeklyTasks = $pdo->query("
    SELECT DATE(updated_at) as day, COUNT(*) as count 
    FROM tasks WHERE status = 'completed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(updated_at) ORDER BY day
")->fetchAll();

$weekLabels = [];
$weekData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekLabels[] = date('D', strtotime($date));
    $found = false;
    foreach ($weeklyTasks as $row) {
        if ($row['day'] === $date) { $weekData[] = (int)$row['count']; $found = true; break; }
    }
    if (!$found) $weekData[] = 0;
}

// 2. Most active users (by tasks completed)
$activeUsers = $pdo->query("
    SELECT u.name, COUNT(t.id) as completed
    FROM users u
    LEFT JOIN tasks t ON t.user_id = u.id AND t.status = 'completed'
    WHERE u.role = 'user'
    GROUP BY u.id
    ORDER BY completed DESC
    LIMIT 10
")->fetchAll();

$userLabels = array_column($activeUsers, 'name');
$userData = array_column($activeUsers, 'completed');

// 3. Tasks by status (all users)
$tasksByStatus = $pdo->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status")->fetchAll();
$statusLabels = [];
$statusData = [];
$statusColors = [];
$colorMap = ['pending' => 'rgba(255,179,71,0.8)', 'in_progress' => 'rgba(108,99,255,0.8)', 'completed' => 'rgba(0,200,150,0.8)'];
foreach ($tasksByStatus as $row) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
    $statusData[] = (int)$row['count'];
    $statusColors[] = $colorMap[$row['status']] ?? 'rgba(108,108,128,0.8)';
}

// 4. Hours tracked per day this week (all users)
$weeklyHours = $pdo->query("
    SELECT DATE(created_at) as day, COALESCE(SUM(duration_minutes), 0) as total
    FROM time_logs WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY day
")->fetchAll();

$hoursData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($weeklyHours as $row) {
        if ($row['day'] === $date) { $hoursData[] = round((int)$row['total'] / 60, 1); $found = true; break; }
    }
    if (!$found) $hoursData[] = 0;
}

// Summary stats
$totalCompleted = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'completed'")->fetchColumn();
$totalHoursAll = round($pdo->query("SELECT COALESCE(SUM(duration_minutes), 0) FROM time_logs")->fetchColumn() / 60, 1);
$totalGoalsCompleted = $pdo->query("SELECT COUNT(*) FROM goals WHERE status = 'completed'")->fetchColumn();
$totalGoals = $pdo->query("SELECT COUNT(*) FROM goals")->fetchColumn();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="page-header">
            <div>
                <h1>Admin Analytics 📈</h1>
                <p>System-wide productivity overview</p>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card green">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $totalCompleted; ?></div>
                <div class="stat-label">Total Completed Tasks</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo $totalHoursAll; ?>h</div>
                <div class="stat-label">Total Hours Tracked</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo $totalGoalsCompleted; ?>/<?php echo $totalGoals; ?></div>
                <div class="stat-label">Goals Completed</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon">📊</div>
                <div class="stat-value"><?php echo array_sum($weekData); ?></div>
                <div class="stat-label">Completed This Week</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><h3>📊 Tasks Completed This Week</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="weeklyChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>⏱️ Hours Tracked This Week</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="hoursChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><h3>🏅 Most Active Users</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="usersChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>📋 Tasks by Status</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weekly Tasks Completed
    new Chart(document.getElementById('weeklyChart').getContext('2d'), createChartConfig('bar',
        <?php echo json_encode($weekLabels); ?>,
        [{ label: 'Tasks Completed', data: <?php echo json_encode($weekData); ?>, backgroundColor: chartColors.green, borderRadius: 6, borderWidth: 0 }]
    ));

    // Weekly Hours
    new Chart(document.getElementById('hoursChart').getContext('2d'), createChartConfig('line',
        <?php echo json_encode($weekLabels); ?>,
        [{
            label: 'Hours',
            data: <?php echo json_encode($hoursData); ?>,
            borderColor: chartColors.purple,
            backgroundColor: chartColors.purpleLight,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: chartColors.purple,
            pointRadius: 5
        }]
    ));

    // Most Active Users
    new Chart(document.getElementById('usersChart').getContext('2d'), createChartConfig('bar',
        <?php echo json_encode($userLabels); ?>,
        [{ label: 'Tasks Completed', data: <?php echo json_encode($userData); ?>, backgroundColor: [chartColors.purple, chartColors.blue, chartColors.green, chartColors.orange, chartColors.pink], borderRadius: 6, borderWidth: 0 }],
        { indexAxis: 'y', plugins: { legend: { display: false } } }
    ));

    // Tasks by Status
    new Chart(document.getElementById('statusChart').getContext('2d'), createChartConfig('doughnut',
        <?php echo json_encode($statusLabels); ?>,
        [{ data: <?php echo json_encode($statusData); ?>, backgroundColor: <?php echo json_encode($statusColors); ?>, borderWidth: 0, hoverOffset: 8 }],
        { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: '#a0a0b0', padding: 16 } } } }
    ));
});
</script>
