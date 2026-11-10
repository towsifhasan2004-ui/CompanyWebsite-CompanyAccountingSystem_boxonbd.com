<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

$id = (int)$_GET['id'];

$sql = "
UPDATE vouchers

SET

status='Approved',
approved_by=?,
approved_at=NOW()

WHERE id=?
AND status='Checked'
";

$stmt = mysqli_prepare($conn,$sql);

mysqli_stmt_bind_param(
$stmt,
"ii",
$_SESSION['user_id'],
$id
);

mysqli_stmt_execute($stmt);
logActivity(
    $conn,
    "Receipt Voucher",
    "Approved",
    $id,
    "Admin approved voucher : ".$voucher_no
);

header("Location: admin_approval.php");
exit;