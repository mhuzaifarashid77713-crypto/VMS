<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$msg = '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role='user' AND (full_name LIKE ? OR email LIKE ?) ORDER BY created_at DESC");
    $like = "%$search%";
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC");
}

if (isset($_GET['deleted'])) $msg = "✅ User has been deleted successfully.";
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Users Management</h1>
            <p>Manage all registered users here.</p>
        </div>
        <a href="add_user.php" class="btn-primary">➕ Add New User</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert alert-success"><?= $msg ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="search-bar">
            <form method="GET" style="display:flex; gap:10px; width:100%">
                <input type="text" name="search" placeholder="🔍 Search by name or email...." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn-primary">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn-edit">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Poora Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($users->num_rows === 0): ?>
                <tr><td colspan="6">
                    <div class="empty-state">
                        <span class="icon">👥</span>
                        <p>No user found.</p>
                    </div>
                </td></tr>
            <?php else: $i = 1; while ($u = $users->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i++ ?></td>
                    <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                    <td style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="badge badge-blue">User</span></td>
                    <td style="color:var(--text-muted)"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <div class="actions">
                            <a href="view_user.php?id=<?= $u['id'] ?>" class="btn-edit">👁 View</a>
                            <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn-edit">✏️ Edit</a>
                            <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn-danger"
                               onclick="return confirm('Are you sure? This user will be deleted.!')">🗑 Delete</a>
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
