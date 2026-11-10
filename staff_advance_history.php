<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

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

sa.*,

e.employee_code,
e.employee_name

FROM staff_advances sa

INNER JOIN employees e
ON e.id=sa.employee_id

WHERE sa.id=?

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
RECOVERY HISTORY
=========================================*/

$history=mysqli_query($conn,"

SELECT

recovery_date,

amount,

remarks

FROM staff_advance_recovery

WHERE advance_id=".$advance['id']."

ORDER BY recovery_date ASC,id ASC

");

?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between">

<h4>

<i class="fa-solid fa-book"></i>

Staff Advance Ledger

</h4>

<a
href="staff_advance_list.php"
class="btn btn-light">

Back

</a>

</div>

<div class="card-body">

<div class="row mb-4">

<div class="col-md-3">

<label>

Employee

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($advance['employee_code']) ?> - <?= htmlspecialchars($advance['employee_name']) ?>"
readonly>

</div>

<div class="col-md-3">

<label>

Advance No

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($advance['advance_no']) ?>"
readonly>

</div>

<div class="col-md-3">

<label>

Advance Date

</label>

<input
type="text"
class="form-control"
value="<?= date('d-M-Y',strtotime($advance['advance_date'])) ?>"
readonly>

</div>

<div class="col-md-3">

<label>

Advance Amount

</label>

<input
type="text"
class="form-control fw-bold text-danger"
value="<?= number_format($advance['amount'],2) ?>"
readonly>

</div>

</div>

<div class="table-responsive">

<table class="table table-bordered table-hover">

<thead class="table-dark">

<tr>

<th>Date</th>

<th>Particulars</th>

<th>Remarks</th>

<th class="text-end">

Advance

</th>

<th class="text-end">

Recovery

</th>

<th class="text-end">

Balance

</th>

</tr>

</thead>

<tbody><?php

$balance = (float)$advance['amount'];

/*=========================================
OPENING ADVANCE
=========================================*/
?>

<tr>

<td>

<?= date('d-M-Y',strtotime($advance['advance_date'])) ?>

</td>

<td>

<span class="badge bg-danger">

Advance Paid

</span>

</td>

<td>

<?= htmlspecialchars($advance['remarks']) ?>

</td>

<td class="text-end text-danger fw-bold">

<?= number_format($advance['amount'],2) ?>

</td>

<td class="text-end">

-

</td>

<td class="text-end fw-bold text-danger">

<?= number_format($balance,2) ?>

</td>

</tr>

<?php

/*=========================================
RECOVERY ENTRIES
=========================================*/

$totalRecovery = 0;

while($row = mysqli_fetch_assoc($history))
{

    $balance -= (float)$row['amount'];

    $totalRecovery += (float)$row['amount'];

?>

<tr>

<td>

<?= date('d-M-Y',strtotime($row['recovery_date'])) ?>

</td>

<td>

<span class="badge bg-success">

Recovery

</span>

</td>

<td>

<?= htmlspecialchars($row['remarks']) ?>

</td>

<td class="text-end">

-

</td>

<td class="text-end text-success fw-bold">

<?= number_format($row['amount'],2) ?>

</td>

<td class="text-end fw-bold">

<?php

if($balance>0)
{

?>

<span class="text-danger">

<?= number_format($balance,2) ?>

</span>

<?php

}
else
{

?>

<span class="text-success">

0.00

</span>

<?php

}

?>

</td>

</tr>

<?php

}

?></tbody>

<tfoot class="table-dark">

<tr>

<th colspan="3" class="text-end">

Total

</th>

<th class="text-end text-danger">

<?= number_format($advance['amount'],2) ?>

</th>

<th class="text-end text-success">

<?= number_format($totalRecovery,2) ?>

</th>

<th class="text-end">

<?php

if($balance>0)
{

?>

<span class="text-warning">

<?= number_format($balance,2) ?>

</span>

<?php

}
else
{

?>

<span class="text-success">

0.00

</span>

<?php

}

?>

</th>

</tr>

</tfoot>

</table>

</div>

<hr>

<div class="row">

<div class="col-md-4">

<div class="card border-danger">

<div class="card-body text-center">

<h6>Total Advance</h6>

<h3 class="text-danger">

<?= number_format($advance['amount'],2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-success">

<div class="card-body text-center">

<h6>Total Recovery</h6>

<h3 class="text-success">

<?= number_format($totalRecovery,2) ?>

</h3>

</div>

</div>

</div>

<div class="col-md-4">

<div class="card border-primary">

<div class="card-body text-center">

<h6>Outstanding Balance</h6>

<h3 class="<?=($balance>0)?'text-danger':'text-success'?>">

<?= number_format($balance,2) ?>

</h3>

</div>

</div>

</div>

</div>

<div class="text-end mt-4">

<button
class="btn btn-primary"
onclick="window.print()">

<i class="fa-solid fa-print"></i>

Print Ledger

</button>

<a
href="staff_advance_list.php"
class="btn btn-secondary">

Back

</a>

</div>

</div>

</div>

</div>

</div>

</div>

<script>

$(document).ready(function(){

    $('.table').DataTable({

        paging:false,

        searching:false,

        info:false,

        ordering:false,

        responsive:true

    });

});

</script>

<?php include 'footer.php'; ?>