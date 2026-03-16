<?php
/**
 * User Analytics
 * Productivity charts and stats using Chart.js.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Analytics';
$userId = currentUserId();

// --- Data for Charts ---

// 1. Tasks completed per day this week
$stmt = $pdo->prepare("
    SELECT DATE(updated_at) as day, COUNT(*) as count 
    FROM tasks 
    WHERE user_id = ? AND status = 'completed' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(updated_at) ORDER BY day
");
$stmt->execute([$userId]);
$weeklyTasks = $stmt->fetchAll();

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

// 2. Hours tracked per day this week
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as day, COALESCE(SUM(duration_minutes), 0) as total
    FROM time_logs 
    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at) ORDER BY day
");
$stmt->execute([$userId]);
$weeklyHours = $stmt->fetchAll();

$hoursData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $found = false;
    foreach ($weeklyHours as $row) {
        if ($row['day'] === $date) { $hoursData[] = round((int)$row['total'] / 60, 1); $found = true; break; }
    }
    if (!$found) $hoursData[] = 0;
}

// 3. Tasks by status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY status");
$stmt->execute([$userId]);
$tasksByStatus = $stmt->fetchAll();

$statusLabels = [];
$statusData = [];
$statusColors = [];
$colorMap = ['pending' => 'rgba(255,179,71,0.8)', 'in_progress' => 'rgba(108,99,255,0.8)', 'completed' => 'rgba(0,200,150,0.8)'];
foreach ($tasksByStatus as $row) {
    $statusLabels[] = ucfirst(str_replace('_', ' ', $row['status']));
    $statusData[] = (int)$row['count'];
    $statusColors[] = $colorMap[$row['status']] ?? 'rgba(108,108,128,0.8)';
}

// 4. Tasks by priority
$stmt = $pdo->prepare("SELECT priority, COUNT(*) as count FROM tasks WHERE user_id = ? GROUP BY priority");
$stmt->execute([$userId]);
$tasksByPriority = $stmt->fetchAll();

$priorityLabels = [];
$priorityData = [];
$priorityColors = [];
$pColorMap = ['low' => 'rgba(78,205,196,0.8)', 'medium' => 'rgba(255,179,71,0.8)', 'high' => 'rgba(255,107,107,0.8)'];
foreach ($tasksByPriority as $row) {
    $priorityLabels[] = ucfirst($row['priority']);
    $priorityData[] = (int)$row['count'];
    $priorityColors[] = $pColorMap[$row['priority']] ?? 'rgba(108,108,128,0.8)';
}

// 5. Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status != 'completed' AND deadline < CURDATE()");
$stmt->execute([$userId]);
$overdueCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM goals WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$userId]);
$goalsCompleted = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM goals WHERE user_id = ?");
$stmt->execute([$userId]);
$goalsTotal = $stmt->fetchColumn();

$goalPercentage = $goalsTotal > 0 ? round(($goalsCompleted / $goalsTotal) * 100) : 0;

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="page-header">
            <div>
                <h1>Analytics 📈</h1>
                <p>Your productivity at a glance</p>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card red">
                <div class="stat-icon">🔥</div>
                <div class="stat-value"><?php echo $overdueCount; ?></div>
                <div class="stat-label">Overdue Tasks</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon">🏆</div>
                <div class="stat-value"><?php echo $goalPercentage; ?>%</div>
                <div class="stat-label">Goal Completion</div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo array_sum($weekData); ?></div>
                <div class="stat-label">Tasks This Week</div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value"><?php echo array_sum($hoursData); ?>h</div>
                <div class="stat-label">Hours This Week</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><h3>✅ Tasks Completed This Week</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="weeklyTasksChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>⏱️ Hours Tracked This Week</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="weeklyHoursChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid-2">
            <div class="card">
                <div class="card-header"><h3>📊 Tasks by Status</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="statusChart"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3>🎯 Tasks by Priority</h3></div>
                <div class="card-body">
                    <div class="chart-container"><canvas id="priorityChart"></canvas></div>
                </div>
            </div>
        </div>

        <!-- Goal Progress -->
        <div class="card">
            <div class="card-header"><h3>🏆 Goal Completion Progress</h3></div>
            <div class="card-body">
                <div class="d-flex align-center justify-between mb-1">
                    <span><?php echo $goalsCompleted; ?> of <?php echo $goalsTotal; ?> goals completed</span>
                    <span><strong><?php echo $goalPercentage; ?>%</strong></span>
                </div>
                <div class="progress-bar-container" style="height:20px;">
                    <div class="progress-bar" style="width: <?php echo $goalPercentage; ?>%"></div>
                </div>
            </div>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Weekly Tasks Bar Chart
    new Chart(document.getElementById('weeklyTasksChart').getContext('2d'), createChartConfig('bar',
        <?php echo json_encode($weekLabels); ?>,
        [{ label: 'Tasks Completed', data: <?php echo json_encode($weekData); ?>, backgroundColor: chartColors.purple, borderRadius: 6, borderWidth: 0 }]
    ));

    // Weekly Hours Line Chart
    new Chart(document.getElementById('weeklyHoursChart').getContext('2d'), createChartConfig('line',
        <?php echo json_encode($weekLabels); ?>,
        [{
            label: 'Hours',
            data: <?php echo json_encode($hoursData); ?>,
            borderColor: chartColors.blue,
            backgroundColor: chartColors.blueLight,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: chartColors.blue,
            pointRadius: 5
        }]
    ));

    // Tasks by Status Doughnut
    new Chart(document.getElementById('statusChart').getContext('2d'), createChartConfig('doughnut',
        <?php echo json_encode($statusLabels); ?>,
        [{ data: <?php echo json_encode($statusData); ?>, backgroundColor: <?php echo json_encode($statusColors); ?>, borderWidth: 0, hoverOffset: 8 }],
        { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: '#a0a0b0', padding: 16 } } } }
    ));

    // Tasks by Priority Doughnut
    new Chart(document.getElementById('priorityChart').getContext('2d'), createChartConfig('doughnut',
        <?php echo json_encode($priorityLabels); ?>,
        [{ data: <?php echo json_encode($priorityData); ?>, backgroundColor: <?php echo json_encode($priorityColors); ?>, borderWidth: 0, hoverOffset: 8 }],
        { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { color: '#a0a0b0', padding: 16 } } } }
    ));
});
</script>
