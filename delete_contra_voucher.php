<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

if(!isset($_GET['id']))
{
    header("Location: contra_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================
   GET VOUCHER
========================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM vouchers
     WHERE id=?
     AND voucher_type='Contra'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    header("Location: contra_voucher.php");
    exit();
}

$voucher = mysqli_fetch_assoc($result);

/* ==========================
   ALLOW ONLY PENDING/REJECTED
========================== */

if(
    $voucher['status']!="Pending" &&
    $voucher['status']!="Rejected"
)
{
    die("Only Pending or Rejected Contra Vouchers can be deleted.");
}

mysqli_begin_transaction($conn);

try
{

    /* ==========================
       DELETE DETAILS
    ========================== */

    $stmt = mysqli_prepare(
        $conn,
        "DELETE
         FROM voucher_details
         WHERE voucher_id=?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    /* ==========================
       DELETE HEADER
    ========================== */

    $stmt = mysqli_prepare(
        $conn,
        "DELETE
         FROM vouchers
         WHERE id=?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    /* ==========================
       AUDIT LOG
    ========================== */

    logActivity(

        $conn,

        "Contra Voucher",

        "Delete",

        $id,

        "Deleted Contra Voucher : ".$voucher['voucher_no']

    );

    /* ==========================
       NOTIFY MANAGERS
    ========================== */

    $managerQuery = mysqli_query(
        $conn,
        "SELECT id
         FROM users
         WHERE role='manager'
         AND status='active'"
    );

    while($manager=mysqli_fetch_assoc($managerQuery))
    {

        addNotification(

            $conn,

            $manager['id'],

            "Contra Voucher Deleted",

            "Contra Voucher ".$voucher['voucher_no']." has been deleted.",

            "danger",

            "contra_voucher.php"

        );

    }

    mysqli_commit($conn);

    header("Location: contra_voucher.php?deleted=1");

    exit();

}
catch(Exception $e)
{

    mysqli_rollback($conn);

    die("Delete failed : ".$e->getMessage());

}

?>