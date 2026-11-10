<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "config/db.php";
require_once 'config/audit.php';
require_once __DIR__ . '/config/role.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $role);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if (empty($role)) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

$_SESSION['role'] = strtolower($role);
?>