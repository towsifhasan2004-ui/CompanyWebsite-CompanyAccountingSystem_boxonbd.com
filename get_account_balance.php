<?php

require_once 'auth.php';
require_once 'config/db.php';

if(!isset($_GET['account_id']))
{
    exit;
}

$account_id = (int)$_GET['account_id'];

$sql = mysqli_query(

    $conn,

    "SELECT
        IFNULL(SUM(debit)-SUM(credit),0) AS balance
     FROM voucher_details
     WHERE account_id='$account_id'"

);

$row = mysqli_fetch_assoc($sql);

echo number_format((float)$row['balance'],2);