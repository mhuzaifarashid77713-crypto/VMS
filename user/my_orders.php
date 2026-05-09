<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where  = $filter !== 'all' ? "AND o.status='$filter'" : "";

$stmt = $conn->prepare("
    SELECT o.*, v.name as vaccine_name, v.manufacturer
    FROM orders o
    JOIN vaccines v ON o.vaccine_id = v.id
    WHERE o.user_id = ? $where
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — VMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#00b894; --accent:#0984e3; --warning:#fdcb6e; --danger:#d63031; --bg:#0a0e1a; --surface:#111827; --surface2:#1a2235; --border:rgba(255,255,255,0.07); --text:#e8eaf0; --text-muted:#8892a4; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }
        .header { background:var(--surface); border-bottom:1px solid var(--border); padding:0 24px; position:sticky; top:0; z-index:100; display:flex; align-items:center; justify-content:space-between; height:65px; gap:12px; }
        .header-brand { display:flex; align-items:center; gap:10px; font-family:'Syne',sans-serif; font-size:17px; font-weight:700; color:#fff; }
        .brand-icon { width:38px; height:38px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; }
        .header-actions { display:flex; align-items:center; gap:8px; }
        .btn-sm { padding:6px 12px; border-radius:8px; font-size:12px; cursor:pointer; text-decoration:none; border:1px solid var(--border); transition:all 0.2s; }
        .btn-sm-danger { background:rgba(214,48,49,0.1); color:#ff7675; border-color:rgba(214,48,49,0.2); }
        .btn-sm-back { background:rgba(9,132,227,0.1); color:#74b9ff; border-color:rgba(9,132,227,0.2); }

        .container { max-width:900px; margin:0 auto; padding:40px 20px; }
        .page-title { font-family:'Syne',sans-serif; font-size:28px; font-weight:800; color:#fff; margin-bottom:6px; }
        .page-sub { color:var(--text-muted); font-size:14px; margin-bottom:28px; }

        .filter-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:24px; }
        .filter-btn { padding:7px 16px; border-radius:30px; border:1px solid var(--border); background:var(--surface); color:var(--text-muted); font-size:13px; text-decoration:none; transition:all 0.2s; }
        .filter-btn.active { background:rgba(0,184,148,0.1); color:var(--primary); border-color:rgba(0,184,148,0.3); }

        .order-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:20px; margin-bottom:16px; }
        .order-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:14px; }
        .order-title { font-family:'Syne',sans-serif; font-size:17px; font-weight:700; color:#fff; margin-bottom:4px; }
        .order-sub { font-size:13px; color:var(--text-muted); }
        .order-price { font-family:'Syne',sans-serif; font-size:20px; font-weight:800; color:var(--primary); }
        .order-details { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:10px; padding-top:14px; border-top:1px solid var(--border); }
        .detail-item { background:var(--surface2); border-radius:10px; padding:10px 14px; }
        .detail-label { font-size:10px; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); margin-bottom:4px; }
        .detail-value { font-size:13px; font-weight:600; color:var(--text); }

        .badge { display:inline-block; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; }
        .badge-yellow { background:rgba(253,203,110,0.1); color:var(--warning); border:1px solid rgba(253,203,110,0.2); }
        .badge-green { background:rgba(0,184,148,0.1); color:var(--primary); border:1px solid rgba(0,184,148,0.2); }
        .badge-red { background:rgba(214,48,49,0.1); color:#ff7675; border:1px solid rgba(214,48,49,0.2); }
        .badge-blue { background:rgba(9,132,227,0.1); color:#74b9ff; border:1px solid rgba(9,132,227,0.2); }

        .empty-state { text-align:center; padding:80px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:60px; display:block; margin-bottom:16px; }

        @media(max-width:600px) { .header { padding:0 14px; } .container { padding:24px 16px; } }
    </style>
</head>
<body>
<header class="header">
    <div class="header-brand">
        <div class="brand-icon">💉</div>
        <span>VMS <span style="color:var(--primary)">Orders</span></span>
    </div>
    <div class="header-actions">
        <a href="index.php" class="btn-sm btn-sm-back">← Back to Vaccines</a>
        <a href="/logout.php" class="btn-sm btn-sm-danger">🚪 Logout</a>
    </div>
</header>

<div class="container">
    <div class="page-title">📦 My Orders</div>
    <div class="page-sub">Track all your vaccine orders here</div>

    <div class="filter-tabs">
        <?php foreach(['all'=>'All Orders','pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'❌ Rejected','delivered'=>'📬 Delivered'] as $val=>$label): ?>
        <a href="?filter=<?= $val ?>" class="filter-btn <?= $filter===$val?'active':'' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <?php if ($orders->num_rows === 0): ?>
        <div class="empty-state"><span class="icon">📦</span><p>No orders found.</p></div>
    <?php else: while ($o = $orders->fetch_assoc()):
        $status_badge = match($o['status']) {
            'pending'   => 'badge-yellow',
            'approved'  => 'badge-green',
            'rejected'  => 'badge-red',
            'delivered' => 'badge-blue',
            default     => 'badge-gray'
        };
        $status_icon = match($o['status']) {
            'pending'   => '⏳',
            'approved'  => '✅',
            'rejected'  => '❌',
            'delivered' => '📬',
            default     => '📦'
        };
    ?>
    <div class="order-card">
        <div class="order-header">
            <div>
                <div class="order-title">💉 <?= htmlspecialchars($o['vaccine_name']) ?></div>
                <div class="order-sub">🏭 <?= htmlspecialchars($o['manufacturer']) ?></div>
            </div>
            <div style="text-align:right;">
                <div class="order-price">Rs <?= number_format($o['total_price'],0) ?></div>
                <span class="badge <?= $status_badge ?>" style="margin-top:6px;display:inline-block;"><?= $status_icon ?> <?= ucfirst($o['status']) ?></span>
            </div>
        </div>
        <div class="order-details">
            <div class="detail-item"><div class="detail-label">Quantity</div><div class="detail-value"><?= $o['quantity'] ?> units</div></div>
            <div class="detail-item"><div class="detail-label">Payment</div><div class="detail-value"><?= ucfirst(str_replace('_',' ',$o['payment'])) ?></div></div>
            <div class="detail-item"><div class="detail-label">Order Date</div><div class="detail-value"><?= date('d M Y', strtotime($o['created_at'])) ?></div></div>
            <?php if ($o['notes']): ?>
            <div class="detail-item"><div class="detail-label">Notes</div><div class="detail-value"><?= htmlspecialchars($o['notes']) ?></div></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; endif; ?>
</div>
</body>
</html>
