<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager','accountant']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

/*=========================================
STAFF ADVANCE LIST
=========================================*/

$advances = mysqli_query($conn,"

SELECT

sa.id,
sa.advance_no,
sa.advance_date,

e.employee_code,
e.employee_name,

sa.amount,

IFNULL(
(
SELECT SUM(amount)
FROM staff_advance_recovery r
WHERE r.advance_id=sa.id
),0
) recovered,

sa.reference_no,
sa.remarks

FROM staff_advances sa

INNER JOIN employees e
ON e.id=sa.employee_id

ORDER BY

sa.advance_date DESC,
sa.id DESC

");

?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">

<h4 class="mb-0">

<i class="fa-solid fa-money-bill-wave"></i>

Staff Advance List

</h4>

<a
href="staff_advance.php"
class="btn btn-light">

<i class="fa fa-plus"></i>

New Advance

</a>

</div>

<div class="card-body">

<?php

if(isset($_GET['success']))
{

?>

<div class="alert alert-success">

Staff Advance Saved Successfully.

</div>

<?php

}

?>

<div class="table-responsive">

<table
id="advanceTable"
class="table table-bordered table-hover table-striped">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Date</th>

<th>Advance No</th>

<th>Employee</th>

<th class="text-end">Advance</th>

<th class="text-end">Recovered</th>

<th class="text-end">Outstanding</th>

<th width="220">Action</th>

</tr>

</thead>

<tbody><?php

$sl = 1;

while($row = mysqli_fetch_assoc($advances))
{

    $outstanding = $row['amount'] - $row['recovered'];

?>

<tr>

<td>

<?= $sl++; ?>

</td>

<td>

<?= date('d-M-Y',strtotime($row['advance_date'])) ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['advance_no']) ?>

</strong>

</td>

<td>

<?= htmlspecialchars($row['employee_code']) ?>

-

<?= htmlspecialchars($row['employee_name']) ?>

</td>

<td class="text-end">

<?= number_format($row['amount'],2) ?>

</td>

<td class="text-end text-success">

<?= number_format($row['recovered'],2) ?>

</td>

<td class="text-end">

<?php

if($outstanding>0)
{

?>

<span class="fw-bold text-danger">

<?= number_format($outstanding,2) ?>

</span>

<?php

}
else
{

?>

<span class="fw-bold text-success">

0.00

</span>

<?php

}

?>

</td>

<td class="text-center">

<a
href="staff_advance_recovery.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-sm">

<i class="fa-solid fa-money-bill-transfer"></i>

Recover

</a>

<a
href="employee_ledger.php?employee=<?= urlencode($row['employee_code']) ?>"
class="btn btn-info btn-sm">

<i class="fa-solid fa-book"></i>

Ledger

</a>

<a
href="staff_advance_print.php?id=<?= $row['id'] ?>"
class="btn btn-primary btn-sm">

<i class="fa-solid fa-print"></i>

</a>

<a
href="staff_advance_delete.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this advance?')">

<i class="fa-solid fa-trash"></i>

</a>

</td>

</tr>

<?php

}

?></tbody>

</table>

</div>

</div>

</div>

</div>

</div>

</div>

<script>

$(document).ready(function(){

    $('#advanceTable').DataTable({

        responsive:true,

        pageLength:10,

        ordering:true,

        searching:true,

        info:true,

        autoWidth:false,

        order:[[1,'desc']],

        columnDefs:[

            {

                orderable:false,

                targets:[7]

            }

        ]

    });

});

</script>

<?php include 'footer.php'; ?>