<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin']);

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
    u.full_name AS created_by_name,
    m.full_name AS checked_by_name,
    v.checked_at

FROM vouchers v

LEFT JOIN users u ON u.id = v.created_by
LEFT JOIN users m ON m.id = v.checked_by

WHERE v.status='Checked'

ORDER BY v.checked_at ASC, v.id ASC
";

$result = mysqli_query($conn, $sql);
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-success text-white">
    <h4>Voucher Approval</h4>
</div>

<div class="card-body">

<table class="table table-bordered table-striped" id="approvalTable">

<thead class="table-dark">

<tr>

<th>#</th>
<th>Voucher No</th>
<th>Date</th>
<th>Type</th>
<th>Created By</th>
<th>Checked By</th>
<th>Checked At</th>
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

<td><?= htmlspecialchars($row['created_by_name']) ?></td>

<td><?= htmlspecialchars($row['checked_by_name']) ?></td>

<td><?= date('d-m-Y H:i',strtotime($row['checked_at'])) ?></td>

<td>

<a href="view_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm">

View

</a>

<a href="approve_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-success btn-sm"
onclick="return confirm('Approve this voucher?')">

Approve

</a>

<a href="reject_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Reject this voucher?')">

Reject

</a>

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

</div>

</div>

<?php include 'footer.php'; ?>

<script>
$(function(){
    $('#approvalTable').DataTable();
});
</script>