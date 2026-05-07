<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM vaccines WHERE name LIKE ? OR manufacturer LIKE ? ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $vaccines = $stmt->get_result();
} else {
    $vaccines = $conn->query("SELECT * FROM vaccines ORDER BY created_at DESC");
}

$msg = isset($_GET['deleted']) ? '✅ Vaccine has been deleted successfully..' : '';
$msg = isset($_GET['added']) ? '✅ New vaccine has been added successfully.!' : $msg;
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccines — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Vaccines Management</h1>
            <p>Add, edit, and delete all vaccines here.</p>
        </div>
        <a href="add_vaccine.php" class="btn-primary">➕ Add New Vaccine</a>
    </div>

    <?php if ($msg): ?><div class="alert alert-success"><?= $msg ?></div><?php endif; ?>

    <div class="card">
        <div class="search-bar">
            <form method="GET" style="display:flex; gap:10px; width:100%">
                <input type="text" name="search" placeholder="🔍 Search by vaccine name or manufacturer...." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary">Search.</button>
                <?php if ($search): ?>
                    <a href="vaccines.php" class="btn-edit">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Vaccine Name</th>
                    <th>Manufacturer</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($vaccines->num_rows === 0): ?>
                <tr><td colspan="8">
                    <div class="empty-state">
                        <span class="icon">💊</span>
                        <p>No vaccine found. Please add one first.</p>
                    </div>
                </td></tr>
            <?php else: $i = 1; while ($v = $vaccines->fetch_assoc()):
                $diff = (strtotime($v['expiry_date']) - time()) / (60*60*24);
                $badge = $diff < 0 ? 'badge-red' : ($diff < 30 ? 'badge-red' : ($diff < 90 ? 'badge-yellow' : 'badge-green'));
                $status = $diff < 0 ? 'Expired' : ($diff < 30 ? 'Critical' : ($diff < 90 ? 'Expiring Soon' : 'OK'));
            ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($v['name']) ?></strong></td>
                    <td style="color:var(--text-muted)"><?= htmlspecialchars($v['manufacturer']) ?></td>
                    <td style="color:var(--primary)">Rs <?= number_format($v['price'], 2) ?></td>
                    <td><?= number_format($v['quantity']) ?></td>
                    <td><?= date('d M Y', strtotime($v['expiry_date'])) ?></td>
                    <td><span class="badge <?= $badge ?>"><?= $status ?></span></td>
                    <td>
                        <div class="actions">
                            <a href="edit_vaccine.php?id=<?= $v['id'] ?>" class="btn-edit">✏️ Edit</a>
                            <a href="delete_vaccine.php?id=<?= $v['id'] ?>" class="btn-danger"
                               onclick="return confirm('Do you want to delete the vaccine??')">🗑 Delete</a>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>
