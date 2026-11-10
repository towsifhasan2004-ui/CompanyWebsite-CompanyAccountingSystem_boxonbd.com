<?php
require_once 'auth.php';
require_once 'config/db.php';

$account_id = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;

$account = null;
$rows = [];

if($account_id > 0)
{
    /* =========================
       Get Account Info
    ========================= */

    $stmt = mysqli_prepare($conn,"
        SELECT * FROM accounts WHERE id=?
    ");

    mysqli_stmt_bind_param($stmt,"i",$account_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);

    /* =========================
       Get Ledger Transactions
    ========================= */

    $sql = "
        SELECT
            v.voucher_no,
            v.voucher_date,
            v.voucher_type,
            vd.debit,
            vd.credit,
            v.narration
        FROM voucher_details vd

        INNER JOIN vouchers v
        ON v.id = vd.voucher_id

        WHERE vd.account_id = $account_id

        ORDER BY v.voucher_date ASC, v.id ASC
    ";

    $rows = mysqli_query($conn,$sql);
}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row">

<!-- =========================
     ACCOUNT SELECT
========================= -->

<div class="col-lg-12 mb-3">

<div class="card shadow">

<div class="card-body">

<div class="text-center mb-3">

    <h3>Boxon Consultancy BD Ltd.</h3>
    <p>Dhaka, Bangladesh</p>
    <hr>

</div>

<div class="no-print">
<form method="GET" class="row">

<div class="col-md-6">

<select name="account_id" class="form-select" required>

<option value="">Select Account</option>

<?php
$acc=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
ORDER BY account_name
");

while($a=mysqli_fetch_assoc($acc)){
?>

<option value="<?= $a['id'] ?>"
<?= ($account_id==$a['id'])?'selected':'' ?>>

<?= htmlspecialchars($a['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-2">

<button class="btn btn-primary w-100">
View Ledger
</button>

</div>

</form>
</div>

</div>

</div>

<?php if($account_id == 0){ ?>

<div class="col-12">
    <div class="alert alert-info">
        Please select an account to view ledger.
    </div>
</div>

</div></div></div>

<?php include 'footer.php'; exit(); } ?>

<!-- =========================
     LEDGER REPORT
========================= -->

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

Ledger - <?= htmlspecialchars($account['account_name']) ?>

</h5>

<button onclick="window.print()" class="btn btn-danger btn-sm no-print">
    <i class="fa fa-print"></i> Print
</button>

</div>

<div class="card-body">

<table class="table table-bordered table-sm">

<thead class="table-dark">

<tr>

<th>Date</th>
<th>Voucher</th>
<th>Type</th>
<th>Narration</th>
<th class="text-end">Debit</th>
<th class="text-end">Credit</th>
<th class="text-end">Balance</th>

</tr>

</thead>

<tbody>

<?php

$opening_balance = (float)$account['opening_balance'];
$balance_type    = $account['balance_type'];

$running_balance = $opening_balance;

if($opening_balance > 0)
{
?>

<tr class="table-secondary">

<td>-</td>
<td>-</td>
<td>-</td>
<td><b>Opening Balance</b></td>

<td class="text-end">
<?= ($balance_type=='Debit') ? number_format($opening_balance,2) : '0.00' ?>
</td>

<td class="text-end">
<?= ($balance_type=='Credit') ? number_format($opening_balance,2) : '0.00' ?>
</td>

<td class="text-end">
<?= number_format($running_balance,2) ?>
</td>

</tr>

<?php
}

$total_debit = 0;
$total_credit = 0;

while($row = mysqli_fetch_assoc($rows))
{
    $debit  = (float)$row['debit'];
    $credit = (float)$row['credit'];

    $total_debit += $debit;
    $total_credit += $credit;

    if($balance_type == 'Debit')
    {
        $running_balance += $debit - $credit;
    }
    else
    {
        $running_balance += $credit - $debit;
    }
?>

<tr>

<td>
<?= date('d-M-Y',strtotime($row['voucher_date'])) ?>
</td>

<td>
<b><?= $row['voucher_no'] ?></b>
</td>

<td>
<span class="badge bg-info">
<?= $row['voucher_type'] ?>
</span>
</td>

<td>
<?= htmlspecialchars($row['narration']) ?>
</td>

<td class="text-end">
<?= number_format($debit,2) ?>
</td>

<td class="text-end">
<?= number_format($credit,2) ?>
</td>

<td class="text-end">
<?= number_format($running_balance,2) ?>
</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-dark">

<tr>

<th colspan="4">Total</th>

<th class="text-end">
<?= number_format($total_debit,2) ?>
</th>

<th class="text-end">
<?= number_format($total_credit,2) ?>
</th>

<th></th>

</tr>

</tfoot>

</table>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>