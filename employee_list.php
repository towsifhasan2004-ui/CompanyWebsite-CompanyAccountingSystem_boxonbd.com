<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$employees = mysqli_query($conn,"
SELECT *
FROM employees
ORDER BY employee_code ASC
");

?>

<div class="container-fluid">

<div class="row">

<div class="col-lg-12">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between">

<h4 class="mb-0">

<i class="fa-solid fa-users"></i>

Employee List

</h4>

<a
href="employee.php"
class="btn btn-light btn-sm">

<i class="fa-solid fa-plus"></i>

Add Employee

</a>

</div>

<div class="card-body">

<div class="table-responsive">

<table
id="employeeTable"
class="table table-bordered table-hover table-striped">

<thead class="table-dark">

<tr>

<th>#</th>

<th>Employee Code</th>

<th>Name</th>

<th>Department</th>

<th>Designation</th>

<th>Mobile</th>

<th>Salary</th>

<th>Status</th>

<th width="180">Action</th>

</tr>

</thead>

<tbody><?php

$sl = 1;

while($row = mysqli_fetch_assoc($employees))
{

?>

<tr>

<td>

<?= $sl++; ?>

</td>

<td>

<strong>

<?= htmlspecialchars($row['employee_code']) ?>

</strong>

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

<td>

<?= htmlspecialchars($row['mobile']) ?>

</td>

<td class="text-end">

<?= number_format($row['basic_salary'],2) ?>

</td>

<td class="text-center">

<?php

if($row['status']=="Active")
{

?>

<span class="badge bg-success">

Active

</span>

<?php

}
else
{

?>

<span class="badge bg-danger">

Inactive

</span>

<?php

}

?>

</td>

<td class="text-center">

<a
href="employee_edit.php?id=<?= $row['id'] ?>"
class="btn btn-warning btn-sm">

<i class="fa-solid fa-pen"></i>

</a>

<a
href="employee_delete.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this employee?')">

<i class="fa-solid fa-trash"></i>

</a>

<a
href="employee_ledger.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm">

<i class="fa-solid fa-book"></i>

</a>

<a
href="staff_advance.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-sm">

<i class="fa-solid fa-money-bill-wave"></i>

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

    $('#employeeTable').DataTable({

        responsive:true,

        pageLength:10,

        ordering:true,

        searching:true,

        info:true,

        lengthMenu:[
            [10,25,50,100,-1],
            [10,25,50,100,"All"]
        ],

        columnDefs:[

            {
                orderable:false,
                targets:[8]
            }

        ]

    });

});

</script>

<?php include 'footer.php'; ?>