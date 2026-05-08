<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$where = "WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $where .= " AND (name LIKE ? OR manufacturer LIKE ? OR description LIKE ?)";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = "sss";
}

if ($filter === 'available') {
    $where .= " AND quantity > 0";
} elseif ($filter === 'expiring') {
    $where .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";
}

if ($params) {
    $stmt = $conn->prepare("SELECT * FROM vaccines $where ORDER BY name ASC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $vaccines = $stmt->get_result();
} else {
    $vaccines = $conn->query("SELECT * FROM vaccines $where ORDER BY name ASC");
}

$total = $conn->query("SELECT COUNT(*) as c FROM vaccines")->fetch_assoc()['c'];
$available = $conn->query("SELECT COUNT(*) as c FROM vaccines WHERE quantity > 0")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccines — VMS</title>
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
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

        /* Header */
        .header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 40px;
            position: sticky; top: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            height: 70px;
        }

        .header-brand {
            display: flex; align-items: center; gap: 12px;
            font-family:'Syne',sans-serif; font-size:18px; font-weight:700; color:#fff;
        }

        .brand-icon {
            width:40px; height:40px;
            background:linear-gradient(135deg, var(--primary), var(--accent));
            border-radius:10px; display:flex; align-items:center; justify-content:center;
            font-size:18px;
        }

        .header-actions { display:flex; align-items:center; gap:12px; }

        .user-pill {
            display: flex; align-items: center; gap: 8px;
            padding: 7px 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 13px;
        }

        .btn-sm {
            padding: 7px 14px; border-radius: 8px;
            font-size: 13px; cursor: pointer;
            text-decoration: none; border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .btn-sm-danger { background: rgba(214,48,49,0.1); color:#ff7675; border-color:rgba(214,48,49,0.2); }
        .btn-sm-danger:hover { background:rgba(214,48,49,0.2); }
        .btn-sm-admin { background:rgba(9,132,227,0.1); color:#74b9ff; border-color:rgba(9,132,227,0.2); }
        .btn-sm-admin:hover { background:rgba(9,132,227,0.2); }

        /* Hero */
        .hero {
            text-align: center;
            padding: 70px 40px 50px;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(0,184,148,0.08) 0%, transparent 70%);
            top: 50%; left: 50%; transform: translate(-50%, -50%);
            pointer-events: none;
        }

        .hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: 48px; font-weight: 800;
            color: #fff; margin-bottom: 12px;
        }

        .hero h1 span { color: var(--primary); }

        .hero p { color: var(--text-muted); font-size: 16px; max-width: 500px; margin: 0 auto 40px; }

        /* Search */
        .search-section {
            max-width: 700px; margin: 0 auto;
        }

        .search-form {
            display: flex; gap: 12px;
        }

        .search-input {
            flex: 1; padding: 14px 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text); font-size: 14px;
            outline: none; transition: all 0.2s;
            font-family: 'DM Sans', sans-serif;
        }

        .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,184,148,0.1); }

        .btn-search {
            padding: 14px 24px;
            background: linear-gradient(135deg, var(--primary), #00a381);
            color: #fff; border: none; border-radius: 12px;
            font-size: 14px; font-weight: 500; cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: all 0.2s;
        }

        .btn-search:hover { transform: translateY(-1px); }

        /* Stats Bar */
        .stats-bar {
            display: flex; gap: 16px; justify-content: center;
            padding: 0 40px 40px; flex-wrap: wrap;
        }

        .stat-pill {
            padding: 8px 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 30px;
            font-size: 13px; color: var(--text-muted);
        }

        .stat-pill strong { color: var(--primary); }

        /* Filter Tabs */
        .filter-section {
            max-width: 1200px; margin: 0 auto;
            padding: 0 40px 24px;
            display: flex; gap: 10px; flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 18px; border-radius: 30px; border: 1px solid var(--border);
            background: var(--surface); color: var(--text-muted);
            font-size: 13px; cursor: pointer; text-decoration: none;
            transition: all 0.2s;
        }

        .filter-btn.active { background: rgba(0,184,148,0.1); color: var(--primary); border-color: rgba(0,184,148,0.3); }
        .filter-btn:hover:not(.active) { background: var(--surface2); color: var(--text); }

        /* Vaccine Grid */
        .vaccines-section { max-width:1200px; margin:0 auto; padding: 0 40px 60px; }

        .vaccines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .vaccine-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
        }

        .vaccine-card:hover {
            border-color: rgba(0,184,148,0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .vaccine-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            border-radius: 16px 16px 0 0;
        }

        .vaccine-card.out-of-stock { opacity: 0.6; }
        .vaccine-card.out-of-stock::before { background: var(--text-muted); }

        .card-top {
            display: flex; justify-content: space-between; align-items: flex-start;
            margin-bottom: 16px;
        }

        .vaccine-icon { font-size: 32px; }

        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-green { background:rgba(0,184,148,0.1); color:var(--primary); border:1px solid rgba(0,184,148,0.2); }
        .badge-red { background:rgba(214,48,49,0.1); color:#ff7675; border:1px solid rgba(214,48,49,0.2); }
        .badge-yellow { background:rgba(253,203,110,0.1); color:var(--warning); border:1px solid rgba(253,203,110,0.2); }
        .badge-gray { background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border); }

        .vaccine-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 700; color: #fff;
            margin-bottom: 4px;
        }

        .vaccine-manufacturer { font-size: 13px; color: var(--text-muted); margin-bottom: 14px; }

        .vaccine-desc {
            font-size: 13px; color: var(--text-muted); line-height: 1.6;
            margin-bottom: 18px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .vaccine-meta {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 10px; margin-bottom: 16px;
        }

        .meta-item {
            background: var(--surface2);
            border-radius: 10px; padding: 10px 14px;
        }

        .meta-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; color: var(--text-muted); margin-bottom: 3px; }
        .meta-value { font-size: 15px; font-weight: 600; color: var(--text); }
        .meta-value.price { color: var(--primary); }

        .expiry-row {
            display: flex; align-items: center; justify-content: space-between;
            font-size: 12px; color: var(--text-muted);
            padding-top: 14px;
            border-top: 1px solid var(--border);
        }

        .empty-state { text-align:center; padding:80px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:60px; display:block; margin-bottom:16px; }
        .empty-state p { font-size:16px; }

        .result-count {
            font-size: 13px; color: var(--text-muted);
            padding: 0 40px 20px; max-width: 1200px; margin: 0 auto;
        }
    </style>
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="header-brand">
        <div class="brand-icon">💉</div>
        <span>VMS <span style="color:var(--primary)">Vaccines</span></span>
    </div>
    <div class="header-actions">
        <div class="user-pill">👤 <?= htmlspecialchars($_SESSION['full_name']) ?></div>
        <?php if (isAdmin()): ?>
            <a href="/admin/dashboard.php" class="btn-sm btn-sm-admin">🛡️ Admin Panel</a>
        <?php endif; ?>
        <a href="/logout.php" class="btn-sm btn-sm-danger">🚪 Logout</a>
    </div>
</header>

<!-- Hero -->
<div class="hero">
    <h1>Vaccine <span>Inventory</span></h1>
    <p>View available vaccines with stock, price, and expiry details.</p>

    <div class="search-section">
        <form class="search-form" method="GET">
            <input class="search-input" type="text" name="search" placeholder="🔍 Search for vaccines or manufacturers...." value="<?= htmlspecialchars($search) ?>">
            <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
            <button type="submit" class="btn-search">Search</button>
        </form>
    </div>
</div>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stat-pill">💊 Total Vaccines: <strong><?= $total ?></strong></div>
    <div class="stat-pill">✅ Available: <strong><?= $available ?></strong></div>
    <div class="stat-pill">📅 Last Updated: <strong>Today</strong></div>
</div>

<!-- Filter Tabs -->
<div class="filter-section">
    <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?>filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">🔬 All Vaccines</a>
    <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?>filter=available" class="filter-btn <?= $filter === 'available' ? 'active' : '' ?>">✅ Available Only</a>
    <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?>filter=expiring" class="filter-btn <?= $filter === 'expiring' ? 'active' : '' ?>">⚠️ Expiring Soon</a>
</div>

<!-- Vaccines Grid -->
<div class="vaccines-section">
    <div class="result-count"><?= $vaccines->num_rows ?> vaccine<?= $vaccines->num_rows !== 1 ? 's' : '' ?> mili<?= $search ? ' "'.htmlspecialchars($search).'" ke liye' : '' ?></div>

    <?php if ($vaccines->num_rows === 0): ?>
        <div class="empty-state">
            <span class="icon">🔍</span>
            <p>No vaccine found.<?= $search ? ' For this search.' : '' ?></p>
        </div>
    <?php else: ?>
    <div class="vaccines-grid">
    <?php while ($v = $vaccines->fetch_assoc()):
        $exp = strtotime($v['expiry_date']);
        $diff = ($exp - time()) / (60*60*24);
        $badge_class = $v['quantity'] <= 0 ? 'badge-gray' : ($diff < 0 ? 'badge-red' : ($diff < 90 ? 'badge-yellow' : 'badge-green'));
        $badge_text  = $v['quantity'] <= 0 ? 'Out of Stock' : ($diff < 0 ? 'Expired' : ($diff < 90 ? 'Expiring Soon' : 'Available'));
        $card_class  = $v['quantity'] <= 0 ? 'out-of-stock' : '';
    ?>
        <div class="vaccine-card <?= $card_class ?>">
            <div class="card-top">
                <span class="vaccine-icon">💉</span>
                <span class="badge <?= $badge_class ?>"><?= $badge_text ?></span>
            </div>
            <div class="vaccine-name"><?= htmlspecialchars($v['name']) ?></div>
            <div class="vaccine-manufacturer">🏭 <?= htmlspecialchars($v['manufacturer']) ?></div>
            <?php if ($v['description']): ?>
                <div class="vaccine-desc"><?= htmlspecialchars($v['description']) ?></div>
            <?php endif; ?>
            <div class="vaccine-meta">
                <div class="meta-item">
                    <div class="meta-label">Price</div>
                    <div class="meta-value price">Rs <?= number_format($v['price'], 0) ?></div>
                </div>
                <div class="meta-item">
                    <div class="meta-label">Stock</div>
                    <div class="meta-value"><?= number_format($v['quantity']) ?> units</div>
                </div>
            </div>
            <div class="expiry-row">
                <span>📅 Expiry: <?= date('d M Y', $exp) ?></span>
                <?php if ($diff > 0): ?>
                    <span><?= round($diff) ?> Days remaining.</span>
                <?php else: ?>
                    <span style="color:#ff7675">Expired</span>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
