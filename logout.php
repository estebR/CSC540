<?php require_once 'auth.php'; ?>


<?php
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
