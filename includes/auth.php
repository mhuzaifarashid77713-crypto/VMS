<?php
// =============================================
//  Auth Helper Functions
// =============================================

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . getBaseUrl() . "/index.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: " . getBaseUrl() . "/user/index.php");
        exit();
    }
}

function getBaseUrl() {
    return '';
}

function logout() {
    session_destroy();
    header("Location: /index.php");
    exit();
}
?>
