<?php
require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/* =========================
   INCOME ACCOUNTS
========================= */

$income_sql = "
SELECT
    a.id,
    a.account_name,
    COALESCE(SUM(vd.credit),0) - COALESCE(SUM(vd.debit),0) AS amount
FROM accounts a

INNER JOIN account_groups ag
ON ag.id = a.group_id

LEFT JOIN voucher_details vd
ON vd.account_id = a.id

WHERE ag.account_type = 'Income'

GROUP BY a.id, a.account_name
";

$income_result = mysqli_query($conn,$income_sql);

/* =========================
   EXPENSE ACCOUNTS
========================= */

$expense_sql = "
SELECT
    a.id,
    a.account_name,
    COALESCE(SUM(vd.debit),0) - COALESCE(SUM(vd.credit),0) AS amount
FROM accounts a

INNER JOIN account_groups ag
ON ag.id = a.group_id

LEFT JOIN voucher_details vd
ON vd.account_id = a.id

WHERE ag.account_type = 'Expense'

GROUP BY a.id, a.account_name
";

$expense_result = mysqli_query($conn,$expense_sql);
?>
<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

Income Statement (Profit & Loss)

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
     INCOME SECTION
========================= -->

<div class="col-md-6">

<div class="card border-success">

<div class="card-header bg-success text-white">

<h6 class="mb-0">Income</h6>

</div>

<div class="card-body">

<table class="table table-sm table-bordered">

<?php
$total_income = 0;

while($row = mysqli_fetch_assoc($income_result))
{
    $amount = (float)$row['amount'];
    $total_income += $amount;
?>

<tr>

<td><?= htmlspecialchars($row['account_name']) ?></td>

<td class="text-end">
<?= number_format($amount,2) ?>
</td>

</tr>

<?php } ?>

<tr class="table-success">

<th>Total Income</th>

<th class="text-end">
<?= number_format($total_income,2) ?>
</th>

</tr>

</table>

</div>

</div>

</div>

<!-- =========================
     EXPENSE SECTION
========================= -->

<div class="col-md-6">

<div class="card border-danger">

<div class="card-header bg-danger text-white">

<h6 class="mb-0">Expenses</h6>

</div>

<div class="card-body">

<table class="table table-sm table-bordered">

<?php
$total_expense = 0;

while($row = mysqli_fetch_assoc($expense_result))
{
    $amount = (float)$row['amount'];
    $total_expense += $amount;
?>

<tr>

<td><?= htmlspecialchars($row['account_name']) ?></td>

<td class="text-end">
<?= number_format($amount,2) ?>
</td>

</tr>

<?php } ?>

<tr class="table-danger">

<th>Total Expenses</th>

<th class="text-end">
<?= number_format($total_expense,2) ?>
</th>

</tr>

</table>

</div>

</div>

</div>

</div>

<!-- =========================
     NET PROFIT / LOSS
========================= -->

<div class="row mt-4">

<div class="col-md-12">

<div class="card">

<div class="card-body text-center">

<?php
$profit = $total_income - $total_expense;
?>

<h4>

<?php if($profit >= 0) { ?>

<span class="text-success">
Net Profit: <?= number_format($profit,2) ?>
</span>

<?php } else { ?>

<span class="text-danger">
Net Loss: <?= number_format(abs($profit),2) ?>
</span>

<?php } ?>

</h4>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>