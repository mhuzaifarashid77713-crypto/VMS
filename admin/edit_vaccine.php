<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: vaccines.php"); exit(); }

$stmt = $conn->prepare("SELECT * FROM vaccines WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$vaccine = $stmt->get_result()->fetch_assoc();
if (!$vaccine) { header("Location: vaccines.php"); exit(); }

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $manufacturer = trim($_POST['manufacturer']);
    $price        = floatval($_POST['price']);
    $quantity     = intval($_POST['quantity']);
    $expiry_date  = $_POST['expiry_date'];
    $description  = trim($_POST['description']);

    if (empty($name) || empty($manufacturer) || empty($expiry_date)) {
        $error = "Please fill all required fields.";
    } else {
        $upd = $conn->prepare("UPDATE vaccines SET name=?, manufacturer=?, price=?, quantity=?, expiry_date=?, description=? WHERE id=?");
        $upd->bind_param("ssdissi", $name, $manufacturer, $price, $quantity, $expiry_date, $description, $id);
        if ($upd->execute()) {
            $success = "✅ Vaccine updated successfully.!";
            // Refresh
            $stmt2 = $conn->prepare("SELECT * FROM vaccines WHERE id = ?");
            $stmt2->bind_param("i", $id);
            $stmt2->execute();
            $vaccine = $stmt2->get_result()->fetch_assoc();
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vaccine Edit — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Vaccine Edit</h1>
            <p><?= htmlspecialchars($vaccine['name']) ?> Edit.</p>
        </div>
        <a href="vaccines.php" class="btn-edit">← Back to Vaccines.</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-grid" style="margin-bottom:20px">
                <div class="form-group">
                    <label>Vaccine Name *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($vaccine['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Manufacturer *</label>
                    <input type="text" name="manufacturer" value="<?= htmlspecialchars($vaccine['manufacturer']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Price (Rs) *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?= $vaccine['price'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Quantity (Stock) *</label>
                    <input type="number" name="quantity" min="0" value="<?= $vaccine['quantity'] ?>" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date *</label>
                    <input type="date" name="expiry_date" value="<?= $vaccine['expiry_date'] ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px">
                <label>Description</label>
                <textarea name="description"><?= htmlspecialchars($vaccine['description']) ?></textarea>
            </div>

            <button type="submit" class="btn-primary" style="padding:12px 28px; font-size:14px;">💾 Update Vaccine</button>
        </form>
    </div>
</main>
</body>
</html>
