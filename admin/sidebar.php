<?php $current = basename($_SERVER['PHP_SELF']); ?>

<!-- Mobile Topbar -->
<div class="topbar">
    <button class="hamburger" onclick="toggleSidebar()">☰</button>
    <div class="topbar-brand">💉 VMS Admin</div>
</div>

<!-- Overlay -->
<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">💉</div>
        <div class="brand-text">VMS Admin <small>Admin Panel</small></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <a href="dashboard.php" class="nav-link <?= $current==='dashboard.php'?'active':'' ?>"><span class="icon">📊</span> Dashboard</a>
        <div class="nav-section">Orders</div>
        <a href="orders.php" class="nav-link <?= $current==='orders.php'?'active':'' ?>"><span class="icon">📦</span> All Orders</a>
        <div class="nav-section">Users</div>
        <a href="users.php" class="nav-link <?= $current==='users.php'?'active':'' ?>"><span class="icon">👥</span> All Users</a>
        <a href="add_user.php" class="nav-link <?= $current==='add_user.php'?'active':'' ?>"><span class="icon">➕</span> Add User</a>
        <div class="nav-section">Vaccines</div>
        <a href="vaccines.php" class="nav-link <?= $current==='vaccines.php'?'active':'' ?>"><span class="icon">💊</span> All Vaccines</a>
        <a href="add_vaccine.php" class="nav-link <?= $current==='add_vaccine.php'?'active':'' ?>"><span class="icon">➕</span> Add Vaccine</a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar">👤</div>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($_SESSION['full_name']) ?></div>
                <div class="role">🛡️ Administrator</div>
            </div>
        </div>
        <a href="/user/index.php" class="btn-switch">🔄 View User</a>
        <a href="/logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</aside>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
