<?php
/**
 * Admin - Edit User
 * Edit a user's name, email, role, and status.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireAdmin();

$pageTitle = 'Edit User';

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('/admin/users.php');

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'User not found.');
    redirect('/admin/users.php');
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $status = $_POST['status'] ?? 'active';

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check email uniqueness (excluding current user)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $error = 'This email is already in use by another user.';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email, $role, $status, $id]);

            logActivity($pdo, currentUserId(), "Updated user: $name", 'user', $id);
            setFlash('success', 'User updated successfully.');
            redirect('/admin/users.php');
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Edit User ✏️</h1>
                <p>Editing: <?php echo e($user['name']); ?></p>
            </div>
            <a href="/admin/users.php" class="btn btn-outline">← Back to Users</a>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error"><?php echo e($error); ?></div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">
                <?php echo csrfField(); ?>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="<?php echo e($_POST['name'] ?? $user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control"
                           value="<?php echo e($_POST['email'] ?? $user['email']); ?>" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select name="role" id="role" class="form-control">
                            <option value="user" <?php echo ($user['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" <?php echo ($user['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo ($user['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Joined</label>
                    <input type="text" class="form-control" value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>" disabled>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
