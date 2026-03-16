<?php
/**
 * Create Goal
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Create Goal';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'short-term';
    $targetDate = $_POST['target_date'] ?? null;
    $progress = max(0, min(100, (int)($_POST['progress'] ?? 0)));

    if (empty($title)) {
        $error = 'Goal title is required.';
    } else {
        $status = ($progress >= 100) ? 'completed' : 'active';
        $stmt = $pdo->prepare("INSERT INTO goals (user_id, title, description, type, target_date, progress, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([currentUserId(), $title, $description, $type, $targetDate ?: null, $progress, $status]);

        logActivity($pdo, currentUserId(), "Created goal: $title", 'goal', $pdo->lastInsertId());
        setFlash('success', 'Goal created!');
        redirect('/modules/goals/list.php');
    }
}

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">

        <div class="page-header">
            <div><h1>Create Goal 🎯</h1></div>
            <a href="/modules/goals/list.php" class="btn btn-outline">← Back</a>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="title">Goal Title *</label>
                    <input type="text" name="title" id="title" class="form-control"
                           placeholder="What do you want to achieve?" value="<?php echo e($_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"
                              placeholder="Describe your goal..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="short-term">Short-term</option>
                            <option value="long-term">Long-term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="target_date">Target Date</label>
                        <input type="date" name="target_date" id="target_date" class="form-control"
                               value="<?php echo e($_POST['target_date'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="progress">Progress (%)</label>
                    <input type="number" name="progress" id="progress" class="form-control"
                           min="0" max="100" value="<?php echo e($_POST['progress'] ?? '0'); ?>">
                </div>

                <button type="submit" class="btn btn-primary">Create Goal</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
