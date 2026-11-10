<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

if (!isset($_GET['id'])) {
    header("Location: user_management.php");
    exit();
}
$stmt = mysqli_prepare($conn,"
SELECT username
FROM users
WHERE id=?");

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$username = $user['username'];

$id = (int)$_GET['id'];

/* ==========================
   DON'T DELETE YOURSELF
========================== */

if ($id == $_SESSION['user_id']) {
    header("Location: user_management.php?error=self_delete");
    exit();
}

/* ==========================
   CHECK USER EXISTS
========================== */

$stmt = mysqli_prepare($conn, "
SELECT id
FROM users
WHERE id=?
LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: user_management.php?error=notfound");
    exit();
}

/* ==========================
   DELETE USER
========================== */

$stmt = mysqli_prepare($conn, "
DELETE FROM users
WHERE id=?
");
logActivity(
    $conn,
    "User",
    "Delete",
    $id,
    "Deleted user: ".$username
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: user_management.php?deleted=1");
} else {
    header("Location: user_management.php?error=delete");
}
exit();