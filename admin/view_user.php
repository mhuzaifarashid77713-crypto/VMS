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
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View User — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .user-profile { display:flex; gap:32px; align-items:flex-start; }
        .avatar-big {
            width:90px; height:90px; border-radius:50%;
            background:linear-gradient(135deg, var(--primary), var(--accent));
            display:flex; align-items:center; justify-content:center;
            font-size:40px; flex-shrink:0;
            box-shadow: 0 10px 30px rgba(0,184,148,0.3);
        }
        .profile-details { flex:1; }
        .profile-name { font-family:'Syne',sans-serif; font-size:24px; font-weight:700; color:#fff; margin-bottom:4px; }
        .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-top:24px; }
        .detail-item { background:var(--surface2); border:1px solid var(--border); border-radius:12px; padding:16px 20px; }
        .detail-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.8px; color:var(--text-muted); margin-bottom:6px; }
        .detail-value { font-size:15px; color:var(--text); }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>User Details</h1>
            <p>Full User Profile</p>
        </div>
        <div class="actions">
            <a href="edit_user.php?id=<?= $id ?>" class="btn-edit">✏️ Edit</a>
            <a href="users.php" class="btn-primary">← Back</a>
        </div>
    </div>

    <div class="form-card">
        <div class="user-profile">
            <div class="avatar-big">👤</div>
            <div class="profile-details">
                <div class="profile-name"><?= htmlspecialchars($user['full_name']) ?></div>
                <span class="badge <?= $user['role'] === 'admin' ? 'badge-green' : 'badge-blue' ?>">
                    <?= $user['role'] === 'admin' ? '🛡️ Admin' : '👤 User' ?>
                </span>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account Role</div>
                        <div class="detail-value"><?= ucfirst($user['role']) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registration Date</div>
                        <div class="detail-value"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account ID</div>
                        <div class="detail-value">#<?= str_pad($user['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
