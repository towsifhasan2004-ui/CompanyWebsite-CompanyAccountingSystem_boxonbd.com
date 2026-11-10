<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-danger text-white">

<h4 class="mb-0">

<i class="fa-solid fa-triangle-exclamation"></i>

Outstanding Staff Advance Report

</h4>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-striped datatable">

<thead class="table-dark">

<tr>

<th>Employee Code</th>

<th>Employee Name</th>

<th>Department</th>

<th>Designation</th>

<th class="text-end">Advance</th>

<th class="text-end">Recovered</th>

<th class="text-end">Balance</th>

<th width="120">Action</th>

</tr>

</thead>

<tbody>

<?php

$query = mysqli_query($conn, "

SELECT

    e.id,

    e.employee_code,

    e.employee_name,

    e.department,

    e.designation,

    SUM(sa.amount) AS total_advance,

    SUM(sa.recovered_amount) AS total_recovered,

    SUM(sa.balance_amount) AS total_balance

FROM employees e

INNER JOIN staff_advances sa
    ON sa.employee_id = e.id

GROUP BY
    e.id,
    e.employee_code,
    e.employee_name,
    e.department,
    e.designation

HAVING SUM(sa.balance_amount) > 0

ORDER BY e.employee_name ASC

");

$grandAdvance  = 0;
$grandRecovered = 0;
$grandBalance  = 0;

while($row = mysqli_fetch_assoc($query))
{

    $grandAdvance += $row['total_advance'];
    $grandRecovered += $row['total_recovered'];
    $grandBalance += $row['total_balance'];

?>

<tr>

<td>

<?= htmlspecialchars($row['employee_code']) ?>

</td>

<td>

<?= htmlspecialchars($row['employee_name']) ?>

</td>

<td>

<?= htmlspecialchars($row['department']) ?>

</td>

<td>

<?= htmlspecialchars($row['designation']) ?>

</td>

<td class="text-end">

<?= number_format($row['total_advance'],2) ?>

</td>

<td class="text-end text-success">

<?= number_format($row['total_recovered'],2) ?>

</td>

<td class="text-end text-danger fw-bold">

<?= number_format($row['total_balance'],2) ?>

</td>

<td>

<a
href="employee_advance_ledger.php?employee_id=<?= $row['id'] ?>"
class="btn btn-primary btn-sm">

<i class="fa-solid fa-book"></i>

Ledger

</a>

</td>

</tr>

<?php

}

?></tbody>

<tfoot class="table-dark">

<tr>

<th colspan="4" class="text-end">

GRAND TOTAL

</th>

<th class="text-end">

<?= number_format($grandAdvance,2) ?>

</th>

<th class="text-end text-success">

<?= number_format($grandRecovered,2) ?>

</th>

<th class="text-end text-warning">

<?= number_format($grandBalance,2) ?>

</th>

<th></th>

</tr>

</tfoot>

</table>

</div>

<hr>

<div class="row">

<div class="col-md-4">

<div class="card border-primary shadow-sm">

<div class="card-body text-center">

<h6>Total Advance</h6>

<h3 class="text-primary">

৳ <?= number_format($grandAdvance,2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-success shadow-sm">

<div class="card-body text-center">

<h6>Total Recovery</h6>

<h3 class="text-success">

৳ <?= number_format($grandRecovered,2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-danger shadow-sm">

<div class="card-body text-center">

<h6>Outstanding Balance</h6>

<h3 class="text-danger">

৳ <?= number_format($grandBalance,2) ?>

</h3>

</div>

</div>

</div>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>