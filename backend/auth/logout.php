<?php
require_once '../config/db.php';

// Destroy session
session_destroy();

// Clear session variables
$_SESSION = [];

// Redirect to login page
header('Location: ../../login.html');
exit;
?>