<?php
/**
 * Create Task
 * Form to create a new task with optional project assignment.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Create Task';
$userId = currentUserId();
$error = '';

// Get user's projects for the dropdown
$stmt = $pdo->prepare("
    SELECT p.id, p.title FROM projects p
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE p.owner_id = ? OR pm.user_id = ?
    GROUP BY p.id
");
$stmt->execute([$userId, $userId, $userId]);
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
            INSERT INTO tasks (user_id, project_id, title, description, priority, status, category, deadline) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $projectId, $title, $description, $priority, $status, $category, $deadline ?: null]);

        $taskId = $pdo->lastInsertId();
        logActivity($pdo, $userId, "Created task: $title", 'task', $taskId);
        setFlash('success', 'Task created successfully!');
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
                <h1>Create Task ✏️</h1>
                <p>Add a new task to your list</p>
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
                           placeholder="What needs to be done?" value="<?php echo e($_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"
                              placeholder="Add details about this task..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select name="priority" id="priority" class="form-control">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" name="category" id="category" class="form-control"
                               placeholder="e.g., Design, Development" value="<?php echo e($_POST['category'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" name="deadline" id="deadline" class="form-control"
                               value="<?php echo e($_POST['deadline'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="project_id">Project (optional)</label>
                    <select name="project_id" id="project_id" class="form-control">
                        <option value="">No Project</option>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo e($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Create Task</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
