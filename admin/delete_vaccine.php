<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header("Location: vaccines.php"); exit(); }

$stmt = $conn->prepare("DELETE FROM vaccines WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header("Location: vaccines.php?deleted=1");
exit();
?>
