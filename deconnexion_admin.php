<?php
session_start();
unset($_SESSION['admin_connecte']);
unset($_SESSION['code_utilise']);
session_destroy();
header('Location: index.php');
exit;
?>