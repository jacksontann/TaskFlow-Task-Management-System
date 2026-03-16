<?php
/**
 * Manage Project Members
 * Add or remove members from a project.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/database.php';

requireLogin();

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
    setFlash('error', 'Only the project owner can manage members.');
    redirect('/modules/projects/view.php?id=' . $id);
}

$pageTitle = 'Manage Members';

// Handle add member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member'])) {
    validateCsrf();
    $memberEmail = trim($_POST['email'] ?? '');
    $memberRole = $_POST['member_role'] ?? 'member';

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$memberEmail]);
    $memberUser = $stmt->fetch();

    if (!$memberUser) {
        setFlash('error', 'User not found with that email.');
    } else {
        // Check if already a member
        $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$id, $memberUser['id']]);
        if ($stmt->fetch()) {
            setFlash('warning', 'User is already a member.');
        } else {
            $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$id, $memberUser['id'], $memberRole]);
            logActivity($pdo, currentUserId(), "Added member to project: " . $project['title'], 'project', $id);
            setFlash('success', 'Member added!');
        }
    }
    redirect('/modules/projects/members.php?id=' . $id);
}

// Handle remove member
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    // Don't remove the owner
    if ($removeId !== $project['owner_id']) {
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$id, $removeId]);
        setFlash('success', 'Member removed.');
    }
    redirect('/modules/projects/members.php?id=' . $id);
}

// Fetch current members
$members = $pdo->prepare("
    SELECT pm.*, u.name, u.email 
    FROM project_members pm 
    JOIN users u ON pm.user_id = u.id 
    WHERE pm.project_id = ?
");
$members->execute([$id]);
$members = $members->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="app-container">
    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content">
        <?php displayFlash(); ?>

        <div class="page-header">
            <div>
                <h1>Manage Members 👥</h1>
                <p>Project: <?php echo e($project['title']); ?></p>
            </div>
            <a href="/modules/projects/view.php?id=<?php echo $id; ?>" class="btn btn-outline">← Back to Project</a>
        </div>

        <!-- Add Member Form -->
        <div class="card mb-3">
            <div class="card-header"><h3>Add Member</h3></div>
            <div class="card-body">
                <form method="POST" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                    <?php echo csrfField(); ?>
                    <div class="form-group" style="flex:1; margin-bottom:0;">
                        <label>User Email</label>
                        <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Role</label>
                        <select name="member_role" class="form-control">
                            <option value="member">Member</option>
                            <option value="viewer">Viewer</option>
                        </select>
                    </div>
                    <button type="submit" name="add_member" class="btn btn-primary">Add</button>
                </form>
            </div>
        </div>

        <!-- Current Members -->
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?php echo e($m['name']); ?></td>
                            <td><?php echo e($m['email']); ?></td>
                            <td><span class="badge badge-active"><?php echo e($m['role']); ?></span></td>
                            <td><?php echo timeAgo($m['joined_at']); ?></td>
                            <td>
                                <?php if ($m['user_id'] !== $project['owner_id']): ?>
                                    <a href="/modules/projects/members.php?id=<?php echo $id; ?>&remove=<?php echo $m['user_id']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirmDelete('Remove this member?')">Remove</a>
                                <?php else: ?>
                                    <span class="badge badge-medium">Owner</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
