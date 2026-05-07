<?php
session_start();
session_destroy();
header("Location: /VMS/index.php");
exit();
?>
