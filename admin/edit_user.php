<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: users.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) { header("Location: users.php"); exit(); }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $role      = $_POST['role'];
    $password  = $_POST['password'];

    // Check email uniqueness
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "This email is already used by another user..";
    } else {
        if (!empty($password)) {
            if (strlen($password) < 6) {
                $error = "Password must be at least 6 characters long..";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, password=?, role=? WHERE id=?");
                $upd->bind_param("ssssi", $full_name, $email, $hashed, $role, $id);
                $upd->execute();
                $success = "✅ User updated successfully (password also changed).).";
            }
        } else {
            $upd = $conn->prepare("UPDATE users SET full_name=?, email=?, role=? WHERE id=?");
            $upd->bind_param("sssi", $full_name, $email, $role, $id);
            $upd->execute();
            $success = "✅ User updated successfully.!";
        }
        if (!$error) {
            $stmt2 = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $user = $stmt2->get_result()->fetch_assoc();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Edit — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>User Edit</h1>
            <p><?= htmlspecialchars($user['full_name']) ?> Edit Account</p>
        </div>
        <a href="users.php" class="btn-edit">← Back to Users</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-grid" style="margin-bottom:20px">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <div class="form-group">
                    <label>New Password (optional).</label>
                    <input type="password" name="password" placeholder="Leave empty if you don’t want to change it.">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn-primary" style="padding:12px 28px; font-size:14px;">💾 Save Changes</button>
        </form>
    </div>
</main>
</body>
</html>
