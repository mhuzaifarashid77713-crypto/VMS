<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$total_users     = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='user'")->fetch_assoc()['c'];
$total_vaccines  = $conn->query("SELECT COUNT(*) as c FROM vaccines")->fetch_assoc()['c'];
$total_qty       = $conn->query("SELECT SUM(quantity) as c FROM vaccines")->fetch_assoc()['c'] ?? 0;
$expiring_soon   = $conn->query("SELECT COUNT(*) as c FROM vaccines WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)")->fetch_assoc()['c'];
$recent_vaccines = $conn->query("SELECT * FROM vaccines ORDER BY created_at DESC LIMIT 5");
$recent_users    = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — VMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #00b894;
            --accent: #0984e3;
            --warning: #fdcb6e;
            --danger: #d63031;
            --bg: #0a0e1a;
            --surface: #111827;
            --surface2: #1a2235;
            --border: rgba(255,255,255,0.07);
            --text: #e8eaf0;
            --text-muted: #8892a4;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            z-index: 200;
            transition: transform 0.3s ease;
        }
        .sidebar.hidden { transform: translateX(-100%); }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }
        .brand-logo {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .brand-text { font-family:'Syne',sans-serif; font-weight:700; font-size:16px; color:#fff; line-height:1.2; }
        .brand-text small { font-family:'DM Sans',sans-serif; font-weight:400; font-size:11px; color:var(--primary); display:block; }

        .sidebar-nav { padding: 16px 12px; flex: 1; overflow-y: auto; }
        .nav-section { font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:1.2px; color:var(--text-muted); padding:8px 12px 6px; margin-top:10px; }
        .nav-link {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 14px; border-radius: 10px;
            color: var(--text-muted); text-decoration: none;
            font-size: 14px; transition: all 0.2s; margin-bottom: 2px;
        }
        .nav-link:hover { background: var(--surface2); color: var(--text); }
        .nav-link.active { background: rgba(0,184,148,0.12); color: var(--primary); border: 1px solid rgba(0,184,148,0.2); }
        .nav-link .icon { font-size:18px; width:22px; text-align:center; }

        .sidebar-footer { padding: 16px 12px; border-top: 1px solid var(--border); }
        .user-card {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; background: var(--surface2);
            border-radius: 12px; margin-bottom: 10px;
        }
        .user-avatar {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
        }
        .user-info { flex:1; min-width:0; }
        .user-info .name { font-size:13px; font-weight:500; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .user-info .role { font-size:11px; color:var(--primary); }

        .btn-logout, .btn-switch {
            display: flex; align-items: center; gap: 8px;
            width: 100%; padding: 9px 14px;
            border: none; border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px; cursor: pointer;
            text-decoration: none; transition: all 0.2s; margin-bottom: 6px;
        }
        .btn-logout { background:rgba(214,48,49,0.1); color:#ff7675; border:1px solid rgba(214,48,49,0.2); }
        .btn-logout:hover { background:rgba(214,48,49,0.2); }
        .btn-switch { background:rgba(9,132,227,0.1); color:#74b9ff; border:1px solid rgba(9,132,227,0.2); }
        .btn-switch:hover { background:rgba(9,132,227,0.2); }

        /* ── Top Bar (mobile) ── */
        .topbar {
            display: none;
            position: fixed; top: 0; left: 0; right: 0;
            height: 60px; z-index: 150;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            align-items: center;
            padding: 0 16px;
            gap: 12px;
        }
        .topbar-brand { font-family:'Syne',sans-serif; font-weight:700; font-size:16px; color:#fff; flex:1; }
        .hamburger {
            background: none; border: none;
            color: var(--text); font-size: 24px;
            cursor: pointer; padding: 4px;
        }

        /* ── Overlay ── */
        .overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.6);
            z-index: 150;
        }
        .overlay.show { display: block; }

        /* ── Main ── */
        .main { margin-left: 260px; flex: 1; padding: 32px; }

        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-family:'Syne',sans-serif; font-size:28px; font-weight:700; color:#fff; }
        .page-header p { color:var(--text-muted); font-size:14px; margin-top:4px; }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px; margin-bottom: 28px;
        }
        .stat-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px;
            position: relative; overflow: hidden; transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-2px); }
        .stat-card::before {
            content: ''; position: absolute; top:0; right:0;
            width:80px; height:80px;
            border-radius: 0 16px 0 80px; opacity: 0.15;
        }
        .stat-card.green::before { background: var(--primary); }
        .stat-card.blue::before { background: var(--accent); }
        .stat-card.yellow::before { background: var(--warning); }
        .stat-card.red::before { background: var(--danger); }
        .stat-icon { font-size:28px; margin-bottom:14px; display:block; }
        .stat-value { font-family:'Syne',sans-serif; font-size:32px; font-weight:800; color:#fff; margin-bottom:4px; }
        .stat-label { font-size:13px; color:var(--text-muted); }

        /* Tables */
        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background:var(--surface); border:1px solid var(--border); border-radius:16px; overflow:hidden; }
        .card-header {
            padding: 18px 20px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-header h3 { font-family:'Syne',sans-serif; font-size:15px; font-weight:700; color:#fff; }
        .card-header a {
            font-size:12px; color:var(--primary); text-decoration:none;
            padding:5px 12px; background:rgba(0,184,148,0.1);
            border-radius:20px; border:1px solid rgba(0,184,148,0.2);
        }
        .table-wrap { overflow-x: auto; }
        table { width:100%; border-collapse:collapse; }
        th { padding:12px 16px; text-align:left; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); background:var(--surface2); }
        td { padding:13px 16px; font-size:13px; color:var(--text); border-bottom:1px solid var(--border); }
        tr:last-child td { border-bottom:none; }
        tr:hover td { background:rgba(255,255,255,0.02); }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-green { background:rgba(0,184,148,0.1); color:var(--primary); border:1px solid rgba(0,184,148,0.2); }
        .badge-red { background:rgba(214,48,49,0.1); color:#ff7675; border:1px solid rgba(214,48,49,0.2); }
        .badge-yellow { background:rgba(253,203,110,0.1); color:var(--warning); border:1px solid rgba(253,203,110,0.2); }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .tables-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .topbar { display: flex; }
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; padding: 80px 16px 24px; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .stat-value { font-size: 26px; }
            .page-header h1 { font-size: 22px; }
        }

        @media (max-width: 420px) {
            .stats-grid { grid-template-columns: 1fr 1fr; gap: 10px; }
            .stat-card { padding: 14px; }
            .stat-value { font-size: 22px; }
        }
    </style>
</head>
<body>

<!-- Mobile Top Bar -->
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
        <a href="dashboard.php" class="nav-link active"><span class="icon">📊</span> Dashboard</a>
        <div class="nav-section">Users</div>
        <a href="users.php" class="nav-link"><span class="icon">👥</span> All Users</a>
        <a href="add_user.php" class="nav-link"><span class="icon">➕</span> Add User</a>
        <div class="nav-section">Vaccines</div>
        <a href="vaccines.php" class="nav-link"><span class="icon">💊</span> All Vaccines</a>
        <a href="add_vaccine.php" class="nav-link"><span class="icon">➕</span> Add Vaccine</a>
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

<!-- Main -->
<main class="main">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Today's overview — everything at a glance.</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card green"><span class="stat-icon">💊</span><div class="stat-value"><?= $total_vaccines ?></div><div class="stat-label">Total Vaccines</div></div>
        <div class="stat-card blue"><span class="stat-icon">📦</span><div class="stat-value"><?= number_format($total_qty) ?></div><div class="stat-label">Total Quantity</div></div>
        <div class="stat-card yellow"><span class="stat-icon">👥</span><div class="stat-value"><?= $total_users ?></div><div class="stat-label">Registered Users</div></div>
        <div class="stat-card red"><span class="stat-icon">⚠️</span><div class="stat-value"><?= $expiring_soon ?></div><div class="stat-label">Expiring (90 days)</div></div>
    </div>

    <div class="tables-grid">
        <div class="card">
            <div class="card-header"><h3>💊 Recent Vaccines</h3><a href="vaccines.php">View All</a></div>
            <div class="table-wrap">
            <table><thead><tr><th>Name</th><th>Qty</th><th>Expiry</th></tr></thead><tbody>
            <?php while($v = $recent_vaccines->fetch_assoc()):
                $exp = strtotime($v['expiry_date']); $diff = ($exp - time()) / 86400;
                $badge = $diff < 30 ? 'badge-red' : ($diff < 90 ? 'badge-yellow' : 'badge-green');
            ?>
            <tr><td><?= htmlspecialchars($v['name']) ?></td><td><?= $v['quantity'] ?></td><td><span class="badge <?= $badge ?>"><?= date('M Y', $exp) ?></span></td></tr>
            <?php endwhile; ?>
            </tbody></table>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3>👥 Recent Users</h3><a href="users.php">View All</a></div>
            <div class="table-wrap">
            <table><thead><tr><th>Name</th><th>Email</th><th>Date</th></tr></thead><tbody>
            <?php while($u = $recent_users->fetch_assoc()): ?>
            <tr><td><?= htmlspecialchars($u['full_name']) ?></td><td style="color:var(--text-muted);font-size:12px"><?= htmlspecialchars($u['email']) ?></td><td style="color:var(--text-muted);font-size:12px"><?= date('d M', strtotime($u['created_at'])) ?></td></tr>
            <?php endwhile; ?>
            </tbody></table>
            </div>
        </div>
    </div>
</main>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('overlay').classList.toggle('show');
}
</script>
</body>
</html>
