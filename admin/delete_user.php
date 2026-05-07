<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: users.php"); exit(); }

// Prevent deleting yourself
if ($id === intval($_SESSION['user_id'])) {
    header("Location: users.php?error=self");
    exit();
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: users.php?deleted=1");
exit();
?>
