<?php
require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* =========================
   FETCH ALL ACCOUNTS
========================= */

$sql = "
SELECT
    a.id,
    a.account_name,
    a.opening_balance,
    a.balance_type,

    COALESCE(SUM(vd.debit),0) AS total_debit,
    COALESCE(SUM(vd.credit),0) AS total_credit

FROM accounts a

LEFT JOIN voucher_details vd
ON vd.account_id = a.id

GROUP BY a.id

ORDER BY a.account_name ASC
";

$result = mysqli_query($conn,$sql);
?>
<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

Trial Balance

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

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>Account</th>
<th class="text-end">Debit</th>
<th class="text-end">Credit</th>

</tr>

</thead>

<tbody>

<?php

$total_debit = 0;
$total_credit = 0;

while($row = mysqli_fetch_assoc($result))
{
    $account_name = $row['account_name'];

    $opening = (float)$row['opening_balance'];
    $type = $row['balance_type'];

    $debit = (float)$row['total_debit'];
    $credit = (float)$row['total_credit'];

    /* =========================
       ADJUSTED BALANCE LOGIC
    ========================= */

    $balance = 0;

    if($type == 'Debit')
    {
        $balance = ($opening + $debit) - $credit;
    }
    else
    {
        $balance = ($opening + $credit) - $debit;
    }

    $debit_amount = 0;
    $credit_amount = 0;

    if($balance >= 0)
    {
        $debit_amount = $balance;
    }
    else
    {
        $credit_amount = abs($balance);
    }

    $total_debit += $debit_amount;
    $total_credit += $credit_amount;

?>

<tr>

<td><?= htmlspecialchars($account_name) ?></td>

<td class="text-end">
<?= number_format($debit_amount,2) ?>
</td>

<td class="text-end">
<?= number_format($credit_amount,2) ?>
</td>

</tr>

<?php } ?>

</tbody>

<tfoot class="table-dark">

<tr>

<th>Total</th>

<th class="text-end">
<?= number_format($total_debit,2) ?>
</th>

<th class="text-end">
<?= number_format($total_credit,2) ?>
</th>

</tr>

</tfoot>

</table>

<?php if(round($total_debit,2) == round($total_credit,2)) { ?>

<div class="alert alert-success mt-3">

✔ Trial Balance is Balanced

</div>

<?php } else { ?>

<div class="alert alert-danger mt-3">

❌ Difference Found:
<?= number_format(abs($total_debit - $total_credit),2) ?>

</div>

<?php } ?>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>