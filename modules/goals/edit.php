<?php
/**
 * Edit Goal
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Edit Goal';
$error = '';
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/goals/list.php');

$stmt = $pdo->prepare("SELECT * FROM goals WHERE id = ?");
$stmt->execute([$id]);
$goal = $stmt->fetch();

if (!$goal) {
    setFlash('error', 'Goal not found.');
    redirect('/modules/goals/list.php');
}

if (!isAdmin() && $goal['user_id'] !== currentUserId()) {
    setFlash('error', 'You can only edit your own goals.');
    redirect('/modules/goals/list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = $_POST['type'] ?? 'short-term';
    $targetDate = $_POST['target_date'] ?? null;
    $progress = max(0, min(100, (int)($_POST['progress'] ?? 0)));
    $status = ($progress >= 100) ? 'completed' : ($_POST['status'] ?? 'active');

    if (empty($title)) {
        $error = 'Goal title is required.';
    } else {
        $stmt = $pdo->prepare("UPDATE goals SET title = ?, description = ?, type = ?, target_date = ?, progress = ?, status = ? WHERE id = ?");
        $stmt->execute([$title, $description, $type, $targetDate ?: null, $progress, $status, $id]);

        logActivity($pdo, currentUserId(), "Updated goal: $title", 'goal', $id);
        setFlash('success', 'Goal updated!');
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
            <div><h1>Edit Goal ✏️</h1></div>
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
                           value="<?php echo e($_POST['title'] ?? $goal['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"><?php echo e($_POST['description'] ?? $goal['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="type">Type</label>
                        <select name="type" id="type" class="form-control">
                            <option value="short-term" <?php echo ($goal['type'] === 'short-term') ? 'selected' : ''; ?>>Short-term</option>
                            <option value="long-term" <?php echo ($goal['type'] === 'long-term') ? 'selected' : ''; ?>>Long-term</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="target_date">Target Date</label>
                        <input type="date" name="target_date" id="target_date" class="form-control"
                               value="<?php echo e($goal['target_date']); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="progress">Progress (%)</label>
                        <input type="number" name="progress" id="progress" class="form-control"
                               min="0" max="100" value="<?php echo e($goal['progress']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?php echo ($goal['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo ($goal['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
