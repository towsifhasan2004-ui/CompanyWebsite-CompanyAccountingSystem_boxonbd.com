<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';

checkRole(['admin','manager']);

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = (int)$_GET['id'];

/* Get Voucher */

$stmt = mysqli_prepare($conn,"
SELECT *
FROM vouchers
WHERE id=?
");

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$voucher){
    die("Voucher not found.");
}

/* Save Rejection */

if(isset($_POST['reject']))
{
    $remarks = trim($_POST['remarks']);

    if($remarks=="")
    {
        $error="Rejection reason is required.";
    }
    else
    {
        $sql="

        UPDATE vouchers

        SET

        status='Rejected',
        remarks=?,
        approved_by=?,
        approved_at=NOW()

        WHERE id=?

        ";

        $stmt=mysqli_prepare($conn,$sql);

        mysqli_stmt_bind_param(
            $stmt,
            "sii",
            $remarks,
            $_SESSION['user_id'],
            $id
        );

        mysqli_stmt_execute($stmt);

        if(isManager())
            header("Location: manager_review.php");
        else
            header("Location: admin_approval.php");

        exit();
    }
}
    logActivity(
    $conn,
    "Receipt Voucher",
    "Rejected",
    $id,
    "Admin rejected voucher : ".$voucher_no
    );

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="card shadow">

<div class="card-header bg-danger text-white">

<h4>Reject Voucher</h4>

</div>

<div class="card-body">

<?php if(isset($error)){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<table class="table table-bordered">

<tr>
<th>Voucher No</th>
<td><?= htmlspecialchars($voucher['voucher_no']) ?></td>
</tr>

<tr>
<th>Date</th>
<td><?= htmlspecialchars($voucher['voucher_date']) ?></td>
</tr>

<tr>
<th>Type</th>
<td><?= htmlspecialchars($voucher['voucher_type']) ?></td>
</tr>

</table>

<form method="POST">

<div class="mb-3">

<label class="form-label">
Reason for Rejection
</label>

<textarea
name="remarks"
class="form-control"
rows="5"
required></textarea>

</div>

<button
type="submit"
name="reject"
class="btn btn-danger">

Reject Voucher

</button>

<a
href="javascript:history.back()"
class="btn btn-secondary">

Cancel

</a>

</form>

</div>

</div>

</div>

<?php include 'footer.php'; ?>