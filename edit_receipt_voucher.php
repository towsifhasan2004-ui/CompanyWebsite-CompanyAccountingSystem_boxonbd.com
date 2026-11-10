<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

$error = "";
$success = "";

/* ==========================================
   GET VOUCHER ID
========================================== */

if(!isset($_GET['id']))
{
    header("Location: receipt_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================================
   LOAD VOUCHER
========================================== */

$stmt = mysqli_prepare(
$conn,

"SELECT *
FROM vouchers
WHERE id=?
AND voucher_type='Receipt'
LIMIT 1");

mysqli_stmt_bind_param(
$stmt,
"i",
$id);

mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(
mysqli_stmt_get_result($stmt));

if(!$voucher)
{
    die("Voucher not found.");
}

/* ==========================================
   OWNER CHECK
========================================== */

if($voucher['created_by'] != $_SESSION['user_id'])
{
    die("Access Denied.");
}

/* ==========================================
   STATUS CHECK
========================================== */

if(
    $voucher['status']=="Checked" ||
    $voucher['status']=="Approved"
)
{
    die("Approved or Checked voucher cannot be edited.");
}

/* ==========================================
   UPDATE VOUCHER
========================================== */

if(isset($_POST['update']))
{

    $voucher_date  = $_POST['voucher_date'];

    $receive_into  = (int)$_POST['receive_into'];

    $received_from = (int)$_POST['received_from'];

    $amount        = (float)$_POST['amount'];

    $narration     = trim($_POST['narration']);

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

            /* ======================
               UPDATE HEADER
            ====================== */

            $stmt=mysqli_prepare(

            $conn,

            "UPDATE vouchers

            SET

            voucher_date=?,

            narration=?,

            status='Pending',

            remarks=NULL,

            checked_by=NULL,

            checked_at=NULL,

            approved_by=NULL,

            approved_at=NULL

            WHERE id=?"

            );

            mysqli_stmt_bind_param(

            $stmt,

            "ssi",

            $voucher_date,

            $narration,

            $id

            );

            mysqli_stmt_execute($stmt);

            /* ======================
               DELETE OLD DETAILS
            ====================== */

            $stmt=mysqli_prepare(

            $conn,

            "DELETE
            FROM voucher_details
            WHERE voucher_id=?"

            );

            mysqli_stmt_bind_param(

            $stmt,

            "i",

            $id

            );

            mysqli_stmt_execute($stmt);

            /* ======================
               INSERT DEBIT
            ====================== */

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

            $id,

            $receive_into,

            $amount

            );

            mysqli_stmt_execute($stmt);

            /* ======================
               INSERT CREDIT
            ====================== */

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

            $id,

            $received_from,

            $amount

            );

            mysqli_stmt_execute($stmt);
                        /* ======================
               AUDIT LOG
            ====================== */

            logActivity(

                $conn,

                "Receipt Voucher",

                "Update",

                $id,

                "Updated Receipt Voucher ".$voucher['voucher_no']

            );

            /* ======================
               SEND NOTIFICATION
               TO ALL MANAGERS
            ====================== */

            $managers = mysqli_query(

                $conn,

                "SELECT id
                 FROM users
                 WHERE role='manager'
                 AND status='active'"

            );

            while($manager = mysqli_fetch_assoc($managers))
            {

                addNotification(

                    $conn,

                    $manager['id'],

                    "Receipt Voucher Updated",

                    "Voucher ".$voucher['voucher_no']." has been updated and is waiting for review.",

                    "warning",

                    "view_voucher.php?id=".$id

                );

            }

            /* ======================
               COMMIT
            ====================== */

            mysqli_commit($conn);

            header("Location: receipt_voucher.php?updated=1");

            exit();

        }
        catch(Exception $e)
        {

            mysqli_rollback($conn);

            $error = "Unable to update voucher.";

        }

    }

}

/* ==========================================
   LOAD VOUCHER DETAILS
========================================== */

$receive_into = "";
$received_from = "";
$amount = 0;

$stmt = mysqli_prepare(

$conn,

"SELECT
account_id,
debit,
credit
FROM voucher_details
WHERE voucher_id=?"

);

mysqli_stmt_bind_param(

$stmt,

"i",

$id

);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while($row = mysqli_fetch_assoc($result))
{

    if($row['debit'] > 0)
    {

        $receive_into = $row['account_id'];

        $amount = $row['debit'];

    }

    if($row['credit'] > 0)
    {

        $received_from = $row['account_id'];

    }

}

include 'header.php';
include 'navbar.php';
include 'sidebar.php';
?>
<div class="container-fluid">

<div class="row justify-content-center">

<div class="col-lg-7">

<div class="card shadow">

<div class="card-header bg-warning text-dark">

<h4 class="mb-0">

<i class="fa-solid fa-pen-to-square"></i>

Edit Receipt Voucher

</h4>

</div>

<div class="card-body">

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
value="<?= htmlspecialchars($voucher['voucher_no']); ?>"
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
value="<?= htmlspecialchars($voucher['voucher_date']); ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Receive Into

</label>

<select
name="receive_into"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$accounts = mysqli_query(
$conn,
"SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name");

while($acc = mysqli_fetch_assoc($accounts))
{
?>

<option
value="<?= $acc['id']; ?>"
<?= ($receive_into==$acc['id'])?'selected':''; ?>>

<?= htmlspecialchars($acc['account_name']); ?>

</option>

<?php
}
?>

</select>

</div>

<div class="mb-3">

<label class="form-label">

Received From

</label>

<select
name="received_from"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$accounts = mysqli_query(
$conn,
"SELECT id,account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name");

while($acc = mysqli_fetch_assoc($accounts))
{
?>

<option
value="<?= $acc['id']; ?>"
<?= ($received_from==$acc['id'])?'selected':''; ?>>

<?= htmlspecialchars($acc['account_name']); ?>

</option>

<?php
}
?>

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
value="<?= $amount; ?>"
required>

</div>

<div class="mb-3">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="4"
class="form-control"><?= htmlspecialchars($voucher['narration']); ?></textarea>

</div>

<?php if($voucher['status']=="Rejected" && !empty($voucher['remarks'])){ ?>

<div class="alert alert-danger">

<strong>

Reason for Rejection

</strong>

<hr>

<?= nl2br(htmlspecialchars($voucher['remarks'])); ?>

</div>

<?php } ?>

<div class="d-flex justify-content-between">

<a
href="receipt_voucher.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Voucher

</button>

</div>

</form>

</div>

</div>

</div>

</div>
</div>

<?php include 'footer.php'; ?>