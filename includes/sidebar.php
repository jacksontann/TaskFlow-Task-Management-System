<?php
/**
 * Sidebar Navigation
 * Role-aware navigation menu. Admin sees extra links.
 */
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <span class="logo-icon">⚡</span>
            <span class="logo-text">TaskFlow</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <!-- Dashboard -->
            <li>
                <a href="<?php echo getDashboardUrl(); ?>" class="<?php echo strpos($currentPage, 'dashboard') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">📊</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            <!-- Tasks -->
            <li>
                <a href="/modules/tasks/list.php" class="<?php echo strpos($currentPage, 'tasks') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">✅</span>
                    <span class="nav-text">Tasks</span>
                </a>
            </li>

            <!-- Projects -->
            <li>
                <a href="/modules/projects/list.php" class="<?php echo strpos($currentPage, 'projects') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">📁</span>
                    <span class="nav-text">Projects</span>
                </a>
            </li>

            <!-- Goals -->
            <li>
                <a href="/modules/goals/list.php" class="<?php echo strpos($currentPage, 'goals') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">🎯</span>
                    <span class="nav-text">Goals</span>
                </a>
            </li>

            <!-- Time Tracking -->
            <li>
                <a href="/modules/time_logs/list.php" class="<?php echo strpos($currentPage, 'time_logs') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">⏱️</span>
                    <span class="nav-text">Time Tracking</span>
                </a>
            </li>

            <!-- Analytics -->
            <li>
                <?php if (isAdmin()): ?>
                <a href="/modules/analytics/admin.php" class="<?php echo strpos($currentPage, 'analytics') !== false ? 'active' : ''; ?>">
                <?php else: ?>
                <a href="/modules/analytics/user.php" class="<?php echo strpos($currentPage, 'analytics') !== false ? 'active' : ''; ?>">
                <?php endif; ?>
                    <span class="nav-icon">📈</span>
                    <span class="nav-text">Analytics</span>
                </a>
            </li>

            <?php if (isAdmin()): ?>
            <!-- Admin Section -->
            <li class="nav-section-title">Admin</li>
            <li>
                <a href="/admin/users.php" class="<?php echo strpos($currentPage, 'admin/users') !== false ? 'active' : ''; ?>">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Users</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </nav>
</aside>
