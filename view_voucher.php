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
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];

/*==========================
GET VOUCHER
==========================*/

$sql = "
SELECT
v.*,
u.full_name AS created_by_name,
m.full_name AS checked_by_name,
a.full_name AS approved_by_name

FROM vouchers v

LEFT JOIN users u
ON u.id=v.created_by

LEFT JOIN users m
ON m.id=v.checked_by

LEFT JOIN users a
ON a.id=v.approved_by

WHERE v.id=?
";

$stmt=mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$voucher=mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$voucher)
{
    die("Voucher not found.");
}

/*==========================
GET VOUCHER DETAILS
==========================*/

$sql="

SELECT

vd.*,
a.account_code,
a.account_name

FROM voucher_details vd

INNER JOIN accounts a
ON a.id=vd.account_id

WHERE vd.voucher_id=?

ORDER BY vd.id

";

$stmt=mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$details=mysqli_stmt_get_result($stmt);

$totalDebit=0;
$totalCredit=0;
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h4>Voucher Details</h4>

</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
<th width="200">Voucher No</th>
<td><?= htmlspecialchars($voucher['voucher_no']) ?></td>

<th>Date</th>
<td><?= date('d-m-Y',strtotime($voucher['voucher_date'])) ?></td>
</tr>

<tr>
<th>Voucher Type</th>
<td><?= htmlspecialchars($voucher['voucher_type']) ?></td>

<th>Status</th>
<td>

<?php

$statusColor='secondary';

switch($voucher['status'])
{
case 'Pending':
$statusColor='warning';
break;

case 'Checked':
$statusColor='info';
break;

case 'Approved':
$statusColor='success';
break;

case 'Rejected':
$statusColor='danger';
break;
}
?>

<span class="badge bg-<?= $statusColor ?>">
<?= htmlspecialchars($voucher['status']) ?>
</span>

</td>

</tr>

<tr>

<th>Created By</th>

<td><?= htmlspecialchars($voucher['created_by_name']) ?></td>

<th>Checked By</th>

<td><?= htmlspecialchars($voucher['checked_by_name']) ?></td>

</tr>

<tr>

<th>Approved By</th>

<td><?= htmlspecialchars($voucher['approved_by_name']) ?></td>

<th>Narration</th>

<td><?= htmlspecialchars($voucher['narration']) ?></td>

</tr>

</table>

<hr>

<h5>Voucher Entries</h5>

<table class="table table-bordered">

<thead class="table-dark">

<tr>

<th>Account Code</th>
<th>Account Name</th>
<th class="text-end">Debit</th>
<th class="text-end">Credit</th>

</tr>

</thead>

<tbody>

<?php while($row=mysqli_fetch_assoc($details)){ ?>

<?php
$totalDebit+=$row['debit'];
$totalCredit+=$row['credit'];
?>

<tr>

<td><?= htmlspecialchars($row['account_code']) ?></td>

<td><?= htmlspecialchars($row['account_name']) ?></td>

<td class="text-end"><?= number_format($row['debit'],2) ?></td>

<td class="text-end"><?= number_format($row['credit'],2) ?></td>

</tr>

<?php } ?>

</tbody>

<tfoot>

<tr class="table-secondary">

<th colspan="2" class="text-end">

Total

</th>

<th class="text-end">

<?= number_format($totalDebit,2) ?>

</th>

<th class="text-end">

<?= number_format($totalCredit,2) ?>

</th>

</tr>

</tfoot>

</table>

<?php if(abs($totalDebit-$totalCredit)<0.01){ ?>

<div class="alert alert-success">

Voucher is balanced.

</div>

<?php } else { ?>

<div class="alert alert-danger">

Voucher is NOT balanced.

</div>

<?php } ?>

<div class="mt-3">

<?php

/*==========================
Manager Buttons
==========================*/

if(isManager() && $voucher['status']=="Pending")
{
?>

<a href="check_voucher.php?id=<?= $voucher['id'] ?>"
class="btn btn-success">

Check Voucher

</a>

<a href="reject_voucher.php?id=<?= $voucher['id'] ?>"
class="btn btn-danger">

Reject

</a>

<?php
}

/*==========================
Admin Buttons
==========================*/

if(isAdmin() && $voucher['status']=="Checked")
{
?>

<a href="approve_voucher.php?id=<?= $voucher['id'] ?>"
class="btn btn-primary">

Approve Voucher

</a>

<a href="reject_voucher.php?id=<?= $voucher['id'] ?>"
class="btn btn-danger">

Reject

</a>

<?php
}
?>

<a href="javascript:history.back()" class="btn btn-secondary">

Back

</a>

</div>

</div>

</div>

</div>

<?php include 'footer.php'; ?>