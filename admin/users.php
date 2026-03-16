<?php
/**
 * Admin - User Management
 * View, search, and manage all users.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$pageTitle = 'Manage Users';

// Handle user deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    // Don't allow deleting yourself
    if ($deleteId !== currentUserId()) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        logActivity($pdo, currentUserId(), "Deleted user #$deleteId", 'user', $deleteId);
        setFlash('success', 'User deleted successfully.');
    } else {
        setFlash('error', 'You cannot delete your own account.');
    }
    redirect('/admin/users.php');
}

// Handle status toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $toggleId = (int)$_GET['toggle'];
    if ($toggleId !== currentUserId()) {
        $stmt = $pdo->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$toggleId]);
        $current = $stmt->fetchColumn();
        $newStatus = ($current === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $toggleId]);
        logActivity($pdo, currentUserId(), "Changed user #$toggleId status to $newStatus", 'user', $toggleId);
        setFlash('success', "User status updated to $newStatus.");
    }
    redirect('/admin/users.php');
}

// Search
$search = trim($_GET['search'] ?? '');
$where = '';
$params = [];
if ($search) {
    $where = "WHERE name LIKE ? OR email LIKE ?";
    $params = ["%$search%", "%$search%"];
}

$users = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$users->execute($params);
$users = $users->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Manage Users 👥</h1>
                <p>View and manage all registered users</p>
            </div>
        </div>

        <!-- Search -->
        <div class="filters-bar">
            <form method="GET" action="" style="display:flex; gap:12px; flex:1;">
                <input type="text" name="search" class="form-control search-input" 
                       placeholder="Search by name or email..." value="<?php echo e($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
                <?php if ($search): ?>
                    <a href="/admin/users.php" class="btn btn-outline btn-sm">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Users Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo e($u['name']); ?></td>
                                <td><?php echo e($u['email']); ?></td>
                                <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-high' : 'badge-active'; ?>"><?php echo e($u['role']); ?></span></td>
                                <td><span class="badge <?php echo statusBadge($u['status']); ?>"><?php echo e($u['status']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/admin/edit_user.php?id=<?php echo $u['id']; ?>" class="btn btn-sm btn-outline">Edit</a>
                                        <?php if ($u['id'] !== currentUserId()): ?>
                                            <a href="/admin/users.php?toggle=<?php echo $u['id']; ?>" 
                                               class="btn btn-sm <?php echo $u['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?>">
                                                <?php echo $u['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </a>
                                            <a href="/admin/users.php?delete=<?php echo $u['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirmDelete('Delete this user and all their data?')">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
