<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name']);
    $manufacturer = trim($_POST['manufacturer']);
    $price        = floatval($_POST['price']);
    $quantity     = intval($_POST['quantity']);
  $expiry_date  = date('Y-m-d', strtotime($_POST['expiry_date']));
    $description  = trim($_POST['description']);

    if (empty($name) || empty($manufacturer) || empty($expiry_date)) {
        $error = "Sab zaruri fields fill karein.";
    } elseif ($price < 0 || $quantity < 0) {
        $error = "Price aur quantity negative nahi ho sakti.";
    } else {
        $stmt = $conn->prepare("INSERT INTO vaccines (name, manufacturer, price, quantity, expiry_date, description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiis", $name, $manufacturer, $price, $quantity, $expiry_date, $description);
        if ($stmt->execute()) {
            header("Location: vaccines.php?added=1");
            exit();
        } else {
            $error = "Masla hua. Dobara try karein.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Vaccine — VMS Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<main class="main">
    <div class="page-header">
        <div class="page-header-left">
            <h1>Add New Vaccine</h1>
            <p>Add a new vaccine to the inventory</p>
        </div>
        <a href="vaccines.php" class="btn-edit">← Back to Vaccines</a>
    </div>

    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <div class="form-grid" style="margin-bottom:20px">
                <div class="form-group">
                    <label>Vaccine Name *</label>
                    <input type="text" name="name" placeholder="vaccine name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Manufacturer *</label>
                    <input type="text" name="manufacturer" placeholder="manufacturer" value="<?= htmlspecialchars($_POST['manufacturer'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Price (Rs) *</label>
                    <input type="number" name="price" step="0.01" min="0" placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Quantity (Stock) *</label>
                    <input type="number" name="quantity" min="0" placeholder="0" value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date *</label>
                    <input type="date" name="expiry_date" value="<?= htmlspecialchars($_POST['expiry_date'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:24px">
                <label>Description</label>
                <textarea name="description" placeholder="Write something about the vaccine (optional)...."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-primary" style="padding:12px 28px; font-size:14px;">💊 Add Vaccine</button>
        </form>
    </div>
</main>
</body>
</html>
