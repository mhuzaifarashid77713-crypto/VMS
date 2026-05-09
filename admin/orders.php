<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $oid    = intval($_POST['order_id']);
    $status = $_POST['status'];
    $upd    = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $upd->bind_param("si", $status, $oid);
    $upd->execute();
    header("Location: orders.php?updated=1");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where  = $filter !== 'all' ? "WHERE o.status='$filter'" : "";

$orders = $conn->query("
    SELECT o.*, u.full_name, u.email, v.name as vaccine_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN vaccines v ON o.vaccine_id = v.id
    $where
    ORDER BY o.created_at DESC
");

$total_orders   = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pending_orders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$total_revenue  = $conn->query("SELECT SUM(total_price) as c FROM orders WHERE status IN ('approved','delivered')")->fetch_assoc()['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>
<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>📦 Orders Management</h1>
            <p>View and manage all customer orders</p>
        </div>
    </div>

    <?php if (isset($_GET['updated'])): ?>
    <div class="alert alert-success">✅ Order status updated successfully!</div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:28px;">
        <div class="stat-card green" style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;">
            <span style="font-size:24px;display:block;margin-bottom:10px;">📦</span>
            <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:#fff;"><?= $total_orders ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Total Orders</div>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;">
            <span style="font-size:24px;display:block;margin-bottom:10px;">⏳</span>
            <div style="font-family:'Syne',sans-serif;font-size:28px;font-weight:800;color:var(--warning);"><?= $pending_orders ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Pending Orders</div>
        </div>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:20px;">
            <span style="font-size:24px;display:block;margin-bottom:10px;">💰</span>
            <div style="font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--primary);">Rs <?= number_format($total_revenue,0) ?></div>
            <div style="font-size:13px;color:var(--text-muted);">Total Revenue</div>
        </div>
    </div>

    <!-- Filter -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
        <?php foreach(['all'=>'All','pending'=>'⏳ Pending','approved'=>'✅ Approved','rejected'=>'❌ Rejected','delivered'=>'📬 Delivered'] as $val=>$label): ?>
        <a href="?filter=<?= $val ?>" style="padding:7px 16px;border-radius:30px;border:1px solid var(--border);background:<?= $filter===$val?'rgba(0,184,148,0.1)':'var(--surface)' ?>;color:<?= $filter===$val?'var(--primary)':'var(--text-muted)' ?>;font-size:13px;text-decoration:none;border-color:<?= $filter===$val?'rgba(0,184,148,0.3)':'var(--border)' ?>;"><?= $label ?></a>
        <?php endforeach; ?>
    </div>

    <div class="card">
        <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Vaccine</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders->num_rows === 0): ?>
                <tr><td colspan="9"><div class="empty-state"><span class="icon">📦</span><p>No orders found.</p></div></td></tr>
            <?php else: $i=1; while ($o = $orders->fetch_assoc()):
                $status_badge = match($o['status']) {
                    'pending'   => 'badge-yellow',
                    'approved'  => 'badge-green',
                    'rejected'  => 'badge-red',
                    'delivered' => 'badge-blue',
                    default     => 'badge-gray'
                };
            ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($o['full_name']) ?></strong><br>
                    <span style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($o['email']) ?></span>
                </td>
                <td><?= htmlspecialchars($o['vaccine_name']) ?></td>
                <td><?= $o['quantity'] ?></td>
                <td style="color:var(--primary);font-weight:600;">Rs <?= number_format($o['total_price'],0) ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= ucfirst(str_replace('_',' ',$o['payment'])) ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td><span class="badge <?= $status_badge ?>"><?= ucfirst($o['status']) ?></span></td>
                <td>
                    <form method="POST" style="display:flex;gap:6px;flex-wrap:wrap;">
                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                        <select name="status" style="padding:5px 8px;background:var(--surface2);border:1px solid var(--border);border-radius:6px;color:var(--text);font-size:12px;">
                            <option value="pending" <?= $o['status']==='pending'?'selected':'' ?>>Pending</option>
                            <option value="approved" <?= $o['status']==='approved'?'selected':'' ?>>Approved</option>
                            <option value="rejected" <?= $o['status']==='rejected'?'selected':'' ?>>Rejected</option>
                            <option value="delivered" <?= $o['status']==='delivered'?'selected':'' ?>>Delivered</option>
                        </select>
                        <button type="submit" class="btn-edit" style="padding:5px 10px;font-size:12px;">Update</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
        </div>
    </div>
</main>
</body>
</html>
