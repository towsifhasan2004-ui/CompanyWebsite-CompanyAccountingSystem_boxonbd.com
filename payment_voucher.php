<?php
require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = "";
$error = "";

/* ==========================================
   VOUCHER NUMBER GENERATOR
========================================== */

$voucher_no = "PV-000001";

$result = mysqli_query(
    $conn,
    "SELECT voucher_no
     FROM vouchers
     WHERE voucher_type='Payment'
     ORDER BY id DESC
     LIMIT 1"
);

if(mysqli_num_rows($result)>0)
{
    $row=mysqli_fetch_assoc($result);

    $last=intval(substr($row['voucher_no'],3));

    $voucher_no="PV-".str_pad($last+1,6,"0",STR_PAD_LEFT);
}

/* ==========================================
   SAVE PAYMENT VOUCHER
========================================== */

if(isset($_POST['save']))
{

    $voucher_date = $_POST['voucher_date'];

    $paid_from = (int)$_POST['paid_from'];

    $paid_to = (int)$_POST['paid_to'];

    $amount = (float)$_POST['amount'];

    $narration = trim($_POST['narration']);

    $created_by = $_SESSION['user_id'];

    /* ==========================================
       VALIDATION
    ========================================== */

    if($paid_from==0)
    {
        $error="Please select Paid From account.";
    }
    elseif($paid_to==0)
    {
        $error="Please select Paid To account.";
    }
    elseif($paid_from==$paid_to)
    {
        $error="Paid From and Paid To cannot be the same account.";
    }
    elseif($amount<=0)
    {
        $error="Amount must be greater than zero.";
    }

    if($error=="")
    {

        mysqli_begin_transaction($conn);

        try
        {

            /* ==========================================
               INSERT VOUCHER HEADER
            ========================================== */

            $stmt=mysqli_prepare(

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
                (?,?,?,?,?,?)"

            );

            $type="Payment";

            $status="Pending";

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

            $voucher_id=mysqli_insert_id($conn);

            /* ==========================================
               DEBIT ENTRY
            ========================================== */

            $stmt=mysqli_prepare(

                $conn,

                "INSERT INTO voucher_details
                (
                    voucher_id,
                    account_id,
                    debit,
                    credit
                )
                VALUES
                (?,?,?,0)"

            );

            mysqli_stmt_bind_param(

                $stmt,

                "iid",

                $voucher_id,

                $paid_to,

                $amount

            );

            mysqli_stmt_execute($stmt);

            /* ==========================================
               CREDIT ENTRY
            ========================================== */

            $stmt=mysqli_prepare(

                $conn,

                "INSERT INTO voucher_details
                (
                    voucher_id,
                    account_id,
                    debit,
                    credit
                )
                VALUES
                (?,?,0,?)"

            );

            mysqli_stmt_bind_param(

                $stmt,

                "iid",

                $voucher_id,

                $paid_from,

                $amount

            );

            mysqli_stmt_execute($stmt);

            /* ==========================================
               AUDIT LOG
            ========================================== */

            logActivity(

                $conn,

                "Payment Voucher",

                "Create",

                $voucher_id,

                "Created Payment Voucher : ".$voucher_no

            );

            /* ==========================================
               SEND NOTIFICATION TO MANAGERS
            ========================================== */

            $managers=mysqli_query(

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

                    "New Payment Voucher",

                    "Payment Voucher ".$voucher_no." is waiting for review.",

                    "info",

                    "view_voucher.php?id=".$voucher_id

                );

            }

            mysqli_commit($conn);

            header("Location: payment_voucher.php?saved=1");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error="Unable to save Payment Voucher.<br>".$e->getMessage();

        }

    }

}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>

<div class="container-fluid">

<div class="row">

<!-- PAYMENT FORM STARTS HERE -->
 <div class="col-lg-4">

<div class="card shadow">

<div class="card-header bg-danger text-white">

<h5 class="mb-0">

<i class="fa-solid fa-money-bill-transfer"></i>

Payment Voucher

</h5>

</div>

<div class="card-body">

<?php if(isset($_GET['saved'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Payment Voucher Saved Successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Payment Voucher Updated Successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if(isset($_GET['deleted'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-trash"></i>

Payment Voucher Deleted Successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger">

<?= $error ?>

</div>

<?php } ?>

<form method="POST">

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

Paid From (Cash / Bank)

</label>

<select
name="paid_from"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$accounts=mysqli_query(

$conn,

"SELECT
id,
account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name"

);

while($acc=mysqli_fetch_assoc($accounts))
{

?>

<option value="<?= $acc['id'] ?>">

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Paid To (Party / Expense)

</label>

<select
name="paid_to"
class="form-select"
required>

<option value="">Select Account</option>

<?php

mysqli_data_seek($accounts,0);

while($acc=mysqli_fetch_assoc($accounts))
{

?>

<option value="<?= $acc['id'] ?>">

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Amount

</label>

<input
type="number"
step="0.01"
min="0.01"
name="amount"
class="form-control"
required>

</div>

<div class="mb-3">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="3"
class="form-control"></textarea>

</div>

<button
type="submit"
name="save"
class="btn btn-danger w-100">

<i class="fa-solid fa-floppy-disk"></i>

Save Payment Voucher

</button>

</form>

</div>

</div>

</div>

<!-- ==========================================
     PAYMENT LIST START
========================================== -->

<div class="col-lg-8">

<div class="card shadow">

<div class="card-header bg-primary text-white">

<h5 class="mb-0">

Payment Voucher List

</h5>

</div>

<div class="card-body">

<table class="table table-bordered table-hover datatable">

<thead class="table-dark">

<tr>

<th>ID</th>

<th>Voucher No</th>

<th>Date</th>

<th>Paid From</th>

<th>Paid To</th>

<th class="text-end">Amount</th>

<th>Status</th>

<th width="220">Action</th>

</tr>

</thead>

<tbody><?php

$sql = "

SELECT

v.id,
v.voucher_no,
v.voucher_date,
v.status,

MAX(
CASE
WHEN vd.credit>0
THEN a.account_name
END
) AS paid_from,

MAX(
CASE
WHEN vd.debit>0
THEN a.account_name
END
) AS paid_to,

SUM(vd.debit) AS amount

FROM vouchers v

INNER JOIN voucher_details vd
ON vd.voucher_id=v.id

INNER JOIN accounts a
ON a.id=vd.account_id

WHERE v.voucher_type='Payment'

GROUP BY

v.id,
v.voucher_no,
v.voucher_date,
v.status

ORDER BY v.id DESC

";

$result = mysqli_query($conn,$sql);

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td><?= $row['id'] ?></td>

<td>

<strong>

<?= htmlspecialchars($row['voucher_no']) ?>

</strong>

</td>

<td>

<?= date('d-M-Y',strtotime($row['voucher_date'])) ?>

</td>

<td>

<?= htmlspecialchars($row['paid_from']) ?>

</td>

<td>

<?= htmlspecialchars($row['paid_to']) ?>

</td>

<td class="text-end">

<?= number_format($row['amount'],2) ?>

</td>

<td>

<?php

switch($row['status'])
{

case "Pending":

echo '<span class="badge bg-warning">Pending</span>';

break;

case "Checked":

echo '<span class="badge bg-info">Checked</span>';

break;

case "Approved":

echo '<span class="badge bg-success">Approved</span>';

break;

case "Rejected":

echo '<span class="badge bg-danger">Rejected</span>';

break;

default:

echo '<span class="badge bg-secondary">'.$row['status'].'</span>';

}

?>

</td>

<td>

<!-- VIEW -->

<a
href="view_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-info btn-sm"
title="View">

<i class="fa-solid fa-eye"></i>

</a>

<!-- PRINT -->

<a
href="print_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-secondary btn-sm"
target="_blank"
title="Print">

<i class="fa-solid fa-print"></i>

</a>

<!-- EDIT -->

<?php if($row['status']=="Pending" || $row['status']=="Rejected"){ ?>

<a
href="edit_payment_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-warning btn-sm"
title="Edit">

<i class="fa-solid fa-pen"></i>

</a>

<?php } else { ?>

<button
class="btn btn-warning btn-sm"
disabled
title="Checked/Approved vouchers cannot be edited">

<i class="fa-solid fa-lock"></i>

</button>

<?php } ?>

<!-- DELETE -->

<?php if($row['status']=="Pending" || $row['status']=="Rejected"){ ?>

<a
href="delete_payment_voucher.php?id=<?= $row['id'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Delete this Payment Voucher?')"
title="Delete">

<i class="fa-solid fa-trash"></i>

</a>

<?php } else { ?>

<button
class="btn btn-danger btn-sm"
disabled
title="Checked/Approved vouchers cannot be deleted">

<i class="fa-solid fa-lock"></i>

</button>

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

</div><!-- ==========================================
     JAVASCRIPT
========================================== -->

<script>

$(document).ready(function(){

    if($.fn.DataTable)
    {
        $('.datatable').DataTable({
            order:[[0,'desc']],
            pageLength:10,
            responsive:true
        });
    }

    /* Prevent same account selection */

    $("select[name='paid_from'], select[name='paid_to']").change(function(){

        let from = $("select[name='paid_from']").val();

        let to = $("select[name='paid_to']").val();

        if(from!="" && to!="" && from==to)
        {
            alert("Paid From and Paid To cannot be the same account.");

            $(this).val("");
        }

    });

    /* Amount validation */

    $("input[name='amount']").on("keyup change",function(){

        let amount=parseFloat($(this).val());

        if(amount<0)
        {
            $(this).val("");
        }

    });

});

</script>

<?php include 'footer.php'; ?>