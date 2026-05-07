<?php
// =============================================
//  Database Configuration
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Change if needed
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'vms_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:20px;color:red;'>
        <h3>❌ Database Connection Failed</h3>
        <p>" . $conn->connect_error . "</p>
        <p>Please check your MySQL settings in <b>includes/db.php</b></p>
    </div>");
}

$conn->set_charset("utf8mb4");
?>
