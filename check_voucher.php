<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['manager']);

$id = (int)$_GET['id'];

$sql = "
UPDATE vouchers

SET

status='Checked',
checked_by=?,
checked_at=NOW()

WHERE id=?
AND status='Pending'
";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"ii",
$_SESSION['user_id'],
$id
);

mysqli_stmt_execute($stmt);

header("Location: manager_review.php");
exit;