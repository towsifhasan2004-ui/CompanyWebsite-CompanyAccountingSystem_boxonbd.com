<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if(!isset($_GET['id']))
{
    header("Location:staff_advance_list.php");
    exit();
}

$id=(int)$_GET['id'];

/*=========================================
LOAD ADVANCE
=========================================*/

$stmt=mysqli_prepare($conn,"
SELECT
id,
voucher_id
FROM staff_advances
WHERE id=?
LIMIT 1
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$result=mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result)==0)
{
    die("Advance Record Not Found.");
}

$advance=mysqli_fetch_assoc($result);

/*=========================================
CHECK RECOVERY
=========================================*/

$stmt=mysqli_prepare($conn,"
SELECT COUNT(*) total
FROM staff_advance_recovery
WHERE advance_id=?
");

mysqli_stmt_bind_param($stmt,"i",$id);

mysqli_stmt_execute($stmt);

$count=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if($count['total']>0)
{
    echo "<script>
    alert('Recovery exists. Delete recovery first.');
    window.location='staff_advance_list.php';
    </script>";
    exit();
}/*=========================================
DELETE TRANSACTION
=========================================*/

mysqli_begin_transaction($conn);

try
{

    /*--------------------------------------
    DELETE VOUCHER DETAILS
    --------------------------------------*/

    if(!empty($advance['voucher_id']))
    {

        $stmt = mysqli_prepare($conn,"
        DELETE FROM voucher_details
        WHERE voucher_id=?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $advance['voucher_id']
        );

        mysqli_stmt_execute($stmt);

    }

    /*--------------------------------------
    DELETE VOUCHER
    --------------------------------------*/

    if(!empty($advance['voucher_id']))
    {

        $stmt = mysqli_prepare($conn,"
        DELETE FROM vouchers
        WHERE id=?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $advance['voucher_id']
        );

        mysqli_stmt_execute($stmt);

    }

    /*--------------------------------------
    DELETE STAFF ADVANCE
    --------------------------------------*/

    $stmt = mysqli_prepare($conn,"
    DELETE FROM staff_advances
    WHERE id=?
    ");

    mysqli_stmt_bind_param(
        $stmt,
        "i",
        $id
    );

    mysqli_stmt_execute($stmt);

    /*--------------------------------------
    COMMIT
    --------------------------------------*/

    mysqli_commit($conn);

    header("Location:staff_advance_list.php?deleted=1");
    exit();

}
catch(Exception $e)
{

    mysqli_rollback($conn);

    die("Delete Failed : ".$e->getMessage());

}

?>