<?php
require_once 'auth.php';
require_once 'config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: accounts.php");
    exit();
}

$id = (int)$_GET['id'];

$stmt = mysqli_prepare($conn, "DELETE FROM accounts WHERE id=?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: accounts.php?deleted=1");
} else {
    header("Location: accounts.php?error=1");
}

exit();