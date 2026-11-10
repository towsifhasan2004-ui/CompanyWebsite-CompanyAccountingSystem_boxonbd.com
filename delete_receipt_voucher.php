<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','accountant']);

if (!isset($_GET['id'])) {
    header("Location: receipt_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/*=========================
  GET VOUCHER
=========================*/

$stmt = mysqli_prepare($conn,"
SELECT
id,
created_by,
status,
voucher_type
FROM vouchers
WHERE id=?
LIMIT 1");

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$voucher){
    die("Voucher not found.");
}

if($voucher['voucher_type'] != 'Receipt'){
    die("Invalid voucher.");
}

/*=========================
  ACCOUNTANT RULES
=========================*/

if($_SESSION['role'] == 'accountant')
{
    if($voucher['created_by'] != $_SESSION['user_id']){
        die("You can delete only your own vouchers.");
    }

    if(!in_array($voucher['status'],['Pending','Rejected'])){
        die("Only Pending or Rejected vouchers can be deleted.");
    }
}
$stmt = mysqli_prepare($conn,"
SELECT voucher_no
FROM vouchers
WHERE id=?");

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$voucher_no = $data['voucher_no'];

/*=========================
  DELETE
=========================*/

mysqli_begin_transaction($conn);

try{

    $stmt = mysqli_prepare($conn,"
    DELETE FROM voucher_details
    WHERE voucher_id=?");

    mysqli_stmt_bind_param($stmt,"i",$id);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conn,"
    DELETE FROM vouchers
    WHERE id=?");

    mysqli_stmt_bind_param($stmt,"i",$id);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conn);
    logActivity(
    $conn,
    "Receipt Voucher",
    "Delete",
    $id,
    "Deleted Receipt Voucher : ".$voucher_no
);

    header("Location: receipt_voucher.php?deleted=1");
    exit();

}catch(Exception $e){

    mysqli_rollback($conn);

    die("Unable to delete voucher.");
}