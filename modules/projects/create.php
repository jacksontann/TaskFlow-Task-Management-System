<?php
/**
 * Create Project
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Create Project';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if (empty($title)) {
        $error = 'Project title is required.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO projects (owner_id, title, description, status, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([currentUserId(), $title, $description, $status, $startDate ?: null, $endDate ?: null]);

        $projectId = $pdo->lastInsertId();

        // Add owner as a member automatically
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");
        $stmt->execute([$projectId, currentUserId()]);

        logActivity($pdo, currentUserId(), "Created project: $title", 'project', $projectId);
        setFlash('success', 'Project created successfully!');
        redirect('/modules/projects/list.php');
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
                <h1>Create Project 📁</h1>
                <p>Start a new project</p>
            </div>
            <a href="/modules/projects/list.php" class="btn btn-outline">← Back</a>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="title">Project Title *</label>
                    <input type="text" name="title" id="title" class="form-control"
                           placeholder="Project name" value="<?php echo e($_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"
                              placeholder="What is this project about?"><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                               value="<?php echo e($_POST['start_date'] ?? date('Y-m-d')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                               value="<?php echo e($_POST['end_date'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Create Project</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
