<?php
/**
 * Top Navigation Bar
 * Shows app name, search placeholder, and user info.
 */
?>
<nav class="topnav">
    <div class="topnav-left">
        <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">&#9776;</button>
        <span class="topnav-title">TaskFlow</span>
    </div>
    <div class="topnav-right">
        <span class="topnav-user">
            <span class="user-avatar"><?php echo strtoupper(substr(currentUserName(), 0, 1)); ?></span>
            <span class="user-name"><?php echo e(currentUserName()); ?></span>
            <span class="user-role badge <?php echo isAdmin() ? 'badge-high' : 'badge-active'; ?>"><?php echo e($_SESSION['role'] ?? 'user'); ?></span>
        </span>
        <a href="/auth/logout.php" class="btn btn-sm btn-outline" title="Logout">Logout</a>
    </div>
</nav>
