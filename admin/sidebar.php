<?php
// Shared sidebar for admin pages
$current = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">💉</div>
        <div class="brand-text">VMS Admin <small>Admin Panel</small></div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="dashboard.php" class="nav-link <?= $current === 'dashboard.php' ? 'active' : '' ?>"><span class="icon">📊</span> Dashboard</a>

        <div class="nav-section">Users</div>
        <a href="users.php" class="nav-link <?= $current === 'users.php' ? 'active' : '' ?>"><span class="icon">👥</span> Sab Users</a>
        <a href="add_user.php" class="nav-link <?= $current === 'add_user.php' ? 'active' : '' ?>"><span class="icon">➕</span> User Add</a>

        <div class="nav-section">Vaccines</div>
        <a href="vaccines.php" class="nav-link <?= $current === 'vaccines.php' ? 'active' : '' ?>"><span class="icon">💊</span> Sab Vaccines</a>
        <a href="add_vaccine.php" class="nav-link <?= $current === 'add_vaccine.php' ? 'active' : '' ?>"><span class="icon">➕</span> Vaccine Add</a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">👤</div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role">🛡️ Administrator</div>
            </div>
        </div>
        <a href="/user/index.php" class="btn-switch">🔄 View User.</a>
        <a href="/logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</aside>
