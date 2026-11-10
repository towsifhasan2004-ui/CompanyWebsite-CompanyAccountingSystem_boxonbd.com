<?php

session_start();

require_once 'config/db.php';
require_once 'config/audit.php';

if(isset($_SESSION['user_id']))
{
    logActivity(
        $conn,
        "Authentication",
        "Logout",
        $_SESSION['user_id'],
        $_SESSION['username']." logged out."
    );
}

session_destroy();

header("Location: login.php");
exit();