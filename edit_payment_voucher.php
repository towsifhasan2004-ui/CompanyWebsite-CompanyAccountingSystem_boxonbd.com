<?php

require_once 'auth.php';
require_once 'config/db.php';
require_once 'config/role.php';
require_once 'config/audit.php';
require_once 'config/notification.php';

checkRole(['accountant']);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$error = "";
$success = "";

/* ==========================================
   GET VOUCHER ID
========================================== */

if(!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: payment_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================================
   LOAD PAYMENT VOUCHER
========================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM vouchers
     WHERE id=?
     AND voucher_type='Payment'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt,"i",$id);
mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if(!$voucher)
{
    die("Payment Voucher not found.");
}

/* ==========================================
   STATUS CHECK
========================================== */

if(
    $voucher['status']=="Checked" ||
    $voucher['status']=="Approved"
)
{
    die("Checked or Approved vouchers cannot be edited.");
}

/* ==========================================
   UPDATE PAYMENT VOUCHER
========================================== */

if(isset($_POST['update']))
{

    $voucher_date = $_POST['voucher_date'];

    $payment_from = (int)$_POST['payment_from'];

    $payment_to = (int)$_POST['payment_to'];

    $amount = (float)$_POST['amount'];

    $narration = trim($_POST['narration']);

    /* ==========================================
       VALIDATION
    ========================================== */

    if($payment_from==0)
    {
        $error = "Please select Payment From account.";
    }
    elseif($payment_to==0)
    {
        $error = "Please select Payment To account.";
    }
    elseif($payment_from==$payment_to)
    {
        $error = "Payment From and Payment To cannot be the same account.";
    }
    elseif($amount<=0)
    {
        $error = "Amount must be greater than zero.";
    }

    if($error=="")
    {

        mysqli_begin_transaction($conn);

        try
        {

            /* ==========================================
               UPDATE HEADER
            ========================================== */

            $stmt = mysqli_prepare(
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

            /* ==========================================
               DELETE OLD DETAILS
            ========================================== */

            $stmt = mysqli_prepare(
                $conn,
                "DELETE FROM voucher_details
                 WHERE voucher_id=?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "i",
                $id
            );

            mysqli_stmt_execute($stmt);

            /* ==========================================
               INSERT CREDIT ENTRY
               (Payment From)
            ========================================== */

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
                (?,?,0,?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iid",
                $id,
                $payment_from,
                $amount
            );

            mysqli_stmt_execute($stmt);

            /* ==========================================
               INSERT DEBIT ENTRY
               (Payment To)
            ========================================== */

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
                (?,?,?,0)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iid",
                $id,
                $payment_to,
                $amount
            );

            mysqli_stmt_execute($stmt);            /* ==========================================
               AUDIT LOG
            ========================================== */

            logActivity(
                $conn,
                "Payment Voucher",
                "Update",
                $id,
                "Updated Payment Voucher : ".$voucher['voucher_no']
            );

            /* ==========================================
               SEND NOTIFICATION TO MANAGERS
            ========================================== */

            $managerQuery = mysqli_query(
                $conn,
                "SELECT id
                 FROM users
                 WHERE role='manager'
                 AND status='active'"
            );

            while($manager = mysqli_fetch_assoc($managerQuery))
            {
                addNotification(
                    $conn,
                    $manager['id'],
                    "Payment Voucher Updated",
                    "Payment Voucher ".$voucher['voucher_no']." has been updated and is waiting for review.",
                    "warning",
                    "view_voucher.php?id=".$id
                );
            }

            /* ==========================================
               COMMIT
            ========================================== */

            mysqli_commit($conn);

            header("Location: payment_voucher.php?updated=1");
            exit();

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);

            $error = "Unable to update Payment Voucher.<br>".$e->getMessage();
        }

    }

}

/* ==========================================
   LOAD VOUCHER DETAILS
========================================== */

$payment_from = "";
$payment_to   = "";
$amount       = 0;

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
    if($row['credit'] > 0)
    {
        $payment_from = $row['account_id'];
        $amount = $row['credit'];
    }

    if($row['debit'] > 0)
    {
        $payment_to = $row['account_id'];
    }
}

/* ==========================================
   LOAD ACCOUNT LIST
========================================== */

$accountList = [];

$result = mysqli_query(
    $conn,
    "SELECT
        id,
        account_name
     FROM accounts
     WHERE status='Active'
     ORDER BY account_name"
);

while($row = mysqli_fetch_assoc($result))
{
    $accountList[] = $row;
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

Edit Payment Voucher

</h4>

</div>

<div class="card-body">

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Payment Voucher updated successfully.

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<?php if($error!=""){ ?>

<div class="alert alert-danger alert-dismissible fade show">

<?= $error ?>

<button
type="button"
class="btn-close"
data-bs-dismiss="alert"></button>

</div>

<?php } ?>

<form method="POST" autocomplete="off"><!-- ==========================================
     VOUCHER NO
========================================== -->

<div class="mb-3">

<label class="form-label">

Voucher No

</label>

<input
type="text"
class="form-control"
value="<?= htmlspecialchars($voucher['voucher_no']) ?>"
readonly>

</div>

<!-- ==========================================
     VOUCHER DATE
========================================== -->

<div class="mb-3">

<label class="form-label">

Voucher Date

</label>

<input
type="date"
name="voucher_date"
class="form-control"
value="<?= $voucher['voucher_date'] ?>"
required>

</div>

<!-- ==========================================
     PAYMENT FROM
========================================== -->

<div class="mb-3">

<label class="form-label">

Payment From

</label>

<select
name="payment_from"
class="form-select"
required>

<option value="">Select Account</option>

<?php foreach($accountList as $acc){ ?>

<option
value="<?= $acc['id'] ?>"
<?= ($payment_from==$acc['id'])?'selected':''; ?>>

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<!-- ==========================================
     PAYMENT TO
========================================== -->

<div class="mb-3">

<label class="form-label">

Payment To

</label>

<select
name="payment_to"
class="form-select"
required>

<option value="">Select Account</option>

<?php foreach($accountList as $acc){ ?>

<option
value="<?= $acc['id'] ?>"
<?= ($payment_to==$acc['id'])?'selected':''; ?>>

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<!-- ==========================================
     AMOUNT
========================================== -->

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
value="<?= $amount ?>"
required>

</div>

<!-- ==========================================
     NARRATION
========================================== -->

<div class="mb-3">

<label class="form-label">

Narration

</label>

<textarea
name="narration"
rows="4"
class="form-control"><?= htmlspecialchars($voucher['narration']) ?></textarea>

</div>

<!-- ==========================================
     REJECTION REMARKS
========================================== -->

<?php if($voucher['status']=="Rejected" && !empty($voucher['remarks'])){ ?>

<div class="alert alert-danger">

<strong>

Manager Rejection Reason

</strong>

<hr>

<?= nl2br(htmlspecialchars($voucher['remarks'])) ?>

</div>

<?php } ?>

<div class="d-flex justify-content-between">

<a
href="payment_voucher.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Payment Voucher

</button>

</div>

</form>

</div>

</div>

</div>

</div>

</div><?php include 'footer.php'; ?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    const paymentFrom = document.querySelector("select[name='payment_from']");
    const paymentTo   = document.querySelector("select[name='payment_to']");
    const amount      = document.querySelector("input[name='amount']");

    /* ==========================
       ACCOUNT VALIDATION
    ========================== */

    function validateAccounts()
    {
        if(
            paymentFrom.value !== "" &&
            paymentTo.value !== "" &&
            paymentFrom.value === paymentTo.value
        )
        {
            alert("Payment From and Payment To cannot be the same account.");

            paymentTo.value = "";

            paymentTo.focus();
        }
    }

    paymentFrom.addEventListener("change", validateAccounts);
    paymentTo.addEventListener("change", validateAccounts);

    /* ==========================
       AMOUNT VALIDATION
    ========================== */

    amount.addEventListener("input", function(){

        if(parseFloat(this.value) < 0)
        {
            this.value = "";
        }

    });

    /* ==========================
       FORM VALIDATION
    ========================== */

    document.querySelector("form").addEventListener("submit", function(e){

        if(paymentFrom.value=="")
        {
            alert("Please select Payment From account.");
            paymentFrom.focus();
            e.preventDefault();
            return;
        }

        if(paymentTo.value=="")
        {
            alert("Please select Payment To account.");
            paymentTo.focus();
            e.preventDefault();
            return;
        }

        if(paymentFrom.value===paymentTo.value)
        {
            alert("Payment From and Payment To cannot be the same account.");
            paymentTo.focus();
            e.preventDefault();
            return;
        }

        if(
            amount.value=="" ||
            parseFloat(amount.value)<=0
        )
        {
            alert("Amount must be greater than zero.");
            amount.focus();
            e.preventDefault();
            return;
        }

    });

});

</script>