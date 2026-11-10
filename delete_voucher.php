<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['accountant']);

if(!isset($_GET['id']))
{
    header("Location:voucher_list.php");
    exit();
}

$id = (int)$_GET['id'];

/*=========================================
LOAD VOUCHER
=========================================*/

$stmt = mysqli_prepare($conn,"
SELECT
    id,
    status
FROM vouchers
WHERE id=?
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Voucher not found.");
}

$voucher = mysqli_fetch_assoc($result);

/*=========================================
ONLY PENDING VOUCHER CAN BE DELETED
=========================================*/

if($voucher['status']!="Pending")
{
    die("Only Pending Voucher can be deleted.");
}

mysqli_begin_transaction($conn);

try{

    /*=========================================
    DELETE DETAILS
    =========================================*/

    mysqli_query($conn,"
    DELETE FROM voucher_details
    WHERE voucher_id=$id
    ");

    /*=========================================
    DELETE HEADER
    =========================================*/

    mysqli_query($conn,"
    DELETE FROM vouchers
    WHERE id=$id
    ");

    mysqli_commit($conn);

    header("Location:voucher_list.php?deleted=1");

    exit();

}
catch(Exception $e)
{

    mysqli_rollback($conn);

    die($e->getMessage());

}

?>