<?php

require_once 'auth.php';
require_once 'config/db.php';

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$sql = mysqli_query($conn,"
SELECT
s.id,
s.employee_id,
s.employee_name,

IFNULL(
(
SELECT SUM(amount)
FROM staff_advance
WHERE employee_id=s.id
AND transaction_type='Advance'
),0) total_advance,

IFNULL(
(
SELECT SUM(amount)
FROM staff_advance
WHERE employee_id=s.id
AND transaction_type='Recovery'
),0) total_recovery

FROM staff s
ORDER BY s.employee_name
");

?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4 class="mb-0">
Staff Advance Report
</h4>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-striped datatable">

<thead class="table-dark">

<tr>

<th>ID</th>
<th>Employee</th>
<th>Total Advance</th>
<th>Total Recovery</th>
<th>Outstanding</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($sql)){ ?>

<?php
$balance =
$row['total_advance']-
$row['total_recovery'];
?>

<tr>

<td>
<?= htmlspecialchars($row['employee_id']) ?>
</td>

<td>
<?= htmlspecialchars($row['employee_name']) ?>
</td>

<td class="text-end text-danger">
<?= number_format($row['total_advance'],2) ?>
</td>

<td class="text-end text-success">
<?= number_format($row['total_recovery'],2) ?>
</td>

<td class="text-end fw-bold">

<?php

if($balance>0){

echo "<span class='text-danger'>".
number_format($balance,2).
"</span>";

}else{

echo "<span class='text-success'>0.00</span>";

}

?>

</td>

<td>

<a
href="staff_advance_history.php?id=<?= $row['id'] ?>"
class="btn btn-primary btn-sm">

History

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>