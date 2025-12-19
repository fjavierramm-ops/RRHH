<?php
// session_start() ya se llama en config.php
require_once('config.php');
session_unset();
session_destroy();
header('Location: Admlogin.php');
exit();
?>
