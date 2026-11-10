<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['manager']);

include 'header.php';
include 'navbar.php';
include 'sidebar.php';

$sql = "
SELECT
    v.id,
    v.voucher_no,
    v.voucher_date,
    v.voucher_type,
    v.narration,
    u.full_name

FROM vouchers v

LEFT JOIN users u
ON u.id = v.created_by

WHERE v.status='Pending'

ORDER BY v.voucher_date DESC, v.id DESC
";

$result = mysqli_query($conn,$sql);
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>Pending Voucher Review</h4>

</div>

<div class="card-body">

<table class="table table-bordered table-striped" id="reviewTable">

<thead>

<tr>
<th>#</th>
<th>Voucher No</th>
<th>Date</th>
<th>Type</th>
<th>Created By</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php
$i=1;

while($row=mysqli_fetch_assoc($result))
{
?>

<tr>

<td><?= $i++ ?></td>

<td><?= htmlspecialchars($row['voucher_no']) ?></td>

<td><?= date('d-m-Y',strtotime($row['voucher_date'])) ?></td>

<td><?= htmlspecialchars($row['voucher_type']) ?></td>

<td><?= htmlspecialchars($row['full_name']) ?></td>

<td>

<a href="view_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm">
View
</a>

<a href="check_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-sm"
onclick="return confirm('Check this voucher?')">
Check
</a>

<a href="reject_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Reject this voucher?')">
Reject
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

<script>
$(function(){
    $('#reviewTable').DataTable();
});
</script>