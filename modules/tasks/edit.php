<?php
/**
 * Edit Task
 * Edit an existing task. Users can only edit their own; admin can edit any.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Edit Task';
$userId = currentUserId();
$error = '';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/tasks/list.php');

// Fetch task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->execute([$id]);
$task = $stmt->fetch();

if (!$task) {
    setFlash('error', 'Task not found.');
    redirect('/modules/tasks/list.php');
}

// Check ownership (unless admin)
if (!isAdmin() && $task['user_id'] !== $userId) {
    setFlash('error', 'You do not have permission to edit this task.');
    redirect('/modules/tasks/list.php');
}

// Get user's projects
$stmt = $pdo->prepare("
    SELECT p.id, p.title FROM projects p
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE p.owner_id = ? OR pm.user_id = ?
    GROUP BY p.id
");
$stmt->execute([$task['user_id'], $task['user_id'], $task['user_id']]);
$projects = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'pending';
    $category = trim($_POST['category'] ?? '');
    $deadline = $_POST['deadline'] ?? null;
    $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

    if (empty($title)) {
        $error = 'Task title is required.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE tasks SET project_id = ?, title = ?, description = ?, priority = ?, status = ?, category = ?, deadline = ?
            WHERE id = ?
        ");
        $stmt->execute([$projectId, $title, $description, $priority, $status, $category, $deadline ?: null, $id]);

        logActivity($pdo, $userId, "Updated task: $title", 'task', $id);
        setFlash('success', 'Task updated successfully!');
        redirect('/modules/tasks/list.php');
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
                <h1>Edit Task ✏️</h1>
                <p>Editing: <?php echo e($task['title']); ?></p>
            </div>
            <a href="/modules/tasks/list.php" class="btn btn-outline">← Back to Tasks</a>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="title">Task Title *</label>
                    <input type="text" name="title" id="title" class="form-control"
                           value="<?php echo e($_POST['title'] ?? $task['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"><?php echo e($_POST['description'] ?? $task['description']); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select name="priority" id="priority" class="form-control">
                            <?php foreach (['low', 'medium', 'high'] as $p): ?>
                                <option value="<?php echo $p; ?>" <?php echo ($task['priority'] === $p) ? 'selected' : ''; ?>><?php echo ucfirst($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php foreach (['pending', 'in_progress', 'completed'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo ($task['status'] === $s) ? 'selected' : ''; ?>><?php echo ucfirst(str_replace('_', ' ', $s)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" name="category" id="category" class="form-control"
                               value="<?php echo e($_POST['category'] ?? $task['category']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" name="deadline" id="deadline" class="form-control"
                               value="<?php echo e($_POST['deadline'] ?? $task['deadline']); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="project_id">Project (optional)</label>
                    <select name="project_id" id="project_id" class="form-control">
                        <option value="">No Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($task['project_id'] == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo e($p['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
