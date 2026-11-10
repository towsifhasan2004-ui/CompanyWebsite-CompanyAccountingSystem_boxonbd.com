<?php
require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* =========================
   GET BANK ACCOUNTS
========================= */

$bank_accounts = [];

$res = mysqli_query($conn,"
SELECT id
FROM accounts
WHERE LOWER(account_name) LIKE '%bank%'
");

while($r = mysqli_fetch_assoc($res))
{
    $bank_accounts[] = $r['id'];
}

$bank_ids = implode(',', $bank_accounts);

if($bank_ids == "")
{
    $bank_ids = "0";
}

/* =========================
   FETCH BANK TRANSACTIONS
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

WHERE vd.account_id IN ($bank_ids)

ORDER BY v.voucher_date ASC, v.id ASC
";

$result = mysqli_query($conn,$sql);
?>
<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

Bank Book

</h5>

<button onclick="window.print()" class="btn btn-danger btn-sm no-print">
    <i class="fa fa-print"></i> Print
</button>

</div>

<div class="card-body">

<div class="text-center mb-3">

    <h3>Boxon Consultancy BD Ltd.</h3>
    <p>Dhaka, Bangladesh</p>
    <hr>

</div>

<div class="no-print">
    <div class="mb-3">
        <button onclick="window.print()" class="btn btn-danger btn-sm">
            <i class="fa fa-print"></i> Print
        </button>
    </div>
</div>

<table class="table table-bordered table-hover table-sm">

<thead class="table-dark">

<tr>

<th>Date</th>
<th>Voucher</th>
<th>Type</th>
<th>Narration</th>
<th class="text-end">Bank In</th>
<th class="text-end">Bank Out</th>
<th class="text-end">Balance</th>

</tr>

</thead>

<tbody>

<?php

$balance = 0;

$total_in = 0;
$total_out = 0;

while($row = mysqli_fetch_assoc($result))
{
    $debit  = (float)$row['debit'];
    $credit = (float)$row['credit'];

    $bank_in  = $debit;
    $bank_out = $credit;

    $total_in  += $bank_in;
    $total_out += $bank_out;

    /* =========================
       RUNNING BALANCE
    ========================= */

    $balance += $bank_in - $bank_out;

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
<?= number_format($bank_in,2) ?>
</td>

<td class="text-end">
<?= number_format($bank_out,2) ?>
</td>

<td class="text-end">
<?= number_format($balance,2) ?>
</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-dark">

<tr>

<th colspan="4">Total</th>

<th class="text-end">
<?= number_format($total_in,2) ?>
</th>

<th class="text-end">
<?= number_format($total_out,2) ?>
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