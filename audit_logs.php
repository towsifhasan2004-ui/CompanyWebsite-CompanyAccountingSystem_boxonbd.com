<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-dark text-white">

<h4>

<i class="fa-solid fa-clock-rotate-left"></i>

Audit Logs

</h4>

</div>

<div class="card-body">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th width="70">ID</th>

<th>User</th>

<th>Module</th>

<th>Action</th>

<th>Description</th>

<th>IP Address</th>

<th>Date & Time</th>

<th width="100">View</th>

</tr>

</thead>

<tbody>

<?php

$sql="

SELECT

a.*,
u.full_name

FROM audit_logs a

LEFT JOIN users u
ON u.id=a.user_id

ORDER BY a.id DESC

";

$result=mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td>

<?= $row['id']; ?>

</td>

<td>

<?= htmlspecialchars($row['full_name']); ?>

</td>

<td>

<?= htmlspecialchars($row['module']); ?>

</td>

<td>

<?php

$badge='secondary';

switch($row['action'])
{
    case 'Create':
        $badge='success';
        break;

    case 'Update':
        $badge='warning';
        break;

    case 'Delete':
        $badge='danger';
        break;

    case 'Approved':
        $badge='primary';
        break;

    case 'Checked':
        $badge='info';
        break;

    case 'Rejected':
        $badge='dark';
        break;
}

?>

<span class="badge bg-<?= $badge ?>">

<?= htmlspecialchars($row['action']); ?>

</span>

</td>

<td>

<?= htmlspecialchars($row['description']); ?>

</td>

<td>

<?= htmlspecialchars($row['ip_address']); ?>

</td>

<td>

<?= date("d-M-Y h:i A",strtotime($row['created_at'])); ?>

</td>

<td>

<a
href="view_audit.php?id=<?= $row['id']; ?>"
class="btn btn-info btn-sm">

<i class="fa-solid fa-eye"></i>

</a>

</td>

</tr>

<?php
}
?>

</tbody>

</table>

</div>

</div>

</div>

<?php include 'footer.php'; ?>