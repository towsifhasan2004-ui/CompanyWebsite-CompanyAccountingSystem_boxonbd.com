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

if (!isset($_GET['id']) || !is_numeric($_GET['id']))
{
    header("Location: contra_voucher.php");
    exit();
}

$id = (int)$_GET['id'];

/* ==========================================
   LOAD VOUCHER HEADER
========================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT *
     FROM vouchers
     WHERE id=?
     AND voucher_type='Contra'
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$voucher = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

if (!$voucher)
{
    die("Contra Voucher not found.");
}

/* ==========================================
   STATUS CHECK
========================================== */

if (
    $voucher['status'] == "Checked" ||
    $voucher['status'] == "Approved"
)
{
    die("Checked or Approved vouchers cannot be edited.");
}

/* ==========================================
   UPDATE CONTRA VOUCHER
========================================== */

if (isset($_POST['update']))
{
    $voucher_date = $_POST['voucher_date'];

    $from_account = (int)$_POST['from_account'];

    $to_account = (int)$_POST['to_account'];

    $amount = (float)$_POST['amount'];

    $narration = trim($_POST['narration']);

    if (
        $from_account == 0 ||
        $to_account == 0 ||
        $amount <= 0
    )
    {
        $error = "Please fill all required fields.";
    }
    elseif ($from_account == $to_account)
    {
        $error = "From Account and To Account cannot be the same.";
    }
    else
    {
        mysqli_begin_transaction($conn);

        try
        {
            /* ==========================
               UPDATE VOUCHER HEADER
            ========================== */

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

            /* ==========================
               DELETE OLD DETAILS
            ========================== */

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

            /* ==========================
               INSERT DEBIT ENTRY
               (TO ACCOUNT)
            ========================== */

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
                $to_account,
                $amount
            );

            mysqli_stmt_execute($stmt);

            /* ==========================
               INSERT CREDIT ENTRY
               (FROM ACCOUNT)
            ========================== */

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
                $from_account,
                $amount
            );

            mysqli_stmt_execute($stmt);
                        /* ==========================
               AUDIT LOG
            ========================== */

            logActivity(
                $conn,
                "Contra Voucher",
                "Update",
                $id,
                "Updated Contra Voucher : ".$voucher['voucher_no']
            );

            /* ==========================
               NOTIFY ALL MANAGERS
            ========================== */

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
                    "Contra Voucher Updated",
                    "Contra Voucher ".$voucher['voucher_no']." has been updated and is waiting for review.",
                    "warning",
                    "view_voucher.php?id=".$id
                );
            }

            /* ==========================
               COMMIT
            ========================== */

            mysqli_commit($conn);

            header("Location: contra_voucher.php?updated=1");
            exit();

        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);

            $error = "Unable to update Contra Voucher.<br>" . $e->getMessage();
        }
    }
}

/* ==========================================
   LOAD VOUCHER DETAILS
========================================== */

$from_account = "";
$to_account   = "";
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
    if($row['debit'] > 0)
    {
        $to_account = $row['account_id'];
        $amount = $row['debit'];
    }

    if($row['credit'] > 0)
    {
        $from_account = $row['account_id'];
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

<i class="fa-solid fa-right-left"></i>

Edit Contra Voucher

</h4>

</div>

<div class="card-body">

<?php if(isset($_GET['updated'])){ ?>

<div class="alert alert-success alert-dismissible fade show">

<i class="fa-solid fa-circle-check"></i>

Contra Voucher updated successfully.

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

<form method="POST" autocomplete="off">
    <!-- ==========================================
     VOUCHER NUMBER
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
     DATE
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
     FROM ACCOUNT
========================================== -->

<div class="mb-3">

<label class="form-label">

Transfer From

</label>

<select
name="from_account"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$accounts = mysqli_query(
$conn,
"SELECT
id,
account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name"
);

while($acc = mysqli_fetch_assoc($accounts))
{

?>

<option
value="<?= $acc['id'] ?>"
<?= ($from_account==$acc['id'])?'selected':''; ?>>

<?= htmlspecialchars($acc['account_name']) ?>

</option>

<?php } ?>

</select>

</div>

<!-- ==========================================
     TO ACCOUNT
========================================== -->

<div class="mb-3">

<label class="form-label">

Transfer To

</label>

<select
name="to_account"
class="form-select"
required>

<option value="">Select Account</option>

<?php

$accounts = mysqli_query(
$conn,
"SELECT
id,
account_name
FROM accounts
WHERE status='Active'
ORDER BY account_name"
);

while($acc = mysqli_fetch_assoc($accounts))
{

?>

<option
value="<?= $acc['id'] ?>"
<?= ($to_account==$acc['id'])?'selected':''; ?>>

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

<?php if($voucher['status']=="Rejected"){ ?>

<div class="alert alert-danger">

<strong>

Rejection Reason

</strong>

<hr>

<?= nl2br(htmlspecialchars($voucher['remarks'])) ?>

</div>

<?php } ?>

<div class="d-flex justify-content-between">

<button
type="submit"
name="update"
class="btn btn-primary">

<i class="fa-solid fa-floppy-disk"></i>

Update Contra Voucher

</button>

<a
href="contra_voucher.php"
class="btn btn-secondary">

<i class="fa-solid fa-arrow-left"></i>

Back

</a>

</div>

</form>

</div>

</div>

</div>

</div>

</div>
<?php include 'footer.php'; ?>

<script>

document.addEventListener("DOMContentLoaded", function(){

    const fromAccount = document.querySelector("select[name='from_account']");
    const toAccount   = document.querySelector("select[name='to_account']");
    const amount      = document.querySelector("input[name='amount']");

    function validateVoucher()
    {
        if(fromAccount.value !== "" &&
           toAccount.value !== "" &&
           fromAccount.value === toAccount.value)
        {
            alert("Transfer From and Transfer To cannot be the same account.");

            toAccount.value = "";

            toAccount.focus();
        }
    }

    fromAccount.addEventListener("change", validateVoucher);
    toAccount.addEventListener("change", validateVoucher);

    amount.addEventListener("input", function(){

        if(parseFloat(this.value) < 0)
        {
            this.value = "";
        }

    });

});

</script>