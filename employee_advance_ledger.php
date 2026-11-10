<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

/*=========================================
EMPLOYEE LIST
=========================================*/

$employees = mysqli_query($conn,"
SELECT
    id,
    employee_code,
    employee_name
FROM employees
WHERE status='Active'
ORDER BY employee_name
");

/*=========================================
SELECTED EMPLOYEE
=========================================*/

$employee_id = isset($_GET['employee_id'])
    ? (int)$_GET['employee_id']
    : 0;

?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">

<i class="fa-solid fa-book"></i>

Employee Advance Ledger

</h4>

</div>

<div class="card-body">

<form method="GET">

<div class="row">

<div class="col-md-5">

<label class="form-label">

Employee

</label>

<select
name="employee_id"
class="form-select"
required>

<option value="">Select Employee</option>

<?php while($emp=mysqli_fetch_assoc($employees)){ ?>

<option
value="<?= $emp['id'] ?>"
<?= ($employee_id==$emp['id'])?'selected':'' ?>>

<?= htmlspecialchars($emp['employee_code']) ?>

-

<?= htmlspecialchars($emp['employee_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-2 d-flex align-items-end">

<button
class="btn btn-primary w-100">

<i class="fa-solid fa-search"></i>

View Ledger

</button>

</div>

</div>

</form>

<hr><?php

if($employee_id>0)
{

/*=========================================
EMPLOYEE INFORMATION
=========================================*/

$empStmt = mysqli_prepare($conn,"
SELECT
    employee_code,
    employee_name,
    department,
    designation
FROM employees
WHERE id=?
");

mysqli_stmt_bind_param($empStmt,"i",$employee_id);

mysqli_stmt_execute($empStmt);

$employee = mysqli_fetch_assoc(
    mysqli_stmt_get_result($empStmt)
);

if($employee)
{

/*=========================================
ADVANCE + RECOVERY LEDGER
=========================================*/

$ledger = mysqli_query($conn,"

SELECT

    advance_date AS trans_date,

    'Advance' AS particulars,

    amount AS advance_amount,

    0 AS recovery_amount,

    remarks

FROM staff_advances

WHERE employee_id=$employee_id

UNION ALL

SELECT

    recovery_date,

    'Recovery',

    0,

    amount,

    remarks

FROM staff_advance_recoveries r

INNER JOIN staff_advances s

ON s.id=r.advance_id

WHERE s.employee_id=$employee_id

ORDER BY trans_date ASC

");

?>

<div class="row mb-3">

<div class="col-md-3">

<strong>Employee Code :</strong><br>

<?= htmlspecialchars($employee['employee_code']) ?>

</div>

<div class="col-md-3">

<strong>Employee Name :</strong><br>

<?= htmlspecialchars($employee['employee_name']) ?>

</div>

<div class="col-md-3">

<strong>Department :</strong><br>

<?= htmlspecialchars($employee['department']) ?>

</div>

<div class="col-md-3">

<strong>Designation :</strong><br>

<?= htmlspecialchars($employee['designation']) ?>

</div>

</div>

<div class="table-responsive">

<table class="table table-bordered table-striped">

<thead class="table-dark">

<tr>

<th width="120">Date</th>

<th>Particular</th>

<th class="text-end">Advance</th>

<th class="text-end">Recovery</th>

<th class="text-end">Balance</th>

<th>Remarks</th>

</tr>

</thead>

<tbody>

<?php

$balance = 0;while($row = mysqli_fetch_assoc($ledger))
{

    if($row['advance_amount'] > 0)
    {
        $balance += $row['advance_amount'];
    }

    if($row['recovery_amount'] > 0)
    {
        $balance -= $row['recovery_amount'];
    }

?>

<tr>

<td>

<?= date('d-m-Y',strtotime($row['trans_date'])) ?>

</td>

<td>

<?php if($row['particulars']=="Advance"){ ?>

<span class="badge bg-primary">

Advance

</span>

<?php }else{ ?>

<span class="badge bg-success">

Recovery

</span>

<?php } ?>

</td>

<td class="text-end">

<?= ($row['advance_amount']>0)
        ? number_format($row['advance_amount'],2)
        : '-' ?>

</td>

<td class="text-end">

<?= ($row['recovery_amount']>0)
        ? number_format($row['recovery_amount'],2)
        : '-' ?>

</td>

<td class="text-end fw-bold">

<?= number_format($balance,2) ?>

</td>

<td>

<?= htmlspecialchars($row['remarks']) ?>

</td>

</tr>

<?php

}

?><?php

/*=========================================
TOTAL ADVANCE
=========================================*/

$totalAdvance = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT
IFNULL(SUM(amount),0) total
FROM staff_advances
WHERE employee_id=$employee_id
"))['total'];

/*=========================================
TOTAL RECOVERY
=========================================*/

$totalRecovery = mysqli_fetch_assoc(mysqli_query($conn,"
SELECT
IFNULL(SUM(r.amount),0) total
FROM staff_advance_recoveries r
INNER JOIN staff_advances s
ON s.id=r.advance_id
WHERE s.employee_id=$employee_id
"))['total'];

$outstanding = $totalAdvance - $totalRecovery;

?>

</tbody>

<tfoot class="table-secondary">

<tr>

<th colspan="2" class="text-end">

TOTAL

</th>

<th class="text-end text-primary">

<?= number_format($totalAdvance,2) ?>

</th>

<th class="text-end text-success">

<?= number_format($totalRecovery,2) ?>

</th>

<th class="text-end <?= ($outstanding>0)?'text-danger':'text-success' ?>">

<?= number_format($outstanding,2) ?>

</th>

<th></th>

</tr>

</tfoot>

</table>

</div>

<div class="row mt-4">

<div class="col-md-4">

<div class="card border-primary">

<div class="card-body text-center">

<h6>Total Advance</h6>

<h3 class="text-primary">

৳ <?= number_format($totalAdvance,2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-success">

<div class="card-body text-center">

<h6>Total Recovery</h6>

<h3 class="text-success">

৳ <?= number_format($totalRecovery,2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-danger">

<div class="card-body text-center">

<h6>Outstanding Balance</h6>

<h3 class="<?= ($outstanding>0)?'text-danger':'text-success' ?>">

৳ <?= number_format($outstanding,2) ?>

</h3>

</div>

</div>

</div>

</div>

<?php

}
else
{

?>

<div class="alert alert-warning">

Employee not found.

</div>

<?php

}

}

?>

</div>

</div>

</div>

<?php include 'footer.php'; ?>