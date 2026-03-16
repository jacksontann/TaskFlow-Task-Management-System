<?php
/**
 * Edit Project
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Edit Project';
$error = '';
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/modules/projects/list.php');

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('error', 'Project not found.');
    redirect('/modules/projects/list.php');
}

if (!isAdmin() && $project['owner_id'] !== currentUserId()) {
    setFlash('error', 'You can only edit your own projects.');
    redirect('/modules/projects/list.php');
}

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
        $stmt = $pdo->prepare("UPDATE projects SET title = ?, description = ?, status = ?, start_date = ?, end_date = ? WHERE id = ?");
        $stmt->execute([$title, $description, $status, $startDate ?: null, $endDate ?: null, $id]);

        logActivity($pdo, currentUserId(), "Updated project: $title", 'project', $id);
        setFlash('success', 'Project updated!');
        redirect('/modules/projects/view.php?id=' . $id);
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
                <h1>Edit Project ✏️</h1>
            </div>
            <a href="/modules/projects/view.php?id=<?php echo $id; ?>" class="btn btn-outline">← Back</a>
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
                           value="<?php echo e($_POST['title'] ?? $project['title']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" class="form-control"><?php echo e($_POST['description'] ?? $project['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <?php foreach (['active', 'completed', 'archived'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($project['status'] === $s) ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                               value="<?php echo e($project['start_date']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                               value="<?php echo e($project['end_date']); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
