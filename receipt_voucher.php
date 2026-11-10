<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

$success = "";
$error = "";

/*====================================================
  Generate Voucher Number
====================================================*/

$voucher_no = "RV-000001";

$result = mysqli_query($conn,"
SELECT voucher_no
FROM vouchers
WHERE voucher_type='Receipt'
ORDER BY id DESC
LIMIT 1");

if(mysqli_num_rows($result)>0)
{
    $row = mysqli_fetch_assoc($result);

    $last = intval(substr($row['voucher_no'],3));

    $voucher_no = "RV-".str_pad($last+1,6,"0",STR_PAD_LEFT);
}

/*====================================================
  Save Voucher
====================================================*/

if(isset($_POST['save']))
{

    $voucher_date   = $_POST['voucher_date'];
    $receive_into   = (int)$_POST['receive_into'];
    $received_from  = (int)$_POST['received_from'];
    $amount         = (float)$_POST['amount'];
    $narration      = trim($_POST['narration']);
    $created_by     = $_SESSION['user_id'];

    if(
        $receive_into==0 ||
        $received_from==0 ||
        $amount<=0
    )
    {
        $error="Please fill all required fields.";
    }
    elseif($receive_into==$received_from)
    {
        $error="Receive Into and Received From cannot be the same account.";
    }
    else
    {

        mysqli_begin_transaction($conn);

        try
        {

            /*==============================
              Voucher Header
            ==============================*/

            $type   = "Receipt";
            $status = "Pending";

            $stmt = mysqli_prepare(
            $conn,

            "INSERT INTO vouchers
            (
                voucher_no,
                voucher_date,
                voucher_type,
                narration,
                created_by,
                status
            )
            VALUES
            (?,?,?,?,?,?)");

            mysqli_stmt_bind_param(

            $stmt,

            "ssssis",

            $voucher_no,
            $voucher_date,
            $type,
            $narration,
            $created_by,
            $status

            );

            mysqli_stmt_execute($stmt);

            $voucher_id = mysqli_insert_id($conn);

            /*==============================
              Debit Entry
            ==============================*/

            $stmt = mysqli_prepare(
            $conn,

            "INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (?,?,?,0)");

            mysqli_stmt_bind_param(

            $stmt,

            "iid",

            $voucher_id,
            $receive_into,
            $amount

            );

            mysqli_stmt_execute($stmt);

            /*==============================
              Credit Entry
            ==============================*/

            $stmt = mysqli_prepare(
            $conn,

            "INSERT INTO voucher_details
            (
                voucher_id,
                account_id,
                debit,
                credit
            )
            VALUES
            (?,?,0,?)");

            mysqli_stmt_bind_param(

            $stmt,

            "iid",

            $voucher_id,
            $received_from,
            $amount

            );

            mysqli_stmt_execute($stmt);

            /*==============================
              Audit Log
            ==============================*/

            logActivity(

                $conn,

                "Receipt Voucher",

                "Create",

                $voucher_id,

                "Created Receipt Voucher ".$voucher_no

            );

            /*==============================
              Notify Managers
            ==============================*/

            $managers = mysqli_query(

            $conn,

            "SELECT id
            FROM users
            WHERE role='manager'
            AND status='active'"

            );

            while($manager=mysqli_fetch_assoc($managers))
            {

                addNotification(

                    $conn,

                    $manager['id'],

                    "New Receipt Voucher",

                    "Voucher ".$voucher_no." is waiting for review.",

                    "warning",

                    "view_voucher.php?id=".$voucher_id

                );

            }

            mysqli_commit($conn);

            header("Location: receipt_voucher.php?saved=1");

            exit();

        }

        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error = "Unable to save voucher.";

        }

    }

}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>
<div class="container-fluid">

<div class="row">

<div class="col-lg-4">

<div class="card shadow">

<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

<i class="fa-solid fa-money-bill-wave"></i>

Receipt Voucher

</h5>

<span class="badge bg-light text-dark">

<?= $voucher_no; ?>

</span>

</div>

<div class="card-body">

<?php if(isset($_GET['saved'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Voucher saved successfully and sent to Manager for review.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Voucher updated successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger alert-dismissible fade show">

<i class="fa-solid fa-triangle-exclamation"></i>

<?= htmlspecialchars($error); ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<form method="POST" autocomplete="off">

<div class="mb-3">

<label class="form-label">

Voucher No

</label>

<input
type="text"
class="form-control"
value="<?= $voucher_no ?>"
readonly>

</div>

<div class="mb-3">

<label class="form-label">

Voucher Date

</label>

<input
type="date"
name="voucher_date"
class="form-control"
value="<?= date('Y-m-d') ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Receive Into Account

<span class="text-danger">*</span>

</label>

<select
name="receive_into"
class="form-select"
required>

<option value="">

Select Account

</option>

<?php

$cash=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name");

while($a=mysqli_fetch_assoc($cash))
{
?>

<option value="<?= $a['id']; ?>">

<?= htmlspecialchars($a['account_name']); ?>

</option>

<?php
}
?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Received From Account

<span class="text-danger">*</span>

</label>

<select
name="received_from"
class="form-select"
required>

<option value="">

Select Account

</option>

<?php

$acc=mysqli_query($conn,"
SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name");

while($a=mysqli_fetch_assoc($acc))
{
?>

<option value="<?= $a['id']; ?>">

<?= htmlspecialchars($a['account_name']); ?>

</option>

<?php
}
?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Amount

<span class="text-danger">*</span>

</label>

<input
type="number"
step="0.01"
min="0.01"
name="amount"
class="form-control"
placeholder="Enter Amount"
required>

</div>

<div class="mb-3">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="4"
class="form-control"
placeholder="Enter narration (optional)"></textarea>

</div>

<div class="d-grid">

<button
type="submit"
name="save"
class="btn btn-success">

<i class="fa-solid fa-floppy-disk"></i>

Save Receipt Voucher

</button>

</div>

</form>

</div>

</div>

</div>
<!-- ================================
     Receipt Voucher List
================================ -->

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

<h5 class="mb-0">

<i class="fa-solid fa-list"></i>

Receipt Voucher List

</h5>

<span class="badge bg-light text-dark">

Receipt Vouchers

</span>

</div>

<div class="card-body">

<div class="table-responsive">

<table class="table table-bordered table-hover align-middle datatable">

<thead class="table-dark">

<tr>

<th width="60">ID</th>

<th>Voucher No</th>

<th>Date</th>

<th>Receive Into</th>

<th>Received From</th>

<th class="text-end">Amount</th>

<th width="120">Status</th>

<th width="180">Action</th>

</tr>

</thead>

<tbody>

<?php

$sql="

SELECT

v.id,
v.voucher_no,
v.voucher_date,
v.status,
v.created_by,

MAX(
CASE
WHEN vd.debit>0
THEN a.account_name
END
) AS receive_into,

MAX(
CASE
WHEN vd.credit>0
THEN a.account_name
END
) AS received_from,

SUM(vd.debit) AS amount

FROM vouchers v

INNER JOIN voucher_details vd
ON vd.voucher_id=v.id

INNER JOIN accounts a
ON a.id=vd.account_id

WHERE v.voucher_type='Receipt'

GROUP BY

v.id,
v.voucher_no,
v.voucher_date,
v.status,
v.created_by

ORDER BY v.id DESC

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

<strong class="text-primary">

<?= htmlspecialchars($row['voucher_no']); ?>

</strong>

</td>

<td>

<?= date("d-M-Y",strtotime($row['voucher_date'])); ?>

</td>

<td>

<?= htmlspecialchars($row['receive_into']); ?>

</td>

<td>

<?= htmlspecialchars($row['received_from']); ?>

</td>

<td class="text-end">

<strong>

<?= number_format($row['amount'],2); ?>

</strong>

</td>

<td>

<?php

switch($row['status'])
{

case 'Pending':

echo '<span class="badge bg-warning text-dark">
Pending
</span>';

break;

case 'Checked':

echo '<span class="badge bg-info">
Checked
</span>';

break;

case 'Approved':

echo '<span class="badge bg-success">
Approved
</span>';

break;

case 'Rejected':

echo '<span class="badge bg-danger">
Rejected
</span>';

break;

default:

echo '<span class="badge bg-secondary">
Unknown
</span>';

}

?>

</td>
<td>

<!-- View -->
<a href="view_voucher.php?id=<?= $row['id']; ?>"
class="btn btn-info btn-sm"
title="View">

<i class="fa-solid fa-eye"></i>

</a>

<!-- Print -->
<?php if($row['status']=="Approved"){ ?>

<a
href="print_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-sm btn-secondary"
target="_blank"
title="Print">

<i class="fa-solid fa-print"></i>

</a>

<?php } else { ?>

<button
class="btn btn-sm btn-secondary"
disabled
title="Only approved vouchers can be printed">

<i class="fa-solid fa-print"></i>

</button>

<?php } ?>
</a>

<?php
/* Accountant can edit/delete only Pending or Rejected vouchers
   and only the vouchers they created. */
if(
    $_SESSION['role']=='accountant'
    &&
    $row['created_by']==$_SESSION['user_id']
    &&
    ($row['status']=='Pending' || $row['status']=='Rejected')
){
?>

<a href="edit_receipt_voucher.php?id=<?= $row['id']; ?>"
class="btn btn-warning btn-sm"
title="Edit">

<i class="fa-solid fa-pen"></i>

</a>

<a href="delete_receipt_voucher.php?id=<?= $row['id']; ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Are you sure you want to delete this voucher?')"
title="Delete">

<i class="fa-solid fa-trash"></i>

</a>

<?php } ?>

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

</div>

</div>

<?php include 'footer.php'; ?>