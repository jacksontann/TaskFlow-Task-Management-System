<?php
/**
 * Add Time Log
 * Manually log time spent on a task.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Log Time';
$userId = currentUserId();
$error = '';

// Pre-select task if passed via query string
$preselectedTask = (int)($_GET['task_id'] ?? 0);

// Get user's tasks for dropdown
if (isAdmin()) {
    $tasks = $pdo->query("SELECT id, title FROM tasks ORDER BY created_at DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT id, title FROM tasks WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $tasks = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $taskId = (int)($_POST['task_id'] ?? 0);
    $durationMinutes = (int)($_POST['duration_minutes'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $date = $_POST['date'] ?? date('Y-m-d');
    $startTime = $_POST['start_time'] ?? '09:00';

    if (!$taskId) {
        $error = 'Please select a task.';
    } elseif ($durationMinutes < 1) {
        $error = 'Duration must be at least 1 minute.';
    } else {
        $startDateTime = $date . ' ' . $startTime . ':00';
        $endDateTime = date('Y-m-d H:i:s', strtotime($startDateTime) + ($durationMinutes * 60));

        $stmt = $pdo->prepare("INSERT INTO time_logs (task_id, user_id, start_time, end_time, duration_minutes, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$taskId, $userId, $startDateTime, $endDateTime, $durationMinutes, $notes ?: null]);

        // Get task title for activity log
        $stmt = $pdo->prepare("SELECT title FROM tasks WHERE id = ?");
        $stmt->execute([$taskId]);
        $taskTitle = $stmt->fetchColumn();

        logActivity($pdo, $userId, "Logged {$durationMinutes} minutes on task: {$taskTitle}", 'time_log', $pdo->lastInsertId());
        setFlash('success', 'Time logged successfully!');
        redirect('/modules/time_logs/list.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="page-header">
            <div>
                <h1>Log Time ⏱️</h1>
                <p>Manually record time spent on a task</p>
            </div>
            <a href="/modules/time_logs/list.php" class="btn btn-outline">← Back</a>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <!-- Timer Section -->
            <div style="text-align:center; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color);">
                <h3 style="margin-bottom: 8px;">⏱️ Quick Timer</h3>
                <p class="text-muted" style="font-size:0.85rem; margin-bottom:12px;">Start a timer and it will calculate the duration for you</p>
                <div class="timer-display" id="timerDisplay">00:00:00</div>
                <div class="timer-controls">
                    <button type="button" class="btn btn-success btn-sm" id="timerStart" onclick="startTimer()">▶ Start</button>
                    <button type="button" class="btn btn-danger btn-sm" id="timerStop" onclick="stopTimerAndFill()" disabled>⏹ Stop</button>
                    <button type="button" class="btn btn-outline btn-sm" onclick="resetTimer()">↺ Reset</button>
                </div>
            </div>

            <form method="POST">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="task_id">Task *</label>
                    <select name="task_id" id="task_id" class="form-control" required>
                        <option value="">Select a task...</option>
                        <?php foreach ($tasks as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($preselectedTask == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo e($t['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_minutes">Duration (minutes) *</label>
                        <input type="number" name="duration_minutes" id="duration_minutes" class="form-control"
                               min="1" placeholder="e.g., 60" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" name="date" id="date" class="form-control"
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="start_time">Start Time</label>
                    <input type="time" name="start_time" id="start_time" class="form-control"
                           value="<?php echo date('H:i'); ?>">
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea name="notes" id="notes" class="form-control" rows="3"
                              placeholder="What did you work on?"><?php echo e($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save Time Log</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

<script>
// When timer stops, fill in the duration field
function stopTimerAndFill() {
    const seconds = stopTimer();
    const minutes = Math.max(1, Math.round(seconds / 60));
    document.getElementById('duration_minutes').value = minutes;
}
</script>
