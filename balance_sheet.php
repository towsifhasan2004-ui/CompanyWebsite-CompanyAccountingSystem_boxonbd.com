<?php
require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* =========================
   ASSETS
========================= */

$asset_sql = "
SELECT
    a.account_name,
    COALESCE(SUM(vd.debit),0) - COALESCE(SUM(vd.credit),0) AS amount
FROM accounts a

INNER JOIN account_groups ag
ON ag.id = a.group_id

LEFT JOIN voucher_details vd
ON vd.account_id = a.id

WHERE ag.account_type = 'Asset'

GROUP BY a.id
";

$asset_result = mysqli_query($conn,$asset_sql);

/* =========================
   LIABILITIES
========================= */

$liability_sql = "
SELECT
    a.account_name,
    COALESCE(SUM(vd.credit),0) - COALESCE(SUM(vd.debit),0) AS amount
FROM accounts a

INNER JOIN account_groups ag
ON ag.id = a.group_id

LEFT JOIN voucher_details vd
ON vd.account_id = a.id

WHERE ag.account_type = 'Liability'

GROUP BY a.id
";

$liability_result = mysqli_query($conn,$liability_sql);
?>
<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

Balance Sheet

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

<div class="no-print mb-3">
    <button onclick="window.print()" class="btn btn-danger btn-sm">
        <i class="fa fa-print"></i> Print
    </button>
</div>

<div class="row">

<!-- =========================
     ASSETS
========================= -->

<div class="col-md-4">

<div class="card border-success">

<div class="card-header bg-success text-white">

Assets

</div>

<div class="card-body">

<table class="table table-sm table-bordered">

<?php
$total_assets = 0;

while($row = mysqli_fetch_assoc($asset_result))
{
    $amount = (float)$row['amount'];
    $total_assets += $amount;
?>

<tr>

<td><?= htmlspecialchars($row['account_name']) ?></td>

<td class="text-end">
<?= number_format($amount,2) ?>
</td>

</tr>

<?php } ?>

<tr class="table-success">

<th>Total Assets</th>

<th class="text-end">
<?= number_format($total_assets,2) ?>
</th>

</tr>

</table>

</div>

</div>

</div>

<!-- =========================
     LIABILITIES
========================= -->

<div class="col-md-4">

<div class="card border-danger">

<div class="card-header bg-danger text-white">

Liabilities

</div>

<div class="card-body">

<table class="table table-sm table-bordered">

<?php
$total_liabilities = 0;

while($row = mysqli_fetch_assoc($liability_result))
{
    $amount = (float)$row['amount'];
    $total_liabilities += $amount;
?>

<tr>

<td><?= htmlspecialchars($row['account_name']) ?></td>

<td class="text-end">
<?= number_format($amount,2) ?>
</td>

</tr>

<?php } ?>

<tr class="table-danger">

<th>Total Liabilities</th>

<th class="text-end">
<?= number_format($total_liabilities,2) ?>
</th>

</tr>

</table>

</div>

</div>

</div>

<!-- =========================
     EQUITY
========================= -->

<div class="col-md-4">

<div class="card border-primary">

<div class="card-header bg-primary text-white">

Equity

</div>

<div class="card-body">

<table class="table table-sm table-bordered">

<?php
/* =========================
   GET NET PROFIT
========================= */

$income_sql = "
SELECT COALESCE(SUM(vd.credit),0) - COALESCE(SUM(vd.debit),0) AS income
FROM voucher_details vd
INNER JOIN accounts a ON a.id = vd.account_id
INNER JOIN account_groups ag ON ag.id = a.group_id
WHERE ag.account_type = 'Income'
";

$expense_sql = "
SELECT COALESCE(SUM(vd.debit),0) - COALESCE(SUM(vd.credit),0) AS expense
FROM voucher_details vd
INNER JOIN accounts a ON a.id = vd.account_id
INNER JOIN account_groups ag ON ag.id = a.group_id
WHERE ag.account_type = 'Expense'
";

$income = mysqli_fetch_assoc(mysqli_query($conn,$income_sql))['income'];
$expense = mysqli_fetch_assoc(mysqli_query($conn,$expense_sql))['expense'];

$profit = $income - $expense;
?>

<tr>

<td>Capital / Retained Earnings</td>

<td class="text-end">
<?= number_format($profit,2) ?>
</td>

</tr>

<tr class="table-primary">

<th>Total Equity</th>

<th class="text-end">
<?= number_format($profit,2) ?>
</th>

</tr>

</table>

</div>

</div>

</div>

</div>

<!-- =========================
     FINAL CHECK
========================= -->

<div class="row mt-4">

<div class="col-md-12">

<div class="card">

<div class="card-body text-center">

<?php
$total_liabilities_equity = $total_liabilities + $profit;
?>

<h5>

Assets: <?= number_format($total_assets,2) ?> |
Liabilities + Equity: <?= number_format($total_liabilities_equity,2) ?>

</h5>

<br>

<?php if(round($total_assets,2) == round($total_liabilities_equity,2)) { ?>

<span class="text-success fw-bold">
✔ Balance Sheet Balanced
</span>

<?php } else { ?>

<span class="text-danger fw-bold">
❌ Difference: <?= number_format(abs($total_assets - $total_liabilities_equity),2) ?>
</span>

<?php } ?>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>