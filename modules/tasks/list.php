<?php
/**
 * Task List
 * Displays all tasks with search, filter, and sort options.
 * Users see only their own tasks; admin sees all tasks.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

$pageTitle = 'Tasks';
$userId = currentUserId();

// Get filter/search params
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$sort = $_GET['sort'] ?? 'latest';

// Build query
$where = [];
$params = [];

// Admin sees all, user sees only their own
if (!isAdmin()) {
    $where[] = "t.user_id = ?";
    $params[] = $userId;
}

if ($search) {
    $where[] = "t.title LIKE ?";
    $params[] = "%$search%";
}
if ($filterStatus) {
    $where[] = "t.status = ?";
    $params[] = $filterStatus;
}
if ($filterPriority) {
    $where[] = "t.priority = ?";
    $params[] = $filterPriority;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderBy = $sort === 'deadline' ? 't.deadline ASC' : 't.created_at DESC';

$sql = "SELECT t.*, u.name as user_name FROM tasks t JOIN users u ON t.user_id = u.id $whereClause ORDER BY $orderBy";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Tasks ✅</h1>
                <p><?php echo count($tasks); ?> task<?php echo count($tasks) !== 1 ? 's' : ''; ?> found</p>
            </div>
            <a href="/modules/tasks/create.php" class="btn btn-primary">+ New Task</a>
        </div>

        <!-- Filters -->
        <div class="filters-bar">
            <form method="GET" action="" style="display:flex; gap:12px; flex-wrap:wrap; width:100%; align-items:center;">
                <input type="text" name="search" class="form-control search-input" 
                       placeholder="Search tasks..." value="<?php echo e($search); ?>">
                <select name="status" class="form-control">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>
                <select name="priority" class="form-control">
                    <option value="">All Priority</option>
                    <option value="low" <?php echo $filterPriority === 'low' ? 'selected' : ''; ?>>Low</option>
                    <option value="medium" <?php echo $filterPriority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="high" <?php echo $filterPriority === 'high' ? 'selected' : ''; ?>>High</option>
                </select>
                <select name="sort" class="form-control">
                    <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest First</option>
                    <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>By Deadline</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <a href="/modules/tasks/list.php" class="btn btn-outline btn-sm">Clear</a>
            </form>
        </div>

        <!-- Task Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <?php if (isAdmin()): ?><th>User</th><?php endif; ?>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tasks)): ?>
                        <tr><td colspan="<?php echo isAdmin() ? 7 : 6; ?>" class="text-center text-muted">No tasks found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tasks as $task): ?>
                            <?php $isOverdue = $task['deadline'] && $task['deadline'] < date('Y-m-d') && $task['status'] !== 'completed'; ?>
                            <tr class="<?php echo $isOverdue ? 'overdue' : ''; ?>">
                                <td><a href="/modules/tasks/view.php?id=<?php echo $task['id']; ?>"><?php echo e($task['title']); ?></a></td>
                                <?php if (isAdmin()): ?><td><?php echo e($task['user_name']); ?></td><?php endif; ?>
                                <td><span class="badge <?php echo priorityBadge($task['priority']); ?>"><?php echo e($task['priority']); ?></span></td>
                                <td><span class="badge <?php echo statusBadge($task['status']); ?>"><?php echo str_replace('_', ' ', e($task['status'])); ?></span></td>
                                <td><?php echo e($task['category'] ?: '—'); ?></td>
                                <td>
                                    <?php if ($task['deadline']): ?>
                                        <span class="<?php echo $isOverdue ? 'text-danger' : ''; ?>">
                                            <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/modules/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <a href="/modules/tasks/delete.php?id=<?php echo $task['id']; ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirmDelete('Delete this task?')">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
