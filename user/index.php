<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireLogin();

// Handle Add to Cart / Order
$order_msg = '';
$order_err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_vaccine_id'])) {
    $vaccine_id = intval($_POST['order_vaccine_id']);
    $quantity   = intval($_POST['order_quantity']);
    $payment    = $_POST['payment_method'];
    $notes      = trim($_POST['notes'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $user_id    = $_SESSION['user_id'];

    // Get vaccine
    $vax = $conn->prepare("SELECT * FROM vaccines WHERE id = ?");
    $vax->bind_param("i", $vaccine_id);
    $vax->execute();
    $vaccine = $vax->get_result()->fetch_assoc();

    if (!$vaccine) {
        $order_err = "Vaccine not found.";
    } elseif ($quantity < 1 || $quantity > $vaccine['quantity']) {
        $order_err = "Invalid quantity. Only {$vaccine['quantity']} units available.";
    } else {
        $total = $vaccine['price'] * $quantity;
       $ins = $conn->prepare("INSERT INTO orders (user_id, vaccine_id, quantity, total_price, payment, notes, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->bind_param("iiidssss", $user_id, $vaccine_id, $quantity, $total, $payment, $notes, $phone, $address);
        if ($ins->execute()) {
            $order_msg = "✅ Order placed successfully! We'll confirm it shortly.";
        } else {
            $order_err = "Something went wrong. Please try again.";
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where  = "WHERE 1=1";
$params = []; $types = "";

if ($search) {
    $where .= " AND (name LIKE ? OR manufacturer LIKE ? OR description LIKE ?)";
    $like = "%$search%"; $params = [$like, $like, $like]; $types = "sss";
}
if ($filter === 'available') $where .= " AND quantity > 0";
elseif ($filter === 'expiring') $where .= " AND expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)";

if ($params) {
    $stmt = $conn->prepare("SELECT * FROM vaccines $where ORDER BY name ASC");
    $stmt->bind_param($types, ...$params); $stmt->execute();
    $vaccines = $stmt->get_result();
} else {
    $vaccines = $conn->query("SELECT * FROM vaccines $where ORDER BY name ASC");
}

$total     = $conn->query("SELECT COUNT(*) as c FROM vaccines")->fetch_assoc()['c'];
$available = $conn->query("SELECT COUNT(*) as c FROM vaccines WHERE quantity > 0")->fetch_assoc()['c'];
$my_orders = $conn->prepare("SELECT o.*, v.name as vaccine_name FROM orders o JOIN vaccines v ON o.vaccine_id=v.id WHERE o.user_id=? ORDER BY o.created_at DESC LIMIT 5");
$my_orders->bind_param("i", $_SESSION['user_id']);
$my_orders->execute();
$my_orders = $my_orders->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccines — VMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root { --primary:#00b894; --accent:#0984e3; --warning:#fdcb6e; --danger:#d63031; --bg:#0a0e1a; --surface:#111827; --surface2:#1a2235; --border:rgba(255,255,255,0.07); --text:#e8eaf0; --text-muted:#8892a4; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; }

        /* Header */
        .header { background:var(--surface); border-bottom:1px solid var(--border); padding:0 24px; position:sticky; top:0; z-index:100; display:flex; align-items:center; justify-content:space-between; height:65px; gap:12px; }
        .header-brand { display:flex; align-items:center; gap:10px; font-family:'Syne',sans-serif; font-size:17px; font-weight:700; color:#fff; white-space:nowrap; }
        .brand-icon { width:38px; height:38px; background:linear-gradient(135deg,var(--primary),var(--accent)); border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; flex-shrink:0; }
        .header-actions { display:flex; align-items:center; gap:8px; }
        .user-pill { display:flex; align-items:center; gap:6px; padding:6px 12px; background:var(--surface2); border:1px solid var(--border); border-radius:30px; font-size:13px; white-space:nowrap; }
        .btn-sm { padding:6px 12px; border-radius:8px; font-size:12px; cursor:pointer; text-decoration:none; border:1px solid var(--border); transition:all 0.2s; white-space:nowrap; }
        .btn-sm-danger { background:rgba(214,48,49,0.1); color:#ff7675; border-color:rgba(214,48,49,0.2); }
        .btn-sm-admin { background:rgba(9,132,227,0.1); color:#74b9ff; border-color:rgba(9,132,227,0.2); }
        .btn-sm-orders { background:rgba(0,184,148,0.1); color:var(--primary); border-color:rgba(0,184,148,0.2); }

        /* Hero */
        .hero { text-align:center; padding:50px 20px 36px; position:relative; overflow:hidden; }
        .hero::before { content:''; position:absolute; width:500px; height:500px; background:radial-gradient(circle,rgba(0,184,148,0.08) 0%,transparent 70%); top:50%; left:50%; transform:translate(-50%,-50%); pointer-events:none; }
        .hero h1 { font-family:'Syne',sans-serif; font-size:clamp(28px,6vw,48px); font-weight:800; color:#fff; margin-bottom:10px; }
        .hero h1 span { color:var(--primary); }
        .hero p { color:var(--text-muted); font-size:15px; max-width:500px; margin:0 auto 30px; }
        .search-section { max-width:680px; margin:0 auto; }
        .search-form { display:flex; gap:10px; }
        .search-input { flex:1; padding:12px 18px; background:var(--surface); border:1px solid var(--border); border-radius:12px; color:var(--text); font-size:14px; outline:none; min-width:0; }
        .search-input:focus { border-color:var(--primary); }
        .btn-search { padding:12px 20px; background:linear-gradient(135deg,var(--primary),#00a381); color:#fff; border:none; border-radius:12px; font-size:14px; font-weight:500; cursor:pointer; white-space:nowrap; }

        /* Stats */
        .stats-bar { display:flex; gap:12px; justify-content:center; padding:0 20px 30px; flex-wrap:wrap; }
        .stat-pill { padding:7px 16px; background:var(--surface); border:1px solid var(--border); border-radius:30px; font-size:12px; color:var(--text-muted); }
        .stat-pill strong { color:var(--primary); }

        /* Filter */
        .filter-section { max-width:1200px; margin:0 auto; padding:0 20px 20px; display:flex; gap:8px; flex-wrap:wrap; }
        .filter-btn { padding:7px 16px; border-radius:30px; border:1px solid var(--border); background:var(--surface); color:var(--text-muted); font-size:13px; cursor:pointer; text-decoration:none; transition:all 0.2s; }
        .filter-btn.active { background:rgba(0,184,148,0.1); color:var(--primary); border-color:rgba(0,184,148,0.3); }

        /* Vaccines Grid */
        .vaccines-section { max-width:1200px; margin:0 auto; padding:0 20px 60px; }
        .result-count { font-size:13px; color:var(--text-muted); margin-bottom:16px; }
        .vaccines-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }

        .vaccine-card { background:var(--surface); border:1px solid var(--border); border-radius:16px; padding:20px; transition:all 0.2s; position:relative; overflow:hidden; }
        .vaccine-card:hover { border-color:rgba(0,184,148,0.3); transform:translateY(-2px); box-shadow:0 10px 30px rgba(0,0,0,0.3); }
        .vaccine-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,var(--primary),var(--accent)); border-radius:16px 16px 0 0; }
        .vaccine-card.out-of-stock { opacity:0.6; }
        .vaccine-card.out-of-stock::before { background:var(--text-muted); }

        .card-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:14px; }
        .vaccine-icon { font-size:28px; }
        .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
        .badge-green { background:rgba(0,184,148,0.1); color:var(--primary); border:1px solid rgba(0,184,148,0.2); }
        .badge-red { background:rgba(214,48,49,0.1); color:#ff7675; border:1px solid rgba(214,48,49,0.2); }
        .badge-yellow { background:rgba(253,203,110,0.1); color:var(--warning); border:1px solid rgba(253,203,110,0.2); }
        .badge-gray { background:rgba(255,255,255,0.05); color:var(--text-muted); border:1px solid var(--border); }
        .badge-blue { background:rgba(9,132,227,0.1); color:#74b9ff; border:1px solid rgba(9,132,227,0.2); }
        .badge-orange { background:rgba(253,203,110,0.1); color:var(--warning); border:1px solid rgba(253,203,110,0.2); }

        .vaccine-name { font-family:'Syne',sans-serif; font-size:17px; font-weight:700; color:#fff; margin-bottom:4px; }
        .vaccine-manufacturer { font-size:13px; color:var(--text-muted); margin-bottom:12px; }
        .vaccine-desc { font-size:13px; color:var(--text-muted); line-height:1.6; margin-bottom:14px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .vaccine-meta { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px; }
        .meta-item { background:var(--surface2); border-radius:10px; padding:10px 12px; }
        .meta-label { font-size:10px; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); margin-bottom:3px; }
        .meta-value { font-size:14px; font-weight:600; color:var(--text); }
        .meta-value.price { color:var(--primary); }
        .expiry-row { display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--text-muted); padding-top:12px; border-top:1px solid var(--border); flex-wrap:wrap; gap:4px; margin-bottom:14px; }

        /* Order Button */
        .btn-order { width:100%; padding:11px; background:linear-gradient(135deg,var(--primary),#00a381); color:#fff; border:none; border-radius:10px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:600; cursor:pointer; transition:all 0.2s; }
        .btn-order:hover { transform:translateY(-1px); box-shadow:0 6px 20px rgba(0,184,148,0.35); }
        .btn-order:disabled { background:var(--surface2); color:var(--text-muted); cursor:not-allowed; transform:none; box-shadow:none; }

        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:500; align-items:center; justify-content:center; padding:20px; }
        .modal-overlay.show { display:flex; }
        .modal { background:var(--surface); border:1px solid var(--border); border-radius:20px; padding:32px; width:100%; max-width:480px; position:relative; }
        .modal-close { position:absolute; top:16px; right:16px; background:none; border:none; color:var(--text-muted); font-size:22px; cursor:pointer; }
        .modal h2 { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; color:#fff; margin-bottom:6px; }
        .modal .vaccine-info { background:var(--surface2); border-radius:12px; padding:14px; margin:16px 0; font-size:14px; }
        .modal .vaccine-info strong { color:var(--primary); }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); margin-bottom:8px; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:12px 14px; background:var(--surface2); border:1px solid var(--border); border-radius:10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:14px; outline:none; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color:var(--primary); }
        .form-group select option { background:var(--surface2); }
        .price-preview { background:rgba(0,184,148,0.08); border:1px solid rgba(0,184,148,0.2); border-radius:10px; padding:12px 14px; font-size:14px; color:var(--primary); font-weight:600; margin-bottom:16px; }
        .btn-place-order { width:100%; padding:13px; background:linear-gradient(135deg,var(--primary),#00a381); color:#fff; border:none; border-radius:10px; font-family:'Syne',sans-serif; font-size:15px; font-weight:700; cursor:pointer; }

        /* My Orders */
        .orders-section { max-width:1200px; margin:0 auto; padding:0 20px 60px; }
        .section-title { font-family:'Syne',sans-serif; font-size:20px; font-weight:700; color:#fff; margin-bottom:16px; }
        .order-card { background:var(--surface); border:1px solid var(--border); border-radius:12px; padding:16px 20px; margin-bottom:12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .order-info h4 { font-size:15px; font-weight:600; color:#fff; margin-bottom:4px; }
        .order-info p { font-size:12px; color:var(--text-muted); }
        .order-meta { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .order-price { font-family:'Syne',sans-serif; font-size:16px; font-weight:700; color:var(--primary); }

        /* Alert */
        .alert { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; max-width:1200px; margin-left:auto; margin-right:auto; }
        .alert-success { background:rgba(0,184,148,0.1); border:1px solid rgba(0,184,148,0.3); color:var(--primary); }
        .alert-error { background:rgba(214,48,49,0.1); border:1px solid rgba(214,48,49,0.3); color:#ff7675; }

        .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
        .empty-state .icon { font-size:48px; display:block; margin-bottom:12px; }

        @media (max-width:600px) {
            .header { padding:0 14px; }
            .user-pill { display:none; }
            .hero { padding:30px 16px 20px; }
            .search-form { flex-direction:column; }
            .vaccines-grid { grid-template-columns:1fr; }
            .modal { padding:24px 18px; }
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
        <a href="#my-orders" class="btn-sm btn-sm-orders">📦 My Orders</a>
        <?php if (isAdmin()): ?>
            <a href="/admin/dashboard.php" class="btn-sm btn-sm-admin">🛡️ Admin</a>
        <?php endif; ?>
        <a href="/logout.php" class="btn-sm btn-sm-danger">🚪 Logout</a>
    </div>
</header>

<!-- Alerts -->
<?php if ($order_msg): ?>
<div class="alert alert-success" style="margin:16px auto;max-width:1200px;padding:0 20px;">
    <div style="padding:12px 16px;background:rgba(0,184,148,0.1);border:1px solid rgba(0,184,148,0.3);border-radius:10px;color:var(--primary);"><?= $order_msg ?></div>
</div>
<?php endif; ?>
<?php if ($order_err): ?>
<div style="margin:16px auto;max-width:1200px;padding:0 20px;">
    <div style="padding:12px 16px;background:rgba(214,48,49,0.1);border:1px solid rgba(214,48,49,0.3);border-radius:10px;color:#ff7675;"><?= $order_err ?></div>
</div>
<?php endif; ?>

<!-- Hero -->
<div class="hero">
    <h1>Vaccine <span>Inventory</span></h1>
    <p>Browse and order vaccines with ease.</p>
    <div class="search-section">
        <form class="search-form" method="GET">
            <input class="search-input" type="text" name="search" placeholder="🔍 Search vaccines or manufacturers..." value="<?= htmlspecialchars($search) ?>">
            <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"><?php endif; ?>
            <button type="submit" class="btn-search">Search</button>
        </form>
    </div>
</div>

<div class="stats-bar">
    <div class="stat-pill">💊 Total: <strong><?= $total ?></strong></div>
    <div class="stat-pill">✅ Available: <strong><?= $available ?></strong></div>
    <div class="stat-pill">📅 Last Updated: <strong>Today</strong></div>
</div>

<div class="filter-section">
    <a href="?<?= $search?'search='.urlencode($search).'&':''?>filter=all" class="filter-btn <?= $filter==='all'?'active':''?>">🔬 All</a>
    <a href="?<?= $search?'search='.urlencode($search).'&':''?>filter=available" class="filter-btn <?= $filter==='available'?'active':''?>">✅ Available</a>
    <a href="?<?= $search?'search='.urlencode($search).'&':''?>filter=expiring" class="filter-btn <?= $filter==='expiring'?'active':''?>">⚠️ Expiring Soon</a>
</div>

<!-- Vaccines -->
<div class="vaccines-section">
    <div class="result-count"><?= $vaccines->num_rows ?> vaccine<?= $vaccines->num_rows!==1?'s':''?> found</div>
    <?php if ($vaccines->num_rows === 0): ?>
        <div class="empty-state"><span class="icon">🔍</span><p>No vaccines found.</p></div>
    <?php else: ?>
    <div class="vaccines-grid">
    <?php while ($v = $vaccines->fetch_assoc()):
        $exp = strtotime($v['expiry_date']); $diff = ($exp - time()) / 86400;
        $badge_class = $v['quantity']<=0?'badge-gray':($diff<0?'badge-red':($diff<90?'badge-yellow':'badge-green'));
        $badge_text  = $v['quantity']<=0?'Out of Stock':($diff<0?'Expired':($diff<90?'Expiring Soon':'Available'));
        $can_order   = $v['quantity'] > 0 && $diff > 0;
    ?>
    <div class="vaccine-card <?= $v['quantity']<=0?'out-of-stock':'' ?>">
        <div class="card-top"><span class="vaccine-icon">💉</span><span class="badge <?= $badge_class ?>"><?= $badge_text ?></span></div>
        <div class="vaccine-name"><?= htmlspecialchars($v['name']) ?></div>
        <div class="vaccine-manufacturer">🏭 <?= htmlspecialchars($v['manufacturer']) ?></div>
        <?php if ($v['description']): ?><div class="vaccine-desc"><?= htmlspecialchars($v['description']) ?></div><?php endif; ?>
        <div class="vaccine-meta">
            <div class="meta-item"><div class="meta-label">Price</div><div class="meta-value price">Rs <?= number_format($v['price'],0) ?></div></div>
            <div class="meta-item"><div class="meta-label">Stock</div><div class="meta-value"><?= number_format($v['quantity']) ?> units</div></div>
        </div>
        <div class="expiry-row">
            <span>📅 <?= date('d M Y',$exp) ?></span>
            <?php if ($diff>0): ?><span><?= round($diff) ?> days left</span><?php else: ?><span style="color:#ff7675">Expired</span><?php endif; ?>
        </div>
        <?php if ($can_order): ?>
        <button class="btn-order" onclick="openOrder(<?= $v['id'] ?>, '<?= addslashes($v['name']) ?>', <?= $v['price'] ?>, <?= $v['quantity'] ?>)">
            🛒 Order Now
        </button>
        <?php else: ?>
        <button class="btn-order" disabled>❌ Not Available</button>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<!-- My Orders -->
<div class="orders-section" id="my-orders">
    <h2 class="section-title">📦 My Recent Orders</h2>
    <?php if ($my_orders->num_rows === 0): ?>
        <div class="empty-state"><span class="icon">📦</span><p>No orders yet. Order your first vaccine above!</p></div>
    <?php else: ?>
    <?php while ($o = $my_orders->fetch_assoc()):
        $status_badge = match($o['status']) {
            'pending'   => 'badge-yellow',
            'approved'  => 'badge-green',
            'rejected'  => 'badge-red',
            'delivered' => 'badge-blue',
            default     => 'badge-gray'
        };
    ?>
    <div class="order-card">
        <div class="order-info">
            <h4>💉 <?= htmlspecialchars($o['vaccine_name']) ?></h4>
            <p>Qty: <?= $o['quantity'] ?> units &nbsp;•&nbsp; <?= ucfirst(str_replace('_',' ',$o['payment'])) ?> &nbsp;•&nbsp; <?= date('d M Y', strtotime($o['created_at'])) ?></p>
        </div>
        <div class="order-meta">
            <div class="order-price">Rs <?= number_format($o['total_price'],0) ?></div>
            <span class="badge <?= $status_badge ?>"><?= ucfirst($o['status']) ?></span>
        </div>
    </div>
    <?php endwhile; ?>
    <div style="text-align:center;margin-top:16px;">
        <a href="my_orders.php" style="color:var(--primary);font-size:14px;text-decoration:none;">View All Orders →</a>
    </div>
    <?php endif; ?>
</div>

<!-- Order Modal -->
<div class="modal-overlay" id="orderModal">
    <div class="modal">
        <button class="modal-close" onclick="closeOrder()">✕</button>
        <h2>🛒 Place Order</h2>
        <div class="vaccine-info" id="modal-vaccine-info">
            <strong id="modal-name"></strong><br>
            Price per unit: <strong id="modal-price"></strong> &nbsp;|&nbsp; Stock: <strong id="modal-stock"></strong> units
        </div>
        <form method="POST">
            <input type="hidden" name="order_vaccine_id" id="modal-vaccine-id">
            <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="order_quantity" id="modal-qty" min="1" value="1" required oninput="updatePrice()">
            </div>
            <div class="price-preview" id="price-preview">Total: Rs 0</div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" required>
                    <option value="cash_on_delivery">💵 Cash on Delivery</option>
                    <option value="bank_transfer">🏦 Bank Transfer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Phone Number *</label>
                <input type="tel" name="phone" placeholder="e.g. 0300-1234567" required>
            </div>
            <div class="form-group">
                <label>Delivery Address *</label>
                <textarea name="address" rows="2" placeholder="House no, Street, City..." required></textarea>
            </div>
            <div class="form-group">
                <label>Additional Notes (Optional)</label>
                <textarea name="notes" rows="2" placeholder="Any special instructions..."></textarea>
            </div>
            <button type="submit" class="btn-place-order">✅ Confirm Order</button>
        </form>
    </div>
</div>

<script>
let currentPrice = 0;
function openOrder(id, name, price, stock) {
    currentPrice = price;
    document.getElementById('modal-vaccine-id').value = id;
    document.getElementById('modal-name').textContent = name;
    document.getElementById('modal-price').textContent = 'Rs ' + price.toLocaleString();
    document.getElementById('modal-stock').textContent = stock;
    document.getElementById('modal-qty').max = stock;
    document.getElementById('modal-qty').value = 1;
    updatePrice();
    document.getElementById('orderModal').classList.add('show');
}
function closeOrder() { document.getElementById('orderModal').classList.remove('show'); }
function updatePrice() {
    const qty = parseInt(document.getElementById('modal-qty').value) || 0;
    document.getElementById('price-preview').textContent = 'Total: Rs ' + (qty * currentPrice).toLocaleString();
}
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeOrder();
});
</script>
</body>
</html>
