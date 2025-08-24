<?php
session_start();
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
header('Location: login.php?logout=1');
exit;
?>
