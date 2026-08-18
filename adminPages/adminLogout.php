<?php

session_start();

// Remove all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to admin login
header("Location: adminLogin.php");
exit();

?>